<?php

/**
 * Get Pathao Store Information
 * This script fetches the available stores for the configured Pathao account
 */

require __DIR__.'/vendor/autoload.php';

use App\Services\PathaoService;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "           PATHAO STORE INFORMATION                           \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    $pathaoService = new PathaoService();
    
    echo "📡 Pathao Configuration:\n";
    echo "   Base URL: " . config('services.pathao.base_url') . "\n";
    echo "   Client ID: " . config('services.pathao.client_id') . "\n";
    echo "   Username: " . config('services.pathao.username') . "\n\n";
    
    echo "🔐 Getting access token...\n";
    $token = $pathaoService->getAccessToken();
    echo "   ✅ Token obtained: " . substr($token, 0, 50) . "...\n\n";
    
    echo "🏪 Fetching store information...\n";
    $result = $pathaoService->getStoreInfo();
    
    if ($result['success']) {
        echo "   ✅ Store information retrieved successfully!\n\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "Store Details:\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        print_r($result['data']);
        echo "\n";
    } else {
        echo "   ❌ Failed to get store information\n";
        echo "   Error: " . $result['error'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";
