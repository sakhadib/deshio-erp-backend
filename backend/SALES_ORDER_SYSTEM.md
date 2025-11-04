# Complete Sales/Order Management System - 3 Channels

## Overview

This system handles sales across **3 channels** with full payment fragmentation support (split payments, partial payments, installments):

1. **In-Person/Counter Sales** - Customer walks into shop, salesman creates order
2. **Social Commerce** - Employee places order for customer via WhatsApp/Phone
3. **E-commerce** - Customer places order online

## Key Features

✅ **3 Sales Channels** with unique identifiers  
✅ **Salesman Tracking** - Every order tracks who created it  
✅ **Automatic Inventory Reduction** on order completion  
✅ **Batch-Level Stock Management** - Sell from specific batches  
✅ **Payment Fragmentation** - Split, partial, installment payments  
✅ **Customer Auto-Creation** - Create customer on-the-fly if doesn't exist  
✅ **Order Lifecycle** - pending → completed → cancelled  

---

## Complete Sales Workflow

###  Channel 1: In-Person/Counter Sale

**Scenario**: Customer comes to shop, buys 2 iPhones

```http
# Step 1: Create Order (salesman creates for themselves)
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "John Doe",
    "phone": "01712345678",
    "address": "Dhaka"
  },
  "items": [
    {
      "product_id": 1,
      "batch_id": 5,  // Specific batch in this store
      "quantity": 2,
      "unit_price": 75000.00,
      "discount_amount": 5000.00
    }
  ],
  "discount_amount": 2000.00,
  "payment": {
    "payment_method_id": 1,  // Cash
    "amount": 145000.00,
    "payment_type": "full"
  }
}

# OR: Manager creates order for specific salesman
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "salesman_id": 7,  // Manual salesman entry (for POS)
  "customer": {
    "name": "John Doe",
    "phone": "01712345678",
    "address": "Dhaka"
  },
  "items": [...],
  "payment": {...}
}

Response:
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20241104-ABC123",
    "order_type": "counter",
    "salesman": {
      "id": 7,
      "name": "Ahmed Rahman"  // Manually assigned salesman
    },
    "customer": {
      "id": 10,
      "name": "John Doe",
      "customer_code": "CUST-2024-XYZ789"
    },
    "total_amount": "145000.00",
    "paid_amount": "145000.00",
    "outstanding_amount": "0.00",
    "payment_status": "paid",
    "status": "pending"  // Not completed yet
  }
}

# Step 2: Complete Order (Reduce Inventory)
PATCH /api/orders/1/complete

Response:
{
  "success": true,
  "message": "Order completed successfully. Inventory updated.",
  "data": {
    "status": "completed",
    "confirmed_at": "2024-11-04 14:30:00"
  }
}

# What happens:
✅ Batch quantity reduced: 100 → 98
✅ Customer purchase stats updated
✅ Order marked completed
✅ Salesman's sales count increased
```

### Channel 2: Social Commerce Sale

**Scenario**: Customer calls via WhatsApp, employee takes order

```http
POST /api/orders
{
  "order_type": "social_commerce",
  "store_id": 1,
  "customer": {
    "name": "Sarah Ahmed",
    "phone": "01987654321",
    "address": "Gulshan, Dhaka"
  },
  "items": [
    {
      "product_id": 2,
      "batch_id": 7,
      "quantity": 1,
      "unit_price": 45000.00
    }
  ],
  "shipping_amount": 200.00,
  "notes": "Customer wants delivery by Friday",
  "shipping_address": {
    "name": "Sarah Ahmed",
    "phone": "01987654321",
    "address": "House 12, Road 5, Gulshan-2",
    "city": "Dhaka",
    "postal_code": "1212"
  },
  "payment": {
    "payment_method_id": 2,  // bKash
    "amount": 20000.00,
    "payment_type": "partial"
  },
  "installment_plan": {
    "total_installments": 3,
    "installment_amount": 8400.00,
    "start_date": "2024-12-01"
  }
}

Response:
{
  "success": true,
  "data": {
    "order_number": "ORD-20241104-DEF456",
    "order_type": "social_commerce",
    "salesman": {
      "id": 3,
      "name": "Fatima Khan"  // Employee who took the call
    },
    "total_amount": "45200.00",
    "paid_amount": "20000.00",
    "outstanding_amount": "25200.00",
    "payment_status": "partially_paid",
    "is_installment": true,
    "installment_info": {
      "total_installments": 3,
      "paid_installments": 1,
      "next_payment_due": "2024-12-01"
    }
  }
}

# Later: Customer pays next installment
POST /api/orders/2/payments/simple
{
  "payment_method_id": 2,
  "amount": 8400.00,
  "payment_type": "installment"
}

# When ready to ship, complete the order
PATCH /api/orders/2/complete
```

