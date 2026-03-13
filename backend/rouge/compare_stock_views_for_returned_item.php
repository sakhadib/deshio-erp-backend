<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductBarcode;
use App\Models\ProductBatch;

$barcode = ProductBarcode::where('current_status', 'in_return')->latest('id')->first();
if (!$barcode) {
    echo "No in_return barcode found\n";
    exit;
}

$productId = $barcode->product_id;
$storeId = $barcode->current_store_id;
$batchId = $barcode->batch_id;

echo "Product {$productId}, Store {$storeId}, Batch {$batchId}, Barcode {$barcode->barcode}\n\n";

$batchQty = ProductBatch::where('product_id', $productId)->where('store_id', $storeId)->sum('quantity');
echo "Batch stock qty (inventory table): {$batchQty}\n";

$sellableBarcodes = ProductBarcode::where('product_id', $productId)
    ->where('current_store_id', $storeId)
    ->where('is_active', true)
    ->where('is_defective', false)
    ->whereIn('current_status', ['in_shop', 'on_display', 'in_warehouse'])
    ->count();

echo "Sellable barcodes by status rule: {$sellableBarcodes}\n";

$statusBreakdown = ProductBarcode::where('product_id', $productId)
    ->where('current_store_id', $storeId)
    ->selectRaw('current_status, is_active, COUNT(*) as c')
    ->groupBy('current_status', 'is_active')
    ->get();

echo "Status breakdown at store:\n";
foreach ($statusBreakdown as $s) {
    echo "- {$s->current_status} | active=" . ($s->is_active ? '1' : '0') . " | {$s->c}\n";
}
