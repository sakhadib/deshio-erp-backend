<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Traits\DatabaseAgnosticSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use DatabaseAgnosticSearch;
    /**
     * Get all customers with filters and pagination
     */
    public function index(Request $request)
    {
        $query = Customer::with(['createdBy', 'assignedEmployee']);

        // Filter by customer type
        if ($request->has('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned employee
        if ($request->has('assigned_employee_id')) {
            $query->where('assigned_employee_id', $request->assigned_employee_id);
        }

        // Search by name, phone, email, customer_code
        if ($request->has('search')) {
            $search = $request->search;
            $this->whereAnyLike($query, ['name', 'phone', 'email', 'customer_code'], $search);
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Filter by gender
        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->get('per_page', 15);
        $customers = $query->paginate($perPage);

        return response()->json($customers);
    }

    /**
     * Create a new customer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_type' => 'required|in:counter,social_commerce,e_commerce',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'preferences' => 'nullable|array',
            'social_profiles' => 'nullable|array',
            'status' => 'nullable|in:active,inactive,blocked',
            'notes' => 'nullable|string',
            'assigned_employee_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'active';
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer->load(['createdBy', 'assignedEmployee'])
        ], 201);
    }

    /**
     * Get a single customer with details
     */
    public function show($id)
    {
        $customer = Customer::with([
            'createdBy',
            'assignedEmployee',
            'orders' => function($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($id);

        // Add computed fields
        $customer->lifetime_value = $customer->total_purchases;
        $customer->orders_count = $customer->total_orders;

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Update customer details
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_type' => 'sometimes|in:counter,social_commerce,e_commerce',
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:customers,phone,' . $id,
            'email' => 'nullable|email|unique:customers,email,' . $id,
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'preferences' => 'nullable|array',
            'social_profiles' => 'nullable|array',
            'status' => 'nullable|in:active,inactive,blocked',
            'notes' => 'nullable|string',
            'assigned_employee_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['customer_code', 'total_purchases', 'total_orders']);
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer->load(['createdBy', 'assignedEmployee'])
        ]);
    }

    /**
     * Soft delete a customer
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Activate a customer
     */
    public function activate($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Customer activated successfully',
            'data' => $customer
        ]);
    }

    /**
     * Deactivate a customer
     */
    public function deactivate($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Customer deactivated successfully',
            'data' => $customer
        ]);
    }

    /**
     * Block a customer
     */
    public function block($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['status' => 'blocked']);

        return response()->json([
            'success' => true,
            'message' => 'Customer blocked successfully',
            'data' => $customer
        ]);
    }

    /**
     * Get customer order history
     */
    public function getOrderHistory(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $query = $customer->orders()->with(['items', 'payments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get customer analytics
     */
    public function getAnalytics($id)
    {
        $customer = Customer::findOrFail($id);

        $analytics = [
            'customer_info' => [
                'name' => $customer->name,
                'customer_code' => $customer->customer_code,
                'customer_type' => $customer->customer_type,
                'member_since' => $customer->created_at->format('Y-m-d'),
                'status' => $customer->status,
            ],
            'purchase_summary' => [
                'lifetime_value' => (float) $customer->total_purchases,
                'total_orders' => $customer->total_orders,
                'average_order_value' => $customer->total_orders > 0 
                    ? (float) ($customer->total_purchases / $customer->total_orders) 
                    : 0,
                'first_purchase' => $customer->first_purchase_at?->format('Y-m-d'),
                'last_purchase' => $customer->last_purchase_at?->format('Y-m-d'),
            ],
            'order_statistics' => [
                'completed_orders' => $customer->orders()->where('status', 'completed')->count(),
                'cancelled_orders' => $customer->orders()->where('status', 'cancelled')->count(),
                'pending_orders' => $customer->orders()->where('status', 'pending')->count(),
            ],
            'payment_statistics' => [
                'total_paid' => (float) $customer->orders()->sum('paid_amount'),
                'total_outstanding' => (float) $customer->orders()->sum('outstanding_amount'),
            ],
        ];

        // Monthly purchase trend (last 12 months)
        $dateFormatSql = $this->getDateFormatSql('created_at', 'month');
        $monthlyTrend = Order::where('customer_id', $id)
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw("{$dateFormatSql} as month, SUM(total_amount) as total, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $analytics['monthly_trend'] = $monthlyTrend;

        // Top purchased products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.customer_id', $id)
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_spent')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $analytics['top_products'] = $topProducts;

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(Request $request)
    {
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'inactive_customers' => Customer::where('status', 'inactive')->count(),
            'blocked_customers' => Customer::where('status', 'blocked')->count(),
            'by_type' => [
                'counter' => Customer::where('customer_type', 'counter')->count(),
                'social_commerce' => Customer::where('customer_type', 'social_commerce')->count(),
                'e_commerce' => Customer::where('customer_type', 'e_commerce')->count(),
            ],
            'new_this_month' => Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'new_today' => Customer::whereDate('created_at', today())->count(),
        ];

        // Gender distribution
        $stats['by_gender'] = Customer::selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender');

        // Top cities
        $stats['top_cities'] = Customer::selectRaw('city, COUNT(*) as count')
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'city');

        // Customer lifetime value ranges
        $stats['ltv_ranges'] = [
            '0-10000' => Customer::where('total_purchases', '>=', 0)->where('total_purchases', '<', 10000)->count(),
            '10000-50000' => Customer::where('total_purchases', '>=', 10000)->where('total_purchases', '<', 50000)->count(),
            '50000-100000' => Customer::where('total_purchases', '>=', 50000)->where('total_purchases', '<', 100000)->count(),
            '100000+' => Customer::where('total_purchases', '>=', 100000)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get customer segments
     */
    public function getSegments(Request $request)
    {
        $segments = [
            'vip_customers' => Customer::where('total_purchases', '>=', 100000)
                ->where('status', 'active')
                ->with(['assignedEmployee'])
                ->get(),
            
            'frequent_buyers' => Customer::where('total_orders', '>=', 10)
                ->where('status', 'active')
                ->orderByDesc('total_orders')
                ->limit(50)
                ->get(),
            
            'new_customers' => Customer::where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at')
                ->get(),
            
            'dormant_customers' => Customer::where('last_purchase_at', '<=', now()->subMonths(6))
                ->whereNotNull('last_purchase_at')
                ->where('status', 'active')
                ->get(),
            
            'at_risk' => Customer::where('last_purchase_at', '<=', now()->subMonths(3))
                ->where('last_purchase_at', '>=', now()->subMonths(6))
                ->where('total_orders', '>=', 3)
                ->where('status', 'active')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $segments
        ]);
    }

    /**
     * Add note to customer
     */
    public function addNote(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::findOrFail($id);
        
        $existingNotes = $customer->notes ? json_decode($customer->notes, true) : [];
        if (!is_array($existingNotes)) {
            $existingNotes = [$existingNotes];
        }
        
        $existingNotes[] = [
            'note' => $request->note,
            'added_by' => Auth::id(),
            'added_at' => now()->toDateTimeString(),
        ];
        
        $customer->notes = json_encode($existingNotes);
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully',
            'data' => $customer
        ]);
    }

    /**
     * Assign employee to customer
     */
    public function assignEmployee(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::findOrFail($id);
        $customer->assigned_employee_id = $request->employee_id;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee assigned successfully',
            'data' => $customer->load('assignedEmployee')
        ]);
    }

    /**
     * Bulk update customer status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'status' => 'required|in:active,inactive,blocked',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        Customer::whereIn('id', $request->customer_ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Customers updated successfully'
        ]);
    }

    /**
     * Search customers
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $customers = Customer::query();
        $this->whereAnyLike($customers, ['name', 'phone', 'email', 'customer_code'], $query);
        $customers = $customers->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
}
