# Transaction System Documentation

## Overview
The Transaction system automatically tracks all financial movements (money in/out) in the ERP system. It uses **Laravel Observers** to automatically create accounting transactions whenever payments, refunds, or expenses occur.

---

## How It Works

### 🔄 Auto-Trigger System
Transactions are **automatically created** when:
- ✅ An order payment is made
- ✅ A service order payment is made  
- ✅ An expense payment is completed (actual cash outflow)
- ✅ A vendor payment is completed
- ✅ A refund is processed

**You don't need to manually create transactions** - the observers handle everything!

**Note**: Expense approval does NOT create transactions - only actual ExpensePayment creates transactions (cash basis accounting).

---

## Architecture

### Models Involved
1. **Transaction** (`app/Models/Transaction.php`) - Main accounting transaction model
2. **OrderPayment** - Customer payments for product orders
3. **ServiceOrderPayment** - Customer payments for service orders
4. **ExpensePayment** - Actual expense payment transactions (creates transactions)
5. **VendorPayment** - Payments to vendors/suppliers
6. **Refund** - Customer refunds
7. **Expense** - Business expense records (does NOT create transactions directly)

### Observers (Auto-Trigger)
Located in `app/Observers/`:
- **OrderPaymentObserver** - Triggers on order payment events
- **ServiceOrderPaymentObserver** - Triggers on service payment events
- **ExpensePaymentObserver** - Triggers on expense payment events ✅ **Creates transactions**
- **VendorPaymentObserver** - Triggers on vendor payment events
- **RefundObserver** - Triggers on refund events
- **ExpenseObserver** - Monitors expense lifecycle (cleanup only, no transaction creation)

### Observer Registration
Observers are registered in `app/Providers/AppServiceProvider.php`:
```php
OrderPayment::observe(OrderPaymentObserver::class);
ServiceOrderPayment::observe(ServiceOrderPaymentObserver::class);
Expense::observe(ExpenseObserver::class);           // For cleanup only
ExpensePayment::observe(ExpensePaymentObserver::class); // Creates transactions
VendorPayment::observe(VendorPaymentObserver::class);
Refund::observe(RefundObserver::class);
```

---

## Transaction Types

### 🟢 Debit (Money IN)
- Order payments (counter sales, social commerce, e-commerce)
- Service order payments
- **Account affected**: Cash/Bank Account

### 🔴 Credit (Money OUT)
- Expense payments (actual payment transactions) ✅ **Creates transactions**
- Vendor payments (payments to suppliers)
- Refunds (to customers)
- **Account affected**: Cash/Bank or Expense Account

**Note**: Approving an Expense does NOT create a transaction. Only when ExpensePayment is completed does the transaction get created.

---

## Automatic Transaction Creation

### 1. Order Payment → Transaction
**Trigger**: When `OrderPayment` is created or completed

**Observer**: `OrderPaymentObserver`
```php
// Automatically happens when:
OrderPayment::create([
    'order_id' => 1,
    'amount' => 5000,
    'payment_method_id' => 1,
    'status' => 'completed'
]);

// Creates Transaction:
{
    "type": "debit",
    "amount": 5000,
    "reference_type": "OrderPayment",
    "reference_id": 1,
    "description": "Order Payment - PAY-001",
    "status": "completed"
}
```

### 2. Expense Payment → Transaction
**Trigger**: When `ExpensePayment` is completed (actual cash outflow)

**Observer**: `ExpensePaymentObserver`
```php
// Automatically happens when:
ExpensePayment::create([
    'expense_id' => 1,
    'amount' => 5000,
    'payment_method_id' => 1,
    'status' => 'completed'
]);

// Creates Transaction:
{
    "type": "credit",
    "amount": 5000,
    "reference_type": "ExpensePayment",
    "reference_id": 1,
    "description": "Expense Payment - EXPPAY-20251116-ABC123",
    "status": "completed"
}
```

**Note**: Creating or approving an Expense record does NOT create a transaction. Only ExpensePayment creates transactions.

### 3. Refund → Transaction
**Trigger**: When `Refund` is completed

