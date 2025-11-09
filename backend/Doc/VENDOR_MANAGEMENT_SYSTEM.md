# Vendor Management System - Complete API Documentation

## Overview
Complete vendor management system with purchase orders, batch tracking, and partial payment support. Vendors are suppliers from where products are purchased. Products are bought in batches with the same batch_id and cost_price. Payments can be partial (e.g., $10,000 bill can be paid $7,000 now and $3,000 later). Only warehouses can receive products from vendors.

## Database Schema

### Tables Created
1. **purchase_orders** - Track purchase orders from vendors to warehouses
2. **purchase_order_items** - Individual products in each purchase order
3. **vendor_payments** - Payments made to vendors (supports partial payments)
4. **vendor_payment_items** - Link payments to specific purchase orders

### Key Features
- ✅ Purchase order management with draft/approved/received workflow
- ✅ Batch tracking integration with product_batches table
- ✅ Partial payment tracking ($7,000 now, $3,000 later on $10,000 bill)
- ✅ Advance payment support (pay before purchase order)
- ✅ Warehouse-only validation for receiving products
- ✅ Comprehensive analytics by every aspect
- ✅ Credit limit tracking and validation
- ✅ Payment allocation across multiple purchase orders

---

## API Endpoints

### Vendor Management

#### 1. Get All Vendors
```http
GET /api/vendors
```
**Query Parameters:**
- `type` - Filter by vendor type (manufacturer/distributor)
- `is_active` - Filter by active status (true/false)
- `search` - Search by name, email, contact person, phone
- `sort_by` - Sort field (name, email, type, credit_limit, created_at)
- `sort_direction` - Sort direction (asc/desc)
- `per_page` - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "ABC Suppliers",
        "type": "manufacturer",
        "email": "contact@abc.com",
        "phone": "123-456-7890",
        "credit_limit": 50000.00,
        "payment_terms": "Net 30",
        "is_active": true
      }
    ],
    "total": 25,
    "per_page": 15
  }
}
```

#### 2. Create Vendor
```http
POST /api/vendors
```
**Request:**
```json
{
  "name": "XYZ Distributors",
  "type": "distributor",
  "email": "info@xyz.com",
  "phone": "987-654-3210",
  "address": "123 Main St, City",
  "contact_person": "John Doe",
  "website": "https://xyz.com",
  "credit_limit": 75000.00,
  "payment_terms": "Net 45",
  "notes": "Preferred supplier for electronics"
}
```

#### 3. Get Vendor Analytics
```http
GET /api/vendors/{id}/analytics?from_date=2024-01-01&to_date=2024-12-31
```
**Response:**
```json
{
  "success": true,
  "data": {
    "vendor_info": {
      "id": 1,
      "name": "ABC Suppliers",
      "type": "manufacturer",
      "credit_limit": 50000.00,
      "payment_terms": "Net 30"
    },
    "purchase_orders": {
      "total_orders": 45,
      "total_value": 235000.00,
      "by_status": {
        "draft": { "count": 2, "total_value": 5000.00 },
        "approved": { "count": 5, "total_value": 25000.00 },
        "received": { "count": 38, "total_value": 205000.00 }
      },
      "average_order_value": 5222.22,
      "largest_order": 15000.00,
      "smallest_order": 1500.00
    },
    "payments": {
      "total_paid": 185000.00,
      "total_transactions": 68,
      "by_payment_type": {
        "purchase_order": { "count": 60, "total_amount": 180000.00 },
        "advance": { "count": 8, "total_amount": 5000.00 }
      },
      "average_payment": 2720.59,
      "largest_payment": 10000.00
    },
    "outstanding": {
      "total_outstanding": 50000.00,
      "total_paid": 185000.00,
      "payment_completion_rate": 78.72,
      "credit_utilization": 100.00,
      "exceeded_credit_limit": true
    },
    "products": {
      "total_products_supplied": 125,
      "active_products": 112,
      "total_quantity_purchased": 8500,
      "total_quantity_received": 8350
    },
    "performance": {
      "on_time_deliveries": 32,
      "late_deliveries": 6,
      "cancelled_orders": 2,
      "fulfillment_rate": 84.21
    },
    "monthly_breakdown": {
      "2024-01": { "orders": 4, "total_value": 18500.00, "paid_amount": 15000.00 },
      "2024-02": { "orders": 6, "total_value": 22000.00, "paid_amount": 20000.00 }
    }
  }
}
```

#### 4. Get All Vendors Analytics (Comparison)
```http
GET /api/vendors/analytics?is_active=true
```
**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_vendors": 25,
      "active_vendors": 23,
      "total_credit_limit": 1250000.00,
      "total_outstanding": 485000.00,
      "total_paid": 2850000.00
    },
    "by_type": {
      "manufacturer": {
        "count": 15,
        "total_purchases": 1850000.00,
        "total_paid": 1650000.00
      },
      "distributor": {
        "count": 10,
        "total_purchases": 1485000.00,
        "total_paid": 1200000.00
      }
    },
    "top_vendors_by_volume": [
      {
        "id": 5,
        "name": "Premium Supplies Inc",
        "type": "manufacturer",
        "total_purchases": 385000.00,
        "total_orders": 42,
        "outstanding": 45000.00
      }
    ],
    "vendors_exceeding_credit": [
      {
        "id": 1,
        "name": "ABC Suppliers",
        "credit_limit": 50000.00,
        "outstanding": 55000.00,
        "exceeded_by": 5000.00
      }
    ]
  }
}
```

