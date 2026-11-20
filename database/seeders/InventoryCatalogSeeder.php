<?php

namespace Database\Seeders;

use App\Models\InventoryVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            DB::table('inventory_vendors')->insertOrIgnore(['name' => 'Amina Garment']);
            $product = ['model' => 'Gamis Amina', 'color' => 'Black'];
            InventoryVariant::firstOrCreate(['sku' => 'AMN-HTM-M'], $product + ['short_sku' => 'AMN-HTM-M', 'size' => 'M', 'stock' => 5]);
            InventoryVariant::firstOrCreate(['sku' => 'AMN-HTM-L'], $product + ['short_sku' => 'AMN-HTM-L', 'size' => 'L', 'stock' => 0]);
        }
    }
}
