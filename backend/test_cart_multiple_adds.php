<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Cart Add Logic (Multiple Adds) ===\n\n";

// Clear cart
DB::table('carts')->where('customer_id', 2)->delete();
echo "✅ Cart cleared\n\n";

// Simulate adding same product multiple times (like customer clicking Add to Cart twice)
$customer = \App\Models\Customer::find(2);
$product = \App\Models\Product::with(['batches' => function($q) {
    $q->active()->available();
}])->find(1);

echo "Test 1: Add product WITHOUT variants (first time)\n";
$cartItem1 = \App\Models\Cart::create([
    'customer_id' => $customer->id,
    'product_id' => $product->id,
    'variant_options' => null,
    'quantity' => 2,
    'unit_price' => 140.00,
    'status' => 'active',
]);
echo "  Created cart item ID: {$cartItem1->id}, Quantity: {$cartItem1->quantity}\n\n";

echo "Test 2: Add SAME product WITHOUT variants (second time) - Should UPDATE\n";
// Check if exists
$existing = \App\Models\Cart::where('customer_id', $customer->id)
    ->where('product_id', $product->id)
    ->where('status', 'active')
    ->whereNull('variant_options')
    ->first();

if ($existing) {
    echo "  ✅ Found existing cart item ID: {$existing->id}\n";
    $existing->update(['quantity' => $existing->quantity + 3]);
    echo "  ✅ Updated quantity to: {$existing->quantity}\n";
} else {
    echo "  ❌ Did NOT find existing item (would create duplicate!)\n";
}
echo "\n";

echo "Test 3: Add product WITH variants (first time)\n";
$variants1 = ['color' => 'Blue', 'size' => 'L'];
$cartItem2 = \App\Models\Cart::create([
    'customer_id' => $customer->id,
    'product_id' => $product->id,
    'variant_options' => $variants1,
    'quantity' => 1,
    'unit_price' => 140.00,
    'status' => 'active',
]);
echo "  Created cart item ID: {$cartItem2->id}, Variants: " . json_encode($cartItem2->variant_options) . "\n\n";

echo "Test 4: Add SAME product WITH SAME variants (second time) - Should UPDATE\n";
// Check if exists using MD5
$variantJson = json_encode($variants1);
$existing2 = \App\Models\Cart::where('customer_id', $customer->id)
    ->where('product_id', $product->id)
    ->where('status', 'active')
    ->whereRaw('MD5(CAST(variant_options AS TEXT)) = MD5(?)', [$variantJson])
    ->first();

if ($existing2) {
    echo "  ✅ Found existing cart item ID: {$existing2->id}\n";
    $existing2->update(['quantity' => $existing2->quantity + 2]);
    echo "  ✅ Updated quantity to: {$existing2->quantity}\n";
} else {
    echo "  ❌ Did NOT find existing item (would create duplicate!)\n";
}
echo "\n";

echo "Test 5: Add SAME product with DIFFERENT variants - Should CREATE NEW\n";
$variants2 = ['color' => 'Red', 'size' => 'M'];
$cartItem3 = \App\Models\Cart::create([
    'customer_id' => $customer->id,
    'product_id' => $product->id,
    'variant_options' => $variants2,
    'quantity' => 1,
    'unit_price' => 140.00,
    'status' => 'active',
]);
echo "  ✅ Created NEW cart item ID: {$cartItem3->id}, Variants: " . json_encode($cartItem3->variant_options) . "\n\n";

echo "Final Cart Contents:\n";
$allItems = \App\Models\Cart::where('customer_id', $customer->id)
    ->where('status', 'active')
    ->get();

foreach ($allItems as $item) {
    echo "  ID: {$item->id}, Product: {$item->product_id}, Qty: {$item->quantity}, Variants: " . json_encode($item->variant_options) . "\n";
}

echo "\n✅ Expected: 3 cart items (1 without variants, 2 with different variants)\n";
echo "✅ Actual: " . $allItems->count() . " cart items\n";
