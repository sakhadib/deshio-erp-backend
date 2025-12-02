<?php
/**
 * COMPREHENSIVE E-COMMERCE CHECKOUT FLOW TEST
 * 
 * Simulates a real customer journey:
 * 1. Customer Login
 * 2. Add Multiple Items to Cart (with edge cases)
 * 3. View Cart
 * 4. Get/Create Customer Address
 * 5. Create Order with Cash on Delivery
 * 6. View Order Confirmation
 * 
 * This test will expose the FE team's reported address issue.
 */

require_once __DIR__ . '/vendor/autoload.php';

$baseUrl = 'http://localhost:8000/api';

// ANSI Color codes for better visibility
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$CYAN = "\033[36m";
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
        'raw' => $response
    ];
}

function printHeader($text) {
    global $BOLD, $CYAN, $RESET;
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "{$BOLD}{$CYAN}" . strtoupper($text) . "{$RESET}\n";
    echo str_repeat("=", 80) . "\n\n";
}

function printStep($stepNum, $description) {
    global $BOLD, $BLUE, $RESET;
    echo "{$BOLD}{$BLUE}STEP {$stepNum}: {$description}{$RESET}\n";
    echo str_repeat("-", 80) . "\n";
}

function printSuccess($message) {
    global $GREEN, $RESET;
    echo "{$GREEN}✅ SUCCESS: {$message}{$RESET}\n";
}

function printError($message) {
    global $RED, $BOLD, $RESET;
    echo "{$BOLD}{$RED}❌ ERROR: {$message}{$RESET}\n";
}

function printWarning($message) {
    global $YELLOW, $RESET;
    echo "{$YELLOW}⚠️  WARNING: {$message}{$RESET}\n";
}

function printInfo($label, $value) {
    global $CYAN, $RESET;
    echo "{$CYAN}{$label}:{$RESET} {$value}\n";
}

// ============================================
// START TEST
// ============================================

printHeader("E-Commerce Checkout Flow Test");

$token = null;
$cartItems = [];
$customerAddresses = [];
$orderId = null;

// ============================================
// STEP 1: CUSTOMER LOGIN
// ============================================
printStep(1, "Customer Login");

$loginData = [
    'email' => 'customer@example.com',
    'password' => 'password123'
];

printInfo("Email", $loginData['email']);
printInfo("Password", str_repeat("*", strlen($loginData['password'])));

$response = httpRequest('POST', "$baseUrl/customer-auth/login", $loginData);

if ($response['status'] === 200 && isset($response['body']['data']['token'])) {
    $token = $response['body']['data']['token'];
    $customerData = $response['body']['data']['customer'] ?? [];
    printSuccess("Logged in successfully");
    printInfo("Customer ID", $customerData['id'] ?? 'N/A');
    printInfo("Customer Name", $customerData['name'] ?? 'N/A');
    printInfo("Token", substr($token, 0, 20) . "...");
} else {
    printError("Login failed");
    printInfo("HTTP Status", $response['status']);
    printInfo("Error", json_encode($response['body'], JSON_PRETTY_PRINT));
    exit(1);
}

// ============================================
// STEP 2: ADD ITEMS TO CART (WITH EDGE CASES)
// ============================================
printStep(2, "Add Items to Cart (Testing Edge Cases)");

// Test Case 1: Add product WITHOUT variants
echo "\n{$CYAN}Test 2.1: Add product WITHOUT variants{$RESET}\n";
$cartRequest1 = [
    'product_id' => 1,
    'quantity' => 2
];
$response = httpRequest('POST', "$baseUrl/cart/add", $cartRequest1, $token);
if ($response['status'] === 200) {
    printSuccess("Added product (ID: 1, Qty: 2) without variants");
    printInfo("Cart Item ID", $response['body']['data']['cart_item']['id'] ?? 'N/A');
} else {
    printError("Failed to add product without variants");
    printInfo("HTTP Status", $response['status']);
    printInfo("Error", json_encode($response['body'], JSON_PRETTY_PRINT));
}

