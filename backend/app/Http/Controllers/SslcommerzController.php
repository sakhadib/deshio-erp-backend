<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Raziul\Sslcommerz\Facades\Sslcommerz;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SslcommerzController extends Controller
{
    public function success(Request $request)
    {
        // Verify hash
        if (!Sslcommerz::verifyHash($request->all())) {
            return response()->json(['message' => 'Invalid hash'], 400);
        }

        $transactionId = $request->input('tran_id');
        $amount = $request->input('amount');
        $valId = $request->input('val_id');

        // Validate payment with SSLCommerz
        $isValid = Sslcommerz::validatePayment($request->all(), $valId, $amount);

        if (!$isValid) {
            return response()->json(['message' => 'Payment validation failed'], 400);
        }

        DB::beginTransaction();
        try {
            // Find order by transaction_id stored in order_id field
            $order = Order::where('id', $request->input('value_a'))->firstOrFail();

            // Update payment status
            $payment = OrderPayment::where('order_id', $order->id)
                ->where('transaction_id', $transactionId)
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => 'completed',
                    'payment_details' => $request->all()
                ]);
            }

            // Update order status
            $order->update(['status' => 'pending_assignment']);

            DB::commit();

            return response()->json([
                'message' => 'Payment successful',
                'order_id' => $order->id,
                'transaction_id' => $transactionId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SSLCommerz success callback error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing payment'], 500);
        }
    }

    public function failure(Request $request)
    {
        $transactionId = $request->input('tran_id');
        
        DB::beginTransaction();
        try {
            $order = Order::where('id', $request->input('value_a'))->first();
            
            if ($order) {
                $payment = OrderPayment::where('order_id', $order->id)
                    ->where('transaction_id', $transactionId)
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status' => 'failed',
                        'payment_details' => $request->all()
                    ]);
                }

                $order->update(['status' => 'payment_failed']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment failed',
                'transaction_id' => $transactionId
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SSLCommerz failure callback error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing failure'], 500);
        }
    }

    public function cancel(Request $request)
    {
        $transactionId = $request->input('tran_id');
        
        DB::beginTransaction();
        try {
            $order = Order::where('id', $request->input('value_a'))->first();
            
            if ($order) {
                $payment = OrderPayment::where('order_id', $order->id)
                    ->where('transaction_id', $transactionId)
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status' => 'cancelled',
                        'payment_details' => $request->all()
                    ]);
                }

                $order->update(['status' => 'cancelled']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment cancelled',
                'transaction_id' => $transactionId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SSLCommerz cancel callback error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing cancellation'], 500);
        }
    }

    public function ipn(Request $request)
    {
        // Verify hash
        if (!Sslcommerz::verifyHash($request->all())) {
            return response()->json(['message' => 'Invalid hash'], 400);
        }

        $transactionId = $request->input('tran_id');
        $status = $request->input('status');

        DB::beginTransaction();
        try {
            $order = Order::where('id', $request->input('value_a'))->first();
            
            if ($order) {
                $payment = OrderPayment::where('order_id', $order->id)
                    ->where('transaction_id', $transactionId)
                    ->first();

                if ($payment) {
                    $paymentStatus = match($status) {
                        'VALID', 'VALIDATED' => 'completed',
                        'FAILED' => 'failed',
                        'CANCELLED' => 'cancelled',
                        default => 'pending'
                    };

                    $payment->update([
                        'status' => $paymentStatus,
                        'payment_details' => $request->all()
                    ]);
                }

                if ($status === 'VALID' || $status === 'VALIDATED') {
                    $order->update(['status' => 'pending_assignment']);
                }
            }

            DB::commit();

            return response()->json(['message' => 'IPN processed']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SSLCommerz IPN error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing IPN'], 500);
        }
    }
}
