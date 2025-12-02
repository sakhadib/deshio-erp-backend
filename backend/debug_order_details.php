<?php
/**
 * Debug order details endpoint
 */

require_once __DIR__ . '/vendor/autoload.php';

$baseUrl = 'http://localhost:8000/api';

// Login
$response = json_decode(file_get_contents("$baseUrl/customer-auth/login", false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['email' => 'customer@example.com', 'password' => 'password123'])
    ]
])), true);

$token = $response['data']['token'];

// Get orders
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/customer/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
print_r(json_decode($response, true));

$orders = json_decode($response, true);
if (isset($orders['data']['orders'][0])) {
    $firstOrder = $orders['data']['orders'][0];
    $orderNumber = $firstOrder['order_number'];
    
    echo "\n\nTrying to get order details for: $orderNumber\n\n";
    
    // Get order details
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/customer/orders/$orderNumber");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer $token"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Status: $httpCode\n";
    echo "Response:\n";
    print_r(json_decode($response, true));
}
