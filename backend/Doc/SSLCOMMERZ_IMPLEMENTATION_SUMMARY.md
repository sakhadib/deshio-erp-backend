# SSLCommerz Payment Gateway - Implementation Summary

## Overview
SSLCommerz is integrated as the primary payment gateway for online orders in the ERRUM BD ERP system. It handles secure payment processing for e-commerce orders with support for multiple payment methods including credit/debit cards, mobile banking, and internet banking.

## Package Information
- **Package**: `raziul/sslcommerz-laravel` v1.0
- **Facade**: `Raziul\Sslcommerz\Facades\Sslcommerz`
- **Type**: Payment Gateway Integration
- **Mode**: Production (Live)

## Configuration & Authentication

### Required Environment Variables (.env)
```env
# SSLCommerz Configuration
SSLC_SANDBOX=false                    # false for production, true for sandbox/testing
SSLC_STORE_ID=errumbd1live           # Your SSLCommerz store ID
SSLC_STORE_PASSWORD=your_password    # Your SSLCommerz store password
SSLC_STORE_CURRENCY=BDT              # Currency (BDT for Bangladesh Taka)
SSLC_STORE_NAME=errumbd              # Your store name
SSLC_STORE_URL=https://www.errumbd.net  # Your store URL
```

### Configuration Files
- `config/sslcommerz.php` - Main SSLCommerz configuration
- Routes defined in `routes/api.php` under `/api/sslcommerz` prefix

### Authentication Method
SSLCommerz uses **Store ID + Store Password** authentication:
- No OAuth tokens required
- Credentials validated on each payment initiation
- Store must be active and approved by SSLCommerz

### Credential Sources
1. **SSLCommerz Merchant Panel**: https://merchant.sslcommerz.com/
2. **Login** → Navigate to **Settings** → **API Integration**
3. Copy **Store ID** and **Store Password**
4. Note whether credentials are for Sandbox or Live mode

## Diagnostic Command

### Test Connection
```bash
php artisan sslcommerz:test
```

This command will:
1. ✓ Check configuration (Store ID, Sandbox mode, Currency)
2. ✓ Test payment session creation with SSLCommerz API
3. ✓ Verify store is active and credentials are valid
4. ✓ Display gateway URL and transaction details

**Successful Output:**
```
Testing SSLCommerz Payment Gateway Connection...

Step 1: Checking Configuration
✓ Store ID: errumbd1live
✓ Mode: Production
✓ Currency: BDT

Step 2: Testing Payment Session Creation (Validation)
✓ Payment session created successfully!
  Gateway URL: https://pay.sslcommerz.com/ddc256474a8bbfaf...
  Transaction ID: TEST_69455686d199c

Store Information:
  Store ID: errumbd1live
  Mode: Production (Live)

✓ SSLCommerz connection test completed successfully!
✓ Payment gateway is ready to use.

Note: A test payment session was created but not completed.
No actual transaction was processed or charged.
```

## API Implementation

### Payment Flow Methods

#### 1. Initiate Payment
```php
use Raziul\Sslcommerz\Facades\Sslcommerz;

$transactionId = 'TXN-' . $order->id . '-' . time();

$response = Sslcommerz::setOrder($totalAmount, $transactionId, 'Order #' . $order->order_number)
    ->setCustomer($customer->name, $customer->email, $customer->phone ?? '01700000000')
    ->setShippingInfo($itemCount, $shippingAddress)
    ->makePayment(['value_a' => $order->id]); // Pass order ID as metadata

if ($response->success()) {
    // Redirect to payment gateway
    $gatewayUrl = $response->gatewayPageURL();
    return response()->json([
        'success' => true,
        'payment_url' => $gatewayUrl,
        'transaction_id' => $transactionId,
    ]);
} else {
    // Handle failure
    $error = $response->failedReason();
    return response()->json(['error' => $error], 500);
}
```

#### 2. Payment Callback Handlers

