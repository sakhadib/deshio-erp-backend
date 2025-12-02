<?php
/**
 * SIMPLIFIED CHECKOUT FLOW TEST
 * Focus on identifying FE team's address issue
 */

require_once __DIR__ . '/vendor/autoload.php';

$baseUrl = 'http://localhost:8000/api';

// ANSI Colors
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$BOLD = "\033[1m";
$RESET = "\033[0m";

function httpRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true),
    ];
}

echo "\n{$BOLD}{$BLUE}=== E-COMMERCE CHECKOUT FLOW TEST ==={$RESET}\n\n";

// STEP 1: Login
echo "{$YELLOW}Step 1: Customer Login{$RESET}\n";
$response = httpRequest('POST', "$baseUrl/customer-auth/login", [
    'email' => 'customer@example.com',
    'password' => 'password123'
]);

if ($response['status'] === 200) {
    $token = $response['body']['data']['token'];
    echo "{$GREEN}✅ Login successful{$RESET}\n";
    echo "   Customer ID: {$response['body']['data']['customer']['id']}\n\n";
} else {
    echo "{$RED}❌ Login failed{$RESET}\n";
    exit(1);
}

// STEP 2: Add item to cart
echo "{$YELLOW}Step 2: Add Product to Cart{$RESET}\n";
$response = httpRequest('POST', "$baseUrl/cart/add", [
    'product_id' => 1,
    'quantity' => 2
], $token);

if ($response['status'] === 200) {
    echo "{$GREEN}✅ Product added to cart{$RESET}\n";
    echo "   Cart Item ID: {$response['body']['data']['cart_item']['id']}\n\n";
} else {
    echo "{$RED}❌ Failed to add product{$RESET}\n";
    echo "   Error: " . json_encode($response['body']) . "\n\n";
}

// STEP 3: View cart
echo "{$YELLOW}Step 3: View Cart{$RESET}\n";
$response = httpRequest('GET', "$baseUrl/cart", null, $token);

if ($response['status'] === 200) {
    $totalItems = count($response['body']['data']['cart_items'] ?? []);
    $totalAmount = $response['body']['data']['summary']['total_amount'] ?? 0;
    echo "{$GREEN}✅ Cart retrieved{$RESET}\n";
    echo "   Items: {$totalItems}\n";
    echo "   Total: {$totalAmount} BDT\n\n";
} else {
    echo "{$RED}❌ Failed to get cart{$RESET}\n\n";
}

// STEP 4: Get Customer Addresses - THIS IS THE BUG!
echo "{$YELLOW}Step 4: Get Customer Addresses{$RESET}\n";
echo "{$BOLD}Trying: GET /api/customer/addresses{$RESET}\n";

$response = httpRequest('GET', "$baseUrl/customer/addresses", null, $token);

if ($response['status'] === 200) {
    $addresses = $response['body']['data']['addresses'] ?? [];
    echo "{$GREEN}✅ Addresses retrieved{$RESET}\n";
    echo "   Total: " . count($addresses) . "\n\n";
} else {
    echo "{$RED}❌ CRITICAL BUG: HTTP {$response['status']}{$RESET}\n";
    echo "{$YELLOW}🐛 Address endpoint doesn't exist!{$RESET}\n";
    echo "{$YELLOW}   Frontend cannot fetch customer addresses{$RESET}\n";
    echo "{$YELLOW}   This is the issue the FE team reported{$RESET}\n\n";
    
    // Try alternative endpoints
    echo "{$YELLOW}Trying alternative: GET /api/profile{$RESET}\n";
    $response2 = httpRequest('GET', "$baseUrl/profile", null, $token);
    if ($response2['status'] === 200) {
        $customer = $response2['body']['data']['customer'] ?? [];
        echo "{$GREEN}✅ Profile endpoint works{$RESET}\n";
        echo "   Has address field: " . ($customer['address'] ?? 'N/A') . "\n";
        echo "   {$YELLOW}BUT: This is basic address string, not structured CustomerAddress{$RESET}\n\n";
    }
}

