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
        // Drop the existing foreign key constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });

        // Make store_id nullable
        DB::statement('ALTER TABLE orders ALTER COLUMN store_id DROP NOT NULL');

        // Re-add the foreign key constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });

        // Make store_id NOT NULL
        DB::statement('ALTER TABLE orders ALTER COLUMN store_id SET NOT NULL');

        // Re-add the foreign key constraint with cascade
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('cascade');
        });
    }
};
