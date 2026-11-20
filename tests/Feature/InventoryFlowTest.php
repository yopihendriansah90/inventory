<?php

use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use App\Models\InventoryVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('QC can download a SKU QR and open the matching variant', function () {
    $medium = InventoryVariant::where('size', 'M')->firstOrFail();
    $this->actingAs(User::where('role', 'qc')->firstOrFail())
        ->get(route('inventory.variants.qr', $medium))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    $this->get(route('inventory.variants.qr', [$medium, 'download' => 1]))
        ->assertOk()->assertDownload('sku-amn-htm-m.svg')->assertSee('<svg', false);
    $this->get(route('inventory.qc', ['sku' => $medium->sku]))
        ->assertViewHas('selectedSku', $medium->sku);
});

test('new sizes of the demo product retain the established SKU format', function () {
    $qc = User::where('role', 'qc')->firstOrFail();
    $this->actingAs($qc)->post(route('inventory.variants.store'), ['model' => 'Gamis Amina', 'color' => 'Black', 'size' => 'xl'])
        ->assertRedirect(route('inventory.qc', ['sku' => 'AMN-HTM-XL']));
    $variant = InventoryVariant::where('size', 'XL')->firstOrFail();
    expect($variant->displaySku())->toBe('AMN-HTM-XL')->and($variant->stock)->toBe(0);
});

test('QC can register a custom product at an existing size and carry it through warehouse and CS', function () {
    $product = ['model' => 'Tunik Safira', 'color' => 'Moka', 'size' => 'm'];
    $this->actingAs(User::where('role', 'qc')->firstOrFail())
        ->post(route('inventory.variants.store'), $product)
        ->assertSessionHasNoErrors();
    $variant = InventoryVariant::where('model', 'Tunik Safira')->firstOrFail();
    expect($variant->stock)->toBe(0)->and($variant->size)->toBe('M');

    $this->post(route('inventory.variants.store'), ['model' => 'tunik  safira', 'color' => 'MOKA', 'size' => 'M'])
        ->assertRedirect(route('inventory.qc', ['sku' => $variant->displaySku()]));
    expect(InventoryVariant::count())->toBe(3);
    $this->get(route('inventory.qc', ['sku' => $variant->sku]))
        ->assertOk()->assertViewHas('selectedSku', $variant->sku);
    $this->get(route('inventory.variants.qr', $variant))->assertHeader('Content-Type', 'image/svg+xml');

    $this->post(route('inventory.ticket.store'), [
        'inventory_vendor_id' => InventoryVendor::firstOrFail()->id,
        'reference' => 'SJ-SAFIRA',
        'received_on' => '2026-11-20',
        'lines' => [[
            'inventory_variant_id' => $variant->id,
            'received_quantity' => 4,
            'failed_quantity' => 1,
        ]],
    ])->assertSessionHasNoErrors();
    $ticket = InventoryTicket::firstOrFail();
    expect($variant->fresh()->stock)->toBe(0);

    $this->actingAs(User::where('role', 'warehouse')->firstOrFail())
        ->get(route('inventory.ticket.receive', $ticket))->assertSee('Tunik Safira')->assertSee('Moka');
    $this->post(route('inventory.ticket.confirm', $ticket))->assertRedirect(route('inventory.ticket.receive', $ticket));

    $this->actingAs(User::where('role', 'cs')->firstOrFail())
        ->get(route('inventory.stock', ['q' => 'safira moka M']))
        ->assertSee($variant->displaySku())->assertSee('3 pcs')->assertDontSee('AMN-HTM-M');
    expect(InventoryVariant::where('sku', 'AMN-HTM-M')->firstOrFail()->stock)->toBe(5);
});

test('the QC modal can create an option and receive inline validation errors', function () {
    $url = route('inventory.variants.store');
    $product = ['model' => 'Khimar Nisa', 'color' => 'Olive', 'size' => 'L'];
    $this->actingAs(User::where('role', 'qc')->firstOrFail())
        ->postJson($url, ['model' => 'Khimar Nisa'])
        ->assertUnprocessable()->assertJsonValidationErrors(['color', 'size']);

    $option = $this->postJson($url, $product)->assertCreated()->assertJsonStructure(['id', 'label', 'qrUrl'])->json();
    $this->postJson($url, $product)->assertOk()->assertExactJson($option);
    $this->actingAs(User::where('role', 'cs')->firstOrFail())->postJson($url, $product)->assertForbidden();
});

test('short SKUs stay unique and the old code still finds the same variant', function () {
    $url = route('inventory.variants.store');
    $product = ['model' => 'Celana Panjang', 'color' => 'Hitam', 'size' => 'L'];
    $this->actingAs(User::where('role', 'qc')->firstOrFail());
    $first = $this->postJson($url, $product)->assertCreated()->json();
    $second = $this->postJson($url, ['model' => 'Celana Pesta'] + $product)->assertCreated()->json();

    expect($first['label'])->toStartWith('CEL-HIT-L · ');
    expect($second['label'])->toStartWith('CEL-HIT-L-02 · ');
    $this->postJson($url, $product)->assertOk()->assertExactJson($first);

    $variant = InventoryVariant::findOrFail($first['id']);
    $this->get(route('inventory.qc', ['sku' => $variant->sku]))
        ->assertOk()->assertSee('CEL-HIT-L');
    $this->get(route('inventory.variants.qr', [$variant, 'download' => 1]))
        ->assertDownload('sku-cel-hit-l.svg');

    $this->actingAs(User::where('role', 'cs')->firstOrFail())
        ->get(route('inventory.stock', ['q' => $variant->sku]))
        ->assertOk()->assertSee('CEL-HIT-L')->assertDontSee('Celana Pesta');
});

test('live stock search returns only the matching table and keeps the same access rules', function () {
    $headers = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html'];
    $url = route('inventory.stock', ['q' => 'gamis black M']);
    $this->get($url, $headers)->assertRedirect(route('signin'));

    $this->actingAs(User::where('role', 'cs')->firstOrFail());
    $this->get($url, $headers)->assertOk()->assertHeader('X-Stock-Results', '1')->assertSee('AMN-HTM-M')
        ->assertDontSee('AMN-HTM-L')->assertDontSee('<!DOCTYPE html>', false);
    $this->get($url)->assertOk()->assertSee('<!DOCTYPE html>', false)->assertSee('AMN-HTM-M')->assertDontSee('AMN-HTM-L');

    $this->actingAs(User::where('role', 'qc')->firstOrFail());
    $this->get($url, $headers)->assertForbidden();
});