// STEP 5: Try to create address via API
echo "{$YELLOW}Step 5: Try to Create Address via API{$RESET}\n";
echo "{$BOLD}Trying: POST /api/customer/addresses{$RESET}\n";

$addressData = [
    'name' => 'Jane Smith',
    'phone' => '+8801987654321',
    'address_line_1' => '456 Side Street',
    'address_line_2' => 'Apartment 5C',
    'city' => 'Chittagong',
    'state' => 'Chittagong Division',
    'postal_code' => '4100',
    'country' => 'Bangladesh',
    'type' => 'shipping',
    'is_default_shipping' => false,
];

$response = httpRequest('POST', "$baseUrl/customer/addresses", $addressData, $token);

if ($response['status'] === 201) {
    echo "{$GREEN}✅ Address created{$RESET}\n\n";
} else {
    echo "{$RED}❌ CRITICAL BUG: HTTP {$response['status']}{$RESET}\n";
    echo "{$YELLOW}🐛 Cannot create address via API!{$RESET}\n";
    echo "{$YELLOW}   Frontend cannot add delivery addresses{$RESET}\n\n";
}

// STEP 6: Checkout (using pre-created address from DB)
echo "{$YELLOW}Step 6: Create Order from Cart (Cash on Delivery){$RESET}\n";
echo "{$BOLD}Using address ID 1 (created directly in DB){$RESET}\n";

$orderData = [
    'payment_method' => 'cod',
    'shipping_address_id' => 1,
    'billing_address_id' => 1,
    'notes' => 'Test order',
    'delivery_preference' => 'standard'
];

$response = httpRequest('POST', "$baseUrl/customer/orders/create-from-cart", $orderData, $token);

if ($response['status'] === 201) {
    $order = $response['body']['data']['order_summary'] ?? [];
    echo "{$GREEN}✅ Order created successfully{$RESET}\n";
    echo "   Order Number: {$order['order_number']}\n";
    echo "   Status: {$order['status']}\n";
    echo "   Total: {$order['total_amount']} BDT\n";
    echo "   Payment: {$order['payment_method']}\n\n";
} else {
    echo "{$RED}❌ Order creation failed: HTTP {$response['status']}{$RESET}\n";
    echo "   Error: " . ($response['body']['message'] ?? 'Unknown error') . "\n";
    if (isset($response['body']['errors'])) {
        echo "   Errors: " . json_encode($response['body']['errors'], JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

// SUMMARY
echo "\n{$BOLD}{$BLUE}=== BUGS IDENTIFIED ==={$RESET}\n\n";
echo "{$RED}🐛 BUG #1: Customer Address Management Routes Missing{$RESET}\n";
echo "   Controller exists: app/Http/Controllers/CustomerAddressController.php\n";
echo "   But routes NOT registered in routes/api.php\n\n";
echo "   {$YELLOW}Missing endpoints:{$RESET}\n";
echo "   - GET    /api/customer/addresses\n";
echo "   - POST   /api/customer/addresses\n";
echo "   - GET    /api/customer/addresses/{id}\n";
echo "   - PUT    /api/customer/addresses/{id}\n";
echo "   - DELETE /api/customer/addresses/{id}\n";
echo "   - PATCH  /api/customer/addresses/{id}/set-default-shipping\n";
echo "   - PATCH  /api/customer/addresses/{id}/set-default-billing\n\n";

echo "{$YELLOW}Impact:{$RESET}\n";
echo "   - Frontend cannot fetch customer addresses\n";
echo "   - Frontend cannot create/update addresses\n";
echo "   - Customers cannot manage delivery addresses\n";
echo "   - Order checkout requires database manipulation\n\n";

echo "{$YELLOW}Solution:{$RESET}\n";
echo "   Add CustomerAddressController routes to routes/api.php\n\n";
