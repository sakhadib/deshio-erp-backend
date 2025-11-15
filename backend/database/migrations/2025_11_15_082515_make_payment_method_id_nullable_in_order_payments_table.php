<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Make payment_method_id nullable to support split payments.
     * When a payment is split across multiple methods, the parent
     * OrderPayment record has payment_method_id = null, and each
     * split has its own payment_method_id in the payment_splits table.
     */
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['payment_method_id']);
            
            // Make the column nullable
            $table->foreignId('payment_method_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['payment_method_id']);
            
            // Make the column not nullable
            // Note: This will fail if there are records with null payment_method_id
            $table->foreignId('payment_method_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods')
                ->onDelete('restrict');
        });
    }
};
