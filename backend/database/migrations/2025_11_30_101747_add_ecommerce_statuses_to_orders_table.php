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
        // PostgreSQL requires dropping and recreating enum types
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("ALTER TABLE orders ALTER COLUMN status TYPE VARCHAR(50)");
        DB::statement("
            ALTER TABLE orders ADD CONSTRAINT orders_status_check 
            CHECK (status IN (
                'pending', 
                'pending_assignment', 
                'assigned_to_store', 
                'picking', 
                'ready_for_shipment',
                'confirmed', 
                'processing', 
                'ready_for_pickup', 
                'shipped', 
                'delivered', 
                'cancelled', 
                'refunded'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("
            ALTER TABLE orders ADD CONSTRAINT orders_status_check 
            CHECK (status IN (
                'pending', 
                'confirmed', 
                'processing', 
                'ready_for_pickup', 
                'shipped', 
                'delivered', 
                'cancelled', 
                'refunded'
            ))
        ");
    }
};
