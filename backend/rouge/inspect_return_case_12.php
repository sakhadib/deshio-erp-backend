<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductReturn;
use App\Models\ProductMovement;
use App\Models\ProductBarcode;

$return = ProductReturn::find(12);
if (!$return) {
    echo "Return 12 not found\n";
    exit;
}

echo "Return {$return->id} {$return->return_number}\n";
echo "status={$return->status} reason={$return->return_reason} type={$return->return_type}\n";
echo "store={$return->store_id} received_at_store={$return->received_at_store_id}\n";
echo "quality_check_passed=" . var_export($return->quality_check_passed, true) . "\n";
echo "return_items=" . json_encode($return->return_items) . "\n\n";

$movements = ProductMovement::where('reference_type', 'return')->where('reference_id', 12)->orderBy('id')->get();
echo "Return movements count=" . $movements->count() . "\n";
foreach ($movements as $m) {
    echo "- movement id={$m->id} batch={$m->product_batch_id} barcode={$m->product_barcode_id} qty={$m->quantity} type={$m->movement_type} notes={$m->notes}\n";
}

echo "\nBarcodes with metadata return_id=12\n";
$barcodes = ProductBarcode::whereJsonContains('location_metadata->return_id', 12)->get();
foreach ($barcodes as $b) {
    echo "- barcode={$b->barcode} product={$b->product_id} batch={$b->batch_id} status={$b->current_status} active=" . ($b->is_active ? '1' : '0') . " defective=" . ($b->is_defective ? '1' : '0') . "\n";
}
