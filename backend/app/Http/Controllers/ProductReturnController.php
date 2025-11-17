<?php

namespace App\Http\Controllers;

use App\Models\ProductReturn;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Employee;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductReturnController extends Controller
{
    /**
     * Get all product returns
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProductReturn::with([
                'order',
                'customer',
                'store',
                'processedBy',
                'approvedBy',
                'refunds'
            ]);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by store
            if ($request->has('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            // Filter by customer
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('return_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('return_date', '<=', $request->to_date);
            }

            // Search by return number or order number
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('order_number', 'like', "%{$search}%");
                        });
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $returns = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $returns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch returns: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific product return
     */
    public function show($id): JsonResponse
    {
        try {
            $return = ProductReturn::with([
                'order.items.product',
                'customer',
                'store',
                'processedBy',
                'approvedBy',
                'rejectedBy',
                'refunds'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $return,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Return not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new product return
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'return_reason' => 'required|in:defective_product,wrong_item,not_as_described,customer_dissatisfaction,size_issue,color_issue,quality_issue,late_delivery,changed_mind,duplicate_order,other',
            'return_type' => 'nullable|in:customer_return,store_return,warehouse_return',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($request->order_id);

            // Validate return items
            $returnItems = [];
            $totalReturnValue = 0;

            foreach ($request->items as $item) {
                $orderItem = OrderItem::findOrFail($item['order_item_id']);

                // Check if item belongs to the order
                if ($orderItem->order_id != $order->id) {
                    throw new \Exception("Item {$item['order_item_id']} does not belong to this order");
                }

                // Check quantity
                $alreadyReturned = $this->getReturnedQuantity($orderItem->id);
                $availableForReturn = $orderItem->quantity - $alreadyReturned;

                if ($item['quantity'] > $availableForReturn) {
                    throw new \Exception("Cannot return {$item['quantity']} units. Only {$availableForReturn} available for return.");
                }

                $itemReturnValue = $item['quantity'] * $orderItem->unit_price;
                $totalReturnValue += $itemReturnValue;

                $returnItems[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'product_batch_id' => $orderItem->product_batch_id,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $orderItem->unit_price,
                    'total_price' => $itemReturnValue,
                    'reason' => $item['reason'] ?? null,
                ];
            }

            // Create return
            $return = ProductReturn::create([
                'return_number' => $this->generateReturnNumber(),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'store_id' => $order->store_id,
                'return_reason' => $request->return_reason,
                'return_type' => $request->return_type,
                'status' => 'pending',
                'return_date' => now(),
                'total_return_value' => $totalReturnValue,
                'total_refund_amount' => $totalReturnValue, // Default to full refund, can be adjusted
                'processing_fee' => 0,
                'customer_notes' => $request->customer_notes,
                'return_items' => $returnItems,
                'attachments' => $request->attachments ?? [],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return created successfully',
                'data' => $return->load(['order', 'customer', 'store']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create return: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update return (for receiving and quality check)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'quality_check_passed' => 'nullable|boolean',
            'quality_check_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'processing_fee' => 'nullable|numeric|min:0',
            'total_refund_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $return = ProductReturn::findOrFail($id);

            if (!in_array($return->status, ['pending', 'approved'])) {
                throw new \Exception('Can only update pending or approved returns');
            }

            $updateData = [];

            // Mark as received
            if ($request->has('quality_check_passed')) {
                $updateData['received_date'] = now();
                $updateData['quality_check_passed'] = $request->quality_check_passed;
            }

            if ($request->has('quality_check_notes')) {
                $updateData['quality_check_notes'] = $request->quality_check_notes;
            }

            if ($request->has('internal_notes')) {
                $updateData['internal_notes'] = $request->internal_notes;
            }

            // Employee can adjust processing fee
            if ($request->has('processing_fee')) {
                $updateData['processing_fee'] = $request->processing_fee;
            }

            // Employee can adjust refund amount (key feature!)
            if ($request->has('total_refund_amount')) {
                if ($request->total_refund_amount > $return->total_return_value) {
                    throw new \Exception('Refund amount cannot exceed return value');
                }
                $updateData['total_refund_amount'] = $request->total_refund_amount;
            }

            $return->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return updated successfully',
                'data' => $return->load(['order', 'customer', 'store']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update return: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a return (employee decision)
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $request->validate([
            'total_refund_amount' => 'nullable|numeric|min:0',
            'processing_fee' => 'nullable|numeric|min:0',
            'internal_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $return = ProductReturn::findOrFail($id);

            if ($return->status !== 'pending') {
                throw new \Exception('Can only approve pending returns');
            }

            if (!$return->quality_check_passed) {
                throw new \Exception('Return must pass quality check before approval');
            }

            $employee = auth()->user();
            if (!$employee) {
                throw new \Exception('Employee authentication required');
            }

            // Employee can set final refund amount at approval
            if ($request->has('total_refund_amount')) {
                if ($request->total_refund_amount > $return->total_return_value) {
                    throw new \Exception('Refund amount cannot exceed return value');
                }
                $return->total_refund_amount = $request->total_refund_amount;
            }

            if ($request->has('processing_fee')) {
                $return->processing_fee = $request->processing_fee;
            }

            if ($request->has('internal_notes')) {
                $return->internal_notes = $request->internal_notes;
            }

            $return->approve($employee);
            $return->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return approved successfully',
                'data' => $return->load(['order', 'customer', 'store', 'approvedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve return: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a return
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $return = ProductReturn::findOrFail($id);

            $employee = auth()->user();
            if (!$employee) {
                throw new \Exception('Employee authentication required');
            }

            $return->reject($employee, $request->rejection_reason);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return rejected successfully',
                'data' => $return->load(['order', 'customer', 'store', 'rejectedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject return: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process a return (restore inventory)
     */
    public function process(Request $request, $id): JsonResponse
    {
        $request->validate([
            'restore_inventory' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $return = ProductReturn::findOrFail($id);

            if ($return->status !== 'approved') {
                throw new \Exception('Can only process approved returns');
            }

            $employee = auth()->user();
            if (!$employee) {
                throw new \Exception('Employee authentication required');
            }

            // Restore inventory if requested
            if ($request->get('restore_inventory', true)) {
                foreach ($return->return_items as $item) {
                    if (isset($item['product_batch_id'])) {
                        $batch = ProductBatch::find($item['product_batch_id']);
                        if ($batch) {
                            $batch->increment('quantity', $item['quantity']);
                            
                            // Log inventory movement
                            \App\Models\ProductMovement::create([
                                'product_id' => $item['product_id'],
                                'product_batch_id' => $item['product_batch_id'],
                                'product_barcode_id' => $batch->barcode_id, // Add barcode_id from batch
                                'store_id' => $return->store_id,
                                'movement_type' => 'return',
                                'quantity' => $item['quantity'],
                                'unit_cost' => $item['unit_price'],
                                'total_cost' => $item['total_price'],
                                'reference_type' => 'return',
                                'reference_id' => $return->id,
                                'notes' => "Product return: {$return->return_number}",
                                'created_by' => $employee->id,
                            ]);
                        }
                    }
                }
            }

            $return->process($employee);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully',
                'data' => $return->load(['order', 'customer', 'store', 'processedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process return: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a return (final step before refund)
     */
    public function complete($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $return = ProductReturn::findOrFail($id);

            if ($return->status !== 'processing') {
                throw new \Exception('Can only complete processing returns');
            }

            $return->complete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return completed successfully. Ready for refund.',
                'data' => $return->load(['order', 'customer', 'store']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete return: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get return statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = ProductReturn::query();

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('return_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('return_date', '<=', $request->to_date);
            }

            // Filter by store
            if ($request->has('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            $stats = [
                'total_returns' => $query->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'processed' => (clone $query)->where('status', 'processed')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'refunded' => (clone $query)->where('status', 'refunded')->count(),
                'total_return_value' => $query->sum('total_return_value'),
                'total_refund_amount' => $query->sum('total_refund_amount'),
                'total_processing_fees' => $query->sum('processing_fee'),
                'by_reason' => ProductReturn::select('return_reason', DB::raw('count(*) as count'))
                    ->groupBy('return_reason')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Generate return number
     */
    private function generateReturnNumber(): string
    {
        $date = now()->format('Ymd');
        $count = ProductReturn::whereDate('created_at', now())->count() + 1;
        return 'RET-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Helper: Get already returned quantity for an order item
     */
    private function getReturnedQuantity($orderItemId): int
    {
        $returns = ProductReturn::whereIn('status', ['approved', 'processed', 'completed', 'refunded'])->get();
        
        $totalReturned = 0;
        foreach ($returns as $return) {
            if ($return->return_items) {
                foreach ($return->return_items as $item) {
                    if (isset($item['order_item_id']) && $item['order_item_id'] == $orderItemId) {
                        $totalReturned += $item['quantity'];
                    }
                }
            }
        }

        return $totalReturned;
    }
}
