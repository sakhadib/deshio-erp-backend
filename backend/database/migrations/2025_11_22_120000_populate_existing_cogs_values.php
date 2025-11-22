<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all existing order_items with NULL cogs
        // Calculate COGS from batch cost_price * quantity
        
        $affectedRows = DB::update("
            UPDATE order_items oi
            JOIN product_batches pb ON oi.product_batch_id = pb.id
            SET oi.cogs = ROUND(pb.cost_price * oi.quantity, 2)
            WHERE oi.cogs IS NULL
        ");
        
        \Log::info("Updated {$affectedRows} order items with calculated COGS");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set COGS back to NULL for items that were updated
        DB::table('order_items')
            ->whereNotNull('cogs')
            ->update(['cogs' => null]);
    }
};
