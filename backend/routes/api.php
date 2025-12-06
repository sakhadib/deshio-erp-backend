<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\VendorPaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSearchController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductBatchController;
use App\Http\Controllers\ProductBarcodeController;
use App\Http\Controllers\ProductDispatchController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ProductReturnController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryRebalancingController;
use App\Http\Controllers\BarcodeLocationController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\SslcommerzController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);

// ============================================
// E-COMMERCE GUEST CHECKOUT (NO AUTH REQUIRED)
// Phone-based checkout for simplified customer experience
// ============================================

Route::post('/guest-checkout', [\App\Http\Controllers\GuestCheckoutController::class, 'checkout']);
Route::post('/guest-orders/by-phone', [\App\Http\Controllers\GuestCheckoutController::class, 'getOrdersByPhone']);

// ============================================
// E-COMMERCE CUSTOMER AUTHENTICATION ROUTES
// Customer registration, login, password reset
// ============================================

// Customer Auth Routes (Public)
Route::prefix('customer-auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\CustomerAuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\CustomerAuthController::class, 'login']);
    Route::post('/password/reset-request', [\App\Http\Controllers\CustomerAuthController::class, 'sendPasswordResetEmail']);
    Route::post('/password/reset', [\App\Http\Controllers\CustomerAuthController::class, 'resetPassword']);
    Route::post('/email/verify', [\App\Http\Controllers\CustomerAuthController::class, 'verifyEmail']);
    Route::post('/email/resend', [\App\Http\Controllers\CustomerAuthController::class, 'resendEmailVerification']);
});

// Protected Customer Routes
Route::middleware('auth:customer')->prefix('customer-auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\CustomerAuthController::class, 'logout']);
    Route::post('/refresh', [\App\Http\Controllers\CustomerAuthController::class, 'refresh']);
    Route::get('/me', [\App\Http\Controllers\CustomerAuthController::class, 'me']);
    Route::post('/password/change', [\App\Http\Controllers\CustomerAuthController::class, 'changePassword']);
});

// ============================================
// E-COMMERCE SHOPPING CART ROUTES
// Cart management for logged-in customers
// ============================================

Route::middleware('auth:customer')->prefix('cart')->group(function () {
    Route::get('/', [\App\Http\Controllers\CartController::class, 'index']);
    Route::post('/add', [\App\Http\Controllers\CartController::class, 'addToCart']);
    Route::put('/update/{cartItemId}', [\App\Http\Controllers\CartController::class, 'updateQuantity']);
    Route::delete('/remove/{cartItemId}', [\App\Http\Controllers\CartController::class, 'removeFromCart']);
    Route::delete('/clear', [\App\Http\Controllers\CartController::class, 'clearCart']);
    Route::post('/save-for-later/{cartItemId}', [\App\Http\Controllers\CartController::class, 'saveForLater']);
    Route::post('/move-to-cart/{cartItemId}', [\App\Http\Controllers\CartController::class, 'moveToCart']);
    Route::get('/saved-items', [\App\Http\Controllers\CartController::class, 'getSavedItems']);
    Route::get('/summary', [\App\Http\Controllers\CartController::class, 'getCartSummary']);
    Route::post('/validate', [\App\Http\Controllers\CartController::class, 'validateCart']);
});

// ============================================
// E-COMMERCE WISHLIST ROUTES
// Wishlist management with multiple named lists
// ============================================

Route::middleware('auth:customer')->prefix('wishlist')->group(function () {
    Route::get('/', [\App\Http\Controllers\WishlistController::class, 'index']);
    Route::post('/add', [\App\Http\Controllers\WishlistController::class, 'addToWishlist']);
    Route::delete('/remove/{wishlistItemId}', [\App\Http\Controllers\WishlistController::class, 'removeFromWishlist']);
    Route::post('/move-to-cart/{wishlistItemId}', [\App\Http\Controllers\WishlistController::class, 'moveToCart']);
    Route::delete('/clear', [\App\Http\Controllers\WishlistController::class, 'clearWishlist']);
    Route::get('/stats', [\App\Http\Controllers\WishlistController::class, 'getWishlistStats']);
    Route::post('/manage-name', [\App\Http\Controllers\WishlistController::class, 'manageWishlistName']);
    Route::post('/move-all-to-cart', [\App\Http\Controllers\WishlistController::class, 'moveAllToCart']);
});

// ============================================
// E-COMMERCE CATALOG ROUTES (PUBLIC)
// Product browsing, search, categories - no auth required
// ============================================

Route::prefix('catalog')->group(function () {
    Route::get('/products', [\App\Http\Controllers\EcommerceCatalogController::class, 'getProducts']);
    Route::get('/products/{identifier}', [\App\Http\Controllers\EcommerceCatalogController::class, 'getProduct']);
    Route::get('/categories', [\App\Http\Controllers\EcommerceCatalogController::class, 'getCategories']);
    Route::get('/featured-products', [\App\Http\Controllers\EcommerceCatalogController::class, 'getFeaturedProducts']);
    Route::get('/new-arrivals', [\App\Http\Controllers\EcommerceCatalogController::class, 'getNewArrivals']);
    Route::get('/suggested-products', [\App\Http\Controllers\EcommerceCatalogController::class, 'getSuggestedProducts']);
    Route::get('/search', [\App\Http\Controllers\EcommerceCatalogController::class, 'searchProducts']);
    Route::get('/price-range', [\App\Http\Controllers\EcommerceCatalogController::class, 'getPriceRange']);
});

// ============================================
// E-COMMERCE CUSTOMER PROFILE ROUTES
// Profile management for logged-in customers
// ============================================

Route::middleware('auth:customer')->prefix('profile')->group(function () {
    Route::get('/', [\App\Http\Controllers\CustomerProfileController::class, 'getProfile']);
    Route::put('/update', [\App\Http\Controllers\CustomerProfileController::class, 'updateProfile']);
    Route::get('/orders', [\App\Http\Controllers\CustomerProfileController::class, 'getOrderHistory']);
    Route::put('/communication-preferences', [\App\Http\Controllers\CustomerProfileController::class, 'updateCommunicationPreferences']);
    Route::put('/shopping-preferences', [\App\Http\Controllers\CustomerProfileController::class, 'updateShoppingPreferences']);
    Route::get('/stats', [\App\Http\Controllers\CustomerProfileController::class, 'getCustomerStats']);
    Route::post('/deactivate', [\App\Http\Controllers\CustomerProfileController::class, 'deactivateAccount']);
});

// ============================================
// SSLCOMMERZ PAYMENT GATEWAY CALLBACKS
// Handle payment success, failure, cancel, and IPN
// ============================================

Route::controller(SslcommerzController::class)
    ->prefix('sslcommerz')
    ->name('sslc.')
    ->group(function () {
        Route::post('success', 'success')->name('success');
        Route::post('failure', 'failure')->name('failure');
        Route::post('cancel', 'cancel')->name('cancel');
        Route::post('ipn', 'ipn')->name('ipn');
    });

// ============================================
// E-COMMERCE CUSTOMER ADDRESS MANAGEMENT ROUTES
// Delivery and billing address management
// ============================================

