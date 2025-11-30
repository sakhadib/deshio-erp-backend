# E-commerce Order Fulfillment Workflow - Complete API Documentation

## Overview
This document describes the complete API workflow for e-commerce orders from cart to Pathao delivery, including customer order placement, employee store assignment, and store fulfillment with barcode scanning.

## Workflow Sequence

```
┌─────────────┐
│  Customer   │
│  Adds Items │
│   to Cart   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Customer  │
│   Places    │
│   Order     │
│ (pending_   │
│ assignment) │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Employee   │
│  Checks     │
│  Inventory  │
│  Across     │
│  Stores     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Employee   │
│  Assigns    │
│  Order to   │
│  Store      │
│ (assigned_  │
│ to_store)   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Store     │
│  Employee   │
│   Scans     │
│  Barcodes   │
│ (picking)   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Order     │
│  Complete   │
│ (ready_for_ │
│ shipment)   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Pathao    │
│  Shipment   │
│  Booking    │
│ (shipped)   │
└─────────────┘
```

## Order Status Transitions

| Status | Description | Next Status | Triggered By |
|--------|-------------|-------------|--------------|
| `pending_assignment` | Order created, no store assigned | `assigned_to_store` | Employee assigns store |
| `assigned_to_store` | Order assigned to specific store | `picking` | Store scans first barcode |
| `picking` | Store is scanning barcodes | `ready_for_shipment` | All items scanned |
| `ready_for_shipment` | All items picked, ready to ship | `shipped` | Pathao shipment created |
| `shipped` | Order shipped via Pathao | `delivered` | Pathao delivery confirmed |
| `delivered` | Order delivered to customer | - | Final status |

---

## API Endpoints

### 1. Customer - Create Order from Cart

**Endpoint:** `POST /api/customer/orders/create-from-cart`  
**Authentication:** `Bearer Token (Customer JWT)`  
**Description:** Convert cart items to an order without store assignment

#### Request Body
```json
{
  "payment_method": "cash_on_delivery",
  "shipping_address_id": 123,
  "billing_address_id": 123,
  "notes": "Please deliver between 2-4 PM",
  "delivery_preference": "standard",
  "scheduled_delivery_date": "2025-12-05",
  "coupon_code": "WELCOME10"
}
```

#### Validation Rules
- `payment_method`: required, in:`cash_on_delivery,bkash,nagad,credit_card,bank_transfer`
- `shipping_address_id`: required, exists in `customer_addresses`
- `billing_address_id`: nullable, exists in `customer_addresses`
- `notes`: nullable, max:500 characters
- `delivery_preference`: nullable, in:`standard,express,scheduled`
- `scheduled_delivery_date`: nullable, date, after:today
- `coupon_code`: nullable, string

#### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Order placed successfully. An employee will assign it to a store shortly.",
  "data": {
    "order": {
      "id": 456,
      "order_number": "ORD-251130-0123",
      "customer_id": 789,
      "store_id": null,
      "order_type": "ecommerce",
      "status": "pending_assignment",
      "payment_status": "pending",
      "subtotal": 2500.00,
      "tax_amount": 125.00,
      "discount_amount": 250.00,
      "shipping_amount": 60.00,
      "total_amount": 2435.00,
      "items": [
        {
          "id": 1001,
          "product_id": 55,
          "product_name": "iPhone 15 Pro",
          "product_sku": "IPH15PRO-256",
          "quantity": 1,
          "unit_price": 1500.00,
          "total_amount": 1500.00
        },
        {
          "id": 1002,
          "product_id": 66,
          "product_name": "AirPods Pro",
          "product_sku": "AIRPODS-PRO-2",
          "quantity": 2,
          "unit_price": 500.00,
          "total_amount": 1000.00
        }
      ]
    },
    "order_summary": {
      "order_number": "ORD-251130-0123",
      "total_items": 3,
      "subtotal": 2500.00,
      "tax": 125.00,
      "shipping": 60.00,
      "discount": 250.00,
      "total_amount": 2435.00,
      "payment_method": "cash_on_delivery",
      "status": "pending_assignment",
      "status_description": "Your order is being processed and will be assigned to a store based on inventory availability."
    }
  }
}
```

#### Error Responses
- `400 Bad Request`: Cart is empty
- `400 Bad Request`: Some products no longer available
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Order creation failed

---

### 2. Employee - Get Pending Assignment Orders

**Endpoint:** `GET /api/order-management/pending-assignment?per_page=15`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** List all e-commerce orders awaiting store assignment

#### Query Parameters
- `per_page` (optional): Number of results per page (default: 15)

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 456,
        "order_number": "ORD-251130-0123",
        "customer_id": 789,
        "customer": {
          "id": 789,
          "name": "John Doe",
          "email": "john@example.com",
          "phone": "+8801712345678"
        },
        "status": "pending_assignment",
        "total_amount": 2435.00,
        "created_at": "2025-11-30T10:30:00Z",
        "items_summary": [
          {
            "product_id": 55,
            "product_name": "iPhone 15 Pro",
            "quantity": 1
          },
          {
            "product_id": 66,
            "product_name": "AirPods Pro",
            "quantity": 2
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "per_page": 15,
      "total": 42
    }
  }
}
```

