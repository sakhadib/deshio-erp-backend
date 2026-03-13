<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductReturn;
use App\Models\ProductBatch;
use App\Models\ProductBarcode;
use Illuminate\Support\Facades\DB;

echo "=== CROSS-STORE RETURN FLOW CHECK ===\n\n";

$total = ProductReturn::count();
$cross = ProductReturn::whereNotNull('received_at_store_id')
    ->whereColumn('received_at_store_id', '!=', 'store_id')
    ->count();

echo "Total returns: {$total}\n";
echo "Cross-store returns (received_at_store_id != store_id): {$cross}\n\n";

$crossReturns = ProductReturn::whereNotNull('received_at_store_id')
    ->whereColumn('received_at_store_id', '!=', 'store_id')
    ->orderByDesc('id')
    ->take(10)
    ->get(['id', 'return_number', 'status', 'store_id', 'received_at_store_id', 'return_type', 'quality_check_passed', 'return_items']);

if ($crossReturns->isEmpty()) {
    echo "No cross-store return records found in DB.\n";
    echo "Cannot prove behavior from historical data, only from code path.\n";
    exit(0);
}

foreach ($crossReturns as $r) {
    echo "Return {$r->return_number} (id={$r->id}) status={$r->status} storeA={$r->store_id} storeB={$r->received_at_store_id} quality=" . var_export($r->quality_check_passed, true) . "\n";

    $items = is_array($r->return_items) ? $r->return_items : [];
    foreach ($items as $idx => $item) {
        $productId = $item['product_id'] ?? null;
        $origBatchId = $item['product_batch_id'] ?? null;
        $qty = (int)($item['quantity'] ?? 0);

        if (!$productId || !$origBatchId || $qty <= 0) {
            continue;
        }

        $origBatch = ProductBatch::find($origBatchId);

        // Expected target batch in store B uses same batch number by controller logic.
        $targetBatch = null;
        if ($origBatch) {
            $targetBatch = ProductBatch::where('product_id', $productId)
                ->where('store_id', $r->received_at_store_id)
                ->where('batch_number', $origBatch->batch_number)
                ->first();
        }

        $returnedBarcodeCount = ProductBarcode::where('product_id', $productId)
            ->where('current_store_id', $r->received_at_store_id)
            ->whereJsonContains('location_metadata->return_id', $r->id)
            ->count();

        echo "  Item " . ($idx + 1) . ": product={$productId}, orig_batch={$origBatchId}, qty={$qty}";
        echo ", orig_store=" . ($origBatch?->store_id ?? 'NULL');
        echo ", target_batch_in_storeB=" . ($targetBatch?->id ?? 'NULL');
        echo ", returned_barcodes_at_storeB={$returnedBarcodeCount}\n";

        if ($returnedBarcodeCount > 0) {
            $statusBreakdown = ProductBarcode::where('product_id', $productId)
                ->where('current_store_id', $r->received_at_store_id)
                ->whereJsonContains('location_metadata->return_id', $r->id)
                ->select('current_status', DB::raw('count(*) as c'))
                ->groupBy('current_status')
                ->get();

            foreach ($statusBreakdown as $s) {
                echo "    barcode_status={$s->current_status} count={$s->c}\n";
            }
        }
    }

    echo "\n";
}

echo "=== END CHECK ===\n";
