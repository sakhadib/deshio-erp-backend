# E-commerce API Documentation

## 🛍️ Complete Customer-Facing E-commerce System

This documentation covers all API endpoints for building a complete e-commerce frontend application. The system includes authentication, shopping cart, wishlist, orders, payments, and customer support.

---

## 📋 Table of Contents

1. [Getting Started](#getting-started)
2. [Authentication System](#authentication-system)
3. [Shopping Cart Management](#shopping-cart-management)
4. [Wishlist Management](#wishlist-management)
5. [Product Catalog (Public)](#product-catalog-public)
6. [Customer Profile](#customer-profile)
7. [Address Management](#address-management)
8. [Order Management](#order-management)
9. [Payment Processing](#payment-processing)
10. [Order Tracking & Notifications](#order-tracking--notifications)
11. [Customer Support](#customer-support)
12. [Loyalty Program](#loyalty-program)
13. [Product Reviews](#product-reviews)
14. [Error Handling](#error-handling)
15. [Integration Examples](#integration-examples)

---

## 🚀 Getting Started

### Base URL
```
https://your-api-domain.com/api
```

### Authentication
Most e-commerce endpoints require customer authentication using JWT tokens. Include the token in the Authorization header:

```javascript
headers: {
  'Authorization': 'Bearer YOUR_JWT_TOKEN',
  'Content-Type': 'application/json'
}
```

### Response Format
All API responses follow this structure:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data here
  }
}
```

---

## 🔐 Authentication System

### Customer Registration
Create a new customer account.

**Endpoint:** `POST /customer-auth/register`

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+8801712345678",
  "password": "securepassword123",
  "password_confirmation": "securepassword123",
  "date_of_birth": "1990-01-15",
  "gender": "male"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful. Please check your email for verification.",
  "data": {
    "customer": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+8801712345678"
    },
    "verification_token": "abc123..."
  }
}
```

### Customer Login
Authenticate and get access token.

**Endpoint:** `POST /customer-auth/login`

**Request:**
```json
{
  "email": "john@example.com",
  "password": "securepassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "customer": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "def502004b8c...",
      "token_type": "bearer",
      "expires_in": 3600
    }
  }
}
```

### Other Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/customer-auth/forgot-password` | Request password reset |
| POST | `/customer-auth/reset-password` | Reset password with token |
| POST | `/customer-auth/verify-email` | Verify email address |
| POST | `/customer-auth/resend-verification` | Resend verification email |
| POST | `/customer-auth/logout` | Logout (requires auth) |
| POST | `/customer-auth/refresh` | Refresh access token |

---

## 🛒 Shopping Cart Management

### Get Cart Contents
**Endpoint:** `GET /cart`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "cart_items": [
      {
        "id": 1,
        "product_id": 101,
        "product_name": "Wireless Headphones",
        "price": 2500.00,
        "quantity": 2,
        "subtotal": 5000.00,
        "product_image": "https://example.com/headphones.jpg"
      }
    ],
    "summary": {
      "total_items": 2,
      "subtotal": 5000.00,
      "estimated_tax": 250.00,
      "estimated_total": 5250.00
    },
    "saved_for_later": 3
  }
}
```

### Add Item to Cart
**Endpoint:** `POST /cart/add`
**Auth Required:** Yes

**Request:**
```json
{
  "product_id": 101,
  "quantity": 2,
  "variant_options": {
    "size": "L",
    "color": "Blue"
  }
}
```

### Update Cart Item Quantity
**Endpoint:** `PUT /cart/update/{cart_item_id}`
**Auth Required:** Yes

**Request:**
```json
{
  "quantity": 3
}
```

### Remove Item from Cart
**Endpoint:** `DELETE /cart/remove/{cart_item_id}`
**Auth Required:** Yes

### Save Item for Later
**Endpoint:** `POST /cart/save-for-later/{cart_item_id}`
**Auth Required:** Yes

### Move Saved Item Back to Cart
**Endpoint:** `POST /cart/move-to-cart/{cart_item_id}`
**Auth Required:** Yes

### Clear Entire Cart
**Endpoint:** `DELETE /cart/clear`
**Auth Required:** Yes

---

## ❤️ Wishlist Management

### Get All Wishlists
**Endpoint:** `GET /wishlist`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "wishlists": [
      {
        "id": 1,
        "name": "My Favorites",
        "items_count": 5,
        "items": [
          {
            "id": 1,
            "product_id": 201,
            "product_name": "Smartphone XYZ",
            "price": 25000.00,
            "product_image": "https://example.com/phone.jpg",
            "in_stock": true
          }
        ]
      }
    ],
    "total_wishlists": 1,
    "total_items": 5
  }
}
```

### Add Item to Wishlist
**Endpoint:** `POST /wishlist/add`
**Auth Required:** Yes

**Request:**
```json
{
  "product_id": 201,
  "wishlist_name": "Electronics"
}
```

### Create New Wishlist
**Endpoint:** `POST /wishlist/create`
**Auth Required:** Yes

**Request:**
```json
{
  "name": "Winter Collection"
}
```

### Move Item from Wishlist to Cart
**Endpoint:** `POST /wishlist/move-to-cart/{wishlist_item_id}`
**Auth Required:** Yes

### Move All Items from Wishlist to Cart
**Endpoint:** `POST /wishlist/move-all-to-cart/{wishlist_id}`
**Auth Required:** Yes

---

## 📦 Product Catalog (Public)

These endpoints are public and don't require authentication.

### Get Products with Filters
**Endpoint:** `GET /catalog/products`

**Query Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page (default: 20)
- `category_id` (int): Filter by category
- `min_price` (float): Minimum price
- `max_price` (float): Maximum price
- `sort` (string): Sort by price_asc, price_desc, newest, popular
- `search` (string): Search term
- `in_stock` (boolean): Only in-stock products

**Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 101,
        "name": "Wireless Headphones",
        "slug": "wireless-headphones",
        "price": 2500.00,
        "original_price": 3000.00,
        "discount_percentage": 17,
        "rating": 4.5,
        "reviews_count": 128,
        "images": [
          {
            "url": "https://example.com/headphones-1.jpg",
            "alt": "Wireless Headphones Front View"
          }
        ],
        "variants": [
          {
            "id": 1,
            "size": "Standard",
            "color": "Black",
            "stock": 25
          }
        ],
        "in_stock": true,
        "stock_quantity": 25,
        "category": {
          "id": 5,
          "name": "Electronics"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "per_page": 20,
      "total": 200
    },
    "filters": {
      "categories": [
        {
          "id": 5,
          "name": "Electronics",
          "product_count": 45
        }
      ],
      "price_range": {
        "min": 100,
        "max": 50000
      }
    }
  }
}
```

### Get Single Product Details
**Endpoint:** `GET /catalog/products/{product_id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 101,
      "name": "Wireless Headphones",
      "description": "High-quality wireless headphones with noise cancellation.",
      "price": 2500.00,
      "rating": 4.5,
      "reviews_count": 128,
      "images": [
        {
          "url": "https://example.com/headphones-1.jpg",
          "alt": "Front view"
        }
      ],
      "specifications": {
        "Brand": "TechAudio",
        "Connectivity": "Bluetooth 5.0",
        "Battery Life": "30 hours"
      },
      "variants": [
        {
          "id": 1,
          "size": "Standard",
          "color": "Black",
          "stock": 25,
          "price": 2500.00
        }
      ],
      "related_products": [
        {
          "id": 102,
          "name": "Bluetooth Speaker",
          "price": 1500.00
        }
      ]
    }
  }
}
```

### Search Products
**Endpoint:** `GET /catalog/search`

**Query Parameters:**
- `q` (string): Search query
- `category_id` (int): Filter by category
- `min_price` (float): Minimum price
- `max_price` (float): Maximum price

### Get Categories
**Endpoint:** `GET /catalog/categories`

**Response:**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Electronics",
        "slug": "electronics",
        "image": "https://example.com/category-electronics.jpg",
        "product_count": 45,
        "subcategories": [
          {
            "id": 2,
            "name": "Smartphones",
            "product_count": 20
          }
        ]
      }
    ]
  }
}
```

### Get Featured Products
**Endpoint:** `GET /catalog/featured`

### Get Product Recommendations
**Endpoint:** `GET /catalog/recommendations`
**Auth Required:** Yes (for personalized recommendations)

---

## 👤 Customer Profile

### Get Profile Information
**Endpoint:** `GET /profile`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "profile": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+8801712345678",
      "date_of_birth": "1990-01-15",
      "gender": "male",
      "email_verified_at": "2024-01-15T10:30:00Z",
      "created_at": "2024-01-01T00:00:00Z"
    },
    "preferences": {
      "email_notifications": true,
      "sms_notifications": false,
      "promotional_emails": true,
      "language": "en",
      "currency": "BDT"
    },
    "statistics": {
      "total_orders": 15,
      "total_spent": 45000.00,
      "loyalty_points": 1250,
      "current_tier": "Silver"
    }
  }
}
```

### Update Profile
**Endpoint:** `PUT /profile`
**Auth Required:** Yes

**Request:**
```json
{
  "name": "John Doe",
  "phone": "+8801712345678",
  "date_of_birth": "1990-01-15",
  "gender": "male"
}
```

### Update Preferences
**Endpoint:** `PUT /profile/preferences`
**Auth Required:** Yes

**Request:**
```json
{
  "communication_preferences": {
    "email_notifications": true,
    "sms_notifications": false,
    "promotional_emails": true
  },
  "shopping_preferences": {
    "language": "en",
    "currency": "BDT",
    "newsletter_subscription": true
  }
}
```

### Get Order History
**Endpoint:** `GET /profile/orders`
**Auth Required:** Yes

**Query Parameters:**
- `page` (int): Page number
- `status` (string): Filter by order status
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date

---

## 📍 Address Management

### Get All Addresses
**Endpoint:** `GET /addresses`
**Auth Required:** Yes

**Query Parameters:**
- `type` (string): shipping, billing, both

**Response:**
```json
{
  "success": true,
  "data": {
    "addresses": [
      {
        "id": 1,
        "type": "both",
        "name": "John Doe",
        "phone": "+8801712345678",
        "address_line_1": "123 Main Street",
        "address_line_2": "Apt 4B",
        "city": "Dhaka",
        "state": "Dhaka Division",
        "postal_code": "1205",
        "country": "Bangladesh",
        "landmark": "Near City Bank",
        "is_default_shipping": true,
        "is_default_billing": true,
        "delivery_instructions": "Ring doorbell twice"
      }
    ],
    "default_shipping": {
      "id": 1,
      "name": "John Doe"
    },
    "default_billing": {
      "id": 1,
      "name": "John Doe"
    }
  }
}
```

### Add New Address
**Endpoint:** `POST /addresses`
**Auth Required:** Yes

**Request:**
```json
{
  "name": "John Doe",
  "phone": "+8801712345678",
  "address_line_1": "123 Main Street",
  "address_line_2": "Apt 4B",
  "city": "Dhaka",
  "state": "Dhaka Division",
  "postal_code": "1205",
  "country": "Bangladesh",
  "landmark": "Near City Bank",
  "type": "both",
  "is_default_shipping": true,
  "is_default_billing": false,
  "delivery_instructions": "Ring doorbell twice"
}
```

### Update Address
**Endpoint:** `PUT /addresses/{address_id}`
**Auth Required:** Yes

### Delete Address
**Endpoint:** `DELETE /addresses/{address_id}`
**Auth Required:** Yes

### Set Default Shipping Address
**Endpoint:** `PUT /addresses/{address_id}/default-shipping`
**Auth Required:** Yes

### Set Default Billing Address
**Endpoint:** `PUT /addresses/{address_id}/default-billing`
**Auth Required:** Yes

### Validate Delivery Area
**Endpoint:** `POST /addresses/validate-delivery`
**Auth Required:** Yes

**Request:**
```json
{
  "city": "Dhaka",
  "state": "Dhaka Division",
  "postal_code": "1205"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "is_delivery_available": true,
    "estimated_delivery_days": "1-2",
    "delivery_charge": 60,
    "message": "Delivery available in 1-2 business days"
  }
}
```

---

## 📦 Order Management

### Get Customer Orders
**Endpoint:** `GET /orders`
**Auth Required:** Yes

**Query Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page
- `status` (string): pending, processing, shipped, delivered, cancelled
- `search` (string): Search by order number or product name
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-241118-1234",
        "status": "shipped",
        "payment_status": "paid",
        "payment_method": "bkash",
        "total_amount": 5250.00,
        "created_at": "2024-11-18T10:30:00Z",
        "estimated_delivery": "2024-11-20",
        "items": [
          {
            "id": 1,
            "product_id": 101,
            "product_name": "Wireless Headphones",
            "quantity": 2,
            "price": 2500.00,
            "total": 5000.00
          }
        ],
        "summary": {
          "total_items": 2,
          "total_amount": 5250.00,
          "status_label": "Shipped",
          "days_since_order": 2,
          "can_cancel": false,
          "can_return": false
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "per_page": 15,
      "total": 45
    }
  }
}
```

### Get Single Order Details
**Endpoint:** `GET /orders/{order_number}`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-241118-1234",
      "status": "shipped",
      "payment_status": "paid",
      "payment_method": "bkash",
      "total_amount": 5250.00,
      "subtotal": 5000.00,
      "tax_amount": 250.00,
      "shipping_charge": 60.00,
      "discount_amount": 0.00,
      "created_at": "2024-11-18T10:30:00Z",
      "items": [
        {
          "id": 1,
          "product_id": 101,
          "product_name": "Wireless Headphones",
          "quantity": 2,
          "price": 2500.00,
          "total": 5000.00,
          "product_image": "https://example.com/headphones.jpg"
        }
      ],
      "delivery_address": {
        "name": "John Doe",
        "phone": "+8801712345678",
        "street": "123 Main Street Apt 4B",
        "city": "Dhaka",
        "postal_code": "1205"
      },
      "summary": {
        "can_cancel": false,
        "can_return": true,
        "tracking_available": true
      }
    }
  }
}
```

### Create Order from Cart
**Endpoint:** `POST /orders/create-from-cart`
**Auth Required:** Yes

**Request:**
```json
{
  "payment_method": "bkash",
  "shipping_address_id": 1,
  "billing_address_id": 1,
  "notes": "Please call before delivery",
  "coupon_code": "WELCOME10",
  "delivery_preference": "standard",
  "scheduled_delivery_date": "2024-11-25"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-241118-1234",
      "status": "pending",
      "total_amount": 5250.00
    },
    "order_summary": {
      "order_number": "ORD-241118-1234",
      "total_amount": 5250.00,
      "payment_method": "bkash",
      "estimated_delivery": "2024-11-22"
    }
  }
}
```

### Cancel Order
**Endpoint:** `POST /orders/{order_number}/cancel`
**Auth Required:** Yes

### Track Order
**Endpoint:** `GET /orders/{order_number}/track`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "order_number": "ORD-241118-1234",
      "status": "shipped",
      "tracking_number": "TRK123456789"
    },
    "tracking": {
      "current_status": "shipped",
      "tracking_number": "TRK123456789",
      "estimated_delivery": "Nov 20, 2024",
      "steps": [
        {
          "status": "pending",
          "label": "Order Placed",
          "completed": true,
          "date": "2024-11-18T10:30:00Z"
        },
        {
          "status": "processing",
          "label": "Order Processing",
          "completed": true,
          "date": "2024-11-18T14:00:00Z"
        },
        {
          "status": "shipped",
          "label": "Order Shipped",
          "completed": true,
          "date": "2024-11-19T09:00:00Z"
        },
        {
          "status": "delivered",
          "label": "Order Delivered",
          "completed": false,
          "date": null
        }
      ]
    }
  }
}
```

