# SSLCommerz Payment Gateway - API Documentation

## Overview
This document describes the API endpoints for integrating SSLCommerz payment gateway into your application. The payment flow involves initiating a payment session, redirecting the customer to SSLCommerz gateway, and handling callback responses.

---

## Base URL
```
Production: https://your-domain.com/api
Development: http://localhost:8000/api
```

---

## Authentication
All order-related endpoints require JWT authentication. Include the bearer token in the Authorization header:

```http
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## Payment Flow

### Step-by-Step Integration

```
1. Customer adds items to cart
2. Customer proceeds to checkout
3. Customer selects "SSLCommerz" as payment method
4. Frontend calls API to create order with payment_method: 'sslcommerz'
5. Backend creates order and initiates SSLCommerz payment session
6. Backend returns payment_url in response
7. Frontend redirects customer to payment_url
8. Customer completes payment on SSLCommerz gateway
9. SSLCommerz redirects customer back to your site (success/failure/cancel)
10. SSLCommerz sends server-to-server callback (IPN) to backend
11. Backend updates order and payment status
12. Frontend displays payment result to customer
```

---

## API Endpoints

### 1. Create E-commerce Order with SSLCommerz Payment

**Endpoint:** `POST /api/customer/orders/create-from-cart`

**Authentication:** Required (Customer JWT)

**Description:** Creates an order from customer's cart and initiates SSLCommerz payment session. Returns payment gateway URL for customer redirect.

**Note:** This endpoint creates order from items already in the customer's cart. Add items to cart first using cart endpoints.

#### Request Body
```json
{
  "payment_method": "sslcommerz",
  "shipping_address_id": 5,
  "billing_address_id": 5,
  "notes": "Please deliver before 5 PM",
  "delivery_preference": "express",
  "scheduled_delivery_date": "2024-12-25",
  "coupon_code": "SAVE10"
}
```

#### Request Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `payment_method` | string | Yes | Must be `"sslcommerz"` for SSLCommerz payments. Options: `cash`, `card`, `bank_transfer`, `digital_wallet`, `cod`, `sslcommerz` |
| `shipping_address_id` | integer | Yes | ID of shipping address from customer_addresses table |
| `billing_address_id` | integer | No | ID of billing address. If not provided, uses shipping address |
| `notes` | string | No | Special delivery instructions (max 500 characters) |
| `delivery_preference` | string | No | `"standard"`, `"express"`, or `"scheduled"` |
| `scheduled_delivery_date` | date | No | Format: `YYYY-MM-DD` (must be after today) |
| `coupon_code` | string | No | Discount coupon code |

#### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Order created. Redirecting to payment gateway.",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-2024-001234",
      "customer_id": 1,
      "store_id": null,
      "order_type": "ecommerce",
      "is_preorder": false,
      "preorder_notes": null,
      "status": "pending_assignment",
      "payment_status": "unpaid",
      "payment_method": "sslcommerz",
      "subtotal": "2500.00",
      "tax_amount": "250.00",
      "discount_amount": "100.00",
      "shipping_amount": "120.00",
      "total_amount": "2770.00",
      "shipping_address": {
        "full_name": "John Doe",
        "phone": "01712345678",
        "address_line_1": "123 Main Street",
        "address_line_2": "Apartment 4B",
        "city": "Dhaka",
        "state": "Dhaka",
        "postal_code": "1212",
        "country": "Bangladesh",
        "full_address": "123 Main Street, Apartment 4B, Dhaka, Dhaka - 1212, Bangladesh"
      },
      "billing_address": {
        "full_name": "John Doe",
        "phone": "01712345678",
        "address_line_1": "123 Main Street",
        "city": "Dhaka",
        "postal_code": "1212",
        "country": "Bangladesh"
      },
      "notes": "Please deliver before 5 PM",
      "metadata": {
        "delivery_preference": "express",
        "scheduled_delivery_date": "2024-12-25",
        "coupon_code": "SAVE10"
      },
      "created_at": "2024-12-19T10:30:00.000000Z",
      "updated_at": "2024-12-19T10:30:00.000000Z"
    },
    "payment_url": "https://pay.sslcommerz.com/ddc256474a8bbfaf063f17ad6870765caba8f9f2",
    "transaction_id": "TXN-123-1734605400"
  }
}
```