#### 5. Get Vendor Purchase History
```http
GET /api/vendors/{id}/purchase-history?from_date=2024-01-01&status=received&per_page=20
```

#### 6. Get Vendor Payment History
```http
GET /api/vendors/{id}/payment-history?from_date=2024-01-01&status=completed&per_page=20
```

---

### Purchase Order Management

#### 1. Create Purchase Order
```http
POST /api/purchase-orders
```
**Request:**
```json
{
  "vendor_id": 1,
  "store_id": 2,
  "expected_delivery_date": "2024-12-15",
  "tax_amount": 500.00,
  "discount_amount": 200.00,
  "shipping_cost": 150.00,
  "notes": "Urgent order for holiday stock",
  "terms_and_conditions": "Payment within 30 days",
  "items": [
    {
      "product_id": 10,
      "quantity_ordered": 100,
      "unit_cost": 45.50,
      "unit_sell_price": 65.00,
      "tax_amount": 0,
      "discount_amount": 0,
      "notes": "Bulk discount applied"
    },
    {
      "product_id": 15,
      "quantity_ordered": 50,
      "unit_cost": 120.00,
      "unit_sell_price": 175.00
    }
  ]
}
```
**Response:**
```json
{
  "success": true,
  "message": "Purchase order created successfully",
  "data": {
    "id": 1,
    "po_number": "PO-20241104-000001",
    "vendor_id": 1,
    "store_id": 2,
    "employee_id": 1,
    "status": "draft",
    "payment_status": "unpaid",
    "subtotal": 10550.00,
    "tax_amount": 500.00,
    "discount_amount": 200.00,
    "shipping_cost": 150.00,
    "total_amount": 11000.00,
    "paid_amount": 0.00,
    "outstanding_amount": 11000.00,
    "items": [...]
  }
}
```

#### 2. Approve Purchase Order
```http
POST /api/purchase-orders/{id}/approve
```
**Response:**
```json
{
  "success": true,
  "message": "Purchase order approved successfully",
  "data": {
    "id": 1,
    "status": "approved"
  }
}
```

#### 3. Receive Purchase Order (Creates Product Batches)
```http
POST /api/purchase-orders/{id}/receive
```
**Request:**
```json
{
  "items": [
    {
      "item_id": 1,
      "quantity_received": 100,
      "batch_number": "BATCH-2024-001",
      "manufactured_date": "2024-10-01",
      "expiry_date": "2025-10-01"
    },
    {
      "item_id": 2,
      "quantity_received": 50,
      "batch_number": "BATCH-2024-002",
      "manufactured_date": "2024-10-15",
      "expiry_date": "2026-10-15"
    }
  ]
}
```
**Response:**
```json
{
  "success": true,
  "message": "Products received successfully",
  "data": {
    "id": 1,
    "status": "received",
    "actual_delivery_date": "2024-11-04",
    "items": [
      {
        "id": 1,
        "product_batch_id": 45,
        "batch_number": "BATCH-2024-001",
        "quantity_received": 100,
        "receive_status": "fully_received"
      }
    ]
  }
}
```

#### 4. Get Purchase Order Statistics
```http
GET /api/purchase-orders/stats?from_date=2024-01-01&to_date=2024-12-31
```
**Response:**
```json
{
  "success": true,
  "data": {
    "total_purchase_orders": 125,
    "by_status": [
      { "status": "draft", "count": 5 },
      { "status": "approved", "count": 15 },
      { "status": "received", "count": 95 },
      { "status": "cancelled", "count": 10 }
    ],
    "by_payment_status": [
      { "payment_status": "unpaid", "count": 20 },
      { "payment_status": "partial", "count": 35 },
      { "payment_status": "paid", "count": 70 }
    ],
    "total_amount": 585000.00,
    "total_paid": 425000.00,
    "total_outstanding": 160000.00,
    "overdue_orders": 8,
    "recent_orders": [...]
  }
}
```

