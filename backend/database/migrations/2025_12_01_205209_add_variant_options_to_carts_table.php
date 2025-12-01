<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Drop the old unique constraint that doesn't account for variants
            $table->dropUnique('unique_customer_product_status');
            
            // Add variant_options JSON column
            $table->json('variant_options')->nullable()->after('product_id');
        });
        
        // Add unique index using raw SQL with md5 hash for variant_options
        // This allows NULL values and handles JSON comparison properly
        \DB::statement('
            CREATE UNIQUE INDEX unique_customer_product_variant_status 
            ON carts (customer_id, product_id, MD5(CAST(variant_options AS TEXT)), status)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the unique index
        \DB::statement('DROP INDEX IF EXISTS unique_customer_product_variant_status');
        
        Schema::table('carts', function (Blueprint $table) {
            // Remove variant_options column
            $table->dropColumn('variant_options');
            
            // Restore old constraint
            $table->unique(['customer_id', 'product_id', 'status'], 'unique_customer_product_status');
        });
    }
};
