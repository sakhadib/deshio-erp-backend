# Payment Flow Guide
### Complete API Documentation for POS & Social Commerce Payment Processing

---

## 📋 Table of Contents
1. [Overview](#overview)
2. [Step 1: Get Available Payment Methods](#step-1-get-available-payment-methods)
3. [Step 2: Create Order](#step-2-create-order)
4. [Step 3: Process Payment](#step-3-process-payment)
   - [Single Payment Method](#single-payment-method)
   - [Split Payment (Multiple Methods)](#split-payment-multiple-methods)
5. [Step 4: Complete Order](#step-4-complete-order)
6. [Complete Examples](#complete-examples)
7. [Cash Denomination Tracking](#cash-denomination-tracking)

---

## Overview

### Customer Types & Payment Flow

| Customer Type | Account Required? | Authentication? | Payment Flow |
|--------------|-------------------|-----------------|--------------|
| **POS/Counter** | ❌ No (Phone only) | ❌ No | Employee processes payment at counter |
| **Social Commerce** | ❌ No (Phone only) | ❌ No | Employee processes payment via WhatsApp/Facebook |
| **E-commerce** | ✅ Yes (Email + Password) | ✅ Yes | Customer pays online (future implementation) |

### Key Points
- **POS & Social Commerce**: Employee creates order on behalf of customer (no customer login needed)
- **Split Payments**: Supported for all payment types (cash + card, multiple cards, etc.)
- **Cash Tracking**: Detailed denomination tracking for cash drawers
- **Auto-Complete**: Payments can be auto-completed for in-person transactions

---

## Step 1: Get Available Payment Methods

### 🔓 PUBLIC API - No Authentication Required

**Endpoint**: `GET /api/payment-methods`

### Request
```http
GET /api/payment-methods?customer_type=counter
```

### Query Parameters
| Parameter | Type | Required | Values |
|-----------|------|----------|--------|
| `customer_type` | string | ✅ Yes | `counter`, `social_commerce`, `ecommerce` |

### Response
```json
{
  "success": true,
  "data": {
    "customer_type": "counter",
    "payment_methods": [
      {
        "id": 1,
        "code": "cash",
        "name": "Cash",
        "type": "cash",
        "supports_partial": true,
        "requires_reference": false,
        "min_amount": null,
        "max_amount": null,
        "fixed_fee": 0.00,
        "percentage_fee": 0.00
      },
      {
        "id": 2,
        "code": "card",
        "name": "Card Payment",
        "type": "card",
        "supports_partial": true,
        "requires_reference": true,
        "min_amount": null,
        "max_amount": null,
        "fixed_fee": 0.00,
        "percentage_fee": 1.50
      },
      {
        "id": 5,
        "code": "mobile_banking",
        "name": "Mobile Banking",
        "type": "mobile_banking",
        "supports_partial": true,
        "requires_reference": true,
        "fixed_fee": 2.00,
        "percentage_fee": 1.00
      }
    ],
    "note": "No customer account required - phone number only"
  }
}
```

---

## Step 2: Create Order

### 🔒 Authenticated - Employee Login Required

**Endpoint**: `POST /api/orders`

### Request Body
```json
{
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "John Doe",
    "phone": "01712345678",
    "address": "123 Main St, Dhaka"
  },
  "items": [
    {
      "product_id": 10,
      "batch_id": 25,
      "barcode": "ABC123XYZ",
      "quantity": 2,
      "unit_price": 1500.00,
      "discount_amount": 100.00
    }
  ],
  "discount_amount": 50.00,
  "shipping_amount": 0.00,
  "notes": "Counter sale - customer requested gift wrapping"
}
```

### Response
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 28,
    "order_number": "ORD-2025-000028",
    "customer_id": 15,
    "store_id": 1,
    "order_type": "counter",
    "status": "pending",
    "payment_status": "pending",
    "subtotal": 3000.00,
    "tax_amount": 0.00,
    "discount_amount": 150.00,
    "shipping_amount": 0.00,
    "total_amount": 2850.00,
    "outstanding_amount": 2850.00,
    "paid_amount": 0.00
  }
}
```

---

## Step 3: Process Payment

### Option A: Single Payment Method

**Endpoint**: `POST /api/orders/{order}/payments/simple`

### Example 1: Cash Payment (Full Amount)

```json
{
  "payment_method_id": 1,
  "amount": 2850.00,
  "payment_type": "full",
  "auto_complete": true,
  "notes": "Cash payment - customer paid exact amount",
  
  "cash_received": [
    {
      "denomination": 1000,
      "quantity": 2,
      "type": "note"
    },
    {
      "denomination": 500,
      "quantity": 1,
      "type": "note"
    },
    {
      "denomination": 100,
      "quantity": 3,
      "type": "note"
    },
    {
      "denomination": 50,
      "quantity": 1,
      "type": "note"
    }
  ],
  "cash_change": []
}
```

### Example 2: Card Payment

```json
{
  "payment_method_id": 2,
  "amount": 2850.00,
  "payment_type": "full",
  "transaction_reference": "CARD-2025-11-14-001234",
  "external_reference": "VISA-****1234",
  "auto_complete": true,
  "notes": "VISA card payment",
  "payment_data": {
    "card_last_4": "1234",
    "card_type": "VISA",
    "approval_code": "123456"
  }
}
```

### Example 3: Mobile Banking (bKash/Nagad)

```json
{
  "payment_method_id": 5,
  "amount": 2850.00,
  "payment_type": "full",
  "transaction_reference": "bKash-TRX123456789",
  "external_reference": "01712345678",
  "auto_complete": true,
  "notes": "bKash payment from 01712345678",
  "payment_data": {
    "mobile_number": "01712345678",
    "provider": "bKash"
  }
}
```

### Response (All Single Payments)
```json
{
  "success": true,
  "message": "Payment created successfully",
  "data": {
    "id": 45,
    "order_id": 28,
    "payment_method_id": 1,
    "amount": 2850.00,
    "fee_amount": 0.00,
    "net_amount": 2850.00,
    "payment_type": "full",
    "status": "completed",
    "transaction_reference": null,
    "order_balance_before": 2850.00,
    "order_balance_after": 0.00,
    "payment_method": {
      "id": 1,
      "name": "Cash",
      "type": "cash"
    }
  }
}
```

---

### Option B: Split Payment (Multiple Methods)

**Endpoint**: `POST /api/orders/{order}/payments/split`

### Example 1: Cash + Card Split

**Scenario**: Customer pays ৳1000 cash + ৳1850 card

```json
{
  "total_amount": 2850.00,
  "payment_type": "full",
  "auto_complete": true,
  "notes": "Split payment: Cash + Card",
  
  "splits": [
    {
      "payment_method_id": 1,
      "amount": 1000.00,
      "notes": "Cash portion",
      "cash_received": [
        {
          "denomination": 1000,
          "quantity": 1,
          "type": "note"
        }
      ]
    },
    {
      "payment_method_id": 2,
      "amount": 1850.00,
      "transaction_reference": "CARD-2025-11-14-001235",
      "external_reference": "MASTERCARD-****5678",
      "notes": "Card portion",
      "payment_data": {
        "card_last_4": "5678",
        "card_type": "MASTERCARD",
        "approval_code": "654321"
      }
    }
  ]
}
```

### Example 2: Multiple Cards

**Scenario**: Customer uses 2 different cards

```json
{
  "total_amount": 2850.00,
  "payment_type": "full",
  "auto_complete": true,
  "notes": "Two card payment",
  
  "splits": [
    {
      "payment_method_id": 2,
      "amount": 1500.00,
      "transaction_reference": "CARD-001",
      "external_reference": "VISA-****1111",
      "notes": "First card",
      "payment_data": {
        "card_last_4": "1111",
        "card_type": "VISA"
      }
    },
    {
      "payment_method_id": 2,
      "amount": 1350.00,
      "transaction_reference": "CARD-002",
      "external_reference": "VISA-****2222",
      "notes": "Second card",
      "payment_data": {
        "card_last_4": "2222",
        "card_type": "VISA"
      }
    }
  ]
}
```

### Example 3: Cash + Mobile Banking + Card

**Scenario**: Customer uses 3 different payment methods

```json
{
  "total_amount": 2850.00,
  "payment_type": "full",
  "auto_complete": true,
  "notes": "Triple split payment",
  
  "splits": [
    {
      "payment_method_id": 1,
      "amount": 850.00,
      "notes": "Cash portion",
      "cash_received": [
        {
          "denomination": 500,
          "quantity": 1,
          "type": "note"
        },
        {
          "denomination": 100,
          "quantity": 3,
          "type": "note"
        },
        {
          "denomination": 50,
          "quantity": 1,
          "type": "note"
        }
      ]
    },
    {
      "payment_method_id": 5,
      "amount": 1000.00,
      "transaction_reference": "bKash-TRX987654321",
      "external_reference": "01798765432",
      "notes": "bKash payment",
      "payment_data": {
        "mobile_number": "01798765432",
        "provider": "bKash"
      }
    },
    {
      "payment_method_id": 2,
      "amount": 1000.00,
      "transaction_reference": "CARD-003",
      "external_reference": "VISA-****3333",
      "notes": "Card payment",
      "payment_data": {
        "card_last_4": "3333",
        "card_type": "VISA"
      }
    }
  ]
}
```

### Response (Split Payment)
```json
{
  "success": true,
  "message": "Split payment created successfully",
  "data": {
    "id": 46,
    "order_id": 28,
    "payment_method_id": null,
    "amount": 2850.00,
    "fee_amount": 27.75,
    "net_amount": 2822.25,
    "payment_type": "full",
    "status": "completed",
    "order_balance_before": 2850.00,
    "order_balance_after": 0.00,
    "splits": [
      {
        "id": 101,
        "payment_method_id": 1,
        "amount": 1000.00,
        "fee_amount": 0.00,
        "net_amount": 1000.00,
        "sequence": 1,
        "payment_method": {
          "name": "Cash",
          "type": "cash"
        }
      },
      {
        "id": 102,
        "payment_method_id": 2,
        "amount": 1850.00,
        "fee_amount": 27.75,
        "net_amount": 1822.25,
        "sequence": 2,
        "transaction_reference": "CARD-2025-11-14-001235",
        "payment_method": {
          "name": "Card Payment",
          "type": "card"
        }
      }
    ]
  }
}
```

---

## Step 4: Complete Order

**Endpoint**: `PATCH /api/orders/{id}/complete`

### Request
```json
{}
```

### Response
```json
{
  "success": true,
  "message": "Order confirmed successfully. Inventory updated. 2 barcode-tracked item(s), 0 non-tracked item(s) processed.",
  "data": {
    "id": 28,
    "order_number": "ORD-2025-000028",
    "status": "confirmed",
    "payment_status": "paid",
    "confirmed_at": "2025-11-14T10:30:00.000000Z",
    "total_amount": 2850.00,
    "paid_amount": 2850.00,
    "outstanding_amount": 0.00
  }
}
```

---

## Complete Examples

### Scenario 1: POS Counter Sale (Single Cash Payment)

```javascript
// Step 1: Get payment methods (public - no auth)
GET /api/payment-methods?customer_type=counter

// Step 2: Employee creates order
POST /api/orders
Headers: { Authorization: "Bearer {employee_token}" }
Body: {
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "Sarah Ahmed",
    "phone": "01712345678"
  },
  "items": [
    {
      "product_id": 5,
      "batch_id": 12,
      "barcode": "BAR001",
      "quantity": 1,
      "unit_price": 2500.00
    }
  ]
}

// Response: Order ID = 30

// Step 3: Process cash payment
POST /api/orders/30/payments/simple
Headers: { Authorization: "Bearer {employee_token}" }
Body: {
  "payment_method_id": 1,
  "amount": 2500.00,
  "payment_type": "full",
  "auto_complete": true,
  "cash_received": [
    { "denomination": 1000, "quantity": 2, "type": "note" },
    { "denomination": 500, "quantity": 1, "type": "note" }
  ]
}

// Step 4: Complete order
PATCH /api/orders/30/complete
Headers: { Authorization: "Bearer {employee_token}" }
```

---

### Scenario 2: Social Commerce Sale (Mobile Banking)

```javascript
// Step 1: Get payment methods
GET /api/payment-methods?customer_type=social_commerce

// Step 2: Employee creates order from WhatsApp
POST /api/orders
Headers: { Authorization: "Bearer {employee_token}" }
Body: {
  "order_type": "social_commerce",
  "store_id": 2,
  "customer": {
    "name": "Fahim Hassan",
    "phone": "01798765432",
    "address": "Mirpur, Dhaka"
  },
  "items": [
    {
      "product_id": 8,
      "batch_id": 20,
      "quantity": 3,
      "unit_price": 850.00
    }
  ],
  "shipping_amount": 100.00
}

// Response: Order ID = 31, Total = 2650.00

// Step 3: Customer pays via bKash
POST /api/orders/31/payments/simple
Headers: { Authorization: "Bearer {employee_token}" }
Body: {
  "payment_method_id": 5,
  "amount": 2650.00,
  "payment_type": "full",
  "transaction_reference": "bKash-TRX555666777",
  "external_reference": "01798765432",
  "auto_complete": true,
  "notes": "bKash payment received via WhatsApp",
  "payment_data": {
    "mobile_number": "01798765432",
    "provider": "bKash"
  }
}

// Step 4: Complete order
PATCH /api/orders/31/complete
```

---

### Scenario 3: POS Split Payment (Cash + Card)

```javascript
// Step 1: Get payment methods
GET /api/payment-methods?customer_type=counter

// Step 2: Create order
POST /api/orders
Body: {
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "Tasnim Rahman",
    "phone": "01611223344"
  },
  "items": [
    {
      "product_id": 12,
      "batch_id": 30,
      "barcode": "BAR005",
      "quantity": 2,
      "unit_price": 1800.00
    }
  ]
}

// Response: Order ID = 32, Total = 3600.00

// Step 3: Customer wants to split payment
POST /api/orders/32/payments/split
Headers: { Authorization: "Bearer {employee_token}" }
Body: {
  "total_amount": 3600.00,
  "payment_type": "full",
  "auto_complete": true,
  "notes": "Customer paid ৳1600 cash + ৳2000 card",
  "splits": [
    {
      "payment_method_id": 1,
      "amount": 1600.00,
      "notes": "Cash portion",
      "cash_received": [
        { "denomination": 1000, "quantity": 1, "type": "note" },
        { "denomination": 500, "quantity": 1, "type": "note" },
        { "denomination": 100, "quantity": 1, "type": "note" }
      ]
    },
    {
      "payment_method_id": 2,
      "amount": 2000.00,
      "transaction_reference": "CARD-111222333",
      "external_reference": "VISA-****4567",
      "notes": "Card portion",
      "payment_data": {
        "card_last_4": "4567",
        "card_type": "VISA",
        "approval_code": "ABC123"
      }
    }
  ]
}

// Step 4: Complete order
PATCH /api/orders/32/complete
```

---

## Cash Denomination Tracking

### Purpose
Track exact cash received and change given for cash drawer reconciliation.

### Bangladesh Currency Denominations

**Notes (Bills)**:
- ৳1000, ৳500, ৳200, ৳100, ৳50, ৳20, ৳10, ৳5, ৳2, ৳1

**Coins**:
- ৳5, ৳2, ৳1, 50 paisa, 25 paisa, 10 paisa, 5 paisa

### Example: Customer Pays ৳3000, Change ৳150

```json
{
  "payment_method_id": 1,
  "amount": 2850.00,
  "cash_received": [
    { "denomination": 1000, "quantity": 3, "type": "note" }
  ],
  "cash_change": [
    { "denomination": 100, "quantity": 1, "type": "note" },
    { "denomination": 50, "quantity": 1, "type": "note" }
  ]
}
```

### Calculate Change Helper

**Endpoint**: `POST /api/payment-utils/calculate-change`

```json
{
  "amount_due": 2850.00,
  "amount_received": 3000.00
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "amount_due": 2850.00,
    "amount_received": 3000.00,
    "change_amount": 150.00,
    "suggested_denominations": [
      { "denomination": 100, "quantity": 1, "type": "note" },
      { "denomination": 50, "quantity": 1, "type": "note" }
    ]
  }
}
```

---

## Error Handling

### Common Errors

#### 1. Payment Amount Exceeds Outstanding
```json
{
  "success": false,
  "message": "Payment amount exceeds remaining balance of 2850.00"
}
```

#### 2. Split Amount Mismatch
```json
{
  "success": false,
  "message": "Total split amount (2800.00) does not match total payment amount (2850.00)"
}
```

#### 3. Invalid Payment Method for Customer Type
```json
{
  "success": false,
  "message": "Payment method not allowed for this customer type"
}
```

#### 4. Order Already Paid
```json
{
  "success": false,
  "message": "Order cannot accept payments in its current state"
}
```

---

## Best Practices

### For POS Employees
1. ✅ Always get fresh payment methods before each sale
2. ✅ Use `auto_complete: true` for in-person transactions
3. ✅ Record cash denominations for accurate drawer counts
4. ✅ Use split payments when customers use multiple methods
5. ✅ Complete order immediately after payment

### For Social Commerce Employees
1. ✅ Confirm payment received before processing
2. ✅ Save transaction references from bKash/Nagad/Rocket
3. ✅ Add customer phone in payment notes
4. ✅ Include delivery address in order notes
5. ✅ Use mobile banking payment method for digital payments

### Payment Security
1. 🔒 Never store full card numbers
2. 🔒 Always use transaction references for non-cash
3. 🔒 Record external references (last 4 digits, approval codes)
4. 🔒 Employee authentication required for all payments

---

## Summary

| Step | Endpoint | Auth | Purpose |
|------|----------|------|---------|
| 1 | `GET /api/payment-methods` | ❌ No | Get available payment methods |
| 2 | `POST /api/orders` | ✅ Yes | Create order with customer info |
| 3a | `POST /api/orders/{order}/payments/simple` | ✅ Yes | Single payment method |
| 3b | `POST /api/orders/{order}/payments/split` | ✅ Yes | Multiple payment methods |
| 4 | `PATCH /api/orders/{id}/complete` | ✅ Yes | Complete order & reduce inventory |

**Key Points**:
- No customer login required for POS/Social Commerce
- Employee processes everything on behalf of customer
- Split payments fully supported
- Cash denomination tracking for drawer reconciliation
- Auto-complete payments for instant confirmation

---

**Document Version**: 1.0  
**Last Updated**: November 14, 2025  
**API Version**: v1