**Observer**: `RefundObserver`
```php
// Automatically happens when:
Refund::create([
    'order_id' => 1,
    'refund_amount' => 1000,
    'status' => 'completed'
]);

// Creates Transaction:
{
    "type": "credit",
    "amount": 1000,
    "reference_type": "Refund",
    "reference_id": 1,
    "description": "Refund - REF-001",
    "status": "completed"
}
```

### 4. Vendor Payment → Transaction
**Trigger**: When `VendorPayment` is completed

**Observer**: `VendorPaymentObserver`
```php
// Automatically happens when:
VendorPayment::create([
    'vendor_id' => 1,
    'amount' => 50000,
    'payment_method_id' => 1,
    'status' => 'completed'
]);

// Creates Transaction:
{
    "type": "credit",
    "amount": 50000,
    "reference_type": "VendorPayment",
    "reference_id": 1,
    "description": "Vendor Payment - VP-20251116-000001",
    "status": "completed"
}
```

---

## Transaction Lifecycle

### Status Flow
```
pending → completed
        ↓
      failed
        ↓
    cancelled
```

### Status Triggers

#### ✅ Completed
- Order payment status changes to `completed`
- Expense payment status changes to `paid`
- Refund status changes to `completed`

#### ⏳ Pending
- Order payment created but not yet completed
- Expense approved but not yet paid

#### ❌ Failed
- Manual action via API: `POST /api/transactions/{id}/fail`

#### 🚫 Cancelled
- Order payment deleted
- Expense deleted
- Refund cancelled

---

## API Endpoints

### List Transactions
```http
GET /api/transactions
```
**Query Parameters:**
- `status` - Filter by status (pending, completed, failed, cancelled)
- `type` - Filter by type (debit, credit)
- `account_id` - Filter by account
- `store_id` - Filter by store
- `start_date` - Date range start
- `end_date` - Date range end

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "transaction_number": "TRX-20251116-001",
      "transaction_date": "2025-11-16",
      "amount": "5000.00",
      "type": "debit",
      "reference_type": "OrderPayment",
      "reference_id": 1,
      "description": "Order Payment - PAY-001",
      "status": "completed",
      "account": {
        "id": 1,
        "name": "Cash Account"
      }
    }
  ]
}
```

### Get Single Transaction
```http
GET /api/transactions/{id}
```

### Manual Transaction Creation (if needed)
```http
POST /api/transactions
```
**Body:**
```json
{
  "transaction_date": "2025-11-16",
  "amount": 5000,
  "type": "debit",
  "account_id": 1,
  "description": "Manual adjustment",
  "store_id": 1
}
```

### Complete Transaction
```http
POST /api/transactions/{id}/complete
```

### Fail Transaction
```http
POST /api/transactions/{id}/fail
```
**Body:**
```json
{
  "reason": "Payment gateway error"
}
```

### Cancel Transaction
```http
POST /api/transactions/{id}/cancel
```
**Body:**
```json
{
  "reason": "Order cancelled by customer"
}
```

### Get Statistics
```http
GET /api/transactions/statistics
```
**Response:**
```json
{
  "success": true,
  "data": {
    "total_transactions": 150,
    "total_debit": 500000,
    "total_credit": 200000,
    "net_amount": 300000,
    "completed": 140,
    "pending": 8,
    "failed": 2
  }
}
```

### Trial Balance
```http
GET /api/transactions/trial-balance
```

### Account Ledger
```http
GET /api/transactions/ledger/{accountId}
```

---

## Database Schema

### `transactions` Table
```sql
- id (bigint)
- transaction_number (string, unique) - Auto-generated
- transaction_date (date)
- amount (decimal)
- type (enum: debit, credit)
- account_id (foreign key → accounts)
- reference_type (morphs) - OrderPayment, Expense, etc.
- reference_id (morphs)
- description (text)
- store_id (foreign key → stores)
- created_by (foreign key → employees)
- metadata (json) - Additional data
- status (enum: pending, completed, failed, cancelled)
- timestamps
```

---

## Model Methods

### Scopes
```php
// Filter by type
Transaction::debit()->get();
Transaction::credit()->get();

// Filter by status
Transaction::completed()->get();
Transaction::pending()->get();
Transaction::failed()->get();

// Filter by reference
Transaction::byReference(OrderPayment::class, 1)->get();

// Filter by account
Transaction::byAccount(1)->get();

// Filter by store
Transaction::byStore(1)->get();

