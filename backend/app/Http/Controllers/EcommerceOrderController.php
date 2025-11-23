<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EcommerceOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customer');
    }

    /**
     * Get customer orders with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customerId = auth('customer')->id();
            $perPage = $request->query('per_page', 15);
            $status = $request->query('status');
            $search = $request->query('search');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $query = Order::where('customer_id', $customerId)
                ->with(['items.product', 'customer'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('items.product', function($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $orders = $query->paginate($perPage);

            // Add order summary for each order
            foreach ($orders as $order) {
                $order->summary = [
                    'total_items' => $order->items->sum('quantity'),
                    'total_amount' => $order->total_amount,
                    'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
                    'days_since_order' => $order->created_at->diffInDays(now()),
                    'can_cancel' => $this->canCancelOrder($order),
                    'can_return' => $this->canReturnOrder($order),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->items(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'total_pages' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ],
                    'filters' => [
                        'status' => $status,
                        'search' => $search,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific order details
     */
    public function show($orderNumber): JsonResponse
    {
        try {
            $customerId = auth('customer')->id();
            
            $order = Order::where('customer_id', $customerId)
                ->where('order_number', $orderNumber)
                ->with([
                    'items.product.images',
                    'customer',
                    'store',
                    'orderPayments'
                ])
                ->firstOrFail();

            // Add calculated fields
            $order->summary = [
                'subtotal' => $order->items->sum(function($item) {
                    return $item->unit_price * $item->quantity;
                }),
                'total_items' => $order->items->sum('quantity'),
                'total_amount' => $order->total_amount,
                'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
                'can_cancel' => $this->canCancelOrder($order),
                'can_return' => $this->canReturnOrder($order),
                'tracking_available' => !empty($order->tracking_number),
            ];

            // Add delivery address (already cast to array in model)
            $order->delivery_address = $order->shipping_address ?? null;
            $order->billing_address = $order->billing_address ?? null;

            return response()->json([
                'success' => true,
                'data' => ['order' => $order],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create order from cart
     */
    public function createFromCart(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|string|in:cash_on_delivery,bkash,nagad,credit_card,bank_transfer',
                'shipping_address_id' => 'required|exists:customer_addresses,id',
                'billing_address_id' => 'nullable|exists:customer_addresses,id',
                'notes' => 'nullable|string|max:500',
                'coupon_code' => 'nullable|string',
                'delivery_preference' => 'nullable|in:standard,express,scheduled',
                'scheduled_delivery_date' => 'nullable|date|after:today',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $customerId = auth('customer')->id();

            // Get cart items
            $cartItems = Cart::where('customer_id', $customerId)
                ->where('status', 'active')
                ->with('product')
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            // Validate addresses
            $shippingAddress = CustomerAddress::forCustomer($customerId)
                ->findOrFail($request->shipping_address_id);
            
            $billingAddress = $request->billing_address_id 
                ? CustomerAddress::forCustomer($customerId)->findOrFail($request->billing_address_id)
                : $shippingAddress;

            DB::beginTransaction();

            try {
                // Calculate totals
                $subtotal = $cartItems->sum(function($item) {
                    return $item->price * $item->quantity;
                });

                $deliveryCharge = $this->calculateDeliveryCharge($shippingAddress);
                $taxAmount = $subtotal * 0.05; // 5% tax
                $totalAmount = $subtotal + $deliveryCharge + $taxAmount;

                // Apply coupon discount if provided
                $discountAmount = 0;
                if ($request->coupon_code) {
                    $discountAmount = $this->applyCoupon($request->coupon_code, $subtotal);
                    $totalAmount -= $discountAmount;
                }

                // Create order
                $order = Order::create([
                    'order_number' => $this->generateOrderNumber(),
                    'customer_id' => $customerId,
                    'store_id' => 1, // Default store
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_charge' => $deliveryCharge,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'payment_status' => $request->payment_method === 'cash_on_delivery' ? 'pending' : 'unpaid',
                    'shipping_address' => json_encode($shippingAddress->formatted_address),
                    'billing_address' => json_encode($billingAddress->formatted_address),
                    'notes' => $request->notes,
                    'order_type' => 'online',
                    'delivery_preference' => $request->delivery_preference ?? 'standard',
                    'scheduled_delivery_date' => $request->scheduled_delivery_date,
                    'coupon_code' => $request->coupon_code,
                ]);

                // Create order items
                foreach ($cartItems as $cartItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'total' => $cartItem->price * $cartItem->quantity,
                    ]);

                    // Update product stock
                    $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
                }

                // Clear cart
                Cart::where('customer_id', $customerId)
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);

                DB::commit();

                // Load relationships for response
                $order->load(['items.product', 'customer']);

                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'data' => [
                        'order' => $order,
                        'order_summary' => [
                            'order_number' => $order->order_number,
                            'total_amount' => $order->total_amount,
                            'payment_method' => $order->payment_method,
                            'estimated_delivery' => $this->getEstimatedDelivery($order),
                        ],
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancel($orderNumber): JsonResponse
    {
        try {
            $customerId = auth('customer')->id();
            
            $order = Order::where('customer_id', $customerId)
                ->where('order_number', $orderNumber)
                ->with('items.product')
                ->firstOrFail();

            if (!$this->canCancelOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this time',
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Update order status
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Customer cancellation',
                ]);

                // Restore product stock
                foreach ($order->items as $item) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Order cancelled successfully',
                    'data' => ['order' => $order],
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track order
     */
    public function track($orderNumber): JsonResponse
    {
        try {
            $customerId = auth('customer')->id();
            
            $order = Order::where('customer_id', $customerId)
                ->where('order_number', $orderNumber)
                ->firstOrFail();

            $trackingSteps = $this->getTrackingSteps($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'tracking' => [
                        'current_status' => $order->status,
                        'tracking_number' => $order->tracking_number,
                        'estimated_delivery' => $this->getEstimatedDelivery($order),
                        'steps' => $trackingSteps,
                        'last_updated' => $order->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $customerId = auth('customer')->id();

            $stats = [
                'total_orders' => Order::where('customer_id', $customerId)->count(),
                'completed_orders' => Order::where('customer_id', $customerId)->where('status', 'completed')->count(),
                'pending_orders' => Order::where('customer_id', $customerId)->whereIn('status', ['pending', 'processing', 'shipped'])->count(),
                'cancelled_orders' => Order::where('customer_id', $customerId)->where('status', 'cancelled')->count(),
                'total_spent' => Order::where('customer_id', $customerId)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                'average_order_value' => Order::where('customer_id', $customerId)
                    ->where('status', 'completed')
                    ->avg('total_amount'),
                'last_order_date' => Order::where('customer_id', $customerId)
                    ->latest()
                    ->value('created_at'),
            ];

            // Recent orders
            $recentOrders = Order::where('customer_id', $customerId)
                ->with('items.product')
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'recent_orders' => $recentOrders,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Helper methods

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymd');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$timestamp}-{$random}";
    }

    private function calculateDeliveryCharge(CustomerAddress $address): float
    {
        // Simple delivery charge calculation
        $city = strtolower($address->city);
        
        if (str_contains($city, 'dhaka')) {
            return 60.00; // Dhaka delivery
        } elseif (in_array($city, ['chittagong', 'sylhet', 'rajshahi', 'khulna'])) {
            return 120.00; // Major cities
        } else {
            return 150.00; // Other areas
        }
    }

    private function applyCoupon(string $couponCode, float $subtotal): float
    {
        // Simple coupon system - in real app, this would check database
        $coupons = [
            'WELCOME10' => ['type' => 'percentage', 'value' => 10, 'min_amount' => 1000],
            'SAVE50' => ['type' => 'fixed', 'value' => 50, 'min_amount' => 500],
            'NEWUSER' => ['type' => 'percentage', 'value' => 15, 'min_amount' => 2000],
        ];

        if (!isset($coupons[$couponCode])) {
            return 0;
        }

        $coupon = $coupons[$couponCode];
        
        if ($subtotal < $coupon['min_amount']) {
            return 0;
        }

        if ($coupon['type'] === 'percentage') {
            return ($subtotal * $coupon['value']) / 100;
        } else {
            return $coupon['value'];
        }
    }

    private function getEstimatedDelivery(Order $order): ?string
    {
        if ($order->scheduled_delivery_date) {
            return $order->scheduled_delivery_date;
        }

        // shipping_address is cast to array in the Order model
        $shippingAddress = $order->shipping_address ?? [];
        $city = strtolower($shippingAddress['city'] ?? '');
        
        $days = str_contains($city, 'dhaka') ? 2 : 4;
        
        if ($order->delivery_preference === 'express') {
            $days = max(1, $days - 1);
        }

        return now()->addDays($days)->format('Y-m-d');
    }

    private function canCancelOrder(Order $order): bool
    {
        return in_array($order->status, ['pending', 'processing']) && 
               $order->created_at->diffInHours(now()) <= 24;
    }

    private function canReturnOrder(Order $order): bool
    {
        return $order->status === 'completed' && 
               $order->updated_at->diffInDays(now()) <= 7;
    }

    private function getTrackingSteps(Order $order): array
    {
        $steps = [
            ['status' => 'pending', 'label' => 'Order Placed', 'completed' => true, 'date' => $order->created_at],
            ['status' => 'processing', 'label' => 'Order Processing', 'completed' => false, 'date' => null],
            ['status' => 'shipped', 'label' => 'Order Shipped', 'completed' => false, 'date' => null],
            ['status' => 'delivered', 'label' => 'Order Delivered', 'completed' => false, 'date' => null],
        ];

        foreach ($steps as &$step) {
            if ($order->status === $step['status'] || 
                ($order->status === 'completed' && $step['status'] === 'delivered')) {
                $step['completed'] = true;
                $step['date'] = $order->updated_at;
                break;
            } elseif ($step['completed']) {
                continue;
            } else {
                break;
            }
        }

        if ($order->status === 'cancelled') {
            $steps[] = ['status' => 'cancelled', 'label' => 'Order Cancelled', 'completed' => true, 'date' => $order->cancelled_at ?? $order->updated_at];
        }

        return $steps;
    }
}