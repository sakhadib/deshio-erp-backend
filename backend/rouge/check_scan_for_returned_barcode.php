<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductBarcode;

$barcode = '382010639597';
$result = ProductBarcode::scanBarcode($barcode);

echo "barcode={$barcode}\n";
echo "found=" . ($result['found'] ? '1' : '0') . "\n";
if ($result['found']) {
    echo "status=" . ($result['barcode']->current_status ?? 'null') . "\n";
    echo "is_active=" . ($result['barcode']->is_active ? '1' : '0') . "\n";
    echo "is_available=" . ($result['is_available'] ? '1' : '0') . "\n";
    echo "batch_qty=" . ($result['quantity_available'] ?? 'null') . "\n";
}