---

## 💳 Payment Processing

### Get Available Payment Methods
**Endpoint:** `GET /payments/methods`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "payment_methods": [
      {
        "id": "cash_on_delivery",
        "name": "Cash on Delivery",
        "description": "Pay with cash when your order is delivered",
        "icon": "cash-icon",
        "fee": 0,
        "is_online": false,
        "is_active": true
      },
      {
        "id": "bkash",
        "name": "bKash",
        "description": "Pay using bKash mobile banking",
        "icon": "bkash-icon",
        "fee": 0,
        "is_online": true,
        "is_active": true,
        "instructions": "You will be redirected to bKash payment page"
      }
    ],
    "default_method": "cash_on_delivery"
  }
}
```

### Process Payment
**Endpoint:** `POST /payments/process`
**Auth Required:** Yes

**Request:**
```json
{
  "order_number": "ORD-241118-1234",
  "payment_method": "bkash",
  "payment_data": {
    "phone": "+8801712345678"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "payment_method": "bkash",
    "status": "completed",
    "transaction_id": "BKT16320789123",
    "message": "Payment successful via bKash",
    "order_status": "confirmed"
  }
}
```

### Verify Payment Status
**Endpoint:** `POST /payments/verify`
**Auth Required:** Yes

**Request:**
```json
{
  "order_number": "ORD-241118-1234",
  "transaction_id": "BKT16320789123"
}
```

### Get Payment History
**Endpoint:** `GET /payments/history`
**Auth Required:** Yes

### Request Refund
**Endpoint:** `POST /payments/request-refund`
**Auth Required:** Yes

**Request:**
```json
{
  "order_number": "ORD-241118-1234",
  "reason": "Product defective",
  "refund_method": "original_payment",
  "bank_details": {
    "account_number": "1234567890",
    "bank_name": "ABC Bank"
  }
}
```

---

## 🚛 Order Tracking & Notifications

### Track Order with Real-time Updates
**Endpoint:** `GET /tracking/orders/{order_number}`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "order_number": "ORD-241118-1234",
      "status": "shipped"
    },
    "tracking": {
      "current_status": "shipped",
      "timeline": [
        {
          "status": "pending",
          "title": "Order Placed",
          "description": "Your order has been placed successfully",
          "completed": true,
          "timestamp": "2024-11-18T10:30:00Z",
          "icon": "check-circle"
        },
        {
          "status": "shipped",
          "title": "Order Shipped",
          "description": "Your order is on its way",
          "completed": true,
          "timestamp": "2024-11-19T09:00:00Z",
          "icon": "truck",
          "tracking_number": "TRK123456789"
        }
      ],
      "estimated_delivery": "Nov 22, 2024"
    },
    "real_time_updates": [
      {
        "timestamp": "2024-11-19T09:00:00Z",
        "message": "Order has left the warehouse",
        "location": "In transit"
      }
    ]
  }
}
```