#### Frontend Implementation
```javascript
// Create order with SSLCommerz payment
async function createOrderWithSSLCommerz(orderData) {
  try {
    const response = await fetch('/api/customer/orders/create-from-cart', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${jwtToken}`
      },
      body: JSON.stringify({
        ...orderData,
        payment_method: 'sslcommerz'
      })
    });

    const result = await response.json();

    if (result.success && result.data.payment_url) {
      // Redirect customer to SSLCommerz payment gateway
      window.location.href = result.data.payment_url;
    } else {
      // Handle error
      console.error('Payment initiation failed:', result.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

#### Error Response (400/422/500)
```json
{
  "success": false,
  "message": "Failed to initiate payment gateway",
  "error": "Store Credential Error Or Store is De-active"
}
```

---

### 2. Create Guest Order with SSLCommerz Payment

**Endpoint:** `POST /api/guest-checkout`

**Authentication:** Not Required

**Description:** Creates an order for guest customers (without account) and initiates SSLCommerz payment session. Customer is identified by phone number.

#### Request Body
```json
{
  "phone": "01712345678",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "payment_method": "sslcommerz",
  "delivery_address": {
    "full_name": "John Doe",
    "phone": "01712345678",
    "address_line_1": "123 Main Street",
    "address_line_2": "Apartment 4B",
    "city": "Dhaka",
    "state": "Dhaka",
    "postal_code": "1212",
    "country": "Bangladesh"
  },
  "items": [
    {
      "product_id": 45,
      "quantity": 2,
      "variant_options": null
    }
  ],
  "notes": "Leave package at door"
}
```

#### Request Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `phone` | string | Yes | Customer phone number (10-20 characters, numbers and +/-/space/() allowed) |
| `customer_name` | string | No | Guest customer name (max 255 characters) |
| `customer_email` | string | No | Valid email address (max 255 characters) |
| `payment_method` | string | Yes | `"cod"`, `"sslcommerz"`, or `"cash"` |
| `delivery_address` | object | Yes | Delivery address details |
| `delivery_address.full_name` | string | Yes | Recipient full name (max 255 characters) |
| `delivery_address.phone` | string | No | Recipient phone number (max 20 characters) |
| `delivery_address.address_line_1` | string | Yes | Primary address line (max 255 characters) |
| `delivery_address.address_line_2` | string | No | Secondary address line (max 255 characters) |
| `delivery_address.city` | string | Yes | City name (max 100 characters) |
| `delivery_address.state` | string | No | State/Division name (max 100 characters) |
| `delivery_address.postal_code` | string | Yes | Postal/ZIP code (max 20 characters) |
| `delivery_address.country` | string | No | Country name (max 100 characters, defaults to Bangladesh) |
| `items` | array | Yes | Array of order items (minimum 1 item) |
| `items[].product_id` | integer | Yes | Product ID (must exist in products table) |
| `items[].quantity` | integer | Yes | Quantity (minimum 1) |
| `items[].variant_options` | object/null | No | Product variant options if applicable |
| `notes` | string | No | Special delivery instructions (max 500 characters) |

#### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Order created. Redirecting to payment gateway.",
  "data": {
    "order_number": "ORD-2024-001235",
    "order_id": 124,
    "customer_id": 56,
    "customer_phone": "01712345678",
    "payment_url": "https://pay.sslcommerz.com/abc123def456...",
    "transaction_id": "TXN-124-1734605500",
    "total_amount": "2770.00"
  }
}
```

**Note:** The system automatically creates or finds a customer record based on the phone number. The `customer_id` in the response is the ID of the found/created customer record.

---

## SSLCommerz Callback Endpoints

These endpoints are called by SSLCommerz after payment completion. **Do not call these endpoints directly from frontend.**

### 3. Payment Success Callback

**Endpoint:** `POST /api/sslcommerz/success`

**Called By:** Customer redirect/POST-back from SSLCommerz

**Content-Type:** `application/x-www-form-urlencoded` (form data, not JSON)

**Description:** SSLCommerz redirects the customer to this endpoint when payment is successful. **Note:** This endpoint currently returns JSON. For better UX, configure SSLCommerz return URLs to point to your frontend pages (e.g., `/payment/success`) or modify this endpoint to redirect to frontend.

#### Request (from SSLCommerz)
```json
{
  "tran_id": "TXN-123-1734605400",
  "val_id": "240619145903yuI4h6VUdBiONGP",
  "amount": "2770.00",
  "card_type": "VISA-Dutch Bangla",
  "store_amount": "2697.50",
  "card_no": "471170XXXXXX7787",
  "bank_tran_id": "240619145903HfG4pjMPPdN67Rf",
  "status": "VALID",
  "tran_date": "2024-12-19 14:59:03",
  "currency": "BDT",
  "card_issuer": "STANDARD CHARTERED BANK",
  "card_brand": "VISA",
  "card_issuer_country": "Bangladesh",
  "verify_sign": "6c4a55b17df6397e4f92a7d878e47c4a",
  "verify_key": "amount,bank_tran_id,base_fair,card_brand...",
  "risk_level": "0",
  "risk_title": "Safe",
  "value_a": "123"
}
```

#### Response
```json
{
  "message": "Payment successful",
  "order_id": 123,
  "transaction_id": "TXN-123-1734605400"
}
```

---

### 4. Payment Failure Callback

**Endpoint:** `POST /api/sslcommerz/failure`

**Called By:** Customer redirect/POST-back from SSLCommerz

**Content-Type:** `application/x-www-form-urlencoded` (form data, not JSON)

**Description:** SSLCommerz redirects the customer to this endpoint when payment fails. **Note:** This endpoint currently returns JSON. For better UX, configure SSLCommerz return URLs to point to your frontend pages (e.g., `/payment/failure`) or modify this endpoint to redirect to frontend. **Security Note:** This endpoint does NOT verify hash - hash verification should be added before updating order status.

#### Request (from SSLCommerz)
```json
{
  "tran_id": "TXN-123-1734605400",
  "error": "Invalid card number",
  "status": "FAILED",
  "value_a": "123"
}
```

#### Response
```json
{
  "message": "Payment failed",
  "transaction_id": "TXN-123-1734605400"
}
```

---

### 5. Payment Cancellation Callback

**Endpoint:** `POST /api/sslcommerz/cancel`

**Called By:** Customer redirect/POST-back from SSLCommerz

**Content-Type:** `application/x-www-form-urlencoded` (form data, not JSON)

**Description:** SSLCommerz redirects the customer to this endpoint when they cancel payment. **Note:** This endpoint currently returns JSON. For better UX, configure SSLCommerz return URLs to point to your frontend pages (e.g., `/payment/cancel`) or modify this endpoint to redirect to frontend. **Security Note:** This endpoint does NOT verify hash - hash verification should be added before updating order status.

#### Request (from SSLCommerz)
```json
{
  "tran_id": "TXN-123-1734605400",
  "status": "CANCELLED",
  "value_a": "123"
}
```

#### Response
```json
{
  "message": "Payment cancelled",
  "transaction_id": "TXN-123-1734605400"
}
```

---

### 6. IPN (Instant Payment Notification)

**Endpoint:** `POST /api/sslcommerz/ipn`

**Called By:** SSLCommerz (server-to-server) - **MOST RELIABLE**

**Content-Type:** `application/x-www-form-urlencoded` (form data, not JSON)

**Description:** SSLCommerz sends instant payment notification for real-time payment updates. This is the most reliable callback as it's server-to-server and not dependent on customer's browser. **Always rely on IPN for critical payment confirmation.** **Security Note:** This endpoint verifies hash but does NOT validate payment amount against order total - amount validation should be added.

#### Request (from SSLCommerz)
Same structure as success callback.

#### Response
```json
{
  "message": "IPN processed"
}
```

---

## Frontend Integration Guide

### Complete Payment Flow Example

```javascript
// 1. Add items to cart (existing cart API)
await addToCart(productId, quantity);

// 2. Get cart items
const cart = await getCart(customerId);

// 3. Select shipping address
const shippingAddress = await getCustomerAddress(addressId);

// 4. Create order with SSLCommerz
async function checkout() {
  try {
    const orderData = {
      payment_method: 'sslcommerz', // Important!
      shipping_address_id: selectedAddressId,
      billing_address_id: selectedAddressId,
      notes: deliveryInstructions
    };
    // Note: customer_id is NOT needed - it's automatically retrieved from JWT token

    const response = await fetch('/api/customer/orders/create-from-cart', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getAuthToken()}`
      },
      body: JSON.stringify(orderData)
    });

    const result = await response.json();

    if (result.success && result.data.payment_url) {
      // Save order info to localStorage for later reference
      localStorage.setItem('pending_order_id', result.data.order.id);
      localStorage.setItem('transaction_id', result.data.transaction_id);
      
      // Redirect to SSLCommerz payment gateway
      window.location.href = result.data.payment_url;
    } else {
      showError(result.message || 'Payment initiation failed');
    }
  } catch (error) {
    console.error('Checkout error:', error);
    showError('An error occurred during checkout');
  }
}

// 5. Handle return from payment gateway
// Create these pages in your frontend:

// Success page: /payment/success
// SSLCommerz will redirect customer here after successful payment
// Check order status via API

// Failure page: /payment/failure
// SSLCommerz will redirect customer here after failed payment

// Cancel page: /payment/cancel
// SSLCommerz will redirect customer here if they cancel
```

### Payment Return Pages

#### Success Page Example
```javascript
// /payment/success page
async function handlePaymentSuccess() {
  const urlParams = new URLSearchParams(window.location.search);
  const orderId = localStorage.getItem('pending_order_id');
  
  if (orderId) {
    // Fetch order details to confirm payment status
    const order = await fetchOrderDetails(orderId);
    
    if (order.payment_status === 'completed') {
      // Show success message
      displaySuccessMessage(order);
      
      // Clear localStorage
      localStorage.removeItem('pending_order_id');
      localStorage.removeItem('transaction_id');
    } else {
      // Payment still processing
      displayProcessingMessage();
    }
  }
}
```

#### Failure Page Example
```javascript
// /payment/failure page
function handlePaymentFailure() {
  const urlParams = new URLSearchParams(window.location.search);
  const error = urlParams.get('error');
  
  displayFailureMessage(error || 'Payment failed');
  
  // Offer option to retry payment
  showRetryButton();
}
```

#### Cancel Page Example
```javascript
// /payment/cancel page
function handlePaymentCancel() {
  displayCancelMessage('Payment was cancelled');
  
  // Redirect back to cart or order review
  setTimeout(() => {
    window.location.href = '/cart';
  }, 3000);
}
```

---

## Payment Status Verification

### Check Order Payment Status

**Endpoint:** `GET /api/customer/orders/{order_number}`

**Authentication:** Required (Customer JWT)

**Description:** Fetch order details to verify payment status after returning from gateway. Uses order number (e.g., "ORD-2024-001234"), not order ID.

#### Request
```http
GET /api/customer/orders/ORD-2024-001234
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Response
```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_number": "ORD-2024-001234",
    "status": "pending_assignment",
    "payment_status": "completed",
    "payment_method": "sslcommerz",
    "total_amount": "2770.00",
    "order_payments": [
      {
        "id": 456,
        "transaction_id": "TXN-123-1734605400",
        "amount": "2770.00",
        "status": "completed",
        "payment_date": "2024-12-19T14:59:03.000000Z",
        "payment_details": {
          "card_type": "VISA-Dutch Bangla",
          "card_no": "471170XXXXXX7787",
          "bank_tran_id": "240619145903HfG4pjMPPdN67Rf"
        }
      }
    ]
  }
}
```

#### Payment Status Values

| Status | Description |
|--------|-------------|
| `unpaid` | Payment not initiated or pending |
| `completed` | Payment successful |
| `failed` | Payment failed |
| `cancelled` | Payment cancelled by customer |
| `refunded` | Payment refunded |
| `partial` | Partial payment received |

---

## Testing

### Test in Development

#### 1. Use Sandbox Mode
```env
# .env file
SSLC_SANDBOX=true
SSLC_STORE_ID=your_sandbox_store_id
SSLC_STORE_PASSWORD=your_sandbox_password
```

#### 2. Test Cards (Sandbox Only)

| Card Network | Card Number | CVV | Expiry | Expected Result |
|--------------|-------------|-----|--------|-----------------|
| Visa | 4111111111111111 | 123 | 12/26 | Success |
| MasterCard | 5555555555554444 | 123 | 12/26 | Success |
| Amex | 378282246310005 | 1234 | 12/26 | Success |

#### 3. Test API Connection
```bash
php artisan sslcommerz:test
```

### Frontend Testing Checklist

- [ ] Create order with SSLCommerz payment method
- [ ] Verify payment_url is returned
- [ ] Redirect to payment gateway successfully
- [ ] Complete payment with test card
- [ ] Verify redirect to success page
- [ ] Confirm order status updated to "completed"
- [ ] Test payment failure scenario
- [ ] Test payment cancellation
- [ ] Verify callback handlers are triggered
- [ ] Check order history displays payment details

---

## Error Handling

### Common Error Codes

| HTTP Code | Error Message | Cause | Solution |
|-----------|--------------|-------|----------|
| 400 | "Invalid hash" | Callback hash verification failed | Check store credentials |
| 400 | "Payment validation failed" | Payment not verified with SSLCommerz | Contact support |
| 400 | "Amount mismatch" | Payment amount differs from order | Check for tampering |
| 422 | "Validation errors" | Invalid request data | Check required fields |
| 500 | "Failed to initiate payment gateway" | SSLCommerz API error | Check credentials and connectivity |
| 500 | "Store Credential Error" | Invalid store ID or password | Verify credentials in .env |

### Frontend Error Handling

```javascript
async function createOrder(orderData) {
  try {
    // Use the correct endpoint based on your use case:
    // - For customer with auth: '/api/customer/orders/create-from-cart'
    // - For guest checkout: '/api/guest-checkout'
    const response = await fetch('/api/customer/orders/create-from-cart', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}` // Required for customer endpoint
      },
      body: JSON.stringify(orderData)
    });

    const result = await response.json();

    if (!response.ok) {
      // Handle HTTP errors
      if (response.status === 422) {
        // Validation errors
        showValidationErrors(result.errors);
      } else if (response.status === 500) {
        // Server error
        showError('Payment gateway error. Please try again.');
      } else {
        showError(result.message || 'An error occurred');
      }
      return null;
    }

    return result;
  } catch (error) {
    // Network error
    console.error('Network error:', error);
    showError('Connection error. Please check your internet.');
    return null;
  }
}
```

---

## Security Considerations

### Frontend Security

1. **Never Store Credentials:**
   - Don't store store ID or password in frontend code
   - Don't expose SSLCommerz credentials in JavaScript

2. **Use HTTPS:**
   - Always use HTTPS for payment pages
   - Ensure SSL certificate is valid

3. **Validate Redirect:**
   - Verify payment_url domain is `pay.sslcommerz.com`
   - Don't redirect to untrusted domains

4. **Don't Trust Client Data:**
   - Never calculate payment amount in frontend
   - Backend always determines final amount

### Backend Security - Current Implementation Status

#### ✅ Implemented:

**1. Hash Verification (Partial)**
```php
Sslcommerz::verifyHash($request->all())
```
- ✅ Implemented in: `success` callback
- ✅ Implemented in: `ipn` callback
- ❌ **NOT implemented** in: `failure` and `cancel` callbacks

**2. Payment Validation (Partial)**
```php
Sslcommerz::validatePayment($data, $valId, $amount)
```
- ✅ Implemented in: `success` callback
- ❌ **NOT implemented** in: `ipn` callback (should be added)

#### ❌ NOT Implemented (Recommended):

**3. Amount Verification**
```php
// RECOMMENDED: Add this to all callbacks
if ($order->total_amount != $request->input('amount')) {
    abort(400, 'Amount mismatch');
}
```
- ❌ Currently NOT checked in any callback
- ⚠️ **Security Risk:** Order can be marked as paid with wrong amount

**⚠️ Security Recommendations Before Production:**
1. Add hash verification to `failure` and `cancel` callbacks
2. Add payment validation to `ipn` callback
3. Add amount verification to all callbacks
4. Configure IP whitelisting in SSLCommerz merchant panel

---

## Rate Limiting

Payment-related endpoints have rate limiting:

- **Order Creation:** 10 requests per minute per user
- **Order Status Check:** 60 requests per minute per user

Exceeded rate limits return:
```json
{
  "message": "Too Many Attempts.",
  "retry_after": 60
}
```

---

## Webhooks (IPN)

SSLCommerz sends IPN (Instant Payment Notification) to your backend automatically. This is the most reliable method for payment confirmation.

### IPN Configuration

Configure IPN URL in SSLCommerz merchant panel:
```
IPN URL: https://your-domain.com/api/sslcommerz/ipn
```

### Callback Types: IPN vs Customer Redirects

| Callback | Type | Reliability | Content-Type | Use Case |
|----------|------|-------------|--------------|----------|
| **IPN** | Server-to-server | ✅ **High** (most reliable) | form-urlencoded | Primary payment confirmation |
| **success** | Customer redirect | ⚠️ Medium (browser-dependent) | form-urlencoded | Customer UX |
| **failure** | Customer redirect | ⚠️ Medium (browser-dependent) | form-urlencoded | Customer notification |
| **cancel** | Customer redirect | ⚠️ Medium (browser-dependent) | form-urlencoded | Customer notification |

**Important Notes:**
- SSLCommerz posts all callbacks as `application/x-www-form-urlencoded` (form data), not JSON
- Current API callbacks return JSON responses - customers will see blank JSON page
- **Recommended:** Configure SSLCommerz return URLs to frontend pages (`/payment/success`, `/payment/failure`, `/payment/cancel`), OR modify backend callbacks to redirect to frontend
- **Best Practice:** Always rely on IPN for payment confirmation, use redirects only for customer experience

---

## API Response Examples

### Successful Order Creation
```json
{
  "success": true,
  "message": "Order created. Redirecting to payment gateway.",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-2024-001234",
      "total_amount": "2770.00",
      "payment_status": "unpaid"
    },
    "payment_url": "https://pay.sslcommerz.com/...",
    "transaction_id": "TXN-123-1734605400"
  }
}
```

### Failed Order Creation
```json
{
  "success": false,
  "message": "Failed to initiate payment gateway",
  "error": "Store Credential Error Or Store is De-active"
}
```

### Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "payment_method": ["The payment method field is required."],
    "shipping_address_id": ["The shipping address id field is required."]
  }
}
```

---

## Support & Resources

### Backend Team
- Test connection: `php artisan sslcommerz:test`
- Check logs: `storage/logs/laravel.log`
- Clear config: `php artisan config:clear`

### SSLCommerz Resources
- **Merchant Panel:** https://merchant.sslcommerz.com/
- **Support Email:** support@sslcommerz.com
- **Developer Portal:** https://developer.sslcommerz.com/

### Internal Documentation
- [SSLCommerz Implementation Summary](SSLCOMMERZ_IMPLEMENTATION_SUMMARY.md)
- [SSLCommerz Quick Reference](SSLCOMMERZ_QUICK_REFERENCE.md)
- [Payment System Documentation](PAYMENT_SYSTEM_DOCUMENTATION.md)
- [E-commerce Order Workflow](ECOMMERCE_ORDER_WORKFLOW.md)

---

## Changelog

### Version 1.0 (December 19, 2024)
- Initial API documentation
- Order creation endpoints
- Callback endpoints
- Frontend integration guide
- Testing guidelines
- Error handling
