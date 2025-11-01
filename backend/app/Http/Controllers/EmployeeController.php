<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'password' => 'required|string|min:8',
            'store_id' => 'required|exists:stores,id',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'employee_code' => 'nullable|string|unique:employees,employee_code',
            'hire_date' => 'nullable|date',
            'department' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'manager_id' => 'nullable|exists:employees,id',
            'is_active' => 'boolean',
            'avatar' => 'nullable|string',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['is_in_service'] = true;

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee->load(['store', 'role', 'manager'])
        ], 201);
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('employees')->ignore($employee->id)],
            'store_id' => 'sometimes|required|exists:stores,id',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'employee_code' => ['sometimes', 'nullable', 'string', Rule::unique('employees')->ignore($employee->id)],
            'hire_date' => 'nullable|date',
            'department' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'manager_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee->id])],
            'avatar' => 'nullable|string',
        ]);

        // Prevent changing own manager to self
        if (isset($validated['manager_id']) && $validated['manager_id'] == $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Employee cannot be their own manager'
            ], 400);
        }

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee->load(['store', 'role', 'manager'])
        ]);
    }

    public function changeEmployeeRole(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $oldRole = $employee->role->title ?? 'No Role';
        $newRole = Role::findOrFail($validated['role_id'])->title;

        $employee->update(['role_id' => $validated['role_id']]);

        return response()->json([
            'success' => true,
            'message' => "Employee role changed from '{$oldRole}' to '{$newRole}'",
            'data' => $employee->load(['store', 'role', 'manager'])
        ]);
    }

    public function transferEmployee(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'new_store_id' => 'required|exists:stores,id',
        ]);

        $newStoreId = $validated['new_store_id'];

        // Check if transferring to the same store
        if ($employee->store_id == $newStoreId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is already assigned to this store'
            ], 400);
        }

        $oldStore = $employee->store->name ?? 'Unknown Store';
        $newStore = Store::findOrFail($newStoreId)->name;

        $employee->update(['store_id' => $newStoreId]);

        return response()->json([
            'success' => true,
            'message' => "Employee transferred from '{$oldStore}' to '{$newStore}'",
            'data' => $employee->load(['store', 'role', 'manager'])
        ]);
    }

    public function deleteEmployee($id)
    {
        $employee = Employee::findOrFail($id);

        // Prevent deleting self
        if ($employee->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 400);
        }

        // Soft delete by setting is_active to false
        $employee->update([
            'is_active' => false,
            'is_in_service' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee deactivated successfully'
        ]);
    }

    public function activateEmployee($id)
    {
        $employee = Employee::findOrFail($id);

        $employee->update([
            'is_active' => true,
            'is_in_service' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee activated successfully',
            'data' => $employee->load(['store', 'role', 'manager'])
        ]);
    }

    public function deactivateEmployee($id)
    {
        $employee = Employee::findOrFail($id);

        // Prevent deactivating self
        if ($employee->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account'
            ], 400);
        }

        $employee->update([
            'is_active' => false,
            'is_in_service' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee deactivated successfully'
        ]);
    }

    public function getEmployees(Request $request)
    {
        $query = Employee::with(['store', 'role', 'manager']);

        // Filters
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('role_id') && $request->role_id) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_in_service')) {
            $query->where('is_in_service', $request->boolean('is_in_service'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSortFields = ['name', 'email', 'employee_code', 'hire_date', 'department', 'salary', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $employees = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    public function getEmployee($id)
    {
        $employee = Employee::with([
            'store',
            'role',
            'manager',
            'subordinates',
            'sessions' => function($query) {
                $query->latest()->limit(5);
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee
        ]);
    }

    public function getEmployeesByStore($storeId)
    {
        $store = Store::findOrFail($storeId);

        $employees = Employee::with(['role', 'manager'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
            'store' => $store
        ]);
    }

    public function getEmployeesByRole($roleId)
    {
        $role = Role::findOrFail($roleId);

        $employees = Employee::with(['store', 'manager'])
            ->where('role_id', $roleId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
            'role' => $role
        ]);
    }

    public function getEmployeeStats()
    {
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'inactive_employees' => Employee::where('is_active', false)->count(),
            'in_service' => Employee::where('is_in_service', true)->count(),
            'by_department' => Employee::where('is_active', true)
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->get(),
            'by_role' => Employee::with('role')
                ->where('is_active', true)
                ->selectRaw('role_id, COUNT(*) as count')
                ->groupBy('role_id')
                ->get()
                ->map(function($item) {
                    return [
                        'role' => $item->role->title ?? 'No Role',
                        'count' => $item->count
                    ];
                }),
            'recent_hires' => Employee::where('is_active', true)
                ->orderBy('hire_date', 'desc')
                ->limit(5)
                ->get(['name', 'hire_date', 'department'])
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function changePassword(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // If changing own password, verify current password
        if ($employee->id == auth()->id() && isset($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $employee->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }
        }

        $employee->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'is_active' => 'required|boolean',
            'is_in_service' => 'boolean',
        ]);

        $count = Employee::whereIn('id', $validated['employee_ids'])
            ->update([
                'is_active' => $validated['is_active'],
                'is_in_service' => $validated['is_in_service'] ?? $validated['is_active']
            ]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$count} employees successfully"
        ]);
    }

    public function getSubordinates($id)
    {
        $employee = Employee::findOrFail($id);

        $subordinates = $employee->subordinates()
            ->with(['store', 'role'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subordinates,
            'manager' => $employee->only(['id', 'name', 'employee_code'])
        ]);
    }
}