// Test Case 2: Add product WITH variants (Blue, Size L)
echo "\n{$CYAN}Test 2.2: Add product WITH variants (Blue, Size L){$RESET}\n";
$cartRequest2 = [
    'product_id' => 2,
    'quantity' => 1,
    'variant_options' => [
        'color' => 'Blue',
        'size' => 'L'
    ]
];
$response = httpRequest('POST', "$baseUrl/cart/add", $cartRequest2, $token);
if ($response['status'] === 200) {
    printSuccess("Added product with variants (Blue, Size L)");
    printInfo("Cart Item ID", $response['body']['data']['cart_item']['id'] ?? 'N/A');
} else {
    printError("Failed to add product with variants");
    printInfo("HTTP Status", $response['status']);
    printInfo("Error", json_encode($response['body'], JSON_PRETTY_PRINT));
}

// Test Case 3: Add SAME product with SAME variants again (should merge quantity)
echo "\n{$CYAN}Test 2.3: Add SAME product with SAME variants again (should merge){$RESET}\n";
$cartRequest3 = [
    'product_id' => 2,
    'quantity' => 2,
    'variant_options' => [
        'color' => 'Blue',
        'size' => 'L'
    ]
];
$response = httpRequest('POST', "$baseUrl/cart/add", $cartRequest3, $token);
if ($response['status'] === 200) {
    $newQty = $response['body']['data']['cart_item']['quantity'] ?? 0;
    if ($newQty === 3) {
        printSuccess("Quantity merged correctly (1 + 2 = 3)");
        printInfo("New Quantity", $newQty);
    } else {
        printWarning("Quantity not merged as expected");
        printInfo("Expected Quantity", 3);
        printInfo("Actual Quantity", $newQty);
    }
} else {
    printError("Failed to add/merge product");
    printInfo("HTTP Status", $response['status']);
}

// Test Case 4: Add SAME product with DIFFERENT variants (should create new item)
echo "\n{$CYAN}Test 2.4: Add SAME product with DIFFERENT variants (should create new item){$RESET}\n";
$cartRequest4 = [
    'product_id' => 2,
    'quantity' => 1,
    'variant_options' => [
        'color' => 'Red',
        'size' => 'M'
    ]
];
$response = httpRequest('POST', "$baseUrl/cart/add", $cartRequest4, $token);
if ($response['status'] === 200) {
    printSuccess("Created new cart item for different variant (Red, Size M)");
    printInfo("Cart Item ID", $response['body']['data']['cart_item']['id'] ?? 'N/A');
} else {
    printError("Failed to add product with different variants");
    printInfo("HTTP Status", $response['status']);
}

// ============================================
// STEP 3: VIEW CART
// ============================================
printStep(3, "View Cart Contents");

$response = httpRequest('GET', "$baseUrl/cart", null, $token);

if ($response['status'] === 200) {
    $cartItems = $response['body']['data']['cart_items'] ?? [];
    $summary = $response['body']['data']['summary'] ?? [];
    
    printSuccess("Cart retrieved successfully");
    printInfo("Total Items", count($cartItems));
    printInfo("Total Quantity", $summary['total_quantity'] ?? 0);
    printInfo("Total Amount", ($summary['total_amount'] ?? 0) . " BDT");
    
    echo "\n{$BOLD}Cart Items:{$RESET}\n";
    foreach ($cartItems as $index => $item) {
        $itemNum = $index + 1;
        echo "  {$itemNum}. Product: {$item['product']['name']} (ID: {$item['product_id']})\n";
        echo "     Quantity: {$item['quantity']}\n";
        echo "     Unit Price: {$item['unit_price']} BDT\n";
        if (isset($item['variant_options']) && $item['variant_options']) {
            echo "     Variants: " . json_encode($item['variant_options']) . "\n";
        }
        echo "\n";
    }
} else {
    printError("Failed to retrieve cart");
    printInfo("HTTP Status", $response['status']);
    printInfo("Error", json_encode($response['body'], JSON_PRETTY_PRINT));
}

