<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;

// Test the exact query from the controller
$query = OrderItem::query()
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->whereNull('orders.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereNull('categories.deleted_at')
    ->whereIn('orders.status', ['confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered']);

$categorySales = $query->select(
    'categories.id as category_id',
    'categories.title as category_name',
    DB::raw('SUM(order_items.quantity) as total_quantity'),
    DB::raw('SUM(CAST(order_items.quantity AS DECIMAL(10,2)) * CAST(order_items.unit_price AS DECIMAL(10,2))) as subtotal'),
    DB::raw('SUM(CAST(order_items.discount_amount AS DECIMAL(10,2))) as total_discount'),
    DB::raw('SUM(CAST(order_items.tax_amount AS DECIMAL(10,2))) as total_tax')
)
->groupBy('categories.id', 'categories.title')
->limit(5)
->get();

echo "Category Sales Query Results:\n";
echo json_encode($categorySales->toArray(), JSON_PRETTY_PRINT);

// Also check a sample order item
echo "\n\nSample Order Items:\n";
$sampleItems = OrderItem::with(['order', 'product.category'])
    ->whereHas('order', function($q) {
        $q->whereIn('status', ['confirmed', 'delivered']);
    })
    ->limit(3)
    ->get(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'discount_amount', 'tax_amount'])
    ->toArray();

echo json_encode($sampleItems, JSON_PRETTY_PRINT);
