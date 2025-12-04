# E-Commerce Checkout Flow - Frontend Integration Guide

**Last Updated:** December 4, 2025  
**API Version:** v1  
**Base URL:** `https://your-domain.com/api`

---

## Table of Contents
1. [Overview](#overview)
2. [Complete Checkout Flow](#complete-checkout-flow)
3. [Cart Management](#cart-management)
4. [Address Management](#address-management)
5. [Checkout & Payment](#checkout--payment)
6. [Payment Methods](#payment-methods)
7. [Order Management](#order-management)
8. [Error Handling](#error-handling)
9. [Example Implementations](#example-implementations)

---

## Overview

### Checkout Process Summary
```
Login → Browse Products → Add to Cart → View Cart → 
Manage Addresses → Select Address → Choose Payment → 
Complete Checkout → Track Order
```

### Authentication
All e-commerce endpoints require customer authentication using JWT tokens.

**Header Required:**
```http
Authorization: Bearer {customer_jwt_token}
```

**Getting Token:**
```http
POST /api/customer-auth/login
Content-Type: application/json

{
  "email": "customer@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "customer": {
    "id": 1,
    "name": "John Doe",
    "email": "customer@example.com"
  }
}
```

---

## Complete Checkout Flow

### Step-by-Step Implementation

#### 1. **Browse & Add Products to Cart**

**Add Product to Cart:**
```http
POST /api/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 123,
  "quantity": 2,
  "variant_options": {
    "color": "Blue",
    "size": "L"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product added to cart",
  "data": {
    "cart_item": {
      "id": 45,
      "customer_id": 1,
      "product_id": 123,
      "quantity": 2,
      "unit_price": 1500.00,
      "variant_options": {
        "color": "Blue",
        "size": "L"
      },
      "product": {
        "id": 123,
        "name": "Premium T-Shirt",
        "sku": "TSH-001",
        "images": [
          {
            "url": "https://domain.com/storage/products/tshirt.jpg",
            "is_primary": true
          }
        ]
      }
    },
    "cart_summary": {
      "total_items": 2,
      "total_quantity": 3,
      "subtotal": 3000.00
    }
  }
}
```

#### 2. **View Cart**

**Get Cart Contents:**
```http
GET /api/cart
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cart_items": [
      {
        "id": 45,
        "product_id": 123,
        "product_name": "Premium T-Shirt",
        "quantity": 2,
        "unit_price": 1500.00,
        "total_price": 3000.00,
        "variant_options": {
          "color": "Blue",
          "size": "L"
        },
        "product": {
          "id": 123,
          "name": "Premium T-Shirt",
          "sku": "TSH-001",
          "stock_quantity": 50,
          "images": [...]
        }
      }
    ],
    "summary": {
      "total_items": 1,
      "total_quantity": 2,
      "subtotal": 3000.00,
      "estimated_tax": 150.00,
      "estimated_total": 3150.00
    }
  }
}
```

#### 3. **Update Cart Quantity**

```http
PUT /api/cart/{cart_item_id}/quantity
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 3
}
```

#### 4. **Remove from Cart**

```http
DELETE /api/cart/{cart_item_id}
Authorization: Bearer {token}
```

---

## Address Management

### Get All Customer Addresses

```http
GET /api/customer/addresses
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "addresses": [
      {
        "id": 1,
        "label": "Home",
        "type": "both",
        "full_name": "John Doe",
        "phone": "01712345678",
        "address_line_1": "House 123, Road 45",
        "address_line_2": "Gulshan 2",
        "city": "Dhaka",
        "state": "Dhaka",
        "postal_code": "1212",
        "country": "Bangladesh",
        "is_default_shipping": true,
        "is_default_billing": true,
        "full_address": "House 123, Road 45, Gulshan 2, Dhaka, Dhaka - 1212, Bangladesh"
      }
    ]
  }
}
```

### Create New Address

```http
POST /api/customer/addresses
Authorization: Bearer {token}
Content-Type: application/json

{
  "label": "Office",
  "type": "shipping",
  "full_name": "John Doe",
  "phone": "01712345678",
  "address_line_1": "Building 5, Floor 3",
  "address_line_2": "Banani",
  "city": "Dhaka",
  "state": "Dhaka",
  "postal_code": "1213",
  "country": "Bangladesh",
  "is_default_shipping": false,
  "is_default_billing": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Address created successfully",
  "data": {
    "address": {
      "id": 2,
      "label": "Office",
      "type": "shipping",
      "full_name": "John Doe",
      "phone": "01712345678",
      "address_line_1": "Building 5, Floor 3",
      "address_line_2": "Banani",
      "city": "Dhaka",
      "state": "Dhaka",
      "postal_code": "1213",
      "country": "Bangladesh",
      "is_default_shipping": false,
      "is_default_billing": false,
      "full_address": "Building 5, Floor 3, Banani, Dhaka, Dhaka - 1213, Bangladesh"
    }
  }
}
```

### Update Address

```http
PUT /api/customer/addresses/{address_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "label": "Office Updated",
  "phone": "01798765432",
  "address_line_1": "Building 6, Floor 2"
}
```

### Set Default Shipping Address

```http
PATCH /api/customer/addresses/{address_id}/set-default-shipping
Authorization: Bearer {token}
```

### Set Default Billing Address

```http
PATCH /api/customer/addresses/{address_id}/set-default-billing
Authorization: Bearer {token}
```

### Delete Address

```http
DELETE /api/customer/addresses/{address_id}
Authorization: Bearer {token}
```

---

## Checkout & Payment

### Complete Checkout from Cart

This is the main checkout endpoint that creates an order from the customer's cart.

```http
POST /api/customer/orders/create-from-cart
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_method": "sslcommerz",
  "shipping_address_id": 1,
  "billing_address_id": 1,
  "notes": "Please deliver after 5 PM",
  "delivery_preference": "standard",
  "coupon_code": "SAVE10"
}
```

**Request Fields:**

| Field | Type | Required | Options | Description |
|-------|------|----------|---------|-------------|
| `payment_method` | string | ✅ Yes | `cod`, `sslcommerz`, `cash`, `card`, `bank_transfer`, `digital_wallet` | Payment method selection |
| `shipping_address_id` | integer | ✅ Yes | - | ID of customer address for delivery |
| `billing_address_id` | integer | ❌ No | - | ID for billing address (defaults to shipping) |
| `notes` | string | ❌ No | Max 500 chars | Special delivery instructions |
| `delivery_preference` | string | ❌ No | `standard`, `express`, `scheduled` | Delivery speed preference |
| `scheduled_delivery_date` | date | ❌ No | Format: YYYY-MM-DD | For scheduled deliveries |
| `coupon_code` | string | ❌ No | - | Discount coupon code |

---

## Payment Methods

### 1. Cash on Delivery (COD)

**Request:**
```json
{
  "payment_method": "cod",
  "shipping_address_id": 1,
  "billing_address_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order placed successfully. An employee will assign it to a store shortly.",
  "data": {
    "order": {
      "id": 789,
      "order_number": "ORD-2025-001234",
      "customer_id": 1,
      "store_id": null,
      "order_type": "ecommerce",
      "status": "pending_assignment",
      "payment_status": "pending",
      "payment_method": "cod",
      "subtotal": 3000.00,
      "tax_amount": 150.00,
      "discount_amount": 0.00,
      "shipping_amount": 60.00,
      "total_amount": 3210.00,
      "shipping_address": {
        "full_name": "John Doe",
        "phone": "01712345678",
        "address_line_1": "House 123, Road 45",
        "city": "Dhaka",
        "postal_code": "1212"
      },
      "items": [
        {
          "id": 1,
          "product_id": 123,
          "product_name": "Premium T-Shirt",
          "product_sku": "TSH-001",
          "quantity": 2,
          "unit_price": 1500.00,
          "total_amount": 3000.00
        }
      ]
    },
    "order_summary": {
      "order_number": "ORD-2025-001234",
      "total_items": 1,
      "subtotal": 3000.00,
      "tax": 150.00,
      "shipping": 60.00,
      "discount": 0.00,
      "total_amount": 3210.00,
      "payment_method": "cod",
      "status": "pending_assignment",
      "status_description": "Your order is being processed and will be assigned to a store based on inventory availability."
    }
  }
}
```

### 2. SSLCommerz (Online Payment Gateway)

**Request:**
```json
{
  "payment_method": "sslcommerz",
  "shipping_address_id": 1,
  "billing_address_id": 1
}
```

**Response (Redirect to Payment Gateway):**
```json
{
  "success": true,
  "message": "Order created. Redirecting to payment gateway.",
  "data": {
    "order": {
      "id": 789,
      "order_number": "ORD-2025-001234",
      "total_amount": 3210.00,
      "status": "pending_assignment",
      "payment_status": "unpaid"
    },
    "payment_url": "https://sandbox.sslcommerz.com/gwprocess/v4/gateway.php?session=abc123xyz",
    "transaction_id": "TXN-789-1733308800"
  }
}
```

**Frontend Implementation:**
```javascript
// After receiving response with payment_url
if (response.data.payment_url) {
  // Redirect customer to SSLCommerz payment page
  window.location.href = response.data.payment_url;
}
```

**SSLCommerz Callback Flow:**

After payment, SSLCommerz redirects to these endpoints:

- **Success:** `POST /api/sslcommerz/success` → Order status: `pending_assignment`
- **Failure:** `POST /api/sslcommerz/failure` → Order status: `payment_failed`
- **Cancel:** `POST /api/sslcommerz/cancel` → Order status: `cancelled`
- **IPN:** `POST /api/sslcommerz/ipn` → Instant Payment Notification (webhook)

**SSLCommerz Success Response:**
```json
{
  "message": "Payment successful",
  "order_id": 789,
  "transaction_id": "TXN-789-1733308800"
}
```

---

## Order Management

### Get Customer Orders

**List All Orders:**
```http
GET /api/customer/orders?status=pending_assignment&per_page=10
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | Filter by order status | `pending_assignment`, `processing`, `shipped`, `delivered`, `cancelled` |
| `search` | string | Search by order number or product name | `ORD-2025-001234` |
| `date_from` | date | Start date filter | `2025-12-01` |
| `date_to` | date | End date filter | `2025-12-31` |
| `per_page` | integer | Items per page | `15` (default) |

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 789,
        "order_number": "ORD-2025-001234",
        "order_type": "ecommerce",
        "status": "pending_assignment",
        "payment_status": "completed",
        "payment_method": "sslcommerz",
        "total_amount": 3210.00,
        "created_at": "2025-12-04T10:30:00Z",
        "summary": {
          "total_items": 1,
          "total_amount": 3210.00,
          "status_label": "Pending Assignment",
          "days_since_order": 0,
          "can_cancel": true,
          "can_return": false
        },
        "items": [
          {
            "product_name": "Premium T-Shirt",
            "quantity": 2,
            "unit_price": 1500.00
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "per_page": 15,
      "total": 72,
      "from": 1,
      "to": 15
    }
  }
}
```

### Get Order Details

```http
GET /api/customer/orders/{order_number}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 789,
      "order_number": "ORD-2025-001234",
      "order_type": "ecommerce",
      "status": "pending_assignment",
      "payment_status": "completed",
      "payment_method": "sslcommerz",
      "subtotal": 3000.00,
      "tax_amount": 150.00,
      "discount_amount": 0.00,
      "shipping_amount": 60.00,
      "total_amount": 3210.00,
      "shipping_address": {
        "full_name": "John Doe",
        "phone": "01712345678",
        "address_line_1": "House 123, Road 45",
        "city": "Dhaka"
      },
      "items": [
        {
          "id": 1,
          "product_id": 123,
          "product_name": "Premium T-Shirt",
          "product_sku": "TSH-001",
          "quantity": 2,
          "unit_price": 1500.00,
          "total_amount": 3000.00,
          "product": {
            "id": 123,
            "name": "Premium T-Shirt",
            "images": [...]
          }
        }
      ],
      "payments": [
        {
          "id": 1,
          "amount": 3210.00,
          "transaction_id": "TXN-789-1733308800",
          "status": "completed",
          "payment_date": "2025-12-04T10:35:00Z"
        }
      ],
      "customer": {
        "id": 1,
        "name": "John Doe",
        "email": "customer@example.com"
      }
    }
  }
}
```

### Track Order

```http
GET /api/customer/orders/{order_number}/track
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-2025-001234",
    "status": "pending_assignment",
    "timeline": [
      {
        "status": "pending_assignment",
        "description": "Order received and pending store assignment",
        "timestamp": "2025-12-04T10:30:00Z"
      }
    ],
    "estimated_delivery": null,
    "tracking_number": null
  }
}
```

### Cancel Order

```http
POST /api/customer/orders/{order_number}/cancel
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Order cancelled successfully",
  "data": {
    "order_number": "ORD-2025-001234",
    "status": "cancelled",
    "refund_amount": 3210.00,
    "refund_status": "pending"
  }
}
```

---

## Error Handling

### Common HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing or invalid JWT token |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server error |

### Error Response Format

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "payment_method": [
      "The payment method field is required."
    ],
    "shipping_address_id": [
      "The selected shipping address is invalid."
    ]
  }
}
```

### Common Errors & Solutions

#### 1. Cart is Empty
```json
{
  "success": false,
  "message": "Cart is empty"
}
```
**Solution:** Add products to cart before checkout.

#### 2. Invalid Address
```json
{
  "success": false,
  "message": "The selected shipping address id is invalid."
}
```
**Solution:** Use valid address ID from customer's saved addresses.

#### 3. Product Out of Stock
```json
{
  "success": false,
  "message": "Some products in your cart are no longer available"
}
```
**Solution:** Remove unavailable products from cart.

#### 4. Authentication Failed
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```
**Solution:** Login again and use valid JWT token.

---

## Example Implementations

### React/Next.js Example

```javascript
// Complete Checkout Flow
import axios from 'axios';

const API_BASE_URL = 'https://your-domain.com/api';
const authToken = localStorage.getItem('customer_token');

// Set default headers
axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`;
axios.defaults.headers.common['Content-Type'] = 'application/json';

// 1. Add to Cart
async function addToCart(productId, quantity, variantOptions = null) {
  try {
    const response = await axios.post(`${API_BASE_URL}/cart/add`, {
      product_id: productId,
      quantity: quantity,
      variant_options: variantOptions
    });
    
    console.log('Added to cart:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error adding to cart:', error.response.data);
    throw error;
  }
}

// 2. Get Cart
async function getCart() {
  try {
    const response = await axios.get(`${API_BASE_URL}/cart`);
    return response.data;
  } catch (error) {
    console.error('Error fetching cart:', error.response.data);
    throw error;
  }
}

// 3. Get Customer Addresses
async function getAddresses() {
  try {
    const response = await axios.get(`${API_BASE_URL}/customer/addresses`);
    return response.data;
  } catch (error) {
    console.error('Error fetching addresses:', error.response.data);
    throw error;
  }
}

// 4. Create New Address
async function createAddress(addressData) {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/customer/addresses`,
      addressData
    );
    return response.data;
  } catch (error) {
    console.error('Error creating address:', error.response.data);
    throw error;
  }
}

// 5. Checkout with COD
async function checkoutWithCOD(shippingAddressId, billingAddressId = null) {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/customer/orders/create-from-cart`,
      {
        payment_method: 'cod',
        shipping_address_id: shippingAddressId,
        billing_address_id: billingAddressId || shippingAddressId,
        notes: 'Please deliver after 5 PM'
      }
    );
    
    console.log('Order created:', response.data);
    return response.data;
  } catch (error) {
    console.error('Checkout error:', error.response.data);
    throw error;
  }
}

// 6. Checkout with SSLCommerz
async function checkoutWithSSLCommerz(shippingAddressId) {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/customer/orders/create-from-cart`,
      {
        payment_method: 'sslcommerz',
        shipping_address_id: shippingAddressId
      }
    );
    
    if (response.data.data.payment_url) {
      // Redirect to SSLCommerz payment page
      window.location.href = response.data.data.payment_url;
    }
    
    return response.data;
  } catch (error) {
    console.error('SSLCommerz checkout error:', error.response.data);
    throw error;
  }
}

// 7. Get Orders
async function getOrders(page = 1, status = null) {
  try {
    const params = { per_page: 10, page };
    if (status) params.status = status;
    
    const response = await axios.get(`${API_BASE_URL}/customer/orders`, {
      params
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching orders:', error.response.data);
    throw error;
  }
}

// Usage Example
async function completeCheckoutFlow() {
  try {
    // Step 1: Add product to cart
    await addToCart(123, 2, { color: 'Blue', size: 'L' });
    
    // Step 2: View cart
    const cart = await getCart();
    console.log('Cart items:', cart.data.cart_items);
    
    // Step 3: Get addresses
    const addresses = await getAddresses();
    const defaultAddress = addresses.data.addresses.find(
      addr => addr.is_default_shipping
    );
    
    // Step 4: Checkout with COD
    const order = await checkoutWithCOD(defaultAddress.id);
    console.log('Order Number:', order.data.order_summary.order_number);
    
    // Or checkout with SSLCommerz
    // await checkoutWithSSLCommerz(defaultAddress.id);
    
  } catch (error) {
    console.error('Checkout flow failed:', error);
  }
}
```

### Vue.js Example

```javascript
// store/checkout.js (Vuex)
import axios from 'axios';

const API_BASE = 'https://your-domain.com/api';

export default {
  state: {
    cart: [],
    addresses: [],
    currentOrder: null
  },
  
  mutations: {
    SET_CART(state, cart) {
      state.cart = cart;
    },
    SET_ADDRESSES(state, addresses) {
      state.addresses = addresses;
    },
    SET_ORDER(state, order) {
      state.currentOrder = order;
    }
  },
  
  actions: {
    async addToCart({ commit }, { productId, quantity, variantOptions }) {
      const response = await axios.post(`${API_BASE}/cart/add`, {
        product_id: productId,
        quantity,
        variant_options: variantOptions
      });
      return response.data;
    },
    
    async fetchCart({ commit }) {
      const response = await axios.get(`${API_BASE}/cart`);
      commit('SET_CART', response.data.data.cart_items);
      return response.data;
    },
    
    async fetchAddresses({ commit }) {
      const response = await axios.get(`${API_BASE}/customer/addresses`);
      commit('SET_ADDRESSES', response.data.data.addresses);
      return response.data;
    },
    
    async checkout({ commit }, checkoutData) {
      const response = await axios.post(
        `${API_BASE}/customer/orders/create-from-cart`,
        checkoutData
      );
      
      if (response.data.data.payment_url) {
        window.location.href = response.data.data.payment_url;
      } else {
        commit('SET_ORDER', response.data.data.order);
      }
      
      return response.data;
    }
  }
};
```

---

## Order Status Flow

```
1. pending_assignment    → Order created, waiting for store assignment
2. processing           → Store assigned, preparing order
3. ready_for_pickup     → Order ready for courier pickup
4. shipped              → Order shipped to customer
5. delivered            → Order successfully delivered
6. cancelled            → Order cancelled by customer/system
7. payment_failed       → Payment failed (SSLCommerz)
```

---

## Payment Status Flow

```
1. pending    → COD orders waiting for delivery
2. unpaid     → Online payment not yet completed
3. completed  → Payment successful
4. failed     → Payment failed
5. refunded   → Payment refunded
```

---

## Important Notes

### 🔐 Security
- Always use HTTPS in production
- Never expose JWT tokens in URL parameters
- Tokens expire after 60 minutes - handle refresh

### 💡 Best Practices
- Validate cart before checkout
- Handle address creation errors gracefully
- Show loading states during checkout
- Implement retry logic for failed payments
- Clear cart only after successful order creation

### 📱 Mobile Considerations
- SSLCommerz redirects work in webview
- Handle deep links for payment returns
- Cache cart data for offline browsing

### 🚀 Performance
- Paginate order lists
- Cache product images
- Debounce search queries
- Lazy load order history

---

## Support & Troubleshooting

### Common Issues

**Issue:** "Cart is empty" error during checkout  
**Fix:** Ensure cart items exist before calling checkout endpoint

**Issue:** SSLCommerz redirect not working  
**Fix:** Check that `SSLC_STORE_ID` and `SSLC_STORE_PASSWORD` are set in `.env`

**Issue:** Address validation fails  
**Fix:** Ensure all required address fields are provided

**Issue:** Orders not showing  
**Fix:** Check JWT token validity and customer_id association

---

## Changelog

### December 4, 2025
- ✅ Added SSLCommerz payment gateway integration
- ✅ Updated checkout flow with payment method selection
- ✅ Added payment callbacks documentation
- ✅ Enhanced error handling examples

### December 2, 2025
- ✅ Initial documentation created
- ✅ Fixed missing CustomerAddress routes
- ✅ Added complete checkout flow examples

---

## Contact

For API issues or questions:
- **Backend Team:** backend@deshio.com
- **Documentation:** https://docs.deshio.com/api
- **Support Portal:** https://support.deshio.com