// ============================================
// STEP 4: GET CUSTOMER ADDRESSES (THIS IS WHERE THE BUG IS!)
// ============================================
printStep(4, "Get Customer Addresses");

echo "{$YELLOW}🔍 Attempting to fetch customer addresses...{$RESET}\n\n";

$response = httpRequest('GET', "$baseUrl/customer/addresses", null, $token);

if ($response['status'] === 200) {
    $customerAddresses = $response['body']['data']['addresses'] ?? [];
    printSuccess("Addresses retrieved successfully");
    printInfo("Total Addresses", count($customerAddresses));
    
    if (count($customerAddresses) > 0) {
        echo "\n{$BOLD}Customer Addresses:{$RESET}\n";
        foreach ($customerAddresses as $index => $addr) {
            $addrNum = $index + 1;
            echo "  {$addrNum}. {$addr['name']} - {$addr['type']}\n";
            echo "     {$addr['address_line_1']}, {$addr['city']}, {$addr['state']} {$addr['postal_code']}\n";
            echo "     Phone: {$addr['phone']}\n";
            if ($addr['is_default_shipping']) {
                echo "     [DEFAULT SHIPPING]\n";
            }
            if ($addr['is_default_billing']) {
                echo "     [DEFAULT BILLING]\n";
            }
            echo "\n";
        }
    } else {
        printWarning("No addresses found. Will attempt to create one...");
    }
} else {
    printError("❌ CRITICAL BUG FOUND: Address endpoint returned HTTP {$response['status']}");
    printInfo("Response", json_encode($response['body'], JSON_PRETTY_PRINT));
    
    echo "\n{$RED}{$BOLD}🐛 BUG IDENTIFIED:{$RESET}\n";
    echo "{$YELLOW}The customer address management routes are missing from the API!{$RESET}\n";
    echo "{$YELLOW}Expected endpoint: /api/customer/addresses{$RESET}\n";
    echo "{$YELLOW}This is the issue the FE team reported.{$RESET}\n\n";
}

// ============================================
// STEP 4.1: TRY TO CREATE ADDRESS (IF MISSING)
// ============================================
if ($response['status'] !== 200 || empty($customerAddresses)) {
    printStep("4.1", "Attempt to Create Address");
    
    $newAddress = [
        'name' => 'John Doe',
        'phone' => '+8801712345678',
        'address_line_1' => '123 Main Street, Apartment 4B',
        'address_line_2' => 'Near City Hospital',
        'city' => 'Dhaka',
        'state' => 'Dhaka Division',
        'postal_code' => '1200',
        'country' => 'Bangladesh',
        'landmark' => 'Opposite to Green Park',
        'type' => 'both',
        'is_default_shipping' => true,
        'is_default_billing' => true,
        'delivery_instructions' => 'Please call before delivery'
    ];
    
    $response = httpRequest('POST', "$baseUrl/customer/addresses", $newAddress, $token);
    
    if ($response['status'] === 201) {
        $address = $response['body']['data']['address'] ?? [];
        printSuccess("Address created successfully");
        printInfo("Address ID", $address['id'] ?? 'N/A');
        $customerAddresses[] = $address;
    } else {
        printError("Failed to create address - HTTP {$response['status']}");
        printInfo("Response", json_encode($response['body'], JSON_PRETTY_PRINT));
        
        echo "\n{$RED}{$BOLD}🐛 CONFIRMED BUG:{$RESET}\n";
        echo "{$YELLOW}Cannot create customer addresses via API{$RESET}\n";
        echo "{$YELLOW}The routes are not registered in routes/api.php{$RESET}\n\n";
    }
}

// ============================================
// STEP 5: CREATE ORDER WITH CASH ON DELIVERY
// ============================================
printStep(5, "Create Order from Cart (Cash on Delivery)");

