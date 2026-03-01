# Card Payment Status Bug Fix

**Date:** March 1, 2026  
**Issue:** Card payments received but payment status not updating to completed  
**Severity:** HIGH - Causes order fulfillment delays and customer confusion

---

## The Problem

**PM Report:**
> "On card payment, some fee is getting added and then system is not clearing the payment status. payments are received but the payment status does not show that it is completed."

**What's Happening:**
1. Customer completes card payment through SSLCommerz gateway
2. SSLCommerz calls back to backend with payment confirmation
3. Backend updates `OrderPayment` record status to 'completed' ✓
4. **BUT** backend never updates the `Order`'s payment status fields ✗
5. Order still shows as unpaid in the system
6. Fulfillment team doesn't see payment and doesn't process the order

---

## Root Cause

In `SslcommerzController.php`, the payment callback handlers directly update the payment status **without updating the order's payment status**.

### Before Fix (Lines 41-45):

```php
if ($payment) {
    $payment->update([
        'status' => 'completed',
        'payment_details' => $request->all()
    ]);
}
// Missing: $order->updatePaymentStatus();
```

### What Should Happen:

The `Order::updatePaymentStatus()` method must be called to:
- Calculate total paid amount from all completed payments
- Update `order.payment_status` (unpaid/partial/paid)
- Update `order.paid_amount`
- Update `order.outstanding_amount`

### Code Flow Comparison:

**Direct Payment (POS):**
```
OrderPayment::complete() 
  → updates payment status
  → calls $this->order->updatePaymentStatus() ✓
  → order status updated ✓
```

**SSLCommerz Payment (Before Fix):**
```
SslcommerzController::success()
  → $payment->update(['status' => 'completed'])
  → ❌ never calls $order->updatePaymentStatus()
  → order status NEVER updated ✗
```

---

## The Fix

### 1. Success Callback - Updated 3 Things:

```php
if ($payment) {
    // 1. Calculate fee from SSLCommerz response
    $amount = floatval($request->input('amount'));
    $storeAmount = floatval($request->input('store_amount', $amount));
    $fee = $amount - $storeAmount;

    $payment->update([
        'status' => 'completed',
        'completed_at' => now(),
        'fee_amount' => $fee,                                    // NEW: Track gateway fee
        'net_amount' => $storeAmount,                            // NEW: Track net amount
        'external_reference' => $request->input('bank_tran_id'), // NEW: Bank transaction ID
        'payment_details' => $request->all()
    ]);

    // 2. CRITICAL FIX: Update order payment status
    $order->updatePaymentStatus();
}
```

**What Changed:**
- ✅ Added fee calculation from SSLCommerz response (`amount` - `store_amount`)
- ✅ Set `completed_at` timestamp
- ✅ Store bank transaction ID for reconciliation
- ✅ **Call `$order->updatePaymentStatus()` to update order fields**

### 2. Failure Callback - Added:

```php
if ($payment) {
    $payment->update([
        'status' => 'failed',
        'failed_at' => now(),                              // NEW: Timestamp
        'failure_reason' => $request->input('error', ...),  // NEW: Failure reason
        'payment_details' => $request->all()
    ]);

    // NEW: Update order payment status
    $order->updatePaymentStatus();
}
```

### 3. Cancel Callback - Added:

```php
if ($payment) {
    $payment->update([
        'status' => 'cancelled',
        'payment_details' => $request->all()
    ]);

    // NEW: Update order payment status
    $order->updatePaymentStatus();
}
```

### 4. IPN Callback - Full Logic:

```php
$paymentStatus = match($status) {
    'VALID', 'VALIDATED' => 'completed',
    'FAILED' => 'failed',
    'CANCELLED' => 'cancelled',
    default => 'pending'
};

// Build update data dynamically
$updateData = [
    'status' => $paymentStatus,
    'payment_details' => $request->all()
];

if ($paymentStatus === 'completed') {
    $amount = floatval($request->input('amount'));
    $storeAmount = floatval($request->input('store_amount', $amount));
    $fee = $amount - $storeAmount;

    $updateData['completed_at'] = now();
    $updateData['fee_amount'] = $fee;
    $updateData['net_amount'] = $storeAmount;
    $updateData['external_reference'] = $request->input('bank_tran_id');
} elseif ($paymentStatus === 'failed') {
    $updateData['failed_at'] = now();
    $updateData['failure_reason'] = $request->input('error', 'Payment failed');
}

$payment->update($updateData);

// NEW: Update order payment status
$order->updatePaymentStatus();
```

