<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_vendor_id')->constrained();
            $table->string('reference', 80);
            $table->date('received_on');
            $table->foreignId('inspected_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['inventory_vendor_id', 'reference']);
        });

        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->foreignId('inventory_ticket_id')->nullable()->constrained();
        });
    }

    public function down(): void
    {
        if (DB::table('inventory_tickets')->exists()) {
            throw new RuntimeException('Ticket data must be preserved before rolling back this migration.');
        }

        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_ticket_id');
        });
        Schema::dropIfExists('inventory_tickets');
    }
};
