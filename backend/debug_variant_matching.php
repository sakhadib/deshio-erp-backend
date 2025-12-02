<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing MD5 Variant Matching ===\n\n";

// Clear cart first
DB::table('carts')->where('customer_id', 2)->delete();
echo "✅ Cart cleared\n\n";

// Create a test cart item with variants
$cartItem = \App\Models\Cart::create([
    'customer_id' => 2,
    'product_id' => 1,
    'variant_options' => ['color' => 'Blue', 'size' => 'L'],
    'quantity' => 1,
    'unit_price' => 100.00,
    'status' => 'active',
]);
echo "✅ Created cart item ID: {$cartItem->id}\n";
echo "   variant_options: " . json_encode($cartItem->variant_options) . "\n\n";

// Test 1: Direct database query
echo "Test 1: Raw database query\n";
$result = DB::select("SELECT id, variant_options, MD5(CAST(variant_options AS TEXT)) as hash FROM carts WHERE customer_id = 2");
foreach ($result as $row) {
    echo "  ID: {$row->id}, Hash: {$row->hash}\n";
    echo "  JSON: {$row->variant_options}\n";
}
echo "\n";

// Test 2: What hash are we searching for?
$searchVariants = ['color' => 'Blue', 'size' => 'L'];
$searchJson = json_encode($searchVariants);
echo "Test 2: Hash we're searching for\n";
echo "  JSON: $searchJson\n";
$searchHash = md5($searchJson);
echo "  MD5: $searchHash\n\n";

// Test 3: Try the query that CartController uses
echo "Test 3: CartController query\n";
$query = \App\Models\Cart::where('customer_id', 2)
    ->where('product_id', 1)
    ->where('status', 'active');

$variantJson = json_encode($searchVariants);
$query->whereRaw('MD5(CAST(variant_options AS TEXT)) = MD5(?)', [$variantJson]);

$found = $query->first();
if ($found) {
    echo "  ✅ Found existing cart item: ID {$found->id}\n";
} else {
    echo "  ❌ Did NOT find existing cart item\n";
}
echo "\n";

// Test 4: Try different approaches
echo "Test 4: Testing different JSON comparison methods\n";

// Method 1: CAST to TEXT
$test1 = DB::select("SELECT * FROM carts WHERE customer_id = 2 AND CAST(variant_options AS TEXT) = CAST(? AS TEXT)", [$searchJson]);
echo "  Method 1 (CAST to TEXT): " . (count($test1) > 0 ? "✅ Found" : "❌ Not found") . "\n";

// Method 2: MD5 with TEXT cast
$test2 = DB::select("SELECT * FROM carts WHERE customer_id = 2 AND MD5(CAST(variant_options AS TEXT)) = MD5(?)", [$searchJson]);
echo "  Method 2 (MD5): " . (count($test2) > 0 ? "✅ Found" : "❌ Not found") . "\n";

// Method 3: Direct JSON comparison with ::jsonb
$test3 = DB::select("SELECT * FROM carts WHERE customer_id = 2 AND variant_options::jsonb = ?::jsonb", [$searchJson]);
echo "  Method 3 (::jsonb): " . (count($test3) > 0 ? "✅ Found" : "❌ Not found") . "\n";

echo "\n";

// Test 5: Check what's actually stored
echo "Test 5: Checking stored JSON format\n";
$stored = DB::select("SELECT id, variant_options, CAST(variant_options AS TEXT) as text_version FROM carts WHERE customer_id = 2");
foreach ($stored as $row) {
    echo "  ID: {$row->id}\n";
    echo "  Raw JSON: {$row->variant_options}\n";
    echo "  As TEXT: {$row->text_version}\n";
    echo "  Our search: $searchJson\n";
    echo "  Match: " . ($row->text_version === $searchJson ? "✅ Yes" : "❌ No") . "\n";
}
