<?php

/**
 * Pathao API Integration Test - Using Controllers
 * 
 * This script tests the complete flow using API controller methods:
 * 1. Create store with Pathao config
 * 2. Create admin user
 * 3. Create products
 * 4. Create customer
 * 5. Create order
 * 6. Create shipment using ShipmentController
 * 7. Send to Pathao using ShipmentController
 * 
 * Run: php test_pathao_api.php
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Store;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PathaoService;
use App\Http\Controllers\ShipmentController;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "     PATHAO API TEST - USING CONTROLLER METHODS               \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // ============================================================
    // STEP 1: Test Pathao Connection
    // ============================================================
    echo "📡 STEP 1: Testing Pathao Connection...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $pathaoService = new PathaoService();
    
    echo "   Pathao Base URL: " . config('services.pathao.base_url') . "\n";
    echo "   Client ID: " . config('services.pathao.client_id') . "\n";
    echo "   Username: " . config('services.pathao.username') . "\n\n";
    
    // Get access token
    echo "   🔐 Obtaining access token...\n";
    $token = $pathaoService->getAccessToken();
    echo "   ✅ Access token obtained: " . substr($token, 0, 50) . "...\n\n";
    
    // Test cities API
    echo "   📍 Fetching cities from Pathao...\n";
    $citiesResult = $pathaoService->getCities();
    if (!$citiesResult['success']) {
        throw new Exception("Failed to fetch cities: " . $citiesResult['error']);
    }
    
    echo "   ✅ Successfully fetched " . count($citiesResult['cities']) . " cities\n";
    
    // Find Dhaka
    $dhakaCity = collect($citiesResult['cities'])->firstWhere('city_name', 'Dhaka');
    if (!$dhakaCity) {
        throw new Exception("Dhaka not found");
    }
    echo "   ✅ Found Dhaka - City ID: " . $dhakaCity['city_id'] . "\n\n";
    
    // Get zones for Dhaka
    echo "   📍 Fetching zones for Dhaka...\n";
    $zonesResult = $pathaoService->getZones($dhakaCity['city_id']);
    if (!$zonesResult['success']) {
        throw new Exception("Failed to fetch zones");
    }
    
    echo "   ✅ Successfully fetched " . count($zonesResult['zones']) . " zones\n";
    
    // Find Uttara
    $uttaraZone = collect($zonesResult['zones'])->first(function($zone) {
        return stripos($zone['zone_name'], 'Uttara') !== false;
    });
    if (!$uttaraZone) {
        throw new Exception("Uttara not found");
    }
    echo "   ✅ Found Uttara Zone - Zone ID: " . $uttaraZone['zone_id'] . "\n\n";
    
    // Get areas for Uttara
    echo "   📍 Fetching areas for Uttara...\n";
    $areasResult = $pathaoService->getAreas($uttaraZone['zone_id']);
    if (!$areasResult['success']) {
        throw new Exception("Failed to fetch areas");
    }
    
    echo "   ✅ Successfully fetched " . count($areasResult['areas']) . " areas\n";
    $sector7Area = collect($areasResult['areas'])->first();
    if ($sector7Area) {
        echo "   ✅ Found area - Area ID: " . $sector7Area['area_id'] . "\n";
    }
    
    echo "\n✅ STEP 1 COMPLETE: Pathao connection working!\n\n";
    
    // ============================================================
    // STEP 2: Setup Store, Admin, Products (Quick Setup)
    // ============================================================
    echo "🏪 STEP 2: Setting up test data...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    DB::beginTransaction();
    
    // Create/Update Store
    $store = Store::where('name', 'Test Store - API')->first();
    if (!$store) {
        $store = Store::create([
            'name' => 'Test Store - API',
            'code' => 'TST-API-001',
            'address' => 'House 10, Road 5, Dhanmondi, Dhaka-1205',
            'phone' => '01700000002',
            'email' => 'store.api@deshio.com',
            'is_active' => true,
            'pathao_store_id' => '261222',
            'pathao_contact_name' => 'Store Manager',
            'pathao_contact_number' => '01700000002',
            'pathao_city_id' => $dhakaCity['city_id'],
            'pathao_zone_id' => $uttaraZone['zone_id'],
            'pathao_area_id' => $sector7Area['area_id'] ?? 1,
        ]);
        echo "   ✅ Created store: {$store->name}\n";
    } else {
        $store->update([
            'pathao_store_id' => '261222',
            'pathao_city_id' => $dhakaCity['city_id'],
            'pathao_zone_id' => $uttaraZone['zone_id'],
            'pathao_area_id' => $sector7Area['area_id'] ?? 1,
        ]);
        echo "   ✅ Updated existing store: {$store->name}\n";
    }
    
    // Create/Get Admin Role
    $adminRole = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrator', 'description' => 'Full system access']
    );
    
    // Create/Get Admin User
    $employee = Employee::where('email', 'test.api@deshio.com')->first();
    if (!$employee) {
        $employee = Employee::create([
            'name' => 'Test API Admin',
            'email' => 'test.api@deshio.com',
            'phone' => '01700000010',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'store_id' => $store->id,
            'is_active' => true,
        ]);
        echo "   ✅ Created admin user: {$employee->name}\n";
    } else {
        echo "   ✅ Using existing admin: {$employee->name}\n";
    }
    
    // Set the authenticated user (for console/testing context)
    // Note: In console/testing, we don't use JWT tokens like web requests do
    Auth::setUser($employee);
    echo "   🔐 User ready: {$employee->email}\n";
    
    // Create Category
    $category = Category::firstOrCreate(
        ['slug' => 'electronics-api-test'],
        ['title' => 'Electronics API Test', 'is_active' => true, 'level' => 0, 'path' => '/']
    );
    
    // Create Products
    $products = [];
    for ($i = 1; $i <= 3; $i++) {
        $product = Product::where('sku', "API-PROD-{$i}")->first();
        if (!$product) {
            $product = Product::create([
                'name' => "API Test Product {$i}",
                'sku' => "API-PROD-{$i}",
                'description' => "Test product for API integration",
                'category_id' => $category->id,
                'is_archived' => false,
            ]);
        }
        $products[] = $product;
        
        // Create batch if not exists
        $batch = ProductBatch::where('product_id', $product->id)
                             ->where('store_id', $store->id)
                             ->first();
        if (!$batch) {
            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'store_id' => $store->id,
                'batch_number' => 'BATCH-API-' . strtoupper(uniqid()),
                'quantity' => 100,
                'cost_price' => 500 + ($i * 100),
                'sell_price' => 800 + ($i * 150),
                'received_date' => now(),
            ]);
        }
    }
    echo "   ✅ Products ready: " . count($products) . " products\n";
    
    // Create Customer
    $customer = Customer::where('email', 'api.customer@test.com')->first();
    if (!$customer) {
        $customer = Customer::create([
            'name' => 'API Test Customer',
            'email' => 'api.customer@test.com',
            'phone' => '01700000099',
            'address' => '32, Sector 7, Uttara, Dhaka-1230',
            'city' => 'Dhaka',
            'zip_code' => '1230',
            'is_active' => true,
        ]);
        echo "   ✅ Created customer: {$customer->name}\n";
    } else {
        echo "   ✅ Using existing customer: {$customer->name}\n";
    }
    
    DB::commit();
    
    echo "\n✅ STEP 2 COMPLETE: Test data ready!\n\n";
    
    // ============================================================
    // STEP 3: Create Order with Items
    // ============================================================
    echo "🛒 STEP 3: Creating Order...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    DB::beginTransaction();
    
    $orderNumber = 'ORD-API-' . strtoupper(uniqid());
    
    // Calculate totals
    $subtotal = 0;
    $orderItemsData = [];
    foreach ($products as $index => $product) {
        $batch = ProductBatch::where('product_id', $product->id)
                             ->where('store_id', $store->id)
                             ->first();
        $quantity = 2;
        $price = $batch->sell_price;
        $itemTotal = $price * $quantity;
        $subtotal += $itemTotal;
        
        $orderItemsData[] = [
            'product' => $product,
            'batch' => $batch,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $itemTotal,
        ];
    }
    
    $tax = $subtotal * 0.05;
    $total = $subtotal + $tax;
    
    $order = Order::create([
        'order_number' => $orderNumber,
        'customer_id' => $customer->id,
        'store_id' => $store->id,
        'order_date' => now(),
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'subtotal' => $subtotal,
        'tax_amount' => $tax,
        'total_amount' => $total,
        'created_by' => $employee->id,
    ]);
    
    echo "   ✅ Created order: {$order->order_number}\n";
    echo "   💰 Total: {$total} BDT\n\n";
    
    // Add order items
    echo "   📦 Adding order items...\n";
    foreach ($orderItemsData as $itemData) {
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $itemData['product']->id,
            'product_batch_id' => $itemData['batch']->id,
            'product_name' => $itemData['product']->name,
            'product_sku' => $itemData['product']->sku,
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['price'],
            'total_amount' => $itemData['total'],
        ]);
        
        // Reduce batch quantity
        $itemData['batch']->decrement('quantity', $itemData['quantity']);
        
        echo "      • {$itemData['product']->name} x{$itemData['quantity']} = {$itemData['total']} BDT\n";
    }
    
    DB::commit();
    
    echo "\n✅ STEP 3 COMPLETE: Order created!\n\n";
    
    // ============================================================
    // STEP 4: Create Shipment Using ShipmentController
    // ============================================================
    echo "📮 STEP 4: Creating Shipment via Controller...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $shipmentController = new ShipmentController();
    
    // Create request object
    $createShipmentRequest = Request::create('/api/shipments', 'POST', [
        'order_id' => $order->id,
        'delivery_type' => 'home_delivery',
        'package_weight' => 2.5,
        'special_instructions' => 'Please call before delivery - API Test',
        'send_to_pathao' => false,  // We'll send separately to test that endpoint
    ]);
    
    echo "   📡 Calling ShipmentController->create()...\n";
    $createResponse = $shipmentController->create($createShipmentRequest);
    $createResponseData = json_decode($createResponse->getContent(), true);
    
    if (!$createResponseData['success']) {
        throw new Exception("Failed to create shipment: " . ($createResponseData['message'] ?? 'Unknown error'));
    }
    
    $shipment = \App\Models\Shipment::find($createResponseData['data']['id']);
    echo "   ✅ Shipment created: {$shipment->shipment_number}\n";
    echo "   📦 Status: {$shipment->status}\n";
    echo "   📦 COD Amount: {$shipment->cod_amount} BDT\n";
    
    // Update delivery address with Pathao location IDs
    $shipment->delivery_address = array_merge(
        is_array($shipment->delivery_address) ? $shipment->delivery_address : [],
        [
            'pathao_city_id' => $dhakaCity['city_id'],
            'pathao_zone_id' => $uttaraZone['zone_id'],
            'pathao_area_id' => $sector7Area['area_id'] ?? 1,
            'address_line_1' => '32, Sector 7',
            'city' => 'Dhaka',
            'zip_code' => '1230',
        ]
    );
    $shipment->save();
    echo "   ✅ Updated delivery address with Pathao location IDs\n";
    
    echo "\n✅ STEP 4 COMPLETE: Shipment created via Controller!\n\n";
    
    // ============================================================
    // STEP 5: Send to Pathao Using ShipmentController
    // ============================================================
    echo "🚀 STEP 5: Sending to Pathao via Controller...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    echo "   📡 Calling ShipmentController->sendToPathao()...\n";
    echo "   📋 Shipment ID: {$shipment->id}\n";
    echo "   📋 Store Pathao ID: {$store->pathao_store_id}\n";
    echo "   📋 Order Number: {$order->order_number}\n";
    echo "   📋 Recipient: {$customer->name} ({$customer->phone})\n\n";
    
    try {
        // Call the controller method directly
        $result = $shipmentController->sendToPathao($shipment->id);
        
        // If the method returns a shipment object (not HTTP response)
        if ($result instanceof \App\Models\Shipment) {
            $shipment = $result;
            
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║        ✅ PATHAO ORDER CREATED VIA CONTROLLER!            ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "   🎉 Consignment ID: " . ($shipment->pathao_consignment_id ?? 'N/A') . "\n";
            echo "   🎉 Tracking Number: " . ($shipment->pathao_tracking_number ?? 'N/A') . "\n";
            echo "   🎉 Delivery Fee: " . ($shipment->delivery_fee ?? 0) . " BDT\n";
            echo "   🎉 Status: {$shipment->status}\n";
            echo "   🎉 Pathao Status: {$shipment->pathao_status}\n";
            echo "\n   📋 Pathao Response:\n";
            if ($shipment->pathao_response) {
                echo "   " . json_encode($shipment->pathao_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
            echo "\n";
            
        } else {
            // If it returned an HTTP response
            $responseData = json_decode($result->getContent(), true);
            if (!$responseData['success']) {
                throw new Exception("Controller returned error: " . ($responseData['message'] ?? 'Unknown error'));
            }
            
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║        ✅ PATHAO ORDER CREATED VIA CONTROLLER!            ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "   Response: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
    } catch (\Exception $e) {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║           ❌ PATHAO SUBMISSION FAILED!                    ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "   ⚠️  Error: " . $e->getMessage() . "\n";
        echo "   📄 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        throw $e;
    }
    
    echo "\n✅ STEP 5 COMPLETE: Sent to Pathao via Controller!\n\n";
    
    // ============================================================
    // SUMMARY
    // ============================================================
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║                  TEST SUMMARY (API)                       ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "   ✅ Method: Using ShipmentController API methods\n";
    echo "   ✅ Admin User: {$employee->name} ({$employee->email})\n";
    echo "   ✅ Store: {$store->name} (Pathao ID: {$store->pathao_store_id})\n";
    echo "   ✅ Customer: {$customer->name}\n";
    echo "   ✅ Order: {$order->order_number} ({$total} BDT)\n";
    echo "   ✅ Shipment: {$shipment->shipment_number}\n";
    echo "   ✅ Pathao Consignment: " . ($shipment->pathao_consignment_id ?? 'N/A') . "\n";
    echo "   ✅ Status: {$shipment->status} / {$shipment->pathao_status}\n";
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "       🎉 ALL API TESTS PASSED! 🎉                           \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║                  ❌ TEST FAILED                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n   Stack Trace:\n";
    echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
    echo "\n";
    exit(1);
}
