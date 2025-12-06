<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Traits\DatabaseAgnosticSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use DatabaseAgnosticSearch;
    /**
     * List all orders with filters
     * 
     * GET /api/orders?order_type=counter&status=pending&payment_status=partially_paid
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'customer',
            'store', // Nullable - E-commerce orders have no store until manually assigned
            'items.product',
            'items.batch',
            'payments.paymentMethod',
        ]);

        // Filter by order type (counter, social_commerce, ecommerce)
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by salesman/employee
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        // Search by order number or customer name
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $this->whereLike($q, 'order_number', $request->search);
                $q->orWhereHas('customer', function ($customerQuery) use ($request) {
                    $this->whereLike($customerQuery, 'name', $request->search);
                    $this->orWhereLike($customerQuery, 'phone', $request->search);
                });
            });
        }

        // Filter overdue payments
        if ($request->boolean('overdue')) {
            $query->where('payment_status', 'overdue');
        }

        // Filter installment orders
        if ($request->boolean('installment_only')) {
            $query->where('is_installment_payment', true);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->input('per_page', 20));

        $formattedOrders = [];
        foreach ($orders as $order) {
            $formattedOrders[] = $this->formatOrderResponse($order);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $orders->currentPage(),
                'data' => $formattedOrders,
                'first_page_url' => $orders->url(1),
                'from' => $orders->firstItem(),
                'last_page' => $orders->lastPage(),
                'last_page_url' => $orders->url($orders->lastPage()),
                'next_page_url' => $orders->nextPageUrl(),
                'path' => $orders->path(),
                'per_page' => $orders->perPage(),
                'prev_page_url' => $orders->previousPageUrl(),
                'to' => $orders->lastItem(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Get specific order details
     * 
     * GET /api/orders/{id}
     */
    public function show($id)
    {
        $order = Order::with([
            'customer',
            'store',
            'items.product',
            'items.batch',
            'items.barcode',
            'payments.paymentMethod',
            'payments.processedBy',
            'payments.paymentSplits.paymentMethod',
            'payments.cashDenominations',
        ])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOrderResponse($order, true)
        ]);
    }

    /**
     * Create new order
     * Handles all 3 sales channels: counter, social_commerce, ecommerce
     * 
     * POST /api/orders
     * Body: {
     *   "order_type": "counter|social_commerce|ecommerce",
     *   "customer_id": 1,  // Or create on-the-fly
     *   "customer": {...},  // If customer doesn't exist
     *   "store_id": 1,
     *   "items": [
     *     {
     *       "product_id": 1,
     *       "batch_id": 1,  // Specific batch to sell from
     *       "quantity": 2,
     *       "unit_price": 750.00,
     *       "discount_amount": 50.00
     *     }
     *   ],
     *   "discount_amount": 100.00,
     *   "shipping_amount": 50.00,
     *   "notes": "Customer wants delivery tomorrow",
     *   "shipping_address": {...},
     *   "payment": {  // Optional: immediate payment
     *     "payment_method_id": 1,
     *     "amount": 1000.00,
     *     "payment_type": "partial|full|installment"
     *   },
     *   "installment_plan": {  // Optional: setup installments
     *     "total_installments": 3,
     *     "installment_amount": 500.00,
     *     "start_date": "2024-12-01"
     *   }
     * }
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_type' => 'required|in:counter,social_commerce,ecommerce',
            'customer_id' => 'nullable|exists:customers,id',
            'customer' => 'nullable|array',  // Made optional - will use walk-in customer if not provided
            'customer.name' => 'required_with:customer|string',
            'customer.phone' => 'required_with:customer|string',
            'customer.email' => 'nullable|email',
            'customer.address' => 'nullable|string',
            'store_id' => 'required|exists:stores,id',
            'salesman_id' => 'nullable|exists:employees,id',  // Manual salesman entry for POS
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'required|exists:product_batches,id',
            'items.*.barcode' => 'nullable|string|exists:product_barcodes,barcode',  // Optional barcode for tracking
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'shipping_address' => 'nullable|array',
            'payment' => 'nullable|array',
            'payment.payment_method_id' => 'required_with:payment|exists:payment_methods,id',
            'payment.amount' => 'required_with:payment|numeric|min:0.01',
            'payment.payment_type' => 'nullable|in:full,partial,installment,advance',
            'installment_plan' => 'nullable|array',
            'installment_plan.total_installments' => 'required_with:installment_plan|integer|min:2',
            'installment_plan.installment_amount' => 'required_with:installment_plan|numeric|min:0.01',
            'installment_plan.start_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get or create customer
            if ($request->filled('customer_id')) {
                $customer = Customer::findOrFail($request->customer_id);
            } elseif ($request->filled('customer')) {
                // Create customer on-the-fly based on order type
                $customerData = $request->customer;
                $customerData['created_by'] = Auth::id();
                
                // Check if customer exists by phone
                $existing = Customer::where('phone', $customerData['phone'])->first();
                if ($existing) {
                    $customer = $existing;
                } else {
                    if ($request->order_type === 'counter') {
                        $customer = Customer::create([
                            'name' => $customerData['name'],
                            'phone' => $customerData['phone'],
                            'email' => $customerData['email'] ?? null,
                            'address' => $customerData['address'] ?? null,
                            'customer_type' => 'counter',
                            'status' => 'active',
                            'created_by' => Auth::id(),
                        ]);
                    } elseif ($request->order_type === 'social_commerce') {
                        $customer = Customer::create([
                            'name' => $customerData['name'],
                            'phone' => $customerData['phone'],
                            'email' => $customerData['email'] ?? null,
                            'address' => $customerData['address'] ?? null,
                            'customer_type' => 'social_commerce',
                            'status' => 'active',
                            'created_by' => Auth::id(),
                        ]);
                    } else {
                        $customer = Customer::create([
                            'name' => $customerData['name'],
                            'phone' => $customerData['phone'],
                            'email' => $customerData['email'] ?? null,
                            'address' => $customerData['address'] ?? null,
                            'customer_type' => 'ecommerce',
                            'status' => 'active',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            } else {
                // No customer provided - use or create walk-in customer for counter orders
                if ($request->order_type === 'counter') {
                    $customer = Customer::firstOrCreate(
                        ['phone' => 'WALK-IN'],
                        [
                            'name' => 'Walk-in Customer',
                            'customer_type' => 'counter',
                            'status' => 'active',
                            'created_by' => Auth::id(),
                        ]
                    );
                } else {
                    // For non-counter orders, customer is required
                    throw new \Exception('Customer information is required for ' . $request->order_type . ' orders');
                }
            }

            // Get salesman (employee creating the order)
            // For POS/counter: allow manual salesman_id entry (manager creating order for another salesman)
            // For social/ecommerce: use authenticated employee
            if ($request->filled('salesman_id')) {
                $salesmanId = $request->salesman_id;
                $salesman = Employee::findOrFail($salesmanId);
            } else {
                $salesmanId = Auth::id();
                $salesman = Employee::find($salesmanId);
            }

            // Determine fulfillment status based on order type
            // Counter orders: immediate fulfillment (barcode scanned at POS)
            // Social/Ecommerce: deferred fulfillment (warehouse scans barcodes later)
            $fulfillmentStatus = null;
            if (in_array($request->order_type, ['social_commerce', 'ecommerce'])) {
                $fulfillmentStatus = 'pending_fulfillment';
            }

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'store_id' => $request->store_id,
                'order_type' => $request->order_type,
                'status' => 'pending',
                'payment_status' => 'pending',
                'fulfillment_status' => $fulfillmentStatus,
                'discount_amount' => $request->discount_amount ?? 0,
                'shipping_amount' => $request->shipping_amount ?? 0,
                'notes' => $request->notes,
                'shipping_address' => $request->shipping_address,
                'created_by' => $salesmanId,  // Track salesman (manual or auth)
                'order_date' => now(),
            ]);

            // Add items
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $batch = ProductBatch::findOrFail($itemData['batch_id']);

                // Validate stock availability
                if ($batch->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$batch->quantity}");
                }

                // Validate batch belongs to the store
                if ($batch->store_id != $request->store_id) {
                    throw new \Exception("Product batch not available at this store");
                }

                // Handle barcode if provided (optional for backward compatibility)
                $barcodeId = null;
                if (!empty($itemData['barcode'])) {
                    $barcode = \App\Models\ProductBarcode::where('barcode', $itemData['barcode'])
                        ->where('product_id', $product->id)
                        ->where('batch_id', $batch->id)
                        ->first();
                    
                    if (!$barcode) {
                        throw new \Exception("Barcode {$itemData['barcode']} not found for product {$product->name}");
                    }
                    
                    if (!$barcode->is_active) {
                        throw new \Exception("Barcode {$itemData['barcode']} is not active");
                    }
                    
                    if ($barcode->is_defective) {
                        throw new \Exception("Barcode {$itemData['barcode']} is marked as defective");
                    }
                    
                    $barcodeId = $barcode->id;
                }
                
                // Debug: Log barcode capture
                \Log::info('Order item barcode capture', [
                    'barcode_value' => $itemData['barcode'] ?? 'NOT_PROVIDED',
                    'barcode_id' => $barcodeId,
                    'product_id' => $product->id,
                    'batch_id' => $batch->id
                ]);

                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $discount = $itemData['discount_amount'] ?? 0;
                $tax = $itemData['tax_amount'] ?? 0;
                
                $itemSubtotal = $quantity * $unitPrice;
                $itemTotal = $itemSubtotal - $discount + $tax;

                // Calculate COGS from batch cost price
                $cogs = round(($batch->cost_price ?? 0) * $quantity, 2);
                
                // Log COGS during order creation for debugging
                \Log::info('Order Item COGS at Creation', [
                    'product_name' => $product->name,
                    'batch_id' => $batch->id,
                    'batch_cost_price' => $batch->cost_price,
                    'quantity' => $quantity,
                    'calculated_cogs' => $cogs,
                ]);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_batch_id' => $batch->id,
                    'product_barcode_id' => $barcodeId,  // NEW: Store barcode if provided
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'cogs' => $cogs,
                    'total_amount' => $itemTotal,
                ]);

                $subtotal += $itemSubtotal;
                $taxTotal += $tax;
            }

            // Calculate order totals
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal - ($request->discount_amount ?? 0) + ($request->shipping_amount ?? 0),
                'outstanding_amount' => $subtotal + $taxTotal - ($request->discount_amount ?? 0) + ($request->shipping_amount ?? 0),
            ]);

            // Setup installment plan if requested
            if ($request->filled('installment_plan')) {
                $plan = $request->installment_plan;
                $order->setupInstallmentPlan(
                    $plan['total_installments'],
                    $plan['installment_amount'],
                    $plan['start_date'] ?? null
                );
            }

            // Process immediate payment if provided
            if ($request->filled('payment')) {
                $paymentMethod = PaymentMethod::findOrFail($request->payment['payment_method_id']);
                $payment = $order->addPayment(
                    $paymentMethod,
                    $request->payment['amount'],
                    [],
                    $salesman
                );

                $payment->update([
                    'payment_type' => $request->payment['payment_type'] ?? 'partial',
                ]);

                // Update order payment status
                $order->updatePaymentStatus();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $this->formatOrderResponse($order->fresh([
                    'customer',
                    'store',
                    'items.product',
                    'items.batch',
                    'payments.paymentMethod'
                ]), true)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to existing order (before completion)
     * 
     * UPDATED: Now requires barcode scanning for individual unit tracking
     * 
     * POST /api/orders/{id}/items
     * Body: {
     *   "barcode": "789012345023"  // Scan individual unit barcode
     *   OR
     *   "barcodes": ["789012345023", "789012345024"]  // Multiple units
     * }
     */
    public function addItem(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Can only add items to pending orders
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to ' . $order->status . ' orders'
            ], 422);
        }

        // NEW: Support both single barcode and array of barcodes
        $validator = Validator::make($request->all(), [
            'barcode' => 'required_without:barcodes|string|exists:product_barcodes,barcode',
            'barcodes' => 'required_without:barcode|array|min:1',
            'barcodes.*' => 'string|exists:product_barcodes,barcode',
            'unit_price' => 'nullable|numeric|min:0',  // Optional, use batch price if not provided
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Normalize to array
        $barcodesToAdd = $request->has('barcodes') 
            ? $request->barcodes 
            : [$request->barcode];

        DB::beginTransaction();
        try {
            $addedItems = [];
            
            foreach ($barcodesToAdd as $barcodeValue) {
                $barcode = \App\Models\ProductBarcode::where('barcode', $barcodeValue)
                    ->with(['product', 'batch'])
                    ->first();

                if (!$barcode) {
                    throw new \Exception("Barcode {$barcodeValue} not found");
                }

                // Validate barcode is active and not defective
                if (!$barcode->is_active) {
                    throw new \Exception("Barcode {$barcodeValue} is not available (inactive)");
                }

                if ($barcode->is_defective) {
                    throw new \Exception("Barcode {$barcodeValue} is marked as defective");
                }

                // Validate batch exists and has stock
                if (!$barcode->batch) {
                    throw new \Exception("Barcode {$barcodeValue} is not associated with any batch");
                }

                $batch = $barcode->batch;
                $product = $barcode->product;

                // Validate batch has stock
                if ($batch->quantity < 1) {
                    throw new \Exception("Product batch {$batch->batch_number} has no stock available");
                }

                // Validate store
                if ($batch->store_id != $order->store_id) {
                    throw new \Exception("Product from batch {$batch->batch_number} not available at this store");
                }

                // Use provided price or batch price
                $unitPrice = $request->unit_price ?? $batch->sell_price;

                // Create order item with barcode tracking
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_batch_id' => $batch->id,
                    'product_barcode_id' => $barcode->id,  // NEW: Track specific barcode
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => 1,  // Always 1 per barcode
                    'unit_price' => $unitPrice,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'tax_amount' => $request->tax_amount ?? 0,
                    'cogs' => round(($batch->cost_price ?? 0) * 1, 2),
                ]);

                // Calculate total for this item
                $orderItem->total_amount = ($unitPrice - ($request->discount_amount ?? 0)) + ($request->tax_amount ?? 0);
                $orderItem->save();

                $addedItems[] = $orderItem;
            }

            // Recalculate order totals
            $order->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($addedItems) . ' item(s) added successfully',
                'data' => [
                    'item' => [
                        'id' => $orderItem->id,
                        'product_name' => $orderItem->product_name,
                        'quantity' => $orderItem->quantity,
                        'unit_price' => number_format((float)$orderItem->unit_price, 2),
                        'total' => number_format((float)$orderItem->total_amount, 2),
                    ],
                    'order_totals' => [
                        'subtotal' => number_format((float)$order->fresh()->subtotal, 2),
                        'total_amount' => number_format((float)$order->fresh()->total_amount, 2),
                        'outstanding_amount' => number_format((float)$order->fresh()->outstanding_amount, 2),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update item quantity/price
     * 
     * PUT /api/orders/{orderId}/items/{itemId}
     */
    public function updateItem(Request $request, $orderId, $itemId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update items in ' . $order->status . ' orders'
            ], 422);
        }

        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->filled('quantity')) {
                // Validate stock
                if ($item->batch->quantity < $request->quantity) {
                    throw new \Exception("Insufficient stock. Available: {$item->batch->quantity}");
                }
                $item->updateQuantity($request->quantity);
            }

            if ($request->filled('unit_price')) {
                $item->unit_price = $request->unit_price;
            }

            if ($request->filled('discount_amount')) {
                $item->applyDiscount($request->discount_amount);
            }

            $item->save();
            $order->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'data' => [
                    'item' => [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'unit_price' => number_format((float)$item->unit_price, 2),
                        'total' => number_format((float)$item->total_amount, 2),
                    ],
                    'order_totals' => [
                        'total_amount' => number_format((float)$order->fresh()->total_amount, 2),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove item from order
     * 
     * DELETE /api/orders/{orderId}/items/{itemId}
     */
    public function removeItem($orderId, $itemId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove items from ' . $order->status . ' orders'
            ], 422);
        }

        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $item->delete();
            $order->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
                'data' => [
                    'order_totals' => [
                        'total_amount' => number_format((float)$order->fresh()->total_amount, 2),
                        'outstanding_amount' => number_format((float)$order->fresh()->outstanding_amount, 2),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Complete order and reduce inventory
     * 
     * UPDATED: Handles both barcode-tracked and non-barcode orders
     * For barcode-tracked items: marks individual barcodes as sold
     * For non-barcode items: just reduces batch quantity
     * This is called after payment is complete or for credit sales
     * 
     * NEW: Validates fulfillment requirement for social/ecommerce orders
     * 
     * PATCH /api/orders/{id}/complete
     */
    public function complete($id)
    {
        $order = Order::with(['items.batch', 'items.barcode'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be completed'
            ], 422);
        }

        // Validate fulfillment requirement for social commerce and ecommerce
        if ($order->needsFulfillment() && !$order->isFulfilled()) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be fulfilled before completion. Please scan barcodes at warehouse first.',
                'hint' => 'Call POST /api/orders/' . $order->id . '/fulfill with barcode scans'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Reduce inventory for each item
            foreach ($order->items as $item) {
                $batch = $item->batch;

                if (!$batch) {
                    throw new \Exception("Batch not found for item {$item->product_name}");
                }

                if ($batch->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$item->product_name}. Available: {$batch->quantity}");
                }

                // Handle barcode-tracked items (check if barcode exists and is not null)
                if ($item->product_barcode_id && $item->barcode) {
                    $barcode = $item->barcode;
                    
                    // Validate barcode is still active
                    if (!$barcode->is_active) {
                        throw new \Exception("Barcode {$barcode->barcode} for {$item->product_name} is no longer active.");
                    }

                    // Mark barcode as sold and update location tracking
                    $barcode->update([
                        'is_active' => false,
                        'current_status' => 'sold',
                        'location_updated_at' => now(),
                        'location_metadata' => [
                            'sold_via' => 'order',
                            'order_number' => $order->order_number,
                            'sale_date' => now()->toISOString(),
                            'sold_by' => auth()->id(),
                        ]
                    ]);

                    // Log barcode sale
                    $note = sprintf(
                        "[%s] Sold 1 unit (Barcode: %s) via Order #%s",
                        now()->format('Y-m-d H:i:s'),
                        $barcode->barcode,
                        $order->order_number
                    );
                } else {
                    // Log non-barcode sale
                    $note = sprintf(
                        "[%s] Sold %d unit(s) (No barcode tracking) via Order #%s",
                        now()->format('Y-m-d H:i:s'),
                        $item->quantity,
                        $order->order_number
                    );
                }

                // Ensure COGS is stored/updated at the time of completion
                $calculatedCogs = ($batch ? ($batch->cost_price ?? 0) * $item->quantity : 0);
                
                // Log COGS calculation for debugging
                \Log::info('COGS Calculation', [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'batch_id' => $batch ? $batch->id : null,
                    'batch_cost_price' => $batch ? $batch->cost_price : null,
                    'quantity' => $item->quantity,
                    'calculated_cogs' => round($calculatedCogs, 2),
                    'existing_cogs' => $item->cogs,
                ]);
                
                $item->update(['cogs' => round($calculatedCogs, 2)]);

                // Reduce batch quantity
                $batch->removeStock($item->quantity);
                
                $batch->update([
                    'notes' => ($batch->notes ? $batch->notes . "\n" : '') . $note
                ]);
            }

            // Update order status to confirmed (delivered will be set when shipment is delivered)
            $order->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Update customer purchase stats
            $order->customer->recordPurchase($order->total_amount, $order->id);

            // Create COGS accounting transactions
            // This posts the Cost of Goods Sold to the accounting system:
            // - Debit: COGS (Expense) - increases expense
            // - Credit: Inventory (Asset) - decreases inventory value
            try {
                $orderWithItems = $order->fresh(['items']);
                Transaction::createFromOrderCOGS($orderWithItems);
                $totalCogs = collect($orderWithItems->items)->sum('cogs');
                \Log::info('COGS Transactions Created', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_cogs' => $totalCogs,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create COGS transactions', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't fail the order completion if COGS transaction fails
                // Just log the error for manual correction
            }

            DB::commit();

            $message = 'Order completed successfully. Inventory updated.';
            $items = collect($order->items);
            $trackedCount = $items->filter(fn($item) => $item->product_barcode_id && $item->barcode)->count();
            $untrackedCount = $items->count() - $trackedCount;
            
            if ($trackedCount > 0) {
                $message .= " {$trackedCount} item(s) tracked with barcodes.";
            }
            if ($untrackedCount > 0) {
                $message .= " {$untrackedCount} item(s) completed without barcode tracking.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $this->formatOrderResponse($order->fresh([
                    'customer',
                    'store',
                    'items.product',
                    'items.batch',
                    'payments'
                ]), true)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel order
     * 
     * PATCH /api/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed orders. Use returns instead.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => ($order->notes ? $order->notes . "\n" : '') . 'Cancelled: ' . ($request->reason ?? 'No reason provided'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $this->formatOrderResponse($order->fresh(), true)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get order statistics
     * 
     * GET /api/orders/statistics
     */
    public function getStatistics(Request $request)
    {
        $query = Order::query();

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by salesman
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $stats = [
            'total_orders' => $query->count(),
            'by_type' => [
                'counter' => (clone $query)->where('order_type', 'counter')->count(),
                'social_commerce' => (clone $query)->where('order_type', 'social_commerce')->count(),
                'ecommerce' => (clone $query)->where('order_type', 'ecommerce')->count(),
            ],
            'by_status' => [
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            ],
            'by_payment_status' => [
                'pending' => (clone $query)->where('payment_status', 'pending')->count(),
                'partially_paid' => (clone $query)->where('payment_status', 'partially_paid')->count(),
                'paid' => (clone $query)->where('payment_status', 'paid')->count(),
                'overdue' => (clone $query)->where('payment_status', 'overdue')->count(),
            ],
            'total_revenue' => (clone $query)->where('status', 'completed')->sum('total_amount'),
            'total_outstanding' => (clone $query)->whereIn('status', ['pending', 'confirmed', 'completed'])->sum('outstanding_amount'),
            'installment_orders' => (clone $query)->where('is_installment_payment', true)->count(),
        ];

        // Top salesmen
        if (!$request->filled('created_by')) {
            $stats['top_salesmen'] = Order::select('created_by')
                ->selectRaw('COUNT(*) as order_count')
                ->selectRaw('SUM(total_amount) as total_sales')
                ->with('createdBy:id,name')
                ->groupBy('created_by')
                ->orderByDesc('total_sales')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'employee_id' => $item->created_by,
                        'employee_name' => $item->createdBy->name ?? 'Unknown',
                        'order_count' => $item->order_count,
                        'total_sales' => number_format((float)$item->total_sales, 2),
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Helper function to format order response
     */
    private function formatOrderResponse(Order $order, $detailed = false)
    {
        // Calculate COGS and gross margin for all responses
        $totalCogs = $order->items->sum(function ($i) {
            return $i->cogs ?? (($i->batch?->cost_price ?? 0) * $i->quantity);
        });
        $grossMargin = (float)$order->total_amount - $totalCogs;

        $response = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'order_type_label' => match($order->order_type) {
                'counter' => 'In-Person Sale',
                'social_commerce' => 'Social Commerce',
                'ecommerce' => 'E-commerce',
                default => $order->order_type,
            },
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'customer' => [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
                'email' => $order->customer->email,
                'customer_code' => $order->customer->customer_code,
            ],
            'store' => [
                'id' => $order->store->id,
                'name' => $order->store->name,
            ],
            'salesman' => $order->createdBy ? [
                'id' => $order->createdBy->id,
                'name' => $order->createdBy->name,
            ] : null,
            'subtotal' => number_format((float)$order->subtotal, 2),
            'tax_amount' => number_format((float)$order->tax_amount, 2),
            'discount_amount' => number_format((float)$order->discount_amount, 2),
            'shipping_amount' => number_format((float)$order->shipping_amount, 2),
            'total_amount' => number_format((float)$order->total_amount, 2),
            'paid_amount' => number_format((float)$order->paid_amount, 2),
            'outstanding_amount' => number_format((float)$order->outstanding_amount, 2),
            'total_cogs' => number_format($totalCogs, 2),
            'gross_margin' => number_format($grossMargin, 2),
            'gross_margin_percentage' => $order->total_amount > 0 ? number_format(($grossMargin / (float)$order->total_amount) * 100, 2) : '0.00',
            'is_installment' => $order->is_installment_payment,
            'order_date' => $order->order_date->format('Y-m-d H:i:s'),
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
        ];

        if ($detailed) {
            $response['items'] = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'batch_id' => $item->product_batch_id,
                    'batch_number' => $item->batch?->batch_number,
                    'barcode_id' => $item->product_barcode_id,
                    'barcode' => $item->barcode?->barcode,
                    'quantity' => $item->quantity,
                    'unit_price' => number_format((float)$item->unit_price, 2),
                    'discount_amount' => number_format((float)$item->discount_amount, 2),
                    'tax_amount' => number_format((float)$item->tax_amount, 2),
                    'total_amount' => number_format((float)$item->total_amount, 2),
                    'cogs' => number_format((float)($item->cogs ?? (($item->batch?->cost_price ?? 0) * $item->quantity)), 2),
                    'item_gross_margin' => number_format((float)$item->total_amount - (float)($item->cogs ?? (($item->batch?->cost_price ?? 0) * $item->quantity)), 2),
                ];
            });

            $response['payments'] = $order->payments->map(function ($payment) {
                $paymentData = [
                    'id' => $payment->id,
                    'amount' => number_format((float)$payment->amount, 2),
                    'payment_method' => $payment->payment_method_name,
                    'payment_type' => $payment->payment_type,
                    'status' => $payment->status,
                    'processed_by' => $payment->processedBy?->name,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];

                // Include split details if it's a split payment
                if ($payment->isSplitPayment()) {
                    $paymentData['splits'] = $payment->paymentSplits->map(function ($split) {
                        return [
                            'payment_method' => $split->paymentMethod->name,
                            'amount' => number_format((float)$split->amount, 2),
                            'status' => $split->status,
                        ];
                    });
                }

                return $paymentData;
            });

            if ($order->is_installment_payment) {
                $response['installment_info'] = [
                    'total_installments' => $order->total_installments,
                    'paid_installments' => $order->paid_installments,
                    'installment_amount' => number_format((float)$order->installment_amount, 2),
                    'next_payment_due' => $order->next_payment_due ? date('Y-m-d', strtotime($order->next_payment_due)) : null,
                    'is_overdue' => $order->isPaymentOverdue(),
                    'days_overdue' => $order->getDaysOverdue(),
                ];
            }

            $response['notes'] = $order->notes;
            $response['shipping_address'] = $order->shipping_address;
            $response['confirmed_at'] = $order->confirmed_at?->format('Y-m-d H:i:s');
        }

        return $response;
    }

    /**
     * Fulfill order by scanning barcodes (for social commerce/ecommerce)
     * 
     * This is the NEW step requested by client:
     * - Social commerce employee creates order WITHOUT barcodes (works from home)
     * - At end of day, warehouse staff scans barcodes to fulfill the order
     * - This assigns specific physical units (barcodes) to order items
     * - After fulfillment, order can be shipped via Pathao
     * 
     * POST /api/orders/{id}/fulfill
     * Body: {
     *   "fulfillments": [
     *     {
     *       "order_item_id": 123,
     *       "barcodes": ["BARCODE-001", "BARCODE-002"]  // Scan actual units
     *     },
     *     {
     *       "order_item_id": 124,
     *       "barcodes": ["BARCODE-003"]
     *     }
     *   ]
     * }
     */
    public function fulfill(Request $request, $id)
    {
        $order = Order::with(['items.batch', 'items.product'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Only social commerce and ecommerce orders need fulfillment
        if (!$order->needsFulfillment()) {
            return response()->json([
                'success' => false,
                'message' => 'This order type does not require fulfillment. Counter orders are fulfilled immediately.'
            ], 422);
        }

        if (!$order->canBeFulfilled()) {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be fulfilled. Current status: {$order->status}, Fulfillment status: {$order->fulfillment_status}"
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'fulfillments' => 'required|array|min:1',
            'fulfillments.*.order_item_id' => 'required|exists:order_items,id',
            'fulfillments.*.barcodes' => 'required|array|min:1',
            'fulfillments.*.barcodes.*' => 'required|string|exists:product_barcodes,barcode',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $fulfilledItems = [];
            $employee = Employee::find(Auth::id());

            foreach ($request->fulfillments as $fulfillment) {
                $orderItem = OrderItem::where('order_id', $order->id)
                    ->find($fulfillment['order_item_id']);

                if (!$orderItem) {
                    throw new \Exception("Order item {$fulfillment['order_item_id']} not found in this order");
                }

                $barcodes = $fulfillment['barcodes'];
                
                // Validate quantity matches
                if (count($barcodes) !== $orderItem->quantity) {
                    throw new \Exception("Item '{$orderItem->product_name}' requires {$orderItem->quantity} barcode(s), but " . count($barcodes) . " provided");
                }

                // Validate all barcodes
                $barcodeModels = [];
                foreach ($barcodes as $barcodeValue) {
                    $barcode = \App\Models\ProductBarcode::where('barcode', $barcodeValue)
                        ->where('product_id', $orderItem->product_id)
                        ->where('batch_id', $orderItem->product_batch_id)
                        ->first();

                    if (!$barcode) {
                        throw new \Exception("Barcode {$barcodeValue} not found for product {$orderItem->product_name} in specified batch");
                    }

                    if (!$barcode->is_active) {
                        throw new \Exception("Barcode {$barcodeValue} is not active (already sold or deactivated)");
                    }

                    if ($barcode->is_defective) {
                        throw new \Exception("Barcode {$barcodeValue} is marked as defective");
                    }

                    // Verify barcode belongs to correct store
                    if ($barcode->batch && $barcode->batch->store_id != $order->store_id) {
                        throw new \Exception("Barcode {$barcodeValue} belongs to a different store");
                    }

                    $barcodeModels[] = $barcode;
                }

                // For single quantity items, assign the barcode directly
                if ($orderItem->quantity == 1) {
                    $orderItem->update([
                        'product_barcode_id' => $barcodeModels[0]->id
                    ]);
                    
                    $fulfilledItems[] = [
                        'item_id' => $orderItem->id,
                        'product_name' => $orderItem->product_name,
                        'barcodes' => [$barcodeModels[0]->barcode]
                    ];
                } else {
                    // For multiple quantity items, we need to split into individual items
                    // This maintains proper barcode tracking
                    $originalQuantity = $orderItem->quantity;
                    $unitPrice = $orderItem->unit_price;
                    $discountPerUnit = $orderItem->discount_amount / $originalQuantity;
                    $taxPerUnit = $orderItem->tax_amount / $originalQuantity;
                    $cogsPerUnit = ($orderItem->cogs ?? 0) / $originalQuantity;

                    // Update first item with first barcode
                    $orderItem->update([
                        'quantity' => 1,
                        'product_barcode_id' => $barcodeModels[0]->id,
                        'discount_amount' => round($discountPerUnit, 2),
                        'tax_amount' => round($taxPerUnit, 2),
                        'cogs' => round($cogsPerUnit, 2),
                        'total_amount' => round($unitPrice - $discountPerUnit + $taxPerUnit, 2),
                    ]);

                    $fulfilledBarcodes = [$barcodeModels[0]->barcode];

                    // Create new items for remaining barcodes
                    for ($i = 1; $i < count($barcodeModels); $i++) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $orderItem->product_id,
                            'product_batch_id' => $orderItem->product_batch_id,
                            'product_barcode_id' => $barcodeModels[$i]->id,
                            'product_name' => $orderItem->product_name,
                            'product_sku' => $orderItem->product_sku,
                            'quantity' => 1,
                            'unit_price' => $unitPrice,
                            'discount_amount' => round($discountPerUnit, 2),
                            'tax_amount' => round($taxPerUnit, 2),
                            'cogs' => round($cogsPerUnit, 2),
                            'total_amount' => round($unitPrice - $discountPerUnit + $taxPerUnit, 2),
                        ]);

                        $fulfilledBarcodes[] = $barcodeModels[$i]->barcode;
                    }

                    $fulfilledItems[] = [
                        'item_id' => $orderItem->id,
                        'product_name' => $orderItem->product_name,
                        'original_quantity' => $originalQuantity,
                        'barcodes' => $fulfilledBarcodes
                    ];
                }
            }

            // Mark order as fulfilled
            $order->fulfill($employee);

            // Recalculate totals (in case of splitting)
            $order->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order fulfilled successfully. Ready for shipment.',
                'data' => [
                    'order_number' => $order->order_number,
                    'fulfillment_status' => $order->fulfillment_status,
                    'fulfilled_at' => $order->fulfilled_at->format('Y-m-d H:i:s'),
                    'fulfilled_by' => $order->fulfilledBy->name,
                    'fulfilled_items' => $fulfilledItems,
                    'next_step' => 'Create shipment for delivery',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Fulfillment failed: ' . $e->getMessage()
            ], 422);
        }
    }
}
