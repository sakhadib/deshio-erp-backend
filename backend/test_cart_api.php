<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if carts table has variant_options column
echo "=== Checking carts table structure ===\n";
$columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'carts' ORDER BY ordinal_position");
foreach ($columns as $column) {
    echo "{$column->column_name}: {$column->data_type}\n";
}

echo "\n=== Checking Cart model fillable ===\n";
$cart = new \App\Models\Cart();
print_r($cart->getFillable());

echo "\n=== Checking Cart model casts ===\n";
print_r($cart->getCasts());

echo "\n=== Testing variant_options creation ===\n";
try {
    $testData = [
        'customer_id' => 1,
        'product_id' => 1,
        'variant_options' => ['color' => 'Blue', 'size' => 'L'],
        'quantity' => 1,
        'unit_price' => 100.00,
        'status' => 'active',
    ];
    echo "Test data:\n";
    print_r($testData);
    
    // Test if we can create (will fail if no customer/product, but shows if fillable works)
    $cart = new \App\Models\Cart($testData);
    echo "Cart model created successfully!\n";
    echo "variant_options value: ";
    print_r($cart->variant_options);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
