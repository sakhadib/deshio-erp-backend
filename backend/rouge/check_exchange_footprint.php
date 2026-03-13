<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductReturn;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

echo "=== EXCHANGE FOOTPRINT CHECK ===\n\n";

$types = ProductReturn::select('return_type', DB::raw('COUNT(*) c'))->groupBy('return_type')->get();
echo "Return types in DB:\n";
foreach ($types as $t) {
    echo "- {$t->return_type}: {$t->c}\n";
}

echo "\nRefund methods in DB:\n";
$methods = Refund::select('refund_method', DB::raw('COUNT(*) c'))->groupBy('refund_method')->get();
foreach ($methods as $m) {
    echo "- {$m->refund_method}: {$m->c}\n";
}

echo "\nPotential exchange markers in refunds metadata:\n";
$exchangeMeta = Refund::whereJsonContains('refund_method_details->type', 'exchange')->count();
echo "- refund_method_details.type=exchange: {$exchangeMeta}\n";

echo "\n=== END ===\n";