---

## Fee Tracking Bonus Fix

**Additional Issue Found:**
SSLCommerz charges transaction fees (typically 2-3% in Bangladesh), but the system wasn't tracking these fees.

**SSLCommerz Response Contains:**
- `amount`: Total amount charged to customer (e.g., 2770.00 BDT)
- `store_amount`: Net amount merchant receives (e.g., 2697.50 BDT)
- Difference is the gateway fee (72.50 BDT in this example)

**Now Tracking:**
- `fee_amount`: Gateway transaction fee
- `net_amount`: Actual amount received after fees
- `external_reference`: Bank transaction ID for reconciliation

**Benefits:**
- ✅ Accurate financial reporting
- ✅ Can reconcile with SSLCommerz settlement reports
- ✅ Track transaction costs per order

---

## Files Modified

1. `app/Http/Controllers/SslcommerzController.php`
   - Updated `success()` method (lines 30-50)
   - Updated `failure()` method (lines 65-90)
   - Updated `cancel()` method (lines 100-130)
   - Updated `ipn()` method (lines 145-185)

---

## Testing Checklist

### Test Scenarios:

1. **✅ Successful Card Payment:**
   - Place order via e-commerce
   - Complete payment via SSLCommerz
   - Verify order shows `payment_status = 'paid'`
   - Verify `paid_amount` equals `total_amount`
   - Verify `outstanding_amount = 0`
   - Verify fee is captured in payment record

2. **✅ Failed Card Payment:**
   - Initiate payment
   - Use test card that fails
   - Verify order shows `payment_status = 'pending'`
   - Verify payment record has `status = 'failed'` with reason

3. **✅ Cancelled Payment:**
   - Initiate payment
   - Click "Cancel" on payment gateway
   - Verify order status updates correctly
   - Verify payment record cancelled

4. **✅ Partial Payment + Card:**
   - Make partial cash payment
   - Complete remaining with card
   - Verify order transitions from `partial` → `paid` correctly

### Database Checks:

```sql
-- Check payment status update
SELECT 
    o.order_number,
    o.payment_status,
    o.total_amount,
    o.paid_amount,
    o.outstanding_amount,
    p.status as payment_status,
    p.amount,
    p.fee_amount,
    p.net_amount
FROM orders o
LEFT JOIN order_payments p ON p.order_id = o.id
WHERE o.payment_method = 'sslcommerz'
ORDER BY o.created_at DESC
LIMIT 10;
```

**Expected After Fix:**
- `o.payment_status` should be 'paid' when payment completed
- `o.paid_amount` should equal `o.total_amount`
- `p.fee_amount` should contain SSLCommerz fee (amount - store_amount)
- `p.external_reference` should contain bank transaction ID

---

## Impact

**Before Fix:**
- ❌ Orders with successful card payments showed as unpaid
- ❌ Fulfillment team couldn't identify paid orders
- ❌ Customer service received complaints about "payment not received"
- ❌ Gateway fees not tracked for accounting

**After Fix:**
- ✅ Order payment status updates immediately after card payment
- ✅ Fulfillment can process paid orders without delay
- ✅ Accurate financial reporting with fee tracking
- ✅ Better reconciliation with gateway settlement reports

---

## Related Systems

This fix ensures consistency across:
- **Order Management:** Orders show correct payment status
- **Accounting System:** Transaction records created by `OrderPaymentObserver`
- **Fulfillment:** Can identify paid orders for processing
- **Customer Portal:** Shows accurate payment status
- **Financial Reports:** Includes gateway fees in cost analysis

---

## Deployment Notes

1. **No Database Migration Required** - only code changes
2. **Backward Compatible** - existing completed payments unaffected
3. **Test on Staging First** with real SSLCommerz test credentials
4. **Monitor Logs** for callback errors after deployment

```bash
# Monitor SSLCommerz callbacks
tail -f storage/logs/laravel.log | grep SSLCommerz
```

---

## Prevention

To prevent similar issues in future:
1. ✅ Always call `$order->updatePaymentStatus()` after changing payment status
2. ✅ Use `OrderPayment::complete()` method instead of direct update when possible
3. ✅ Add unit tests for payment status transitions
4. ✅ Add integration tests for gateway callbacks

---

## Questions?

Contact the backend team for:
- SSLCommerz integration details
- Payment flow questions
- Testing assistance
