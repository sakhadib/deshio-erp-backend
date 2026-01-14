<?php

/**
 * Pathao Integration Test Script
 * 
 * This script tests the complete flow:
 * 1. Create admin user
 * 2. Create store with Pathao config
 * 3. Create products
 * 4. Create inventory batches
 * 5. Create order for Uttara, Dhaka customer
 * 6. Create shipment and send to Pathao
 * 
 * Run: php test_pathao_order.php
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Store;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Services\PathaoService;
use Codeboxr\PathaoCourier\Facade\PathaoCourier;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "        PATHAO INTEGRATION TEST - COMPLETE FLOW               \n";
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
    echo "   Username: " . config('services.pathao.username') . "\n";
    echo "   Store ID: 329652\n\n";
    
    // Get access token
    echo "   🔐 Obtaining access token...\n";
    $token = $pathaoService->getAccessToken();
    echo "   ✅ Access token obtained: " . substr($token, 0, 50) . "...\n\n";
    
    // Test cities API
    echo "   📍 Fetching cities from Pathao...\n";
    $citiesResult = $pathaoService->getCities();
    if ($citiesResult['success']) {
        echo "   ✅ Successfully fetched " . count($citiesResult['cities']) . " cities\n";
        // Find Dhaka
        $dhakaCity = collect($citiesResult['cities'])->firstWhere('city_name', 'Dhaka');
        if ($dhakaCity) {
            echo "   ✅ Found Dhaka - City ID: " . $dhakaCity['city_id'] . "\n\n";
            
            // Get zones for Dhaka
            echo "   📍 Fetching zones for Dhaka...\n";
            $zonesResult = $pathaoService->getZones($dhakaCity['city_id']);
            if ($zonesResult['success']) {
                echo "   ✅ Successfully fetched " . count($zonesResult['zones']) . " zones\n";
                // Find Uttara
                $uttaraZone = collect($zonesResult['zones'])->first(function($zone) {
                    return stripos($zone['zone_name'], 'Uttara') !== false;
                });
                if ($uttaraZone) {
                    echo "   ✅ Found Uttara Zone - Zone ID: " . $uttaraZone['zone_id'] . "\n\n";
                    
                    // Get areas for Uttara
                    echo "   📍 Fetching areas for Uttara...\n";
                    $areasResult = $pathaoService->getAreas($uttaraZone['zone_id']);
                    if ($areasResult['success']) {
                        echo "   ✅ Successfully fetched " . count($areasResult['areas']) . " areas\n";
                        $sector7Area = collect($areasResult['areas'])->first(function($area) {
                            return stripos($area['area_name'], 'Sector') !== false;
                        });
                        if ($sector7Area) {
                            echo "   ✅ Found Sector area - Area ID: " . $sector7Area['area_id'] . "\n";
                        }
                    }
                }
            }
        }
    } else {
        throw new Exception("Failed to fetch cities: " . $citiesResult['error']);
    }
    
    echo "\n✅ STEP 1 COMPLETE: Pathao connection working!\n\n";
    
    // ============================================================
    // STEP 2: Create Store with Pathao Configuration (BEFORE Employee)
    // ============================================================
    echo "🏪 STEP 2: Creating Store with Pathao Configuration...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    DB::beginTransaction();
    
    $store = Store::where('name', 'Test Store - Pathao')->first();
    if (!$store) {
        $store = Store::create([
            'name' => 'Test Store - Pathao',
            'code' => 'TST-PTH-001',
            'address' => 'House 10, Road 5, Dhanmondi, Dhaka-1205',
            'phone' => '01700000002',
            'email' => 'store@deshio.com',
            'is_active' => true,
            'pathao_store_id' => 329652,  // Provided Pathao store ID
            'pathao_contact_name' => 'Store Manager',
            'pathao_contact_number' => '01700000002',
            'pathao_city_id' => $dhakaCity['city_id'] ?? 1,
            'pathao_zone_id' => $uttaraZone['zone_id'] ?? 1,
            'pathao_area_id' => $sector7Area['area_id'] ?? 1,
        ]);
        echo "   ✅ Created store: {$store->name}\n";
    } else {
        $store->update([
            'pathao_store_id' => 329652,
            'pathao_contact_name' => 'Store Manager',
            'pathao_contact_number' => '01700000002',
        ]);
        echo "   ✅ Updated existing store: {$store->name}\n";
    }
    
    echo "   📦 Pathao Store ID: {$store->pathao_store_id}\n";
    echo "   📍 Pickup City: " . ($dhakaCity['city_name'] ?? 'N/A') . "\n";
    
    echo "\n✅ STEP 2 COMPLETE: Store configured with Pathao!\n\n";
    
    // ============================================================
    // STEP 3: Create Admin User (AFTER Store)
    // ============================================================
    echo "👤 STEP 3: Creating Admin User...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    // Check if admin role exists
    $adminRole = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrator', 'description' => 'Full system access']
    );
    echo "   ✅ Admin role ready (ID: {$adminRole->id})\n";
    
    // Create or get test employee
    $employee = Employee::where('email', 'test.admin@deshio.com')->first();
    if (!$employee) {
        $employee = Employee::create([
            'name' => 'Test Admin',
            'email' => 'test.admin@deshio.com',
            'phone' => '01700000001',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'store_id' => $store->id,  // Add store_id
            'is_active' => true,
        ]);
        echo "   ✅ Created new admin user: {$employee->name} ({$employee->email})\n";
    } else {
        echo "   ✅ Using existing admin user: {$employee->name} ({$employee->email})\n";
    }
    
    echo "\n✅ STEP 3 COMPLETE: Admin user ready!\n\n";
    
    // ============================================================
    // STEP 4: Create Products
    // ============================================================
    echo "📦 STEP 4: Creating Products...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    // Create category
    $category = Category::firstOrCreate(
        ['slug' => 'electronics-test'],
        ['title' => 'Electronics Test', 'is_active' => true, 'level' => 0, 'path' => '/']
    );
    echo "   ✅ Category ready: {$category->title}\n";
    
    // Create products
    $products = [];
    for ($i = 1; $i <= 3; $i++) {
        $product = Product::where('sku', "TEST-PROD-{$i}")->first();
        if (!$product) {
            $product = Product::create([
                'name' => "Test Product {$i}",
                'sku' => "TEST-PROD-{$i}",
                'description' => "Test product for Pathao integration",
                'category_id' => $category->id,
                'is_archived' => false,
            ]);
            echo "   ✅ Created product: {$product->name} (SKU: {$product->sku})\n";
        } else {
            echo "   ✅ Using existing product: {$product->name} (SKU: {$product->sku})\n";
        }
        $products[] = $product;
    }
    
    echo "\n✅ STEP 4 COMPLETE: " . count($products) . " products ready!\n\n";
    
    // ============================================================
    // STEP 5: Create Inventory Batches
    // ============================================================
    echo "📊 STEP 5: Creating Inventory Batches...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $batches = [];
    foreach ($products as $index => $product) {
        $batchNumber = 'BATCH-' . strtoupper(uniqid());
        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'batch_number' => $batchNumber,
            'quantity' => 50,
            'cost_price' => 500 + ($index * 100),
            'sell_price' => 800 + ($index * 150),
            'status' => 'active',
            'received_date' => now(),
        ]);
        $batches[$product->id] = $batch;
        echo "   ✅ Created batch for {$product->name}: {$batchNumber} (Qty: 50, Price: {$batch->sell_price} BDT)\n";
    }
    
    echo "\n✅ STEP 5 COMPLETE: Inventory batches created!\n\n";
    
    // ============================================================
    // STEP 6: Create Customer in Uttara, Dhaka
    // ============================================================
    echo "👥 STEP 6: Creating Customer in Uttara, Dhaka...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $customer = Customer::where('email', 'customer.uttara@test.com')->first();
    if (!$customer) {
        $customer = Customer::create([
            'name' => 'Uttara Customer',
            'email' => 'customer.uttara@test.com',
            'phone' => '01700000003',
            'address' => '32, Sector 7, Uttara, Dhaka-1230',
            'city' => 'Dhaka',
            'zip_code' => '1230',
            'is_active' => true,
        ]);
        echo "   ✅ Created customer: {$customer->name}\n";
    } else {
        echo "   ✅ Using existing customer: {$customer->name}\n";
    }
    
    echo "   📍 Address: {$customer->address}\n";
    echo "   📞 Phone: {$customer->phone}\n";
    
    echo "\n✅ STEP 6 COMPLETE: Customer ready!\n\n";
    
    // ============================================================
    // STEP 7: Create Order
    // ============================================================
    echo "🛒 STEP 7: Creating Order...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $orderNumber = 'ORD-' . strtoupper(uniqid());
    
    // Calculate order totals
    $subtotal = 0;
    $orderItemsData = [];
    foreach ($products as $index => $product) {
        $batch = $batches[$product->id];
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
    
    $tax = $subtotal * 0.05; // 5% tax
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
    echo "   💰 Subtotal: {$subtotal} BDT\n";
    echo "   💰 Tax: {$tax} BDT\n";
    echo "   💰 Total: {$total} BDT\n\n";
    
    // Create order items
    echo "   📦 Adding order items...\n";
    foreach ($orderItemsData as $itemData) {
        $orderItem = OrderItem::create([
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
    
    echo "\n✅ STEP 7 COMPLETE: Order created with " . count($orderItemsData) . " items!\n\n";
    
    DB::commit();
    
    // ============================================================
    // STEP 8: Create Shipment and Send to Pathao
    // ============================================================
    echo "📮 STEP 8: Creating Shipment and Sending to Pathao...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    DB::beginTransaction();
    
    try {
        // Prepare shipment data
        $shipmentData = [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'store_id' => $store->id,
            'shipment_number' => 'SHIP-' . strtoupper(uniqid()),
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'delivery_type' => 'home_delivery',
            'status' => 'pending',
            'package_weight' => 2.5,
            'cod_amount' => $total,
            'special_instructions' => 'Please call before delivery',
            'pickup_address' => [
                'store_name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'city_id' => $store->pathao_city_id,
                'zone_id' => $store->pathao_zone_id,
                'area_id' => $store->pathao_area_id,
            ],
            'delivery_address' => [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'address_line_1' => '32, Sector 7',
                'street' => '32, Sector 7, Uttara, Dhaka-1230',
                'city' => 'Dhaka',
                'zip_code' => '1230',
                'pathao_city_id' => $dhakaCity['city_id'] ?? 1,
                'pathao_zone_id' => $uttaraZone['zone_id'] ?? 1,
                'pathao_area_id' => $sector7Area['area_id'] ?? 1,
            ],
            'created_by' => $employee->id,
        ];
        
        $shipment = Shipment::create($shipmentData);
        echo "   ✅ Shipment created: {$shipment->shipment_number}\n";
        echo "   📦 Package weight: {$shipment->package_weight} kg\n";
        echo "   💰 COD amount: {$shipment->cod_amount} BDT\n\n";
        
        // Prepare Pathao order data
        echo "   📡 Preparing Pathao order data...\n";
        $pathaoOrderData = [
            'store_id' => 329652,
            'merchant_order_id' => $order->order_number,
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'recipient_address' => '32, Sector 7, Uttara, Dhaka-1230',
            'recipient_city' => $dhakaCity['city_id'] ?? 1,
            'recipient_zone' => $uttaraZone['zone_id'] ?? 1,
            'recipient_area' => $sector7Area['area_id'] ?? 1,
            'delivery_type' => 48,  // 48=normal delivery, 12=express
            'item_type' => 2,  // 1=document, 2=parcel
            'special_instruction' => 'Please call before delivery',
            'item_quantity' => $order->items->count(),
            'item_weight' => 2.5,
            'amount_to_collect' => $total,
            'item_description' => 'Electronics items - ' . $order->items->count() . ' products',
        ];
        
        echo "   📋 Pathao Order Data:\n";
        echo "      Store ID: {$pathaoOrderData['store_id']}\n";
        echo "      Merchant Order ID: {$pathaoOrderData['merchant_order_id']}\n";
        echo "      Recipient: {$pathaoOrderData['recipient_name']} ({$pathaoOrderData['recipient_phone']})\n";
        echo "      Address: {$pathaoOrderData['recipient_address']}\n";
        echo "      City ID: {$pathaoOrderData['recipient_city']}\n";
        echo "      Zone ID: {$pathaoOrderData['recipient_zone']}\n";
        echo "      Area ID: {$pathaoOrderData['recipient_area']}\n";
        echo "      Weight: {$pathaoOrderData['item_weight']} kg\n";
        echo "      COD: {$pathaoOrderData['amount_to_collect']} BDT\n\n";
        
        // Send to Pathao
        echo "   🚀 Sending order to Pathao...\n";
        $pathaoResponse = PathaoCourier::order()->create($pathaoOrderData);
        
        // Convert response to array if it's an object
        $responseArray = json_decode(json_encode($pathaoResponse), true);
        
        // Check if response has either 'data' field or direct response fields
        if ($responseArray && (isset($responseArray['data']) || isset($responseArray['consignment_id']))) {
            // Use data from 'data' field if exists, otherwise use direct response
            $pathaoData = isset($responseArray['data']) ? $responseArray['data'] : $responseArray;
            
            // Update shipment with Pathao details
            $shipment->update([
                'pathao_consignment_id' => $pathaoData['consignment_id'] ?? null,
                'pathao_tracking_number' => $pathaoData['invoice_id'] ?? $pathaoData['consignment_id'] ?? null,
                'pathao_status' => 'pickup_requested',
                'pathao_response' => $responseArray,
                'status' => 'pickup_requested',
                'pickup_requested_at' => now(),
                'delivery_fee' => $pathaoData['delivery_fee'] ?? 0,
            ]);
            
            DB::commit();
            
            echo "\n";
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║           ✅ PATHAO ORDER CREATED SUCCESSFULLY!           ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "   🎉 Consignment ID: " . ($pathaoData['consignment_id'] ?? 'N/A') . "\n";
            echo "   🎉 Invoice ID: " . ($pathaoData['invoice_id'] ?? 'N/A') . "\n";
            echo "   🎉 Delivery Fee: " . ($pathaoData['delivery_fee'] ?? 0) . " BDT\n";
            echo "   🎉 Status: " . ($pathaoData['order_status'] ?? 'Pending Pickup') . "\n";
            
            if (isset($pathaoData['merchant_order_id'])) {
                echo "   🎉 Merchant Order: " . $pathaoData['merchant_order_id'] . "\n";
            }
            
            echo "\n   📋 Full Pathao Response:\n";
            echo "   " . str_repeat("─", 55) . "\n";
            echo "   " . json_encode($responseArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            echo "\n";
            
        } else {
            throw new Exception("Invalid response from Pathao API: " . json_encode($responseArray));
        }
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║              ❌ PATHAO ORDER FAILED!                      ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "   ⚠️  Error: " . $e->getMessage() . "\n";
        echo "   📄 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "\n   Stack Trace:\n";
        echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
        throw $e;
    }
    
    echo "\n✅ STEP 8 COMPLETE: Shipment sent to Pathao successfully!\n\n";
    
    // ============================================================
    // SUMMARY
    // ============================================================
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║                    TEST SUMMARY                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "   ✅ Admin User: {$employee->name} ({$employee->email})\n";
    echo "   ✅ Store: {$store->name} (Pathao ID: {$store->pathao_store_id})\n";
    echo "   ✅ Products: " . count($products) . " products created\n";
    echo "   ✅ Inventory: " . count($batches) . " batches created\n";
    echo "   ✅ Customer: {$customer->name} ({$customer->address})\n";
    echo "   ✅ Order: {$order->order_number} ({$total} BDT)\n";
    echo "   ✅ Shipment: {$shipment->shipment_number}\n";
    echo "   ✅ Pathao: " . ($shipment->pathao_consignment_id ?? 'N/A') . "\n";
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "              🎉 ALL TESTS PASSED! 🎉                         \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║                    ❌ TEST FAILED                         ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n   Stack Trace:\n";
    echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
    echo "\n";
    exit(1);
}