#### 5. Add Item to Purchase Order (Draft Only)
```http
POST /api/purchase-orders/{id}/items
```

#### 6. Update Item in Purchase Order (Draft Only)
```http
PUT /api/purchase-orders/{id}/items/{itemId}
```

#### 7. Remove Item from Purchase Order (Draft Only)
```http
DELETE /api/purchase-orders/{id}/items/{itemId}
```

---

### Vendor Payment Management

#### 1. Create Vendor Payment (Partial Payment Example)
```http
POST /api/vendor-payments
```
**Request - Pay $7,000 now on a $10,000 bill:**
```json
{
  "vendor_id": 1,
  "payment_method_id": 1,
  "account_id": 2,
  "amount": 7000.00,
  "payment_date": "2024-11-04",
  "payment_type": "purchase_order",
  "reference_number": "CHQ-12345",
  "notes": "Partial payment - will pay remaining $3000 next week",
  "allocations": [
    {
      "purchase_order_id": 1,
      "amount": 7000.00,
      "notes": "Partial payment on PO-20241104-000001"
    }
  ]
}
```
**Response:**
```json
{
  "success": true,
  "message": "Vendor payment created successfully",
  "data": {
    "id": 1,
    "payment_number": "VP-20241104-000001",
    "vendor_id": 1,
    "amount": 7000.00,
    "allocated_amount": 7000.00,
    "unallocated_amount": 0.00,
    "status": "completed",
    "payment_items": [
      {
        "id": 1,
        "purchase_order_id": 1,
        "allocated_amount": 7000.00,
        "allocation_type": "partial",
        "po_outstanding_before": 10000.00,
        "po_outstanding_after": 3000.00
      }
    ]
  }
}
```

#### 2. Create Second Partial Payment ($3,000 remaining)
```http
POST /api/vendor-payments
```
**Request:**
```json
{
  "vendor_id": 1,
  "payment_method_id": 1,
  "account_id": 2,
  "amount": 3000.00,
  "payment_date": "2024-11-11",
  "payment_type": "purchase_order",
  "notes": "Final payment to complete PO",
  "allocations": [
    {
      "purchase_order_id": 1,
      "amount": 3000.00,
      "notes": "Final payment on PO-20241104-000001"
    }
  ]
}
```
**Response:**
```json
{
  "success": true,
  "message": "Vendor payment created successfully",
  "data": {
    "id": 2,
    "payment_number": "VP-20241111-000001",
    "amount": 3000.00,
    "payment_items": [
      {
        "id": 2,
        "purchase_order_id": 1,
        "allocated_amount": 3000.00,
        "allocation_type": "full",
        "po_outstanding_before": 3000.00,
        "po_outstanding_after": 0.00
      }
    ]
  }
}
```

#### 3. Create Advance Payment (Pay Before Purchase Order)
```http
POST /api/vendor-payments
```
**Request:**
```json
{
  "vendor_id": 1,
  "payment_method_id": 1,
  "account_id": 2,
  "amount": 5000.00,
  "payment_date": "2024-11-01",
  "payment_type": "advance",
  "notes": "Advance payment for future orders"
}
```
**Response:**
```json
{
  "success": true,
  "message": "Vendor payment created successfully",
  "data": {
    "id": 3,
    "payment_number": "VP-20241101-000001",
    "amount": 5000.00,
    "allocated_amount": 0.00,
    "unallocated_amount": 5000.00,
    "payment_type": "advance",
    "status": "completed"
  }
}
```

#### 4. Allocate Advance Payment to Purchase Order
```http
POST /api/vendor-payments/{id}/allocate
```
**Request:**
```json
{
  "allocations": [
    {
      "purchase_order_id": 2,
      "amount": 3000.00,
      "notes": "Allocating advance payment"
    }
  ]
}
```

