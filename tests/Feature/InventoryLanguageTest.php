<?php

use App\Http\Middleware\SetInventoryLocale;
use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use App\Models\InventoryVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the sign-in language is saved independently of the login session', function () {
    $this->seed();
    $this->get(route('signin'))->assertOk()->assertSee('lang="en"', false)->assertSee('Bahasa Indonesia');
    $this->post(route('inventory.language'), ['locale' => 'id', 'return_to' => '/signin'])
        ->assertRedirect(route('signin'))->assertCookie(SetInventoryLocale::COOKIE, 'id');

    $this->withCookie(SetInventoryLocale::COOKIE, 'id');
    $this->get(route('signin'))->assertOk()->assertSee('lang="id"', false)
        ->assertSee('<title>Masuk | Stok Barang</title>', false)->assertSee('Kata sandi');
    $this->from(route('signin'))->post(route('signin.store'), ['email' => 'qc@mail.com', 'password' => 'wrong'])
        ->assertRedirect(route('signin'))->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    $this->post(route('signin.store'), ['email' => 'qc@mail.com', 'password' => 'admin'])->assertRedirect(route('inventory.home'));
    $this->get(route('inventory.qc'))->assertOk()->assertSee('Daftar Tiket Pemeriksaan');
    $this->post(route('logout'))->assertRedirect(route('signin'))->assertCookieMissing(SetInventoryLocale::COOKIE);
    $this->get(route('signin'))->assertSee('Kata sandi');

    $this->post(route('inventory.language'), ['locale' => 'en', 'return_to' => '//example.com'])
        ->assertRedirect(route('inventory.home'))->assertCookie(SetInventoryLocale::COOKIE, 'en');
    $this->postJson(route('inventory.language'), ['locale' => 'fr'])->assertUnprocessable()->assertJsonValidationErrors('locale');
});

test('inventory screens, validation, and print reports use the selected language', function () {
    $this->seed();
    $this->withCookie(SetInventoryLocale::COOKIE, 'id')->withCredentials();
    $this->actingAs(User::where('role', 'qc')->firstOrFail());
    $this->get(route('inventory.qc', ['new' => 1]))->assertOk()->assertSee('Periksa Barang')
        ->assertSee('Nomor surat jalan')->assertDontSee('name="locale"', false);
    $this->postJson(route('inventory.vendors.store'), [])->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'Isi nama vendor.');

    $variant = InventoryVariant::where('size', 'M')->firstOrFail();
    $this->post(route('inventory.ticket.store'), [
        'inventory_vendor_id' => InventoryVendor::firstOrFail()->id,
        'reference' => 'SJ-LOCALE',
        'received_on' => '2026-08-20',
        'lines' => [['inventory_variant_id' => $variant->id, 'received_quantity' => 5, 'failed_quantity' => 1]],
    ])->assertSessionHasNoErrors();
    $ticket = InventoryTicket::firstOrFail();
    $this->get(route('inventory.ticket.report', [$ticket, 'vendor']))->assertOk()
        ->assertSee('Berita Acara Hasil Pemeriksaan Vendor')->assertSee('20 Agt 2026');

    $this->actingAs(User::where('role', 'warehouse')->firstOrFail())
        ->get(route('inventory.receiving'))->assertOk()->assertSee('Penerimaan Gudang')->assertSee('Menunggu konfirmasi gudang');
    $this->actingAs(User::where('role', 'cs')->firstOrFail())
        ->get(route('inventory.stock'))->assertOk()->assertSee('Stok Siap Jual')->assertSee('5 buah');
    $this->get('/ecommerce')->assertOk()->assertSee('lang="en"', false);
});
