<?php

use App\Models\InventoryDispatch;
use App\Models\InventoryVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cs = User::factory()->create(['role' => 'cs']);
    $this->warehouse = User::factory()->create(['role' => 'warehouse']);
    $this->qc = User::factory()->create(['role' => 'qc']);
    $this->small = InventoryVariant::register(['model' => 'Gamis', 'color' => 'Hitam', 'size' => 'S', 'stock' => 10]);
    $this->large = InventoryVariant::register(['model' => 'Gamis', 'color' => 'Hitam', 'size' => 'L', 'stock' => 4]);
    $this->requestData = [
        'sales_reference' => ' sale-001 ',
        'recipient_name' => 'Nadia Demo',
        'recipient_phone' => '081234567890',
        'recipient_address' => "Jl. Contoh 123\nBandung",
        'lines' => [
            $this->small->id => ['inventory_variant_id' => (string) $this->small->id, 'quantity' => '6'],
            $this->large->id => ['inventory_variant_id' => (string) $this->large->id, 'quantity' => '2'],
        ],
    ];
});

test('CS reserves a multi-SKU ticket and the warehouse dispatches it once after opening its QR', function () {
    $store = route('inventory.dispatch.store');
    $this->actingAs($this->warehouse)->post($store, $this->requestData)->assertForbidden();
    $this->actingAs($this->cs)->get(route('inventory.dispatch.create'))->assertOk()
        ->assertSeeInOrder(['Sales Ticket and Buyer Details', 'Purchased Items', 'Preview and Print'])
        ->assertSee($this->small->displaySku());
    $this->post($store, $this->requestData)->assertSessionHasNoErrors();

    $ticket = InventoryDispatch::firstOrFail();
    $detail = route('inventory.dispatch.show', $ticket);
    $scanned = route('inventory.dispatch.show', ['dispatch' => $ticket, 'scan' => 1]);
    $printQr = route('inventory.dispatch.print', [$ticket, 'qr']);
    $printReport = route('inventory.dispatch.print', [$ticket, 'report']);
    $this->get($detail)->assertOk()->assertSee('Preview and Print')->assertSee('Print QR Label')->assertSee('Print Report');
    $this->get($printQr)->assertOk()->assertSee($ticket->code())->assertSee(route('inventory.dispatch.qr', $ticket))
        ->assertDontSee($this->requestData['recipient_phone']);
    $this->get(route('inventory.dispatch.qr', $ticket))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    $this->get($printReport)->assertOk()->assertSee('Outbound Dispatch Report')
        ->assertSee($this->requestData['recipient_phone'])->assertSee($this->small->displaySku());
    expect($ticket->sales_reference)->toBe('SALE-001')
        ->and($ticket->lines)->toHaveCount(2)
        ->and($this->small->fresh()->stock)->toBe(10)
        ->and($this->small->fresh()->reserved_stock)->toBe(6)
        ->and($this->small->fresh()->availableStock())->toBe(4);

    $this->postJson($store, $this->requestData)->assertUnprocessable()->assertJsonValidationErrors('sales_reference');
    foreach (['SALE-001', 'Nadia', $ticket->code()] as $term) {
        $this->get(route('inventory.dispatch.index', ['q' => $term]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertHeader('X-Dispatch-Results', '1')->assertSee($ticket->code())
            ->assertDontSee($this->requestData['recipient_phone'])->assertDontSee('Jl. Contoh 123');
    }
    $this->get(route('inventory.stock'))->assertOk()->assertSee('Reserved Stock')->assertSee('Available Stock');
    $this->get($detail)->assertOk()->assertSee($this->requestData['recipient_phone']);
    $this->post(route('inventory.dispatch.advance', [$ticket, 'start']))->assertForbidden();
    $this->get(route('inventory.dispatch.scan'))->assertForbidden();
    $this->actingAs($this->qc)->get($detail)->assertForbidden();
    $this->get($printQr)->assertForbidden();
    $this->get($printReport)->assertForbidden();

    $this->actingAs($this->warehouse)->get(route('inventory.dispatch.scan'))->assertOk()
        ->assertSee('OUT-000001')->assertSee('Point the camera at the outbound ticket QR.')
        ->assertSee(route('inventory.receiving.scan'))->assertDontSee('Point the camera at the receiving ticket QR.');
    $this->postJson(route('inventory.dispatch.advance', [$ticket, 'ship']))->assertUnprocessable();
    $this->post(route('inventory.dispatch.advance', [$ticket, 'start']))->assertRedirect($detail);
    expect($ticket->fresh()->status)->toBe(InventoryDispatch::PACKING);
    $this->get($detail)->assertDontSee('Confirm Packing Complete')->assertSee('Dispatch ticket QR')
        ->assertSeeInOrder(['2 SKUs', 'Print QR Label', 'Items to dispatch', 'Sales Ticket and Buyer Details']);
    $this->get(route('inventory.dispatch.qr', ['dispatch' => $ticket, 'download' => 1]))
        ->assertOk()->assertDownload(strtolower($ticket->code()).'.svg');

    foreach ([$scanned, $ticket->code()] as $value) {
        $this->getJson(route('inventory.dispatch.lookup', ['q' => $value]))->assertExactJson(['url' => $scanned]);
    }

    $this->get($scanned)->assertOk()->assertSee('Confirm Packing Complete');
    $this->post(route('inventory.dispatch.advance', [$ticket, 'pack']))->assertRedirect($detail);
    expect($ticket->fresh()->status)->toBe(InventoryDispatch::READY)
        ->and($this->small->fresh()->stock)->toBe(10)
        ->and($this->small->fresh()->reserved_stock)->toBe(6);

    $this->get($detail)->assertOk()->assertSee('Scan QR to Confirm Dispatch')->assertDontSee('Confirm Goods Dispatched');
    $this->get($scanned)->assertOk()->assertSee('Confirm Goods Dispatched')
        ->assertSee(route('inventory.dispatch.advance', [$ticket, 'ship']));
    $this->post(route('inventory.dispatch.advance', [$ticket, 'ship']))->assertRedirect($detail);
    $this->postJson(route('inventory.dispatch.advance', [$ticket, 'ship']))->assertUnprocessable();
    $this->get($scanned)->assertOk()->assertSee('Shipped')->assertDontSee('Confirm Goods Dispatched');
    expect($this->small->fresh()->stock)->toBe(4)
        ->and($this->large->fresh()->stock)->toBe(2)
        ->and($this->small->fresh()->reserved_stock)->toBe(0)
        ->and($ticket->events()->pluck('status')->all())->toBe(['pending', 'packing', 'ready', 'shipped'])
        ->and($ticket->events()->pluck('user_id')->all())->toBe([$this->cs->id, $this->warehouse->id, $this->warehouse->id, $this->warehouse->id]);
    $this->actingAs($this->cs)->postJson(route('inventory.dispatch.cancel', $ticket))->assertUnprocessable();
});

test('insufficient stock rolls back the entire ticket and cancellation releases reserved stock', function () {
    $store = route('inventory.dispatch.store');
    $this->actingAs($this->cs)->post($store, $this->requestData)->assertSessionHasNoErrors();

    $data = $this->requestData;
    $data['sales_reference'] = 'SALE-002';
    $data['lines'][$this->small->id]['quantity'] = 4;
    $data['lines'][$this->large->id]['quantity'] = 3;
    $this->postJson($store, $data)->assertUnprocessable()->assertJsonValidationErrors("lines.{$this->large->id}.quantity");
    expect(InventoryDispatch::count())->toBe(1)
        ->and($this->small->fresh()->reserved_stock)->toBe(6)
        ->and($this->large->fresh()->reserved_stock)->toBe(2);

    $data['lines'][$this->small->id]['quantity'] = 5;
    $this->postJson($store, $data)->assertUnprocessable()->assertJsonValidationErrors("lines.{$this->small->id}.quantity");

    $ticket = InventoryDispatch::firstOrFail();
    $this->post(route('inventory.dispatch.cancel', $ticket))->assertRedirect(route('inventory.dispatch.show', $ticket));
    expect($this->small->fresh()->availableStock())->toBe(10)
        ->and($this->large->fresh()->availableStock())->toBe(4)
        ->and($ticket->events()->get()->last()->status)->toBe(InventoryDispatch::CANCELLED);
    $this->post($store, $data)->assertSessionHasNoErrors();
});