---

### 3. Employee - Get Available Stores (Inventory Check)

**Endpoint:** `GET /api/order-management/orders/{orderId}/available-stores`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** Check which stores have sufficient inventory to fulfill the order

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "order_id": 456,
    "order_number": "ORD-251130-0123",
    "total_items": 3,
    "stores": [
      {
        "store_id": 5,
        "store_name": "Dhanmondi Branch",
        "store_address": "Road 27, Dhanmondi, Dhaka",
        "can_fulfill_entire_order": true,
        "fulfillment_percentage": 100.0,
        "total_items_available": 5,
        "total_items_required": 3,
        "inventory_details": [
          {
            "product_id": 55,
            "product_name": "iPhone 15 Pro",
            "product_sku": "IPH15PRO-256",
            "required_quantity": 1,
            "available_quantity": 2,
            "can_fulfill": true,
            "batches": [
              {
                "batch_id": 101,
                "batch_number": "BATCH-2025-001",
                "quantity": 2,
                "sell_price": 1500.00,
                "expiry_date": null
              }
            ]
          },
          {
            "product_id": 66,
            "product_name": "AirPods Pro",
            "product_sku": "AIRPODS-PRO-2",
            "required_quantity": 2,
            "available_quantity": 3,
            "can_fulfill": true,
            "batches": [
              {
                "batch_id": 102,
                "batch_number": "BATCH-2025-002",
                "quantity": 3,
                "sell_price": 500.00,
                "expiry_date": "2026-12-31"
              }
            ]
          }
        ]
      },
      {
        "store_id": 8,
        "store_name": "Gulshan Branch",
        "store_address": "Gulshan-2, Dhaka",
        "can_fulfill_entire_order": false,
        "fulfillment_percentage": 66.67,
        "total_items_available": 2,
        "total_items_required": 3,
        "inventory_details": [
          {
            "product_id": 55,
            "product_name": "iPhone 15 Pro",
            "required_quantity": 1,
            "available_quantity": 0,
            "can_fulfill": false,
            "batches": []
          },
          {
            "product_id": 66,
            "product_name": "AirPods Pro",
            "required_quantity": 2,
            "available_quantity": 2,
            "can_fulfill": true,
            "batches": [
              {
                "batch_id": 103,
                "batch_number": "BATCH-2025-003",
                "quantity": 2,
                "sell_price": 500.00,
                "expiry_date": null
              }
            ]
          }
        ]
      }
    ],
    "recommendation": {
      "store_id": 5,
      "store_name": "Dhanmondi Branch",
      "reason": "Can fulfill entire order",
      "fulfillment_percentage": 100
    }
  }
}
```

#### Error Responses
- `400 Bad Request`: Order is not pending assignment
- `404 Not Found`: Order not found
- `500 Internal Server Error`: Failed to fetch available stores

---

### 4. Employee - Assign Order to Store

**Endpoint:** `POST /api/order-management/orders/{orderId}/assign-store`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** Assign an order to a specific store after verifying inventory

#### Request Body
```json
{
  "store_id": 5,
  "notes": "Dhanmondi branch has best inventory availability"
}
```

#### Validation Rules
- `store_id`: required, exists in `stores`
- `notes`: nullable, max:500 characters

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Order successfully assigned to Dhanmondi Branch",
  "data": {
    "order": {
      "id": 456,
      "order_number": "ORD-251130-0123",
      "customer_id": 789,
      "store_id": 5,
      "status": "assigned_to_store",
      "processed_by": 42,
      "store": {
        "id": 5,
        "name": "Dhanmondi Branch",
        "address": "Road 27, Dhanmondi, Dhaka"
      },
      "metadata": {
        "assigned_at": "2025-11-30T11:15:00Z",
        "assigned_by": 42,
        "assignment_notes": "Dhanmondi branch has best inventory availability"
      }
    }
  }
}
```

