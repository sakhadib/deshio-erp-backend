<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBatch;
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
     * - status: Filter by order status (confirmed, pending_assignment, pending) - optional
     *           Default: includes confirmed and pending_assignment orders
     * 
     * Response: CSV file download with columns:
     * - Category
     * - Sold Qty
     * - SUB Total (quantity × unit_price)
     * - Discount Amount (total discounts applied)
     * - Exchange Amount (value of exchanged items)
     * - Return Amount (value of returned items)
     * - Net Sales (without VAT) = Subtotal - Discount - Returns - Exchanges
     * - VAT Amount (actual tax from orders)
     * - Net Amount (final revenue including VAT)
     * 
     * Accounting Formula:
     * - Subtotal = SUM(quantity × unit_price) for all items in category
     * - Net Sales = Subtotal - Discounts - Returns - Exchanges
     * - Net Amount = Net Sales + VAT
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

        // Filter by order status (default: confirmed orders only)
        if ($request->filled('status')) {
            $query->where('orders.status', $request->status);
        } else {
            // Default: include confirmed orders (real statuses: confirmed, pending_assignment, pending)
            $query->whereIn('orders.status', ['confirmed', 'pending_assignment']);
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
            DB::raw('SUM(order_items.discount_amount) as total_discount'),  // Fixed: Don't multiply by quantity
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
                
                // Accounting Logic:
                // Net Sales (without VAT) = Subtotal - Discount - Returns - Exchanges
                $netSalesWithoutVAT = $subtotal - $discount - $returnAmount - $exchangeAmount;
                
                // VAT Amount: Use actual tax from order items
                $vatAmount = $taxAmount;
                
                // Net Amount = Net Sales + VAT (total revenue after all deductions)
                $netAmount = $netSalesWithoutVAT + $vatAmount;
                
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
                'Vat Amount',
                'Total Without Vat',
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
                $vatAmount = floatval($order->tax_amount);
                $totalWithoutVat = $totalPrice - $vatAmount;
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
                    number_format($vatAmount, 2),
                    number_format($totalWithoutVat, 2),
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

    /**
     * Export stock report as CSV
     * 
     * GET /api/reporting/csv/stock
     * 
     * Query Parameters:
     * - store_id: Filter by specific store - optional
     * - category_id: Filter by category - optional
     * - product_id: Filter by product - optional
     * - include_inactive: Include inactive batches (default: false) - optional
     * 
     * Response: CSV file download with product stock details including:
     * - Category, Product Code (SKU), Product Name, Product Brand, Product Description
     * - Sold Quantity (total sold from this batch)
     * - Sub Total (total sales revenue from this batch)
     * - Remaining Stock Quantity
     * - Stock Volume (remaining quantity × sell price)
     */
    public function exportStockCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'nullable|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'product_id' => 'nullable|exists:products,id',
            'include_inactive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query for product batches with relationships
        $query = ProductBatch::query()
            ->with(['product.category', 'store']);

        // Join products to access category and product details
        $query->join('products', 'product_batches.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at');

        // Filters
        if ($request->filled('store_id')) {
            $query->where('product_batches.store_id', $request->store_id);
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_batches.product_id', $request->product_id);
        }

        // By default, only show active batches
        if (!$request->boolean('include_inactive')) {
            $query->where('product_batches.is_active', true);
        }

        // Select batch fields
        $query->select('product_batches.*');

        $batches = $query->orderBy('products.category_id')
            ->orderBy('products.sku')
            ->orderBy('product_batches.batch_number')
            ->get();

        // Calculate sold quantities for each batch
        $batchIds = $batches->pluck('id')->toArray();
        
        $soldQuantities = [];
        $soldSubtotals = [];
        
        if (!empty($batchIds)) {
            $orderItemsData = OrderItem::query()
                ->whereIn('product_batch_id', $batchIds)
                ->whereHas('order', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->selectRaw('product_batch_id, SUM(quantity) as total_sold, SUM(total_amount) as total_revenue')
                ->groupBy('product_batch_id')
                ->get()
                ->keyBy('product_batch_id');
            
            foreach ($orderItemsData as $batchId => $data) {
                $soldQuantities[$batchId] = $data->total_sold;
                $soldSubtotals[$batchId] = $data->total_revenue;
            }
        }

        // Generate CSV
        $filename = 'stock-report-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($batches, $soldQuantities, $soldSubtotals) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Category',
                'Product Code',
                'Product Name',
                'Product Brand',
                'Product Description',
                'Batch Number',
                'Sold Quantity',
                'Sub Total',
                'Remaining Stock Quantity',
                'Stock Volume',
                'Store',
            ]);

            // CSV Rows - One row per batch
            foreach ($batches as $batch) {
                $product = $batch->product;
                
                // Category
                $categoryName = $product && $product->category ? $product->category->title : 'N/A';
                
                // Product identification
                $productCode = $product ? $product->sku : 'N/A';
                $productName = $product ? $product->name : 'N/A';
                $productBrand = $product && $product->brand ? $product->brand : 'N/A';
                $productDescription = $product && $product->description ? $product->description : 'N/A';
                
                // Batch number
                $batchNumber = $batch->batch_number ?? 'N/A';
                
                // Sold quantity and subtotal
                $soldQty = $soldQuantities[$batch->id] ?? 0;
                $soldSubtotal = $soldSubtotals[$batch->id] ?? 0;
                
                // Remaining stock
                $remainingStock = floatval($batch->quantity);
                
                // Stock volume = remaining quantity × sell price
                $sellPrice = floatval($batch->sell_price);
                $stockVolume = $remainingStock * $sellPrice;
                
                // Store name
                $storeName = $batch->store ? $batch->store->name : 'N/A';
                
                // Write row
                fputcsv($file, [
                    $categoryName,
                    $productCode,
                    $productName,
                    $productBrand,
                    $productDescription,
                    $batchNumber,
                    number_format($soldQty, 0),
                    number_format($soldSubtotal, 2),
                    number_format($remainingStock, 0),
                    number_format($stockVolume, 2),
                    $storeName,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export booking (order items) report as CSV
     * 
     * GET /api/reporting/csv/booking
     * 
     * Query Parameters:
     * - date_from: Start date (YYYY-MM-DD) - optional
     * - date_to: End date (YYYY-MM-DD) - optional
     * - store_id: Filter by specific store - optional
     * - status: Filter by order status - optional
     * - customer_id: Filter by customer - optional
     * - product_id: Filter by product - optional
     * 
     * Response: CSV file download with booking details including:
     * - Order Number, Customer Name, Customer Phone, Customer Code
     * - Product Name, Product Code (SKU), Product Barcode, Quantity
     * - Selling Price, Cost Price (from batch)
     * - Payable (order total), Paid Amount, Due Amount
     */
    public function exportBookingCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_id' => 'nullable|exists:stores,id',
            'status' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query for order items with related data
        $query = OrderItem::query()
            ->with(['order.customer', 'product', 'batch', 'barcode'])
            ->whereHas('order', function($q) use ($request) {
                $q->whereNull('deleted_at');
                
                // Filters on order
                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }
                
                if ($request->filled('date_from')) {
                    $q->whereDate('order_date', '>=', $request->date_from);
                }
                
                if ($request->filled('date_to')) {
                    $q->whereDate('order_date', '<=', $request->date_to);
                }
                
                if ($request->filled('customer_id')) {
                    $q->where('customer_id', $request->customer_id);
                }
            });

        // Filters on order items
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $orderItems = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $filename = 'booking-report-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($orderItems) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Order Number',
                'Order Date',
                'Customer Name',
                'Customer Phone',
                'Customer Code',
                'Product Name',
                'Product Code (SKU)',
                'Product Barcode',
                'Batch Number',
                'Quantity',
                'Selling Price',
                'Cost Price',
                'Item Subtotal',
                'Payable (Order Total)',
                'Paid Amount',
                'Due Amount',
            ]);

            // CSV Rows - One row per order item
            foreach ($orderItems as $item) {
                $order = $item->order;
                $customer = $order ? $order->customer : null;
                $product = $item->product;
                $batch = $item->batch;
                $barcode = $item->barcode;
                
                // Customer info
                $orderNumber = $order ? $order->order_number : 'N/A';
                $orderDate = $order && $order->order_date ? $order->order_date->format('Y-m-d H:i:s') : 'N/A';
                $customerName = $customer ? $customer->name : 'N/A';
                $customerPhone = $customer ? $customer->phone : 'N/A';
                $customerCode = $customer ? ($customer->customer_code ?? 'N/A') : 'N/A';
                
                // Product info
                $productName = $item->product_name ?? 'N/A';
                $productSku = $item->product_sku ?? 'N/A';
                $productBarcode = $barcode ? $barcode->barcode : 'N/A';
                $batchNumber = $batch ? $batch->batch_number : 'N/A';
                
                // Quantity
                $quantity = floatval($item->quantity);
                
                // Pricing from batch
                $sellingPrice = $batch ? floatval($batch->sell_price) : 0;
                $costPrice = $batch ? floatval($batch->cost_price) : 0;
                
                // Item subtotal
                $itemSubtotal = floatval($item->total_amount);
                
                // Order financial data
                $payable = $order ? floatval($order->total_amount) : 0;
                $paid = $order ? floatval($order->paid_amount) : 0;
                $due = $order ? floatval($order->outstanding_amount) : 0;
                
                // Write row
                fputcsv($file, [
                    $orderNumber,
                    $orderDate,
                    $customerName,
                    $customerPhone,
                    $customerCode,
                    $productName,
                    $productSku,
                    $productBarcode,
                    $batchNumber,
                    number_format($quantity, 0),
                    number_format($sellingPrice, 2),
                    number_format($costPrice, 2),
                    number_format($itemSubtotal, 2),
                    number_format($payable, 2),
                    number_format($paid, 2),
                    number_format($due, 2),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export payment breakdown report as CSV
     * 
     * GET /api/reporting/csv/payment-breakdown
     * 
     * Query Parameters:
     * - date_from: Start date (YYYY-MM-DD) - optional
     * - date_to: End date (YYYY-MM-DD) - optional
     * - today: Get today's orders only (boolean) - optional
     * - store_id: Filter by specific store - optional
     * - order_type: Filter by order type (counter, ecommerce, social_commerce) - optional
     * - status: Filter by order status - optional
     * 
     * Response: CSV file download with payment breakdown per order
     */
    public function exportPaymentBreakdownCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'today' => 'nullable|boolean',
            'store_id' => 'nullable|exists:stores,id',
            'order_type' => 'nullable|in:counter,ecommerce,social_commerce,service',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query for orders with items and payments
        $query = Order::query()
            ->with(['customer', 'items.product', 'payments.paymentMethod', 'store'])
            ->whereNull('deleted_at');

        // Today filter (overrides date_from/date_to)
        if ($request->boolean('today')) {
            $query->whereDate('order_date', today());
        } else {
            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('order_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('order_date', '<=', $request->date_to);
            }
        }

        // Store filter
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Order type filter
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        // Generate CSV
        $filename = 'payment-breakdown-' . now()->format('Y-m-d-His') . '.csv';
        
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
                'Date',
                'Invoice Number',
                'Store/Branch',
                'Customer Name',
                'Customer Phone',
                'Customer Address',
                'Product Name',
                'Quantity',
                'Cash Paid',
                'Bkash/Mobile Banking Paid',
                'Bank Paid',
                'Due',
                'System (Online/POS)',
                'Order Status',
            ]);

            // CSV Rows - One row per order
            foreach ($orders as $order) {
                // Customer info
                $customerName = $order->customer ? $order->customer->name : 'Walk-in Customer';
                $customerPhone = $order->customer ? $order->customer->phone : 'N/A';
                
                // Customer address
                $customerAddress = '';
                if ($order->shipping_address && is_array($order->shipping_address)) {
                    $addressParts = array_filter([
                        $order->shipping_address['street'] ?? $order->shipping_address['address_line_1'] ?? '',
                        $order->shipping_address['area'] ?? $order->shipping_address['address_line_2'] ?? '',
                        $order->shipping_address['city'] ?? '',
                    ]);
                    $customerAddress = implode(', ', $addressParts);
                } elseif ($order->customer && $order->customer->address) {
                    $customerAddress = $order->customer->address;
                }
                
                // Store/Branch name
                $storeName = $order->store ? $order->store->name : 'N/A';
                
                // Product names - concatenate all items with quantities
                $productNames = [];
                foreach ($order->items as $item) {
                    $productNames[] = ($item->product_name ?? 'Unknown') . ' (x' . $item->quantity . ')';
                }
                $productNameQty = implode(', ', $productNames);
                
                // Total quantity
                $totalQuantity = $order->items->sum('quantity');
                
                // Calculate payment breakdown by payment method type
                $cashPaid = 0;
                $mobileBankingPaid = 0;
                $bankPaid = 0;
                
                foreach ($order->payments as $payment) {
                    if ($payment->status !== 'completed') {
                        continue; // Only count completed payments
                    }
                    
                    $amount = floatval($payment->amount);
                    
                    if ($payment->paymentMethod) {
                        $methodType = $payment->paymentMethod->type;
                        $methodName = strtolower($payment->paymentMethod->name);
                        
                        // Cash payment (ID 1 or type 'cash')
                        if ($payment->payment_method_id == 1 || $methodType == 'cash') {
                            $cashPaid += $amount;
                        }
                        // Mobile banking (bKash, Nagad, Rocket, etc.) - ID 6 or type 'mobile_banking'
                        elseif ($payment->payment_method_id == 6 || $methodType == 'mobile_banking' || 
                                str_contains($methodName, 'bkash') || str_contains($methodName, 'nagad') ||
                                str_contains($methodName, 'rocket') || str_contains($methodName, 'upay')) {
                            $mobileBankingPaid += $amount;
                        }
                        // Bank transfer - ID 4 or type 'bank_transfer' or 'online_banking'
                        elseif ($payment->payment_method_id == 4 || 
                                $methodType == 'bank_transfer' || 
                                $methodType == 'online_banking') {
                            $bankPaid += $amount;
                        }
                    }
                }
                
                // Due amount
                $dueAmount = floatval($order->outstanding_amount);
                
                // System type - map order_type to readable format
                $systemType = match($order->order_type) {
                    'counter' => 'POS',
                    'ecommerce' => 'Online (E-commerce)',
                    'social_commerce' => 'Online (Social)',
                    'service' => 'Service Order',
                    default => ucfirst($order->order_type ?? 'N/A'),
                };
                
                // Order status
                $orderStatus = ucfirst(str_replace('_', ' ', $order->status ?? 'N/A'));
                
                // Write row
                fputcsv($file, [
                    $order->order_date ? $order->order_date->format('Y-m-d H:i:s') : 'N/A',
                    $order->order_number ?? 'N/A',
                    $storeName,
                    $customerName,
                    $customerPhone,
                    $customerAddress,
                    $productNameQty,
                    $totalQuantity,
                    number_format($cashPaid, 2),
                    number_format($mobileBankingPaid, 2),
                    number_format($bankPaid, 2),
                    number_format($dueAmount, 2),
                    $systemType,
                    $orderStatus,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export customer installment/partial payment report as CSV
     * 
     * GET /api/reporting/csv/installments
     * 
     * Generates detailed report of all orders with installment/partial payments.
     * Shows customer information, order details, payment history, and outstanding balances.
     * Each row represents one payment made by a customer.
     * 
     * Filters:
     * - date_from, date_to: Order date range (optional)
     * - customer_id: Specific customer (optional)
     * - store_id: Specific store (optional)
     * - payment_status: Filter by payment status (optional: unpaid, partial, paid, overdue)
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function exportInstallmentsCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'customer_id' => 'nullable|exists:customers,id',
            'store_id' => 'nullable|exists:stores,id',
            'payment_status' => 'nullable|in:unpaid,partial,paid,overdue',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Query orders with partial payments or installments
        $query = Order::with([
            'customer',
            'store',
            'items.product',
            'payments' => function($q) {
                $q->whereNull('deleted_at')->orderBy('payment_received_date', 'asc');
            }
        ])
        ->where(function($q) {
            $q->where('allow_partial_payments', true)
              ->orWhere('is_installment_payment', true)
              ->orWhere('payment_status', 'partial');
        });

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        $filename = "Installment-Report-" . now()->format('Y-m-d') . ".csv";

        return response()->stream(function() use ($orders) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($file, [
                'Order Number',
                'Order Date',
                'Store',
                'Customer Name',
                'Customer Phone',
                'Customer Email',
                'Customer Address',
                'Products',
                'Total Items',
                'Order Total',
                'Total Paid',
                'Outstanding',
                'Payment Status',
                'Next Payment Due',
                'Payment Number',
                'Payment Date',
                'Payment Amount',
                'Payment Method',
                'Payment Type',
                'Installment Number',
                'Balance Before',
                'Balance After',
                'Processed By',
                'Payment Notes',
            ]);

            // Process each order
            foreach ($orders as $order) {
                $customer = $order->customer;
                $store = $order->store;
                
                // Product summary
                $products = [];
                $totalItems = 0;
                foreach ($order->items as $item) {
                    $products[] = ($item->product->name ?? 'N/A') . ' (x' . $item->quantity . ')';
                    $totalItems += $item->quantity;
                }
                $productsSummary = implode(', ', $products);

                // Payment status display
                $paymentStatus = ucfirst($order->payment_status ?? 'N/A');
                
                // If order has no payments yet, show order info with empty payment fields
                if ($order->payments->isEmpty()) {
                    fputcsv($file, [
                        $order->order_number,
                        $order->order_date ? $order->order_date->format('Y-m-d H:i') : '',
                        $store->name ?? '',
                        $customer->name ?? '',
                        $customer->phone ?? '',
                        $customer->email ?? '',
                        $customer->address ?? '',
                        $productsSummary,
                        $totalItems,
                        number_format($order->total_amount, 2),
                        number_format($order->paid_amount, 2),
                        number_format($order->outstanding_amount, 2),
                        $paymentStatus,
                        $order->next_payment_due ? $order->next_payment_due->format('Y-m-d') : '',
                        'NO PAYMENTS YET',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]);
                } else {
                    // One row per payment
                    foreach ($order->payments as $payment) {
                        fputcsv($file, [
                            $order->order_number,
                            $order->order_date ? $order->order_date->format('Y-m-d H:i') : '',
                            $store->name ?? '',
                            $customer->name ?? '',
                            $customer->phone ?? '',
                            $customer->email ?? '',
                            $customer->address ?? '',
                            $productsSummary,
                            $totalItems,
                            number_format($order->total_amount, 2),
                            number_format($order->paid_amount, 2),
                            number_format($order->outstanding_amount, 2),
                            $paymentStatus,
                            $order->next_payment_due ? $order->next_payment_due->format('Y-m-d') : '',
                            $payment->payment_number ?? '',
                            $payment->payment_received_date ? $payment->payment_received_date->format('Y-m-d') : '',
                            number_format($payment->amount, 2),
                            $payment->paymentMethod->name ?? 'N/A',
                            ucfirst($payment->payment_type ?? 'N/A'),
                            $payment->installment_number ?? '',
                            number_format($payment->order_balance_before ?? 0, 2),
                            number_format($payment->order_balance_after ?? 0, 2),
                            $payment->processedBy->name ?? '',
                            $payment->notes ?? '',
                        ]);
                    }
                }
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
