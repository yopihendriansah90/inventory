<?php

use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use App\Models\InventoryVendor;
use App\Models\User;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('one ticket groups the SKUs for one vendor and warehouse confirms them once', function () {
    $qc = User::where('role', 'qc')->firstOrFail();
    $warehouse = User::where('role', 'warehouse')->firstOrFail();
    $cs = User::where('role', 'cs')->firstOrFail();
    $vendor = InventoryVendor::firstOrFail();
    $medium = InventoryVariant::where('size', 'M')->firstOrFail();
    $large = InventoryVariant::where('size', 'L')->firstOrFail();
    $data = [
        'inventory_vendor_id' => $vendor->id,
        'reference' => 'SJ-SHARED',
        'received_on' => '2026-11-20',
        'lines' => [
            ['inventory_variant_id' => $medium->id, 'received_quantity' => 20, 'failed_quantity' => 2, 'notes' => 'Loose stitching'],
            ['inventory_variant_id' => $large->id, 'received_quantity' => 10, 'failed_quantity' => 0],
        ],
    ];

    $this->actingAs($qc)->post(route('inventory.ticket.store'), $data)->assertSessionHasNoErrors();
    $ticket = InventoryTicket::firstOrFail();
    $this->get(route('inventory.ticket.show', $ticket))->assertOk()->assertSee($ticket->code())->assertSee($vendor->code())->assertSee('SKU QR');
    $this->get(route('inventory.qc', ['new' => 1]))->assertOk()
        ->assertSee('Inspect Items')->assertDontSee('Inspection tickets')->assertDontSee('SKU QR');
    expect($ticket->lines)->toHaveCount(2);
    $this->post(route('inventory.ticket.store'), $data)->assertSessionHasErrors('reference');
    $this->get(route('inventory.ticket.report', [$ticket, 'vendor']))->assertOk()->assertSee('Loose stitching');
    $this->get(route('inventory.ticket.report', [$ticket, 'warehouse']))->assertOk()->assertDontSee('Loose stitching');
    $this->get(route('inventory.ticket.qr', [$ticket, 'download' => 1]))
        ->assertOk()->assertDownload(Str::lower($ticket->code()).'.svg');

    $newVendor = $this->postJson(route('inventory.vendors.store'), ['name' => 'Zahra Production'])->assertCreated()->json();
    $this->postJson(route('inventory.vendors.store'), ['name' => 'zahra  production'])->assertOk()->assertExactJson($newVendor);
    $data['inventory_vendor_id'] = $newVendor['id'];
    $this->post(route('inventory.ticket.store'), $data)->assertSessionHasNoErrors();
    expect(InventoryTicket::count())->toBe(2);
    $this->get(route('inventory.qc'))->assertOk()->assertSee('Search receiving tickets')->assertSee(route('inventory.qc.scan'));
    $results = ['X-Requested-With' => 'XMLHttpRequest'];
    $this->get(route('inventory.qc', ['q' => strtolower($ticket->code())]), $results)
        ->assertOk()->assertHeader('X-QC-Ticket-Results', '1')->assertSee($ticket->code())->assertDontSee('Zahra Production');
    $this->get(route('inventory.qc', ['q' => 'zahra']), $results)
        ->assertOk()->assertSee('Zahra Production')->assertDontSee($ticket->code());
    $this->get(route('inventory.qc', ['q' => 'sj-shared', 'result' => 'failed']), $results)
        ->assertOk()->assertSee($ticket->code())->assertSee('Zahra Production');
    expect($medium->fresh()->stock)->toBe(5)->and($large->fresh()->stock)->toBe(0);

    $this->actingAs($cs)->get(route('inventory.ticket.receive', $ticket))->assertForbidden();
    $this->post(route('inventory.ticket.confirm', $ticket))->assertForbidden();
    $this->post(route('inventory.ticket.store'), $data)->assertForbidden();
    $this->get(route('inventory.ticket.qr', $ticket))->assertForbidden();

    $this->actingAs($warehouse)->get(route('inventory.receiving'))->assertSee($ticket->code())->assertSee('Zahra Production')->assertDontSee('SJ-SHARED');
    $this->get(route('inventory.ticket.receive', $ticket))->assertOk()->assertSee('AMN-HTM-M')->assertSee('AMN-HTM-L')
        ->assertSee('SJ-SHARED')->assertDontSee('Loose stitching')->assertDontSee('SKU QR');
    $this->post('/inventory/receiving/'.$ticket->lines->first()->id.'/confirm')->assertNotFound();
    $this->post(route('inventory.ticket.confirm', $ticket))->assertRedirect(route('inventory.ticket.receive', $ticket));
    $this->post(route('inventory.ticket.confirm', $ticket))->assertRedirect(route('inventory.ticket.receive', $ticket));
    expect($medium->fresh()->stock)->toBe(23)->and($large->fresh()->stock)->toBe(10);
    expect($ticket->fresh()->confirmed_by)->toBe($warehouse->id);

    $this->seed(InventoryCatalogSeeder::class);
    expect($medium->fresh()->stock)->toBe(23);
});

