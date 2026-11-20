<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_variants', function (Blueprint $table) {
            $table->string('model', 80)->default('Gamis Amina');
            $table->string('color', 40)->default('Black');
            $table->dropUnique(['size']);
        });
    }

    public function down(): void
    {
        if (DB::table('inventory_variants')->where('model', '!=', 'Gamis Amina')->orWhere('color', '!=', 'Black')->exists()) {
            throw new RuntimeException('Rolling back would discard custom inventory product details.');
        }

        Schema::table('inventory_variants', function (Blueprint $table) {
            $table->unique('size');
            $table->dropColumn(['model', 'color']);
        });
    }
};