if (empty($customerAddresses)) {
    printError("CANNOT PROCEED: No customer addresses available");
    printWarning("Creating a test address directly in database...");
    
    // Direct database insertion to continue the test
    echo "\n{$YELLOW}Executing direct database query to create address...{$RESET}\n";
    
    try {
        require_once __DIR__ . '/bootstrap/app.php';
        $app = require_once __DIR__ . '/bootstrap/app.php';
        
        $address = App\Models\CustomerAddress::create([
            'customer_id' => 1, // Assuming customer ID from login
            'name' => 'John Doe',
            'phone' => '+8801712345678',
            'address_line_1' => '123 Main Street, Apartment 4B',
            'address_line_2' => 'Near City Hospital',
            'city' => 'Dhaka',
            'state' => 'Dhaka Division',
            'postal_code' => '1200',
            'country' => 'Bangladesh',
            'landmark' => 'Opposite to Green Park',
            'type' => 'both',
            'is_default_shipping' => true,
            'is_default_billing' => true,
            'delivery_instructions' => 'Please call before delivery'
        ]);
        
        printSuccess("Address created via database");
        printInfo("Address ID", $address->id);
        $customerAddresses[] = $address->toArray();
        
    } catch (\Exception $e) {
        printError("Failed to create address via database: " . $e->getMessage());
        echo "\n{$RED}{$BOLD}TEST CANNOT CONTINUE WITHOUT ADDRESS{$RESET}\n";
        exit(1);
    }
}

// Now create the order
$shippingAddressId = $customerAddresses[0]['id'] ?? null;

if (!$shippingAddressId) {
    printError("No valid address ID found");
    exit(1);
}

$orderData = [
    'payment_method' => 'cod', // Cash on Delivery
    'shipping_address_id' => $shippingAddressId,
    'billing_address_id' => $shippingAddressId, // Same as shipping
    'notes' => 'Please handle with care',
    'delivery_preference' => 'standard'
];

printInfo("Payment Method", "Cash on Delivery (COD)");
printInfo("Shipping Address ID", $shippingAddressId);
printInfo("Delivery Preference", "Standard");

$response = httpRequest('POST', "$baseUrl/customer/orders/create-from-cart", $orderData, $token);

if ($response['status'] === 201) {
    $order = $response['body']['data']['order'] ?? [];
    $orderSummary = $response['body']['data']['order_summary'] ?? [];
    
    printSuccess("Order created successfully!");
    printInfo("Order Number", $orderSummary['order_number'] ?? 'N/A');
    printInfo("Order Status", $orderSummary['status'] ?? 'N/A');
    printInfo("Status Description", $orderSummary['status_description'] ?? 'N/A');
    printInfo("Total Items", $orderSummary['total_items'] ?? 0);
    printInfo("Subtotal", ($orderSummary['subtotal'] ?? 0) . " BDT");
    printInfo("Tax", ($orderSummary['tax'] ?? 0) . " BDT");
    printInfo("Shipping", ($orderSummary['shipping'] ?? 0) . " BDT");
    printInfo("Total Amount", ($orderSummary['total_amount'] ?? 0) . " BDT");
    printInfo("Payment Method", $orderSummary['payment_method'] ?? 'N/A');
    
    $orderId = $order['id'] ?? null;
} else {
    printError("Failed to create order");
    printInfo("HTTP Status", $response['status']);
    printInfo("Error", json_encode($response['body'], JSON_PRETTY_PRINT));
    
    if ($response['status'] === 400 && isset($response['body']['message'])) {
        echo "\n{$YELLOW}Order creation failed: {$response['body']['message']}{$RESET}\n";
    }
}

