<?php

/**
 * Test script for CSV API endpoints
 * This will create an admin user, login, and test both CSV endpoints
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\Store;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== CSV API Testing Script ===\n\n";

// Step 1: Create or find admin user
echo "Step 1: Creating/Finding admin user...\n";

$admin = Employee::where('email', 'admin@test.com')->first();

if (!$admin) {
    // Get first store and role
    $store = Store::first();
    $role = Role::first();
    
    if (!$store) {
        echo "✗ No stores found in database. Please create a store first.\n";
        exit(1);
    }
    
    if (!$role) {
        echo "✗ No roles found in database. Please create a role first.\n";
        exit(1);
    }
    
    $admin = Employee::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('password123'),
        'phone' => '01700000000',
        'store_id' => $store->id,
        'role_id' => $role->id,
        'is_active' => true,
        'is_in_service' => true,
    ]);
    echo "✓ Admin user created: admin@test.com / password123\n";
    echo "  Store ID: {$store->id} ({$store->name})\n";
    echo "  Role ID: {$role->id} ({$role->name})\n";
} else {
    echo "✓ Admin user already exists: admin@test.com\n";
}

echo "\n";

// Step 2: Login and get token
echo "Step 2: Logging in and getting JWT token...\n";

$loginUrl = 'http://localhost:8000/api/login';
$loginData = [
    'email' => 'admin@test.com',
    'password' => 'password123'
];

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "✗ Login failed with status code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$loginResponse = json_decode($response, true);

if (!isset($loginResponse['access_token'])) {
    echo "✗ No access token in response\n";
    echo "Response: $response\n";
    exit(1);
}

$token = $loginResponse['access_token'];
echo "✓ Login successful!\n";
echo "Token: " . substr($token, 0, 20) . "...\n";

echo "\n";

// Step 3: Test Category Sales CSV
echo "Step 3: Testing Category Sales CSV endpoint...\n";

$categoryUrl = 'http://localhost:8000/api/reporting/csv/category-sales';
$ch = curl_init($categoryUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "Status Code: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($httpCode === 200) {
    if (strpos($contentType, 'text/csv') !== false || strpos($contentType, 'application/octet-stream') !== false) {
        echo "✓ Category Sales CSV downloaded successfully!\n";
        echo "First 200 chars: " . substr($response, 0, 200) . "...\n";
        
        // Save to file
        file_put_contents(__DIR__ . '/category_sales_test.csv', $response);
        echo "✓ Saved to: category_sales_test.csv\n";
    } else {
        echo "⚠ Response is not CSV format\n";
        echo "Response preview: " . substr($response, 0, 500) . "\n";
    }
} else {
    echo "✗ Category Sales CSV request failed\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\n";

// Step 4: Test Stock CSV
echo "Step 4: Testing Stock CSV endpoint...\n";

$stockUrl = 'http://localhost:8000/api/reporting/csv/stock';
$ch = curl_init($stockUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "Status Code: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($httpCode === 200) {
    if (strpos($contentType, 'text/csv') !== false || strpos($contentType, 'application/octet-stream') !== false) {
        echo "✓ Stock CSV downloaded successfully!\n";
        echo "First 200 chars: " . substr($response, 0, 200) . "...\n";
        
        // Save to file
        file_put_contents(__DIR__ . '/stock_test.csv', $response);
        echo "✓ Saved to: stock_test.csv\n";
    } else {
        echo "⚠ Response is not CSV format\n";
        echo "Response preview: " . substr($response, 0, 500) . "\n";
    }
} else {
    echo "✗ Stock CSV request failed\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\n";

// Step 5: Check database for data
echo "Step 5: Checking database for test data...\n";

$ordersCount = DB::table('orders')->count();
$orderItemsCount = DB::table('order_items')->count();
$productsCount = DB::table('products')->whereNull('deleted_at')->count();
$productBatchesCount = DB::table('product_batches')->count();

echo "Orders: $ordersCount\n";
echo "Order Items: $orderItemsCount\n";
echo "Products: $productsCount\n";
echo "Product Batches: $productBatchesCount\n";

if ($ordersCount > 0 && $orderItemsCount > 0) {
    echo "✓ Database has test data for CSV exports\n";
} else {
    echo "⚠ Database is empty - CSV files may be empty\n";
}

echo "\n=== Testing Complete ===\n";
