# Installment Payment Early Payoff Guide

**Document Created:** January 7, 2026  
**Purpose:** Guide for handling early installment payoff scenarios  
**Backend Support:** ✅ Fully Implemented

---

## Overview

The system fully supports customers who want to pay off their installment orders early. An employee can accept any number of payments on an installment order, and the system automatically tracks the balance and updates the order status.

---

## Business Scenario

**Example Case:**
- Mr. Jack buys a product worth 1,000 TK on a 4-month installment plan (250 TK/month)
- **Month 1:** He pays the first installment of 250 TK
- **A few days later:** He returns to the shop and says "Here's 750 TK more, I want to clear my balance"
- **Result:** Employee can process the 750 TK payment, and the order is automatically marked as fully paid

---

## How It Works

### 1. **Multiple Payments on Same Order**
- The system allows unlimited payments on a single order
- Each payment is tracked separately
- Total paid amount is automatically calculated

### 2. **Automatic Balance Calculation**
After each payment completion, the system automatically:
- Calculates `total_paid` by summing all completed payments
- Updates `outstanding_amount = total_amount - total_paid`
- Updates `payment_status` based on the balance:
  - `'paid'` - When fully paid (total_paid >= total_amount)
  - `'partial'` - When partially paid (total_paid < total_amount)
  - `'pending'` - When no payment made yet

### 3. **No Manual Status Updates Required**
The backend automatically handles all status transitions. Frontend just needs to create the payment.

---

## API Endpoint

### **Create Order Payment**

```
POST /api/employee/orders/{order_id}/payments/simple
```

**Authentication:** Required (JWT Bearer Token)

**Headers:**
```json
{
  "Authorization": "Bearer {employee_token}",
  "Content-Type": "application/json"
}
```

---

## Payment Request Examples

### **1st Payment (Initial Installment)**

```json
POST /api/employee/orders/22/payments/simple
{
  "payment_method_id": 1,
  "amount": 250,
  "payment_type": "installment",
  "auto_complete": true,
  "notes": "1st installment of 4-month plan"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment created successfully",
  "data": {
    "id": 45,
    "order_id": 22,
    "amount": 250,
    "status": "completed",
    "payment_type": "installment",
    "order_balance_before": 1000,
    "order_balance_after": 750,
    "completed_at": "2026-01-07T10:30:00Z"
  }
}
```

**Order Status After:** `payment_status: "partial"` (250/1000 paid)

---

### **2nd Payment (Early Payoff)**

A few days later, customer wants to clear the remaining balance:

```json
POST /api/employee/orders/22/payments/simple
{
  "payment_method_id": 1,
  "amount": 750,
  "payment_type": "final",
  "auto_complete": true,
  "notes": "Early payoff - cleared remaining balance"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment created successfully",
  "data": {
    "id": 46,
    "order_id": 22,
    "amount": 750,
    "status": "completed",
    "payment_type": "final",
    "order_balance_before": 750,
    "order_balance_after": 0,
    "completed_at": "2026-01-10T14:20:00Z"
  }
}
```

**Order Status After:** `payment_status: "paid"` ✅ (1000/1000 paid)

---

## Request Parameters

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `payment_method_id` | integer | ✅ | ID of the payment method (1=Cash, 2=Card, etc.) |
| `amount` | number | ✅ | Payment amount (can be any amount ≤ outstanding balance) |
| `payment_type` | string | ❌ | Type: `full`, `installment`, `partial`, `final`, `advance` |
| `auto_complete` | boolean | ❌ | Set to `true` for immediate completion (default: false) |
| `transaction_reference` | string | ❌ | Bank/gateway transaction reference |
| `external_reference` | string | ❌ | External system reference |
| `notes` | string | ❌ | Payment notes/memo |
| `cash_received` | array | ❌ | Cash denomination details (for cash payments) |
| `cash_change` | array | ❌ | Change given details (for cash payments) |

---

## Payment Type Usage

| Payment Type | Use Case |
|-------------|----------|
| `installment` | Regular scheduled installment payment |
| `partial` | Partial payment (not part of installment plan) |
| `final` | Final/clearing payment to complete the order |
| `full` | Full amount payment in one go |
| `advance` | Advance payment before order fulfillment |

**Recommendation:** Use `"final"` for early payoff scenarios.

---

## Frontend Implementation Guide

### **Step 1: Display Order Balance**

