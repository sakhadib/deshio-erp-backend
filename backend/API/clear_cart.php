<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

DB::table('carts')->where('customer_id', 2)->delete();
echo "Cart cleared for customer 2\n";

// Show remaining carts
$carts = DB::table('carts')->where('customer_id', 2)->get();
echo "Remaining cart items: " . $carts->count() . "\n";
