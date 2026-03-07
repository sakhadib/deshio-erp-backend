<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

// Check if there's a parent_product_id or similar field
echo "=== CHECKING products TABLE STRUCTURE ===\n\n";
$columns = DB::select("DESCRIBE products");
foreach ($columns as $col) {
    echo "{$col->Field} | {$col->Type} | Null: {$col->Null} | Default: {$col->Default}\n";
}

// Check product_fields to see if there's a variant indicator
echo "\n\n=== CHECKING product_fields TABLE ===\n\n";
$fields = DB::table('fields')->get();
echo "Available fields:\n";
foreach ($fields as $field) {
    echo "  - ID: {$field->id} | Title: {$field->title} | Type: {$field->type}\n";
}

// Check if any of the Silk Tiedye products have product_fields set
echo "\n\n=== CHECKING product_fields FOR Silk Tiedye PRODUCTS ===\n\n";
$productFields = DB::table('product_fields')
    ->whereIn('product_id', [4281, 4282, 4283, 4284, 4285])
    ->join('fields', 'product_fields.field_id', '=', 'fields.id')
    ->select('product_fields.*', 'fields.title as field_name')
    ->get();

if ($productFields->count() > 0) {
    foreach ($productFields as $pf) {
        echo "Product {$pf->product_id}: {$pf->field_name} = {$pf->value}\n";
    }
} else {
    echo "No product_fields set for these products\n";
}

// Check if there's a pattern - is the first product always the "base"?
echo "\n\n=== CHECKING SKU GROUPS (potential base identification) ===\n\n";

$skuGroup = Product::where('sku', 'ST-A23-1550')
    ->orderBy('id')
    ->get(['id', 'name', 'created_at']);

echo "All products with SKU ST-A23-1550 (ordered by ID - first might be base):\n";
foreach ($skuGroup as $product) {
    echo "  ID: {$product->id} | Name: {$product->name} | Created: {$product->created_at}\n";
}

// Let's check another SKU group pattern
echo "\n\n=== CHECKING ANOTHER SKU GROUP ===\n\n";

$sampleSku = Product::selectRaw('sku, COUNT(*) as count')
    ->groupBy('sku')
    ->having('count', '>', 5)
    ->orderBy('count', 'DESC')
    ->first();

if ($sampleSku) {
    echo "Sample SKU with multiple products: {$sampleSku->sku} ({$sampleSku->count} products)\n\n";
    
    $group = Product::where('sku', $sampleSku->sku)
        ->orderBy('id')
        ->take(5)
        ->get(['id', 'name']);
    
    foreach ($group as $p) {
        echo "  ID: {$p->id} | Name: {$p->name}\n";
    }
}
