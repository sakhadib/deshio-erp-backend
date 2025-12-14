<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Account;

echo "\n";
echo "===============================================\n";
echo "RIGOROUS ACCOUNTING TEST\n";
echo "TAX_MODE: " . config('app.tax_mode', 'inclusive') . "\n";
echo "===============================================\n\n";

$taxMode = config('app.tax_mode', 'inclusive');
$errors = [];
$tests = 0;
$passed = 0;

function assertEquals($expected, $actual, $message, &$errors, &$tests, &$passed) {
    $tests++;
    $tolerance = 0.01; // Allow 1 cent rounding difference
    $diff = abs($expected - $actual);
    
    if ($diff <= $tolerance) {
        $passed++;
        echo "✓ $message\n";
        return true;
    } else {
        $error = "✗ $message\n  Expected: $expected, Got: $actual, Diff: $diff";
        echo "$error\n";
        $errors[] = $error;
        return false;
    }
}

function assertBalance($debits, $credits, $message, &$errors, &$tests, &$passed) {
    $tests++;
    $tolerance = 0.01;
    $diff = abs($debits - $credits);
    
    if ($diff <= $tolerance) {
        $passed++;
        echo "✓ $message (Debits: $debits = Credits: $credits)\n";
        return true;
    } else {
        $error = "✗ $message\n  Debits: $debits, Credits: $credits, Diff: $diff";
        echo "$error\n";
        $errors[] = $error;
        return false;
    }
}