### Get All Orders Tracking
**Endpoint:** `GET /tracking/orders`
**Auth Required:** Yes

### Update Notification Preferences
**Endpoint:** `PUT /tracking/preferences`
**Auth Required:** Yes

**Request:**
```json
{
  "email_notifications": true,
  "sms_notifications": false,
  "order_updates": true,
  "delivery_updates": true,
  "promotional_notifications": false
}
```

### Get Notification History
**Endpoint:** `GET /tracking/notifications`
**Auth Required:** Yes

### Get Live Delivery Location
**Endpoint:** `GET /tracking/delivery/{order_number}`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "order_number": "ORD-241118-1234",
      "status": "out_for_delivery"
    },
    "location": {
      "current_location": {
        "lat": 23.8103,
        "lng": 90.4125,
        "address": "Delivery vehicle location"
      },
      "delivery_address": {
        "lat": 23.8103,
        "lng": 90.4125,
        "address": "Customer delivery address"
      },
      "delivery_person": {
        "name": "Karim Ahmed",
        "phone": "+8801712345678",
        "vehicle_type": "Motorcycle"
      }
    },
    "estimated_arrival": "02:30 PM"
  }
}
```

---

## 🎧 Customer Support

### Get Support Tickets
**Endpoint:** `GET /support/tickets`
**Auth Required:** Yes

**Query Parameters:**
- `status` (string): open, pending, resolved
- `priority` (string): low, medium, high, urgent

**Response:**
```json
{
  "success": true,
  "data": {
    "tickets": [
      {
        "ticket_id": "TKT-241118-0001",
        "subject": "Order not delivered",
        "category": "delivery",
        "priority": "high",
        "status": "open",
        "created_at": "2024-11-18T08:30:00Z",
        "updated_at": "2024-11-18T09:30:00Z",
        "order_number": "ORD-241118-1234"
      }
    ],
    "summary": {
      "open_tickets": 2,
      "pending_tickets": 1,
      "resolved_tickets": 5
    }
  }
}
```

### Create Support Ticket
**Endpoint:** `POST /support/tickets`
**Auth Required:** Yes

**Request:**
```json
{
  "subject": "Product not working",
  "category": "product",
  "priority": "high",
  "description": "The headphones I received are not working properly. The left speaker has no sound.",
  "order_number": "ORD-241118-1234",
  "attachments": ["file1.jpg", "file2.pdf"]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Support ticket created successfully",
  "data": {
    "ticket": {
      "ticket_id": "TKT-241118-0002",
      "subject": "Product not working",
      "status": "open",
      "created_at": "2024-11-18T10:00:00Z"
    },
    "estimated_response_time": "2-4 hours"
  }
}
```

### Get Ticket Details
**Endpoint:** `GET /support/tickets/{ticket_id}`
**Auth Required:** Yes

### Add Message to Ticket
**Endpoint:** `POST /support/tickets/{ticket_id}/messages`
**Auth Required:** Yes

**Request:**
```json
{
  "message": "I have tried resetting the headphones but the issue persists.",
  "attachments": ["additional-photo.jpg"]
}
```

### Get FAQ
**Endpoint:** `GET /support/faq`

**Query Parameters:**
- `category` (string): order, delivery, payment, refund, account, product
- `search` (string): Search term

**Response:**
```json
{
  "success": true,
  "data": {
    "faqs": [
      {
        "id": 1,
        "category": "order",
        "question": "How can I track my order?",
        "answer": "You can track your order by going to the 'My Orders' section and clicking on the track button next to your order.",
        "helpful_count": 25
      }
    ],
    "categories": [
      {
        "id": "order",
        "name": "Orders & Tracking"
      }
    ]
  }
}
```

### Initiate Live Chat
**Endpoint:** `POST /support/live-chat`
**Auth Required:** Yes

**Request:**
```json
{
  "initial_message": "I need help with my recent order",
  "category": "order"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Live chat session initiated",
  "data": {
    "chat_session": {
      "chat_id": "chat_67890",
      "status": "waiting",
      "queue_position": 3,
      "estimated_wait_time": "5 minutes"
    },
    "support_hours": "9 AM - 9 PM (Daily)"
  }
}
```

---

## 🏆 Loyalty Program & Store Credits

### Get Loyalty Program Details
**Endpoint:** `GET /loyalty`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 1,
      "name": "John Doe",
      "member_since": "Jan 2024"
    },
    "loyalty": {
      "current_points": 1250,
      "total_earned": 2500,
      "total_redeemed": 1250,
      "pending_points": 150,
      "points_expiring_soon": 50
    },
    "tier": {
      "current": "Silver",
      "next": "Gold",
      "progress_percentage": 62.5,
      "points_to_next_tier": 1750
    },
    "benefits": [
      "Point earning rate: 1.5 points per ৳10 spent",
      "Birthday discount: 10%",
      "Free shipping threshold: ৳1000",
      "Early access to sales"
    ],
    "store_credits": {
      "current": 250.00,
      "total_earned": 500.00,
      "total_used": 250.00,
      "expiring_soon": 50.00
    }
  }
}
```