When showing an order's payment status, display:
- Total Amount: `order.total_amount`
- Paid Amount: `order.paid_amount`
- Outstanding: `order.outstanding_amount`
- Status: `order.payment_status`

```javascript
// Example order object
{
  "id": 22,
  "total_amount": 1000,
  "paid_amount": 250,
  "outstanding_amount": 750,
  "payment_status": "partial",
  "is_installment_payment": true,
  "total_installments": 4,
  "paid_installments": 1
}
```

### **Step 2: Accept Any Payment Amount**

Allow employees to enter **any amount** up to the outstanding balance:
- Minimum: 0.01
- Maximum: `order.outstanding_amount`
- No need to restrict to installment amount

```javascript
// Validation example
const maxPayment = order.outstanding_amount;
if (paymentAmount > maxPayment) {
  alert(`Payment cannot exceed outstanding amount: ${maxPayment} TK`);
}
```

### **Step 3: Create Payment**

```javascript
async function createPayment(orderId, amount, paymentMethodId, notes) {
  const response = await fetch(
    `/api/employee/orders/${orderId}/payments/simple`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${employeeToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        payment_method_id: paymentMethodId,
        amount: amount,
        payment_type: 'final', // or 'installment' for regular payments
        auto_complete: true,
        notes: notes
      })
    }
  );
  
  return await response.json();
}
```

### **Step 4: Refresh Order Details**

After successful payment, fetch updated order details to show:
- Updated `paid_amount`
- Updated `outstanding_amount`
- Updated `payment_status` (may change to "paid")

---

## Important Notes

### ✅ **Supported Features**
- Multiple payments on same order
- Any payment amount (not restricted to installment amount)
- Automatic status updates
- Payment history tracking
- Balance calculation

### ⚠️ **Frontend Responsibilities**
1. Display accurate outstanding balance
2. Validate payment amount ≤ outstanding
3. Allow flexible payment amounts (don't restrict to fixed installment)
4. Refresh order details after payment
5. Show payment history

### 🔒 **Backend Handles Automatically**
- Sum all completed payments
- Calculate outstanding balance
- Update order payment status
- Track installment progress
- Prevent overpayment (validation exists)

---

## Common UI Scenarios

### **Scenario 1: Regular Installment Payment**
```
Order: 1000 TK (4 installments of 250 TK)
Outstanding: 750 TK

UI Display:
- Suggested Amount: 250 TK (next installment)
- Allow Custom Amount: Yes (1 - 750 TK)
- Payment Type: "installment"
```

### **Scenario 2: Early Payoff**
```
Order: 1000 TK (4 installments of 250 TK)
Outstanding: 750 TK

UI Display:
- Quick Action: "Pay Full Balance (750 TK)"
- Allow Custom Amount: Yes (1 - 750 TK)
- Payment Type: "final"
```

### **Scenario 3: Partial Extra Payment**
```
Order: 1000 TK (4 installments of 250 TK)
Outstanding: 750 TK

UI Display:
- Customer pays: 400 TK
- Remaining after: 350 TK
- Payment Type: "partial"
- Status: Still "partial" (not fully paid)
```

---

## Testing Checklist

- [ ] Create installment order (e.g., 1000 TK, 4 months)
- [ ] Make first payment (250 TK) - Check status = "partial"
- [ ] Make second payment (750 TK) - Check status = "paid"
- [ ] Verify paid_amount = 1000, outstanding_amount = 0
- [ ] Verify both payments appear in order payment history
- [ ] Try overpayment - Should get validation error
- [ ] Test with different payment methods (Cash, Card, etc.)

---

## Error Handling

### **Common Errors:**

**1. Overpayment Attempt**
```json
{
  "success": false,
  "message": "Payment amount exceeds outstanding balance",
  "errors": {
    "amount": ["Amount cannot exceed 750"]
  }
}
```

**2. Order Not Found**
```json
{
  "success": false,
  "message": "Order not found"
}
```

**3. Invalid Payment Method**
```json
{
  "success": false,
  "errors": {
    "payment_method_id": ["The selected payment method id is invalid."]
  }
}
```

---

## Support

For any issues or questions:
- Check order payment history: `GET /api/employee/orders/{order_id}/payments`
- Verify order details: `GET /api/employee/orders/{order_id}`
- Contact backend team if status updates not working correctly

---

**Document Version:** 1.0  
**Last Updated:** January 7, 2026  
**Status:** Production Ready ✅
