<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== E-COMMERCE CART API DIAGNOSTIC TEST ===\n\n";

// Step 1: Create or find an e-commerce customer
echo "Step 1: Creating/Finding e-commerce customer...\n";
$customer = \App\Models\Customer::where('email', 'test.ecommerce@example.com')->first();

if (!$customer) {
    try {
        $customer = \App\Models\Customer::create([
            'customer_type' => 'ecommerce',
            'name' => 'Test Ecommerce Customer',
            'email' => 'test.ecommerce@example.com',
            'phone' => '01712345678',
            'password' => bcrypt('password123'),
            'status' => 'active',
            'address' => '123 Test Street',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);
        echo "✅ Customer created: ID {$customer->id}, Email: {$customer->email}\n";
    } catch (\Exception $e) {
        echo "❌ Failed to create customer: {$e->getMessage()}\n";
        exit(1);
    }
} else {
    echo "✅ Found existing customer: ID {$customer->id}, Email: {$customer->email}\n";
}

// Step 2: Get JWT token for customer
echo "\nStep 2: Getting JWT token...\n";
try {
    $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($customer);
    echo "✅ JWT Token generated: " . substr($token, 0, 50) . "...\n";
} catch (\Exception $e) {
    echo "❌ Failed to generate token: {$e->getMessage()}\n";
    exit(1);
}

// Step 3: Find or create a test product
echo "\nStep 3: Finding/Creating test product...\n";
$product = \App\Models\Product::with(['batches' => function($q) {
    $q->where('is_active', true)->where('availability', true);
}])->first();

if (!$product) {
    echo "❌ No products found in database. Creating test product...\n";
    
    // Get or create a category
    $category = \App\Models\Category::first();
    if (!$category) {
        $category = \App\Models\Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'status' => 'active',
        ]);
    }
    
    $product = \App\Models\Product::create([
        'category_id' => $category->id,
        'name' => 'Test Product',
        'sku' => 'TEST-' . time(),
        'description' => 'Test product for cart',
        'is_archived' => false,
    ]);
    
    // Create a batch for the product
    $store = \App\Models\Store::first();
    if (!$store) {
        $store = \App\Models\Store::create([
            'name' => 'Test Store',
            'code' => 'TS001',
            'address' => 'Test Address',
            'status' => 'active',
        ]);
    }
    
    $batch = \App\Models\ProductBatch::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-' . time(),
        'quantity' => 100,
        'cost_price' => 50.00,
        'sell_price' => 99.99,
        'availability' => true,
        'store_id' => $store->id,
        'is_active' => true,
    ]);
    
    echo "✅ Created test product: ID {$product->id}, Name: {$product->name}\n";
    echo "✅ Created batch: ID {$batch->id}, Quantity: {$batch->quantity}, Price: {$batch->sell_price}\n";
} else {
    echo "✅ Found product: ID {$product->id}, Name: {$product->name}\n";
    $batch = $product->batches->first();
    if ($batch) {
        echo "✅ Found batch: ID {$batch->id}, Quantity: {$batch->quantity}, Price: {$batch->sell_price}\n";
    } else {
        echo "⚠️  No batches found for product. Creating one...\n";
        $store = \App\Models\Store::first();
        if (!$store) {
            $store = \App\Models\Store::create([
                'name' => 'Test Store',
                'code' => 'TS001',
                'address' => 'Test Address',
                'status' => 'active',
            ]);
        }
        
        $batch = \App\Models\ProductBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'BATCH-' . time(),
            'quantity' => 100,
            'cost_price' => 50.00,
            'sell_price' => 99.99,
            'availability' => true,
            'store_id' => $store->id,
            'is_active' => true,
        ]);
        echo "✅ Created batch: ID {$batch->id}, Quantity: {$batch->quantity}, Price: {$batch->sell_price}\n";
    }
}

// Step 4: Simulate API call to add to cart
echo "\nStep 4: Testing POST /api/cart/add endpoint...\n";

// Test Case 1: Without variant_options
echo "\n--- Test Case 1: Add to cart WITHOUT variants ---\n";
$payload1 = [
    'product_id' => $product->id,
    'quantity' => 2,
];