### Get Points History
**Endpoint:** `GET /loyalty/points/history`
**Auth Required:** Yes

### Get Available Rewards
**Endpoint:** `GET /loyalty/rewards`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "rewards": [
      {
        "id": "reward_1",
        "name": "৳50 Store Credit",
        "description": "Store credit voucher worth ৳50",
        "category": "store_credit",
        "points_required": 250,
        "can_redeem": true,
        "points_needed": 0
      },
      {
        "id": "reward_2",
        "name": "10% Off Next Order",
        "description": "10% discount on your next purchase",
        "category": "discount",
        "points_required": 200,
        "can_redeem": true,
        "points_needed": 0
      }
    ],
    "current_points": 1250,
    "affordable_count": 2
  }
}
```

### Redeem Reward
**Endpoint:** `POST /loyalty/rewards/redeem`
**Auth Required:** Yes

**Request:**
```json
{
  "reward_id": "reward_1",
  "quantity": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reward redeemed successfully",
  "data": {
    "redemption": {
      "redemption_id": "RED-241118-0001",
      "reward_name": "৳50 Store Credit",
      "points_used": 250,
      "status": "confirmed",
      "redemption_code": "ABC12345"
    },
    "remaining_points": 1000,
    "instructions": [
      "Your store credit has been added to your account",
      "Use it automatically at checkout on your next order"
    ]
  }
}
```

### Get Store Credits
**Endpoint:** `GET /loyalty/store-credits`
**Auth Required:** Yes

### Apply Store Credit to Order
**Endpoint:** `POST /loyalty/store-credits/apply`
**Auth Required:** Yes

**Request:**
```json
{
  "amount": 100.00,
  "order_total": 1500.00
}
```

### Get Referral Program
**Endpoint:** `GET /loyalty/referral`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "referral_program": {
      "referral_code": "REF000001",
      "total_referrals": 3,
      "successful_referrals": 2,
      "total_earned": 500,
      "referral_link": "https://yoursite.com/register?ref=REF000001",
      "program_details": {
        "referrer_reward": "200 points + ৳100 store credit",
        "referee_reward": "100 points + 10% discount on first order"
      }
    }
  }
}
```

