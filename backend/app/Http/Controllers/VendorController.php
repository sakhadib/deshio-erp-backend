<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function createVendor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'type' => 'required|string|in:manufacturer,distributor',
            'email' => 'nullable|email|unique:vendors,email',
            'contact_person' => 'nullable|string',
            'website' => 'nullable|url',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201);
    }

    public function updateVendor(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'type' => 'sometimes|required|string|in:manufacturer,distributor',
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('vendors')->ignore($vendor->id)],
            'contact_person' => 'nullable|string',
            'website' => 'nullable|url',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $vendor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ]);
    }

    public function deleteVendor($id)
    {
        $vendor = Vendor::findOrFail($id);

        // Soft delete by setting is_active to false
        $vendor->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor deactivated successfully'
        ]);
    }

    public function activateVendor($id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor activated successfully',
            'data' => $vendor
        ]);
    }

    public function deactivateVendor($id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor deactivated successfully'
        ]);
    }

    public function getVendors(Request $request)
    {
        $query = Vendor::query();

        // Filters
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSortFields = ['name', 'email', 'type', 'credit_limit', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $vendors = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $vendors
        ]);
    }

    public function getVendor($id)
    {
        $vendor = Vendor::with(['products' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }

    public function getVendorsByType($type)
    {
        $vendors = Vendor::where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vendors,
            'type' => $type
        ]);
    }

    public function getVendorStats()
    {
        $stats = [
            'total_vendors' => Vendor::count(),
            'active_vendors' => Vendor::where('is_active', true)->count(),
            'inactive_vendors' => Vendor::where('is_active', false)->count(),
            'by_type' => Vendor::where('is_active', true)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'total_credit_limit' => Vendor::where('is_active', true)->sum('credit_limit'),
            'recent_vendors' => Vendor::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['name', 'type', 'created_at'])
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'vendor_ids' => 'required|array',
            'vendor_ids.*' => 'exists:vendors,id',
            'is_active' => 'required|boolean',
        ]);

        $count = Vendor::whereIn('id', $validated['vendor_ids'])
            ->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$count} vendors successfully"
        ]);
    }
}
