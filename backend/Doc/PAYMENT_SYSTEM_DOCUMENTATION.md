# Advanced Payment System Documentation

## Overview

This document describes the advanced payment tracking system implemented for the Deshio ERP Backend. The system supports complex payment scenarios including:

1. **Multi-channel Orders**: E-commerce, in-person shop, social media platforms
2. **Flexible Payment Timing**: Pay now or pay later with installment support
3. **Payment Splits**: Single payment split across multiple payment methods
4. **Cash Denomination Tracking**: Track exact cash notes/coins received and change given

## Database Schema

### New Tables

#### 1. `payment_splits`
Tracks when a single payment is split across multiple payment methods.

**Example**: A $1000 order payment can be split as:
- $300 cash
- $500 bank transfer
- $200 card

**Key Fields**:
- `order_payment_id`: Links to the parent payment
- `payment_method_id`: The payment method used for this split
- `amount`: Amount paid via this method
- `split_sequence`: Order of payment (1, 2, 3...)
- `status`: pending, completed, failed, etc.

#### 2. `cash_denominations`
Tracks the actual cash notes/coins received and given as change.

**Example Scenarios**:
1. Customer pays $220 with exact denominations
2. Customer pays $300 (3 × $100 notes) for a $220 bill
   - System records: 3 × $100 received
   - Change given: 1 × $50 + 1 × $20 + 1 × $10

**Key Fields**:
- `payment_split_id` OR `order_payment_id`: Links to parent payment
- `type`: 'received' or 'change'
- `denomination_value`: Value of the note/coin (100, 50, 20, 10, 5, 1, 0.25, etc.)
- `quantity`: Number of notes/coins
- `total_amount`: denomination_value × quantity
- `cash_type`: 'note' or 'coin'

## Models

### PaymentSplit Model
Located at: `app/Models/PaymentSplit.php`

**Relationships**:
- Belongs to `OrderPayment`
- Belongs to `PaymentMethod`
- Belongs to `Store`
- Has many `CashDenomination`

**Key Methods**:
- `complete()`: Mark split as completed
- `fail()`: Mark split as failed
- `refund()`: Process refund for this split
- `isCash()`: Check if this is a cash payment
- `createSplit()`: Static method to create a new split

### CashDenomination Model
Located at: `app/Models/CashDenomination.php`

**Relationships**:
- Belongs to `PaymentSplit` (optional)
- Belongs to `OrderPayment` (optional)
- Belongs to `Store`
- Belongs to `Employee` (recorded_by)

**Key Methods**:
- `recordReceived()`: Record cash received from customer
- `recordChange()`: Record change given to customer
- `getTotalReceived()`: Calculate total cash received
- `getTotalChange()`: Calculate total change given
- `getReceivedBreakdown()`: Get detailed breakdown of received denominations
- `getChangeBreakdown()`: Get detailed breakdown of change given
- `calculateOptimalChange()`: Calculate optimal denomination breakdown for change

## API Endpoints

### 1. Simple Payment (Single Payment Method)
**POST** `/api/orders/{orderId}/payments/simple`

Creates a payment with a single payment method.

**Request Body**:
```json
{
  "payment_method_id": 1,
  "amount": 1000.00,
  "payment_type": "full",
  "transaction_reference": "TXN123456",
  "notes": "Counter sale payment",
  "auto_complete": true,
  "cash_received": [
    {
      "denomination": 100,
      "quantity": 10,
      "type": "note"
    }
  ],
  "cash_change": []
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payment created successfully",
  "data": {
    "id": 1,
    "payment_number": "PAY-20251104-ABC123",
    "amount": 1000.00,
    "status": "completed",
    "cashDenominations": [...]
  }
}
```

### 2. Split Payment (Multiple Payment Methods)
**POST** `/api/orders/{orderId}/payments/split`

Creates a payment split across multiple payment methods.

