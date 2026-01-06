<?php

/**
 * Test script for order tracking by phone number
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

echo "=== Order Tracking by Phone Test ===\n\n";

// Step 1: Check existing customers with phone numbers
echo "Step 1: Finding customers with orders...\n";

$customers = Customer::whereHas('orders')->with('orders')->take(3)->get();

if ($customers->isEmpty()) {
    echo "✗ No customers with orders found\n";
    exit(1);
}

echo "✓ Found {$customers->count()} customer(s) with orders\n\n";

foreach ($customers as $customer) {
    echo "Customer: {$customer->name} | Phone: {$customer->phone} | Orders: {$customer->orders->count()}\n";
}

echo "\n";

// Step 2: Test the API with different phone formats
echo "Step 2: Testing API with different phone formats...\n\n";

$testPhone = $customers->first()->phone;
echo "Original phone in DB: {$testPhone}\n\n";

// Test cases
$testCases = [
    ['label' => 'Exact match', 'phone' => $testPhone],
    ['label' => 'With spaces', 'phone' => substr($testPhone, 0, 5) . ' ' . substr($testPhone, 5)],
    ['label' => 'With mobile no text', 'phone' => 'mobile no ' . $testPhone],
    ['label' => 'With dashes', 'phone' => substr($testPhone, 0, 3) . '-' . substr($testPhone, 3)],
    ['label' => 'With +88 prefix', 'phone' => '+88' . $testPhone],
];

$apiUrl = 'http://localhost:8000/api/guest-orders/by-phone';

foreach ($testCases as $index => $testCase) {
    echo "Test " . ($index + 1) . ": {$testCase['label']}\n";
    echo "Phone sent: '{$testCase['phone']}'\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['phone' => $testCase['phone']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    echo "Status: {$httpCode}\n";
    
    if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
        echo "✓ SUCCESS - Found {$responseData['data']['total_orders']} order(s)\n";
        echo "  Customer: {$responseData['data']['customer']['name']}\n";
    } else {
        echo "✗ FAILED\n";
        if (isset($responseData['message'])) {
            echo "  Message: {$responseData['message']}\n";
        }
        if (isset($responseData['errors'])) {
            echo "  Errors: " . json_encode($responseData['errors']) . "\n";
        }
    }
    
    echo "\n";
}

// Step 3: Check how phone is cleaned in the controller
echo "Step 3: Testing phone cleaning logic...\n\n";

$phoneTests = [
    'mobile no 01712345678',
    '01712345678',
    '+8801712345678',
    '017-123-45678',
    '017 123 45678',
];

foreach ($phoneTests as $phone) {
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    echo "Input: '{$phone}'\n";
    echo "Cleaned: '{$cleanPhone}'\n";
    echo "\n";
}

echo "=== Test Complete ===\n";
