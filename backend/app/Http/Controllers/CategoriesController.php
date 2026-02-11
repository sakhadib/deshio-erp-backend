<?php

namespace App\Http\Controllers;



























































































































































































































































































































































































































**Status:** ✅ Complete**Migration File:** `2026_02_11_143633_add_tax_percentage_to_categories_table.php`  **Migration Date:** February 11, 2026  ---For questions about the migration, contact the backend team.### 📞 Support5. Train team on new tax system4. Update product documentation3. (Optional) Clear batch-level taxes2. Update frontend category forms to include tax field1. Set tax_percentage for all categories### 📋 Action Items- **Easy Updates:** Change category tax affects all products- **Backward Compatible:** Old data still works- **Flexibility:** Can still use batch tax as fallback- **Simplicity:** Set tax once at category level- **Consistency:** All products in a category have same tax### ✅ What We Achieved## Summary```WHERE id = ?;SET tax_percentage = 15.0 UPDATE categories ```sqlIf category has no tax, set it:```WHERE p.id = ?;LEFT JOIN categories c ON c.id = p.category_idFROM products p    c.tax_percentage as category_tax    c.title as category,    p.name as product,SELECT -- Check product's category and its tax```sql**Verify:**### Issue: Category tax not applying```echo "Effective Tax: " . $batch->getEffectiveTax();echo "Batch Tax: " . ($batch->tax_percentage ?? 'none');echo "Category Tax: " . ($batch->product->category->tax_percentage ?? 'none');$batch = ProductBatch::with('product.category')->find($batchId);```php**Debug:**3. Does batch have tax_percentage?2. What's the category's tax_percentage?1. What's the product's category?**Check:**### Issue: Orders showing wrong tax## Troubleshooting```</InfoText>  {product.category.tax_percentage}%  Tax will be inherited from category: <InfoText>// ✅ Show inherited tax as read-only info<FormField label="Tax %" name="tax_percentage" />// ❌ Remove this field```jsxDon't show tax input on batch creation (inherited from category):### Batch Creation```</div>  Tax: {product.category.tax_percentage}%  Category: {product.category.title}  Product: {product.name}<div>```jsxShow inherited tax on product details:### Product Display```/>  onChange={handleTaxChange}  value={category.tax_percentage}  step="0.01"  max="100"  min="0"  type="number"  label="Tax Percentage (%)"<FormField```jsxShow tax percentage on category management screens:### Display Category Tax## Frontend Impact```GROUP BY c.id, c.title, c.tax_percentage;WHERE o.created_at >= '2026-01-01'JOIN categories c ON c.id = p.category_idJOIN products p ON p.id = oi.product_idJOIN order_items oi ON oi.order_id = o.idFROM orders o    SUM(o.tax_amount) as total_tax_collected    c.tax_percentage,    c.title as category,SELECT ```sql**Tax by Category:**### Reporting   All future orders will use new rate automatically.   ```   WHERE tax_percentage = 15.0;   SET tax_percentage = 18.0    UPDATE categories    ```sql3. **Update category tax when government rates change:**   - Consistent pricing   - Simpler to manage   - Let batches inherit from category2. **Don't set batch-level tax anymore:**   ```   Services → 0%   Food → 5%   Clothing → 10%   Electronics → 15%   ```1. **Define tax at category level:**### Setting Up Tax## Best Practices- Category always takes priority when set- System works with mix of category and batch taxes- Can set category taxes gradually### ✅ Gradual Migration- No data loss- If category tax = 0, batch tax is used as fallback- Batches with batch-level tax still work### ✅ Existing Batches- Tax amounts already calculated and stored- Orders created before migration remain unchanged### ✅ Existing Orders## Backward Compatibility```- New order: 15% tax (uses current category tax)- Old order: 10% tax (frozen at creation time)Expected:3. Create new order with same product2. Update category tax to 15%1. Create order with product (category tax: 10%)Action:```### Test 4: Category Update Reflects Immediately```- Order calculates tax at 0% (no tax)Expected:- Batch: has tax_percentage = 0%- Product: Consultation (category: Services)- Category: Services (tax: 0%)Setup:```### Test 3: No Tax```- Order calculates tax at 10% (batch fallback)Expected:- Batch: has tax_percentage = 10%- Product: Chair (category: Furniture)- Category: Furniture (tax: 0%)Setup:```### Test 2: Batch Tax Fallback```- Order calculates tax at 15% (category wins)Expected:- Batch: has tax_percentage = 5%- Product: Laptop (category: Electronics)- Category: Electronics (tax: 15%)Setup:```### Test 1: Category Tax Priority## Testing Scenarios3. Don't set tax at batch level anymore2. All products under that category automatically inherit the tax1. Set tax percentage at **category level** when creating/updating category### For New Products**Note:** This is optional. If batch tax exists, category tax still takes priority.```SET tax_percentage = 0;UPDATE product_batches -- Clear batch tax (category tax will be used)```sqlOnce category taxes are set, you can optionally clear batch-level taxes:#### Step 3: (Optional) Clear Batch TaxCategory tax automatically applies to all products in that category.#### Step 2: Verify Tax Application```WHERE slug = 'food';SET tax_percentage = 5.0 UPDATE categories -- Example: Set 5% tax for Food categoryWHERE slug = 'electronics';SET tax_percentage = 15.0 UPDATE categories -- Example: Set 15% tax for Electronics category```sqlUpdate each category with appropriate tax percentage:#### Step 1: Set Category Tax### For Existing Data## Migration Path```}  }    "created_at": "2026-02-11T14:36:33.000000Z"    "tax_percentage": "15.00",    "title": "Electronics",    "id": 1,  "category": {  "success": true,{```json#### Response```}  "tax_percentage": 18.0{Content-Type: application/jsonPUT /api/categories/{id}```http#### Update Category```}  "tax_percentage": 15.0  "description": "Electronic products",  "title": "Electronics",{Content-Type: application/jsonPOST /api/categories```http#### Create Category### Category Endpoints## API Changes```'tax_percentage' => 'nullable|numeric|min:0|max:100',```php**Added Validation:****File:** `app/Http/Controllers/CategoriesController.php`### 4. Categories Controller```$taxPercentage = $batch->getEffectiveTax();```php**Changed To:**```$taxPercentage = $batch->tax_percentage ?? 0;```php**Changed From:**- `app/Http/Controllers/EcommerceOrderController.php`- `app/Http/Controllers/GuestCheckoutController.php`- `app/Http/Controllers/OrderController.php`**Updated Files:**### 3. Order Controllers- Recalculates when `product_id` changes (category might change)- `calculateTaxFields()` now uses `getEffectiveTax()`**Updated:**```}    return (float) ($this->tax_percentage ?? 0);    // Fall back to batch tax    }        return (float) $this->product->category->tax_percentage;        $this->product->category->tax_percentage > 0) {        $this->product->category &&     if ($this->product &&     // Check category tax first (priority){public function getEffectiveTax(): float */ * Priority: Category tax > Batch tax * Get effective tax percentage for this batch/**```php**New Method:****File:** `app/Models/ProductBatch.php`### 2. ProductBatch Model```];    'tax_percentage' => 'decimal:2',    // ... existing castsprotected $casts = [];    'tax_percentage',    // ... existing fieldsprotected $fillable = [```php**Added:****File:** `app/Models/Category.php`### 1. Category Model## Code Changes```Effective tax: 0%   (No tax)─────────────────Batch tax: 0%Category tax: 0%Effective tax: 5%   (Fallback to batch)─────────────────Batch tax: 5%Category tax: 0%Effective tax: 15%  (Category wins)─────────────────Batch tax: 5%Category tax: 15%```php**Example:**3. Else → No tax (0%)2. Else if batch has `tax_percentage > 0` → Use batch tax (fallback)1. If category has `tax_percentage > 0` → **Use category tax****For backward compatibility:**### Priority Rule: Category Tax > Batch Tax## Tax Priority System- **Status:** ✅ Executed- **File:** `2026_02_11_143633_add_tax_percentage_to_categories_table.php`### Migration File```COMMENT 'Tax percentage for all products in this category';ADD COLUMN tax_percentage DECIMAL(5,2) DEFAULT 0 ALTER TABLE categories ```sql### New Field in Categories Table## Database Changes```✅ All products in category have same tax rate = consistent  └─ Product C → All batches: 15%  ├─ Product B → All batches: 15%  ├─ Product A → All batches: 15%Category: Electronics (tax: 15%)```### After (Category-Level Tax) ✅```❌ Same product, different batches, different tax rates = inconsistentProduct A → Batch 3 (tax: 3%)Product A → Batch 2 (tax: 5%)Product A → Batch 1 (tax: 2%)```### Before (Batch-Level Tax) ❌## What ChangedTax percentage has been **migrated from batch-level to category-level**. All products under a category now inherit the category's tax percentage.
use App\Models\Category;
use App\Traits\DatabaseAgnosticSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoriesController extends Controller
{
    use DatabaseAgnosticSearch;
    public function createCategory(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // max 2MB
            'color' => 'nullable|string|max:7', // hex color
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|exists:categories,id',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Validate parent category doesn't create circular reference
        if (isset($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent category not found'
                ], 404);
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($validated['title']) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('categories', $imageName, 'public');
            $validated['image'] = $imagePath;
        }

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

        // Load relationships
        $category->load('parent', 'children');

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // max 2MB
            'remove_image' => 'nullable|boolean', // flag to remove existing image
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|exists:categories,id',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Prevent setting self as parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'Category cannot be its own parent'
            ], 400);
        }

        // Prevent circular reference (setting a descendant as parent)
        if (isset($validated['parent_id'])) {
            $descendants = $category->descendants();
            if ($descendants->contains('id', $validated['parent_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot set a descendant category as parent (circular reference)'
                ], 400);
            }
        }

        // Handle image removal
        if ($request->has('remove_image') && $request->remove_image == true) {
            if ($category->image) {
                \Storage::disk('public')->delete($category->image);
                $validated['image'] = null;
            }
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image) {
                \Storage::disk('public')->delete($category->image);
            }
            
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($validated['title'] ?? $category->title) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('categories', $imageName, 'public');
            $validated['image'] = $imagePath;
        }

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

        // Load relationships
        $category->load('parent', 'children');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has children
        if ($category->hasChildren()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with subcategories. Delete or move subcategories first.'
            ], 400);
        }

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

        // Filter by parent_id (null for root categories)
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Get hierarchical tree structure
        if ($request->boolean('tree')) {
            $categories = $query->with('allChildren')->whereNull('parent_id')->get();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $this->whereAnyLike($query, ['title', 'description', 'slug'], $search);
        }

        // Load relationships
        $query->with(['parent', 'children']);

        // Sorting
        $sortBy = $request->get('sort_by', 'order');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSortFields = ['title', 'slug', 'order', 'created_at', 'level'];
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
            'parent',
            'children.children',
            'activeProducts' => function($query) {
                $query->select('id', 'name', 'category_id', 'is_active')->limit(10);
            }
        ])->findOrFail($id);

        // Add full path and additional info
        $categoryArray = $category->toArray();
        $categoryArray['full_path'] = $category->getFullPath();
        $categoryArray['ancestors'] = $category->ancestors()->toArray();
        $categoryArray['has_children'] = $category->hasChildren();
        $categoryArray['is_root'] = $category->isRoot();

        return response()->json([
            'success' => true,
            'data' => $categoryArray
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

    // Nested category specific endpoints

    public function getCategoryTree(Request $request)
    {
        $query = Category::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Get all root categories with nested children
        $categories = $query->with('allChildren')->whereNull('parent_id')->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function getRootCategories(Request $request)
    {
        $query = Category::rootCategories();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->with('children')->orderBy('order');

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function getSubcategories($parentId)
    {
        $parent = Category::findOrFail($parentId);

        $subcategories = $parent->children()
            ->with('children')
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'parent' => $parent,
                'subcategories' => $subcategories
            ]
        ]);
    }

    public function moveCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'new_parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::findOrFail($id);

        // Prevent setting self as parent
        if (isset($validated['new_parent_id']) && $validated['new_parent_id'] == $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'Category cannot be its own parent'
            ], 400);
        }

        // Prevent circular reference
        if (isset($validated['new_parent_id'])) {
            $descendants = $category->descendants();
            if ($descendants->contains('id', $validated['new_parent_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move category under its own descendant'
                ], 400);
            }
        }

        $category->update(['parent_id' => $validated['new_parent_id']]);
        $category->load('parent', 'children');

        return response()->json([
            'success' => true,
            'message' => 'Category moved successfully',
            'data' => $category
        ]);
    }

    public function getCategoryBreadcrumb($id)
    {
        $category = Category::findOrFail($id);

        $breadcrumb = $category->ancestors()->reverse()->values();
        $breadcrumb->push($category);

        return response()->json([
            'success' => true,
            'data' => $breadcrumb
        ]);
    }

    public function getCategoryDescendants($id)
    {
        $category = Category::findOrFail($id);

        $descendants = $category->descendants();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'descendants' => $descendants,
                'total_descendants' => $descendants->count()
            ]
        ]);
    }

    /**
     * Permanently delete a category (hard delete)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function hardDeleteCategory($id)
    {
        // Find category including soft deleted ones
        $category = Category::withTrashed()->findOrFail($id);

        // Check if category has children (including soft deleted)
        $childrenCount = Category::withTrashed()->where('parent_id', $id)->count();
        if ($childrenCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot permanently delete category with subcategories. Delete subcategories first.'
            ], 400);
        }

        // Check if category has products
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot permanently delete category with associated products. Remove products first.'
            ], 400);
        }

        // Delete image if exists
        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        // Permanently delete the category
        $categoryTitle = $category->title;
        $category->forceDelete();

        return response()->json([
            'success' => true,
            'message' => "Category '{$categoryTitle}' has been permanently deleted"
        ]);
    }
}
