<?php

/**
 * Debug Pathao Store Access
 * Check what stores are actually accessible with current credentials
 */

require __DIR__.'/vendor/autoload.php';

use App\Services\PathaoService;
use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "           PATHAO STORE ACCESS DEBUG                          \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    $pathaoService = new PathaoService();
    
    echo "📋 Current Credentials:\n";
    echo "   Base URL: " . config('services.pathao.base_url') . "\n";
    echo "   Client ID: " . config('services.pathao.client_id') . "\n";
    echo "   Username: " . config('services.pathao.username') . "\n";
    echo "   Configured Store ID: " . config('services.pathao.store_id') . "\n\n";
    
    echo "🔐 Getting access token...\n";
    $token = $pathaoService->getAccessToken();
    echo "   ✅ Token obtained: " . substr($token, 0, 50) . "...\n\n";
    
    // Try to get merchant info
    echo "🏪 Fetching merchant/store information...\n";
    $baseUrl = config('services.pathao.base_url');
    
    // Method 1: Get store info
    echo "\n   Method 1: GET /merchant/info\n";
    $response1 = Http::withToken($token)
        ->get("{$baseUrl}/aladdin/api/v1/merchant/info");
    
    if ($response1->successful()) {
        echo "   ✅ Response:\n";
        print_r($response1->json());
    } else {
        echo "   ❌ Failed: " . $response1->status() . "\n";
        echo "   " . $response1->body() . "\n";
    }
    
    // Method 2: Get stores list
    echo "\n   Method 2: GET /stores\n";
    $response2 = Http::withToken($token)
        ->get("{$baseUrl}/aladdin/api/v1/stores");
    
    if ($response2->successful()) {
        echo "   ✅ Response:\n";
        print_r($response2->json());
    } else {
        echo "   ❌ Failed: " . $response2->status() . "\n";
        echo "   " . $response2->body() . "\n";
    }
    
    // Method 3: Try to create order with different store IDs and see what happens
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "🧪 TESTING STORE ID VALIDATION\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $testStoreIds = [329652, 261222, 256784, 999999];
    
    // Get a valid city/zone/area for test
    $citiesResult = $pathaoService->getCities();
    $dhaka = collect($citiesResult['cities'])->firstWhere('city_name', 'Dhaka');
    $zonesResult = $pathaoService->getZones($dhaka['city_id']);
    $zone = $zonesResult['zones'][0];
    $areasResult = $pathaoService->getAreas($zone['zone_id']);
    $area = $areasResult['areas'][0];
    
    foreach ($testStoreIds as $storeId) {
        echo "Testing Store ID: {$storeId}\n";
        
        $testData = [
            'store_id' => $storeId,
            'merchant_order_id' => 'TEST-' . time() . '-' . $storeId,
            'recipient_name' => 'Test Customer',
            'recipient_phone' => '01700000000',
            'recipient_address' => 'Test Address',
            'recipient_city' => $dhaka['city_id'],
            'recipient_zone' => $zone['zone_id'],
            'recipient_area' => $area['area_id'],
            'delivery_type' => 48,
            'item_type' => 2,
            'special_instruction' => 'Test',
            'item_quantity' => 1,
            'item_weight' => 0.5,
            'amount_to_collect' => 100,
            'item_description' => 'Test',
        ];
        
        $response = Http::withToken($token)
            ->post("{$baseUrl}/aladdin/api/v1/orders", $testData);
        
        if ($response->successful()) {
            echo "   ✅ SUCCESS! Order created\n";
            print_r($response->json());
        } else {
            echo "   ❌ FAILED: " . $response->status() . "\n";
            $error = $response->json();
            if (isset($error['errors']['store_id'])) {
                echo "   Error: " . json_encode($error['errors']['store_id']) . "\n";
            } else {
                echo "   " . json_encode($error) . "\n";
            }
        }
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";
