<?php

// Test multiple adds via HTTP API (simulating real customer behavior)

$baseUrl = 'http://localhost:8000/api';

echo "=== REAL CUSTOMER CART SCENARIO TEST ===\n\n";

// Step 1: Login
echo "Step 1: Customer Login...\n";
$loginData = ['email' => 'test.ecommerce@example.com', 'password' => 'password123'];

$ch = curl_init("$baseUrl/customer-auth/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$loginResponse = json_decode($response, true);
$token = $loginResponse['data']['token'] ?? null;
if (!$token) die("❌ Login failed\n");
echo "✅ Logged in\n\n";

// Clear cart first
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
DB::table('carts')->where('customer_id', 2)->delete();
echo "✅ Cart cleared for clean test\n\n";

// Scenario: Customer browsing and adding products
echo "--- Scenario: Customer Adding Multiple Products ---\n\n";

// Step 2: Customer adds Blue T-Shirt (Size L) - First time
echo "1. Customer adds Blue T-Shirt (Size L) - Quantity 1\n";
$ch = curl_init("$baseUrl/cart/add");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'product_id' => 1,
    'quantity' => 1,
    'variant_options' => ['color' => 'Blue', 'size' => 'L']
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP $httpCode - " . ($httpCode == 200 ? "✅ Added" : "❌ Failed: $response") . "\n\n";

// Step 3: Customer adds same Blue T-Shirt (Size L) again - Should UPDATE quantity
echo "2. Customer adds SAME Blue T-Shirt (Size L) again - Quantity 2 more\n";
echo "   Expected: Update existing cart item to quantity 3\n";
$ch = curl_init("$baseUrl/cart/add");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'product_id' => 1,
    'quantity' => 2,
    'variant_options' => ['color' => 'Blue', 'size' => 'L']
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $result = json_decode($response, true);
    $qty = $result['data']['cart_item']['quantity'] ?? 0;
    echo "   HTTP $httpCode - ✅ Updated! New quantity: $qty\n";
    if ($qty == 3) {
        echo "   ✅ CORRECT: Quantity merged (1 + 2 = 3)\n";
    } else {
        echo "   ❌ WRONG: Expected 3, got $qty\n";
    }
} else {
    echo "   HTTP $httpCode - ❌ Failed: $response\n";
}
echo "\n";

// Step 4: Customer adds Red T-Shirt (Size M) - Different variant, should be NEW item
echo "3. Customer adds Red T-Shirt (Size M) - Quantity 1\n";
echo "   Expected: Create NEW cart item (different variant)\n";
$ch = curl_init("$baseUrl/cart/add");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'product_id' => 1,
    'quantity' => 1,
    'variant_options' => ['color' => 'Red', 'size' => 'M']
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP $httpCode - " . ($httpCode == 200 ? "✅ Added as new item" : "❌ Failed") . "\n\n";

// Step 5: Customer adds product without variants
echo "4. Customer adds product WITHOUT variants - Quantity 2\n";
$ch = curl_init("$baseUrl/cart/add");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'product_id' => 1,
    'quantity' => 2
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP $httpCode - " . ($httpCode == 200 ? "✅ Added" : "❌ Failed") . "\n\n";

// Step 6: Get cart to see final state
echo "5. Get cart contents\n";
$ch = curl_init("$baseUrl/cart");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $cartData = json_decode($response, true);
    $items = $cartData['data']['cart_items'] ?? [];
    echo "   ✅ Cart has " . count($items) . " items:\n";
    foreach ($items as $i => $item) {
        $variants = json_encode($item['variant_options']);
        echo "      Item " . ($i+1) . ": Qty {$item['quantity']}, Variants: $variants\n";
    }
    
    $summary = $cartData['data']['summary'] ?? [];
    echo "\n   Cart Summary:\n";
    echo "      Total Items: {$summary['total_items']}\n";
    echo "      Total Amount: {$summary['total_amount']} BDT\n";
    
    echo "\n   ✅ Expected: 3 separate cart items\n";
    echo "   ✅ Actual: " . count($items) . " cart items\n";
    
    if (count($items) == 3) {
        echo "\n   🎉 SUCCESS! Cart works correctly:\n";
        echo "      - Same product + same variants = merged quantity ✅\n";
        echo "      - Same product + different variants = separate items ✅\n";
        echo "      - Product without variants = separate item ✅\n";
    }
} else {
    echo "   ❌ Failed to get cart\n";
}

echo "\n=== TEST COMPLETE ===\n";