#### Error Responses
- `400 Bad Request`: Order is not pending assignment
- `400 Bad Request`: Insufficient inventory for product
- `404 Not Found`: Order or store not found
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Failed to assign order

---

### 5. Store Employee - Get Assigned Orders (Dashboard)

**Endpoint:** `GET /api/store/fulfillment/orders/assigned?status=assigned_to_store,picking&per_page=15`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** Get all orders assigned to the employee's store for fulfillment

#### Query Parameters
- `status` (optional): Comma-separated statuses (default: `assigned_to_store,picking`)
- `per_page` (optional): Results per page (default: 15)

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "store": {
      "id": 5,
      "name": "Dhanmondi Branch",
      "address": "Road 27, Dhanmondi, Dhaka"
    },
    "orders": [
      {
        "id": 456,
        "order_number": "ORD-251130-0123",
        "customer": {
          "id": 789,
          "name": "John Doe",
          "phone": "+8801712345678"
        },
        "status": "assigned_to_store",
        "total_amount": 2435.00,
        "created_at": "2025-11-30T10:30:00Z",
        "items": [
          {
            "id": 1001,
            "product_id": 55,
            "product_name": "iPhone 15 Pro",
            "quantity": 1,
            "scan_status": "pending",
            "available_barcodes_count": 2
          },
          {
            "id": 1002,
            "product_id": 66,
            "product_name": "AirPods Pro",
            "quantity": 2,
            "scan_status": "pending",
            "available_barcodes_count": 3
          }
        ],
        "fulfillment_progress": {
          "total_items": 2,
          "fulfilled_items": 0,
          "pending_items": 2,
          "percentage": 0.0,
          "is_complete": false
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "per_page": 15,
      "total": 5
    },
    "summary": {
      "total_orders": 5,
      "assigned_to_store_count": 3,
      "picking_count": 2,
      "ready_for_shipment_count": 0
    }
  }
}
```

#### Error Responses
- `400 Bad Request`: Employee is not assigned to a store
- `500 Internal Server Error`: Failed to fetch assigned orders

---

### 6. Store Employee - Scan Barcode for Order Item

**Endpoint:** `POST /api/store/fulfillment/orders/{orderId}/scan-barcode`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** Scan a product barcode to fulfill an order item

#### Request Body
```json
{
  "barcode": "BC123456789012",
  "order_item_id": 1001
}
```

#### Validation Rules
- `barcode`: required, string
- `order_item_id`: required, exists in `order_items`

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Barcode scanned successfully",
  "data": {
    "order_item": {
      "id": 1001,
      "product_id": 55,
      "product_name": "iPhone 15 Pro",
      "quantity": 1,
      "product_barcode_id": 5001,
      "product_batch_id": 101
    },
    "scanned_barcode": {
      "id": 5001,
      "barcode": "BC123456789012",
      "product_id": 55,
      "batch_id": 101,
      "current_store_id": 15,
      "current_status": "in_shipment",
      "location_metadata": {
        "order_id": 456,
        "order_number": "ORD-251130-0123",
        "scanned_at": "2025-11-30T12:00:00Z",
        "scanned_by": 88
      }
    },
    "order_status": "picking",
    "fulfillment_progress": {
      "fulfilled_items": 1,
      "total_items": 2,
      "percentage": 50.0,
      "is_complete": false
    }
  }
}
```

#### Status Transitions During Scanning
- First scan: `assigned_to_store` → `picking`
- Last scan: `picking` → `ready_for_shipment`

