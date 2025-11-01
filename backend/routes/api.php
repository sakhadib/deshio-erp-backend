<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VendorController;
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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Employee management routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'getEmployees']);
        Route::post('/', [EmployeeController::class, 'createEmployee']);
        Route::get('/stats', [EmployeeController::class, 'getEmployeeStats']);
        Route::get('/by-store/{storeId}', [EmployeeController::class, 'getEmployeesByStore']);
        Route::get('/by-role/{roleId}', [EmployeeController::class, 'getEmployeesByRole']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [EmployeeController::class, 'getEmployee']);
            Route::put('/', [EmployeeController::class, 'updateEmployee']);
            Route::delete('/', [EmployeeController::class, 'deleteEmployee']);
            Route::patch('/role', [EmployeeController::class, 'changeEmployeeRole']);
            Route::patch('/transfer', [EmployeeController::class, 'transferEmployee']);
            Route::patch('/activate', [EmployeeController::class, 'activateEmployee']);
            Route::patch('/deactivate', [EmployeeController::class, 'deactivateEmployee']);
            Route::patch('/password', [EmployeeController::class, 'changePassword']);
            Route::get('/subordinates', [EmployeeController::class, 'getSubordinates']);
        });
    });

    // Bulk operations
    Route::patch('/employees/bulk/status', [EmployeeController::class, 'bulkUpdateStatus']);

    // Vendor management routes
    Route::prefix('vendors')->group(function () {
        Route::get('/', [VendorController::class, 'getVendors']);
        Route::post('/', [VendorController::class, 'createVendor']);
        Route::get('/stats', [VendorController::class, 'getVendorStats']);
        Route::get('/by-type/{type}', [VendorController::class, 'getVendorsByType']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [VendorController::class, 'getVendor']);
            Route::put('/', [VendorController::class, 'updateVendor']);
            Route::delete('/', [VendorController::class, 'deleteVendor']);
            Route::patch('/activate', [VendorController::class, 'activateVendor']);
            Route::patch('/deactivate', [VendorController::class, 'deactivateVendor']);
        });
    });

    // Bulk vendor operations
    Route::patch('/vendors/bulk/status', [VendorController::class, 'bulkUpdateStatus']);
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
});