---

## ⭐ Product Reviews & Ratings

### Get Product Reviews (Public)
**Endpoint:** `GET /reviews/products/{product_id}`

**Query Parameters:**
- `rating` (int): Filter by star rating (1-5)
- `sort` (string): newest, oldest, highest_rating, lowest_rating, helpful
- `per_page` (int): Items per page

**Response:**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 101,
      "name": "Wireless Headphones",
      "average_rating": 4.2,
      "total_reviews": 128
    },
    "reviews": [
      {
        "review_id": "REV-241118-0001",
        "customer_name": "Ahmed K.",
        "customer_verified": true,
        "rating": 5,
        "title": "Excellent product!",
        "comment": "Really happy with this purchase. Quality is great and delivery was fast.",
        "pros": "Good quality, fast delivery",
        "cons": "Nothing to complain about",
        "recommend": true,
        "helpful_count": 12,
        "has_images": true,
        "purchase_verified": true,
        "created_at": "2024-11-16T10:30:00Z",
        "images": [
          {
            "url": "https://example.com/review1.jpg",
            "thumb": "https://example.com/review1_thumb.jpg"
          }
        ]
      }
    ],
    "statistics": {
      "average_rating": 4.2,
      "total_reviews": 128,
      "rating_distribution": {
        "5": 65,
        "4": 32,
        "3": 18,
        "2": 8,
        "1": 5
      },
      "verified_reviews_count": 95,
      "recommendation_percentage": 85
    }
  }
}
```

### Get Customer's Reviews
**Endpoint:** `GET /reviews/my-reviews`
**Auth Required:** Yes

### Get Products Available for Review
**Endpoint:** `GET /reviews/reviewable-products`
**Auth Required:** Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "reviewable_products": [
      {
        "product_id": 1,
        "product_name": "Wireless Headphones",
        "order_number": "ORD-241118-9999",
        "purchased_date": "2024-11-15T10:30:00Z",
        "delivery_date": "2024-11-17T15:00:00Z",
        "price": 2500.00,
        "image_url": "https://example.com/headphones.jpg",
        "can_review_until": "2024-12-17T15:00:00Z"
      }
    ],
    "total_pending_reviews": 2,
    "incentive_message": "Get 50 loyalty points for each review you write!"
  }
}
```

