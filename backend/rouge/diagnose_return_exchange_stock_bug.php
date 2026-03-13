<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductReturn;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;

echo "================ RETURN/EXCHANGE STOCK DIAGNOSTIC ================\n\n";

// 1) High-level status counts for barcode states that matter.
echo "1) Barcode state counts\n";
$stateCounts = ProductBarcode::select('current_status', 'is_active', DB::raw('COUNT(*) AS c'))
    ->groupBy('current_status', 'is_active')
    ->orderBy('current_status')
    ->get();

foreach ($stateCounts as $row) {
    echo sprintf("- status=%-14s is_active=%s count=%s\n", $row->current_status ?? 'NULL', $row->is_active ? '1' : '0', $row->c);
}

echo "\n";

// 2) Recent processed/completed/refunded returns.
echo "2) Recent returns (latest 10 with inventory-relevant statuses)\n";
$returns = ProductReturn::whereIn('status', ['processing', 'completed', 'refunded'])
    ->orderByDesc('id')
    ->take(10)
    ->get(['id', 'return_number', 'status', 'store_id', 'received_at_store_id', 'return_type', 'return_reason', 'return_items', 'processed_date']);

if ($returns->isEmpty()) {
    echo "- No processed/completed/refunded returns found.\n";
    exit(0);
}

foreach ($returns as $ret) {
    echo "\nReturn #{$ret->return_number} (id={$ret->id}) status={$ret->status} type={$ret->return_type} store={$ret->store_id} recv_store=" . ($ret->received_at_store_id ?? 'NULL') . "\n";

    $items = is_array($ret->return_items) ? $ret->return_items : [];
    if (empty($items)) {
        echo "  - No return_items\n";
        continue;
    }

    foreach ($items as $idx => $item) {
        $productId = $item['product_id'] ?? null;
        $batchId = $item['product_batch_id'] ?? null;
        $qty = (int)($item['quantity'] ?? 0);

        echo "  Item " . ($idx + 1) . ": product_id={$productId}, batch_id={$batchId}, qty={$qty}\n";

        if (!$productId || !$batchId || $qty <= 0) {
            echo "    -> skip (incomplete item metadata)\n";
            continue;
        }

        $batch = ProductBatch::find($batchId);
        if ($batch) {
            echo "    Batch qty now={$batch->quantity}, store_id={$batch->store_id}\n";
        } else {
            echo "    Batch missing\n";
        }

        $barcodeStats = ProductBarcode::where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->select('current_status', 'is_active', DB::raw('COUNT(*) as c'))
            ->groupBy('current_status', 'is_active')
            ->get();

        foreach ($barcodeStats as $s) {
            echo "    barcode state: status={$s->current_status}, is_active=" . ($s->is_active ? '1' : '0') . ", count={$s->c}\n";
        }

        $suspect = ProductBarcode::where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->where('current_status', 'in_return')
            ->where('is_active', false)
            ->count();

        if ($suspect > 0) {
            echo "    !! suspect: {$suspect} barcode(s) in_return but inactive (not sellable)\n";
        }
    }
}

// 3) Direct inconsistency check: batch quantity > 0 but all barcodes inactive or in non-sellable state.
echo "\n3) Batch-vs-barcode consistency spot check (latest 30 batches with qty>0)\n";
$batches = ProductBatch::where('quantity', '>', 0)->orderByDesc('id')->take(30)->get(['id', 'product_id', 'store_id', 'quantity']);

$inconsistent = 0;
foreach ($batches as $b) {
    $sellable = ProductBarcode::where('batch_id', $b->id)
        ->where('is_active', true)
        ->whereIn('current_status', ['in_warehouse', 'in_shop', 'on_display'])
        ->count();

    if ($sellable === 0) {
        $inconsistent++;
        echo "- batch_id={$b->id} product_id={$b->product_id} qty={$b->quantity} sellable_barcodes=0\n";
    }
}

echo "\nInconsistent sampled batches: {$inconsistent}/" . $batches->count() . "\n";

// 4) Return type values currently used in data.
echo "\n4) return_type values in DB\n";
$types = ProductReturn::select('return_type', DB::raw('COUNT(*) as c'))->groupBy('return_type')->get();
foreach ($types as $t) {
    echo "- return_type={$t->return_type} count={$t->c}\n";
}

echo "\n================ END DIAGNOSTIC ================\n";
