# SSLCommerz Quick Reference Guide

## Configuration

### Environment Variables (.env)
```env
SSLC_SANDBOX=false                          # Mode: false = Production, true = Sandbox
SSLC_STORE_ID=errumbd1live                  # Your SSLCommerz Store ID
SSLC_STORE_PASSWORD=your_password_here      # Your Store Password
SSLC_STORE_CURRENCY=BDT                     # Currency Code
SSLC_STORE_NAME=errumbd                     # Your Store Name
SSLC_STORE_URL=https://www.errumbd.net      # Your Store URL
```

### Test Connection
```bash
php artisan sslcommerz:test
```

## Quick Implementation

### 1. Initiate Payment
```php
use Raziul\Sslcommerz\Facades\Sslcommerz;

$transactionId = 'TXN-' . $order->id . '-' . time();

$response = Sslcommerz::setOrder($amount, $transactionId, 'Order Description')
    ->setCustomer($name, $email, $phone)
    ->setShippingInfo($itemCount, $address)
    ->makePayment(['order_id' => $order->id]);

if ($response->success()) {
    $gatewayUrl = $response->gatewayPageURL();
    // Redirect customer to $gatewayUrl
} else {
    $error = $response->failedReason();
    // Handle error
}
```

### 2. Handle Success Callback
```php
public function success(Request $request)
{
    // Verify hash
    if (!Sslcommerz::verifyHash($request->all())) {
        return response()->json(['message' => 'Invalid hash'], 400);
    }

    // Validate payment
    $valId = $request->input('val_id');
    $amount = $request->input('amount');
    $isValid = Sslcommerz::validatePayment($request->all(), $valId, $amount);

    if ($isValid) {
        // Update order status
        // Mark payment as completed
    }
}
```

### 3. Handle Failure Callback
```php
public function failure(Request $request)
{
    $transactionId = $request->input('tran_id');
    // Update payment status to 'failed'
    // Update order status to 'payment_failed'
}
```

## API Methods

### Sslcommerz Facade Methods

| Method | Parameters | Description |
|--------|-----------|-------------|
| `setOrder()` | `($amount, $tranId, $description)` | Set order amount and transaction ID |
| `setCustomer()` | `($name, $email, $phone)` | Set customer information |
| `setShippingInfo()` | `($itemCount, $address)` | Set shipping details |
| `makePayment()` | `(array $additionalData)` | Initiate payment session |
| `verifyHash()` | `(array $data)` | Verify callback hash |
| `validatePayment()` | `($data, $valId, $amount)` | Validate payment with SSLCommerz |

### Response Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `success()` | `boolean` | Check if payment session created successfully |
| `gatewayPageURL()` | `string` | Get payment gateway URL |
| `failedReason()` | `string` | Get failure reason if payment initiation failed |

## Response Structures

### Success Response (from makePayment)
```php
[
    'status' => 'SUCCESS',
    'GatewayPageURL' => 'https://pay.sslcommerz.com/ddc256474a8bbfaf...',
    'sessionkey' => 'unique_session_key_here',
    'store_id' => 'errumbd1live',
    'verify_sign' => 'hash_signature',
    'verify_key' => 'verification_key',
]
```

### Callback Request Data (from SSLCommerz)
```php
[
    'tran_id' => 'TXN-123-1234567890',           // Your transaction ID
    'val_id' => 'validation_id',                  // SSLCommerz validation ID
    'amount' => '1000.00',                        // Transaction amount
    'card_type' => 'VISA-Dutch Bangla',          // Card type used
    'status' => 'VALID',                          // Transaction status
    'tran_date' => '2024-01-15 10:30:45',        // Transaction date/time
    'currency' => 'BDT',                          // Currency
    'card_issuer' => 'STANDARD CHARTERED BANK',   // Issuing bank
    'card_no' => '471170XXXXXX7787',             // Masked card number
    'bank_tran_id' => 'bank_transaction_id',      // Bank transaction ID
    'store_amount' => '975.00',                   // Amount after SSLCommerz fees
    'verify_sign' => 'hash_for_verification',     // Hash signature
    'risk_level' => '0',                          // Risk assessment
    'risk_title' => 'Safe',                       // Risk assessment title
    'order_id' => '123',                          // Custom data (from makePayment)
]
```

## Callback Routes

### Define Routes (routes/api.php)
```php
Route::controller(SslcommerzController::class)
    ->prefix('sslcommerz')
    ->group(function () {
        Route::post('success', 'success')->name('sslcommerz.success');
        Route::post('failure', 'failure')->name('sslcommerz.failure');
        Route::post('cancel', 'cancel')->name('sslcommerz.cancel');
        Route::post('ipn', 'ipn')->name('sslcommerz.ipn');
    });
```

### Callback URLs (Configure in SSLCommerz Merchant Panel)
```
Success URL: https://your-domain.com/api/sslcommerz/success
Failure URL: https://your-domain.com/api/sslcommerz/failure
Cancel URL:  https://your-domain.com/api/sslcommerz/cancel
IPN URL:     https://your-domain.com/api/sslcommerz/ipn
```

## Common Tasks

### Check Payment Status
```php
// In your callback handler
$status = $request->input('status');  // 'VALID', 'FAILED', 'CANCELLED'

switch ($status) {
    case 'VALID':
        // Payment successful
        break;
    case 'FAILED':
        // Payment failed
        break;
    case 'CANCELLED':
        // Payment cancelled by user
        break;
}
```

