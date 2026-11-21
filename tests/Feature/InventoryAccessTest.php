<?php

use Database\Seeders\InventoryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo accounts sign in to their role dashboard and can only open permitted pages', function ($email, $dashboardTitle, $allowed, $mobileNav) {
    $this->seed(InventoryDemoSeeder::class);

    $this->get(route('inventory.home'))->assertRedirect(route('signin'));
    $this->get(route('signin'))->assertOk()->assertSee('<title>Sign In | Inventory</title>', false);

    $this->post(route('signin.store'), ['email' => $email, 'password' => 'admin'])
        ->assertRedirect(route('inventory.home'));

    $workspace = $this->get(route('inventory.home'))->assertOk()->assertSee($dashboardTitle);
    $workspace->assertSee($mobileNav, false)
        ->assertSee('data-inventory-topbar', false)
        ->assertSee('data-inventory-mobile-nav', false);
    foreach (['qc', 'receiving', 'stock'] as $page) {
        $url = route('inventory.'.$page);
        if (in_array($page, $allowed, true)) {
            $workspace->assertSee('href="'.$url.'"', false);
            $this->get($url)->assertOk();
        } else {
            $workspace->assertDontSee('href="'.$url.'"', false);
            $this->get($url)->assertForbidden();
        }
    }

    $this->post(route('logout'))->assertRedirect(route('signin'));
    $this->assertGuest();
    $this->get(route('inventory.home'))->assertRedirect(route('signin'));
})->with([
    'QC' => ['qc@mail.com', 'QC Dashboard', ['qc'], 'data-qc-nav'],
    'warehouse' => ['gudang@mail.com', 'Warehouse Dashboard', ['receiving', 'stock'], 'data-warehouse-nav'],
    'CS' => ['cs@mail.com', 'Customer Service Dashboard', ['stock'], 'data-cs-nav'],
]);

test('a wrong password keeps the user signed out', function () {
    $this->seed(InventoryDemoSeeder::class);

    $this->from(route('signin'))
        ->post(route('signin.store'), ['email' => 'qc@mail.com', 'password' => 'incorrect'])
        ->assertRedirect(route('signin'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
