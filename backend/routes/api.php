<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\VendorPaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductBatchController;
use App\Http\Controllers\ProductBarcodeController;
use App\Http\Controllers\ProductDispatchController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ProductReturnController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryRebalancingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CategoriesController;
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

// Auth routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
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
});
