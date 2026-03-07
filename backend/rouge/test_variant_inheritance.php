<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;

echo "=== TESTING VARIANT IMAGE INHERITANCE ===\n\n";

$controller = new ProductController();

// Test product 4284 (variant NU1-4)
echo "--- Product 4284 (Variant NU1-4) ---\n";
$response = $controller->show(4284);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    $product = $data['data'];
    
    echo "Product ID: {$product['id']}\n";
    echo "Product Name: {$product['name']}\n";
    echo "SKU: {$product['sku']}\n\n";
    
    echo "SKU Group Info:\n";
    echo "  - Is Variant Group: " . ($product['sku_group_info']['is_variant_group'] ? 'Yes' : 'No') . "\n";
    echo "  - Total Variants: {$product['sku_group_info']['total_variants']}\n";
    echo "  - Is Base Product: " . ($product['sku_group_info']['is_base_product'] ? 'Yes' : 'No') . "\n";
    echo "  - Base Product ID: {$product['sku_group_info']['base_product_id']}\n";
    echo "  - Base Product Name: {$product['sku_group_info']['base_product_name']}\n\n";
    
    echo "Images (original - own images only):\n";
    foreach ($product['images'] as $img) {
        $primary = $img['is_primary'] ? ' [PRIMARY]' : '';
        $inherited = isset($img['is_inherited']) && $img['is_inherited'] ? ' [INHERITED]' : '';
        echo "  - Image ID: {$img['id']}{$primary}{$inherited}\n";
    }
    
    echo "\nAll Images (with inheritance):\n";
    foreach ($product['all_images'] as $img) {
        $primary = $img['is_primary'] ? ' [PRIMARY]' : '';
        $inherited = isset($img['is_inherited']) && $img['is_inherited'] ? ' [INHERITED from Product ' . $img['inherited_from_product_id'] . ']' : ' [OWN]';
        echo "  - Image ID: {$img['id']}{$primary}{$inherited}\n";
    }
} else {
    echo "Error: " . $data['message'] . "\n";
}

// Test the base product (4281)
echo "\n\n--- Product 4281 (Base - Variant NU1-1) ---\n";
$response = $controller->show(4281);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    $product = $data['data'];
    
    echo "Product ID: {$product['id']}\n";
    echo "Product Name: {$product['name']}\n";
    echo "SKU: {$product['sku']}\n\n";
    
    echo "SKU Group Info:\n";
    echo "  - Is Variant Group: " . ($product['sku_group_info']['is_variant_group'] ? 'Yes' : 'No') . "\n";
    echo "  - Total Variants: {$product['sku_group_info']['total_variants']}\n";
    echo "  - Is Base Product: " . ($product['sku_group_info']['is_base_product'] ? 'Yes' : 'No') . "\n\n";
    
    echo "All Images:\n";
    foreach ($product['all_images'] as $img) {
        $primary = $img['is_primary'] ? ' [PRIMARY]' : '';
        $inherited = isset($img['is_inherited']) && $img['is_inherited'] ? ' [INHERITED]' : ' [OWN]';
        echo "  - Image ID: {$img['id']}{$primary}{$inherited}\n";
    }
}

// Test a standalone product (no variants)
echo "\n\n--- Testing Standalone Product (no SKU group) ---\n";
$standaloneProduct = \App\Models\Product::selectRaw('sku, COUNT(*) as count')
    ->groupBy('sku')
    ->having('count', '=', 1)
    ->first();

if ($standaloneProduct) {
    $product = \App\Models\Product::where('sku', $standaloneProduct->sku)->first();
    echo "Product ID: {$product->id}\n";
    echo "Product Name: {$product->name}\n";
    echo "SKU: {$product->sku}\n\n";
    
    $response = $controller->show($product->id);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        $productData = $data['data'];
        echo "SKU Group Info:\n";
        echo "  - Is Variant Group: " . ($productData['sku_group_info']['is_variant_group'] ? 'Yes' : 'No') . "\n";
        echo "  - Total Variants: {$productData['sku_group_info']['total_variants']}\n";
        echo "  - Is Base Product: " . ($productData['sku_group_info']['is_base_product'] ? 'Yes' : 'No') . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
