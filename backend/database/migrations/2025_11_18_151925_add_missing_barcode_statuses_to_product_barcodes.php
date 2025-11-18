<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add 'available' and 'sold' status values to product_barcodes.current_status enum
     */
    public function up(): void
    {
        // MySQL doesn't allow direct ALTER of ENUM, so we need to use raw SQL
        DB::statement("ALTER TABLE `product_barcodes` 
            MODIFY COLUMN `current_status` ENUM(
                'available',         -- Product available for sale (replaces in_warehouse/in_shop)
                'in_warehouse',      -- Stored in warehouse
                'in_shop',           -- Available in retail shop
                'on_display',        -- Currently displayed on shop floor
                'in_transit',        -- Being moved between locations
                'in_shipment',       -- Packaged for customer delivery
                'sold',              -- Sold to customer (final state)
                'with_customer',     -- Delivered to customer
                'in_return',         -- Being returned by customer
                'defective',         -- Marked as defective
                'repair',            -- Sent for repair
                'vendor_return',     -- Returned to vendor
                'disposed'           -- Disposed/written off
            ) DEFAULT 'available' 
            COMMENT 'Current state of this physical unit'
        ");

        // Update existing 'in_warehouse' and 'in_shop' statuses to 'available' for consistency
        DB::table('product_barcodes')
            ->whereIn('current_status', ['in_warehouse', 'in_shop'])
            ->update(['current_status' => 'available']);

        // Add status_before and status_after to product_movements if not already present
        DB::statement("ALTER TABLE `product_movements` 
            MODIFY COLUMN `status_before` ENUM(
                'available', 'in_warehouse', 'in_shop', 'on_display', 
                'in_transit', 'in_shipment', 'sold', 'with_customer', 
                'in_return', 'defective', 'repair', 'vendor_return', 'disposed'
            ) NULL COMMENT 'Status before movement'
        ");

        DB::statement("ALTER TABLE `product_movements` 
            MODIFY COLUMN `status_after` ENUM(
                'available', 'in_warehouse', 'in_shop', 'on_display', 
                'in_transit', 'in_shipment', 'sold', 'with_customer', 
                'in_return', 'defective', 'repair', 'vendor_return', 'disposed'
            ) NULL COMMENT 'Status after movement'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'available' back to 'in_warehouse'
        DB::table('product_barcodes')
            ->where('current_status', 'available')
            ->update(['current_status' => 'in_warehouse']);

        // Revert 'sold' to 'with_customer'
        DB::table('product_barcodes')
            ->where('current_status', 'sold')
            ->update(['current_status' => 'with_customer']);

        // Remove the new enum values
        DB::statement("ALTER TABLE `product_barcodes` 
            MODIFY COLUMN `current_status` ENUM(
                'in_warehouse', 'in_shop', 'on_display', 'in_transit', 
                'in_shipment', 'with_customer', 'in_return', 'defective', 
                'repair', 'vendor_return', 'disposed'
            ) DEFAULT 'in_warehouse' 
            COMMENT 'Current state of this physical unit'
        ");

        DB::statement("ALTER TABLE `product_movements` 
            MODIFY COLUMN `status_before` ENUM(
                'in_warehouse', 'in_shop', 'on_display', 'in_transit', 
                'in_shipment', 'with_customer', 'in_return', 'defective', 
                'repair', 'vendor_return', 'disposed'
            ) NULL COMMENT 'Status before movement'
        ");

        DB::statement("ALTER TABLE `product_movements` 
            MODIFY COLUMN `status_after` ENUM(
                'in_warehouse', 'in_shop', 'on_display', 'in_transit', 
                'in_shipment', 'with_customer', 'in_return', 'defective', 
                'repair', 'vendor_return', 'disposed'
            ) NULL COMMENT 'Status after movement'
        ");
    }
};
