<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment routes
Route::middleware('auth:sanctum')->group(function () {
    // Payment methods
    Route::get('/payment-methods', [PaymentController::class, 'getMethodsByCustomerType']);
    Route::get('/orders/{order}/payment-methods', [PaymentController::class, 'getAvailableMethods']);

    // Order payments
    Route::post('/orders/{order}/payments', [PaymentController::class, 'processPayment']);
    Route::post('/orders/{order}/payments/multiple', [PaymentController::class, 'processMultiplePayments']);
    Route::get('/orders/{order}/payments', [PaymentController::class, 'getOrderPayments']);

    // Payment refunds
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refundPayment']);

    // Payment statistics
    Route::get('/payments/stats', [PaymentController::class, 'getPaymentStats']);
});
