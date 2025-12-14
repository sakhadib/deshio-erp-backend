<?php

// Test accounting calculation with inclusive tax
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING INCLUSIVE TAX ACCOUNTING ===\n\n";

// Get existing data
$store = \App\Models\Store::first();
$employee = \App\Models\Employee::first();

// Create test data
echo "Creating test customer...\n";
$customer = \App\Models\Customer::create([
    'name' => 'Test Customer',
    'phone' => '01712345678',
    'customer_type' => 'counter',
    'status' => 'active',
    'created_by' => $employee->id
]);

echo "Creating test category...\n";
$timestamp = time();
$category = \App\Models\Category::create([
    'title' => 'Test Category ' . $timestamp,
    'name' => 'Test Category ' . $timestamp,
    'slug' => 'test-category-' . $timestamp,
    'status' => 'active',
    'created_by' => $employee->id
]);

echo "Creating test product...\n";
$product = \App\Models\Product::create([
    'category_id' => $category->id,
    'sku' => 'TEST-001',
    'name' => 'Test Product',
    'is_archived' => false
]);

echo "Creating product batch with inclusive tax...\n";
// Selling price: 1100, Tax: 10%
// Expected: base_price = 1000, tax_amount = 100
$batchNumber = 'BATCH-TEST-' . time();
$batch = \App\Models\ProductBatch::create([
    'product_id' => $product->id,
    'store_id' => $store->id,
    'batch_number' => $batchNumber,
    'quantity' => 100,
    'cost_price' => 800,
    'sell_price' => 1100,  // Includes tax
    'tax_percentage' => 10,
    'created_by' => $employee->id
]);

echo "\nBatch Details:\n";
echo "  Selling Price (inclusive): {$batch->sell_price}\n";
echo "  Base Price (calculated): {$batch->base_price}\n";
echo "  Tax Amount per unit: {$batch->tax_amount}\n";
echo "  Tax Percentage: {$batch->tax_percentage}%\n";

// Create order
echo "\nCreating order with 2 units...\n";
$order = \App\Models\Order::create([
    'order_number' => 'ORD-TEST-' . time(),
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

// Create order item
echo "Creating order item (2 units x 1100 = 2200 total)...\n";
$taxPercentage = $batch->tax_percentage ?? 0;
$quantity = 2;
$unitPrice = $batch->sell_price;
$itemTotal = $unitPrice * $quantity; // 2200
$itemTax = $taxPercentage > 0 
    ? round($itemTotal - ($itemTotal / (1 + ($taxPercentage / 100))), 2)
    : 0; // Should be 200

$orderItem = \App\Models\OrderItem::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'product_batch_id' => $batch->id,
    'product_name' => $product->name,
    'product_sku' => $product->sku,
    'quantity' => $quantity,
    'unit_price' => $unitPrice,
    'tax_amount' => $itemTax,
    'discount_amount' => 0,
    'total_amount' => $itemTotal,
    'cogs' => $batch->cost_price * $quantity
]);

// Update order totals
$order->update([
    'subtotal' => $itemTotal,
    'tax_amount' => $itemTax,
    'total_amount' => $itemTotal,
    'outstanding_amount' => $itemTotal
]);

echo "\nOrder Details:\n";
echo "  Order Number: {$order->order_number}\n";
echo "  Subtotal (inclusive): {$order->subtotal}\n";
echo "  Tax Amount: {$order->tax_amount}\n";
echo "  Total Amount: {$order->total_amount}\n";

// Test full payment
echo "\n=== TEST 1: FULL PAYMENT ===\n";
$paymentMethod = \App\Models\PaymentMethod::where('code', 'cash')->first();

$payment1 = \App\Models\OrderPayment::createPayment($order, $paymentMethod, 2200, [], $employee);
$payment1->update(['status' => 'completed', 'completed_at' => now()]);

// Create transaction
$transaction1 = \App\Models\Transaction::createFromOrderPayment($payment1);

echo "Payment Amount: {$payment1->amount}\n";
echo "\nTransactions Created:\n";
$transactions = \App\Models\Transaction::where('reference_type', \App\Models\OrderPayment::class)
    ->where('reference_id', $payment1->id)
    ->get();

$totalDebit = 0;
$totalCredit = 0;

foreach ($transactions as $t) {
    echo "  {$t->type}: {$t->amount} - {$t->description}\n";
    echo "    Account: {$t->account->name}\n";
    if ($t->type === 'debit') $totalDebit += $t->amount;
    if ($t->type === 'credit') $totalCredit += $t->amount;
}

echo "\nTotal Debits: {$totalDebit}\n";
echo "Total Credits: {$totalCredit}\n";
echo "Balanced: " . ($totalDebit == $totalCredit ? "YES ✓" : "NO ✗") . "\n";

// Verify amounts
$expectedRevenue = 2200 - 200; // 2000
$expectedTax = 200;

