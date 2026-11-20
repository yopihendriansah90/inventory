<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_receipts')->whereNull('inventory_ticket_id')->delete();
    }

    public function down(): void
    {
        // Deleted legacy receipts cannot be restored.
    }
};
