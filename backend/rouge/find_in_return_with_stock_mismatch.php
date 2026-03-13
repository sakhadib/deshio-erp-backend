<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductBarcode;
use App\Models\ProductBatch;

echo "=== in_return batch mismatch candidates ===\n\n";

$rows = ProductBarcode::where('current_status', 'in_return')->get();
foreach ($rows as $b) {
    $batch = ProductBatch::find($b->batch_id);
    $batchQty = $batch?->quantity ?? null;

    $sellableInBatch = ProductBarcode::where('batch_id', $b->batch_id)
        ->where('is_active', true)
        ->where('is_defective', false)
        ->whereIn('current_status', ['in_shop', 'on_display', 'in_warehouse'])
        ->count();

    echo "barcode={$b->barcode} product={$b->product_id} batch={$b->batch_id} store={$b->current_store_id} batch_qty=" . ($batchQty ?? 'NULL') . " sellable_in_batch={$sellableInBatch}\n";
}
