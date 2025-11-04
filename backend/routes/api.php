<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\VendorPaymentController;
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

        Route::prefix('{id}')->group(function () {
            Route::get('/', [CategoriesController::class, 'getCategory']);
            Route::put('/', [CategoriesController::class, 'updateCategory']);
            Route::delete('/', [CategoriesController::class, 'deleteCategory']);
            Route::patch('/activate', [CategoriesController::class, 'activateCategory']);
            Route::patch('/deactivate', [CategoriesController::class, 'deactivateCategory']);
        });
    });

    // Bulk category operations
    Route::patch('/categories/bulk/status', [CategoriesController::class, 'bulkUpdateStatus']);
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
});
