<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpensePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'vendor', 'employee', 'store', 'createdBy', 'approvedBy', 'payments']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->has('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        // Date range
        if ($request->has('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'expense_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->get('per_page', 15);
        $expenses = $query->paginate($perPage);

        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'employee_id' => 'nullable|exists:employees,id',
            'store_id' => 'nullable|exists:stores,id',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date',
            'description' => 'required|string',
            'reference_number' => 'nullable|string',
            'vendor_invoice_number' => 'nullable|string',
            'expense_type' => 'required|in:one_time,recurring',
            'attachments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['created_by'] = Auth::id();
        $data['status'] = 'pending';
        $data['payment_status'] = 'unpaid';
        
        // Calculate total
        $data['total_amount'] = $data['amount'] + ($data['tax_amount'] ?? 0) - ($data['discount_amount'] ?? 0);
        $data['outstanding_amount'] = $data['total_amount'];

        $expense = Expense::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully',
            'data' => $expense->load(['category', 'vendor'])
        ], 201);
    }

    public function show($id)
    {
        $expense = Expense::with([
            'category',
            'vendor',
            'employee',
            'store',
            'createdBy',
            'approvedBy',
            'processedBy',
            'payments'
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $expense]);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        if (in_array($expense->status, ['approved', 'completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update expense in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'amount' => 'sometimes|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'description' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['expense_number', 'status', 'payment_status']);
        
        if (isset($data['amount']) || isset($data['tax_amount']) || isset($data['discount_amount'])) {
            $amount = $data['amount'] ?? $expense->amount;
            $tax = $data['tax_amount'] ?? $expense->tax_amount;
            $discount = $data['discount_amount'] ?? $expense->discount_amount;
            $data['total_amount'] = $amount + $tax - $discount;
            $data['outstanding_amount'] = $data['total_amount'] - $expense->paid_amount;
        }

        $expense->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully',
            'data' => $expense
        ]);
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Can only delete pending expenses'
            ], 400);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
    }

    public function approve(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Expense is not in pending status'
            ], 400);
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense approved successfully',
            'data' => $expense
        ]);
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Expense is not in pending status'
            ], 400);
        }

        $expense->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense rejected successfully',
            'data' => $expense
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Can only add payments to approved expenses'
            ], 400);
        }

        if ($request->amount > $expense->outstanding_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds outstanding amount'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $payment = ExpensePayment::create([
                'expense_id' => $expense->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'processed_by' => Auth::id(),
            ]);

            $expense->paid_amount += $request->amount;
            $expense->outstanding_amount -= $request->amount;
            
            if ($expense->outstanding_amount <= 0) {
                $expense->payment_status = 'paid';
                $expense->status = 'completed';
                $expense->completed_at = now();
            } else {
                $expense->payment_status = 'partial';
            }
            
            $expense->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully',
                'data' => ['expense' => $expense, 'payment' => $payment]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $stats = [
            'total_expenses' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->count(),
            'total_amount' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('total_amount'),
            'total_paid' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('paid_amount'),
            'total_outstanding' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('outstanding_amount'),
            'by_status' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total')
                ->groupBy('status')
                ->get(),
            'by_category' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->selectRaw('expense_categories.name, COUNT(*) as count, SUM(expenses.total_amount) as total')
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'pending_approval' => Expense::where('status', 'pending')->count(),
            'overdue' => Expense::where('due_date', '<', now())
                ->where('payment_status', '!=', 'paid')
                ->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function getOverdue(Request $request)
    {
        $expenses = Expense::with(['category', 'vendor', 'store'])
            ->where('due_date', '<', now())
            ->whereNotIn('payment_status', ['paid'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderBy('due_date', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $expenses]);
    }
}

