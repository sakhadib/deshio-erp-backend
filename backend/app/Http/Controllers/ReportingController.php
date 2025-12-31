<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReturn;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class ReportingController extends Controller
{
    /**
     * Export category-wise sales report as CSV
     * 
     * GET /api/reporting/csv/category-sales
     * 
     * Query Parameters:
     * - date_from: Start date (YYYY-MM-DD) - optional
     * - date_to: End date (YYYY-MM-DD) - optional
     * - store_id: Filter by specific store - optional
     * - status: Filter by order status (completed, pending, etc.) - optional, default: completed
     * 
     * Response: CSV file download with columns:
     * - Category
     * - Sold Qty
     * - SUB Total
     * - Discount Amount
     * - Exchange Amount
     * - Return Amount
     * - Net Sales (without VAT)
     * - VAT Amount (7.5)
     * - Net Amount
     */
    public function exportCategorySalesCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_id' => 'nullable|exists:stores,id',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query for order items joined with products and categories
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('orders.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at');

        // Filter by order status (optional - if not provided, includes all statuses)
        if ($request->filled('status')) {
            $query->where('orders.status', $request->status);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('orders.order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('orders.order_date', '<=', $request->date_to);
        }

        // Store filter
        if ($request->filled('store_id')) {
            $query->where('orders.store_id', $request->store_id);
        }

        // Group by category and aggregate sales data
        $categorySales = $query->select(
            'categories.id as category_id',
            'categories.title as category_name',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.quantity * order_items.unit_price) as subtotal'),
            DB::raw('SUM(order_items.discount_amount * order_items.quantity) as total_discount'),
            DB::raw('SUM(order_items.tax_amount) as total_tax')
        )
        ->groupBy('categories.id', 'categories.title')
        ->get();

        // Calculate returns and refunds per category
        $categoryReturns = ProductReturn::query()
            ->join('orders', 'product_returns.order_id', '=', 'orders.id')
            ->whereIn('product_returns.status', ['approved', 'processed', 'completed'])
            ->when($request->filled('date_from'), function($q) use ($request) {
                $q->whereDate('product_returns.return_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                $q->whereDate('product_returns.return_date', '<=', $request->date_to);
            })
            ->when($request->filled('store_id'), function($q) use ($request) {
                $q->where('product_returns.store_id', $request->store_id);
            })
            ->select(
                'product_returns.id',
                'product_returns.return_items',
                'product_returns.total_return_value'
            )
            ->get();

        // Process returns per category
        $returnsByCategory = [];
        foreach ($categoryReturns as $return) {
            $returnItems = is_string($return->return_items) 
                ? json_decode($return->return_items, true) 
                : $return->return_items;
            
            if (is_array($returnItems)) {
                foreach ($returnItems as $item) {
                    if (isset($item['product_id'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        if ($product && $product->category_id) {
                            if (!isset($returnsByCategory[$product->category_id])) {
                                $returnsByCategory[$product->category_id] = 0;
                            }
                            $returnsByCategory[$product->category_id] += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                        }
                    }
                }
            }
        }

        // Calculate refunds per category (for exchanges)
        $categoryRefunds = Refund::query()
            ->join('product_returns', 'refunds.return_id', '=', 'product_returns.id')
            ->join('orders', 'refunds.order_id', '=', 'orders.id')
            ->whereIn('refunds.status', ['completed', 'processed'])
            ->where('refunds.refund_method', 'exchange') // Exchange transactions
            ->when($request->filled('date_from'), function($q) use ($request) {
                $q->whereDate('refunds.completed_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                $q->whereDate('refunds.completed_at', '<=', $request->date_to);
            })
            ->when($request->filled('store_id'), function($q) use ($request) {
                $q->where('orders.store_id', $request->store_id);
            })
            ->select(
                'refunds.id',
                'product_returns.return_items',
                'refunds.refund_amount'
            )
            ->get();

        // Process exchanges per category
        $exchangesByCategory = [];
        foreach ($categoryRefunds as $refund) {
            $returnItems = is_string($refund->return_items) 
                ? json_decode($refund->return_items, true) 
                : $refund->return_items;
            
            if (is_array($returnItems)) {
                foreach ($returnItems as $item) {
                    if (isset($item['product_id'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        if ($product && $product->category_id) {
                            if (!isset($exchangesByCategory[$product->category_id])) {
                                $exchangesByCategory[$product->category_id] = 0;
                            }
                            // Proportional exchange amount
                            $itemTotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                            $exchangesByCategory[$product->category_id] += $itemTotal;
                        }
                    }
                }
            }
        }

        // Generate CSV
        $filename = 'category-sales-report-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($categorySales, $returnsByCategory, $exchangesByCategory) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Category',
                'Sold Qty',
                'SUB Total',
                'Discount Amount',
                'Exchange Amount',
                'Return Amount',
                'Net Sales (without VAT)',
                'VAT Amount (7.5)',
                'Net Amount'
            ]);

            // CSV Rows
            foreach ($categorySales as $sale) {
                $categoryId = $sale->category_id;
                $subtotal = floatval($sale->subtotal);
                $discount = floatval($sale->total_discount);
                $taxAmount = floatval($sale->total_tax);
                
                $returnAmount = $returnsByCategory[$categoryId] ?? 0;
                $exchangeAmount = $exchangesByCategory[$categoryId] ?? 0;
                
                // Calculate net sales (subtotal - discount - returns - exchanges)
                $netSalesWithoutVAT = $subtotal - $discount - $returnAmount - $exchangeAmount;
                
                // If tax is already in subtotal (inclusive), extract it
                // Otherwise VAT = 7.5% of net sales
                $vatAmount = $taxAmount > 0 ? $taxAmount : ($netSalesWithoutVAT * 0.075);
                
                // Net amount = net sales + VAT (or net sales if VAT already included)
                $netAmount = $taxAmount > 0 ? $netSalesWithoutVAT : ($netSalesWithoutVAT * 1.075);
                
                fputcsv($file, [
                    $sale->category_name,
                    number_format($sale->total_quantity, 0),
                    number_format($subtotal, 2),
                    number_format($discount, 2),
                    number_format($exchangeAmount, 2),
                    number_format($returnAmount, 2),
                    number_format($netSalesWithoutVAT, 2),
                    number_format($vatAmount, 2),
                    number_format($netAmount, 2),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export detailed sales report as CSV
     * 
     * GET /api/reporting/csv/sales
     * 
     * Query Parameters:
     * - date_from: Start date (YYYY-MM-DD) - optional
     * - date_to: End date (YYYY-MM-DD) - optional
     * - store_id: Filter by specific store - optional
     * - status: Filter by order status - optional
     * - customer_id: Filter by customer - optional
     * 
     * Response: CSV file download with order-level details
     */
    public function exportSalesCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_id' => 'nullable|exists:stores,id',
            'status' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query for orders with related data
        $query = Order::query()
            ->with(['customer', 'items.product', 'payments.paymentMethod', 'shipments'])
            ->whereNull('deleted_at');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        // Generate CSV
        $filename = 'sales-report-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Creation Date',
                'Invoice Number',
                'Customer Name',
                'Customer Phone',
                'Customer Address',
                'Product Name And QTY',
                'Product Specification',
                'Product Attribute',
                'Sub Total Price',
                'Discount',
                'Price After Discount',
                'Delivery Charge',
                'Total Price',
                'Paid Amount',
                'Due Amount',
                'Delivery Partner',
                'Delivery Area',
                'Payment Method',
                'Order Status'
            ]);

            // CSV Rows - One row per order
            foreach ($orders as $order) {
                // Customer info
                $customerName = $order->customer ? $order->customer->name : 'N/A';
                $customerPhone = $order->customer ? $order->customer->phone : 'N/A';
                
                // Customer address (from order's shipping_address or customer's address)
                $customerAddress = '';
                if ($order->shipping_address && is_array($order->shipping_address)) {
                    $addressParts = array_filter([
                        $order->shipping_address['street'] ?? $order->shipping_address['address_line_1'] ?? '',
                        $order->shipping_address['area'] ?? $order->shipping_address['address_line_2'] ?? '',
                        $order->shipping_address['city'] ?? '',
                    ]);
                    $customerAddress = implode(', ', $addressParts);
                } elseif ($order->customer) {
                    $customerAddress = $order->customer->address ?? '';
                }
                
                // Product details - concatenate all items
                $productNames = [];
                $productSpecs = [];
                $productAttrs = [];
                
                foreach ($order->items as $item) {
                    $productNames[] = ($item->product_name ?? 'Unknown') . ' (x' . $item->quantity . ')';
                    
                    // Product specification (custom fields)
                    $specs = [];
                    if ($item->product_options) {
                        $options = is_string($item->product_options) 
                            ? json_decode($item->product_options, true) 
                            : $item->product_options;
                        if (is_array($options)) {
                            foreach ($options as $key => $value) {
                                $specs[] = "$key: $value";
                            }
                        }
                    }
                    $productSpecs[] = !empty($specs) ? implode('; ', $specs) : 'N/A';
                    
                    // Product attributes (SKU, batch info, etc.)
                    $attrs = [];
                    if ($item->product_sku) {
                        $attrs[] = "SKU: {$item->product_sku}";
                    }
                    $productAttrs[] = !empty($attrs) ? implode('; ', $attrs) : 'N/A';
                }
                
                $productNameQty = implode(' | ', $productNames);
                $productSpec = implode(' | ', $productSpecs);
                $productAttr = implode(' | ', $productAttrs);
                
                // Financial calculations
                $subtotal = floatval($order->subtotal);
                $discount = floatval($order->discount_amount);
                $priceAfterDiscount = $subtotal - $discount;
                $deliveryCharge = floatval($order->shipping_amount);
                $totalPrice = floatval($order->total_amount);
                $paidAmount = floatval($order->paid_amount);
                $dueAmount = floatval($order->outstanding_amount);
                
                // Delivery partner (from shipments)
                $deliveryPartner = 'N/A';
                $deliveryArea = '';
                
                if ($order->shipments && $order->shipments->count() > 0) {
                    $shipment = $order->shipments->first();
                    $deliveryPartner = $shipment->carrier_name ?? 'N/A';
                    
                    // Delivery area from shipping address
                    if ($order->shipping_address && is_array($order->shipping_address)) {
                        $deliveryArea = $order->shipping_address['area'] ?? $order->shipping_address['city'] ?? '';
                    }
                } elseif ($order->shipping_address && is_array($order->shipping_address)) {
                    $deliveryArea = $order->shipping_address['area'] ?? $order->shipping_address['city'] ?? '';
                }
                
                // Payment method (from payments)
                $paymentMethods = [];
                if ($order->payments && $order->payments->count() > 0) {
                    foreach ($order->payments as $payment) {
                        if ($payment->paymentMethod) {
                            $paymentMethods[] = $payment->paymentMethod->name;
                        } elseif ($payment->payment_method) {
                            $paymentMethods[] = $payment->payment_method;
                        }
                    }
                }
                $paymentMethod = !empty($paymentMethods) ? implode(', ', array_unique($paymentMethods)) : 'N/A';
                
                // Write row
                fputcsv($file, [
                    $order->order_date ? $order->order_date->format('Y-m-d H:i:s') : 'N/A',
                    $order->order_number ?? 'N/A',
                    $customerName,
                    $customerPhone,
                    $customerAddress,
                    $productNameQty,
                    $productSpec,
                    $productAttr,
                    number_format($subtotal, 2),
                    number_format($discount, 2),
                    number_format($priceAfterDiscount, 2),
                    number_format($deliveryCharge, 2),
                    number_format($totalPrice, 2),
                    number_format($paidAmount, 2),
                    number_format($dueAmount, 2),
                    $deliveryPartner,
                    $deliveryArea,
                    $paymentMethod,
                    ucfirst(str_replace('_', ' ', $order->status ?? 'N/A')),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