Route::middleware('auth:customer')->prefix('customer')->group(function () {
    Route::prefix('addresses')->group(function () {
        // List all addresses for customer
        Route::get('/', [\App\Http\Controllers\CustomerAddressController::class, 'index']);
        
        // Create new address
        Route::post('/', [\App\Http\Controllers\CustomerAddressController::class, 'store']);
        
        // Get default addresses
        Route::get('/default/shipping', [\App\Http\Controllers\CustomerAddressController::class, 'getDefaultShipping']);
        Route::get('/default/billing', [\App\Http\Controllers\CustomerAddressController::class, 'getDefaultBilling']);
        
        // Validate address
        Route::post('/validate', [\App\Http\Controllers\CustomerAddressController::class, 'validateAddress']);
        
        // Individual address operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [\App\Http\Controllers\CustomerAddressController::class, 'show']);
            Route::put('/', [\App\Http\Controllers\CustomerAddressController::class, 'update']);
            Route::delete('/', [\App\Http\Controllers\CustomerAddressController::class, 'destroy']);
            Route::patch('/set-default-shipping', [\App\Http\Controllers\CustomerAddressController::class, 'setDefaultShipping']);
            Route::patch('/set-default-billing', [\App\Http\Controllers\CustomerAddressController::class, 'setDefaultBilling']);
        });
    });
});

// E-commerce Order Management (Customer)
Route::middleware('auth:customer')->prefix('customer')->group(function () {
    Route::prefix('orders')->group(function () {
        // Create order from cart
        Route::post('/create-from-cart', [\App\Http\Controllers\EcommerceOrderController::class, 'createFromCart']);
        
        // List customer orders with filters
        Route::get('/', [\App\Http\Controllers\EcommerceOrderController::class, 'index']);
        
        // Get order details
        Route::get('/{orderNumber}', [\App\Http\Controllers\EcommerceOrderController::class, 'show']);
        
        // Cancel order
        Route::post('/{orderNumber}/cancel', [\App\Http\Controllers\EcommerceOrderController::class, 'cancel']);
        
        // Track order
        Route::get('/{orderNumber}/track', [\App\Http\Controllers\EcommerceOrderController::class, 'track']);
        
        // Get order statistics
        Route::get('/stats/summary', [\App\Http\Controllers\EcommerceOrderController::class, 'statistics']);
    });
});

// Public API for payment methods (no auth required for POS/Social Commerce)
Route::get('/payment-methods', [PaymentController::class, 'getMethodsByCustomerType']);

// Get all payment methods (for vendor payments, expenses, etc.)
Route::get('/payment-methods/all', [PaymentController::class, 'getAllPaymentMethods']);

