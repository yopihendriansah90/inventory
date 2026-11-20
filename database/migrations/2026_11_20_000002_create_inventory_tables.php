<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('inventory_variants', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('size', 12)->unique();
            $table->unsignedBigInteger('stock')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_variant_id')->constrained();
            $table->foreignId('inventory_vendor_id')->constrained();
            $table->string('reference', 80);
            $table->date('received_on');
            $table->unsignedInteger('received_quantity');
            $table->unsignedInteger('failed_quantity');
            $table->text('notes')->nullable();
            $table->foreignId('inspected_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['inventory_vendor_id', 'inventory_variant_id', 'reference'], 'inventory_receipts_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receipts');
        Schema::dropIfExists('inventory_variants');
        Schema::dropIfExists('inventory_vendors');
    }
};
