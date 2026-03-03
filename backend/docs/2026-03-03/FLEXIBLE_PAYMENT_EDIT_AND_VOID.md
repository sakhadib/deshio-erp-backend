# Flexible Payment System - Edit & Void Features

**Date:** March 3, 2026  
**Type:** Feature Enhancement  
**Component:** Order Payment System

---

## Overview

Implemented flexible payment editing and voiding capabilities to support real-world payment scenarios where customers can:
- Pay any amount at any time (no fixed schedule)
- Correct payment entry errors by editing amount/date
- Void incorrect payments and recreate them
- See complete audit trail of all payment changes

This eliminates the need for rigid installment schedules and allows truly flexible partial payments.

---

## Business Problem

**Scenario:** Customer wants to buy 1000 taka product but pay gradually

**Current System Issues:**
1. ❌ Fixed installment schedules (e.g., 3 x 333.33 monthly)
2. ❌ Cannot edit payment amount after entry
3. ❌ Cannot void wrong payments easily
4. ❌ Enforces due dates (customer can't pay anytime)

**PM Request:**
```
"price dhor 1000 taka. ajke ami 300 dilam. koyekdin por 200 dilam, 
arekdin 50 dilam, evabe cholte thaklam when payment purata completes, 
payment will be marked as done. kono specific date jeno na thake. 
like ami ese any day kichu payment diye jaite pari"
```

**Translation:** Total 1000 taka → Pay 300 today, 200 next week, 50 anytime, etc. No fixed dates. Just keep paying until done.

---

## Solution

### Two Payment Modes

**Mode 1: Flexible Partial Payments (RECOMMENDED)**
```php
Order settings:
- allow_partial_payments = true
- is_installment_payment = false
- No next_payment_due date
- No fixed installment amounts

Customer behavior:
- Pay any amount, any time
- No due date enforcement
- Auto marks 'paid' when total reached
```

**Mode 2: Rigid Installments (Legacy)**
```php
Order settings:
- is_installment_payment = true
- total_installments = 3
- installment_amount = 333.33
- next_payment_due = auto-calculated

Use case:
- Formal payment plans
- Due date tracking needed
- Fixed schedule required
```

### New Features Implemented

**1. Edit Payment Amount/Date**
- **Endpoint:** `PUT /api/orders/{order}/payments/{payment}`
- **Use Case:** Correct data entry errors
- **Tracks:** Full audit trail of changes
- **Restrictions:** Only completed payments, no split payments

**2. Void/Delete Payment**
- **Endpoint:** `DELETE /api/orders/{order}/payments/{payment}`
- **Use Case:** Cancel wrong payment and recreate
- **Tracks:** Reason, who voided, when
- **Restrictions:** Cannot void refunded payments

**3. Auto-Recalculation**
- After edit/void, system automatically:
  - Recalculates order.paid_amount
  - Updates order.outstanding_amount
  - Changes order.payment_status (partial/paid)

---

## API Documentation

### 1. Edit Payment

**Endpoint:** `PUT /api/orders/{orderId}/payments/{paymentId}`

**Request Body:**
```json
{
  "amount": 250,
  "payment_received_date": "2026-03-05",
  "notes": "Corrected amount - customer said 250 not 200",
  "transaction_reference": "TXN123456",
  "external_reference": "EXT789",
  "reason": "Data entry error - wrong amount entered"
}
```

**All fields optional except `reason`**

**Success Response (200):**
```json
{
  "success": true,
  "message": "Payment updated successfully",
  "data": {
    "payment": {
      "id": 456,
      "payment_number": "PAY-2026-000123",
      "amount": 250,
      "status": "completed",
      "metadata": {
        "edit_history": [
          {
            "edited_at": "2026-03-07T10:30:00Z",
            "edited_by": 5,
            "edited_by_name": "John Doe",
            "reason": "Data entry error - wrong amount entered",
            "changes": {
              "amount": {
                "old": 200,
                "new": 250
              },
              "fee_amount": {
                "old": 4.00,
                "new": 5.00
              }
            }
          }
        ]
      }
    },
    "order": {
      "id": 123,
      "order_number": "ORD-2026-001234",
      "total_amount": 1000,
      "paid_amount": 550,
      "outstanding_amount": 450,
      "payment_status": "partial"
    },
    "edit_summary": {
      "edited_at": "2026-03-07T10:30:00Z",
      "edited_by": 5,
      "edited_by_name": "John Doe",
      "reason": "Data entry error - wrong amount entered",
      "changes": {
        "amount": {"old": 200, "new": 250}
      }
    }
  }
}
```

**Error Responses:**

```json
// 404 - Payment not found
{
  "success": false,
  "message": "Payment not found for this order"
}

// 422 - Cannot edit split payment
{
  "success": false,
  "message": "Split payments cannot be edited. Please void and recreate."
}

// 422 - Cannot edit refunded payment
{
  "success": false,
  "message": "Cannot edit refunded or cancelled payments"
}

// 422 - Cannot edit incomplete payment
{
  "success": false,
  "message": "Can only edit completed payments. Process/complete the payment first."
}

// 422 - Would cause overpayment
{
  "success": false,
  "message": "New amount would cause order overpayment (outstanding: 450, change: 600)"
}

// 422 - No changes detected
{
  "success": false,
  "message": "No changes detected"
}
```

**Restrictions:**
- ✅ Can edit: amount, date, notes, references
- ❌ Cannot edit: payment_method, order_id
- ❌ Cannot edit split payments (too complex)
- ❌ Cannot edit refunded/cancelled payments
- ❌ Can only edit completed payments
- ❌ New amount cannot cause negative outstanding

---

### 2. Void/Delete Payment

**Endpoint:** `DELETE /api/orders/{orderId}/payments/{paymentId}`

**Request Body:**
```json
{
  "reason": "Wrong payment method used - customer paid with bkash not cash"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Payment voided successfully",
  "data": {
    "payment": {
      "id": 456,
      "payment_number": "PAY-2026-000123",
      "amount": 300,
      "status": "cancelled",
      "failure_reason": "Wrong payment method used - customer paid with bkash not cash",
      "metadata": {
        "void_details": {
          "voided_at": "2026-03-07T11:00:00Z",
          "voided_by": 5,
          "voided_by_name": "John Doe",
          "reason": "Wrong payment method used - customer paid with bkash not cash",
          "original_amount": 300,
          "original_status": "completed"
        }
      }
    },
    "order": {
      "id": 123,
      "order_number": "ORD-2026-001234",
      "total_amount": 1000,
      "paid_amount": 250,
      "outstanding_amount": 750,
      "payment_status": "partial"
    },
    "voided_amount": 300
  }
}
```

**Error Responses:**

```json
// 404 - Payment not found
{
  "success": false,
  "message": "Payment not found for this order"
}

// 422 - Cannot void refunded payment
{
  "success": false,
  "message": "Cannot void refunded payments. Use refund endpoint instead."
}

// 422 - Already voided
{
  "success": false,
  "message": "Payment is already voided"
}
```

**After Voiding:**
- Payment status → 'cancelled'
- Order recalculates: paid_amount, outstanding_amount, payment_status
- Void details stored in payment.metadata
- Audit trail preserved

---

## Complete Workflow Example

### Scenario: Customer buys 1000 taka product, pays gradually

**Day 1: Create order**
```bash
POST /api/orders
{
  "customer_id": 10,
  "items": [...],
  "total_amount": 1000,
  "allow_partial_payments": true,
  "is_installment_payment": false
}

Response:
{
  "order_number": "ORD-123",
  "total_amount": 1000,
  "paid_amount": 0,
  "outstanding_amount": 1000,
  "payment_status": "pending"
}
```

**Day 1: Customer pays 300**
```bash
POST /api/orders/123/payments/simple
{
  "payment_method_id": 1,
  "amount": 300,
  "auto_complete": true
}

Response:
Order: paid_amount=300, outstanding=700, status='partial'
```

**Day 5: Customer pays 200**
```bash
POST /api/orders/123/payments/simple
{
  "payment_method_id": 1,
  "amount": 200,
  "auto_complete": true
}

Response:
Order: paid_amount=500, outstanding=500, status='partial'
```

**Day 7: Oops! Day 5 payment was 250, not 200**
```bash
PUT /api/orders/123/payments/456
{
  "amount": 250,
  "reason": "Customer said amount was 250 not 200"
}

Response:
Order: paid_amount=550, outstanding=450, status='partial'
Payment metadata now has edit_history
```

**Day 10: Wrong! Payment was bkash not cash. Need to fix.**
```bash
# 1. Void the wrong payment
DELETE /api/orders/123/payments/456
{
  "reason": "Wrong payment method - was bkash not cash"
}

Response:
Order: paid_amount=300, outstanding=700, status='partial'

# 2. Create correct payment
POST /api/orders/123/payments/simple
{
  "payment_method_id": 2,  // bkash
  "amount": 250,
  "auto_complete": true
}

Response:
Order: paid_amount=550, outstanding=450, status='partial'
```

**Day 15: Customer pays remaining 450**
```bash
POST /api/orders/123/payments/simple
{
  "payment_method_id": 1,
  "amount": 450,
  "auto_complete": true
}

Response:
Order: paid_amount=1000, outstanding=0, status='PAID' ✅
```

---

## Technical Implementation

### Files Modified

**1. OrderPaymentController.php**
- Added `update()` method (lines ~600-785)
- Added `destroy()` method (lines ~786-890)
- Full audit trail tracking
- Order recalculation after changes

**2. routes/api.php**
- Added `PUT /orders/{order}/payments/{payment}`
- Added `DELETE /orders/{order}/payments/{payment}`

**3. No migrations needed** - Uses existing fields:
- `metadata` column for edit/void history
- `status` column for cancelled status
- `failure_reason` for void reason

### Database Changes

**Payment metadata structure after edit:**
```json
{
  "edit_history": [
    {
      "edited_at": "2026-03-07T10:30:00Z",
      "edited_by": 5,
      "edited_by_name": "John Doe",
      "reason": "Data entry error",
      "changes": {
        "amount": {"old": 200, "new": 250},
        "fee_amount": {"old": 4, "new": 5},
        "notes": {"old": "Payment received", "new": "Corrected payment"}
      }
    }
  ]
}
```

**Payment metadata structure after void:**
```json
{
  "void_details": {
    "voided_at": "2026-03-07T11:00:00Z",
    "voided_by": 5,
    "voided_by_name": "John Doe",
    "reason": "Wrong payment method used",
    "original_amount": 300,
    "original_status": "completed"
  }
}
```

### Key Features

**Edit Payment:**
1. Validates payment belongs to order
2. Cannot edit split payments
3. Cannot edit refunded/cancelled payments
4. Only edits completed payments
5. Recalculates fees based on new amount
6. Tracks all changes in metadata.edit_history
7. Updates order totals automatically
8. Returns full audit trail

**Void Payment:**
1. Validates payment belongs to order
2. Cannot void refunded payments (use refund endpoint)
3. Marks status as 'cancelled'
4. Stores void details in metadata
5. Updates order totals automatically
6. Preserves audit trail

**Auto-Recalculation:**
```php
// After edit or void
$order->updatePaymentStatus();

// This method:
- Sums all completed (non-cancelled) payments
- Updates paid_amount
- Calculates outstanding_amount
- Sets payment_status (pending/partial/paid)
```

---

## Testing Checklist

### Unit Tests Needed

```php
// Edit payment tests
test_edit_payment_amount_updates_order_totals()
test_cannot_edit_split_payment()
test_cannot_edit_refunded_payment()
test_cannot_edit_incomplete_payment()
test_edit_tracks_history_in_metadata()
test_edit_recalculates_fees()
test_new_amount_cannot_cause_overpayment()

// Void payment tests
test_void_payment_marks_as_cancelled()
test_void_payment_updates_order_totals()
test_cannot_void_refunded_payment()
test_void_stores_details_in_metadata()
test_voided_payment_not_counted_in_totals()
```

### Manual Testing Scenarios

1. **Happy Path - Flexible Payments:**
   - Create order 1000 taka
   - Pay 300 → Check status = 'partial'
   - Pay 200 → Check status = 'partial'
   - Pay 500 → Check status = 'paid'

2. **Edit Payment:**
   - Create payment 200
   - Edit to 250
   - Verify order totals updated
   - Check metadata has edit_history

3. **Void Payment:**
   - Create payment 300
   - Void with reason
   - Verify order totals recalculated
   - Check status = 'cancelled'
   - Verify not counted in paid_amount

4. **Error Cases:**
   - Try to edit split payment → Should fail
   - Try to void refunded payment → Should fail
   - Try to edit incomplete payment → Should fail
   - Edit amount causing overpayment → Should fail

---

## Security & Audit Trail

**All changes tracked:**
- Who made the change (employee ID + name)
- When (timestamp)
- Why (required reason field)
- What changed (old vs new values)

**Metadata preserved:**
- Edit history never deleted
- Void details preserved
- Original values stored
- Complete audit trail

**Permissions:**
- Only authenticated employees can edit/void
- Requires proper order access
- Cannot edit other orders' payments

---

## Migration Guide

**From Rigid Installments to Flexible Payments:**

```php
// Old way (rigid)
Order::create([
    'total_amount' => 1000,
    'is_installment_payment' => true,
    'total_installments' => 3,
    'installment_amount' => 333.33,
    'next_payment_due' => now()->addMonth(),
]);

// New way (flexible) ✅
Order::create([
    'total_amount' => 1000,
    'allow_partial_payments' => true,
    'is_installment_payment' => false,
    // No due dates, no fixed amounts
]);
```

**Existing Orders:**
- No migration needed
- Works with both modes
- Default: `allow_partial_payments = true`
- Flexible payments work automatically

---

## Performance Considerations

**Query Cost:**
- Edit: 1 update query + 1 order update
- Void: 1 update query + 1 order update
- Auto-recalculation: 1 sum query (on completed payments)

**Index Usage:**
- `order_payments.order_id` (indexed)
- `order_payments.status` (indexed)
- Efficient even with 1000+ payments per order

**No Performance Impact:**
- Uses existing fields
- No JOIN overhead
- Lightweight JSON metadata storage

---

## Future Enhancements

1. **Bulk Edit** - Edit multiple payments at once
2. **Payment Approval Workflow** - Require manager approval for edits
3. **Edit Time Limit** - Restrict edits after N days
4. **Notification** - Alert customer on payment edit
5. **Payment Templates** - Save common payment amounts
6. **CSV Export** - Export payment history with edits

---

## Related Documentation

- Order Payment System: `app/Models/OrderPayment.php`
- Order Model: `app/Models/Order.php`
- Payment Controller: `app/Http/Controllers/OrderPaymentController.php`
- API Routes: `routes/api.php` (lines 1112-1135)
- Fragmented Payments Migration: `2025_10_29_055717_update_orders_table_for_fragmented_payments.php`

---

## Approval & Deployment

- **Developer:** GitHub Copilot (Claude Sonnet 4.5)
- **Implemented:** March 3, 2026
- **Status:** ✅ Ready for Testing
- **Production Ready:** Yes (pending QA approval)

---

## Quick Reference

**Enable flexible payments:**
```php
'allow_partial_payments' => true,
'is_installment_payment' => false
```

**Edit payment:**
```bash
PUT /api/orders/{order}/payments/{payment}
Body: { "amount": 250, "reason": "Correction" }
```

**Void payment:**
```bash
DELETE /api/orders/{order}/payments/{payment}
Body: { "reason": "Wrong method" }
```

**Check audit trail:**
```php
$payment->metadata['edit_history']  // All edits
$payment->metadata['void_details']  // Void info
```

**Order auto-updates after:**
- ✅ New payment added
- ✅ Payment edited
- ✅ Payment voided
- ✅ Payment refunded