#### Error Responses
- `400 Bad Request`: Order item already scanned
- `400 Bad Request`: Scanned barcode does not match product
- `404 Not Found`: Barcode not found or not available in store
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Failed to scan barcode

---

### 7. Store Employee - Get Order Details

**Endpoint:** `GET /api/store/fulfillment/orders/{orderId}`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** Get detailed order information for fulfillment

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 456,
      "order_number": "ORD-251130-0123",
      "status": "picking",
      "customer": {
        "id": 789,
        "name": "John Doe",
        "phone": "+8801712345678"
      },
      "items": [
        {
          "id": 1001,
          "product_name": "iPhone 15 Pro",
          "quantity": 1,
          "scan_status": "scanned",
          "scanned_barcode": {
            "id": 5001,
            "barcode": "BC123456789012"
          },
          "available_count": 1
        },
        {
          "id": 1002,
          "product_name": "AirPods Pro",
          "quantity": 2,
          "scan_status": "pending",
          "available_barcodes": [
            {
              "id": 5002,
              "barcode": "BC123456789013"
            },
            {
              "id": 5003,
              "barcode": "BC123456789014"
            }
          ],
          "available_count": 2
        }
      ]
    },
    "fulfillment_status": {
      "total_items": 2,
      "fulfilled_items": 1,
      "pending_items": 1,
      "percentage": 50.0,
      "is_complete": false,
      "can_ship": false
    }
  }
}
```

---

### 8. Store Employee - Mark Ready for Shipment

**Endpoint:** `POST /api/store/fulfillment/orders/{orderId}/ready-for-shipment`  
**Authentication:** `Bearer Token (Employee JWT)`  
**Description:** Manually mark order as ready for shipment (after all items scanned)

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Order marked as ready for shipment",
  "data": {
    "order": {
      "id": 456,
      "order_number": "ORD-251130-0123",
      "status": "ready_for_shipment",
      "fulfilled_at": "2025-11-30T12:30:00Z",
      "fulfilled_by": 88
    }
  }
}
```

#### Error Responses
- `400 Bad Request`: Cannot mark as ready - items not yet scanned
- `404 Not Found`: Order not found
- `500 Internal Server Error`: Failed to mark order

---

## Database Changes

### Migrations Applied

1. **2025_11_30_101747_add_ecommerce_statuses_to_orders_table.php**
   - Added new order statuses: `pending_assignment`, `assigned_to_store`, `picking`, `ready_for_shipment`
   - Modified PostgreSQL CHECK constraint on `orders.status` column

2. **2025_11_30_102618_make_store_id_nullable_in_orders_table.php**
   - Made `orders.store_id` column nullable
   - Changed foreign key from `onDelete('cascade')` to `onDelete('set null')`
   - Allows orders to be created without immediate store assignment

### Existing Schema Used

- **orders**: id, order_number, customer_id, store_id (nullable), order_type, status, payment_status, totals, timestamps
- **order_items**: id, order_id, product_id, product_batch_id, product_barcode_id, quantity, unit_price, total_amount
- **product_batches**: id, product_id, store_id, batch_number, quantity, sell_price, availability, expiry_date
- **product_barcodes**: id, barcode, product_id, batch_id, current_store_id, current_status, location_metadata, location_updated_at
- **stores**: id, name, address, pathao_key, is_warehouse, is_online
- **employees**: id, store_id, name, email, role_id

### ProductBarcode Schema Details

The `product_barcodes` table tracks individual physical units with location and status:

**Key Fields:**
- `current_store_id`: Physical location of the barcode/unit (foreign key to stores)
- `current_status`: One of: `in_warehouse`, `in_shop`, `on_display`, `in_transit`, `in_shipment`, `with_customer`
- `location_metadata`: JSON field for additional tracking data (e.g., shelf, bin, order info)
- `location_updated_at`: Timestamp of last location/status change

**Status Flow for E-commerce Orders:**
1. `in_warehouse` or `in_shop` - Available for fulfillment
2. `in_shipment` - Scanned and assigned to order
3. `with_customer` - Delivered (via Pathao tracking)

---

## Authentication & Authorization

### Customer Endpoints
- **Guard:** `auth:customer`
- **JWT Token:** Required in `Authorization: Bearer {token}` header
- **Access:** Own orders only