#### 5. Get Payments for Purchase Order
```http
GET /api/vendor-payments/purchase-order/{purchaseOrderId}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "purchase_order": {
      "id": 1,
      "po_number": "PO-20241104-000001",
      "total_amount": 10000.00,
      "paid_amount": 10000.00,
      "outstanding_amount": 0.00,
      "payment_status": "paid"
    },
    "payments": [
      {
        "id": 1,
        "payment_number": "VP-20241104-000001",
        "payment_date": "2024-11-04",
        "allocated_amount": 7000.00,
        "allocation_type": "partial"
      },
      {
        "id": 2,
        "payment_number": "VP-20241111-000001",
        "payment_date": "2024-11-11",
        "allocated_amount": 3000.00,
        "allocation_type": "full"
      }
    ]
  }
}
```

#### 6. Get Outstanding Payments for Vendor
```http
GET /api/vendor-payments/outstanding/{vendorId}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "total_outstanding": 45000.00,
    "advance_payments_available": 2000.00,
    "net_outstanding": 43000.00,
    "purchase_orders": [
      {
        "id": 5,
        "po_number": "PO-20241101-000003",
        "total_amount": 15000.00,
        "paid_amount": 10000.00,
        "outstanding_amount": 5000.00,
        "payment_status": "partial"
      },
      {
        "id": 8,
        "po_number": "PO-20241103-000005",
        "total_amount": 40000.00,
        "paid_amount": 0.00,
        "outstanding_amount": 40000.00,
        "payment_status": "unpaid"
      }
    ]
  }
}
```

#### 7. Get Payment Statistics
```http
GET /api/vendor-payments/stats?from_date=2024-01-01&to_date=2024-12-31&vendor_id=1
```

#### 8. Cancel Payment (Reverses Allocations)
```http
POST /api/vendor-payments/{id}/cancel
```

#### 9. Refund Payment
```http
POST /api/vendor-payments/{id}/refund
```

---

## Workflow Examples

### Example 1: Complete Purchase Order Workflow

**Step 1: Create Purchase Order**
```http
POST /api/purchase-orders
```

**Step 2: Approve Purchase Order**
```http
POST /api/purchase-orders/1/approve
```

**Step 3: Make Partial Payment ($7,000 of $10,000)**
```http
POST /api/vendor-payments
{
  "amount": 7000.00,
  "allocations": [{ "purchase_order_id": 1, "amount": 7000.00 }]
}
```

**Step 4: Receive Products (Creates Batches)**
```http
POST /api/purchase-orders/1/receive
{
  "items": [
    {
      "item_id": 1,
      "quantity_received": 100,
      "batch_number": "BATCH-2024-001",
      "manufactured_date": "2024-10-01",
      "expiry_date": "2025-10-01"
    }
  ]
}
```

**Step 5: Make Final Payment ($3,000)**
```http
POST /api/vendor-payments
{
  "amount": 3000.00,
  "allocations": [{ "purchase_order_id": 1, "amount": 3000.00 }]
}
```

### Example 2: Advance Payment Workflow

**Step 1: Make Advance Payment**
```http
POST /api/vendor-payments
{
  "payment_type": "advance",
  "amount": 5000.00
}
```

**Step 2: Create Purchase Order Later**
```http
POST /api/purchase-orders
```

**Step 3: Allocate Advance to Purchase Order**
```http
POST /api/vendor-payments/3/allocate
{
  "allocations": [{ "purchase_order_id": 2, "amount": 3000.00 }]
}
```

### Example 3: Multi-PO Payment (Split Payment Across Orders)

**Pay $15,000 across 3 purchase orders:**
```http
POST /api/vendor-payments
{
  "amount": 15000.00,
  "allocations": [
    { "purchase_order_id": 1, "amount": 5000.00 },
    { "purchase_order_id": 2, "amount": 7000.00 },
    { "purchase_order_id": 3, "amount": 3000.00 }
  ]
}
```

---

## Business Rules

### Purchase Orders
1. **Warehouse Only**: Only warehouses (store_type='warehouse') can receive products from vendors
2. **Status Workflow**: draft → approved → partially_received → received (or cancelled)
3. **Payment Status**: unpaid → partial → paid
4. **Editing**: Only draft purchase orders can be edited or have items modified
5. **Cancellation**: Cannot cancel received purchase orders

### Payments
1. **Partial Payments**: Any payment amount can be allocated to a purchase order (partial support)
2. **Multiple POs**: One payment can be split across multiple purchase orders
3. **Advance Payments**: Can pay vendors before creating purchase orders
4. **Allocation**: Advance payments can be allocated to purchase orders later
5. **Refunds**: Only completed payments can be refunded
6. **Cancellation**: Cancelling a payment reverses all allocations

