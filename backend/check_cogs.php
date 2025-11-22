<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking COGS Data ===\n\n";

// Check order_items
echo "Order Items:\n";
$items = DB::table('order_items')
    ->select('id', 'product_name', 'cogs', 'quantity', 'unit_price', 'product_batch_id')
    ->limit(5)
    ->get();

foreach($items as $item) {
    echo "ID: {$item->id} | Product: {$item->product_name} | COGS: " . ($item->cogs ?? 'NULL') . " | Qty: {$item->quantity} | Unit Price: {$item->unit_price}\n";
}

echo "\n\nProduct Batches:\n";
$batches = DB::table('product_batches')
    ->select('id', 'batch_number', 'cost_price', 'sell_price', 'quantity')
    ->limit(5)
    ->get();

foreach($batches as $batch) {
    echo "ID: {$batch->id} | Batch: {$batch->batch_number} | Cost: " . ($batch->cost_price ?? 'NULL') . " | Sell: {$batch->sell_price} | Qty: {$batch->quantity}\n";
}

echo "\n\nOrder Items with Batch Cost:\n";
$itemsWithBatch = DB::table('order_items as oi')
    ->join('product_batches as pb', 'oi.product_batch_id', '=', 'pb.id')
    ->select('oi.id', 'oi.product_name', 'oi.cogs', 'oi.quantity', 'pb.cost_price', 'pb.batch_number')
    ->limit(5)
    ->get();

foreach($itemsWithBatch as $item) {
    echo "OrderItem ID: {$item->id} | Product: {$item->product_name} | COGS: " . ($item->cogs ?? 'NULL') . 
         " | Batch Cost: " . ($item->cost_price ?? 'NULL') . " | Qty: {$item->quantity} | Batch: {$item->batch_number}\n";
}
