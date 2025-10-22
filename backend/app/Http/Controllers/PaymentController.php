<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Get available payment methods for an order
     */
    public function getAvailableMethods(Request $request, Order $order): JsonResponse
    {
        $customerType = $order->customer->customer_type;
        $methods = PaymentMethod::getAvailableMethodsForCustomerType($customerType);

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Get payment methods for a customer type
     */
    public function getMethodsByCustomerType(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_type' => ['required', Rule::in(['counter', 'social_commerce', 'ecommerce'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $methods = PaymentMethod::getAvailableMethodsForCustomerType($request->customer_type);

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Process a payment for an order
     */
    public function processPayment(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_data' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if order can accept payments
            if (!$order->canAcceptPayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot accept payments in its current state',
                ], 400);
            }

            // Check remaining amount
            $remainingAmount = $order->getRemainingAmount();
            if ($request->amount > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Payment amount exceeds remaining balance of {$remainingAmount}",
                ], 400);
            }

            // Get payment method
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

            // Validate payment method is allowed for customer type
            if (!$paymentMethod->isAllowedForCustomerType($order->customer->customer_type)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not allowed for this customer type',
                ], 400);
            }

            // Create payment
            $payment = $order->addPayment(
                $paymentMethod,
                $request->amount,
                $request->payment_data ?? [],
                auth()->user() // Assuming employee is authenticated
            );

            // Process the payment
            $transactionReference = $request->payment_data['transaction_reference'] ?? null;
            $externalReference = $request->payment_data['external_reference'] ?? null;

            if ($order->processPayment($payment, $transactionReference, $externalReference)) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'payment' => $payment->load('paymentMethod'),
                        'order_summary' => $order->payment_summary,
                    ],
                ]);
            } else {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process payment',
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process multiple payments for an order (fragmented payment)
     */
    public function processMultiplePayments(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.payment_data' => 'nullable|array',
            'payments.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if order can accept payments
            if (!$order->canAcceptPayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot accept payments in its current state',
                ], 400);
            }

            $totalPaymentAmount = collect($request->payments)->sum('amount');
            $remainingAmount = $order->getRemainingAmount();

            if ($totalPaymentAmount > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Total payment amount exceeds remaining balance of {$remainingAmount}",
                ], 400);
            }

            $processedPayments = [];
            $failedPayments = [];

            foreach ($request->payments as $paymentData) {
                try {
                    $paymentMethod = PaymentMethod::findOrFail($paymentData['payment_method_id']);

                    // Validate payment method is allowed for customer type
                    if (!$paymentMethod->isAllowedForCustomerType($order->customer->customer_type)) {
                        $failedPayments[] = [
                            'payment_method' => $paymentMethod->name,
                            'amount' => $paymentData['amount'],
                            'error' => 'Payment method not allowed for this customer type',
                        ];
                        continue;
                    }

                    // Create payment
                    $payment = $order->addPayment(
                        $paymentMethod,
                        $paymentData['amount'],
                        $paymentData['payment_data'] ?? [],
                        auth()->user()
                    );

                    // Process the payment
                    $transactionReference = $paymentData['payment_data']['transaction_reference'] ?? null;
                    $externalReference = $paymentData['payment_data']['external_reference'] ?? null;

                    if ($order->processPayment($payment, $transactionReference, $externalReference)) {
                        $processedPayments[] = $payment->load('paymentMethod');
                    } else {
                        $failedPayments[] = [
                            'payment_method' => $paymentMethod->name,
                            'amount' => $paymentData['amount'],
                            'error' => 'Payment processing failed',
                        ];
                    }

                } catch (\Exception $e) {
                    $failedPayments[] = [
                        'payment_method' => $paymentData['payment_method_id'],
                        'amount' => $paymentData['amount'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (count($processedPayments) > 0) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => count($processedPayments) . ' payment(s) processed successfully',
                    'data' => [
                        'processed_payments' => $processedPayments,
                        'failed_payments' => $failedPayments,
                        'order_summary' => $order->payment_summary,
                    ],
                ]);
            } else {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'All payments failed to process',
                    'data' => [
                        'failed_payments' => $failedPayments,
                    ],
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Multiple payment processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payments for an order
     */
    public function getOrderPayments(Order $order): JsonResponse
    {
        $payments = $order->payments()->with('paymentMethod')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'summary' => $order->payment_summary,
            ],
        ]);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Request $request, OrderPayment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if payment can be refunded
            if (!$payment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed payments can be refunded',
                ], 400);
            }

            // Check refund amount
            if ($request->refund_amount > $payment->getRefundableAmount()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount exceeds refundable balance',
                ], 400);
            }

            if ($payment->refund($request->refund_amount, $request->reason)) {
                // Update order payment status
                $payment->order->updatePaymentStatus();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment refunded successfully',
                    'data' => [
                        'payment' => $payment->load('paymentMethod'),
                        'order_summary' => $payment->order->payment_summary,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund processing failed',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refund failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(Request $request): JsonResponse
    {
        $query = OrderPayment::query();

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('processed_at', [$request->start_date, $request->end_date]);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $stats = [
            'total_payments' => (clone $query)->count(),
            'completed_payments' => (clone $query)->completed()->count(),
            'pending_payments' => (clone $query)->pending()->count(),
            'failed_payments' => (clone $query)->failed()->count(),
            'refunded_payments' => (clone $query)->refunded()->count(),
            'total_amount' => (clone $query)->completed()->sum('amount'),
            'total_fees' => (clone $query)->completed()->sum('fee_amount'),
            'total_refunded' => (clone $query)->refunded()->sum('refunded_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}