### Store Payment Details
```php
OrderPayment::create([
    'order_id' => $order->id,
    'amount' => $amount,
    'transaction_id' => $request->input('tran_id'),
    'status' => 'completed',  // or 'failed', 'cancelled'
    'payment_details' => $request->all(),  // Store all callback data
    'payment_date' => now(),
]);
```

### Refund Payment (Manual Process)
SSLCommerz refunds are processed manually through the merchant panel:
1. Login to merchant.sslcommerz.com
2. Navigate to **Transactions** → **Search Transaction**
3. Find the transaction by Transaction ID
4. Click **Refund** button
5. Enter refund amount and reason
6. Confirm refund

## Error Handling

### Common Error Messages

| Error Message | Cause | Solution |
|--------------|-------|----------|
| "Store Credential Error" | Invalid Store ID or Password | Verify credentials in .env file |
| "Store is De-active" | Store not activated | Contact SSLCommerz support |
| "Invalid hash" | Hash verification failed | Check store password is correct |
| "Payment validation failed" | Payment not verified with SSLCommerz | Ensure validatePayment() is called |
| "Amount mismatch" | Callback amount differs from order | Log and investigate transaction |

### Debugging Checklist

1. **Connection Issues:**
   ```bash
   php artisan sslcommerz:test
   ```

2. **Config Issues:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. **Check Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verify Sandbox Mode:**
   ```php
   // Check current mode
   config('sslcommerz.sandbox')  // true = Sandbox, false = Production
   ```

## Security Best Practices

### Always Verify Callbacks
```php
// Step 1: Verify hash
if (!Sslcommerz::verifyHash($request->all())) {
    abort(400, 'Invalid hash');
}

// Step 2: Validate payment
$isValid = Sslcommerz::validatePayment(
    $request->all(),
    $request->input('val_id'),
    $request->input('amount')
);

if (!$isValid) {
    abort(400, 'Payment validation failed');
}

// Step 3: Check amount matches order
$expectedAmount = $order->total_amount;
$receivedAmount = $request->input('amount');

if ($expectedAmount != $receivedAmount) {
    Log::critical('Payment amount mismatch', [
        'expected' => $expectedAmount,
        'received' => $receivedAmount,
        'transaction_id' => $request->input('tran_id'),
    ]);
    abort(400, 'Amount mismatch');
}
```

### Never Trust Client Data
```php
// ❌ WRONG - Don't get amount from request
$amount = $request->input('amount');

// ✅ CORRECT - Get amount from your database
$order = Order::find($request->input('order_id'));
$amount = $order->total_amount;
```

### IP Whitelisting
Configure your server's IP addresses in SSLCommerz merchant panel:
1. Login to merchant panel
2. **Settings** → **Store Settings**
3. Add your server IPs to whitelist
4. Save changes

## Testing

### Test in Sandbox Mode
```env
SSLC_SANDBOX=true
SSLC_STORE_ID=your_sandbox_store_id
SSLC_STORE_PASSWORD=your_sandbox_password
```

### Test Cards (Sandbox)
Use these test cards in sandbox mode:

| Card Type | Card Number | CVV | Expiry |
|-----------|-------------|-----|--------|
| Visa | 4111111111111111 | 123 | Any future date |
| MasterCard | 5555555555554444 | 123 | Any future date |

### Test Flow
1. Create order with `payment_method: 'sslcommerz'`
2. Get `payment_url` from response
3. Navigate to payment URL
4. Use test card to complete payment
5. Verify callback handlers are called
6. Check order status updated correctly

## Production Deployment

### Pre-Launch Checklist
- [ ] Set `SSLC_SANDBOX=false`
- [ ] Update to production store credentials
- [ ] Run `php artisan sslcommerz:test` successfully
- [ ] Register callback URLs in merchant panel
- [ ] Whitelist production server IP
- [ ] Test end-to-end payment flow
- [ ] Set up monitoring/alerts
- [ ] Document refund procedures

### Monitoring
```php
// Add to your callback handlers
Log::info('SSLCommerz Payment', [
    'status' => $request->input('status'),
    'transaction_id' => $request->input('tran_id'),
    'amount' => $request->input('amount'),
    'card_type' => $request->input('card_type'),
]);
```

## Useful Commands

```bash
# Test SSLCommerz connection
php artisan sslcommerz:test

# Clear config cache
php artisan config:clear

# View current config
php artisan config:show sslcommerz

# Check routes
php artisan route:list --name=sslcommerz

# Monitor logs
tail -f storage/logs/laravel.log | grep -i sslcommerz
```

## Resources

- **Merchant Panel**: https://merchant.sslcommerz.com/
- **Developer Portal**: https://developer.sslcommerz.com/
- **Support Email**: support@sslcommerz.com
- **Package Documentation**: https://github.com/raziul/sslcommerz-laravel

## Related Documentation
- [SSLCommerz Implementation Summary](SSLCOMMERZ_IMPLEMENTATION_SUMMARY.md)
- [Payment System Documentation](PAYMENT_SYSTEM_DOCUMENTATION.md)
- [E-commerce Order Workflow](ECOMMERCE_ORDER_WORKFLOW.md)
