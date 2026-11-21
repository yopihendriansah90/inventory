<?php

test('guests can open the role guides in a new tab from sign in', function () {
    $this->get(route('signin'))->assertOk()->assertSee(
        'href="'.route('inventory.guide').'" target="_blank" rel="noopener"',
        false,
    );

    $this->get(route('inventory.guide'))->assertOk()
        ->assertSee('Inventory User Guide')
        ->assertSee('id="qc"', false)
        ->assertSee('id="gudang"', false)
        ->assertSee('id="cs"', false)
        ->assertSee('inventory-guide/qc-qr.png', false)
        ->assertSee('inventory-guide/gudang-barang-keluar.png', false)
        ->assertSee('inventory-guide/cs-lacak.png', false);

    $this->withCookie('inventory_locale', 'id')->get(route('inventory.guide'))->assertOk()
        ->assertSee('Panduan Penggunaan Inventory');
});