### Batch Tracking
1. **Auto-Create**: Product batches are automatically created when receiving purchase orders
2. **Batch Number**: Each batch has unique batch_number (can use PO number + item ID)
3. **Same Cost**: Products in same batch have same cost_price
4. **Warehouse Storage**: Batches are stored in the warehouse specified in purchase order
5. **Expiry Tracking**: Manufactured date and expiry date tracked per batch

### Credit Limits
1. **Tracking**: System tracks total outstanding amount per vendor
2. **Validation**: Warns when vendor exceeds credit_limit
3. **Reporting**: Analytics show credit utilization percentage
4. **Management**: Credit limits can be updated per vendor

---

## Database Models

### PurchaseOrder Model
**Key Methods:**
- `generatePONumber()` - Generate PO number: PO-YYYYMMDD-XXXXXX
- `calculateTotals()` - Calculate subtotal, total, outstanding from items
- `recordPayment($amount)` - Record payment against this PO
- `markAsReceived($items)` - Receive products and create batches
- `cancel($reason)` - Cancel purchase order
- `isFullyReceived()` - Check if all items received
- `isFullyPaid()` - Check if fully paid
- `getPaymentHistory()` - Get all payments for this PO

### VendorPayment Model
**Key Methods:**
- `generatePaymentNumber()` - Generate payment number: VP-YYYYMMDD-XXXXXX
- `allocateToPurchaseOrders($allocations)` - Allocate payment to multiple POs
- `complete()` - Mark payment as completed
- `cancel()` - Cancel payment and reverse allocations
- `refund()` - Refund payment and create refund entry
- `hasUnallocatedAmount()` - Check if advance payment has unallocated amount

### Vendor Model
**Key Methods:**
- `getTotalOutstanding()` - Get total outstanding across all POs
- `getTotalPaid()` - Get total paid amount
- `hasExceededCreditLimit()` - Check if credit limit exceeded

---

## Testing Guide

### Test Case 1: Partial Payment Workflow
1. Create PO for $10,000
2. Pay $7,000 → Check outstanding = $3,000
3. Pay $3,000 → Check payment_status = 'paid'
4. Verify payment history shows 2 transactions

### Test Case 2: Warehouse Validation
1. Try to create PO with store_type='retail' → Should fail
2. Create PO with store_type='warehouse' → Should succeed

### Test Case 3: Batch Creation
1. Create PO with 2 items
2. Approve PO
3. Receive PO with batch data
4. Verify 2 product_batches created
5. Verify batches linked to correct warehouse

### Test Case 4: Credit Limit
1. Set vendor credit_limit = $50,000
2. Create POs totaling $55,000
3. Pay $10,000 → Check credit_utilization
4. Verify analytics shows exceeded_credit_limit = true

### Test Case 5: Advance Payment
1. Create advance payment $5,000
2. Verify unallocated_amount = $5,000
3. Create PO for $8,000
4. Allocate $3,000 from advance
5. Verify unallocated_amount = $2,000
6. Verify PO outstanding = $5,000

---

## Migration Files

Run migrations in order:
```bash
php artisan migrate
```

**Created Migrations:**
1. `2025_11_04_100001_create_purchase_orders_table.php`
2. `2025_11_04_100002_create_purchase_order_items_table.php`
3. `2025_11_04_100003_create_vendor_payments_table.php`
4. `2025_11_04_100004_create_vendor_payment_items_table.php`

**Existing Tables Used:**
- `vendors` - Vendor master data
- `product_batches` - Product batch tracking
- `stores` - Warehouse/store data
- `products` - Product master data
- `payment_methods` - Payment method types
- `accounts` - Account/wallet data

---

## Summary

✅ **Complete vendor management** with CRUD operations
✅ **Purchase order system** with draft/approve/receive workflow
✅ **Partial payment tracking** - pay $7,000 now, $3,000 later
✅ **Batch tracking** - products bought in batches with same cost_price
✅ **Warehouse validation** - only warehouses receive from vendors
✅ **Advance payments** - pay before creating purchase orders
✅ **Multi-PO payments** - split one payment across multiple POs
✅ **Comprehensive analytics** - by every aspect (volume, performance, timeline, etc.)
✅ **Credit limit management** - track and warn on credit limit exceeded
✅ **Payment allocation** - flexible payment allocation and reallocation

**Total Endpoints Created:** 40+
**Total Models Created:** 4 (PurchaseOrder, PurchaseOrderItem, VendorPayment, VendorPaymentItem)
**Total Migrations Created:** 4

This system provides complete vendor/procurement management with all the requested features!