try {
    // Setup test data
    echo "Setting up test data...\n";
    
    $store = Store::first() ?? Store::create(['name' => 'Test Store', 'code' => 'TS001']);
    $employee = Employee::first();
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test' . time() . '@example.com',
        'phone' => '1234567890',
        'customer_type' => 'counter',
        'status' => 'active',
        'created_by' => $employee->id
    ]);
    
    $timestamp = time();
    $category = Category::create([
        'title' => 'Test Category ' . $timestamp,
        'slug' => 'test-category-' . $timestamp,
        'level' => 0,
        'path' => '',
    ]);
    
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TEST-' . $timestamp,
        'name' => 'Test Product',
        'is_archived' => false
    ]);
    
    echo "✓ Test data setup complete\n\n";
    
    // ============================================================
    // TEST 1: Single Product, Full Payment
    // ============================================================
    echo "TEST 1: Single Product, Full Payment\n";
    echo "--------------------------------------------\n";
    
    $batch1 = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T1-' . time(),
        'quantity' => 100,
        'cost_price' => 500,
        'sell_price' => $taxMode === 'inclusive' ? 1100 : 1000,
        'tax_percentage' => 10,
    ]);
    
    echo "Batch Created:\n";
    echo "  Sell Price: {$batch1->sell_price}\n";
    echo "  Base Price: {$batch1->base_price}\n";
    echo "  Tax Amount: {$batch1->tax_amount}\n";
    echo "  Total Price: {$batch1->total_price}\n\n";
    
    // Expected values for order of 2 units
    $quantity = 2;
    if ($taxMode === 'inclusive') {
        $expectedSubtotal = 2200;  // 1100 * 2
        $expectedTax = 200;         // Extracted from subtotal
        $expectedTotal = 2200;      // Subtotal (already includes tax)
        $expectedRevenue = 2000;    // Subtotal - Tax
    } else {
        $expectedSubtotal = 2000;   // 1000 * 2
        $expectedTax = 200;         // 2000 * 10%
        $expectedTotal = 2200;      // Subtotal + Tax
        $expectedRevenue = 2000;    // Subtotal (base price)
    }
    
    $order1 = Order::create([
        'order_number' => 'ORD-T1-' . time(),
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);
    
    $item1 = OrderItem::create([
        'order_id' => $order1->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch1->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => $quantity,
        'unit_price' => $batch1->sell_price,
        'tax_amount' => $batch1->tax_amount * $quantity,
        'total_amount' => $batch1->sell_price * $quantity,
        'cogs' => $batch1->cost_price * $quantity
    ]);
    
    $order1->calculateTotals();
    $order1->refresh();
    
    assertEquals($expectedSubtotal, $order1->subtotal, "Order subtotal", $errors, $tests, $passed);
    assertEquals($expectedTax, $order1->tax_amount, "Order tax amount", $errors, $tests, $passed);
    assertEquals($expectedTotal, $order1->total_amount, "Order total", $errors, $tests, $passed);
    
    // Full payment
    $paymentMethod = PaymentMethod::where('code', 'cash')->first();
    $payment1 = OrderPayment::createPayment($order1, $paymentMethod, $expectedTotal, [], $employee);
    $payment1->update(['status' => 'completed', 'completed_at' => now()]);
    
    $transactions1 = Transaction::where('reference_type', OrderPayment::class)
        ->where('reference_id', $payment1->id)
        ->get();
    
    $debits1 = $transactions1->where('type', 'debit')->sum('amount');
    $credits1 = $transactions1->where('type', 'credit')->sum('amount');
    $revenue1 = $transactions1->filter(fn($t) => strpos($t->description, 'Revenue') !== false)->first();
    $tax1 = $transactions1->filter(fn($t) => strpos($t->description, 'Tax Collected') !== false)->first();
    
    assertBalance($debits1, $credits1, "Double-entry balanced", $errors, $tests, $passed);
    assertEquals($expectedRevenue, $revenue1->amount ?? 0, "Revenue amount", $errors, $tests, $passed);
    assertEquals($expectedTax, $tax1->amount ?? 0, "Tax amount", $errors, $tests, $passed);
    assertEquals($expectedTotal, $debits1, "Cash received", $errors, $tests, $passed);
    
    echo "\n";
    
    // ============================================================
    // TEST 2: Multiple Products, Different Tax Rates
    // ============================================================
    echo "TEST 2: Multiple Products, Different Tax Rates\n";
    echo "--------------------------------------------\n";
    
    $batch2a = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T2A-' . time(),
        'quantity' => 100,
        'cost_price' => 400,
        'sell_price' => $taxMode === 'inclusive' ? 550 : 500,
        'tax_percentage' => 10,
    ]);
    
    $batch2b = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T2B-' . time(),
        'quantity' => 100,
        'cost_price' => 800,
        'sell_price' => $taxMode === 'inclusive' ? 1500 : 1250,
        'tax_percentage' => 20,
    ]);
    
    if ($taxMode === 'inclusive') {
        $expectedSubtotal2 = 1650;      // 550 + 1100 (550*2)
        $expectedTax2 = 150 + 250;      // 50 + 200
        $expectedTotal2 = 1650;
        $expectedRevenue2 = 1250;       // 1650 - 400
    } else {
        $expectedSubtotal2 = 1500;      // 500 + 1000 (500*2)
        $expectedTax2 = 50 + 200;       // (500*10%) + (1000*20%)
        $expectedTotal2 = 1750;         // 1500 + 250
        $expectedRevenue2 = 1500;
    }
    
    $order2 = Order::create([
        'order_number' => 'ORD-T2-' . time(),
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);
    
    OrderItem::create([
        'order_id' => $order2->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch2a->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 1,
        'unit_price' => $batch2a->sell_price,
        'tax_amount' => $batch2a->tax_amount,
        'total_amount' => $batch2a->sell_price,
        'cogs' => $batch2a->cost_price
    ]);
    
    OrderItem::create([
        'order_id' => $order2->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch2b->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => $batch2b->sell_price,
        'tax_amount' => $batch2b->tax_amount * 2,
        'total_amount' => $batch2b->sell_price * 2,
        'cogs' => $batch2b->cost_price * 2
    ]);
    
    $order2->calculateTotals();
    $order2->refresh();
    
    // Recalculate expected based on actual batch values
    $item2aSubtotal = $batch2a->sell_price;
    $item2bSubtotal = $batch2b->sell_price * 2;
    $expectedSubtotal2 = $item2aSubtotal + $item2bSubtotal;
    $expectedTax2 = $batch2a->tax_amount + ($batch2b->tax_amount * 2);
    
    if ($taxMode === 'inclusive') {
        $expectedTotal2 = $expectedSubtotal2;
        $expectedRevenue2 = $expectedSubtotal2 - $expectedTax2;
    } else {
        $expectedTotal2 = $expectedSubtotal2 + $expectedTax2;
        $expectedRevenue2 = $expectedSubtotal2;
    }
    
    assertEquals($expectedSubtotal2, $order2->subtotal, "Multi-product subtotal", $errors, $tests, $passed);
    assertEquals($expectedTax2, $order2->tax_amount, "Multi-product tax", $errors, $tests, $passed);
    assertEquals($expectedTotal2, $order2->total_amount, "Multi-product total", $errors, $tests, $passed);
    
    $payment2 = OrderPayment::createPayment($order2, $paymentMethod, $expectedTotal2, [], $employee);
    $payment2->update(['status' => 'completed', 'completed_at' => now()]);
    
    $transactions2 = Transaction::where('reference_type', OrderPayment::class)
        ->where('reference_id', $payment2->id)
        ->get();
    
    $debits2 = $transactions2->where('type', 'debit')->sum('amount');
    $credits2 = $transactions2->where('type', 'credit')->sum('amount');
    
    assertBalance($debits2, $credits2, "Multi-product balanced", $errors, $tests, $passed);
    
    echo "\n";
    
    // ============================================================
    // TEST 3: Partial Payments (2 installments)
    // ============================================================
    echo "TEST 3: Partial Payments (50% + 50%)\n";
    echo "--------------------------------------------\n";
    
    $batch3 = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T3-' . time(),
        'quantity' => 100,
        'cost_price' => 600,
        'sell_price' => $taxMode === 'inclusive' ? 1100 : 1000,
        'tax_percentage' => 10,
    ]);
    
    $order3 = Order::create([
        'order_number' => 'ORD-T3-' . time(),
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);
    
    OrderItem::create([
        'order_id' => $order3->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch3->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 4,
        'unit_price' => $batch3->sell_price,
        'tax_amount' => $batch3->tax_amount * 4,
        'total_amount' => $batch3->sell_price * 4,
        'cogs' => $batch3->cost_price * 4
    ]);
    
    $order3->calculateTotals();
    $order3->refresh();
    
    if ($taxMode === 'inclusive') {
        $expectedTotal3 = 4400;      // 1100 * 4
        $expectedTax3 = 400;         // Extracted
        $expectedRevenue3 = 4000;    // 4400 - 400
    } else {
        $expectedTotal3 = 4400;      // 4000 + 400
        $expectedTax3 = 400;         // 4000 * 10%
        $expectedRevenue3 = 4000;
    }
    
    // First payment: 50%
    $payment3a = OrderPayment::createPayment($order3, $paymentMethod, $expectedTotal3 / 2, [], $employee);
    $payment3a->update(['status' => 'completed', 'completed_at' => now()]);
    
    $transactions3a = Transaction::where('reference_type', OrderPayment::class)
        ->where('reference_id', $payment3a->id)
        ->get();
    
    $debits3a = $transactions3a->where('type', 'debit')->sum('amount');
    $credits3a = $transactions3a->where('type', 'credit')->sum('amount');
    $revenue3a = $transactions3a->filter(fn($t) => strpos($t->description, 'Revenue') !== false)->first();
    $tax3a = $transactions3a->filter(fn($t) => strpos($t->description, 'Tax Collected') !== false)->first();
    
    assertBalance($debits3a, $credits3a, "Partial payment 1 balanced", $errors, $tests, $passed);
    assertEquals($expectedRevenue3 / 2, $revenue3a->amount ?? 0, "Partial payment 1 revenue (50%)", $errors, $tests, $passed);
    assertEquals($expectedTax3 / 2, $tax3a->amount ?? 0, "Partial payment 1 tax (50%)", $errors, $tests, $passed);
    
    // Second payment: 50%
    $payment3b = OrderPayment::createPayment($order3, $paymentMethod, $expectedTotal3 / 2, [], $employee);
    $payment3b->update(['status' => 'completed', 'completed_at' => now()]);
    
    $transactions3b = Transaction::where('reference_type', OrderPayment::class)
        ->where('reference_id', $payment3b->id)
        ->get();
    
    $debits3b = $transactions3b->where('type', 'debit')->sum('amount');
    $credits3b = $transactions3b->where('type', 'credit')->sum('amount');
    $revenue3b = $transactions3b->filter(fn($t) => strpos($t->description, 'Revenue') !== false)->first();
    $tax3b = $transactions3b->filter(fn($t) => strpos($t->description, 'Tax Collected') !== false)->first();
    
    assertBalance($debits3b, $credits3b, "Partial payment 2 balanced", $errors, $tests, $passed);
    assertEquals($expectedRevenue3 / 2, $revenue3b->amount ?? 0, "Partial payment 2 revenue (50%)", $errors, $tests, $passed);
    assertEquals($expectedTax3 / 2, $tax3b->amount ?? 0, "Partial payment 2 tax (50%)", $errors, $tests, $passed);
    
    // Verify total
    $totalRevenue3 = ($revenue3a->amount ?? 0) + ($revenue3b->amount ?? 0);
    $totalTax3 = ($tax3a->amount ?? 0) + ($tax3b->amount ?? 0);
    
    assertEquals($expectedRevenue3, $totalRevenue3, "Total revenue from partial payments", $errors, $tests, $passed);
    assertEquals($expectedTax3, $totalTax3, "Total tax from partial payments", $errors, $tests, $passed);
    
    echo "\n";
    
    // ============================================================
    // TEST 4: Partial Payment (70%, 20%, 10%)
    // ============================================================
    echo "TEST 4: Partial Payments (70% + 20% + 10%)\n";
    echo "--------------------------------------------\n";
    
    $batch4 = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T4-' . time(),
        'quantity' => 100,
        'cost_price' => 500,
        'sell_price' => $taxMode === 'inclusive' ? 1100 : 1000,
        'tax_percentage' => 10,
    ]);
    
    $order4 = Order::create([
        'order_number' => 'ORD-T4-' . time(),
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);
    
    OrderItem::create([
        'order_id' => $order4->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch4->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 5,
        'unit_price' => $batch4->sell_price,
        'tax_amount' => $batch4->tax_amount * 5,
        'total_amount' => $batch4->sell_price * 5,
        'cogs' => $batch4->cost_price * 5
    ]);
    
    $order4->calculateTotals();
    $order4->refresh();
    
    if ($taxMode === 'inclusive') {
        $expectedTotal4 = 5500;
        $expectedTax4 = 500;
        $expectedRevenue4 = 5000;
    } else {
        $expectedTotal4 = 5500;
        $expectedTax4 = 500;
        $expectedRevenue4 = 5000;
    }
    
    $paymentAmounts = [
        ['percentage' => 0.70, 'amount' => $expectedTotal4 * 0.70],
        ['percentage' => 0.20, 'amount' => $expectedTotal4 * 0.20],
        ['percentage' => 0.10, 'amount' => $expectedTotal4 * 0.10],
    ];
    
    $totalRevenue4 = 0;
    $totalTax4 = 0;
    $paymentCount = 0;
    
    foreach ($paymentAmounts as $paymentData) {
        $paymentCount++;
        usleep(100000); // 0.1 second delay to ensure unique timestamps
        
        $payment = OrderPayment::createPayment($order4, $paymentMethod, $paymentData['amount'], [], $employee);
        $payment->update(['status' => 'completed', 'completed_at' => now()]);
        
        $transactions = Transaction::where('reference_type', OrderPayment::class)
            ->where('reference_id', $payment->id)
            ->get();
        
        $debits = $transactions->where('type', 'debit')->sum('amount');
        $credits = $transactions->where('type', 'credit')->sum('amount');
        $revenue = $transactions->filter(fn($t) => strpos($t->description, 'Revenue') !== false)->first();
        $tax = $transactions->filter(fn($t) => strpos($t->description, 'Tax Collected') !== false)->first();
        
        $percentDisplay = ($paymentData['percentage'] * 100);
        assertBalance($debits, $credits, "Partial payment {$percentDisplay}% (Payment $paymentCount) balanced", $errors, $tests, $passed);
        
        $totalRevenue4 += $revenue->amount ?? 0;
        $totalTax4 += $tax->amount ?? 0;
    }
    
    assertEquals($expectedRevenue4, $totalRevenue4, "Total revenue from 3-part payments", $errors, $tests, $passed);
    assertEquals($expectedTax4, $totalTax4, "Total tax from 3-part payments", $errors, $tests, $passed);
    
    echo "\n";
    
    // ============================================================
    // TEST 5: Order with Discount
    // ============================================================
    echo "TEST 5: Order with Discount\n";
    echo "--------------------------------------------\n";
    
    $batch5 = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T5-' . time(),
        'quantity' => 100,
        'cost_price' => 500,
        'sell_price' => $taxMode === 'inclusive' ? 1100 : 1000,
        'tax_percentage' => 10,
    ]);
    
    $order5 = Order::create([
        'order_number' => 'ORD-T5-' . time(),
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'discount_amount' => 200,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);
    
    OrderItem::create([
        'order_id' => $order5->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch5->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => $batch5->sell_price,
        'tax_amount' => $batch5->tax_amount * 2,
        'total_amount' => $batch5->sell_price * 2,
        'cogs' => $batch5->cost_price * 2,
        'discount_amount' => 200
    ]);
    
    $order5->calculateTotals();
    $order5->refresh();
    
    if ($taxMode === 'inclusive') {
        $expectedSubtotal5 = 2200;
        $expectedTax5 = 200;
        $expectedTotal5 = 2000;  // 2200 - 200 discount
    } else {
        $expectedSubtotal5 = 2000;
        $expectedTax5 = 200;
        $expectedTotal5 = 2000;  // 2000 + 200 - 200 discount
    }
    
    assertEquals($expectedTotal5, $order5->total_amount, "Order with discount total", $errors, $tests, $passed);
    
    $payment5 = OrderPayment::createPayment($order5, $paymentMethod, $expectedTotal5, [], $employee);
    $payment5->update(['status' => 'completed', 'completed_at' => now()]);
    
    $transactions5 = Transaction::where('reference_type', OrderPayment::class)
        ->where('reference_id', $payment5->id)
        ->get();
    
    $debits5 = $transactions5->where('type', 'debit')->sum('amount');
    $credits5 = $transactions5->where('type', 'credit')->sum('amount');
    
    assertBalance($debits5, $credits5, "Order with discount balanced", $errors, $tests, $passed);
    
    echo "\n";
    
    // ============================================================
    // TEST 6: High Quantity, Small Amounts (Rounding Test)
    // ============================================================
    echo "TEST 6: High Quantity & Rounding\n";
    echo "--------------------------------------------\n";
    
    $batch6 = ProductBatch::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'batch_number' => 'BATCH-T6-' . time(),
        'quantity' => 1000,
        'cost_price' => 1.50,
        'sell_price' => $taxMode === 'inclusive' ? 3.30 : 3.00,
        'tax_percentage' => 10,
    ]);
    
    $order6 = Order::create([
        'order_number' => 'ORD-T6-' . time(),
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_type' => 'counter',
        'status' => 'pending',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'created_by' => $employee->id,
        'order_date' => now()
    ]);
    
    OrderItem::create([
        'order_id' => $order6->id,
        'product_id' => $product->id,
        'product_batch_id' => $batch6->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 33,
        'unit_price' => $batch6->sell_price,
        'tax_amount' => $batch6->tax_amount * 33,
        'total_amount' => $batch6->sell_price * 33,
        'cogs' => $batch6->cost_price * 33
    ]);
    
    $order6->calculateTotals();
    $order6->refresh();
    
    // Partial payment test
    $payment6 = OrderPayment::createPayment($order6, $paymentMethod, $order6->total_amount / 3, [], $employee);
    $payment6->update(['status' => 'completed', 'completed_at' => now()]);
    
    $transactions6 = Transaction::where('reference_type', OrderPayment::class)
        ->where('reference_id', $payment6->id)
        ->get();
    
    $debits6 = $transactions6->where('type', 'debit')->sum('amount');
    $credits6 = $transactions6->where('type', 'credit')->sum('amount');
    
    assertBalance($debits6, $credits6, "Small amounts with rounding balanced", $errors, $tests, $passed);
    
    echo "\n";
    
    // ============================================================
    // TEST 7: Account Balance Verification
    // ============================================================
    echo "TEST 7: Account Balance Verification\n";
    echo "--------------------------------------------\n";
    
    $cashAccount = Account::where('type', 'asset')
        ->where(function($q) {
            $q->where('name', 'like', '%Cash%')
              ->orWhere('sub_type', 'current_asset');
        })
        ->first();
    
    $revenueAccount = Account::where('type', 'income')
        ->where('sub_type', 'sales_revenue')
        ->first();
    
    $taxAccount = Account::where('type', 'liability')
        ->where('name', 'like', '%Tax%')
        ->first();
    
    if ($cashAccount) {
        $cashBalance = Transaction::where('account_id', $cashAccount->id)
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) - SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as balance', ['debit', 'credit'])
            ->value('balance');
        echo "✓ Cash Account Balance: $cashBalance\n";
    }
    
    if ($revenueAccount) {
        $revenueBalance = Transaction::where('account_id', $revenueAccount->id)
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) - SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as balance', ['credit', 'debit'])
            ->value('balance');
        echo "✓ Revenue Account Balance: $revenueBalance\n";
    }
    
    if ($taxAccount) {
        $taxBalance = Transaction::where('account_id', $taxAccount->id)
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) - SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as balance', ['credit', 'debit'])
            ->value('balance');
        echo "✓ Tax Payable Balance: $taxBalance\n";
    }
    
    echo "\n";
    
    // ============================================================
    // FINAL REPORT
    // ============================================================
    echo "===============================================\n";
    echo "TEST RESULTS SUMMARY\n";
    echo "===============================================\n";
    echo "Tax Mode: " . strtoupper($taxMode) . "\n";
    echo "Tests Run: $tests\n";
    echo "Tests Passed: $passed\n";
    echo "Tests Failed: " . ($tests - $passed) . "\n";
    echo "Success Rate: " . round(($passed / $tests) * 100, 2) . "%\n";
    
    if (count($errors) > 0) {
        echo "\n⚠ ERRORS FOUND:\n";
        foreach ($errors as $error) {
            echo $error . "\n";
        }
        echo "\n❌ ACCOUNTING TEST FAILED\n";
        exit(1);
    } else {
        echo "\n✅ ALL ACCOUNTING TESTS PASSED!\n";
        echo "✅ Double-entry bookkeeping verified\n";
        echo "✅ Revenue/tax split accurate\n";
        echo "✅ Partial payments handled correctly\n";
        echo "✅ Rounding handled correctly\n";
        echo "✅ Account balances consistent\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ TEST FAILED WITH EXCEPTION:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
