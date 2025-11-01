<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoriesController extends Controller
{
    public function createCategory(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7', // hex color
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        // Generate slug from title if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        // Update slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $category->title) {
            $newSlug = Str::slug($validated['title']);
            $originalSlug = $newSlug;
            $counter = 1;
            while (Category::where('slug', $newSlug)->where('id', '!=', $category->id)->exists()) {
                $newSlug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $newSlug;
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has products
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated products'
            ], 400);
        }

        // Soft delete by setting is_active to false
        $category->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Category deactivated successfully'
        ]);
    }

    public function activateCategory($id)
    {
        $category = Category::findOrFail($id);

        $category->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Category activated successfully',
            'data' => $category
        ]);
    }

    public function deactivateCategory($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has active products
        if ($category->activeProducts()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate category with active products'
            ], 400);
        }

        $category->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Category deactivated successfully'
        ]);
    }

    public function getCategories(Request $request)
    {
        $query = Category::query();

        // Filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'order');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSortFields = ['title', 'slug', 'order', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $categories = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function getCategory($id)
    {
        $category = Category::with([
            'activeProducts' => function($query) {
                $query->select('id', 'name', 'category_id', 'is_active')->limit(10);
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    public function getCategoryStats()
    {
        $stats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'inactive_categories' => Category::where('is_active', false)->count(),
            'categories_with_products' => Category::whereHas('products')->count(),
            'total_products_by_category' => Category::withCount('products')
                ->where('is_active', true)
                ->orderBy('products_count', 'desc')
                ->take(10)
                ->get(['title', 'products_count']),
            'recent_categories' => Category::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['title', 'slug', 'is_active', 'created_at'])
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'is_active' => 'required|boolean',
        ]);

        // Check if any categories have active products when deactivating
        if (!$validated['is_active']) {
            $categoriesWithProducts = Category::whereIn('id', $validated['category_ids'])
                ->whereHas('activeProducts')
                ->pluck('title')
                ->toArray();

            if (!empty($categoriesWithProducts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate categories with active products: ' . implode(', ', $categoriesWithProducts)
                ], 400);
            }
        }

        $count = Category::whereIn('id', $validated['category_ids'])
            ->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$count} categories successfully"
        ]);
    }

    public function reorderCategories(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['order' => $categoryData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    }
}
