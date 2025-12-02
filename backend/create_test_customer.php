<?php
/**
 * Create a test customer for checkout flow testing
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

try {
    // Check if customer already exists
    $existingCustomer = Customer::where('email', 'customer@example.com')->first();
    
    if ($existingCustomer) {
        echo "✅ Customer already exists:\n";
        echo "   ID: {$existingCustomer->id}\n";
        echo "   Email: {$existingCustomer->email}\n";
        echo "   Name: {$existingCustomer->name}\n";
        echo "   Status: {$existingCustomer->status}\n";
        
        // Update password to ensure it's correct
        $existingCustomer->password = Hash::make('password123');
        $existingCustomer->email_verified_at = now();
        $existingCustomer->status = 'active';
        $existingCustomer->save();
        
        echo "   Password reset to: password123\n";
        echo "   Email verified: Yes\n";
        exit(0);
    }
    
    // Create new customer
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'phone' => '+8801712345678',
        'customer_type' => 'ecommerce',
        'status' => 'active',
        'email_verified_at' => now(),
        'address' => '123 Main Street',
        'city' => 'Dhaka',
        'state' => 'Dhaka Division',
        'postal_code' => '1200',
        'country' => 'Bangladesh',
    ]);
    
    echo "✅ Test customer created successfully!\n";
    echo "   ID: {$customer->id}\n";
    echo "   Email: {$customer->email}\n";
    echo "   Password: password123\n";
    echo "   Name: {$customer->name}\n";
    echo "   Phone: {$customer->phone}\n";
    echo "   Status: {$customer->status}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