**Request Body**:
```json
{
  "total_amount": 1000.00,
  "payment_type": "full",
  "notes": "Counter sale with split payment",
  "auto_complete": true,
  "splits": [
    {
      "payment_method_id": 1,
      "amount": 300.00,
      "cash_received": [
        {
          "denomination": 100,
          "quantity": 3,
          "type": "note"
        }
      ]
    },
    {
      "payment_method_id": 2,
      "amount": 500.00,
      "transaction_reference": "BANK-REF-123"
    },
    {
      "payment_method_id": 3,
      "amount": 200.00,
      "transaction_reference": "CARD-REF-456"
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Split payment created successfully",
  "data": {
    "id": 2,
    "payment_number": "PAY-20251104-DEF456",
    "amount": 1000.00,
    "status": "completed",
    "paymentSplits": [
      {
        "split_sequence": 1,
        "payment_method": "Cash",
        "amount": 300.00,
        "cashDenominations": [...]
      },
      {
        "split_sequence": 2,
        "payment_method": "Bank Transfer",
        "amount": 500.00
      },
      {
        "split_sequence": 3,
        "payment_method": "Card",
        "amount": 200.00
      }
    ]
  }
}
```

### 3. Get Payment Details
**GET** `/api/orders/{orderId}/payments/{paymentId}/details`

