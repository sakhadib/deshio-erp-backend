<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "           PATHAO TOKEN DEBUG - FORMAT TEST                    \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$baseUrl = config('services.pathao.base_url');
$clientId = config('services.pathao.client_id');
$clientSecret = config('services.pathao.client_secret');
$username = config('services.pathao.username');
$password = config('services.pathao.password');

echo "Configuration:\n";
echo "  Base URL: {$baseUrl}\n";
echo "  Client ID: {$clientId}\n";
echo "  Username: {$username}\n";
echo "  Password: " . str_repeat('*', strlen($password)) . "\n";
echo "\n";

// Test 1: Form-encoded (current implementation)
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 1: Form-encoded POST (application/x-www-form-urlencoded)\n";
echo "═══════════════════════════════════════════════════════════════\n";

try {
    $response = Http::timeout(30)
        ->acceptJson()
        ->asForm()
        ->post("{$baseUrl}/aladdin/api/v1/issue-token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Body: " . $response->body() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ SUCCESS - Token: " . substr($data['access_token'] ?? 'N/A', 0, 50) . "...\n";
    } else {
        echo "❌ FAILED\n";
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: JSON POST (original implementation)
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 2: JSON POST (application/json)\n";
echo "═══════════════════════════════════════════════════════════════\n";

try {
    $response = Http::timeout(30)
        ->acceptJson()
        ->post("{$baseUrl}/aladdin/api/v1/issue-token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Body: " . $response->body() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ SUCCESS - Token: " . substr($data['access_token'] ?? 'N/A', 0, 50) . "...\n";
    } else {
        echo "❌ FAILED\n";
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 3: JSON POST with Content-Type header explicitly set
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 3: JSON POST with explicit Content-Type\n";
echo "═══════════════════════════════════════════════════════════════\n";

try {
    $response = Http::timeout(30)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->post("{$baseUrl}/aladdin/api/v1/issue-token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Body: " . $response->body() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ SUCCESS - Token: " . substr($data['access_token'] ?? 'N/A', 0, 50) . "...\n";
    } else {
        echo "❌ FAILED\n";
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n";
