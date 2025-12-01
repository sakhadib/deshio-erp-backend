<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    /**
     * Get customer's cart
     */
    public function index(Request $request)
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();
            
            $cartItems = Cart::with(['product.images', 'product.category'])
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalAmount = $cartItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

            $totalItems = $cartItems->sum('quantity');

            return response()->json([
                'success' => true,
                'data' => [
                    'cart_items' => $cartItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'selling_price' => $item->product->selling_price,
                                'images' => $item->product->images->take(1),
                                'category' => $item->product->category->name ?? null,
                                'stock_quantity' => $item->product->stock_quantity,
                                'in_stock' => $item->product->stock_quantity > 0,
                            ],
                            'variant_options' => $item->variant_options,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->quantity * $item->unit_price,
                            'notes' => $item->notes,
                            'added_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                        ];
                    }),
                    'summary' => [
                        'total_items' => $totalItems,
                        'total_amount' => $totalAmount,
                        'currency' => 'BDT',
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add product to cart
     */
    public function addToCart(Request $request)
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1|max:100',
                'notes' => 'nullable|string|max:500',
                'variant_options' => 'nullable|array',
                'variant_options.color' => 'nullable|string|max:50',
                'variant_options.size' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $product = Product::findOrFail($request->product_id);

            // Check if product is available for purchase
            if (!$product->is_active || $product->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is not available for purchase',
                ], 400);
            }

            // Check stock availability
            if ($product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity,
                ], 400);
            }

            // Check if item already exists in cart (matching product_id and variant_options)
            $query = Cart::where('customer_id', $customer->id)
                ->where('product_id', $product->id)
                ->where('status', 'active');
            
            // Match variant_options: NULL vs NULL, or exact JSON match
            if ($request->has('variant_options') && $request->variant_options) {
                $query->where('variant_options', json_encode($request->variant_options));
            } else {
                $query->whereNull('variant_options');
            }
            
            $existingCartItem = $query->first();

            if ($existingCartItem) {
                // Update existing cart item
                $newQuantity = $existingCartItem->quantity + $request->quantity;
                
                if ($newQuantity > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Total quantity exceeds available stock. Current in cart: ' . $existingCartItem->quantity . ', Available: ' . $product->stock_quantity,
                    ], 400);
                }

                $existingCartItem->update([
                    'quantity' => $newQuantity,
                    'notes' => $request->notes ?? $existingCartItem->notes,
                ]);

                $cartItem = $existingCartItem;
            } else {
                // Create new cart item
                $cartItem = Cart::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'variant_options' => $request->variant_options,
                    'quantity' => $request->quantity,
                    'unit_price' => $product->selling_price,
                    'notes' => $request->notes,
                    'status' => 'active',
                ]);
            }

            // Load relationships
            $cartItem->load(['product.images', 'product.category']);

            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'data' => [
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'product_id' => $cartItem->product_id,
                        'product' => [
                            'id' => $cartItem->product->id,
                            'name' => $cartItem->product->name,
                            'selling_price' => $cartItem->product->selling_price,
                            'images' => $cartItem->product->images->take(1),
                        ],
                        'variant_options' => $cartItem->variant_options,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->unit_price,
                        'total_price' => $cartItem->quantity * $cartItem->unit_price,
                        'notes' => $cartItem->notes,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(Request $request, $cartItemId)
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cartItem = Cart::where('id', $cartItemId)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->firstOrFail();

            $product = Product::findOrFail($cartItem->product_id);

            // Check stock availability
            if ($product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity,
                ], 400);
            }

            $cartItem->update([
                'quantity' => $request->quantity,
                'unit_price' => $product->selling_price, // Update price in case it changed
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'data' => [
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->unit_price,
                        'total_price' => $cartItem->quantity * $cartItem->unit_price,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cartItemId)
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $cartItem = Cart::where('id', $cartItemId)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->firstOrFail();

            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart()
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            Cart::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save item for later (move to saved items)
     */
    public function saveForLater($cartItemId)
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $cartItem = Cart::where('id', $cartItemId)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->firstOrFail();

            $cartItem->update(['status' => 'saved']);

            return response()->json([
                'success' => true,
                'message' => 'Item saved for later',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move saved item back to cart
     */
    public function moveToCart($cartItemId)
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $cartItem = Cart::where('id', $cartItemId)
                ->where('customer_id', $customer->id)
                ->where('status', 'saved')
                ->firstOrFail();

            $product = Product::findOrFail($cartItem->product_id);

            // Check stock availability
            if ($product->stock_quantity < $cartItem->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity,
                ], 400);
            }

            $cartItem->update([
                'status' => 'active',
                'unit_price' => $product->selling_price, // Update price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item moved to cart',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get saved items
     */
    public function getSavedItems()
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $savedItems = Cart::with(['product.images', 'product.category'])
                ->where('customer_id', $customer->id)
                ->where('status', 'saved')
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'saved_items' => $savedItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'selling_price' => $item->product->selling_price,
                                'images' => $item->product->images->take(1),
                                'category' => $item->product->category->name ?? null,
                                'stock_quantity' => $item->product->stock_quantity,
                                'in_stock' => $item->product->stock_quantity > 0,
                                'price_changed' => $item->unit_price != $item->product->selling_price,
                            ],
                            'quantity' => $item->quantity,
                            'original_price' => $item->unit_price,
                            'current_price' => $item->product->selling_price,
                            'notes' => $item->notes,
                            'saved_at' => $item->updated_at,
                        ];
                    }),
                    'total_saved_items' => $savedItems->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get saved items: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cart summary (item count, total)
     */
    public function getCartSummary()
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $cartItems = Cart::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->get();

            $totalItems = $cartItems->sum('quantity');
            $totalAmount = $cartItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => $totalItems,
                    'total_amount' => $totalAmount,
                    'currency' => 'BDT',
                    'has_items' => $totalItems > 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cart summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate cart before checkout
     */
    public function validateCart()
    {
        try {
            $customer = JWTAuth::parseToken()->authenticate();

            $cartItems = Cart::with('product')
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            $issues = [];
            $validItems = [];

            foreach ($cartItems as $item) {
                $product = $item->product;
                
                // Check product availability
                if (!$product->is_active || $product->status !== 'active') {
                    $issues[] = [
                        'item_id' => $item->id,
                        'product_name' => $product->name,
                        'issue' => 'Product is no longer available',
                    ];
                    continue;
                }

                // Check stock
                if ($product->stock_quantity < $item->quantity) {
                    $issues[] = [
                        'item_id' => $item->id,
                        'product_name' => $product->name,
                        'issue' => 'Insufficient stock. Available: ' . $product->stock_quantity,
                        'available_quantity' => $product->stock_quantity,
                    ];
                    continue;
                }

                // Check price changes
                if ($item->unit_price != $product->selling_price) {
                    $issues[] = [
                        'item_id' => $item->id,
                        'product_name' => $product->name,
                        'issue' => 'Price has changed',
                        'old_price' => $item->unit_price,
                        'new_price' => $product->selling_price,
                    ];
                }

                $validItems[] = $item;
            }

            return response()->json([
                'success' => count($issues) === 0,
                'data' => [
                    'is_valid' => count($issues) === 0,
                    'valid_items_count' => count($validItems),
                    'total_items_count' => $cartItems->count(),
                    'issues' => $issues,
                    'total_amount' => $validItems ? collect($validItems)->sum(function ($item) {
                        return $item->quantity * $item->product->selling_price;
                    }) : 0,
                ],
                'message' => count($issues) === 0 ? 'Cart is valid for checkout' : 'Cart has issues that need to be resolved',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate cart: ' . $e->getMessage(),
            ], 500);
        }
    }
}