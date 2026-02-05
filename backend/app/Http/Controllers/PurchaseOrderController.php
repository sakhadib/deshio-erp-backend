<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Traits\DatabaseAgnosticSearch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    use DatabaseAgnosticSearch;
    /**
     * Create a new purchase order
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'store_id' => 'required|exists:stores,id',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.unit_sell_price' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        // Verify store is a warehouse
        $store = Store::findOrFail($validated['store_id']);
        if (!$store->is_warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Only warehouse can receive products from vendors'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create purchase order
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'vendor_id' => $validated['vendor_id'],
                'store_id' => $validated['store_id'],
                'created_by' => auth()->id(),
                'order_date' => now()->format('Y-m-d'),
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            ]);

            // Create purchase order items
            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'unit_cost' => $itemData['unit_cost'],
                    'unit_sell_price' => $itemData['unit_sell_price'] ?? $product->price,
                    'tax_amount' => $itemData['tax_amount'] ?? 0,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Calculate totals
            $po->calculateTotals();
            $po->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $po->load('items', 'vendor', 'store')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all purchase orders with filters
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'store', 'createdBy']);

        // Filters
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search')) {
            $this->whereLike($query, 'po_number', $request->search);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $purchaseOrders = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $purchaseOrders
        ]);
    }

    /**
     * Get single purchase order with details
     */
    public function show($id)
    {
        $po = PurchaseOrder::with([
            'vendor',
            'store',
            'createdBy',
            'approvedBy',
            'receivedBy',
            'items.product',
            'items.productBatch',
            'payments.vendorPayment'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $po
        ]);
    }

    /**
     * Update purchase order (only in draft status)
     */
    public function update(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Can only update draft purchase orders'
            ], 422);
        }

        $validated = $request->validate([
            'vendor_id' => 'sometimes|exists:vendors,id',
            'expected_delivery_date' => 'nullable|date',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $po->update($validated);
        $po->calculateTotals();
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order updated successfully',
            'data' => $po->load('items', 'vendor', 'store')
        ]);
    }

    /**
     * Add item to purchase order
     */
    public function addItem(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Can only add items to draft purchase orders'
            ], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_ordered' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'unit_sell_price' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity_ordered' => $validated['quantity_ordered'],
            'unit_cost' => $validated['unit_cost'],
            'unit_sell_price' => $validated['unit_sell_price'] ?? $product->price,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        $po->calculateTotals();
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Item added to purchase order',
            'data' => $item
        ]);
    }

    /**
     * Update item in purchase order
     */
    public function updateItem(Request $request, $id, $itemId)
    {
        $po = PurchaseOrder::findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $id)
            ->findOrFail($itemId);

        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Can only update items in draft purchase orders'
            ], 422);
        }

        $validated = $request->validate([
            'quantity_ordered' => 'sometimes|integer|min:1',
            'unit_cost' => 'sometimes|numeric|min:0',
            'unit_sell_price' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $item->update($validated);
        $po->calculateTotals();
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item
        ]);
    }

    /**
     * Remove item from purchase order
     */
    public function removeItem($id, $itemId)
    {
        $po = PurchaseOrder::findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $id)
            ->findOrFail($itemId);

        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Can only remove items from draft purchase orders'
            ], 422);
        }

        $item->delete();
        $po->calculateTotals();
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully'
        ]);
    }

    /**
     * Approve purchase order
     */
    public function approve($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Can only approve draft purchase orders'
            ], 422);
        }

        $po->status = 'approved';
        $po->approved_by = auth()->id();
        $po->approved_at = now();
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved successfully',
            'data' => $po
        ]);
    }

    /**
     * Receive purchase order (create product batches)
     */
    public function receive(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if (!in_array($po->status, ['approved', 'partially_received'])) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order must be approved before receiving'
            ], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.batch_number' => 'nullable|string',
            'items.*.manufactured_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        try {
            $po->markAsReceived($validated['items']);
            
            // Update received_by and received_at
            $po->received_by = auth()->id();
            $po->received_at = now();
            $po->save();

            return response()->json([
                'success' => true,
                'message' => 'Products received successfully',
                'data' => $po->load('items.productBatch')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancel(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel received purchase order'
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string'
        ]);

        $po->cancel($validated['reason'] ?? null);
        $po->cancelled_at = now();
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order cancelled successfully'
        ]);
    }

    /**
     * Get purchase order statistics
     */
    public function statistics(Request $request)
    {
        $query = PurchaseOrder::query();

        // Date range filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $stats = [
            'total_purchase_orders' => $query->count(),
            'by_status' => (clone $query)->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'by_payment_status' => (clone $query)->selectRaw('payment_status, COUNT(*) as count')
                ->groupBy('payment_status')
                ->get(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'total_paid' => (clone $query)->sum('paid_amount'),
            'total_outstanding' => (clone $query)->sum('outstanding_amount'),
            'overdue_orders' => PurchaseOrder::overdue()->count(),
            'recent_orders' => PurchaseOrder::with('vendor')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Hard delete a purchase order (permanently removes from database)
     * 
     * DELETE /api/purchase-orders/{id}
     * 
     * Safety checks:
     * - Cannot delete if any payments have been made
     * - Cannot delete if any items have been received (batches created)
     * - Cannot delete if status is 'received' or 'partially_received'
     * 
     * Query params:
     * - force=true : Skip confirmation (for API calls that already confirmed)
     */
    public function destroy(Request $request, $id)
    {
        $po = PurchaseOrder::with(['items.productBatch', 'payments'])->findOrFail($id);

        // Safety Check 1: Cannot delete received POs
        if (in_array($po->status, ['received', 'partially_received'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete purchase order that has received items. Status: ' . $po->status,
                'error_code' => 'PO_RECEIVED'
            ], 422);
        }

        // Safety Check 2: Cannot delete if payments exist
        if ($po->payments()->exists()) {
            $paymentCount = $po->payments()->count();
            $totalPaid = $po->paid_amount;
            return response()->json([
                'success' => false,
                'message' => "Cannot delete purchase order with existing payments. Found {$paymentCount} payment(s) totaling {$totalPaid}",
                'error_code' => 'PO_HAS_PAYMENTS',
                'data' => [
                    'payment_count' => $paymentCount,
                    'total_paid' => $totalPaid
                ]
            ], 422);
        }

        // Safety Check 3: Cannot delete if any items have product batches (received stock)
        $itemsWithBatches = $po->items->filter(function ($item) {
            return $item->productBatch !== null || $item->quantity_received > 0;
        });

        if ($itemsWithBatches->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete purchase order with received inventory. Some items have been received into stock.',
                'error_code' => 'PO_ITEMS_RECEIVED',
                'data' => [
                    'received_items' => $itemsWithBatches->map(function ($item) {
                        return [
                            'item_id' => $item->id,
                            'product_name' => $item->product_name,
                            'quantity_received' => $item->quantity_received
                        ];
                    })->values()
                ]
            ], 422);
        }

        // Get info before deletion for response
        $deletedInfo = [
            'id' => $po->id,
            'po_number' => $po->po_number,
            'vendor_name' => $po->vendor?->name,
            'total_amount' => $po->total_amount,
            'status' => $po->status,
            'items_count' => $po->items->count(),
        ];

        DB::beginTransaction();
        try {
            // Delete all items first
            $po->items()->delete();
            
            // Delete the purchase order
            $po->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Purchase order {$deletedInfo['po_number']} has been permanently deleted",
                'data' => $deletedInfo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a purchase order can be safely deleted
     * 
     * GET /api/purchase-orders/{id}/can-delete
     * 
     * Returns deletion eligibility and any blocking reasons
     */
    public function canDelete($id)
    {
        $po = PurchaseOrder::with(['items.productBatch', 'payments', 'vendor'])->findOrFail($id);

        $blockers = [];
        $canDelete = true;

        // Check status
        if (in_array($po->status, ['received', 'partially_received'])) {
            $canDelete = false;
            $blockers[] = [
                'type' => 'status',
                'message' => "Purchase order has status '{$po->status}' - items have been received"
            ];
        }

        // Check payments
        if ($po->payments()->exists()) {
            $canDelete = false;
            $paymentCount = $po->payments()->count();
            $blockers[] = [
                'type' => 'payments',
                'message' => "Purchase order has {$paymentCount} payment(s) totaling {$po->paid_amount}",
                'details' => [
                    'payment_count' => $paymentCount,
                    'total_paid' => $po->paid_amount
                ]
            ];
        }

        // Check received items
        $receivedItems = $po->items->filter(fn($item) => $item->quantity_received > 0);
        if ($receivedItems->count() > 0) {
            $canDelete = false;
            $blockers[] = [
                'type' => 'received_items',
                'message' => "{$receivedItems->count()} item(s) have been received into inventory",
                'details' => $receivedItems->map(fn($item) => [
                    'product_name' => $item->product_name,
                    'quantity_received' => $item->quantity_received
                ])->values()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'can_delete' => $canDelete,
                'po_number' => $po->po_number,
                'vendor_name' => $po->vendor?->name,
                'status' => $po->status,
                'total_amount' => $po->total_amount,
                'items_count' => $po->items->count(),
                'blockers' => $blockers
            ]
        ]);
    }

    /**
     * Export single purchase order as PDF
     */
    public function exportPdf($id)
    {
        $po = PurchaseOrder::with([
            'vendor',
            'store',
            'createdBy',
            'approvedBy',
            'receivedBy',
            'items.product',
            'payments.vendorPayment'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.purchase-order', ['po' => $po]);
        $pdf->setPaper('a4', 'portrait');

        $filename = "PO-{$po->po_number}-" . now()->format('Ymd') . ".pdf";

        // Check if inline view or download
        if (request('inline', false)) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Export purchase orders summary report as PDF
     */
    public function exportSummaryPdf(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'store_id' => 'nullable|exists:stores,id',
            'status' => 'nullable|in:draft,approved,partially_received,received,cancelled',
            'payment_status' => 'nullable|in:unpaid,partial,paid',
        ]);

        $query = PurchaseOrder::with(['vendor', 'store'])
            ->withCount('items');

        // Apply filters
        $filters = [];

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
            $filters['from_date'] = $request->from_date;
        }

        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
            $filters['to_date'] = $request->to_date;
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
            $filters['vendor_id'] = $request->vendor_id;
            $vendor = \App\Models\Vendor::find($request->vendor_id);
            $filters['vendor_name'] = $vendor?->name;
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
            $filters['store_id'] = $request->store_id;
            $store = Store::find($request->store_id);
            $filters['store_name'] = $store?->name;
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
            $filters['status'] = $request->status;
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
            $filters['payment_status'] = $request->payment_status;
        }

        $purchaseOrders = $query->orderBy('order_date', 'desc')->get();

        // Calculate summary
        $summary = [
            'total_orders' => $purchaseOrders->count(),
            'total_amount' => $purchaseOrders->sum('total_amount'),
            'total_paid' => $purchaseOrders->sum('paid_amount'),
            'total_outstanding' => $purchaseOrders->sum('outstanding_amount'),
            'total_items' => $purchaseOrders->sum('items_count'),
        ];

        // Status breakdown
        $statusBreakdown = $purchaseOrders->groupBy('status')->map(function ($group, $status) {
            return (object)[
                'status' => $status,
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ];
        })->values();

        // Vendor breakdown (top vendors)
        $vendorBreakdown = $purchaseOrders->groupBy('vendor_id')->map(function ($group) {
            $vendor = $group->first()->vendor;
            return (object)[
                'vendor_name' => $vendor?->name ?? 'Unknown',
                'order_count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'paid_amount' => $group->sum('paid_amount'),
                'outstanding' => $group->sum('outstanding_amount'),
            ];
        })->sortByDesc('total_amount')->values();

        $pdf = Pdf::loadView('pdf.purchase-orders-summary', [
            'purchaseOrders' => $purchaseOrders,
            'summary' => $summary,
            'filters' => $filters,
            'statusBreakdown' => $statusBreakdown,
            'vendorBreakdown' => $vendorBreakdown,
        ]);
        $pdf->setPaper('a4', 'landscape');

        $filename = "PO-Summary-Report-" . now()->format('Ymd-His') . ".pdf";

        if ($request->boolean('inline', false)) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }
}