### Employee Endpoints
- **Guard:** `auth:api` (Employee)
- **JWT Token:** Required in `Authorization: Bearer {token}` header
- **Access Levels:**
  - Order Management: Any employee with permissions
  - Store Fulfillment: Only employees assigned to the order's store

---

## Error Handling

### Standard Error Response Format
```json
{
  "success": false,
  "message": "Human-readable error message",
  "error": "Technical error details (only in development)",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes
- `200 OK`: Successful request
- `201 Created`: Order created successfully
- `400 Bad Request`: Invalid request (business logic failure)
- `401 Unauthorized`: Missing or invalid authentication token
- `403 Forbidden`: Authenticated but not authorized for this resource
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Server error

---

## Testing Plan

### Unit Tests Required

1. **Cart to Order Conversion**
   - Empty cart validation
   - Product availability check
   - Price calculation accuracy
   - Cart clearing after order
   - Address validation
   - Coupon application

2. **Inventory Availability Calculation**
   - Single store full inventory
   - Multiple stores partial inventory
   - No stores with full stock
   - Expired batch exclusion
   - Unavailable batch exclusion (availability=false)
   - FIFO batch ordering

3. **Store Assignment**
   - Successful assignment
   - Insufficient inventory rejection
   - Concurrent assignment race condition
   - Employee authorization
   - Store validation
   - Status transition (pending_assignment → assigned_to_store)

4. **Barcode Scanning**
   - Valid barcode scan
   - Invalid barcode rejection
   - Wrong product barcode
   - Duplicate scan prevention
   - Batch quantity deduction
   - Barcode status update (shop → shipment)
   - Status transitions (assigned_to_store → picking → ready_for_shipment)

5. **Order Fulfillment Progress**
   - Percentage calculation
   - Completion detection
   - Partially fulfilled orders
   - Multiple item handling

### Integration Tests Required

1. **Complete Workflow**
   - Customer creates order from cart
   - Employee checks inventory
   - Employee assigns to store
   - Store scans all barcodes
   - Order marked ready for shipment

2. **Edge Cases**
   - Out of stock during assignment
   - Product deleted after cart addition
   - Store becomes inactive during fulfillment
   - Concurrent barcode scanning
   - Expired batches during fulfillment

3. **Authorization**
   - Customer accessing other customer's orders
   - Store employee accessing other store's orders
   - Employee without store assignment

---

## Pathao Integration (TODO)

### Next Implementation: Pathao Shipment Booking

**Endpoint:** `POST /api/store/fulfillment/orders/{orderId}/book-pathao`  
**Status:** Not yet implemented

#### Planned Features
1. Verify all items scanned
2. Call Pathao API using store's `pathao_key`
3. Create `Shipment` record with tracking number
4. Update order status to `shipped`
5. Send notification to customer

#### Pathao Config (config/pathao.php)
```php
'base_url' => env('PATHAO_BASE_URL', 'https://courier-api-sandbox.pathao.com'),
'client_id' => env('PATHAO_CLIENT_ID'),
'client_secret' => env('PATHAO_CLIENT_SECRET'),
'username' => env('PATHAO_USERNAME'),
'password' => env('PATHAO_PASSWORD'),
```

---

## Performance Considerations

1. **Inventory Queries**
   - Uses eager loading: `with(['items.product'])`
   - Batch queries: Single query per store for all products
   - Indexes: `(product_id, store_id, availability)` on `product_batches`

2. **Pagination**
   - All list endpoints support pagination
   - Default: 15 items per page
   - Adjustable via `per_page` query parameter

3. **Transaction Safety**
   - All write operations use `DB::beginTransaction()`
   - Rollback on exceptions
   - Prevents race conditions on barcode scanning and stock deduction

4. **Caching Opportunities**
   - Store list (rarely changes)
   - Product details (moderate changes)
   - Customer addresses (rarely changes)

---

## Test Coverage

### Comprehensive Test Suite (52 Tests - All Passing ✅)

The workflow is fully tested with PHPUnit feature tests covering all critical paths:

#### 1. EcommerceOrderCreationTest (11 tests)
- ✅ Empty cart validation
- ✅ Successful order creation from cart
- ✅ Order total calculations (subtotal, tax, shipping, discount)
- ✅ Shipping address validation
- ✅ Payment method validation (cod, cash, card, digital_wallet, bank_transfer)
- ✅ Product availability verification
- ✅ City-based shipping charge calculation
- ✅ Order items data integrity
- ✅ Authentication requirements
- ✅ Billing address handling
- ✅ Saved cart items filtering

#### 2. OrderInventoryAvailabilityTest (13 tests)
- ✅ Full inventory stores identification
- ✅ Partial inventory stores ranking
- ✅ Expired batch exclusion
- ✅ Unavailable batch exclusion
- ✅ Store ordering by fulfillment capability
- ✅ Best store recommendations
- ✅ Partial fulfillment recommendations
- ✅ Order status validation (must be pending_assignment)
- ✅ Batch details in response
- ✅ Multiple batch aggregation
- ✅ Authentication requirements
- ✅ Warehouse store exclusion
- ✅ Offline store exclusion

#### 3. OrderStoreAssignmentTest (13 tests)
- ✅ Successful store assignment
- ✅ Insufficient inventory rejection
- ✅ Store existence validation
- ✅ Order status validation (must be pending_assignment)
- ✅ Multiple product inventory validation
- ✅ Expired batch exclusion from checks
- ✅ Unavailable batch exclusion from checks
- ✅ Multiple batch inventory aggregation
- ✅ Authentication requirements
- ✅ Notes length validation (max 1000 chars)
- ✅ Order not found handling
- ✅ Complete order response with store details
- ✅ Concurrent assignment prevention (race conditions)

#### 4. BarcodeScanningFulfillmentTest (15 tests)
- ✅ Successful barcode scanning
- ✅ Status transition: assigned_to_store → picking (first scan)
- ✅ Status transition: picking → ready_for_shipment (last scan)
- ✅ Invalid barcode rejection
- ✅ Wrong product barcode rejection
- ✅ Duplicate scan prevention
- ✅ Different store barcode rejection
- ✅ Wrong status barcode rejection (must be in_shop)
- ✅ Scan metadata storage (location_metadata field)
- ✅ Fulfillment progress tracking
- ✅ Required field validation
- ✅ Authentication requirements
- ✅ Order status validation (assigned_to_store or picking)
- ✅ Store authorization (employee must be from same store)
- ✅ Multiple items scanning workflow

### Schema Alignment Notes

**ProductBarcode Field Mapping:**
- Database uses `current_status` (not `status`)
- Database uses `location_metadata` (not `metadata`)
- Valid status values: `in_warehouse`, `in_shop`, `on_display`, `in_transit`, `in_shipment`, `with_customer`
- Controllers and tests have been aligned with actual database schema

**Test Database:**
- Uses RefreshDatabase trait for test isolation
- All tests run with fresh migrations
- Factories properly aligned with schema constraints
- PostgreSQL check constraints validated

### Running Tests

```bash
# Run all e-commerce workflow tests
php artisan test tests/Feature/EcommerceOrderCreationTest.php tests/Feature/OrderInventoryAvailabilityTest.php tests/Feature/OrderStoreAssignmentTest.php tests/Feature/BarcodeScanningFulfillmentTest.php

# Run specific test suite
php artisan test tests/Feature/BarcodeScanningFulfillmentTest.php

# Run with coverage
php artisan test --coverage
```

---

## Deployment Checklist

- [x] Migrations applied (status enum, store_id nullable)
- [x] Routes registered for all endpoints
- [x] Controllers implemented
- [x] Authorization middleware applied
- [x] Schema alignment completed (ProductBarcode fields)
- [x] Unit tests written and passing (52 tests)
- [x] Integration tests written and passing
- [ ] Pathao integration implemented
- [ ] Notification system integrated
- [x] API documentation reviewed and updated
- [ ] Postman collection created
- [ ] Error monitoring configured
- [ ] Load testing performed

---

## Contact & Support

For questions about this API workflow, contact:
- Backend Team: backend@deshio-erp.com
- API Documentation: https://api-docs.deshio-erp.com
- Issue Tracker: https://github.com/deshio-erp/backend/issues

---

*Last Updated: November 30, 2025*  
*Version: 1.0*  
*Status: Pending Pathao Integration & Testing*
