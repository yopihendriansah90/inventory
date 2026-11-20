<?php

use App\Models\InventoryDispatch;
use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use App\Models\InventoryVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('role dashboards open filtered queues and show current inventory totals', function () {
    $this->seed();
    $qc = User::where('role', 'qc')->firstOrFail();
    $warehouse = User::where('role', 'warehouse')->firstOrFail();
    $cs = User::where('role', 'cs')->firstOrFail();
    $variant = InventoryVariant::firstOrFail();
    $today = now('Asia/Jakarta')->toDateString();
    $data = [
        'inventory_vendor_id' => InventoryVendor::firstOrFail()->id,
        'reference' => 'TODAY-INSPECTION',
        'received_on' => $today,
        'lines' => [['inventory_variant_id' => $variant->id, 'received_quantity' => 8, 'failed_quantity' => 2]],
    ];

    $this->actingAs($qc)->post(route('inventory.ticket.store'), $data)->assertSessionHasNoErrors();
    $ticket = InventoryTicket::firstOrFail();
    $data['reference'] = 'PAST-INSPECTION';
    $data['received_on'] = now('Asia/Jakarta')->subDay()->toDateString();
    $this->post(route('inventory.ticket.store'), $data)->assertSessionHasNoErrors();

    $this->get(route('inventory.home'))->assertOk()
        ->assertViewHas('metrics', fn ($metrics) => array_column($metrics, 1) === [1, 6, 2])
        ->assertSee($ticket->code());
    $this->get(route('inventory.qc', ['date' => $today, 'result' => 'failed']))
        ->assertOk()->assertSee('TODAY-INSPECTION')->assertDontSee('PAST-INSPECTION');

    $this->actingAs($warehouse)->post(route('inventory.ticket.confirm', $ticket))->assertSessionHasNoErrors();
    $this->get(route('inventory.receiving', ['pending' => 1]))->assertOk()
        ->assertSee('Awaiting warehouse receipt')->assertDontSee($ticket->code());

    $purchase = [
        'sales_reference' => 'SALE-DASHBOARD',
        'recipient_name' => 'Pembeli Dashboard',
        'recipient_phone' => '081200000011',
        'recipient_address' => 'Alamat Contoh',
        'lines' => [['inventory_variant_id' => $variant->id, 'quantity' => 3]],
    ];
    $this->actingAs($cs)->post(route('inventory.dispatch.store'), $purchase)->assertSessionHasNoErrors();
    $dispatch = InventoryDispatch::firstOrFail();
    $this->get(route('inventory.home'))->assertOk()
        ->assertViewHas('metrics', fn ($metrics) => array_column($metrics, 1) === [8, 3, 11])
        ->assertSee($dispatch->code())
        ->assertDontSee('081200000011');
    $this->get(route('inventory.stock', ['preview' => 1, 'q' => $variant->displaySku()]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()->assertHeader('X-Stock-Results', '1')->assertSee($variant->displaySku());

    $this->actingAs($warehouse)->post(route('inventory.dispatch.advance', [$dispatch, 'start']))->assertSessionHasNoErrors();
    $this->post(route('inventory.dispatch.advance', [$dispatch, 'pack']))->assertSessionHasNoErrors();
    $this->get(route('inventory.home'))->assertOk()->assertSee('Dispatch queue')
        ->assertViewHas('metrics', fn ($metrics) => array_column($metrics, 1) === [1, 0, 0, 1])
        ->assertSee(route('inventory.dispatch.index', ['status' => InventoryDispatch::READY]))
        ->assertSee($dispatch->code());
    $this->get(route('inventory.dispatch.index', ['status' => InventoryDispatch::READY, 'q' => 'Pembeli']), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()->assertSee($dispatch->code());
    $this->get(route('inventory.dispatch.index', ['status' => InventoryDispatch::PACKING]))
        ->assertOk()->assertDontSee($dispatch->code());
});