### Create Product Review
**Endpoint:** `POST /reviews`
**Auth Required:** Yes

**Request (FormData for file uploads):**
```javascript
const formData = new FormData();
formData.append('product_id', '101');
formData.append('order_number', 'ORD-241118-9999');
formData.append('rating', '5');
formData.append('title', 'Great product!');
formData.append('comment', 'Very satisfied with this purchase.');
formData.append('pros', 'Good quality, fast shipping');
formData.append('cons', 'None');
formData.append('recommend', 'true');
formData.append('images[]', file1);
formData.append('images[]', file2);
```

**Response:**
```json
{
  "success": true,
  "message": "Review submitted successfully",
  "data": {
    "review": {
      "review_id": "REV-241118-0002",
      "rating": 5,
      "title": "Great product!",
      "status": "pending"
    },
    "points_earned": 50,
    "moderation_message": "Your review will be published after moderation (usually within 24 hours)"
  }
}
```

### Update Review
**Endpoint:** `PUT /reviews/{review_id}`
**Auth Required:** Yes

### Delete Review
**Endpoint:** `DELETE /reviews/{review_id}`
**Auth Required:** Yes

### Mark Review as Helpful
**Endpoint:** `POST /reviews/{review_id}/helpful`
**Auth Required:** Yes

### Report Review
**Endpoint:** `POST /reviews/{review_id}/report`
**Auth Required:** Yes

**Request:**
```json
{
  "reason": "inappropriate_content",
  "comment": "This review contains offensive language"
}
```

---

## ⚠️ Error Handling

### Standard Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message for this field"]
  }
}
```

### Common HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 400 | Bad Request (validation errors) |
| 401 | Unauthorized (authentication required) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Unprocessable Entity (validation failed) |
| 500 | Internal Server Error |

### Example Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Authentication Error (401):**
```json
{
  "success": false,
  "message": "Unauthenticated. Please login to continue.",
  "error": "Token not provided"
}
```

**Not Found Error (404):**
```json
{
  "success": false,
  "message": "Product not found",
  "error": "No product found with the given ID"
}
```

---

## 🔧 Integration Examples

### React/JavaScript Integration

#### 1. API Client Setup
```javascript
// api.js
const API_BASE_URL = 'https://your-api-domain.com/api';