### Channel 3: E-commerce Sale

**Scenario**: Customer orders from website

```http
POST /api/orders
{
  "order_type": "ecommerce",
  "store_id": 1,  // Main warehouse
  "customer_id": 15,  // Registered customer
  "items": [
    {
      "product_id": 3,
      "batch_id": 10,
      "quantity": 1,
      "unit_price": 12000.00
    },
    {
      "product_id": 5,
      "batch_id": 12,
      "quantity": 2,
      "unit_price": 3500.00
    }
  ],
  "discount_amount": 1000.00,
  "shipping_amount": 150.00,
  "shipping_address": {
    "name": "Ali Hassan",
    "phone": "01712345678",
    "address": "123 Main Street",
    "city": "Chittagong",
    "postal_code": "4000"
  },
  "payment": {
    "payment_method_id": 5,  // SSL Commerz (Online)
    "amount": 18150.00,
    "payment_type": "full"
  }
}

# Complete and ship
PATCH /api/orders/3/complete
```

---

## API Endpoints Reference

### Order Management (11 Endpoints)

```http
# List orders (with filters)
GET /api/orders?order_type=counter&payment_status=partially_paid

# Get order details
GET /api/orders/{id}

# Create order (all 3 channels)
POST /api/orders

# Add item to order
POST /api/orders/{id}/items
{
  "product_id": 1,
  "batch_id": 5,
  "quantity": 1,
  "unit_price": 750.00
}

# Update item (quantity/price)
PUT /api/orders/{orderId}/items/{itemId}
{
  "quantity": 3,
  "unit_price": 700.00
}

# Remove item
DELETE /api/orders/{orderId}/items/{itemId}

# Complete order (reduce inventory)
PATCH /api/orders/{id}/complete

# Cancel order
PATCH /api/orders/{id}/cancel
{
  "reason": "Customer changed mind"
}

# Get statistics
GET /api/orders/statistics?date_from=2024-11-01&created_by=5
```

### Payment Processing (Existing OrderPaymentController)

```http
# Simple payment
POST /api/orders/{order}/payments/simple
{
  "payment_method_id": 1,
  "amount": 1000.00,
  "payment_type": "partial"
}

# Split payment (multiple methods)
POST /api/orders/{order}/payments/split
{
  "total_amount": 10000.00,
  "splits": [
    {
      "payment_method_id": 1,  // Cash
      "amount": 5000.00
    },
    {
      "payment_method_id": 2,  // bKash
      "amount": 5000.00
    }
  ],
  "cash_received": [
    {"denomination": 1000, "quantity": 5}
  ]
}

# All payments for order
GET /api/orders/{order}/payments/advanced
```

---

## Complete Use Cases

### Use Case 1: Counter Sale with Cash

```bash
# Customer buys laptop
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "Rahim Ahmed",
    "phone": "01712345678"
  },
  "items": [{
    "product_id": 10,
    "batch_id": 25,
    "quantity": 1,
    "unit_price": 65000.00
  }],
  "payment": {
    "payment_method_id": 1,  // Cash
    "amount": 65000.00,
    "payment_type": "full"
  }
}

# Complete immediately
PATCH /api/orders/1/complete

✅ Inventory reduced
✅ Customer created with counter type
✅ Salesman tracked (authenticated user)
✅ Payment recorded
✅ Order completed
```

### Use Case 1b: Manager Creates Order for Salesman (POS)

```bash
# Manager logged in, creates order for specific salesman
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "salesman_id": 12,  // Assign to specific salesman for commission
  "customer": {
    "name": "Kamal Hossain",
    "phone": "01812345678"
  },
  "items": [{
    "product_id": 10,
    "batch_id": 25,
    "quantity": 1,
    "unit_price": 65000.00
  }],
  "payment": {
    "payment_method_id": 1,
    "amount": 65000.00,
    "payment_type": "full"
  }
}

# Complete immediately
PATCH /api/orders/1/complete

✅ Order credited to salesman #12 (not manager)
✅ Commission tracked correctly
✅ Statistics show correct salesman
```

### Use Case 2: Social Commerce with Installments

