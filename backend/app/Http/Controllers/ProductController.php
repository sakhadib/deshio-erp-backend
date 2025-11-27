<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Field;
use App\Models\ProductField;
use App\Models\Category;
use App\Models\Vendor;
use App\Traits\DatabaseAgnosticSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use DatabaseAgnosticSearch;
    /**
     * Get all products with filters and custom fields
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'vendor', 'productFields.field', 'images' => function($q) {
            $q->where('is_active', true)->orderBy('is_primary', 'desc')->orderBy('sort_order');
        }]);

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('is_archived')) {
            $query->where('is_archived', $request->boolean('is_archived'));
        } else {
            $query->where('is_archived', false); // Default to active products
        }

        if ($request->has('search')) {
            $search = $request->search;
            $this->whereAnyLike($query, ['name', 'sku', 'description'], $search);
        }

        // Search by custom field value
        if ($request->has('field_search')) {
            $fieldId = $request->input('field_id');
            $fieldValue = $request->input('field_search');
            
            $query->whereHas('productFields', function($q) use ($fieldId, $fieldValue) {
                if ($fieldId) {
                    $q->where('field_id', $fieldId);
                }
                $this->whereLike($q, 'value', $fieldValue);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = ['name', 'sku', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $products = $query->paginate($request->get('per_page', 15));

        // Transform to include formatted custom fields
        foreach ($products as $product) {
            $product->custom_fields = $this->formatCustomFields($product);
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get single product with all details
     */
    public function show($id)
    {
        $product = Product::with([
            'category',
            'vendor',
            'productFields.field',
            'images',
            'barcodes',
            'batches.store',
            'priceOverrides'
        ])->findOrFail($id);

        $product->custom_fields = $this->formatCustomFields($product);
        
        // Include inventory summary
        $product->inventory_summary = [
            'total_quantity' => $product->getTotalInventory(),
            'available_batches' => $product->availableBatches()->count(),
            'lowest_price' => $product->getLowestBatchPrice(),
            'highest_price' => $product->getHighestBatchPrice(),
            'average_price' => $product->getAverageBatchPrice(),
        ];

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Create product with custom fields
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'required|exists:vendors,id',
            'brand' => 'required|string|max:255',
            'sku' => 'required|string', // SKU not unique - supports variations
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.field_id' => 'required|exists:fields,id|distinct', // Prevent duplicate field_ids
            'custom_fields.*.value' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            // Create product
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'vendor_id' => $validated['vendor_id'],
                'brand' => $validated['brand'],
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_archived' => false,
            ]);

            // Add custom fields if provided
            if (isset($validated['custom_fields'])) {
                foreach ($validated['custom_fields'] as $fieldData) {
                    $field = Field::findOrFail($fieldData['field_id']);
                    
                    // Validate field value against field type
                    $this->validateFieldValue($field, $fieldData['value'] ?? null);
                    
                    // Use updateOrCreate to handle potential duplicates in request
                    ProductField::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'field_id' => $field->id,
                        ],
                        [
                            'value' => $this->formatFieldValue($field, $fieldData['value'] ?? null),
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load('productFields.field', 'category', 'vendor')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'vendor_id' => 'sometimes|exists:vendors,id',
            'brand' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string', // SKU not unique - supports variations
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.field_id' => 'required|exists:fields,id|distinct', // Prevent duplicate field_ids
            'custom_fields.*.value' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            // Update basic product info
            $product->update([
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'vendor_id' => $validated['vendor_id'] ?? $product->vendor_id,
                'brand' => $validated['brand'] ?? $product->brand,
                'sku' => $validated['sku'] ?? $product->sku,
                'name' => $validated['name'] ?? $product->name,
                'description' => $validated['description'] ?? $product->description,
            ]);

            // Update custom fields if provided
            if (isset($validated['custom_fields'])) {
                foreach ($validated['custom_fields'] as $fieldData) {
                    $field = Field::findOrFail($fieldData['field_id']);
                    
                    // Validate field value
                    $this->validateFieldValue($field, $fieldData['value'] ?? null);
                    
                    ProductField::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'field_id' => $field->id,
                        ],
                        [
                            'value' => $this->formatFieldValue($field, $fieldData['value'] ?? null),
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load('productFields.field', 'category', 'vendor')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update specific custom field
     */
    public function updateCustomField(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'value' => 'nullable',
        ]);

        $field = Field::findOrFail($validated['field_id']);
        
        // Validate field value
        $this->validateFieldValue($field, $validated['value'] ?? null);

        ProductField::updateOrCreate(
            [
                'product_id' => $product->id,
                'field_id' => $field->id,
            ],
            [
                'value' => $this->formatFieldValue($field, $validated['value'] ?? null),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Custom field updated successfully',
            'data' => $product->load('productFields.field')
        ]);
    }

    /**
     * Remove custom field from product
     */
    public function removeCustomField($id, $fieldId)
    {
        $product = Product::findOrFail($id);
        
        $deleted = ProductField::where('product_id', $product->id)
            ->where('field_id', $fieldId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Custom field not found on this product'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Custom field removed successfully'
        ]);
    }

    /**
     * Archive product (soft delete)
     */
    public function archive($id)
    {
        $product = Product::findOrFail($id);
        $product->is_archived = true;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product archived successfully'
        ]);
    }

    /**
     * Restore archived product
     */
    public function restore($id)
    {
        $product = Product::findOrFail($id);
        $product->is_archived = false;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product restored successfully',
            'data' => $product
        ]);
    }

    /**
     * Permanently delete product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Check if product has batches or orders
        if ($product->batches()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product with existing batches. Archive it instead.'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get available fields for products
     */
    public function getAvailableFields()
    {
        $fields = Field::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fields
        ]);
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:archive,restore,update_category,update_vendor',
            'category_id' => 'required_if:action,update_category|exists:categories,id',
            'vendor_id' => 'required_if:action,update_vendor|exists:vendors,id',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;

            switch ($validated['action']) {
                case 'archive':
                    $count = Product::whereIn('id', $validated['product_ids'])
                        ->update(['is_archived' => true]);
                    $message = "Archived {$count} products";
                    break;

                case 'restore':
                    $count = Product::whereIn('id', $validated['product_ids'])
                        ->update(['is_archived' => false]);
                    $message = "Restored {$count} products";
                    break;

                case 'update_category':
                    $count = Product::whereIn('id', $validated['product_ids'])
                        ->update(['category_id' => $validated['category_id']]);
                    $message = "Updated category for {$count} products";
                    break;

                case 'update_vendor':
                    $count = Product::whereIn('id', $validated['product_ids'])
                        ->update(['vendor_id' => $validated['vendor_id']]);
                    $message = "Updated vendor for {$count} products";
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product statistics
     */
    public function getStatistics(Request $request)
    {
        $query = Product::query();

        // Date filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_archived', false)->count(),
            'archived_products' => Product::where('is_archived', true)->count(),
            'by_category' => Product::where('is_archived', false)
                ->with('category:id,name')
                ->get()
                ->groupBy('category_id')
                ->map(function($group) {
                    return [
                        'category' => $group->first()->category->name ?? 'Uncategorized',
                        'count' => $group->count()
                    ];
                })
                ->values(),
            'by_vendor' => Product::where('is_archived', false)
                ->with('vendor:id,name')
                ->get()
                ->groupBy('vendor_id')
                ->map(function($group) {
                    return [
                        'vendor' => $group->first()->vendor->name ?? 'Unknown',
                        'count' => $group->count()
                    ];
                })
                ->values(),
            'recently_added' => Product::where('is_archived', false)
                ->with('category', 'vendor')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'total_inventory_value' => $this->calculateInventoryValue(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Search products by custom field value
     */
    public function searchByCustomField(Request $request)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'value' => 'required',
            'operator' => 'nullable|in:=,like,>,<,>=,<=',
        ]);

        $operator = $validated['operator'] ?? 'like';
        $value = $operator === 'like' ? "%{$validated['value']}%" : $validated['value'];

        $products = Product::whereHas('productFields', function($q) use ($validated, $operator, $value) {
            $q->where('field_id', $validated['field_id'])
              ->where('value', $operator, $value);
        })
        ->with(['category', 'vendor', 'productFields.field', 'images' => function($q) {
            $q->where('is_active', true)->orderBy('is_primary', 'desc')->orderBy('sort_order');
        }])
        ->where('is_archived', false)
        ->paginate($request->get('per_page', 15));

        foreach ($products as $product) {
            $product->custom_fields = $this->formatCustomFields($product);
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Helper: Format custom fields for display
     */
    private function formatCustomFields($product)
    {
        return $product->productFields->map(function($productField) {
            return [
                'field_id' => $productField->field_id,
                'field_title' => $productField->field->title,
                'field_type' => $productField->field->type,
                'value' => $productField->parsed_value,
                'raw_value' => $productField->value,
            ];
        });
    }

    /**
     * Helper: Validate field value based on field type
     */
    private function validateFieldValue($field, $value)
    {
        // Required field check
        if ($field->is_required && empty($value)) {
            throw new \InvalidArgumentException("Field '{$field->title}' is required");
        }

        // Skip validation if value is null/empty and field is not required
        if (empty($value) && !$field->is_required) {
            return;
        }

        // Type-specific validation
        switch ($field->type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email format for field '{$field->title}'");
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException("Invalid URL format for field '{$field->title}'");
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException("Field '{$field->title}' must be a number");
                }
                break;

            case 'date':
                if (!strtotime($value)) {
                    throw new \InvalidArgumentException("Invalid date format for field '{$field->title}'");
                }
                break;

            case 'select':
            case 'radio':
                if ($field->hasOptions() && !in_array($value, $field->options)) {
                    throw new \InvalidArgumentException("Invalid option for field '{$field->title}'");
                }
                break;

            case 'checkbox':
                if ($field->hasOptions()) {
                    $values = is_array($value) ? $value : json_decode($value, true);
                    foreach ($values as $val) {
                        if (!in_array($val, $field->options)) {
                            throw new \InvalidArgumentException("Invalid option '{$val}' for field '{$field->title}'");
                        }
                    }
                }
                break;
        }
    }

    /**
     * Helper: Format field value for storage
     */
    private function formatFieldValue($field, $value)
    {
        if (empty($value)) {
            return null;
        }

        switch ($field->type) {
            case 'boolean':
                return $value ? 'true' : 'false';

            case 'json':
            case 'checkbox':
                return is_array($value) ? json_encode($value) : $value;

            case 'number':
                return (string) $value;

            default:
                return (string) $value;
        }
    }

    /**
     * Helper: Calculate total inventory value
     */
    private function calculateInventoryValue()
    {
        return DB::table('product_batches')
            ->join('products', 'product_batches.product_id', '=', 'products.id')
            ->where('products.is_archived', false)
            ->where('product_batches.quantity', '>', 0)
            ->selectRaw('SUM(product_batches.quantity * product_batches.cost_price) as total_value')
            ->value('total_value') ?? 0;
    }
}