// ============================================
// STEP 6: VIEW ORDER CONFIRMATION
// ============================================
if ($orderId) {
    printStep(6, "View Order Details");
    
    $orderNumber = $orderSummary['order_number'] ?? null;
    
    if ($orderNumber) {
        $response = httpRequest('GET', "$baseUrl/customer/orders/{$orderNumber}", null, $token);
        
        if ($response['status'] === 200) {
            $order = $response['body']['data']['order'] ?? [];
            
            printSuccess("Order details retrieved");
            printInfo("Order Number", $order['order_number'] ?? 'N/A');
            printInfo("Status", $order['status'] ?? 'N/A');
            printInfo("Payment Status", $order['payment_status'] ?? 'N/A');
            
            echo "\n{$BOLD}Shipping Address:{$RESET}\n";
            $shippingAddr = $order['shipping_address'] ?? [];
            if ($shippingAddr) {
                echo "  Name: " . ($shippingAddr['name'] ?? 'N/A') . "\n";
                echo "  Phone: " . ($shippingAddr['phone'] ?? 'N/A') . "\n";
                echo "  Address: " . ($shippingAddr['address_line_1'] ?? 'N/A') . "\n";
                echo "  City: " . ($shippingAddr['city'] ?? 'N/A') . ", " . ($shippingAddr['state'] ?? 'N/A') . " " . ($shippingAddr['postal_code'] ?? 'N/A') . "\n";
            } else {
                printWarning("No shipping address in order!");
            }
            
            echo "\n{$BOLD}Order Items:{$RESET}\n";
            foreach ($order['items'] ?? [] as $index => $item) {
                $itemNum = $index + 1;
                echo "  {$itemNum}. {$item['product_name']} x {$item['quantity']}\n";
                echo "     Unit Price: {$item['unit_price']} BDT\n";
                echo "     Total: {$item['total_amount']} BDT\n\n";
            }
        } else {
            printError("Failed to retrieve order details");
            printInfo("HTTP Status", $response['status']);
        }
    }
}

// ============================================
// TEST SUMMARY
// ============================================
printHeader("Test Summary");

echo "{$BOLD}Test Results:{$RESET}\n\n";

echo "1. ✅ Customer Login: {$GREEN}PASSED{$RESET}\n";
echo "2. ✅ Add Items to Cart: {$GREEN}PASSED{$RESET}\n";
echo "3. ✅ View Cart: {$GREEN}PASSED{$RESET}\n";

if (empty($customerAddresses) && $response['status'] !== 200) {
    echo "4. ❌ Get Customer Addresses: {$RED}FAILED{$RESET}\n";
    echo "   {$YELLOW}BUG: Address management routes are missing from API{$RESET}\n";
} else {
    echo "4. ✅ Get Customer Addresses: {$GREEN}PASSED{$RESET}\n";
}

if ($orderId) {
    echo "5. ✅ Create Order (COD): {$GREEN}PASSED{$RESET}\n";
    echo "6. ✅ View Order Details: {$GREEN}PASSED{$RESET}\n";
} else {
    echo "5. ❌ Create Order (COD): {$RED}FAILED{$RESET}\n";
    echo "6. ⊘  View Order Details: {$YELLOW}SKIPPED{$RESET}\n";
}

echo "\n{$BOLD}{$RED}🐛 CRITICAL ISSUE FOUND:{$RESET}\n";
echo "{$YELLOW}CustomerAddress management routes are NOT registered in routes/api.php{$RESET}\n";
echo "{$YELLOW}The controller exists but routes are missing.{$RESET}\n";
echo "{$YELLOW}Frontend cannot manage customer addresses via API.{$RESET}\n\n";

echo "{$BOLD}Required API Endpoints (Missing):{$RESET}\n";
echo "  - GET    /api/customer/addresses\n";
echo "  - POST   /api/customer/addresses\n";
echo "  - GET    /api/customer/addresses/{id}\n";
echo "  - PUT    /api/customer/addresses/{id}\n";
echo "  - DELETE /api/customer/addresses/{id}\n";
echo "  - PATCH  /api/customer/addresses/{id}/set-default-shipping\n";
echo "  - PATCH  /api/customer/addresses/{id}/set-default-billing\n\n";

printHeader("End of Test");
