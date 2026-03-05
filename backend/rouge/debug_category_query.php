<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;

// Check order items for Saree category (ID 1)
echo "=== Checking Order Items for Saree Category (Raw Data) ===\n\n";

$items = DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->where('categories.id', 1)
    ->whereNull('orders.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereNull('categories.deleted_at')
    ->whereIn('orders.status', ['confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered'])
    ->select(
        'order_items.id',
        'order_items.quantity',
        'order_items.unit_price',
        'order_items.discount_amount',
        'orders.order_number',
        'orders.status',
        'products.name',
        'categories.title',
        DB::raw('order_items.quantity * order_items.unit_price as line_total')
    )
    ->limit(10)
    ->get();

echo json_encode($items->toArray(), JSON_PRETTY_PRINT);

echo "\n\n=== Testing Aggregation with CAST ===\n\n";

$test = DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->where('categories.id', 1)
    ->whereNull('orders.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereNull('categories.deleted_at')
    ->whereIn('orders.status', ['confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered'])
    ->selectRaw('SUM(CAST(order_items.quantity AS DECIMAL(10,2)) * CAST(order_items.unit_price AS DECIMAL(10,2))) as subtotal')
    ->first();

echo json_encode($test, JSON_PRETTY_PRINT);

echo "\n\n=== Without CAST ===\n\n";

$test2 = DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->where('categories.id', 1)
    ->whereNull('orders.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereNull('categories.deleted_at')
    ->whereIn('orders.status', ['confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered'])
    ->selectRaw('SUM(order_items.quantity * order_items.unit_price) as subtotal')
    ->first();

echo json_encode($test2, JSON_PRETTY_PRINT);
