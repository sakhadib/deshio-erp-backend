<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Http\Controllers\ProductReturnController;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Models\ProductReturn;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function out($msg)
{
    echo $msg . PHP_EOL;
}

function assertTrue($condition, $label)
{
    if ($condition) {
        out("[PASS] " . $label);
        return true;
    }

    out("[FAIL] " . $label);
    return false;
}

function assertApiSuccess(array $payload, string $label)
{
    $ok = ($payload['success'] ?? false) === true;
    if ($ok) {
        out("[PASS] " . $label);
        return true;
    }

    $msg = $payload['message'] ?? 'Unknown API error';
    out("[FAIL] " . $label . " | message=" . $msg);
    return false;
}

out("=== RETURN/EXCHANGE REQUIREMENTS VALIDATION ===");
out("Running with DB transaction rollback for safe testing...");
out("");

$passes = 0;
$fails = 0;

DB::beginTransaction();
try {
    $employee = Employee::query()->first();
    if (!$employee) {
        throw new Exception('No employee found for auth simulation.');
    }
    Auth::login($employee);

    $controller = new ProductReturnController();

    // Candidate sold barcode with order linkage.
    $soldBarcode = ProductBarcode::query()
        ->whereIn('current_status', ['with_customer', 'sold'])
        ->where('is_defective', false)
        ->whereNotNull('batch_id')
        ->whereNotNull('location_metadata')
        ->orderByDesc('id')
        ->first();

    if (!$soldBarcode) {
        throw new Exception('No sold barcode candidate found.');
    }

    $orderId = $soldBarcode->location_metadata['order_id'] ?? null;
    if (!$orderId) {
        throw new Exception('Sold barcode has no order_id in metadata.');
    }

    $order = Order::find($orderId);
    if (!$order) {
        throw new Exception('Order not found for sold barcode metadata.');
    }

    $orderItem = OrderItem::query()
        ->where('order_id', $order->id)
        ->where('product_id', $soldBarcode->product_id)
        ->where('product_batch_id', $soldBarcode->batch_id)
        ->orderByDesc('id')
        ->first();

    if (!$orderItem) {
        throw new Exception('No matching order item found for sold barcode.');
    }

    // Pick a different store for cross-store return.
    $storeB = Store::query()->where('id', '!=', $order->store_id)->orderBy('id')->first();
    if (!$storeB) {
        throw new Exception('No alternative store available for cross-store test.');
    }

    $originalBatch = ProductBatch::findOrFail($orderItem->product_batch_id);

    // Pre-check whether store B has the exact batch already.
    $existingTargetBatch = ProductBatch::query()
        ->where('product_id', $orderItem->product_id)
        ->where('store_id', $storeB->id)
        ->where('batch_number', $originalBatch->batch_number)
        ->first();

    $hadBatchBefore = $existingTargetBatch !== null;
    $qtyBefore = $existingTargetBatch?->quantity ?? 0;

    out("Using order #{$order->order_number} / product {$orderItem->product_name} / barcode {$soldBarcode->barcode}");
    out("Cross-store return target: Store A={$order->store_id} -> Store B={$storeB->id}");
    out("Target batch existed before? " . ($hadBatchBefore ? 'YES' : 'NO'));
    out("");

    // 1) Create return (cross-store)
    $storeRequest = Request::create('/api/returns', 'POST', [
        'order_id' => $order->id,
        'received_at_store_id' => $storeB->id,
        'return_reason' => 'changed_mind',
        'return_type' => 'customer_return',
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
                'reason' => 'Test cross-store return',
            ],
        ],
    ]);

    $storeResp = $controller->store($storeRequest);
    $storePayload = json_decode($storeResp->getContent(), true);

    $ok = assertApiSuccess($storePayload, 'Create return for barcode-tracked sold item');
    $ok ? $passes++ : $fails++;

    if (!$ok) {
        throw new Exception('Cannot continue test flow after create failure.');
    }

    $returnId = $storePayload['data']['id'];

    // 2) Set quality check (required for approval)
    $updateRequest = Request::create("/api/returns/{$returnId}", 'PATCH', [
        'quality_check_passed' => true,
        'quality_check_notes' => 'Automated test quality pass',
    ]);

    $updateResp = $controller->update($updateRequest, $returnId);
    $updatePayload = json_decode($updateResp->getContent(), true);
    $ok = assertApiSuccess($updatePayload, 'Quality check update succeeds');
    $ok ? $passes++ : $fails++;

    // 3) Approve return (should auto-restore stock and barcode to sellable status)
    $approveRequest = Request::create("/api/returns/{$returnId}/approve", 'POST', []);
    $approveResp = $controller->approve($approveRequest, $returnId);
    $approvePayload = json_decode($approveResp->getContent(), true);

    $ok = assertApiSuccess($approvePayload, 'Approve return succeeds');
    $ok ? $passes++ : $fails++;

    if (!$ok) {
        throw new Exception('Approval failed; aborting dependent checks.');
    }

    $return = ProductReturn::findOrFail($returnId);

    $ok = assertTrue($return->status === 'approved', 'Return status remains approved after auto-restoration');
    $ok ? $passes++ : $fails++;

    $targetBatchAfterApprove = ProductBatch::query()
        ->where('product_id', $orderItem->product_id)
        ->where('store_id', $storeB->id)
        ->where('batch_number', $originalBatch->batch_number)
        ->first();

    $ok = assertTrue($targetBatchAfterApprove !== null, 'Cross-store target batch exists after approval');
    $ok ? $passes++ : $fails++;

    if ($targetBatchAfterApprove) {
        $expectedQty = $qtyBefore + 1;
        $ok = assertTrue((int) $targetBatchAfterApprove->quantity === $expectedQty, 'Target batch quantity increased exactly by returned quantity');
        $ok ? $passes++ : $fails++;
    }

    $returnedBarcode = ProductBarcode::whereJsonContains('location_metadata->return_id', $returnId)
        ->orderByDesc('id')
        ->first();

    $ok = assertTrue($returnedBarcode !== null, 'Returned barcode linked to return_id metadata');
    $ok ? $passes++ : $fails++;

    if ($returnedBarcode) {
        $ok = assertTrue($returnedBarcode->current_status === 'in_warehouse', 'No in_return phase: barcode status is in_warehouse immediately');
        $ok ? $passes++ : $fails++;

        $ok = assertTrue((int) $returnedBarcode->current_store_id === (int) $storeB->id, 'Returned barcode moved to receiving store');
        $ok ? $passes++ : $fails++;

        $ok = assertTrue($returnedBarcode->is_active === true, 'Returned barcode is active for resale');
        $ok ? $passes++ : $fails++;

        $scan = ProductBarcode::scanBarcode($returnedBarcode->barcode);
        $ok = assertTrue(($scan['is_available'] ?? false) === true, 'Scan API reports returned barcode as available');
        $ok ? $passes++ : $fails++;
    }

    // 4) Process endpoint should be idempotent (no extra quantity increment)
    $qtyBeforeProcess = $targetBatchAfterApprove ? (int) $targetBatchAfterApprove->quantity : null;
    $processRequest = Request::create("/api/returns/{$returnId}/process", 'POST', [
        'restore_inventory' => true,
    ]);
    $processResp = $controller->process($processRequest, $returnId);
    $processPayload = json_decode($processResp->getContent(), true);

    $ok = assertApiSuccess($processPayload, 'Process endpoint remains backward-compatible');
    $ok ? $passes++ : $fails++;

    if ($targetBatchAfterApprove) {
        $targetBatchAfterProcess = ProductBatch::find($targetBatchAfterApprove->id);
    } else {
        $targetBatchAfterProcess = null;
    }
    if ($targetBatchAfterProcess && $qtyBeforeProcess !== null) {
        $ok = assertTrue((int) $targetBatchAfterProcess->quantity === $qtyBeforeProcess, 'Process is idempotent: no duplicate stock increment');
        $ok ? $passes++ : $fails++;
    }

    // 5) Complete return for refund readiness.
    $completeResp = $controller->complete($returnId);
    $completePayload = json_decode($completeResp->getContent(), true);
    $ok = assertApiSuccess($completePayload, 'Complete return succeeds');
    $ok ? $passes++ : $fails++;

    // 6) Exchange = return + new purchase linkage endpoint.
    $replacementOrder = Order::query()
        ->where('customer_id', $order->customer_id)
        ->where('id', '!=', $order->id)
        ->where('status', '!=', 'cancelled')
        ->orderByDesc('id')
        ->first();

    if ($replacementOrder) {
        $exchangeReq = Request::create("/api/returns/{$returnId}/exchange", 'POST', [
            'new_order_id' => $replacementOrder->id,
            'notes' => 'Automated exchange link test',
        ]);
        $exchangeResp = $controller->exchange($exchangeReq, $returnId);
        $exchangePayload = json_decode($exchangeResp->getContent(), true);
        $ok = assertApiSuccess($exchangePayload, 'Exchange link endpoint succeeds (return + new purchase)');
        $ok ? $passes++ : $fails++;

        $refreshReturn = ProductReturn::find($returnId);
        $lastHistory = collect($refreshReturn->status_history ?? [])->last();
        $ok = assertTrue(
            ($lastHistory['status'] ?? null) === 'exchange_linked'
            && (int) ($lastHistory['new_order_id'] ?? 0) === (int) $replacementOrder->id,
            'Exchange linkage recorded in return status history'
        );
        $ok ? $passes++ : $fails++;
    } else {
        out('[WARN] Skipped exchange-link success test: no replacement order found for same customer.');
    }

    // 7) Edge case: non-barcode item should be blocked.
    $noBarcodeItem = OrderItem::query()->whereNull('product_barcode_id')->whereNotNull('order_id')->orderByDesc('id')->first();
    if ($noBarcodeItem) {
        $noBarcodeOrder = Order::find($noBarcodeItem->order_id);
        if ($noBarcodeOrder) {
            $blockReq = Request::create('/api/returns', 'POST', [
                'order_id' => $noBarcodeOrder->id,
                'return_reason' => 'changed_mind',
                'return_type' => 'customer_return',
                'items' => [
                    [
                        'order_item_id' => $noBarcodeItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

            $blockResp = $controller->store($blockReq);
            $blockPayload = json_decode($blockResp->getContent(), true);
            $ok = assertTrue(($blockPayload['success'] ?? true) === false, 'Non-barcode order item return is blocked');
            $ok ? $passes++ : $fails++;
        }
    } else {
        out('[WARN] Skipped non-barcode block test: no no-barcode order item found.');
    }

    out('');
    out("=== TEST SUMMARY ===");
    out("Pass: {$passes}");
    out("Fail: {$fails}");

    // Safety rollback for all writes from this script.
    DB::rollBack();
    out('All test writes rolled back.');
} catch (Throwable $e) {
    DB::rollBack();
    out('[FATAL] ' . $e->getMessage());
    out($e->getFile() . ':' . $e->getLine());
    exit(1);
}
