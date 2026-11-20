<?php

use App\Models\InventoryVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_variants', function (Blueprint $table) {
            $table->string('short_sku', 80)->nullable()->unique();
        });

        DB::transaction(function () {
            // Reserve existing readable codes before assigning abbreviations.
            DB::table('inventory_variants')->select('id', 'sku')->chunkById(100, function ($variants) {
                foreach ($variants as $variant) {
                    if (! preg_match('/-[0-9A-F]{16}\z/', $variant->sku)) {
                        DB::table('inventory_variants')->where('id', $variant->id)->update(['short_sku' => $variant->sku]);
                    }
                }
            });

            DB::table('inventory_variants')->whereNull('short_sku')->chunkById(100, function ($variants) {
                foreach ($variants as $variant) {
                    $base = InventoryVariant::shortSkuBase($variant->model, $variant->color, $variant->size, $variant->sku);
                    DB::table('inventory_variants')->where('id', $variant->id)
                        ->update(['short_sku' => InventoryVariant::availableShortSku($base)]);
                }
            });
        });
    }

    public function down(): void
    {
        Schema::table('inventory_variants', function (Blueprint $table) {
            $table->dropUnique(['short_sku']);
            $table->dropColumn('short_sku');
        });
    }
};
