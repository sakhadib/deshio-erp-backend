<?php

/**
 * Comprehensive test for phone tracking fix
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;

echo "═══════════════════════════════════════════════════════\n";
echo "    PHONE TRACKING FIX - COMPREHENSIVE TEST\n";
echo "═══════════════════════════════════════════════════════\n\n";

$customer = Customer::where('phone', 'LIKE', '01%')
    ->whereHas('orders')
    ->first();

if (!$customer) {
    echo "No test customer found\n";
    exit(1);
}

echo "Test Customer: {$customer->name}\n";
echo "Phone: {$customer->phone}\n";
echo "Orders: {$customer->orders->count()}\n\n";

$apiUrl = 'http://localhost:8000/api/guest-orders/by-phone';

// All possible edge cases
$testCases = [
    // Standard formats
    ['name' => 'Standard format', 'phone' => $customer->phone, 'shouldWork' => true],
    ['name' => 'With +88', 'phone' => '+88' . $customer->phone, 'shouldWork' => true],
    
    // PM's reported issue
    ['name' => 'With "mobile no" prefix (PM issue)', 'phone' => 'mobile no ' . $customer->phone, 'shouldWork' => true],
    ['name' => 'With "Mobile No" mixed case', 'phone' => 'Mobile No ' . $customer->phone, 'shouldWork' => true],
    ['name' => 'With "mobile:" format', 'phone' => 'mobile: ' . $customer->phone, 'shouldWork' => true],
    
    // Bengali text
    ['name' => 'Bengali মোবাইল নং', 'phone' => 'মোবাইল নং ' . $customer->phone, 'shouldWork' => true],
    ['name' => 'Bengali ফোন', 'phone' => 'ফোন ' . $customer->phone, 'shouldWork' => true],
    
    // Formatting variations
    ['name' => 'With dashes', 'phone' => substr($customer->phone, 0, 3) . '-' . substr($customer->phone, 3), 'shouldWork' => true],
    ['name' => 'With spaces', 'phone' => implode(' ', str_split($customer->phone, 3)), 'shouldWork' => true],
    ['name' => 'With parentheses', 'phone' => '(' . substr($customer->phone, 0, 3) . ')' . substr($customer->phone, 3), 'shouldWork' => true],
    
    // Extra whitespace
    ['name' => 'Leading/trailing spaces', 'phone' => '  ' . $customer->phone . '  ', 'shouldWork' => true],
    ['name' => 'Multiple spaces between', 'phone' => str_replace('', '   ', $customer->phone), 'shouldWork' => true],
    
    // User input errors
    ['name' => 'Copy-paste with label', 'phone' => 'Phone Number: ' . $customer->phone, 'shouldWork' => true],
    ['name' => 'Copy-paste with Tel', 'phone' => 'Tel: ' . $customer->phone, 'shouldWork' => true],
    ['name' => 'With Contact prefix', 'phone' => 'Contact ' . $customer->phone, 'shouldWork' => true],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $index => $test) {
    echo "[" . ($index + 1) . "/" . count($testCases) . "] ";
    echo $test['name'] . "\n";
    echo "    Input: '{$test['phone']}'\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['phone' => $test['phone']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    $success = ($httpCode === 200 && isset($responseData['success']) && $responseData['success']);
    
    if ($success === $test['shouldWork']) {
        echo "    ✓ PASS";
        $passed++;
        if ($success) {
            echo " - Found {$responseData['data']['total_orders']} order(s)";
        }
        echo "\n";
    } else {
        echo "    ✗ FAIL";
        $failed++;
        if (!$success && isset($responseData['message'])) {
            echo " - {$responseData['message']}";
        }
        echo "\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "                    TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════\n";
echo "Total Tests: " . count($testCases) . "\n";
echo "Passed: {$passed} ✓\n";
echo "Failed: {$failed} ✗\n";
echo "Success Rate: " . round(($passed / count($testCases)) * 100, 1) . "%\n";
echo "═══════════════════════════════════════════════════════\n\n";

if ($failed === 0) {
    echo "🎉 ALL TESTS PASSED! The fix is working correctly.\n";
    echo "\nThe API now handles:\n";
    echo "  ✓ Clean phone numbers\n";
    echo "  ✓ Phone with 'mobile no' prefix (PM's issue)\n";
    echo "  ✓ Bengali text prefixes\n";
    echo "  ✓ Various formatting (dashes, spaces, parentheses)\n";
    echo "  ✓ Copy-paste errors with labels\n";
    echo "  ✓ Extra whitespace\n";
} else {
    echo "⚠ Some tests failed. Review the output above.\n";
}
