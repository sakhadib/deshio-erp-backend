# SSLCommerz Payment Method Detection Fix

**Date:** March 1, 2026  
**Priority:** HIGH  
**Issue:** All mobile banking payments via SSLCommerz stored as 'bkash' instead of actual method (Nagad/Rocket)

---

## The Problem

**Testing Team Report:**
> "When customer pays with 'nagad' from sslcommerz, system still stores bkash. all the mobile banking is stored as bkash."

**Root Cause:**
When customers complete payment through SSLCommerz:
1. Order created with `payment_method = 'sslcommerz'`
2. Customer selects Nagad/Rocket/bKash at SSLCommerz payment page
3. SSLCommerz callback sends `card_type` field (e.g., "NAGADMFS", "ROCKETMFS", "DBBLMOBILEBANKING")
4. System **ignored** the `card_type` field completely
5. Order forever shows wrong payment method

**Impact:**
- ❌ Financial reports show incorrect payment method breakdown
- ❌ Cannot reconcile Nagad vs Rocket vs bKash transactions
- ❌ Settlement reports from gateways don't match system records
- ❌ Customer support cannot identify actual payment method used

---

## SSLCommerz Card Type Values

SSLCommerz returns different `card_type` values based on payment method:

### Mobile Banking:
- **Nagad:** `"NAGADMFS"`, `"NAGAD-Nagad"`
- **Rocket:** `"ROCKETMFS"`, `"ROCKET-DBBL"`
- **bKash:** `"BKASH-BKash"`, `"DBBLMOBILEBANKING"`

### Card Payments:
- **Visa:** `"VISA-Dutch Bangla"`, `"VISA-Standard Chartered"`
- **Mastercard:** `"MASTERCARD-City Bank"`, `"MASTER CARD"`
- **Amex:** `"AMEX"`, `"AMERICANEXPRESS"`

### Online Banking:
- **Internet Banking:** `"INTERNETBANKING"`, `"ONLINE"`

---

## The Solution

### 1. Added Payment Method Detection

Created `detectPaymentMethod()` helper method that parses SSLCommerz `card_type`:

```php
private function detectPaymentMethod(string $cardType): string
{
    $cardTypeUpper = strtoupper($cardType);
    
    // Mobile Banking Detection
    if (str_contains($cardTypeUpper, 'NAGAD')) {
        return 'nagad';
    }
    if (str_contains($cardTypeUpper, 'ROCKET')) {
        return 'rocket';
    }
    if (str_contains($cardTypeUpper, 'BKASH') || str_contains($cardTypeUpper, 'DBBLMOBILEBANKING')) {
        return 'bkash';
    }
    
    // Card Detection
    if (str_contains($cardTypeUpper, 'VISA') || 
        str_contains($cardTypeUpper, 'MASTERCARD') || 
        str_contains($cardTypeUpper, 'AMEX')) {
        return 'card';
    }
    
    // Internet Banking
    if (str_contains($cardTypeUpper, 'INTERNETBANKING')) {
        return 'online_banking';
    }
    
    // Default fallback
    return 'sslcommerz';
}
```

### 2. Updated Success Callback

Now extracts `card_type` and updates both payment record and order:

```php
// Detect actual payment method from card_type
$cardType = $request->input('card_type', '');
$actualPaymentMethod = $this->detectPaymentMethod($cardType);

$payment->update([
    'status' => 'completed',
    'metadata' => [
        'actual_payment_method' => $actualPaymentMethod,
        'card_type' => $cardType,
        'card_issuer' => $request->input('card_issuer'),
        'card_brand' => $request->input('card_brand'),
    ]
]);

// Update order's payment_method to reflect actual method used
$order->update(['payment_method' => $actualPaymentMethod]);
```

### 3. Updated IPN Callback

Same logic applied to IPN callback for reliability.

---

## Before vs After

### Before Fix:

**Order Record:**
```json
{
  "payment_method": "sslcommerz",  // ❌ Generic, not helpful
  "payment_status": "paid"
}
```

**Payment Record:**
```json
{
  "status": "completed",
  "payment_details": {
    "card_type": "NAGADMFS"  // ❌ Buried in JSON, not extracted
  }
}
```

### After Fix:

**Order Record:**
```json
{
  "payment_method": "nagad",  // ✅ Actual method used
  "payment_status": "paid"
}
```

