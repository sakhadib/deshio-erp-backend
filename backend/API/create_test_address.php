<?php
/**
 * Create customer address directly via SQL for testing
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check if address exists for customer 3
    $existingAddress = DB::table('customer_addresses')
        ->where('customer_id', 3)
        ->first();
    
    if ($existingAddress) {
        echo "✅ Address already exists for customer ID 3:\n";
        echo "   Address ID: {$existingAddress->id}\n";
        echo "   Name: {$existingAddress->name}\n";
        echo "   City: {$existingAddress->city}\n";
        exit(0);
    }
    
    // Create new address
    $addressId = DB::table('customer_addresses')->insertGetId([
        'customer_id' => 3,
        'name' => 'John Doe',
        'phone' => '+8801712345678',
        'address_line_1' => '123 Main Street, Apartment 4B',
        'address_line_2' => 'Near City Hospital',
        'city' => 'Dhaka',
        'state' => 'Dhaka Division',
        'postal_code' => '1200',
        'country' => 'Bangladesh',
        'landmark' => 'Opposite to Green Park',
        'type' => 'both',
        'is_default_shipping' => true,
        'is_default_billing' => true,
        'delivery_instructions' => 'Please call before delivery',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✅ Test address created successfully!\n";
    echo "   Address ID: {$addressId}\n";
    echo "   Customer ID: 3\n";
    echo "   Name: John Doe\n";
    echo "   City: Dhaka\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
