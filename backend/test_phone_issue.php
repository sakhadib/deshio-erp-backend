<?php

/**
 * Test order tracking with actual phone number
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;

echo "=== Testing Order Tracking with Real Phone ===\n\n";

// Get customer with actual phone number
$customer = Customer::where('phone', 'LIKE', '01%')
    ->whereHas('orders')
    ->first();

if (!$customer) {
    echo "No customer found with 01... phone pattern\n";
    exit(1);
}

echo "Testing with customer: {$customer->name}\n";
echo "Phone in DB: {$customer->phone}\n";
echo "Orders count: {$customer->orders->count()}\n\n";

$apiUrl = 'http://localhost:8000/api/guest-orders/by-phone';

// Test scenarios that PM mentioned
$testCases = [
    [
        'name' => 'Clean phone number (should work)',
        'payload' => ['phone' => $customer->phone]
    ],
    [
        'name' => 'With "mobile no" text (PM mentioned this issue)',
        'payload' => ['phone' => 'mobile no ' . $customer->phone]
    ],
    [
        'name' => 'With extra spaces',
        'payload' => ['phone' => '  ' . $customer->phone . '  ']
    ],
    [
        'name' => 'With Bengali text (মোবাইল নং)',
        'payload' => ['phone' => 'মোবাইল নং ' . $customer->phone]
    ],
];

foreach ($testCases as $test) {
    echo "═══════════════════════════════════════\n";
    echo "Test: {$test['name']}\n";
    echo "Payload: " . json_encode($test['payload']) . "\n";
    echo "───────────────────────────────────────\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['payload']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    echo "HTTP Status: {$httpCode}\n";
    
    if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
        echo "✓ SUCCESS\n";
        echo "  Customer: {$responseData['data']['customer']['name']}\n";
        echo "  Phone returned: {$responseData['data']['customer']['phone']}\n";
        echo "  Orders found: {$responseData['data']['total_orders']}\n";
    } else {
        echo "✗ FAILED\n";
        echo "  Success: " . ($responseData['success'] ?? 'N/A') . "\n";
        echo "  Message: " . ($responseData['message'] ?? 'N/A') . "\n";
        if (isset($responseData['errors'])) {
            echo "  Errors: " . json_encode($responseData['errors'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";
}

echo "=== Analysis ===\n\n";

// Show what happens with cleaning
$problematicPhone = 'mobile no ' . $customer->phone;
$cleaned = preg_replace('/[^0-9+]/', '', $problematicPhone);

echo "Problematic input: '{$problematicPhone}'\n";
echo "After cleaning: '{$cleaned}'\n";
echo "Expected in DB: '{$customer->phone}'\n";
echo "Match: " . ($cleaned === $customer->phone ? 'YES ✓' : 'NO ✗') . "\n\n";

// Check validation regex
echo "Validation regex: /^[0-9+\-\s()]+$/\n";
echo "Does 'mobile no 01712345678' match? " . (preg_match('/^[0-9+\-\s()]+$/', $problematicPhone) ? 'YES' : 'NO') . "\n";
echo "Issue: Letters 'm', 'o', 'b', 'i', 'l', 'e', 'n', 'o' are NOT allowed by validation!\n\n";

echo "=== Test Complete ===\n";
