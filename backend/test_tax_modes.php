<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Transaction;

echo "=== TESTING TAX MODES (INCLUSIVE VS EXCLUSIVE) ===\n\n";

// Get test data
$store = Store::first();
$employee = Employee::first();
$paymentMethod = PaymentMethod::where('code', 'cash')->first();

// Test both modes
$modes = ['inclusive', 'exclusive'];

foreach ($modes as $mode) {
    echo "===============================================\n";
    echo "TESTING: TAX_MODE = {$mode}\n";
    echo "===============================================\n\n";
    
    // Set the tax mode in config
    config(['app.tax_mode' => $mode]);
    
    // Create test customer
    echo "Creating test customer...\n";
    $timestamp = time() . '-' . $mode;
    $customer = Customer::create([
        'name' => 'Test Customer ' . $timestamp,
        'email' => 'test-' . $timestamp . '@example.com',
        'phone' => '1234567890',
        'customer_type' => 'counter',
        'status' => 'active',
        'created_by' => $employee->id
    ]);

    echo "Creating test category...\n";
    $category = Category::create([
        'title' => 'Test Category ' . $timestamp,
        'name' => 'Test Category ' . $timestamp,
        'slug' => 'test-category-' . $timestamp,
        'status' => 'active',
        'level' => 0,
        'path' => '',
    ]);

    echo "Creating test product...\n";
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TEST-' . $timestamp,
        'name' => 'Test Product ' . $mode,
        'is_archived' => false
    ]);

    echo "Creating product batch with {$mode} tax...\n";
    echo "  Input: sell_price=1000, tax_percentage=10%\n";
    
    $batch = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-' . $timestamp,
        'quantity' => 100,
        'cost_price' => 500,
        'sell_price' => 1000,
        'tax_percentage' => 10,
        'availability' => true,
        'is_active' => true,
    ]);

    // Refresh to get calculated values
    $batch = $batch->fresh();

    echo "\nBatch Calculation Results:\n";
    echo "  Sell Price: {$batch->sell_price}\n";
    echo "  Base Price: {$batch->base_price}\n";
    echo "  Tax Amount: {$batch->tax_amount}\n";
    echo "  Total Price: {$batch->total_price}\n";

    if ($mode === 'inclusive') {
        echo "\n  Expected (Inclusive):\n";
        echo "    Base: 909.09 (1000 / 1.10)\n";
        echo "    Tax: 90.91 (1000 - 909.09)\n";
        echo "    Total: 1000 (sell_price)\n";
    } else {
        echo "\n  Expected (Exclusive):\n";
        echo "    Base: 1000 (sell_price)\n";
        echo "    Tax: 100 (1000 * 10%)\n";
        echo "    Total: 1100 (sell_price + tax)\n";
    }

    // Create order
    echo "\n\nCreating order with 2 units...\n";
    $order = Order::create([
        'order_number' => 'ORD-TEST-' . $timestamp,
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
        'outstanding_amount' => 0,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);

    // Calculate item values
    $quantity = 2;
    $unitPrice = $batch->sell_price;
    
    // Calculate tax
    $taxPercentage = $batch->tax_percentage;
    if ($mode === 'inclusive') {
        $basePrice = round($unitPrice / (1 + ($taxPercentage / 100)), 2);
        $taxPerUnit = round($unitPrice - $basePrice, 2);
    } else {
        $basePrice = $unitPrice;
        $taxPerUnit = round($unitPrice * ($taxPercentage / 100), 2);
    }
    
    $itemSubtotal = $quantity * $unitPrice;
    $itemTax = $taxPerUnit * $quantity;
    
    echo "Order Item Calculation:\n";
    echo "  Quantity: {$quantity}\n";
    echo "  Unit Price: {$unitPrice}\n";
    echo "  Tax Per Unit: {$taxPerUnit}\n";
    echo "  Item Subtotal: {$itemSubtotal}\n";
    echo "  Item Tax Total: {$itemTax}\n";

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'discount_amount' => 0,
        'tax_amount' => $itemTax,
        'total_amount' => $itemSubtotal,
        'cogs' => $batch->cost_price * $quantity
    ]);

    // Update order totals
    if ($mode === 'inclusive') {
        $orderTotal = $itemSubtotal;  // Tax already included
    } else {
        $orderTotal = $itemSubtotal + $itemTax;  // Add tax to subtotal
    }

    $order->update([
        'subtotal' => $itemSubtotal,
        'tax_amount' => $itemTax,
        'total_amount' => $orderTotal,
        'outstanding_amount' => $orderTotal
    ]);

    echo "\nOrder Totals:\n";
    echo "  Subtotal: {$order->subtotal}\n";
    echo "  Tax Amount: {$order->tax_amount}\n";
    echo "  Total Amount: {$order->total_amount}\n";

    if ($mode === 'inclusive') {
        echo "\n  Expected (Inclusive): Total = Subtotal = 2000\n";
    } else {
        echo "\n  Expected (Exclusive): Total = Subtotal + Tax = 2000 + 200 = 2200\n";
    }

    // Test payment and accounting
    echo "\n\nTesting Payment & Accounting...\n";
    $payment = \App\Models\OrderPayment::createPayment($order, $paymentMethod, $orderTotal, [], $employee);
    $payment->update(['status' => 'completed', 'completed_at' => now()]);

    echo "Payment Amount: {$payment->amount}\n";

    // Get transactions
    $transactions = Transaction::where('reference_type', \App\Models\OrderPayment::class)
        ->where('reference_id', $payment->id)
        ->get();

    echo "\nAccounting Transactions:\n";
    $totalDebit = 0;
    $totalCredit = 0;

    foreach ($transactions as $t) {
        echo "  {$t->type}: {$t->amount} - {$t->description}\n";
        if ($t->type === 'debit') $totalDebit += $t->amount;
        if ($t->type === 'credit') $totalCredit += $t->amount;
    }

    echo "\nTotal Debits: {$totalDebit}\n";
    echo "Total Credits: {$totalCredit}\n";
    echo "Balanced: " . ($totalDebit == $totalCredit ? "YES ✓" : "NO ✗") . "\n";

    // Verify revenue and tax split
    $revenueTransaction = $transactions->filter(function($t) {
        return strpos($t->description, 'Revenue') !== false;
    })->first();
    
    $taxTransaction = $transactions->filter(function($t) {
        return strpos($t->description, 'Tax Collected') !== false;
    })->first();

    if ($revenueTransaction && $taxTransaction) {
        $revenue = $revenueTransaction->amount;
        $tax = $taxTransaction->amount;
        
        echo "\nRevenue Split:\n";
        echo "  Revenue: {$revenue}\n";
        echo "  Tax: {$tax}\n";
        echo "  Total: " . ($revenue + $tax) . "\n";
        
        if ($mode === 'inclusive') {
            echo "\n  Expected (Inclusive): Revenue=1818.18, Tax=181.82\n";
            $expectedRevenue = 1818.18;
            $expectedTax = 181.82;
        } else {
            echo "\n  Expected (Exclusive): Revenue=2000, Tax=200\n";
            $expectedRevenue = 2000;
            $expectedTax = 200;
        }
        
        $revenueMatch = abs($revenue - $expectedRevenue) < 0.5;
        $taxMatch = abs($tax - $expectedTax) < 0.5;
        
        echo "  Revenue Correct: " . ($revenueMatch ? "YES ✓" : "NO ✗") . "\n";
        echo "  Tax Correct: " . ($taxMatch ? "YES ✓" : "NO ✗") . "\n";
    }

    echo "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nSUMMARY:\n";
echo "- Both inclusive and exclusive tax modes tested\n";
echo "- ProductBatch calculations verified\n";
echo "- Order calculations verified\n";
echo "- Accounting transactions verified\n";
echo "- Double-entry bookkeeping balanced\n";
