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

            $query = Product::with(['images', 'category', 'batches' => function ($q) {
                    $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
                }])
                ->where('is_archived', false);

            if ($inStock) {
                $query->whereHas('batches', function ($q) {
                    $q->where('quantity', '>', 0);
                });
            }

            if ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('title', 'like', "%{$category}%");
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Price filtering needs to be done on the collection after loading batches
            // Sorting
            $allowedSorts = ['created_at', 'name'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            }

            $products = $query->paginate($perPage);

            // Filter by price if needed (after loading products with batches)
            $filteredProducts = collect($products->items())->filter(function ($product) use ($minPrice, $maxPrice) {
                $lowestPrice = $product->batches->min('sell_price');
                
                if ($minPrice && $lowestPrice < $minPrice) {
                    return false;
                }
                
                if ($maxPrice && $lowestPrice > $maxPrice) {
                    return false;
                }
                
                return true;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $filteredProducts->values()->map(function ($product) {
                        $lowestBatch = $product->batches->sortBy('sell_price')->first();
                        $totalStock = $product->batches->sum('quantity');
                        
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'description' => $product->description,
                            'short_description' => $product->description ? (strlen($product->description) > 150 ? substr($product->description, 0, 150) . '...' : $product->description) : null,
                            'selling_price' => $lowestBatch ? $lowestBatch->sell_price : 0,
                            'cost_price' => $lowestBatch ? $lowestBatch->cost_price : 0,
                            'stock_quantity' => $totalStock,
                            'in_stock' => $totalStock > 0,
                            'images' => $product->images->where('is_active', true)->take(3)->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->image_url,
                                    'alt_text' => $image->alt_text,
                                    'is_primary' => $image->is_primary,
                                ];
                            }),
                            'category' => $product->category ? [
                                'id' => $product->category->id,
                                'name' => $product->category->title,
                            ] : null,
                            'created_at' => $product->created_at,
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $filteredProducts->count(), // Fixed: Use filtered count
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
            // Find product by ID
            $product = Product::with(['images', 'category', 'barcodes', 'batches' => function ($q) {
                    $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
                }])
                ->where('is_archived', false)
                ->where('id', $identifier)
                ->firstOrFail();

            // Get related products
            $relatedProducts = Product::with(['images', 'batches' => function ($q) {
                    $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
                }])
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_archived', false)
                ->whereHas('batches', function ($q) {
                    $q->where('quantity', '>', 0);
                })
                ->take(6)
                ->get();

            $lowestBatch = $product->batches->sortBy('sell_price')->first();
            $totalStock = $product->batches->sum('quantity');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'description' => $product->description,
                        'selling_price' => $lowestBatch ? $lowestBatch->sell_price : 0,
                        'cost_price' => $lowestBatch ? $lowestBatch->cost_price : 0,
                        'stock_quantity' => $totalStock,
                        'in_stock' => $totalStock > 0,
                        'images' => $product->images->where('is_active', true)->map(function ($image) {
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
                            'name' => $product->category->title,
                        ] : null,
                        'vendor' => $product->vendor ? [
                            'id' => $product->vendor->id,
                            'name' => $product->vendor->business_name,
                        ] : null,
                        'batches' => $product->batches->map(function ($batch) {
                            return [
                                'id' => $batch->id,
                                'sell_price' => $batch->sell_price,
                                'quantity' => $batch->quantity,
                                'store_id' => $batch->store_id,
                            ];
                        }),
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                    ],
                    'related_products' => $relatedProducts->map(function ($product) {
                        $lowestBatch = $product->batches->sortBy('sell_price')->first();
                        $totalStock = $product->batches->sum('quantity');
                        
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'selling_price' => $lowestBatch ? $lowestBatch->sell_price : 0,
                            'images' => $product->images->where('is_active', true)->take(1),
                            'in_stock' => $totalStock > 0,
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
                    ->orderBy('order', 'asc')
                    ->orderBy('title', 'asc')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->title,
                            'description' => $category->description,
                            'image' => $category->image,
                            'image_url' => $category->image_url,
                            'color' => $category->color,
                            'icon' => $category->icon,
                            'product_count' => $category->products()->count(),
                            'children' => $category->children->where('is_active', true)->map(function ($child) {
                                return [
                                    'id' => $child->id,
                                    'name' => $child->title,
                                    'image' => $child->image,
                                    'image_url' => $child->image_url,
                                    'color' => $child->color,
                                    'icon' => $child->icon,
                                    'product_count' => $child->products()->count(),
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
     * Note: Since products table doesn't have is_featured, returning newest products with stock
     */
    public function getFeaturedProducts(Request $request)
    {
        try {
            $limit = min($request->get('limit', 8), 20);

            $cacheKey = "featured_products_{$limit}";
            $products = Cache::remember($cacheKey, 1800, function () use ($limit) {
                return Product::with(['images', 'category', 'batches' => function ($q) {
                        $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
                    }])
                    ->where('is_archived', false)
                    ->whereHas('batches', function ($q) {
                        $q->where('quantity', '>', 0);
                    })
                    ->orderBy('created_at', 'desc')
                    ->take($limit)
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'featured_products' => $products->map(function ($product) {
                        $lowestBatch = $product->batches->sortBy('sell_price')->first();
                        $totalStock = $product->batches->sum('quantity');
                        
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'selling_price' => $lowestBatch ? $lowestBatch->sell_price : 0,
                            'images' => $product->images->where('is_active', true)->take(2)->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->image_url,
                                    'alt_text' => $image->alt_text,
                                    'is_primary' => $image->is_primary,
                                ];
                            }),
                            'category' => $product->category ? [
                                'name' => $product->category->title,
                            ] : null,
                            'in_stock' => $totalStock > 0,
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

            $products = Product::with(['images', 'category', 'batches' => function ($q) {
                    $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
                }])
                ->where('is_archived', false)
                ->whereHas('batches', function ($q) {
                    $q->where('quantity', '>', 0);
                })
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
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
            $suggestions = Product::where('is_archived', false)
                ->where('name', 'like', "{$query}%")
                ->pluck('name')
                ->take(5);

            $transformedProducts = collect($products->items())->map(function ($product) {
                $lowestBatch = $product->batches->sortBy('sell_price')->first();
                $totalStock = $product->batches->sum('quantity');
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $lowestBatch ? $lowestBatch->sell_price : 0,
                    'images' => $product->images->where('is_active', true)->take(1),
                    'category' => $product->category->title ?? null,
                    'in_stock' => $totalStock > 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $transformedProducts,
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
                $minPrice = \App\Models\ProductBatch::where('quantity', '>', 0)
                    ->min('sell_price');

                $maxPrice = \App\Models\ProductBatch::where('quantity', '>', 0)
                    ->max('sell_price');

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

            $products = Product::with(['images', 'category', 'batches' => function ($q) {
                    $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
                }])
                ->where('is_archived', false)
                ->whereHas('batches', function ($q) {
                    $q->where('quantity', '>', 0);
                })
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'new_arrivals' => $products->map(function ($product) {
                        $lowestBatch = $product->batches->sortBy('sell_price')->first();
                        
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'selling_price' => $lowestBatch ? $lowestBatch->sell_price : 0,
                            'images' => $product->images->where('is_active', true)->take(2)->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->image_url,
                                    'alt_text' => $image->alt_text,
                                    'is_primary' => $image->is_primary,
                                ];
                            }),
                            'category' => $product->category->title ?? null,
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