```bash
# 1. Employee takes order via WhatsApp
POST /api/orders
{
  "order_type": "social_commerce",
  "store_id": 1,
  "customer": {
    "name": "Nadia Khan",
    "phone": "01987654321"
  },
  "items": [{
    "product_id": 5,
    "batch_id": 15,
    "quantity": 1,
    "unit_price": 30000.00
  }],
  "installment_plan": {
    "total_installments": 6,
    "installment_amount": 5000.00,
    "start_date": "2024-11-10"
  }
}
# Order created, no payment yet

# 2. Customer pays first installment
POST /api/orders/1/payments/simple
{
  "payment_method_id": 2,  // bKash
  "amount": 5000.00,
  "payment_type": "installment"
}

# 3. Ship the product
PATCH /api/orders/1/complete

# 4. Customer pays 2nd installment
POST /api/orders/1/payments/simple
{
  "payment_method_id": 2,
  "amount": 5000.00,
  "payment_type": "installment"
}

# Payment status automatically updates:
# - 1 installment: partially_paid
# - 6 installments: paid
# - Overdue: payment_status becomes 'overdue'
```

### Use Case 3: E-commerce with Partial Payment

```bash
# Customer orders online
POST /api/orders
{
  "order_type": "ecommerce",
  "store_id": 1,
  "customer_id": 50,
  "items": [{
    "product_id": 8,
    "batch_id": 20,
    "quantity": 2,
    "unit_price": 8500.00
  }],
  "shipping_amount": 200.00,
  "payment": {
    "payment_method_id": 5,  // Online gateway
    "amount": 7000.00,
    "payment_type": "partial"
  }
}

# Later: Customer pays remaining
POST /api/orders/1/payments/simple
{
  "payment_method_id": 5,
  "amount": 10200.00,  // Remaining amount
  "payment_type": "final"
}

# Ship the order
PATCH /api/orders/1/complete
```

### Use Case 4: Counter Sale with Split Payment

```bash
# Customer pays with cash + card
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "Karim Sheikh",
    "phone": "01612345678"
  },
  "items": [{
    "product_id": 12,
    "batch_id": 30,
    "quantity": 1,
    "unit_price": 25000.00
  }]
}
# Order created

# Add split payment
POST /api/orders/1/payments/split
{
  "total_amount": 25000.00,
  "splits": [
    {
      "payment_method_id": 1,  // Cash
      "amount": 15000.00
    },
    {
      "payment_method_id": 3,  // Card
      "amount": 10000.00,
      "transaction_reference": "CARD-TXN-123"
    }
  ],
  "cash_received": [
    {"denomination": 1000, "quantity": 15}
  ],
  "cash_change": [
    {"denomination": 100, "quantity": 0}
  ]
}

# Complete order
PATCH /api/orders/1/complete
```

---

## Salesman Tracking

Every order automatically tracks the salesman (employee) who created it:

```json
{
  "order_number": "ORD-20241104-ABC123",
  "salesman": {
    "id": 5,
    "name": "Ahmed Rahman"
  },
  "created_at": "2024-11-04 14:30:00"
}
```

### Manual Salesman Entry (POS/Counter)

For POS/counter sales, you can manually specify the salesman. This is useful when:
- A manager creates an order for another salesman
- Shared POS terminals where multiple salesmen work
- Commission tracking for specific salesmen

```http
# Manager creates order and assigns to specific salesman
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "salesman_id": 7,  // Manual salesman assignment
  "customer": {...},
  "items": [...]
}

# If salesman_id not provided, uses authenticated employee (default)
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  // salesman_id omitted → uses Auth::id()
  "customer": {...},
  "items": [...]
}
```

**Use Cases:**
- 📱 **Shared POS Terminal**: Manager creates order, selects salesman from dropdown
- 💼 **Commission Tracking**: Assign sales to correct salesman for commission
- 👔 **Supervisor Orders**: Supervisor creates order on behalf of field salesman
- 🔄 **Order Transfer**: Reassign order to different salesman

### Salesman Performance

```http
# Get orders by specific salesman
GET /api/orders?created_by=5

# Get top salesmen
GET /api/orders/statistics

Response:
{
  "top_salesmen": [
    {
      "employee_id": 5,
      "employee_name": "Ahmed Rahman",
      "order_count": 45,
      "total_sales": "1500000.00"
    },
    {
      "employee_id": 3,
      "employee_name": "Fatima Khan",
      "order_count": 38,
      "total_sales": "1200000.00"
    }
  ]
}
```

---

## Inventory Integration

When order is completed, inventory is automatically reduced:

```
Before Order:
ProductBatch #25
- Quantity: 100 units
- Batch Number: BATCH-20241101-ABC123

Order Created:
Order #ORD-001
- Product: iPhone 15 Pro
- Batch: #25
- Quantity: 2 units
- Status: pending

Order Completed:
PATCH /api/orders/1/complete

After Completion:
ProductBatch #25
- Quantity: 98 units ✅
- Notes: "[2024-11-04 14:30:00] Sold 2 units via Order #ORD-001"

Customer Updated:
- total_purchases: +145000.00
- total_orders: +1
- last_purchase_at: 2024-11-04 14:30:00
```

---

## Payment Status Flow

```
Order Created → payment_status: "pending"
    ↓
First Payment → payment_status: "partially_paid"
    ↓
Full Payment → payment_status: "paid"

If next_payment_due passed:
    → payment_status: "overdue"
```

### Installment Flow

```
Setup Installments:
- total_installments: 6
- installment_amount: 5000.00
- next_payment_due: 2024-11-10

Payment 1 (5000):
- paid_installments: 1
- next_payment_due: 2024-12-10

Payment 2 (5000):
- paid_installments: 2
- next_payment_due: 2025-01-10

...

Payment 6 (5000):
- paid_installments: 6
- payment_status: "paid"
```

---

## Statistics & Reports

### Order Statistics

```http
GET /api/orders/statistics

Response:
{
  "total_orders": 450,
  "by_type": {
    "counter": 250,
    "social_commerce": 150,
    "ecommerce": 50
  },
  "by_status": {
    "pending": 20,
    "confirmed": 10,
    "completed": 400,
    "cancelled": 20
  },
  "by_payment_status": {
    "pending": 15,
    "partially_paid": 85,
    "paid": 340,
    "overdue": 10
  },
  "total_revenue": "15000000.00",
  "total_outstanding": "500000.00",
  "installment_orders": 120,
  "top_salesmen": [...]
}
```

### Filter Options

```http
# By order type
GET /api/orders?order_type=counter

# By payment status
GET /api/orders?payment_status=overdue

# By salesman
GET /api/orders?created_by=5

# By store
GET /api/orders?store_id=1

# By date range
GET /api/orders?date_from=2024-11-01&date_to=2024-11-30

# Installment orders only
GET /api/orders?installment_only=true

# Search by order number or customer
GET /api/orders?search=ORD-001
GET /api/orders?search=John
```

---

## Error Handling

### Common Errors

```json
// Insufficient stock
{
  "success": false,
  "message": "Insufficient stock for iPhone 15 Pro. Available: 5"
}

// Batch not at store
{
  "success": false,
  "message": "Product batch not available at this store"
}

// Cannot complete without payment (if configured)
{
  "success": false,
  "message": "Order must be fully paid before completion"
}

// Cannot add items to completed order
{
  "success": false,
  "message": "Cannot add items to completed orders"
}
```

---

## Best Practices

### For Counter Sales
1. **Option A - Salesman creates own order:**
   - Create order (salesman_id auto-filled from Auth)
   - Add payment immediately (usually full payment)
   - Complete order right away
   - Print receipt

2. **Option B - Manager creates for salesman (shared POS):**
   - Create order with salesman_id field
   - Add payment
   - Complete order
   - Commission credited to specified salesman

### For Social Commerce
1. Create order with customer details
2. Setup installment if needed
3. Get first payment
4. Ship/complete when convenient
5. Track remaining payments

### For E-commerce
1. Customer creates order (or employee on behalf)
2. Payment gateway integration
3. Complete order after payment confirmation
4. Generate shipping label
5. Track delivery

---

## Integration Points

### With Payment System
- Orders use OrderPaymentController for all payments
- Supports split payments (cash + card)
- Supports partial payments
- Supports installments
- Cash denomination tracking

### With Inventory System
- Orders sell from specific ProductBatches
- Inventory reduced on completion
- Batch notes updated
- Stock validation before sale

### With Customer System
- Auto-create customers
- Track customer purchases
- Update customer stats
- Customer type based on order type

---

## Testing Checklist

- [ ] Create counter sale with full payment
- [ ] Create social commerce order with installments
- [ ] Create e-commerce order
- [ ] Add items to pending order
- [ ] Update item quantity
- [ ] Remove item from order
- [ ] Complete order and verify inventory reduction
- [ ] Cancel order
- [ ] Make split payment (cash + card)
- [ ] Make partial payment
- [ ] Setup and pay installments
- [ ] Test overdue payment detection
- [ ] Get salesman statistics
- [ ] Filter orders by various criteria

---

**System Ready**: Complete 3-channel sales system with payment fragmentation, salesman tracking, and automatic inventory management! 🚀