test('warehouse can open a receiving ticket from a QR link or ticket number', function () {
    $qc = User::where('role', 'qc')->firstOrFail();
    $warehouse = User::where('role', 'warehouse')->firstOrFail();
    $variant = InventoryVariant::firstOrFail();
    $data = [
        'inventory_vendor_id' => InventoryVendor::firstOrFail()->id,
        'reference' => 'SJ-SCAN',
        'received_on' => '2026-11-20',
        'lines' => [['inventory_variant_id' => $variant->id, 'received_quantity' => 3, 'failed_quantity' => 0]],
    ];
    $this->actingAs($qc)->post(route('inventory.ticket.store'), $data)->assertSessionHasNoErrors();
    $ticket = InventoryTicket::firstOrFail();
    $lookup = route('inventory.receiving.lookup');
    $detail = route('inventory.ticket.receive', $ticket);
    $qcDetail = route('inventory.ticket.show', $ticket);
    $qcLookup = route('inventory.qc.lookup');
    $this->get(route('inventory.receiving.scan'))->assertForbidden();
    $this->getJson($lookup.'?'.http_build_query(['q' => $ticket->code()]))->assertForbidden();
    $this->get(route('inventory.qc.scan'))->assertOk()->assertSee('Scan QC Receiving Ticket QR');
    $this->getJson($qcLookup.'?'.http_build_query(['q' => $detail]))->assertOk()->assertExactJson(['url' => $qcDetail]);
    $this->get($qcLookup.'?'.http_build_query(['q' => $ticket->code()]))->assertRedirect($qcDetail);
    $this->getJson($qcLookup.'?'.http_build_query(['q' => 'OUT-000001']))->assertUnprocessable()->assertJsonValidationErrors('q');

    $this->actingAs($warehouse)->get(route('inventory.receiving.scan'))->assertOk()->assertSee('data-warehouse-nav', false)
        ->assertSee('Point the camera at the receiving ticket QR.')->assertSee(route('inventory.dispatch.scan'));
    $this->getJson($qcLookup.'?'.http_build_query(['q' => $ticket->code()]))->assertForbidden();
    $this->getJson($lookup.'?'.http_build_query(['q' => $detail]))->assertOk()->assertExactJson(['url' => $detail]);
    $this->get($lookup.'?'.http_build_query(['q' => strtolower($ticket->code())]))->assertRedirect($detail);
    $this->getJson($lookup.'?'.http_build_query(['q' => 'https://example.com/other']))->assertUnprocessable()->assertJsonValidationErrors('q');
    $this->getJson($lookup.'?'.http_build_query(['q' => 'QC-999999']))->assertUnprocessable()->assertJsonValidationErrors('q');
    expect($ticket->fresh()->confirmed_at)->toBeNull();
});

test('a fully rejected ticket produces only the vendor report and keeps stock unchanged', function () {
    $medium = InventoryVariant::where('size', 'M')->firstOrFail();
    $data = [
        'inventory_vendor_id' => InventoryVendor::firstOrFail()->id,
        'reference' => 'SJ-FAILED',
        'received_on' => '2026-11-20',
        'lines' => [['inventory_variant_id' => $medium->id, 'received_quantity' => 5, 'failed_quantity' => 6]],
    ];

    $this->actingAs(User::where('role', 'qc')->firstOrFail())
        ->post(route('inventory.ticket.store'), $data)->assertSessionHasErrors('lines.0.failed_quantity');
    $data['lines'][0]['failed_quantity'] = 5;
    $this->post(route('inventory.ticket.store'), $data)->assertSessionHasNoErrors();
    $ticket = InventoryTicket::firstOrFail();
    $this->get(route('inventory.ticket.report', [$ticket, 'vendor']))->assertOk()->assertSee('Failed QC');
    $this->get(route('inventory.ticket.report', [$ticket, 'warehouse']))->assertNotFound();
    $this->get(route('inventory.ticket.qr', $ticket))->assertNotFound();

    $this->actingAs(User::where('role', 'warehouse')->firstOrFail())
        ->get(route('inventory.receiving'))->assertDontSee($ticket->code());
    $this->getJson(route('inventory.receiving.lookup', ['q' => $ticket->code()]))->assertUnprocessable()->assertJsonValidationErrors('q');
    $this->post(route('inventory.ticket.confirm', $ticket))->assertConflict();
    expect($medium->fresh()->stock)->toBe(5);
});

test('legacy receipt cleanup preserves ticket lines and recorded stock', function () {
    $variant = InventoryVariant::where('size', 'M')->firstOrFail();
    $vendor = InventoryVendor::firstOrFail();
    $qc = User::where('role', 'qc')->firstOrFail();
    $warehouse = User::where('role', 'warehouse')->firstOrFail();
    $header = ['inventory_vendor_id' => $vendor->id, 'received_on' => '2026-11-20', 'inspected_by' => $qc->id];
    $line = $header + ['inventory_variant_id' => $variant->id, 'received_quantity' => 3, 'failed_quantity' => 0];
    $variant->update(['stock' => 8]);
    DB::table('inventory_receipts')->insert($line + [
        'reference' => 'OLD-CONFIRMED',
        'confirmed_at' => now(),
        'confirmed_by' => $warehouse->id,
    ]);
    DB::table('inventory_receipts')->insert($line + ['reference' => 'OLD-PENDING']);
    $ticket = InventoryTicket::create($header + ['reference' => 'ACTIVE-TICKET']);
    $ticket->lines()->create($line + ['reference' => $ticket->reference]);

    $cleanup = require database_path('migrations/2026_11_20_000006_remove_legacy_inventory_receipts.php');
    $cleanup->up();

    expect(DB::table('inventory_receipts')->whereNull('inventory_ticket_id')->count())->toBe(0);
    expect($ticket->lines()->count())->toBe(1);
    expect($variant->fresh()->stock)->toBe(8);

    $this->actingAs($qc)->get(route('inventory.qc'))->assertSee('Inspection tickets')->assertDontSee('Previous single-item receipts');
    $this->post('/inventory/qc')->assertMethodNotAllowed();
    $this->actingAs($warehouse)->get(route('inventory.receiving'))->assertSee($ticket->code())->assertDontSee('Previous single-item receipts');
});
