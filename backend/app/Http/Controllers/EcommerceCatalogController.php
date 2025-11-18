<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EcommerceCatalogController extends Controller
{
    /**
     * Get products for e-commerce (public endpoint)
     */
    public function getProducts(Request $request)
    {
        try {
            $perPage = min($request->get('per_page', 12), 50);
            $category = $request->get('category');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $search = $request->get('search');
            $inStock = $request->get('in_stock', true);

            $query = Product::with(['images', 'category'])
                ->where('is_active', true)
                ->where('status', 'active');

            if ($inStock) {
                $query->where('stock_quantity', '>', 0);
            }

            if ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', 'like', "%{$category}%")
                      ->orWhere('slug', $category);
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('tags', 'like', "%{$search}%");
                });
            }

            if ($minPrice) {
                $query->where('selling_price', '>=', $minPrice);
            }

            if ($maxPrice) {
                $query->where('selling_price', '<=', $maxPrice);
            }

            // Sorting
            $allowedSorts = ['created_at', 'name', 'selling_price', 'stock_quantity'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            }

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'description' => $product->description,
                            'short_description' => substr($product->description, 0, 150) . '...',
                            'selling_price' => $product->selling_price,
                            'original_price' => $product->original_price,
                            'discount_percentage' => $product->original_price > 0 
                                ? round((($product->original_price - $product->selling_price) / $product->original_price) * 100, 2) 
                                : 0,
                            'stock_quantity' => $product->stock_quantity,
                            'in_stock' => $product->stock_quantity > 0,
                            'images' => $product->images->take(3)->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->image_url,
                                    'alt_text' => $image->alt_text,
                                    'is_primary' => $image->is_primary,
                                ];
                            }),
                            'category' => $product->category ? [
                                'id' => $product->category->id,
                                'name' => $product->category->name,
                                'slug' => $product->category->slug,
                            ] : null,
                            'tags' => $product->tags ? explode(',', $product->tags) : [],
                            'created_at' => $product->created_at,
                            'is_featured' => $product->is_featured,
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ],
                    'filters_applied' => [
                        'category' => $category,
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice,
                        'search' => $search,
                        'in_stock' => $inStock,
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get products: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single product details (public endpoint)
     */
    public function getProduct(Request $request, $identifier)
    {
        try {
            // Find product by ID or slug
            $product = Product::with(['images', 'category', 'barcodes'])
                ->where('is_active', true)
                ->where('status', 'active')
                ->where(function ($q) use ($identifier) {
                    if (is_numeric($identifier)) {
                        $q->where('id', $identifier);
                    } else {
                        $q->where('slug', $identifier);
                    }
                })
                ->firstOrFail();

            // Get related products
            $relatedProducts = Product::with(['images'])
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_active', true)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->take(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'selling_price' => $product->selling_price,
                        'original_price' => $product->original_price,
                        'discount_percentage' => $product->original_price > 0 
                            ? round((($product->original_price - $product->selling_price) / $product->original_price) * 100, 2) 
                            : 0,
                        'stock_quantity' => $product->stock_quantity,
                        'in_stock' => $product->stock_quantity > 0,
                        'sku' => $product->sku,
                        'weight' => $product->weight,
                        'dimensions' => [
                            'length' => $product->length,
                            'width' => $product->width,
                            'height' => $product->height,
                        ],
                        'images' => $product->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'url' => $image->image_url,
                                'alt_text' => $image->alt_text,
                                'is_primary' => $image->is_primary,
                                'display_order' => $image->display_order,
                            ];
                        }),
                        'category' => $product->category ? [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                            'slug' => $product->category->slug,
                        ] : null,
                        'tags' => $product->tags ? explode(',', $product->tags) : [],
                        'specifications' => $product->specifications ?? [],
                        'care_instructions' => $product->care_instructions,
                        'warranty_info' => $product->warranty_info,
                        'is_featured' => $product->is_featured,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                    ],
                    'related_products' => $relatedProducts->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'selling_price' => $product->selling_price,
                            'original_price' => $product->original_price,
                            'images' => $product->images->take(1),
                            'in_stock' => $product->stock_quantity > 0,
                        ];
                    }),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }
    }

    /**
     * Get categories for e-commerce (public endpoint)
     */
    public function getCategories(Request $request)
    {
        try {
            $cacheKey = 'ecommerce_categories';
            $categories = Cache::remember($cacheKey, 3600, function () {
                return Category::with('children')
                    ->where('is_active', true)
                    ->whereNull('parent_id') // Root categories only
                    ->orderBy('display_order')
                    ->orderBy('name')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'description' => $category->description,
                            'image_url' => $category->image_url,
                            'product_count' => $category->products()->where('is_active', true)->count(),
                            'children' => $category->children->map(function ($child) {
                                return [
                                    'id' => $child->id,
                                    'name' => $child->name,
                                    'slug' => $child->slug,
                                    'product_count' => $child->products()->where('is_active', true)->count(),
                                ];
                            }),
                        ];
                    }),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured products (public endpoint)
     */
    public function getFeaturedProducts(Request $request)
    {
        try {
            $limit = min($request->get('limit', 8), 20);

            $cacheKey = "featured_products_{$limit}";
            $products = Cache::remember($cacheKey, 1800, function () use ($limit) {
                return Product::with(['images', 'category'])
                    ->where('is_active', true)
                    ->where('status', 'active')
                    ->where('is_featured', true)
                    ->where('stock_quantity', '>', 0)
                    ->orderBy('created_at', 'desc')
                    ->take($limit)
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'featured_products' => $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'selling_price' => $product->selling_price,
                            'original_price' => $product->original_price,
                            'discount_percentage' => $product->original_price > 0 
                                ? round((($product->original_price - $product->selling_price) / $product->original_price) * 100, 2) 
                                : 0,
                            'images' => $product->images->take(2),
                            'category' => $product->category ? [
                                'name' => $product->category->name,
                                'slug' => $product->category->slug,
                            ] : null,
                            'in_stock' => $product->stock_quantity > 0,
                        ];
                    }),
                    'total_featured' => $products->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get featured products: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Product search with suggestions (public endpoint)
     */
    public function searchProducts(Request $request)
    {
        try {
            $query = $request->get('q');
            $perPage = min($request->get('per_page', 12), 50);

            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 2 characters',
                ], 400);
            }

            $products = Product::with(['images', 'category'])
                ->where('is_active', true)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('tags', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%");
                })
                ->orderByRaw("CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN name LIKE '%{$query}%' THEN 2
                    WHEN description LIKE '%{$query}%' THEN 3
                    ELSE 4
                END")
                ->paginate($perPage);

            // Get search suggestions
            $suggestions = Product::where('is_active', true)
                ->where('status', 'active')
                ->where('name', 'like', "{$query}%")
                ->pluck('name')
                ->take(5);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'selling_price' => $product->selling_price,
                            'original_price' => $product->original_price,
                            'images' => $product->images->take(1),
                            'category' => $product->category->name ?? null,
                            'in_stock' => $product->stock_quantity > 0,
                        ];
                    }),
                    'suggestions' => $suggestions,
                    'search_query' => $query,
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get price range for filtering (public endpoint)
     */
    public function getPriceRange()
    {
        try {
            $cacheKey = 'product_price_range';
            $priceRange = Cache::remember($cacheKey, 3600, function () {
                $minPrice = Product::where('is_active', true)
                    ->where('status', 'active')
                    ->where('stock_quantity', '>', 0)
                    ->min('selling_price');

                $maxPrice = Product::where('is_active', true)
                    ->where('status', 'active')
                    ->where('stock_quantity', '>', 0)
                    ->max('selling_price');

                return [
                    'min_price' => $minPrice ?? 0,
                    'max_price' => $maxPrice ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $priceRange,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get price range: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get new arrivals (public endpoint)
     */
    public function getNewArrivals(Request $request)
    {
        try {
            $limit = min($request->get('limit', 8), 20);
            $days = $request->get('days', 30); // Products added in last 30 days

            $products = Product::with(['images', 'category'])
                ->where('is_active', true)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'new_arrivals' => $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'selling_price' => $product->selling_price,
                            'original_price' => $product->original_price,
                            'images' => $product->images->take(2),
                            'category' => $product->category->name ?? null,
                            'added_days_ago' => $product->created_at->diffInDays(now()),
                        ];
                    }),
                    'total_new_arrivals' => $products->count(),
                    'days_range' => $days,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get new arrivals: ' . $e->getMessage(),
            ], 500);
        }
    }
}