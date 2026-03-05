<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;

echo "=== Test Eloquent Query (same as controller) ===\n\n";

$query = OrderItem::query()
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->whereNull('orders.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereNull('categories.deleted_at')    ->whereIn('orders.status', ['confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered']);

// Get SQL query before grouping
echo "SQL: " . $query->toSql() . "\n\n";

$categorySales = $query->select(
    'categories.id as category_id',
    'categories.title as category_name',
    DB::raw('SUM(order_items.quantity) as total_quantity'),
    DB::raw('SUM(CAST(order_items.quantity AS DECIMAL(10,2)) * CAST(order_items.unit_price AS DECIMAL(10,2))) as subtotal'),
    DB::raw('SUM(CAST(order_items.discount_amount AS DECIMAL(10,2))) as total_discount'),
    DB::raw('SUM(CAST(order_items.tax_amount AS DECIMAL(10,2))) as total_tax')
)
->groupBy('categories.id', 'categories.title')
->where('categories.id', 1)
->get();

echo "Result:\n";
echo json_encode($categorySales->toArray(), JSON_PRETTY_PRINT);

echo "\n\n=== Test with toBase() ===\n\n";

$query2 = DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->whereNull('orders.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereNull('categories.deleted_at')
    ->whereIn('orders.status', ['confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered'])
    ->select(
        'categories.id as category_id',
        'categories.title as category_name',
        DB::raw('SUM(order_items.quantity) as total_quantity'),
        DB::raw('SUM(CAST(order_items.quantity AS DECIMAL(10,2)) * CAST(order_items.unit_price AS DECIMAL(10,2))) as subtotal'),
        DB::raw('SUM(CAST(order_items.discount_amount AS DECIMAL(10,2))) as total_discount'),
        DB::raw('SUM(CAST(order_items.tax_amount AS DECIMAL(10,2))) as total_tax')
    )
    ->groupBy('categories.id', 'categories.title')
    ->where('categories.id', 1)
    ->get();

echo json_encode($query2->toArray(), JSON_PRETTY_PRINT);