// Auth routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Order Management (Employee) - Inventory & Assignment
    Route::prefix('order-management')->group(function () {
        // Get orders pending store assignment
        Route::get('/pending-assignment', [\App\Http\Controllers\OrderManagementController::class, 'getPendingAssignmentOrders']);
        
        // Get available stores for an order based on inventory
        Route::get('/orders/{orderId}/available-stores', [\App\Http\Controllers\OrderManagementController::class, 'getAvailableStores']);
        
        // Assign order to a specific store
        Route::post('/orders/{orderId}/assign-store', [\App\Http\Controllers\OrderManagementController::class, 'assignOrderToStore']);
    });

    // Store Fulfillment (Store Employee) - Dashboard & Barcode Scanning
    Route::prefix('store/fulfillment')->group(function () {
        // Get orders assigned to employee's store
        Route::get('/orders/assigned', [\App\Http\Controllers\StoreFulfillmentController::class, 'getAssignedOrders']);
        
        // Get specific order details for fulfillment
        Route::get('/orders/{orderId}', [\App\Http\Controllers\StoreFulfillmentController::class, 'getOrderDetails']);
        
        // Scan barcode to fulfill order item
        Route::post('/orders/{orderId}/scan-barcode', [\App\Http\Controllers\StoreFulfillmentController::class, 'scanBarcode']);
        
        // Mark order as ready for shipment
        Route::post('/orders/{orderId}/ready-for-shipment', [\App\Http\Controllers\StoreFulfillmentController::class, 'markReadyForShipment']);
    });

    // Employee management routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'getEmployees']);
        Route::post('/', [EmployeeController::class, 'createEmployee']);
        Route::get('/stats', [EmployeeController::class, 'getEmployeeStats']);
        Route::get('/by-store/{storeId}', [EmployeeController::class, 'getEmployeesByStore']);
        Route::get('/by-role/{roleId}', [EmployeeController::class, 'getEmployeesByRole']);
        Route::get('/by-manager/{managerId}', [EmployeeController::class, 'getEmployeesByManager']);
        Route::get('/by-department/{department}', [EmployeeController::class, 'getEmployeesByDepartment']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [EmployeeController::class, 'getEmployee']);
            Route::put('/', [EmployeeController::class, 'updateEmployee']);
            Route::delete('/', [EmployeeController::class, 'deleteEmployee']);
            
            // Employee management actions
            Route::patch('/role', [EmployeeController::class, 'changeEmployeeRole']);
            Route::patch('/transfer', [EmployeeController::class, 'transferEmployee']);
            Route::patch('/activate', [EmployeeController::class, 'activateEmployee']);
            Route::patch('/deactivate', [EmployeeController::class, 'deactivateEmployee']);
            Route::patch('/password', [EmployeeController::class, 'changePassword']);
            Route::patch('/salary', [EmployeeController::class, 'updateSalary']);
            
            // Manager/hierarchy management
            Route::get('/subordinates', [EmployeeController::class, 'getSubordinates']);
            Route::get('/hierarchy', [EmployeeController::class, 'getHierarchy']);
            Route::post('/assign-manager', [EmployeeController::class, 'assignManager']);
            Route::delete('/remove-manager', [EmployeeController::class, 'removeManager']);
            
            // Session management
            Route::get('/sessions', [EmployeeController::class, 'getSessions']);
            Route::delete('/sessions/revoke-all', [EmployeeController::class, 'revokeAllSessions']);
            Route::delete('/sessions/{sessionId}', [EmployeeController::class, 'revokeSession']);
            
            // MFA management
            Route::get('/mfa', [EmployeeController::class, 'getMFASettings']);
            Route::post('/mfa/enable', [EmployeeController::class, 'enableMFA']);
            Route::delete('/mfa/{mfaId}/disable', [EmployeeController::class, 'disableMFA']);
            Route::post('/mfa/{mfaId}/backup-codes/regenerate', [EmployeeController::class, 'regenerateBackupCodes']);
            
            // Activity tracking
            Route::get('/activity-log', [EmployeeController::class, 'getActivityLog']);
        });
    });

    // Bulk operations
    Route::patch('/employees/bulk/status', [EmployeeController::class, 'bulkUpdateStatus']);

    // Vendor management routes
    Route::prefix('vendors')->group(function () {
        Route::get('/', [VendorController::class, 'getVendors']);
        Route::post('/', [VendorController::class, 'createVendor']);
        Route::get('/stats', [VendorController::class, 'getVendorStats']);
        Route::get('/analytics', [VendorController::class, 'getAllVendorsAnalytics']);
        Route::get('/by-type/{type}', [VendorController::class, 'getVendorsByType']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [VendorController::class, 'getVendor']);
            Route::put('/', [VendorController::class, 'updateVendor']);
            Route::delete('/', [VendorController::class, 'deleteVendor']);
            Route::patch('/activate', [VendorController::class, 'activateVendor']);
            Route::patch('/deactivate', [VendorController::class, 'deactivateVendor']);
            
            // Vendor analytics and history
            Route::get('/analytics', [VendorController::class, 'getVendorAnalytics']);
            Route::get('/purchase-history', [VendorController::class, 'getPurchaseHistory']);
            Route::get('/payment-history', [VendorController::class, 'getPaymentHistory']);
        });
    });

        // Bulk vendor operations
    Route::patch('/vendors/bulk/status', [VendorController::class, 'bulkUpdateStatus']);

    // Purchase Order management routes
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::post('/', [PurchaseOrderController::class, 'create']);
        Route::get('/stats', [PurchaseOrderController::class, 'statistics']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'show']);
            Route::put('/', [PurchaseOrderController::class, 'update']);
            
            // PO Actions
            Route::post('/approve', [PurchaseOrderController::class, 'approve']);
            Route::post('/receive', [PurchaseOrderController::class, 'receive']);
            Route::post('/cancel', [PurchaseOrderController::class, 'cancel']);
            
            // PO Items management
            Route::post('/items', [PurchaseOrderController::class, 'addItem']);
            Route::put('/items/{itemId}', [PurchaseOrderController::class, 'updateItem']);
            Route::delete('/items/{itemId}', [PurchaseOrderController::class, 'removeItem']);
        });
    });

    // Vendor Payment management routes
    Route::prefix('vendor-payments')->group(function () {
        Route::get('/', [VendorPaymentController::class, 'index']);
        Route::post('/', [VendorPaymentController::class, 'create']);
        Route::get('/stats', [VendorPaymentController::class, 'statistics']);
        Route::get('/purchase-order/{purchaseOrderId}', [VendorPaymentController::class, 'getByPurchaseOrder']);
        Route::get('/outstanding/{vendorId}', [VendorPaymentController::class, 'getOutstanding']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [VendorPaymentController::class, 'show']);
            Route::post('/allocate', [VendorPaymentController::class, 'allocateAdvance']);
            Route::post('/cancel', [VendorPaymentController::class, 'cancel']);
            Route::post('/refund', [VendorPaymentController::class, 'refund']);
        });
    });

    // Store management routes
    Route::prefix('stores')->group(function () {
        Route::get('/', [StoreController::class, 'getStores']);
        Route::post('/', [StoreController::class, 'createStore']);
        Route::get('/stats', [StoreController::class, 'getStoreStats']);
        Route::get('/by-type/{type}', [StoreController::class, 'getStoresByType']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [StoreController::class, 'getStore']);
            Route::put('/', [StoreController::class, 'updateStore']);
            Route::delete('/', [StoreController::class, 'deleteStore']);
            Route::patch('/activate', [StoreController::class, 'activateStore']);
            Route::patch('/deactivate', [StoreController::class, 'deactivateStore']);
            Route::get('/inventory', [StoreController::class, 'getStoreInventory']);
        });
    });

        // Bulk store operations
    Route::patch('/stores/bulk/status', [StoreController::class, 'bulkUpdateStatus']);

    // Category management routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoriesController::class, 'getCategories']);
        Route::post('/', [CategoriesController::class, 'createCategory']);
        Route::get('/stats', [CategoriesController::class, 'getCategoryStats']);
        Route::patch('/reorder', [CategoriesController::class, 'reorderCategories']);
        
        // Nested category routes
        Route::get('/tree', [CategoriesController::class, 'getCategoryTree']);
        Route::get('/root', [CategoriesController::class, 'getRootCategories']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [CategoriesController::class, 'getCategory']);
            Route::put('/', [CategoriesController::class, 'updateCategory']);
            Route::delete('/', [CategoriesController::class, 'deleteCategory']);
            Route::patch('/activate', [CategoriesController::class, 'activateCategory']);
            Route::patch('/deactivate', [CategoriesController::class, 'deactivateCategory']);
            
            // Nested category specific routes
            Route::get('/subcategories', [CategoriesController::class, 'getSubcategories']);
            Route::patch('/move', [CategoriesController::class, 'moveCategory']);
            Route::get('/breadcrumb', [CategoriesController::class, 'getCategoryBreadcrumb']);
            Route::get('/descendants', [CategoriesController::class, 'getCategoryDescendants']);
        });
    });

    // Bulk category operations
    Route::patch('/categories/bulk/status', [CategoriesController::class, 'bulkUpdateStatus']);

    // ============================================
    // CUSTOMER MANAGEMENT ROUTES
    // Customer profiles, orders, analytics, segmentation
    // ============================================
    
    Route::prefix('customers')->group(function () {
        // List and statistics
        Route::get('/', [CustomerController::class, 'index']);
        Route::get('/statistics', [CustomerController::class, 'getStatistics']);
        Route::get('/segments', [CustomerController::class, 'getSegments']);
        Route::get('/search', [CustomerController::class, 'search']);
        
        // Create customer
        Route::post('/', [CustomerController::class, 'store']);
        
        // Bulk operations
        Route::patch('/bulk/status', [CustomerController::class, 'bulkUpdateStatus']);
        
        // Individual customer operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [CustomerController::class, 'show']);
            Route::put('/', [CustomerController::class, 'update']);
            Route::delete('/', [CustomerController::class, 'destroy']);
            Route::patch('/activate', [CustomerController::class, 'activate']);
            Route::patch('/deactivate', [CustomerController::class, 'deactivate']);
            Route::patch('/block', [CustomerController::class, 'block']);
            
            // Customer analytics and history
            Route::get('/orders', [CustomerController::class, 'getOrderHistory']);
            Route::get('/analytics', [CustomerController::class, 'getAnalytics']);
            Route::post('/notes', [CustomerController::class, 'addNote']);
            Route::post('/assign-employee', [CustomerController::class, 'assignEmployee']);
        });
    });

    // ============================================
    // SERVICE MANAGEMENT ROUTES
    // Tailoring, alterations, and custom services
    // ============================================
    
    Route::prefix('services')->group(function () {
        // List and statistics
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/active', [ServiceController::class, 'getActiveServices']);
        Route::get('/statistics', [ServiceController::class, 'getStatistics']);
        Route::get('/by-category/{category}', [ServiceController::class, 'getByCategory']);
        
        // Create service
        Route::post('/', [ServiceController::class, 'store']);
        
        // Bulk operations
        Route::patch('/bulk/status', [ServiceController::class, 'bulkUpdateStatus']);
        Route::patch('/reorder', [ServiceController::class, 'reorder']);
        
        // Individual service operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [ServiceController::class, 'show']);
            Route::put('/', [ServiceController::class, 'update']);
            Route::delete('/', [ServiceController::class, 'destroy']);
            Route::patch('/activate', [ServiceController::class, 'activate']);
            Route::patch('/deactivate', [ServiceController::class, 'deactivate']);
        });
    });

    // ============================================
    // EXPENSE MANAGEMENT ROUTES
    // Track business expenses, payments, budgets
    // ============================================
    
    Route::prefix('expenses')->group(function () {
        // List and statistics
        Route::get('/', [ExpenseController::class, 'index']);
        Route::get('/statistics', [ExpenseController::class, 'getStatistics']);
        Route::get('/overdue', [ExpenseController::class, 'getOverdue']);
        
        // Create expense
        Route::post('/', [ExpenseController::class, 'store']);
        
        // Individual expense operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [ExpenseController::class, 'show']);
            Route::put('/', [ExpenseController::class, 'update']);
            Route::delete('/', [ExpenseController::class, 'destroy']);
            Route::post('/approve', [ExpenseController::class, 'approve']);
            Route::post('/reject', [ExpenseController::class, 'reject']);
            Route::post('/payments', [ExpenseController::class, 'addPayment']);
            
            // Receipt management routes
            Route::post('/receipts', [ExpenseController::class, 'uploadReceipt']);
            Route::get('/receipts', [ExpenseController::class, 'getReceipts']);
            Route::delete('/receipts/{receiptId}', [ExpenseController::class, 'deleteReceipt']);
            Route::patch('/receipts/{receiptId}/set-primary', [ExpenseController::class, 'setPrimaryReceipt']);
            Route::get('/receipts/{receiptId}/download', [ExpenseController::class, 'downloadReceipt']);
        });
    });

    // Expense Category Management Routes
    Route::prefix('expense-categories')->group(function () {
        Route::get('/', [ExpenseCategoryController::class, 'index']);
        Route::post('/', [ExpenseCategoryController::class, 'store']);
        Route::get('/tree', [ExpenseCategoryController::class, 'getTree']);
        Route::get('/statistics', [ExpenseCategoryController::class, 'getStatistics']);
        
        Route::prefix('{id}')->group(function () {
            Route::get('/', [ExpenseCategoryController::class, 'show']);
            Route::put('/', [ExpenseCategoryController::class, 'update']);
            Route::delete('/', [ExpenseCategoryController::class, 'destroy']);
        });
    });

    // ============================================
    // ROLE & PERMISSION MANAGEMENT ROUTES
    // RBAC system for access control
    // ============================================
    
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/statistics', [RoleController::class, 'getStatistics']);
        
        Route::prefix('{id}')->group(function () {
            Route::get('/', [RoleController::class, 'show']);
            Route::put('/', [RoleController::class, 'update']);
            Route::delete('/', [RoleController::class, 'destroy']);
            Route::post('/permissions', [RoleController::class, 'assignPermissions']);
            Route::delete('/permissions', [RoleController::class, 'removePermissions']);
        });
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('/by-module', [PermissionController::class, 'getByModule']);
        Route::get('/statistics', [PermissionController::class, 'getStatistics']);
        
        Route::prefix('{id}')->group(function () {
            Route::get('/', [PermissionController::class, 'show']);
            Route::put('/', [PermissionController::class, 'update']);
            Route::delete('/', [PermissionController::class, 'destroy']);
        });
    });

    // ============================================
    // DASHBOARD & ANALYTICS ROUTES
    // Today's metrics, sales trends, operations overview
    // ============================================
    
    Route::prefix('dashboard')->group(function () {
        // Today's key metrics
        Route::get('/today-metrics', [DashboardController::class, 'todayMetrics']);
        
        // Sales analytics
        Route::get('/last-30-days-sales', [DashboardController::class, 'last30DaysSales']);
        Route::get('/sales-by-channel', [DashboardController::class, 'salesByChannel']);
        Route::get('/top-stores', [DashboardController::class, 'topStoresBySales']);
        
        // Product performance
        Route::get('/today-top-products', [DashboardController::class, 'todayTopProducts']);
        Route::get('/slow-moving-products', [DashboardController::class, 'slowMovingProducts']);
        
        // Inventory insights
        Route::get('/low-stock-products', [DashboardController::class, 'lowStockProducts']);
        Route::get('/inventory-age-by-value', [DashboardController::class, 'inventoryAgeByValue']);
        
        // Operations overview
        Route::get('/operations-today', [DashboardController::class, 'operationsToday']);
    });

    // ============================================
    // REPORTING & ANALYTICS ROUTES
    // Business intelligence and dashboard metrics
    // ============================================
    
    Route::prefix('reports')->group(function () {
        // Dashboard
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        
        // Sales Reports
        Route::get('/sales/summary', [ReportController::class, 'salesSummary']);
        Route::get('/sales/best-sellers', [ReportController::class, 'bestSellers']);
        Route::get('/sales/slow-moving', [ReportController::class, 'slowMoving']);
        Route::get('/sales/profit-margins', [ReportController::class, 'profitMargins']);
        
        // Staff Reports
        Route::get('/staff/performance', [ReportController::class, 'staffPerformance']);
        
        // Customer Reports
        Route::get('/customers/acquisition', [ReportController::class, 'customerAcquisition']);
        
        // Inventory Reports
        Route::get('/inventory/value', [ReportController::class, 'inventoryValue']);
        
        // Expense Reports
        Route::get('/expenses/summary', [ReportController::class, 'expenseSummary']);
    });

    // ============================================
    // ACCOUNTING REPORTS ROUTES
    // Textbook-style financial statements and reports
    // T-Account, Trial Balance, Income Statement, Balance Sheet, etc.
    // ============================================
    
    Route::prefix('accounting')->group(function () {
        // Textbook-style T-Account (Debit/Credit Ledger)
        Route::get('/t-account/{accountId}', [\App\Http\Controllers\AccountingReportController::class, 'getTAccount']);
        
        // Trial Balance
        Route::get('/trial-balance', [\App\Http\Controllers\AccountingReportController::class, 'getTrialBalance']);
        
        // Income Statement (Profit & Loss)
        Route::get('/income-statement', [\App\Http\Controllers\AccountingReportController::class, 'getIncomeStatement']);
        
        // Balance Sheet
        Route::get('/balance-sheet', [\App\Http\Controllers\AccountingReportController::class, 'getBalanceSheet']);
        
        // Cash Flow Statement
        Route::get('/cash-flow-statement', [\App\Http\Controllers\AccountingReportController::class, 'getCashFlowStatement']);
        
        // Cost Sheet
        Route::get('/cost-sheet', [\App\Http\Controllers\AccountingReportController::class, 'getCostSheet']);
        
        // Journal Entries
        Route::get('/journal-entries', [\App\Http\Controllers\AccountingReportController::class, 'getJournalEntries']);
    });

    // ============================================
    // ACCOUNT & TRANSACTION MANAGEMENT ROUTES
    // Chart of accounts and financial transactions
    // ============================================
    
    Route::prefix('accounts')->group(function () {
        // List and statistics
        Route::get('/', [AccountController::class, 'index']);
        Route::get('/statistics', [AccountController::class, 'getStatistics']);
        Route::get('/tree', [AccountController::class, 'getTree']);
        Route::get('/chart-of-accounts', [AccountController::class, 'getChartOfAccounts']);
        Route::post('/initialize-defaults', [AccountController::class, 'initializeDefaultAccounts']);
        
        // Create account
        Route::post('/', [AccountController::class, 'store']);
        
        // Account details
        Route::prefix('{id}')->group(function () {
            Route::get('/', [AccountController::class, 'show']);
            Route::put('/', [AccountController::class, 'update']);
            Route::delete('/', [AccountController::class, 'destroy']);
            Route::get('/balance', [AccountController::class, 'getBalance']);
            Route::post('/activate', [AccountController::class, 'activate']);
            Route::post('/deactivate', [AccountController::class, 'deactivate']);
            Route::get('/transactions', [TransactionController::class, 'getAccountTransactions']);
        });
    });
    
    Route::prefix('transactions')->group(function () {
        // List and statistics
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/statistics', [TransactionController::class, 'getStatistics']);
        Route::get('/trial-balance', [TransactionController::class, 'getTrialBalance']);
        Route::get('/ledger/{accountId}', [TransactionController::class, 'getLedger']);
        
        // Create transaction
        Route::post('/', [TransactionController::class, 'store']);
        Route::post('/bulk-complete', [TransactionController::class, 'bulkComplete']);
        
        // Transaction details
        Route::prefix('{id}')->group(function () {
            Route::get('/', [TransactionController::class, 'show']);
            Route::put('/', [TransactionController::class, 'update']);
            Route::delete('/', [TransactionController::class, 'destroy']);
            Route::post('/complete', [TransactionController::class, 'complete']);
            Route::post('/fail', [TransactionController::class, 'fail']);
            Route::post('/cancel', [TransactionController::class, 'cancel']);
        });
    });

    // ============================================
    // PROMOTION & DISCOUNT MANAGEMENT ROUTES
    // Coupon codes, discount rules, validation
    // ============================================
    
    Route::prefix('promotions')->group(function () {
        // List and statistics
        Route::get('/', [PromotionController::class, 'index']);
        Route::get('/statistics', [PromotionController::class, 'getStatistics']);
        
        // Validation and application
        Route::post('/validate', [PromotionController::class, 'validateCode']);
        Route::post('/apply', [PromotionController::class, 'applyToOrder']);
        
        // Create promotion
        Route::post('/', [PromotionController::class, 'store']);
        
        // Individual promotion operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [PromotionController::class, 'show']);
            Route::put('/', [PromotionController::class, 'update']);
            Route::delete('/', [PromotionController::class, 'destroy']);
            Route::post('/activate', [PromotionController::class, 'activate']);
            Route::post('/deactivate', [PromotionController::class, 'deactivate']);
            Route::get('/usage-history', [PromotionController::class, 'getUsageHistory']);
            Route::post('/duplicate', [PromotionController::class, 'duplicate']);
        });
    });

    // ============================================
    // PRODUCT VARIANT MANAGEMENT ROUTES
    // Size/color matrices for clothing products
    // ============================================
    
    // Variant options (sizes, colors, etc.)
    Route::prefix('variant-options')->group(function () {
        Route::get('/', [ProductVariantController::class, 'getOptions']);
        Route::post('/', [ProductVariantController::class, 'storeOption']);
    });
    
    // Product variants
    Route::prefix('products/{productId}/variants')->group(function () {
        Route::get('/', [ProductVariantController::class, 'index']);
        Route::post('/', [ProductVariantController::class, 'store']);
        Route::get('/statistics', [ProductVariantController::class, 'getStatistics']);
        Route::post('/generate-matrix', [ProductVariantController::class, 'generateMatrix']);
        
        Route::prefix('{variantId}')->group(function () {
            Route::get('/', [ProductVariantController::class, 'show']);
            Route::put('/', [ProductVariantController::class, 'update']);
            Route::delete('/', [ProductVariantController::class, 'destroy']);
        });
    });

    // ============================================
    // COLLECTION/SEASON MANAGEMENT ROUTES
    // Fashion collections and seasonal catalogs
    // ============================================
    
    Route::prefix('collections')->group(function () {
        // List and statistics
        Route::get('/', [CollectionController::class, 'index']);
        Route::get('/statistics', [CollectionController::class, 'getStatistics']);
        
        // Create collection
        Route::post('/', [CollectionController::class, 'store']);
        
        // Individual collection operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [CollectionController::class, 'show']);
            Route::put('/', [CollectionController::class, 'update']);
            Route::delete('/', [CollectionController::class, 'destroy']);
            Route::post('/duplicate', [CollectionController::class, 'duplicate']);
            
            // Product management
            Route::get('/products', [CollectionController::class, 'getProducts']);
            Route::post('/products', [CollectionController::class, 'addProducts']);
            Route::delete('/products/{productId}', [CollectionController::class, 'removeProduct']);
            Route::patch('/products/reorder', [CollectionController::class, 'reorderProducts']);
        });
    });

    // ============================================
    // PRICE MANAGEMENT ROUTES
    // Price overrides, history, bulk updates
    // ============================================
    
    Route::prefix('price-overrides')->group(function () {
        // List and statistics
        Route::get('/', [PriceController::class, 'index']);
        Route::get('/statistics', [PriceController::class, 'getStatistics']);
        
        // Create and bulk operations
        Route::post('/', [PriceController::class, 'store']);
        Route::post('/bulk-update', [PriceController::class, 'bulkUpdate']);
        
        // Individual override operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [PriceController::class, 'show']);
            Route::put('/', [PriceController::class, 'update']);
            Route::delete('/', [PriceController::class, 'destroy']);
            Route::post('/approve', [PriceController::class, 'approve']);
        });
    });
    
    // Product price utilities
    Route::get('/products/{productId}/price-history', [PriceController::class, 'getPriceHistory']);
    Route::get('/products/{productId}/active-price', [PriceController::class, 'getActivePrice']);

    // ============================================
    // FIELD MANAGEMENT ROUTES
    // Custom fields for products and services
    // ============================================


    
    Route::prefix('fields')->group(function () {
        // List and statistics
        Route::get('/', [FieldController::class, 'index']);
        Route::get('/active', [FieldController::class, 'getActiveFields']);
        Route::get('/statistics', [FieldController::class, 'getStatistics']);
        Route::get('/types', [FieldController::class, 'getTypes']);
        Route::get('/by-type/{type}', [FieldController::class, 'getByType']);
        
        // Create field
        Route::post('/', [FieldController::class, 'store']);
        
        // Bulk operations
        Route::patch('/bulk/status', [FieldController::class, 'bulkUpdateStatus']);
        Route::patch('/reorder', [FieldController::class, 'reorder']);
        
        // Individual field operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [FieldController::class, 'show']);
            Route::put('/', [FieldController::class, 'update']);
            Route::delete('/', [FieldController::class, 'destroy']);
            Route::patch('/activate', [FieldController::class, 'activate']);
            Route::patch('/deactivate', [FieldController::class, 'deactivate']);
            Route::post('/duplicate', [FieldController::class, 'duplicate']);
        });
    });

    // ============================================
    // SALES / ORDER MANAGEMENT ROUTES
    // 3 Channels: Counter, Social Commerce, E-commerce
    // ============================================
    
    Route::prefix('orders')->group(function () {
        // List and statistics
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/statistics', [OrderController::class, 'getStatistics']);

        // Create order (all 3 channels)
        Route::post('/', [OrderController::class, 'create']);

        // Order operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [OrderController::class, 'show']);
            
            // Item management (before completion)
            Route::post('/items', [OrderController::class, 'addItem']);
            Route::put('/items/{itemId}', [OrderController::class, 'updateItem']);
            Route::delete('/items/{itemId}', [OrderController::class, 'removeItem']);
            
            // Order lifecycle
            Route::patch('/fulfill', [OrderController::class, 'fulfill']);  // Warehouse fulfillment (scan barcodes)
            Route::patch('/complete', [OrderController::class, 'complete']);  // Reduce inventory
            Route::patch('/cancel', [OrderController::class, 'cancel']);
        });
    });

    // ============================================
    // SHIPMENT / COURIER MANAGEMENT ROUTES
    // Pathao Integration for Delivery
    // ============================================
    
    Route::prefix('shipments')->group(function () {
        // List and statistics
        Route::get('/', [ShipmentController::class, 'index']);
        Route::get('/statistics', [ShipmentController::class, 'getStatistics']);

        // Pathao area lookup (for creating shipments)
        Route::get('/pathao/cities', [ShipmentController::class, 'getPathaoCities']);
        Route::get('/pathao/zones/{cityId}', [ShipmentController::class, 'getPathaoZones']);
        Route::get('/pathao/areas/{zoneId}', [ShipmentController::class, 'getPathaoAreas']);
        Route::get('/pathao/stores', [ShipmentController::class, 'getPathaoStores']);
        Route::post('/pathao/stores', [ShipmentController::class, 'createPathaoStore']);

        // Bulk operations
        Route::post('/bulk-send-to-pathao', [ShipmentController::class, 'bulkSendToPathao']);
        Route::post('/bulk-sync-pathao-status', [ShipmentController::class, 'bulkSyncPathaoStatus']);

        // Create shipment from order
        Route::post('/', [ShipmentController::class, 'create']);

        // Shipment operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [ShipmentController::class, 'show']);
            Route::post('/send-to-pathao', [ShipmentController::class, 'sendToPathao']);
            Route::get('/sync-pathao-status', [ShipmentController::class, 'syncPathaoStatus']);
            Route::patch('/cancel', [ShipmentController::class, 'cancel']);
        });
    });

    Route::prefix('payments')->group(function () {
        Route::get('/methods', [PaymentController::class, 'getMethodsByCustomerType']);
        Route::get('/overdue', [PaymentController::class, 'getOverduePayments']);
        Route::get('/stats', [PaymentController::class, 'getPaymentStats']);
    });

    // Order payment routes
    Route::prefix('orders/{order}/payments')->group(function () {
        Route::get('/', [PaymentController::class, 'getOrderPayments']);
        Route::post('/', [PaymentController::class, 'processPayment']);
        Route::post('/multiple', [PaymentController::class, 'processMultiplePayments']);
        Route::post('/installment/setup', [PaymentController::class, 'setupInstallmentPlan']);
        Route::post('/installment', [PaymentController::class, 'addInstallmentPayment']);
        Route::post('/partial', [PaymentController::class, 'addPartialPayment']);
        Route::get('/methods', [PaymentController::class, 'getAvailableMethods']);
    });

    // Payment refund routes
    Route::prefix('payments/{payment}')->group(function () {
        Route::post('/refund', [PaymentController::class, 'refundPayment']);
    });

    // Advanced Order Payment Management Routes (with splits and cash denominations)
    Route::prefix('orders/{order}/payments')->group(function () {
        // Get all payments for an order
        Route::get('/advanced', [OrderPaymentController::class, 'index']);
        
        // Create simple payment (single method)
        Route::post('/simple', [OrderPaymentController::class, 'store']);
        
        // Create split payment (multiple methods in one transaction)
        Route::post('/split', [OrderPaymentController::class, 'storeSplitPayment']);
        
        // Payment detail with splits and cash tracking
        Route::get('/{payment}/details', [OrderPaymentController::class, 'show']);
        
        // Payment processing actions
        Route::post('/{payment}/process', [OrderPaymentController::class, 'process']);
        Route::post('/{payment}/complete', [OrderPaymentController::class, 'complete']);
        Route::post('/{payment}/fail', [OrderPaymentController::class, 'fail']);
        Route::post('/{payment}/refund', [OrderPaymentController::class, 'refund']);
        
        // Cash denomination tracking
        Route::get('/{payment}/cash-denominations', [OrderPaymentController::class, 'getCashDenominations']);
    });

    // Utility routes for payment processing
    Route::prefix('payment-utils')->group(function () {
        Route::post('/calculate-change', [OrderPaymentController::class, 'calculateChange']);
    });

    // Product Management Routes (with custom fields support)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'create']);
        Route::get('/stats', [ProductController::class, 'getStatistics']);
        Route::get('/available-fields', [ProductController::class, 'getAvailableFields']);
        Route::post('/search-by-field', [ProductController::class, 'searchByCustomField']);
        Route::post('/bulk-update', [ProductController::class, 'bulkUpdate']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [ProductController::class, 'show']);
            Route::put('/', [ProductController::class, 'update']);
            Route::delete('/', [ProductController::class, 'destroy']);
            Route::patch('/archive', [ProductController::class, 'archive']);
            Route::patch('/restore', [ProductController::class, 'restore']);
            
            // Custom field management
            Route::post('/custom-fields', [ProductController::class, 'updateCustomField']);
            Route::delete('/custom-fields/{fieldId}', [ProductController::class, 'removeCustomField']);
        });
    });

    // ============================================
    // PRODUCT SEARCH ROUTES
    // Multi-language search with fuzzy matching
    // Supports: English, Bangla Unicode, Romanized Bangla, Misspellings
    // ============================================
    
    Route::prefix('products')->group(function () {
        // Advanced search with multi-language and fuzzy matching
        Route::post('/advanced-search', [ProductSearchController::class, 'advancedSearch']);
        
        // Quick search for autocomplete
        Route::get('/quick-search', [ProductSearchController::class, 'quickSearch']);
        
        // Search suggestions
        Route::get('/search-suggestions', [ProductSearchController::class, 'searchSuggestions']);
        
        // Search analytics
        Route::get('/search-stats', [ProductSearchController::class, 'getSearchStats']);
    });

    // ============================================
    // PRODUCT IMAGE MANAGEMENT ROUTES
    // Upload, manage, and organize product images
    // Support for single and bulk uploads, primary image, reordering
    // ============================================
    
    // Product-specific image routes
    Route::prefix('products/{productId}/images')->group(function () {
        Route::get('/', [ProductImageController::class, 'index']);                      // Get all images
        Route::post('/', [ProductImageController::class, 'upload']);                    // Upload single image
        Route::post('/bulk-upload', [ProductImageController::class, 'bulkUpload']);     // Upload multiple images
        Route::patch('/reorder', [ProductImageController::class, 'reorder']);           // Reorder images
        Route::delete('/delete-all', [ProductImageController::class, 'destroyAll']);    // Delete all images
        Route::get('/statistics', [ProductImageController::class, 'getStatistics']);    // Image statistics
        Route::get('/primary', [ProductImageController::class, 'getPrimary']);          // Get primary image
    });

    // Individual image management routes
    Route::prefix('product-images')->group(function () {
        Route::get('/{id}', [ProductImageController::class, 'show']);                   // Get single image
        Route::put('/{id}', [ProductImageController::class, 'update']);                 // Update image details
        Route::delete('/{id}', [ProductImageController::class, 'destroy']);             // Delete image
        Route::patch('/{id}/make-primary', [ProductImageController::class, 'makePrimary']); // Set as primary
        Route::patch('/{id}/toggle-active', [ProductImageController::class, 'toggleActive']); // Toggle active status
    });

    // Purchase Order Management Routes
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::post('/', [PurchaseOrderController::class, 'create']);
        Route::get('/stats', [PurchaseOrderController::class, 'statistics']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'show']);
            Route::put('/', [PurchaseOrderController::class, 'update']);
            Route::post('/approve', [PurchaseOrderController::class, 'approve']);
            Route::post('/receive', [PurchaseOrderController::class, 'receive']);
            Route::post('/cancel', [PurchaseOrderController::class, 'cancel']);
            
            // PO item management
            Route::post('/items', [PurchaseOrderController::class, 'addItem']);
            Route::put('/items/{itemId}', [PurchaseOrderController::class, 'updateItem']);
            Route::delete('/items/{itemId}', [PurchaseOrderController::class, 'removeItem']);
        });
    });

    // Vendor Payment Management Routes
    Route::prefix('vendor-payments')->group(function () {
        Route::get('/', [VendorPaymentController::class, 'index']);
        Route::post('/', [VendorPaymentController::class, 'create']);
        Route::get('/stats', [VendorPaymentController::class, 'statistics']);
        Route::get('/purchase-order/{purchaseOrderId}', [VendorPaymentController::class, 'getByPurchaseOrder']);
        Route::get('/outstanding/{vendorId}', [VendorPaymentController::class, 'getOutstanding']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [VendorPaymentController::class, 'show']);
            Route::post('/allocate', [VendorPaymentController::class, 'allocateAdvance']);
            Route::post('/cancel', [VendorPaymentController::class, 'cancel']);
            Route::post('/refund', [VendorPaymentController::class, 'refund']);
        });
    });

    // Enhanced Vendor Routes (analytics)
    Route::get('/vendors/{id}/analytics', [VendorController::class, 'getVendorAnalytics']);
    Route::get('/vendors/analytics', [VendorController::class, 'getAllVendorsAnalytics']);
    Route::get('/vendors/{id}/purchase-history', [VendorController::class, 'getPurchaseHistory']);
    Route::get('/vendors/{id}/payment-history', [VendorController::class, 'getPaymentHistory']);

    // Product Batch Management Routes
    Route::prefix('batches')->group(function () {
        Route::get('/', [ProductBatchController::class, 'index']);
        Route::post('/', [ProductBatchController::class, 'create']);
        Route::get('/statistics', [ProductBatchController::class, 'getStatistics']);
        Route::get('/low-stock', [ProductBatchController::class, 'getLowStock']);
        Route::get('/expiring-soon', [ProductBatchController::class, 'getExpiringSoon']);
        Route::get('/expired', [ProductBatchController::class, 'getExpired']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [ProductBatchController::class, 'show']);
            Route::put('/', [ProductBatchController::class, 'update']);
            Route::post('/adjust-stock', [ProductBatchController::class, 'adjustStock']);
            Route::delete('/', [ProductBatchController::class, 'destroy']);
        });
    });

    // Product Barcode Management Routes
    Route::prefix('barcodes')->group(function () {
        Route::get('/', [ProductBarcodeController::class, 'index']);
        Route::post('/generate', [ProductBarcodeController::class, 'generate']);
        Route::post('/scan', [ProductBarcodeController::class, 'scan']);
        Route::post('/batch-scan', [ProductBarcodeController::class, 'batchScan']);
        Route::get('/{barcode}/history', [ProductBarcodeController::class, 'getHistory']);
        Route::get('/{barcode}/location', [ProductBarcodeController::class, 'getCurrentLocation']);
        
        Route::prefix('{id}')->group(function () {
            Route::patch('/make-primary', [ProductBarcodeController::class, 'makePrimary']);
            Route::delete('/', [ProductBarcodeController::class, 'deactivate']);
        });
    });

    // Get barcodes for a specific product
    Route::get('/products/{productId}/barcodes', [ProductBarcodeController::class, 'getProductBarcodes']);

    // Product Dispatch Management Routes
    Route::prefix('dispatches')->group(function () {
        Route::get('/', [ProductDispatchController::class, 'index']);
        Route::post('/', [ProductDispatchController::class, 'create']);
        Route::get('/statistics', [ProductDispatchController::class, 'getStatistics']);
        
        // Pathao delivery integration
        Route::get('/pending-shipment', [ProductDispatchController::class, 'getPendingShipment']);
        Route::post('/bulk-create-shipment', [ProductDispatchController::class, 'bulkCreateShipment']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [ProductDispatchController::class, 'show']);
            Route::post('/items', [ProductDispatchController::class, 'addItem']);
            Route::delete('/items/{itemId}', [ProductDispatchController::class, 'removeItem']);
            
            // Barcode scanning for dispatch items
            Route::post('/items/{itemId}/scan-barcode', [ProductDispatchController::class, 'scanBarcode']);
            Route::get('/items/{itemId}/scanned-barcodes', [ProductDispatchController::class, 'getScannedBarcodes']);
            
            Route::patch('/approve', [ProductDispatchController::class, 'approve']);
            Route::patch('/dispatch', [ProductDispatchController::class, 'markDispatched']);
            Route::patch('/deliver', [ProductDispatchController::class, 'markDelivered']);
            Route::patch('/cancel', [ProductDispatchController::class, 'cancel']);
            
            // Create shipment from dispatch
            Route::post('/create-shipment', [ProductDispatchController::class, 'createShipment']);
        });
    });

    // Product Return Management Routes
    Route::prefix('returns')->group(function () {
        Route::get('/', [ProductReturnController::class, 'index']);
        Route::post('/', [ProductReturnController::class, 'store']);
        Route::get('/statistics', [ProductReturnController::class, 'statistics']);
        
        Route::prefix('{id}')->group(function () {
            Route::get('/', [ProductReturnController::class, 'show']);
            Route::patch('/', [ProductReturnController::class, 'update']);
            Route::post('/approve', [ProductReturnController::class, 'approve']);
            Route::post('/reject', [ProductReturnController::class, 'reject']);
            Route::post('/quality-check', [ProductReturnController::class, 'qualityCheck']);
            Route::post('/process', [ProductReturnController::class, 'process']);
            Route::post('/complete', [ProductReturnController::class, 'complete']);
        });
    });

    // Refund Management Routes
    Route::prefix('refunds')->group(function () {
        Route::get('/', [RefundController::class, 'index']);
        Route::post('/', [RefundController::class, 'store']);
        Route::get('/statistics', [RefundController::class, 'statistics']);
        
        Route::prefix('{id}')->group(function () {
            Route::get('/', [RefundController::class, 'show']);
            Route::post('/process', [RefundController::class, 'process']);
            Route::post('/complete', [RefundController::class, 'complete']);
            Route::post('/fail', [RefundController::class, 'fail']);
            Route::post('/cancel', [RefundController::class, 'cancel']);
        });
    });

    // Defective Product Management Routes
    Route::prefix('defective-products')->group(function () {
        // List and statistics
        Route::get('/', [\App\Http\Controllers\DefectiveProductController::class, 'index']);
        Route::get('/available-for-sale', [\App\Http\Controllers\DefectiveProductController::class, 'getAvailableForSale']);
        Route::get('/statistics', [\App\Http\Controllers\DefectiveProductController::class, 'statistics']);
        
        // Mark product as defective and scan barcode
        Route::post('/mark-defective', [\App\Http\Controllers\DefectiveProductController::class, 'markAsDefective']);
        Route::post('/scan', [\App\Http\Controllers\DefectiveProductController::class, 'scanBarcode']);
        
        // Individual defective product operations
        Route::prefix('{id}')->group(function () {
            Route::get('/', [\App\Http\Controllers\DefectiveProductController::class, 'show']);
            Route::post('/inspect', [\App\Http\Controllers\DefectiveProductController::class, 'inspect']);
            Route::post('/make-available', [\App\Http\Controllers\DefectiveProductController::class, 'makeAvailableForSale']);
            Route::post('/sell', [\App\Http\Controllers\DefectiveProductController::class, 'sell']);
            Route::post('/dispose', [\App\Http\Controllers\DefectiveProductController::class, 'dispose']);
            Route::post('/return-to-vendor', [\App\Http\Controllers\DefectiveProductController::class, 'returnToVendor']);
            
            // Image management routes
            Route::post('/images', [\App\Http\Controllers\DefectiveProductController::class, 'uploadImages']);
            Route::get('/images', [\App\Http\Controllers\DefectiveProductController::class, 'getImages']);
            Route::delete('/images', [\App\Http\Controllers\DefectiveProductController::class, 'deleteImage']);
        });
    });

    // ============================================
    // GLOBAL INVENTORY MANAGEMENT ROUTES
    // Company-wide inventory tracking and analytics
    // ============================================
    
    Route::prefix('inventory')->group(function () {
        // Global inventory overview
        Route::get('/global', [InventoryController::class, 'getGlobalInventory']);
        Route::get('/statistics', [InventoryController::class, 'getStatistics']);
        Route::get('/value', [InventoryController::class, 'getInventoryValue']);
        
        // Search and alerts
        Route::post('/search', [InventoryController::class, 'searchProductAcrossStores']);
        Route::get('/low-stock-alerts', [InventoryController::class, 'getLowStockAlerts']);
        Route::get('/stock-aging', [InventoryController::class, 'getStockAging']);
    });

    // ============================================
    // INVENTORY REBALANCING ROUTES
    // Automated suggestions and manual rebalancing between stores
    // ============================================
    
    Route::prefix('inventory-rebalancing')->group(function () {
        // List and statistics
        Route::get('/', [InventoryRebalancingController::class, 'index']);
        Route::get('/statistics', [InventoryRebalancingController::class, 'getStatistics']);
        Route::get('/suggestions', [InventoryRebalancingController::class, 'getSuggestions']);
        
        // Create rebalancing request
        Route::post('/', [InventoryRebalancingController::class, 'create']);
        
        // Rebalancing operations
        Route::prefix('{id}')->group(function () {
            Route::post('/approve', [InventoryRebalancingController::class, 'approve']);
            Route::post('/reject', [InventoryRebalancingController::class, 'reject']);
            Route::post('/cancel', [InventoryRebalancingController::class, 'cancel']);
            Route::post('/complete', [InventoryRebalancingController::class, 'complete']);
        });
    });

    // ============================================
    // BARCODE LOCATION TRACKING ROUTES
    // Track exact location and complete history of every physical unit
    // ============================================
    
    Route::prefix('barcode-tracking')->group(function () {
        // Individual barcode tracking
        Route::get('/{barcode}/location', [BarcodeLocationController::class, 'getBarcodeLocation']);
        Route::get('/{barcode}/history', [BarcodeLocationController::class, 'getBarcodeHistory']);
        
        // Store-based tracking
        Route::get('/store/{storeId}', [BarcodeLocationController::class, 'getBarcodesAtStore']);
        
        // Advanced search and filtering
        Route::post('/search', [BarcodeLocationController::class, 'advancedSearch']);
        
        // Grouped views
        Route::get('/grouped-by-status', [BarcodeLocationController::class, 'getGroupedByStatus']);
        Route::get('/grouped-by-store', [BarcodeLocationController::class, 'getGroupedByStore']);
        Route::get('/grouped-by-product', [BarcodeLocationController::class, 'getGroupedByProduct']);
        
        // Movement history
        Route::get('/movements', [BarcodeLocationController::class, 'getMovements']);
        
        // Statistics and analytics
        Route::get('/statistics', [BarcodeLocationController::class, 'getStatistics']);
        Route::get('/stagnant', [BarcodeLocationController::class, 'getStagnantBarcodes']);
        Route::get('/overdue-transit', [BarcodeLocationController::class, 'getOverdueTransit']);
        
        // Specialized view endpoints
        Route::get('/by-product/{productId}', [BarcodeLocationController::class, 'getByProduct']);
        Route::get('/by-batch/{batchId}', [BarcodeLocationController::class, 'getByBatch']);
        Route::get('/sales', [BarcodeLocationController::class, 'getSales']);
        Route::post('/compare-stores', [BarcodeLocationController::class, 'compareStores']);
        Route::get('/recent', [BarcodeLocationController::class, 'getRecent']);
    });

    // ============================================
    // RECYCLE BIN / SOFT DELETE MANAGEMENT
    // Manage deleted items with 7-day recovery period
    // ============================================
    
    Route::prefix('recycle-bin')->group(function () {
        // List and statistics
        Route::get('/', [RecycleBinController::class, 'index']);
        Route::get('/statistics', [RecycleBinController::class, 'getStatistics']);
        
        // View deleted item details
        Route::get('/{type}/{id}', [RecycleBinController::class, 'show']);
        
        // Restore operations
        Route::post('/restore', [RecycleBinController::class, 'restore']);
        Route::post('/restore-multiple', [RecycleBinController::class, 'restoreMultiple']);
        
        // Permanent delete operations
        Route::delete('/permanent-delete', [RecycleBinController::class, 'permanentDelete']);
        Route::delete('/empty', [RecycleBinController::class, 'emptyRecycleBin']);
        
    // Auto-cleanup (for scheduled jobs)
    Route::post('/auto-cleanup', [RecycleBinController::class, 'autoCleanup']);
    });

}); // End of auth:api middleware group