**Payment Record:**
```json
{
  "status": "completed",
  "metadata": {
    "actual_payment_method": "nagad",  // ✅ Explicitly captured
    "card_type": "NAGADMFS",
    "card_issuer": "Nagad Mobile Financial Services",
    "card_brand": "NAGAD"
  },
  "payment_details": {
    "card_type": "NAGADMFS"  // ✅ Still preserved
  }
}
```

---

## Testing

### Test Scenarios:

1. **✅ Nagad Payment:**
   - Complete payment via Nagad
   - Verify order shows `payment_method = 'nagad'`
   - Verify payment metadata contains correct card_type

2. **✅ Rocket Payment:**
   - Complete payment via Rocket
   - Verify order shows `payment_method = 'rocket'`
   
3. **✅ bKash Payment:**
   - Complete payment via bKash
   - Verify order shows `payment_method = 'bkash'`

4. **✅ Card Payment:**
   - Complete payment via Visa/Mastercard
   - Verify order shows `payment_method = 'card'`
   - Verify card_issuer captured correctly

### SQL Query to Verify:

```sql
-- Check recent SSLCommerz payments
SELECT 
    o.order_number,
    o.payment_method,
    p.metadata->>'$.actual_payment_method' as detected_method,
    p.payment_details->>'$.card_type' as card_type,
    p.created_at
FROM orders o
JOIN order_payments p ON p.order_id = o.id
WHERE p.payment_details->>'$.card_type' IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 20;
```

**Expected Results:**
- Nagad payments: `payment_method = 'nagad'`
- Rocket payments: `payment_method = 'rocket'`
- bKash payments: `payment_method = 'bkash'`
- Card payments: `payment_method = 'card'`

---

## Files Modified

1. **app/Http/Controllers/SslcommerzController.php**
   - Added `detectPaymentMethod()` helper (lines 14-55)
   - Updated `success()` callback (lines 40-65)
   - Updated `ipn()` callback (lines 185-210)

---

## Impact

### Before Fix:
- ❌ All mobile banking payments show as 'sslcommerz' or wrong method
- ❌ Cannot generate accurate payment method reports
- ❌ Cannot reconcile with gateway settlement reports
- ❌ Manual data correction required for accounting

### After Fix:
- ✅ Accurate payment method capture (nagad/rocket/bkash/card)
- ✅ Correct financial reporting by payment method
- ✅ Easy reconciliation with gateway statements
- ✅ Better customer support (can see actual method used)
- ✅ Proper tracking of mobile banking vs card usage

---

## Data Migration (Optional)

For existing orders with incorrect payment methods:

```sql
-- Update orders where payment_details contains card_type
UPDATE orders o
JOIN order_payments p ON p.order_id = o.id
SET o.payment_method = CASE
    WHEN p.payment_details->>'$.card_type' LIKE '%NAGAD%' THEN 'nagad'
    WHEN p.payment_details->>'$.card_type' LIKE '%ROCKET%' THEN 'rocket'
    WHEN p.payment_details->>'$.card_type' LIKE '%BKASH%' THEN 'bkash'
    WHEN p.payment_details->>'$.card_type' LIKE '%DBBLMOBILEBANKING%' THEN 'bkash'
    WHEN p.payment_details->>'$.card_type' LIKE '%VISA%' THEN 'card'
    WHEN p.payment_details->>'$.card_type' LIKE '%MASTERCARD%' THEN 'card'
    ELSE o.payment_method
END
WHERE o.payment_method = 'sslcommerz'
AND p.payment_details->>'$.card_type' IS NOT NULL;
```

---

## Deployment Notes

1. **No Database Migration Required** - only code changes
2. **Backward Compatible** - existing data unaffected
3. **Test on Staging First** with real SSLCommerz sandbox
4. **Monitor Logs** for new card_type patterns not yet mapped

```bash
# Monitor SSLCommerz callbacks after deployment
tail -f storage/logs/laravel.log | grep "card_type"
```

---

## Prevention

To prevent similar issues:
1. ✅ Always parse gateway callback fields completely
2. ✅ Store both raw callback and extracted values
3. ✅ Add payment method validation in reports
4. ✅ Alert when payment_method = 'sslcommerz' (should be specific)

---

## Related Issues

This fix resolves the payment method detection issue. Related systems now benefit:
- **Financial Reports:** Accurate payment method breakdown
- **Settlement Reconciliation:** Match with Nagad/Rocket/bKash statements
- **Customer Support:** Can identify actual payment method used
- **Analytics:** Proper tracking of mobile banking adoption

---

## Questions?

Contact backend team for:
- SSLCommerz callback documentation
- Payment method mapping logic
- Testing assistance
- Data migration support
