<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('reserved_stock')->default(0);
        });

        Schema::create('inventory_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('sales_reference', 80)->unique();
            $table->string('recipient_name', 120);
            $table->string('recipient_phone', 32);
            $table->text('recipient_address');
            $table->string('status', 16)->index();
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('inventory_dispatch_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_dispatch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_variant_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unique(['inventory_dispatch_id', 'inventory_variant_id'], 'dispatch_lines_ticket_variant_unique');
        });

        Schema::create('inventory_dispatch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_dispatch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 16);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_dispatch_events');
        Schema::dropIfExists('inventory_dispatch_lines');
        Schema::dropIfExists('inventory_dispatches');
        Schema::table('inventory_variants', fn (Blueprint $table) => $table->dropColumn('reserved_stock'));
    }
};