Retrieves detailed payment information including splits and cash denominations.

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 2,
    "payment_number": "PAY-20251104-DEF456",
    "amount": 1000.00,
    "status": "completed",
    "is_split_payment": true,
    "has_cash_denominations": true,
    "split_summary": [
      {
        "sequence": 1,
        "method": "Cash",
        "amount": 300.00,
        "fee": 0.00,
        "net": 300.00,
        "status": "completed"
      },
      ...
    ]
  }
}
```

### 4. Get Cash Denominations
**GET** `/api/orders/{orderId}/payments/{paymentId}/cash-denominations`

Retrieves detailed cash denomination breakdown for a payment.

**Response**:
```json
{
  "success": true,
  "data": {
    "payment_id": 2,
    "payment_number": "PAY-20251104-DEF456",
    "has_splits": true,
    "splits": [
      {
        "split_id": 1,
        "sequence": 1,
        "payment_method": "Cash",
        "amount": 300.00,
        "cash_received": [
          {
            "denomination": 100,
            "quantity": 3,
            "total": 300,
            "type": "note",
            "currency": "USD"
          }
        ],
        "total_received": 300.00,
        "cash_change": [],
        "total_change": 0.00
      }
    ]
  }
}
```

### 5. Calculate Optimal Change
**POST** `/api/payment-utils/calculate-change`

Calculates the optimal denomination breakdown for giving change.

**Request Body**:
```json
{
  "amount": 80.00,
  "currency": "USD"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "amount": 80.00,
    "currency": "USD",
    "change_breakdown": [
      {
        "denomination": 50,
        "quantity": 1,
        "total": 50,
        "type": "note"
      },
      {
        "denomination": 20,
        "quantity": 1,
        "total": 20,
        "type": "note"
      },
      {
        "denomination": 10,
        "quantity": 1,
        "total": 10,
        "type": "note"
      }
    ],
    "total_denominations": 3
  }
}
```

### 6. Process Payment
**POST** `/api/orders/{orderId}/payments/{paymentId}/process`

Starts processing a pending payment.

### 7. Complete Payment
**POST** `/api/orders/{orderId}/payments/{paymentId}/complete`

Marks a payment as completed.

**Request Body**:
```json
{
  "transaction_reference": "TXN-FINAL-123",
  "external_reference": "EXT-REF-456"
}
```

### 8. Fail Payment
**POST** `/api/orders/{orderId}/payments/{paymentId}/fail`

Marks a payment as failed.

**Request Body**:
```json
{
  "reason": "Insufficient funds"
}
```

### 9. Refund Payment
**POST** `/api/orders/{orderId}/payments/{paymentId}/refund`

Processes a refund for a payment.

**Request Body**:
```json
{
  "amount": 100.00,
  "reason": "Customer requested partial refund"
}
```

## Use Cases

### Use Case 1: Simple Counter Sale with Exact Cash
```
Customer buys for $220, pays with exact denominations:
- 3 × $50 notes
- 2 × $20 notes
- 3 × $10 notes
```

**API Call**:
```bash
POST /api/orders/123/payments/simple
{
  "payment_method_id": 1,
  "amount": 220.00,
  "auto_complete": true,
  "cash_received": [
    {"denomination": 50, "quantity": 3, "type": "note"},
    {"denomination": 20, "quantity": 2, "type": "note"},
    {"denomination": 10, "quantity": 3, "type": "note"}
  ]
}
```

### Use Case 2: Counter Sale with Change
```
Customer buys for $220, pays with 3 × $100 notes
Change to give: 1 × $50 + 1 × $20 + 1 × $10
```

**API Call**:
```bash
POST /api/orders/123/payments/simple
{
  "payment_method_id": 1,
  "amount": 220.00,
  "auto_complete": true,
  "cash_received": [
    {"denomination": 100, "quantity": 3, "type": "note"}
  ],
  "cash_change": [
    {"denomination": 50, "quantity": 1, "type": "note"},
    {"denomination": 20, "quantity": 1, "type": "note"},
    {"denomination": 10, "quantity": 1, "type": "note"}
  ]
}
```

### Use Case 3: Split Payment
```
Customer buys for $1000, pays with:
- $300 cash
- $500 bank transfer
- $200 card
```

**API Call**:
```bash
POST /api/orders/123/payments/split
{
  "total_amount": 1000.00,
  "auto_complete": true,
  "splits": [
    {
      "payment_method_id": 1,
      "amount": 300.00,
      "cash_received": [{"denomination": 100, "quantity": 3}]
    },
    {
      "payment_method_id": 2,
      "amount": 500.00,
      "transaction_reference": "BANK-123"
    },
    {
      "payment_method_id": 3,
      "amount": 200.00,
      "transaction_reference": "CARD-456"
    }
  ]
}
```

### Use Case 4: Installment Payment
```
Customer buys for $5000, wants to pay in 5 installments of $1000 each
First installment paid with cash
```

**Setup installment plan** (existing endpoint):
```bash
POST /api/orders/123/payments/installment/setup
{
  "total_installments": 5,
  "installment_amount": 1000.00,
  "start_date": "2025-11-04"
}
```

**Pay first installment**:
```bash
POST /api/orders/123/payments/simple
{
  "payment_method_id": 1,
  "amount": 1000.00,
  "payment_type": "installment",
  "auto_complete": true,
  "cash_received": [
    {"denomination": 100, "quantity": 10}
  ]
}
```

## Benefits

### 1. Complete Audit Trail
- Every payment method used is tracked
- Exact cash notes received and given are recorded
- All transaction references are stored
- Payment status history is maintained

### 2. Flexible Payment Options
- Customers can split payments across multiple methods
- Installment plans without rigid schedules
- Partial payments accepted anytime
- Cash denomination tracking for accountability

### 3. Multi-Channel Support
- E-commerce orders
- In-person shop sales
- Social media platform orders (via employee)
- All channels support the same payment features

### 4. Financial Reconciliation
- Easy to reconcile cash drawer
- Track which denominations are in stock
- Identify shortages or overages
- Complete payment method breakdown

## Migration Commands

To run the new migrations:

```bash
php artisan migrate
```

This will create:
- `payment_splits` table
- `cash_denominations` table

## Notes

1. **Existing System**: The system already had support for installment payments and multiple payment methods. These new migrations ADD split payment and cash denomination tracking.

2. **Backward Compatibility**: Existing payments without splits or cash denominations will continue to work normally.

3. **Optional Features**: Cash denomination tracking is optional. If not provided in the request, the payment will still be created without denomination details.

4. **Auto-Complete**: The `auto_complete` flag in requests is useful for in-person transactions where payment is immediate. For online payments, leave it false and complete manually after verification.

5. **Supported Currencies**: Currently supports USD and BDT for cash denomination calculations. More can be added easily.

## Testing

Example test scenarios:

1. **Simple cash payment with exact amount**
2. **Cash payment requiring change**
3. **Split payment across 2-3 methods**
4. **Split payment with cash denomination tracking**
5. **Installment payment with cash denomination tracking**
6. **Refund of split payment**
7. **Calculate optimal change for various amounts**

## Future Enhancements

1. Cash drawer management integration
2. Automated denomination suggestions based on available cash
3. Multi-currency support expansion
4. Payment gateway integrations for online payments
5. Automatic split suggestions based on customer preferences
6. Payment analytics and reporting dashboards
