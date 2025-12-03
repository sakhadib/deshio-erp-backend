<?php

// Test Cart API via HTTP (simulating frontend request)

$baseUrl = 'http://localhost:8000/api';

echo "=== CART API HTTP TEST ===\n\n";

// Step 1: Login as customer
echo "Step 1: Customer Login...\n";
$loginData = [
    'email' => 'test.ecommerce@example.com',
    'password' => 'password123',
];

$ch = curl_init("$baseUrl/customer-auth/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Login failed (HTTP $httpCode): $response\n";
    exit(1);
}

$loginResponse = json_decode($response, true);
$token = $loginResponse['data']['token'] ?? $loginResponse['token'] ?? $loginResponse['access_token'] ?? null;

if (!$token) {
    echo "❌ No token in response:\n";
    echo $response . "\n";
    exit(1);
}

echo "✅ Login successful! Token: " . substr($token, 0, 30) . "...\n";

// Step 2: Get a product ID from database
echo "\nStep 2: Getting product ID from database...\n";
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$product = \App\Models\Product::with('batches')->first();
if (!$product) {
    echo "❌ No products found\n";
    exit(1);
}
echo "✅ Found product: ID {$product->id}, Name: {$product->name}\n";

// Step 3: Clear existing cart
echo "\nStep 3: Clearing existing cart...\n";
\App\Models\Cart::where('customer_id', 2)->delete();
echo "✅ Cart cleared\n";

// Step 4: Add to cart WITHOUT variants
echo "\nStep 4: Testing POST /api/cart/add (without variants)...\n";
$addToCartData = [
    'product_id' => $product->id,
    'quantity' => 2,
];

$ch = curl_init("$baseUrl/cart/add");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($addToCartData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Successfully added to cart!\n";
    $cartResponse = json_decode($response, true);
    if (isset($cartResponse['data']['cart_item'])) {
        $cartItem = $cartResponse['data']['cart_item'];
        echo "   Cart Item ID: {$cartItem['id']}\n";
        echo "   Quantity: {$cartItem['quantity']}\n";
        echo "   Unit Price: {$cartItem['unit_price']}\n";
    }
} else {
    echo "❌ Failed to add to cart\n";
}

// Step 5: Add to cart WITH variants
echo "\nStep 5: Testing POST /api/cart/add (with variants)...\n";
$addToCartData2 = [
    'product_id' => $product->id,
    'quantity' => 1,
    'variant_options' => [
        'color' => 'Blue',
        'size' => 'L',
    ],
];

$ch = curl_init("$baseUrl/cart/add");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($addToCartData2));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Successfully added product with variants!\n";
} else {
    echo "❌ Failed to add product with variants\n";
}

// Step 6: Get cart
echo "\nStep 6: Testing GET /api/cart...\n";
$ch = curl_init("$baseUrl/cart");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";

if ($httpCode === 200) {
    echo "✅ Cart retrieved successfully!\n";
    $cartResponse = json_decode($response, true);
    if (isset($cartResponse['data']['cart_items'])) {
        $items = $cartResponse['data']['cart_items'];
        echo "   Total items in cart: " . count($items) . "\n";
        foreach ($items as $i => $item) {
            echo "   Item " . ($i + 1) . ": {$item['product']['name']}, Qty: {$item['quantity']}, Variants: " . json_encode($item['variant_options']) . "\n";
        }
    }
} else {
    echo "❌ Failed to get cart\n";
    echo "Response: $response\n";
}

echo "\n=== TEST COMPLETE ===\n";
