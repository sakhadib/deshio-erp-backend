<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductBarcode;
use App\Models\ProductReturn;

echo "=== IN_RETURN BARCODES INSPECTION ===\n\n";

$rows = ProductBarcode::with(['product', 'batch', 'currentStore'])
    ->where('current_status', 'in_return')
    ->orderByDesc('id')
    ->get();

echo "Total in_return barcodes: " . $rows->count() . "\n\n";

foreach ($rows as $b) {
    $meta = $b->location_metadata ?? [];
    $returnId = $meta['return_id'] ?? null;

    echo "Barcode ID {$b->id} | code={$b->barcode} | product_id={$b->product_id} ({$b->product->name})\n";
    echo "  status={$b->current_status} | is_active=" . ($b->is_active ? '1' : '0') . " | is_defective=" . ($b->is_defective ? '1' : '0') . "\n";
    echo "  store_id={$b->current_store_id} | batch_id={$b->batch_id} | batch_qty=" . ($b->batch->quantity ?? 'N/A') . "\n";
    echo "  return_id_from_metadata=" . ($returnId ?? 'NULL') . "\n";

    if ($returnId) {
        $ret = ProductReturn::find($returnId);
        if ($ret) {
            echo "  return_status={$ret->status} | return_number={$ret->return_number} | return_type={$ret->return_type}\n";
        } else {
            echo "  return_status=NOT_FOUND\n";
        }
    }

    $availableByRules = $b->isAvailableForSale() ? 'YES' : 'NO';
    echo "  available_for_sale_rule=" . $availableByRules . "\n\n";
}

echo "=== END ===\n";