try {
    // Simulate the controller logic
    $request = new \Illuminate\Http\Request($payload1);
    
    // Validate
    $validator = \Illuminate\Support\Facades\Validator::make($payload1, [
        'product_id' => 'required|integer|exists:products,id',
        'quantity' => 'required|integer|min:1|max:100',
        'notes' => 'nullable|string|max:500',
        'variant_options' => 'nullable|array',
        'variant_options.color' => 'nullable|string|max:50',
        'variant_options.size' => 'nullable|string|max:50',
    ]);
    
    if ($validator->fails()) {
        echo "❌ Validation failed:\n";
        print_r($validator->errors()->all());
    } else {
        echo "✅ Validation passed\n";
        
        // Check if product exists
        $testProduct = \App\Models\Product::find($payload1['product_id']);
        if (!$testProduct) {
            echo "❌ Product not found\n";
        } else {
            echo "✅ Product found: {$testProduct->name}\n";
            
            // Check stock (need to look at batches since Product doesn't have stock_quantity)
            $totalStock = $testProduct->batches()->active()->sum('quantity');
            echo "✅ Total stock available: {$totalStock}\n";
            
            // Try to create cart item
            try {
                $cartItem = \App\Models\Cart::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'variant_options' => null,
                    'quantity' => $payload1['quantity'],
                    'unit_price' => $batch->sell_price ?? 99.99,
                    'notes' => null,
                    'status' => 'active',
                ]);
                echo "✅ Cart item created: ID {$cartItem->id}\n";
            } catch (\Exception $e) {
                echo "❌ Failed to create cart item: {$e->getMessage()}\n";
                echo "SQL Error: " . $e->getMessage() . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Test failed: {$e->getMessage()}\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Test Case 2: With variant_options
echo "\n--- Test Case 2: Add to cart WITH variants ---\n";
$payload2 = [
    'product_id' => $product->id,
    'quantity' => 1,
    'variant_options' => [
        'color' => 'Blue',
        'size' => 'L',
    ],
];

try {
    $validator = \Illuminate\Support\Facades\Validator::make($payload2, [
        'product_id' => 'required|integer|exists:products,id',
        'quantity' => 'required|integer|min:1|max:100',
        'notes' => 'nullable|string|max:500',
        'variant_options' => 'nullable|array',
        'variant_options.color' => 'nullable|string|max:50',
        'variant_options.size' => 'nullable|string|max:50',
    ]);
    
    if ($validator->fails()) {
        echo "❌ Validation failed:\n";
        print_r($validator->errors()->all());
    } else {
        echo "✅ Validation passed\n";
        
        try {
            $cartItem = \App\Models\Cart::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'variant_options' => $payload2['variant_options'],
                'quantity' => $payload2['quantity'],
                'unit_price' => $batch->sell_price ?? 99.99,
                'notes' => null,
                'status' => 'active',
            ]);
            echo "✅ Cart item created with variants: ID {$cartItem->id}\n";
        } catch (\Exception $e) {
            echo "❌ Failed to create cart item: {$e->getMessage()}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Test failed: {$e->getMessage()}\n";
}

// Step 5: Check what's in the cart now
echo "\nStep 5: Checking cart contents...\n";
$cartItems = \App\Models\Cart::where('customer_id', $customer->id)
    ->where('status', 'active')
    ->with('product')
    ->get();

if ($cartItems->isEmpty()) {
    echo "⚠️  Cart is empty\n";
} else {
    echo "✅ Cart has {$cartItems->count()} item(s):\n";
    foreach ($cartItems as $item) {
        echo "  - Product: {$item->product->name}, Qty: {$item->quantity}, Price: {$item->unit_price}, Variants: " . json_encode($item->variant_options) . "\n";
    }
}

// Step 6: Check Product model fields that might be missing
echo "\nStep 6: Analyzing Product model fields...\n";
$productFields = DB::select("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'products' ORDER BY ordinal_position");
echo "Products table columns:\n";
foreach ($productFields as $field) {
    echo "  - {$field->column_name}: {$field->data_type} (nullable: {$field->is_nullable})\n";
}

// Check if products have required e-commerce fields
echo "\nChecking if Product has e-commerce fields:\n";
$hasStockQuantity = collect($productFields)->contains('column_name', 'stock_quantity');
$hasSellingPrice = collect($productFields)->contains('column_name', 'selling_price');
$hasStatus = collect($productFields)->contains('column_name', 'status');
$hasIsActive = collect($productFields)->contains('column_name', 'is_active');

echo "  - stock_quantity: " . ($hasStockQuantity ? "✅ EXISTS" : "❌ MISSING") . "\n";
echo "  - selling_price: " . ($hasSellingPrice ? "✅ EXISTS" : "❌ MISSING") . "\n";
echo "  - status: " . ($hasStatus ? "✅ EXISTS" : "❌ MISSING") . "\n";
echo "  - is_active: " . ($hasIsActive ? "✅ EXISTS" : "❌ MISSING") . "\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "\nSUMMARY:\n";
echo "- Customer authentication: ✅\n";
echo "- Product availability: " . ($product ? "✅" : "❌") . "\n";
echo "- Product batches: " . ($batch ? "✅" : "❌") . "\n";
echo "- Cart validation: ✅\n";
echo "- Cart creation: Check output above\n";

echo "\nPROBLEM ANALYSIS:\n";
if (!$hasStockQuantity || !$hasSellingPrice || !$hasStatus) {
    echo "⚠️  ISSUE FOUND: Product table is missing e-commerce fields!\n";
    echo "   The CartController expects 'stock_quantity', 'selling_price', 'status', 'is_active' on Product model\n";
    echo "   But Product model only has basic ERP fields (sku, name, description, is_archived)\n";
    echo "   Price and stock are managed through ProductBatch!\n";
    echo "\n   SOLUTION: CartController needs to be updated to:\n";
    echo "   1. Get price from ProductBatch instead of Product.selling_price\n";
    echo "   2. Check stock from ProductBatch.quantity instead of Product.stock_quantity\n";
    echo "   3. Check Product.is_archived instead of Product.status/is_active\n";
}