class EcommerceAPI {
  constructor() {
    this.token = localStorage.getItem('auth_token');
  }

  setAuthToken(token) {
    this.token = token;
    localStorage.setItem('auth_token', token);
  }

  getHeaders() {
    const headers = {
      'Content-Type': 'application/json',
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    return headers;
  }

  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const config = {
      headers: this.getHeaders(),
      ...options,
    };

    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'API request failed');
    }

    return data;
  }

  // Authentication
  async login(email, password) {
    const response = await this.request('/customer-auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    if (response.success) {
      this.setAuthToken(response.data.tokens.access_token);
    }

    return response;
  }

  // Cart operations
  async getCart() {
    return this.request('/cart');
  }

  async addToCart(productId, quantity, variantOptions = {}) {
    return this.request('/cart/add', {
      method: 'POST',
      body: JSON.stringify({
        product_id: productId,
        quantity,
        variant_options: variantOptions,
      }),
    });
  }

  // Product catalog
  async getProducts(filters = {}) {
    const queryString = new URLSearchParams(filters).toString();
    return this.request(`/catalog/products?${queryString}`);
  }

  async getProduct(productId) {
    return this.request(`/catalog/products/${productId}`);
  }

  // Orders
  async createOrder(orderData) {
    return this.request('/orders/create-from-cart', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });
  }

  async getOrders(filters = {}) {
    const queryString = new URLSearchParams(filters).toString();
    return this.request(`/orders?${queryString}`);
  }
}

export const api = new EcommerceAPI();
```

#### 2. React Component Examples

**Product List Component:**
```jsx
import React, { useState, useEffect } from 'react';
import { api } from './api';

function ProductList() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    page: 1,
    per_page: 20,
    sort: 'newest'
  });

  useEffect(() => {
    loadProducts();
  }, [filters]);

  const loadProducts = async () => {
    try {
      setLoading(true);
      const response = await api.getProducts(filters);
      setProducts(response.data.products);
    } catch (error) {
      console.error('Failed to load products:', error);
    } finally {
      setLoading(false);
    }
  };

  const addToCart = async (productId) => {
    try {
      await api.addToCart(productId, 1);
      alert('Product added to cart!');
    } catch (error) {
      console.error('Failed to add to cart:', error);
    }
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="product-grid">
      {products.map(product => (
        <div key={product.id} className="product-card">
          <img src={product.images[0]?.url} alt={product.name} />
          <h3>{product.name}</h3>
          <p className="price">৳{product.price}</p>
          <button onClick={() => addToCart(product.id)}>
            Add to Cart
          </button>
        </div>
      ))}
    </div>
  );
}

export default ProductList;
```

**Shopping Cart Component:**
```jsx
import React, { useState, useEffect } from 'react';
import { api } from './api';