// Date filters
Transaction::thisMonth()->get();
Transaction::thisYear()->get();
Transaction::byDateRange('2025-01-01', '2025-12-31')->get();
```

### Instance Methods
```php
$transaction->complete();        // Mark as completed
$transaction->fail('reason');    // Mark as failed
$transaction->cancel('reason');  // Mark as cancelled
$transaction->isDebit();         // Check if debit
$transaction->isCredit();        // Check if credit
```

### Static Factory Methods
```php
Transaction::createFromOrderPayment($orderPayment);
Transaction::createFromServiceOrderPayment($servicePayment);
Transaction::createFromExpense($expense);
Transaction::createFromExpensePayment($expensePayment);
Transaction::createFromVendorPayment($vendorPayment);
Transaction::createFromRefund($refund);
```

---

## Important Notes

### ⚠️ Account Configuration
Currently, the system uses **placeholder account IDs**:
```php
private static function getCashAccountId($storeId = null): ?int
{
    return 1; // TODO: Make this configurable
}

private static function getExpenseAccountId($categoryId): ?int
{
    return 2; // TODO: Map expense categories to accounts
}
```

**TODO**: Configure proper account mapping based on:
- Store-specific cash accounts
- Expense category to account mapping
- Payment method to account mapping

### 🔄 Sync Behavior
- Transactions are created **on creation** of payment/expense
- Transactions are updated **on status changes**
- Transactions are cancelled **on deletion**
- Transactions are restored **on restoration**

### 💡 Best Practices
1. **Don't manually create transactions** for payments/expenses - let observers handle it
2. **Use transaction status** to track payment completion
3. **Check transaction existence** before manual creation:
   ```php
   $exists = Transaction::byReference(OrderPayment::class, $paymentId)->exists();
   if (!$exists) {
       Transaction::createFromOrderPayment($payment);
   }
   ```

---

## Testing

### Check if Transaction Created
```php
// After creating order payment
$payment = OrderPayment::create([...]);

// Check transaction
$transaction = Transaction::byReference(OrderPayment::class, $payment->id)->first();

// Should exist
assert($transaction !== null);
assert($transaction->type === 'debit');
assert($transaction->amount == $payment->amount);
```

### Check Transaction Updates
```php
// Update payment status
$payment->update(['status' => 'completed']);

// Transaction should be completed
$transaction->refresh();
assert($transaction->status === 'completed');
```

---

## Common Issues & Solutions

### Issue: Transaction not created
**Cause**: Observer not registered
**Solution**: Check `AppServiceProvider::boot()` for observer registration

### Issue: Duplicate transactions
**Cause**: Multiple observer triggers
**Solution**: Check for existing transaction before creating:
```php
$transaction = Transaction::byReference($model, $id)->first();
if (!$transaction) {
    Transaction::createFrom...($model);
}
```

### Issue: Wrong account used
**Cause**: Placeholder account IDs in helper methods
**Solution**: Update `getCashAccountId()` and `getExpenseAccountId()` with proper logic

---

## Future Enhancements

### 📋 TODO List
1. ✅ Basic transaction auto-creation
2. ✅ Observer registration
3. ⏳ Configurable account mapping
4. ⏳ Store-specific cash accounts
5. ⏳ Payment method to account mapping
6. ⏳ Expense category to account mapping
7. ⏳ Journal entry support
8. ⏳ Multi-currency support
9. ⏳ Reconciliation features
10. ⏳ Financial reports (P&L, Balance Sheet)

---

## Quick Reference

### Transaction Creation Flow
```
OrderPayment created/completed
        ↓
OrderPaymentObserver triggered
        ↓
Transaction::createFromOrderPayment()
        ↓
Transaction saved with:
  - type: debit
  - reference: OrderPayment
  - status: completed/pending
```

### Transaction Relationship
```
Transaction morphTo Reference (OrderPayment, Expense, Refund)
Transaction belongsTo Account
Transaction belongsTo Store
Transaction belongsTo Employee (created_by)
```

---

## Support & Questions

For questions or issues:
1. Check observer registration in `AppServiceProvider`
2. Verify model events are firing
3. Check transaction creation methods in `Transaction` model
4. Review observer logic for your specific case

**Last Updated**: November 16, 2025