**Success Callback** (`SslcommerzController@success`):
```php
public function success(Request $request)
{
    // Step 1: Verify hash to ensure request is from SSLCommerz
    if (!Sslcommerz::verifyHash($request->all())) {
        return response()->json(['message' => 'Invalid hash'], 400);
    }

    // Step 2: Extract transaction details
    $transactionId = $request->input('tran_id');
    $amount = $request->input('amount');
    $valId = $request->input('val_id');

    // Step 3: Validate payment with SSLCommerz
    $isValid = Sslcommerz::validatePayment($request->all(), $valId, $amount);

    if (!$isValid) {
        return response()->json(['message' => 'Payment validation failed'], 400);
    }

    // Step 4: Update order and payment status
    $order = Order::where('id', $request->input('value_a'))->firstOrFail();
    
    $payment = OrderPayment::where('order_id', $order->id)
        ->where('transaction_id', $transactionId)
        ->first();

    if ($payment) {
        $payment->update([
            'status' => 'completed',
            'payment_details' => $request->all()
        ]);
    }

    $order->update(['status' => 'pending_assignment']);

    return response()->json([
        'message' => 'Payment successful',
        'order_id' => $order->id,
        'transaction_id' => $transactionId
    ]);
}
```

**Failure Callback** (`SslcommerzController@failure`):
```php
public function failure(Request $request)
{
    $transactionId = $request->input('tran_id');
    
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

    return response()->json([
        'message' => 'Payment failed',
        'transaction_id' => $transactionId
    ], 400);
}
```

**Cancel Callback** (`SslcommerzController@cancel`):
```php
public function cancel(Request $request)
{
    $transactionId = $request->input('tran_id');
    
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

    return response()->json([
        'message' => 'Payment cancelled',
        'transaction_id' => $transactionId
    ]);
}
```

**IPN (Instant Payment Notification)** (`SslcommerzController@ipn`):
```php
public function ipn(Request $request)
{
    // Similar to success handler but specifically for server-to-server notifications
    if (!Sslcommerz::verifyHash($request->all())) {
        return response()->json(['message' => 'Invalid hash'], 400);
    }

    $transactionId = $request->input('tran_id');
    $amount = $request->input('amount');
    $valId = $request->input('val_id');

    $isValid = Sslcommerz::validatePayment($request->all(), $valId, $amount);

    if ($isValid) {
        // Update payment status
        $order = Order::where('id', $request->input('value_a'))->first();
        // ... update logic
    }

    return response()->json(['message' => 'IPN processed']);
}
```

## API Endpoints

### Callback Routes
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

**Callback URLs:**
- Success: `https://your-domain.com/api/sslcommerz/success`
- Failure: `https://your-domain.com/api/sslcommerz/failure`
- Cancel: `https://your-domain.com/api/sslcommerz/cancel`
- IPN: `https://your-domain.com/api/sslcommerz/ipn`

**Note**: These URLs must be configured in your SSLCommerz merchant panel under **Settings** → **Store Settings** → **Callback URLs**.

## Response Handling

### Success Response Structure
```php
$response->success()          // boolean: true if payment session created
$response->gatewayPageURL()   // string: URL to redirect customer to
$response->failedReason()     // string: error message if failed
```

### Payment Session Data
```php
[
    'status' => 'SUCCESS',
    'GatewayPageURL' => 'https://pay.sslcommerz.com/...',
    'sessionkey' => 'unique_session_key',
    'store_id' => 'errumbd1live',
    'verify_sign' => 'hash_signature',
    'verify_key' => 'verification_key',
]
```

### Callback Request Data (from SSLCommerz)
```php
[
    'tran_id' => 'TXN-123-1234567890',
    'val_id' => 'validation_id_from_sslcommerz',
    'amount' => '1000.00',
    'card_type' => 'VISA-Dutch Bangla',
    'store_amount' => '975.00',
    'card_no' => '471170XXXXXX7787',
    'bank_tran_id' => 'bank_transaction_id',
    'status' => 'VALID',
    'tran_date' => '2024-01-15 10:30:45',
    'currency' => 'BDT',
    'card_issuer' => 'STANDARD CHARTERED BANK',
    'card_brand' => 'VISA',
    'card_issuer_country' => 'Bangladesh',
    'currency_amount' => '1000.00',
    'verify_sign' => 'hash_for_verification',
    'verify_key' => 'verification_key',
    'risk_title' => 'Safe',
    'risk_level' => '0',
    'value_a' => '123',  // Custom data (order ID)
]
```

## Security Features

### 1. Hash Verification
```php
if (!Sslcommerz::verifyHash($request->all())) {
    return response()->json(['message' => 'Invalid hash'], 400);
}
```
Ensures the callback request is genuinely from SSLCommerz and hasn't been tampered with.

