<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;

// Check product 4284 and similar products
echo "=== CHECKING PRODUCT 4284 AND SIMILAR NAMES ===\n\n";

$products = Product::where('name', 'like', '%Silk Tiedye 3Piece%')
    ->orderBy('id')
    ->get(['id', 'name', 'sku', 'brand']);

foreach ($products as $product) {
    echo "ID: {$product->id} | SKU: {$product->sku} | Name: {$product->name} | Brand: {$product->brand}\n";
    
    // Check images for this product
    $images = ProductImage::where('product_id', $product->id)->get(['id', 'is_primary', 'image_path']);
    if ($images->count() > 0) {
        echo "  Images ({$images->count()}):\n";
        foreach ($images as $img) {
            $primary = $img->is_primary ? ' [PRIMARY]' : '';
            echo "    - Image ID: {$img->id}{$primary}\n";
        }
    } else {
        echo "  No images\n";
    }
    echo "\n";
}

// Let's also check a few random products to see the pattern
echo "\n=== SAMPLE OF OTHER PRODUCTS (checking for variant pattern) ===\n\n";

$samples = Product::with('images')
    ->whereHas('images')
    ->inRandomOrder()
    ->take(5)
    ->get(['id', 'name', 'sku', 'brand']);

foreach ($samples as $product) {
    echo "ID: {$product->id} | SKU: {$product->sku} | Name: {$product->name}\n";
    echo "  Images: {$product->images->count()}\n\n";
}

// Check if there's a pattern in names
echo "\n=== CHECKING FOR COMMON BASE NAMES (variant pattern) ===\n\n";

$names = Product::selectRaw("SUBSTRING_INDEX(name, '-', 1) as base_name, COUNT(*) as count")
    ->groupBy('base_name')
    ->having('count', '>', 1)
    ->orderBy('count', 'DESC')
    ->limit(10)
    ->get();

echo "Base names with multiple products (potential variants):\n";
foreach ($names as $row) {
    echo "  - '{$row->base_name}': {$row->count} products\n";
}