function ShoppingCart() {
  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadCart();
  }, []);

  const loadCart = async () => {
    try {
      const response = await api.getCart();
      setCart(response.data);
    } catch (error) {
      console.error('Failed to load cart:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateQuantity = async (itemId, newQuantity) => {
    try {
      await api.request(`/cart/update/${itemId}`, {
        method: 'PUT',
        body: JSON.stringify({ quantity: newQuantity }),
      });
      loadCart(); // Reload cart
    } catch (error) {
      console.error('Failed to update quantity:', error);
    }
  };

  const removeItem = async (itemId) => {
    try {
      await api.request(`/cart/remove/${itemId}`, {
        method: 'DELETE',
      });
      loadCart(); // Reload cart
    } catch (error) {
      console.error('Failed to remove item:', error);
    }
  };

  if (loading) return <div>Loading cart...</div>;
  if (!cart || cart.cart_items.length === 0) {
    return <div>Your cart is empty</div>;
  }

  return (
    <div className="shopping-cart">
      <h2>Shopping Cart</h2>
      {cart.cart_items.map(item => (
        <div key={item.id} className="cart-item">
          <img src={item.product_image} alt={item.product_name} />
          <div className="item-details">
            <h4>{item.product_name}</h4>
            <p>৳{item.price} x {item.quantity}</p>
            <div className="quantity-controls">
              <button onClick={() => updateQuantity(item.id, item.quantity - 1)}>
                -
              </button>
              <span>{item.quantity}</span>
              <button onClick={() => updateQuantity(item.id, item.quantity + 1)}>
                +
              </button>
            </div>
            <button onClick={() => removeItem(item.id)}>
              Remove
            </button>
          </div>
        </div>
      ))}
      <div className="cart-summary">
        <p>Total Items: {cart.summary.total_items}</p>
        <p>Subtotal: ৳{cart.summary.subtotal}</p>
        <p>Total: ৳{cart.summary.estimated_total}</p>
        <button className="checkout-btn">
          Proceed to Checkout
        </button>
      </div>
    </div>
  );
}

export default ShoppingCart;
```

#### 3. Order Tracking Component
```jsx
import React, { useState, useEffect } from 'react';
import { api } from './api';

function OrderTracking({ orderNumber }) {
  const [tracking, setTracking] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadTracking();
  }, [orderNumber]);

  const loadTracking = async () => {
    try {
      const response = await api.request(`/tracking/orders/${orderNumber}`);
      setTracking(response.data);
    } catch (error) {
      console.error('Failed to load tracking:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading tracking info...</div>;

  return (
    <div className="order-tracking">
      <h2>Order Tracking - {orderNumber}</h2>
      <div className="tracking-timeline">
        {tracking.tracking.timeline.map((step, index) => (
          <div 
            key={index} 
            className={`timeline-step ${step.completed ? 'completed' : 'pending'}`}
          >
            <div className="step-icon">
              <i className={`icon-${step.icon}`}></i>
            </div>
            <div className="step-content">
              <h4>{step.title}</h4>
              <p>{step.description}</p>
              {step.timestamp && (
                <span className="timestamp">
                  {new Date(step.timestamp).toLocaleString()}
                </span>
              )}
            </div>
          </div>
        ))}
      </div>
      <div className="estimated-delivery">
        <p>Estimated Delivery: {tracking.tracking.estimated_delivery}</p>
      </div>
    </div>
  );
}

export default OrderTracking;
```

### Payment Integration Example

```javascript
// Payment processing function
async function processPayment(orderNumber, paymentMethod, paymentData = {}) {
  try {
    const response = await api.request('/payments/process', {
      method: 'POST',
      body: JSON.stringify({
        order_number: orderNumber,
        payment_method: paymentMethod,
        payment_data: paymentData,
      }),
    });

    if (response.success) {
      // Handle successful payment
      switch (paymentMethod) {
        case 'bkash':
        case 'nagad':
          // Redirect to payment gateway or show success
          window.location.href = `/order-confirmation/${orderNumber}`;
          break;
        case 'cash_on_delivery':
          // Show order confirmation
          alert('Order confirmed! You will pay upon delivery.');
          break;
        default:
          console.log('Payment processed:', response.data);
      }
    }
  } catch (error) {
    console.error('Payment failed:', error);
    alert('Payment failed. Please try again.');
  }
}
```

---

## 📱 Mobile App Integration Tips

### 1. Offline Cart Management
```javascript
// Store cart locally for offline support
const offlineCart = {
  save: (cart) => {
    localStorage.setItem('offline_cart', JSON.stringify(cart));
  },
  
  load: () => {
    const stored = localStorage.getItem('offline_cart');
    return stored ? JSON.parse(stored) : { items: [] };
  },
  
  sync: async () => {
    const offlineItems = offlineCart.load();
    if (offlineItems.items.length > 0) {
      // Sync with server when online
      for (const item of offlineItems.items) {
        await api.addToCart(item.product_id, item.quantity);
      }
      localStorage.removeItem('offline_cart');
    }
  }
};
```

### 2. Push Notifications Setup
```javascript
// Register for push notifications
async function registerForPushNotifications(orderNumber) {
  try {
    await api.request('/tracking/subscribe', {
      method: 'POST',
      body: JSON.stringify({
        order_number: orderNumber,
        notification_types: ['status_change', 'delivery_attempt']
      }),
    });
  } catch (error) {
    console.error('Failed to register for notifications:', error);
  }
}
```

### 3. Image Upload for Reviews
```javascript
// Upload review images
async function submitReviewWithImages(reviewData, imageFiles) {
  const formData = new FormData();
  
  // Add text data
  Object.keys(reviewData).forEach(key => {
    formData.append(key, reviewData[key]);
  });
  
  // Add image files
  imageFiles.forEach((file, index) => {
    formData.append(`images[]`, file);
  });
  
  try {
    const response = await fetch(`${API_BASE_URL}/reviews`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${api.token}`,
      },
      body: formData,
    });
    
    return await response.json();
  } catch (error) {
    console.error('Failed to submit review:', error);
    throw error;
  }
}
```

---

## 🎯 Best Practices

### 1. Authentication Management
- Store JWT tokens securely
- Implement automatic token refresh
- Handle authentication errors gracefully
- Provide clear login/logout flows

### 2. Cart Persistence
- Save cart state locally for guest users
- Merge guest cart with user cart on login
- Implement cart abandonment recovery

### 3. Order Management
- Show clear order status updates
- Provide estimated delivery times
- Enable order cancellation when possible
- Send confirmation emails/SMS

### 4. Payment Security
- Never store sensitive payment data
- Use secure payment gateways
- Implement payment verification
- Handle payment failures gracefully

### 5. Performance Optimization
- Implement lazy loading for product images
- Use pagination for large lists
- Cache frequently accessed data
- Implement search debouncing

### 6. User Experience
- Provide loading states for all actions
- Show helpful error messages
- Implement search suggestions
- Enable product comparison features

---

This documentation provides a complete guide for integrating with the e-commerce API system. For any questions or additional features, please contact the development team.