$revenueTransaction = $transactions->filter(function($t) {
    return strpos($t->description, 'Revenue') !== false;
})->first();
$taxTransaction = $transactions->filter(function($t) {
    return strpos($t->description, 'Tax Collected') !== false;
})->first();

echo "\nExpected Revenue: {$expectedRevenue}\n";
echo "Actual Revenue: " . ($revenueTransaction ? $revenueTransaction->amount : 'N/A') . "\n";
echo "Expected Tax: {$expectedTax}\n";
echo "Actual Tax: " . ($taxTransaction ? $taxTransaction->amount : 'N/A') . "\n";

// Test partial payment
echo "\n=== TEST 2: PARTIAL PAYMENT (50%) ===\n";

// Create new order for partial payment test
$order2 = \App\Models\Order::create([
    'order_number' => 'ORD-TEST-' . (time() + 1),
    'customer_id' => $customer->id,
    'store_id' => $store->id,
    'order_type' => 'counter',
    'status' => 'pending',
    'payment_status' => 'pending',
    'subtotal' => $itemTotal,
    'tax_amount' => $itemTax,
    'total_amount' => $itemTotal,
    'outstanding_amount' => $itemTotal,
    'created_by' => $employee->id,
    'order_date' => now()
]);

$orderItem2 = \App\Models\OrderItem::create([
    'order_id' => $order2->id,
    'product_id' => $product->id,
    'product_batch_id' => $batch->id,
    'product_name' => $product->name,
    'product_sku' => $product->sku,
    'quantity' => $quantity,
    'unit_price' => $unitPrice,
    'tax_amount' => $itemTax,
    'discount_amount' => 0,
    'total_amount' => $itemTotal,
    'cogs' => $batch->cost_price * $quantity
]);

$partialAmount = 1100; // 50% of 2200
$payment2 = \App\Models\OrderPayment::createPayment($order2, $paymentMethod, $partialAmount, [], $employee);
$payment2->update(['status' => 'completed', 'completed_at' => now()]);

$transaction2 = \App\Models\Transaction::createFromOrderPayment($payment2);

echo "Payment Amount: {$payment2->amount} (50% of total)\n";
echo "Order Total: {$order2->total_amount}\n";
echo "Order Tax: {$order2->tax_amount}\n";

echo "\nTransactions Created:\n";
$transactions2 = \App\Models\Transaction::where('reference_type', \App\Models\OrderPayment::class)
    ->where('reference_id', $payment2->id)
    ->get();

$totalDebit2 = 0;
$totalCredit2 = 0;

foreach ($transactions2 as $t) {
    echo "  {$t->type}: {$t->amount} - {$t->description}\n";
    echo "    Account: {$t->account->name}\n";
    if ($t->type === 'debit') $totalDebit2 += $t->amount;
    if ($t->type === 'credit') $totalCredit2 += $t->amount;
}

echo "\nTotal Debits: {$totalDebit2}\n";
echo "Total Credits: {$totalCredit2}\n";
echo "Balanced: " . ($totalDebit2 == $totalCredit2 ? "YES ✓" : "NO ✗") . "\n";

// Verify proportional amounts
$expectedProportionalTax = round($partialAmount * ($order2->tax_amount / $order2->total_amount), 2); // 100
$expectedProportionalRevenue = $partialAmount - $expectedProportionalTax; // 1000

$revenueTransaction2 = $transactions2->filter(function($t) {
    return strpos($t->description, 'Revenue') !== false;
})->first();
$taxTransaction2 = $transactions2->filter(function($t) {
    return strpos($t->description, 'Tax Collected') !== false;
})->first();

echo "\nExpected Proportional Revenue: {$expectedProportionalRevenue}\n";
echo "Actual Revenue: " . ($revenueTransaction2 ? $revenueTransaction2->amount : 'N/A') . "\n";
echo "Expected Proportional Tax: {$expectedProportionalTax}\n";
echo "Actual Tax: " . ($taxTransaction2 ? $taxTransaction2->amount : 'N/A') . "\n";

echo "\n=== TEST COMPLETE ===\n";
echo "\nSUMMARY:\n";
echo "Full Payment Balanced: " . ($totalDebit == $totalCredit ? "YES ✓" : "NO ✗") . "\n";
echo "Partial Payment Balanced: " . ($totalDebit2 == $totalCredit2 ? "YES ✓" : "NO ✗") . "\n";
echo "\nRevenue + Tax = Total for Full Payment: ";
$fullCheck = ($revenueTransaction && $taxTransaction && 
    ($revenueTransaction->amount + $taxTransaction->amount) == $payment1->amount);
echo ($fullCheck ? "YES ✓" : "NO ✗") . "\n";

echo "Revenue + Tax = Total for Partial Payment: ";
$partialCheck = ($revenueTransaction2 && $taxTransaction2 && 
    ($revenueTransaction2->amount + $taxTransaction2->amount) == $payment2->amount);
echo ($partialCheck ? "YES ✓" : "NO ✗") . "\n";
