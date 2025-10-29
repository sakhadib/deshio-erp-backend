<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;

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

        $employee = Employee::create($validated);

        return response()->json($employee, 201);
    }
}
