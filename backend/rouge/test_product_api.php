<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Employee;

// Authenticate
$employee = Employee::first();
if (!$employee) {
    echo "ERROR: No employees found\n";
    exit(1);
}
auth()->guard('api')->setUser($employee);

echo "Testing Product API Response for ID: 4284\n";
echo str_repeat("=", 80) . "\n\n";

try {
    $product = Product::with([
        'category',
        'vendor',
        'productFields.field',
        'images',
        'barcodes',
        'batches.store',
        'priceOverrides'
    ])->find(4284);

    if (!$product) {
        echo "ERROR: Product 4284 not found\n";
        exit(1);
    }

    // Build the response exactly as the controller would
    $response = [
        'success' => true,
        'data' => $product->toArray()
    ];

    // Add computed attributes
    $response['data']['inventory_summary'] = [
        'total_quantity' => $product->getTotalInventory(),
        'available_batches' => $product->availableBatches()->count(),
        'lowest_price' => $product->getLowestBatchPrice(),
        'highest_price' => $product->getHighestBatchPrice(),
        'average_price' => $product->getAverageBatchPrice(),
    ];

    echo "Response Structure:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