### 2. Payment Validation
```php
$isValid = Sslcommerz::validatePayment($request->all(), $valId, $amount);
```
Double-checks the payment status directly with SSLCommerz API to prevent fraud.

### 3. IP Whitelisting
Configure your server IPs in SSLCommerz merchant panel to restrict callback requests to authorized servers only.

## Troubleshooting

### Common Issues

#### 1. "Store Credential Error Or Store is De-active"
**Causes:**
- Store ID or Password incorrect
- Using sandbox credentials in production mode (or vice versa)
- Store not approved/activated by SSLCommerz

**Solutions:**
- Verify `SSLC_STORE_ID` and `SSLC_STORE_PASSWORD` in `.env`
- Check `SSLC_SANDBOX` setting matches your credential type
- Contact SSLCommerz support to verify store status
- Run: `php artisan config:clear` after changes

#### 2. Payment Session Creation Fails
**Causes:**
- Invalid customer email format
- Missing required fields
- Network connectivity issues

**Solutions:**
- Ensure customer email is valid format
- Check all required fields in `setOrder()`, `setCustomer()`, `setShippingInfo()`
- Verify network access to SSLCommerz servers
- Check firewall rules

#### 3. Callback URLs Not Working
**Causes:**
- URLs not registered in SSLCommerz merchant panel
- Route names don't match configuration
- CSRF protection blocking callbacks

**Solutions:**
- Register all callback URLs in merchant panel
- Verify routes in `routes/api.php`
- Ensure callback routes are excluded from CSRF protection

#### 4. Hash Verification Fails
**Causes:**
- Store password mismatch
- Request data modified/corrupted

**Solutions:**
- Verify `SSLC_STORE_PASSWORD` is correct
- Check for any middleware modifying request data
- Enable detailed logging to inspect request

### Debugging Steps

1. **Test Connection First:**
   ```bash
   php artisan sslcommerz:test
   ```

2. **Enable Detailed Logging:**
   ```php
   Log::info('SSLCommerz Request', $request->all());
   ```

3. **Check Configuration:**
   ```bash
   php artisan config:show sslcommerz
   ```

4. **Verify Callback URLs:**
   - Login to SSLCommerz merchant panel
   - Check registered callback URLs
   - Ensure they match your application routes

5. **Test with Small Amount:**
   - Use minimum transaction amount for testing
   - Complete full payment flow in sandbox/test mode first

## Production Checklist

### Before Going Live

- [ ] Verify store credentials are for **production** (not sandbox)
- [ ] Set `SSLC_SANDBOX=false` in `.env`
- [ ] Run `php artisan sslcommerz:test` successfully
- [ ] Register all callback URLs in SSLCommerz merchant panel
- [ ] Whitelist production server IP in SSLCommerz settings
- [ ] Test complete payment flow end-to-end
- [ ] Implement proper error handling and logging
- [ ] Set up payment monitoring/alerts
- [ ] Document refund procedures
- [ ] Train support staff on payment issue handling

### Security Best Practices

- [ ] Always verify hash on callbacks
- [ ] Always validate payment with SSLCommerz API
- [ ] Never trust amount from callback alone
- [ ] Log all payment transactions
- [ ] Monitor for suspicious activities
- [ ] Keep store password secure (never commit to Git)
- [ ] Use environment variables for credentials
- [ ] Implement rate limiting on payment endpoints
- [ ] Set up alerts for failed transactions

## Integration Points

### Controllers Using SSLCommerz
1. **EcommerceOrderController** - Initiates payment for e-commerce orders
2. **GuestCheckoutController** - Handles payment for guest orders
3. **SslcommerzController** - Processes all SSLCommerz callbacks

### Database Tables
- **orders** - Stores order information with payment status
- **order_payments** - Tracks payment transactions and details

### Payment Status Flow
```
Initiated → Pending → (Success/Failed/Cancelled) → Completed/Failed/Cancelled
```

## Related Documentation
- [Payment System Documentation](PAYMENT_SYSTEM_DOCUMENTATION.md)
- [E-commerce Implementation](ECOMMERCE_IMPLEMENTATION_SUMMARY.md)
- [Order Workflow](ECOMMERCE_ORDER_WORKFLOW.md)
- SSLCommerz Official Documentation: https://developer.sslcommerz.com/

## Support
- **SSLCommerz Support**: support@sslcommerz.com
- **Merchant Panel**: https://merchant.sslcommerz.com/
- **Developer Portal**: https://developer.sslcommerz.com/
