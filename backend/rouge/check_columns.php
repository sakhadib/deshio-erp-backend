<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("DESCRIBE products");

echo "=== ALL COLUMNS IN products TABLE ===\n\n";
foreach ($columns as $col) {
    echo "{$col->Field} | {$col->Type} | Null: {$col->Null} | Key: {$col->Key} | Default: {$col->Default} | Extra: {$col->Extra}\n";
}
