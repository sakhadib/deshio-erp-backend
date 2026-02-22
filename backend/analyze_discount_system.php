<?php

/**
 * DISCOUNT SYSTEM FEASIBILITY ANALYSIS
 * 
 * This script analyzes the current backend state to determine
 * if PM's sale/discount requirement is implementable.
 * 
 * PM Requirements:
 * 1. Create sale campaigns with percentage/fixed discounts
 * 2. Apply to individual products OR entire categories
 * 3. System-wide application (POS, eCommerce, social commerce)
 * 4. Start date + optional end date OR manual termination
 * 5. API to check active discounts
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Promotion;
use App\Models\ProductBatch;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "  DISCOUNT SYSTEM FEASIBILITY ANALYSIS\n";
echo "=================================================================\n\n";

// 1. Check existing promotions table structure
echo "1. PROMOTIONS TABLE STRUCTURE\n";
echo "   -----------------------------------------\n";

if (Schema::hasTable('promotions')) {
    $columns = Schema::getColumnListing('promotions');
    echo "   ✅ promotions table EXISTS\n";
    echo "   Columns: " . implode(', ', $columns) . "\n\n";
    
    // Check key fields for PM's requirement
    $requiredFields = ['type', 'discount_value', 'applicable_products', 'applicable_categories', 'start_date', 'end_date', 'is_active'];
    foreach ($requiredFields as $field) {
        $exists = in_array($field, $columns);
        $status = $exists ? '✅' : '❌';
        echo "   {$status} {$field}\n";
    }
    
    echo "\n   Current promotions count: " . Promotion::count() . "\n";
    
    // Show first promotion if exists
    $sample = Promotion::first();
    if ($sample) {
        echo "\n   Sample promotion:\n";
        echo "   - Name: {$sample->name}\n";
        echo "   - Type: {$sample->type}\n";
        echo "   - Code: {$sample->code}\n";
        echo "   - Discount: {$sample->discount_value}\n";
    }
} else {
    echo "   ❌ promotions table DOES NOT EXIST\n";
}

echo "\n";

// 2. Check pricing structure
echo "2. PRICING STRUCTURE ANALYSIS\n";
echo "   -----------------------------------------\n";

$batchSample = ProductBatch::with('product')->first();
if ($batchSample) {
    echo "   ✅ ProductBatch structure:\n";
    echo "   - sell_price: {$batchSample->sell_price} (main price field)\n";
    echo "   - cost_price: {$batchSample->cost_price}\n";
    echo "   - tax_percentage: {$batchSample->tax_percentage}\n";
    echo "   - Product: {$batchSample->product->name}\n";
} else {
    echo "   ⚠️  No product batches found in database\n";
}

echo "\n";

// 3. Check order discount support
echo "3. ORDER DISCOUNT SUPPORT\n";
echo "   -----------------------------------------\n";

$orderColumns = Schema::getColumnListing('orders');
$orderItemColumns = Schema::getColumnListing('order_items');

echo "   Orders table has 'discount_amount': " . (in_array('discount_amount', $orderColumns) ? '✅ YES' : '❌ NO') . "\n";
echo "   OrderItems table has 'discount_amount': " . (in_array('discount_amount', $orderItemColumns) ? '✅ YES' : '❌ NO') . "\n";

$sampleOrder = Order::with('items')->first();
if ($sampleOrder) {
    $hasDiscount = $sampleOrder->discount_amount > 0;
    echo "\n   Sample order #{$sampleOrder->order_number}:\n";
    echo "   - Subtotal: {$sampleOrder->subtotal_amount}\n";
    echo "   - Discount: {$sampleOrder->discount_amount} " . ($hasDiscount ? '(has discount)' : '(no discount)') . "\n";
    echo "   - Total: {$sampleOrder->total_amount}\n";
}

echo "\n";

// 4. Check categories
echo "4. CATEGORY STRUCTURE\n";
echo "   -----------------------------------------\n";

$categoryCount = Category::count();
$productCount = Product::count();

echo "   Total categories: {$categoryCount}\n";
echo "   Total products: {$productCount}\n";

if ($categoryCount > 0) {
    $sampleCategory = Category::withCount('products')->first();
    echo "\n   Sample category:\n";
    echo "   - Name: {$sampleCategory->name}\n";
    echo "   - Products: {$sampleCategory->products_count}\n";
}

echo "\n";

// 5. Check existing discount implementation
echo "5. EXISTING DISCOUNT IMPLEMENTATION\n";
echo "   -----------------------------------------\n";

// Check if PromotionController has automatic discount methods
$controllerPath = app_path('Http/Controllers/PromotionController.php');
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    
    $hasValidateCode = strpos($controllerContent, 'validateCode') !== false;
    $hasApplyToOrder = strpos($controllerContent, 'applyToOrder') !== false;
    $hasAutoDiscount = strpos($controllerContent, 'getActiveDiscounts') !== false || 
                       strpos($controllerContent, 'calculateAutoDiscount') !== false;
    
    echo "   ✅ PromotionController exists\n";
    echo "   " . ($hasValidateCode ? '✅' : '❌') . " Has validateCode() method (coupon validation)\n";
    echo "   " . ($hasApplyToOrder ? '✅' : '❌') . " Has applyToOrder() method (manual application)\n";
    echo "   " . ($hasAutoDiscount ? '✅' : '❌') . " Has automatic discount methods\n";
    
    if (!$hasAutoDiscount) {
        echo "\n   ⚠️  Current system is COUPON-BASED, not AUTOMATIC\n";
        echo "   ⚠️  Requires code input to apply discount\n";
    }
} else {
    echo "   ❌ PromotionController does NOT exist\n";
}

echo "\n";

// 6. Analysis summary
echo "=================================================================\n";
echo "  FEASIBILITY ASSESSMENT\n";
echo "=================================================================\n\n";

$canImplement = true;
$requirements = [
    'Database table for campaigns' => Schema::hasTable('promotions'),
    'Discount value storage' => in_array('discount_value', Schema::getColumnListing('promotions')),
    'Product targeting' => in_array('applicable_products', Schema::getColumnListing('promotions')),
    'Category targeting' => in_array('applicable_categories', Schema::getColumnListing('promotions')),
    'Date range support' => in_array('start_date', Schema::getColumnListing('promotions')) && in_array('end_date', Schema::getColumnListing('promotions')),
    'Active status control' => in_array('is_active', Schema::getColumnListing('promotions')),
    'Order discount field' => in_array('discount_amount', $orderColumns),
    'OrderItem discount field' => in_array('discount_amount', $orderItemColumns),
];

foreach ($requirements as $requirement => $met) {
    $status = $met ? '✅ AVAILABLE' : '❌ MISSING';
    echo "   {$status} : {$requirement}\n";
    if (!$met) {
        $canImplement = false;
    }
}

echo "\n";

if ($canImplement) {
    echo "   ✅ IMPLEMENTATION IS FEASIBLE\n\n";
    echo "   Infrastructure exists:\n";
    echo "   - Database tables ready\n";
    echo "   - Product/Category relationships working\n";
    echo "   - Discount fields in orders\n";
    echo "   - Date range and activation controls\n\n";
    
    echo "   What needs to be BUILT:\n";
    echo "   1. Automatic discount calculation service\n";
    echo "   2. API to get active discounts for products/categories\n";
    echo "   3. Integration into order creation (POS/eCommerce/social)\n";
    echo "   4. Campaign management panel endpoints (beyond existing promotions CRUD)\n";
    echo "   5. System-wide discount application logic\n\n";
    
    echo "   Existing 'promotions' table CAN BE REUSED:\n";
    echo "   - Change from 'code-based' to 'automatic'\n";
    echo "   - Add 'is_automatic' flag (or use is_public creatively)\n";
    echo "   - Leverage existing applicable_products/categories JSON\n";
    echo "   - Use existing start_date/end_date/is_active\n";
    
} else {
    echo "   ❌ MISSING REQUIRED INFRASTRUCTURE\n";
    echo "   Additional database migrations needed\n";
}

echo "\n";

// 7. Recommended approach
echo "=================================================================\n";
echo "  RECOMMENDED IMPLEMENTATION APPROACH\n";
echo "=================================================================\n\n";

if ($canImplement) {
    echo "   OPTION 1: Extend existing Promotions table (RECOMMENDED)\n";
    echo "   --------------------------------------------------------\n";
    echo "   ✅ Adds 'is_automatic' boolean flag\n";
    echo "   ✅ Auto-discounts (is_automatic=true) apply without code\n";
    echo "   ✅ Reuse existing fields: applicable_products, applicable_categories\n";
    echo "   ✅ Keep coupon system alongside automatic discounts\n";
    echo "   ✅ Use same management interface\n\n";
    
    echo "   OPTION 2: Create separate 'sale_campaigns' table\n";
    echo "   --------------------------------------------------------\n";
    echo "   ⚠️  More work - new migration needed\n";
    echo "   ⚠️  Duplicates existing promotion structure\n";
    echo "   ✅ Cleaner separation of concerns\n";
    echo "   ✅ Different permissions/access control\n";
    
    echo "\n   💡 SUGGESTION: Use Option 1 (extend promotions table)\n";
}

echo "\n=================================================================\n";
echo "  ANALYSIS COMPLETE\n";
echo "=================================================================\n";
