# Transaction Type Guide for Frontend Developers

## ⚠️ CRITICAL: Understanding Debit vs Credit

### The Golden Rule
- **DEBIT** = Money **COMING IN** to the business (Revenue, Income) 💰➡️
- **CREDIT** = Money **GOING OUT** from the business (Expenses, Payments) 💰⬅️

---

## Transaction Types Reference

### 🟢 DEBIT Transactions (Money IN)

#### When to use `"type": "debit"`:
1. **Customer pays for products** → OrderPayment
2. **Customer pays for services** → ServiceOrderPayment  
3. **Cash sales at counter**
4. **Online order payments**
5. **Any money received from customers**

**Example:**
```json
POST /api/transactions
{
  "transaction_date": "2025-11-17",
  "amount": 5000,
  "type": "debit",  // ✅ Money coming IN
  "account_id": 1,
  "description": "Counter sale payment",
  "reference_type": "OrderPayment",
  "reference_id": 123,
  "status": "completed"
}
```

---

### 🔴 CREDIT Transactions (Money OUT)

#### When to use `"type": "credit"`:
1. **Paying business expenses** → ExpensePayment
   - Rent payment
   - Utilities
   - Office supplies
   - Salaries
2. **Paying vendors/suppliers** → VendorPayment
3. **Refunding customers** → Refund
4. **Any money leaving the business**

**Example:**
```json
POST /api/transactions
{
  "transaction_date": "2025-11-17",
  "amount": 10000,
  "type": "credit",  // ✅ Money going OUT
  "account_id": 1,
  "description": "Office rent - November 2025",
  "reference_type": "manual",
  "metadata": {
    "category": "Office Rent",
    "receiptImage": "data:image/jpeg;base64,..."
  },
  "status": "completed"
}
```

---

## Common Mistakes ❌ and Fixes ✅

### Mistake #1: Using Debit for Expenses
```json
❌ WRONG:
{
  "type": "debit",  // Wrong! Expenses are money OUT
  "description": "Paid electricity bill",
  "amount": 5000
}

✅ CORRECT:
{
  "type": "credit",  // Money going out
  "description": "Paid electricity bill",
  "amount": 5000
}
```

### Mistake #2: Using Credit for Customer Payments
```json
❌ WRONG:
{
  "type": "credit",  // Wrong! Customer payments are money IN
  "description": "Customer order payment",
  "amount": 10000,
  "reference_type": "OrderPayment"
}

✅ CORRECT:
{
  "type": "debit",  // Money coming in
  "description": "Customer order payment",
  "amount": 10000,
  "reference_type": "OrderPayment"
}
```

---

## Auto-Detection & Validation

### Backend Validation (Added)
The backend now:
1. ✅ **Validates** that `type` is either `"debit"` or `"credit"`
2. ✅ **Logs warnings** if type doesn't match reference_type
3. ✅ **Accepts** the type you send (so you must send the correct one!)

### Reference Type Hints
The backend expects these types for each reference:

| Reference Type | Expected Type | Reason |
|---------------|---------------|---------|
| `OrderPayment` | `debit` | Customer paying us |
| `ServiceOrderPayment` | `debit` | Customer paying us |
| `CustomerPayment` | `debit` | Customer paying us |
| `Expense` | `credit` | Business spending money |
| `ExpensePayment` | `credit` | Business spending money |
| `VendorPayment` | `credit` | Paying suppliers |
| `Refund` | `credit` | Returning money to customer |
| `manual` | `credit` (usually) | Manual entries are usually expenses |

---

## API Endpoint Reference

### Create Transaction
```http
POST /api/transactions
Authorization: Bearer {token}
Content-Type: application/json
```

**Required Fields:**
```json
{
  "transaction_date": "2025-11-17",  // Required: Date of transaction
  "amount": 10000,                   // Required: Positive number
  "type": "debit OR credit",         // Required: See guide above
  "account_id": 1,                   // Required: Account ID
  "description": "Description"       // Optional: What this transaction is for
}
```

**Optional Fields:**
```json
{
  "store_id": 1,                     // Optional: Which store
  "reference_type": "manual",        // Optional: Type of reference
  "reference_id": 123,               // Optional: ID of related record
  "metadata": {                      // Optional: Extra data
    "category": "Office Supplies",
    "receiptImage": "base64...",
    "notes": "Additional info"
  },
  "status": "completed"              // Optional: pending/completed/failed/cancelled
}
```

---

## Real-World Examples

### Example 1: Counter Sale (Customer Payment)
```json
POST /api/transactions
{
  "transaction_date": "2025-11-17",
  "amount": 15000,
  "type": "debit",  // ✅ Money IN from customer
  "account_id": 1,
  "description": "Counter sale - Invoice #INV-12345",
  "store_id": 1,
  "reference_type": "OrderPayment",
  "reference_id": 456,
  "status": "completed"
}
```

### Example 2: Rent Payment (Business Expense)
```json
POST /api/transactions
{
  "transaction_date": "2025-11-01",
  "amount": 50000,
  "type": "credit",  // ✅ Money OUT for expense
  "account_id": 1,
  "description": "Office rent - November 2025",
  "reference_type": "manual",
  "metadata": {
    "category": "Office Rent",
    "landlord": "ABC Properties",
    "receiptImage": "data:image/jpeg;base64,..."
  },
  "status": "completed"
}
```

### Example 3: Utility Bill Payment
```json
POST /api/transactions
{
  "transaction_date": "2025-11-17",
  "amount": 8500,
  "type": "credit",  // ✅ Money OUT for utility
  "account_id": 1,
  "description": "Electricity bill - October 2025",
  "reference_type": "manual",
  "metadata": {
    "category": "Utilities",
    "provider": "DESCO",
    "billMonth": "October 2025"
  },
  "status": "completed"
}
```

### Example 4: Vendor Payment (Supplier Payment)
```json
POST /api/transactions
{
  "transaction_date": "2025-11-17",
  "amount": 250000,
  "type": "credit",  // ✅ Money OUT to vendor
  "account_id": 1,
  "description": "Payment to Fashion Wholesale Ltd",
  "reference_type": "VendorPayment",
  "reference_id": 789,
  "status": "completed"
}
```

### Example 5: Customer Refund
```json
POST /api/transactions
{
  "transaction_date": "2025-11-17",
  "amount": 3000,
  "type": "credit",  // ✅ Money OUT to customer
  "account_id": 1,
  "description": "Refund for Order #ORD-98765",
  "reference_type": "Refund",
  "reference_id": 321,
  "status": "completed"
}
```

---

## Quick Decision Tree

```
Is money COMING IN to the business?
├─ YES → type: "debit"
│   └─ Examples: Customer payments, sales, received refunds
│
└─ NO (money GOING OUT)
    └─ type: "credit"
        └─ Examples: Expenses, vendor payments, customer refunds
```

---

## Account Balance Calculation

### How Debit/Credit Affects Balance
```javascript
// Starting balance: 100,000

// DEBIT (Money IN) = +10,000
New balance = 100,000 + 10,000 = 110,000

// CREDIT (Money OUT) = -5,000
New balance = 110,000 - 5,000 = 105,000
```

### In Code:
```javascript
if (transaction.type === 'debit') {
  balance += transaction.amount;  // Add money
} else if (transaction.type === 'credit') {
  balance -= transaction.amount;  // Subtract money
}
```

---

## Validation Responses

### Success Response
```json
{
  "success": true,
  "message": "Transaction created successfully",
  "data": {
    "id": 123,
    "transaction_number": "TXN-20251117-ABC123",
    "transaction_date": "2025-11-17",
    "amount": "10000.00",
    "type": "credit",
    "description": "Office rent payment",
    "status": "completed",
    "account": { ... },
    "created_at": "2025-11-17T10:30:00.000000Z"
  }
}
```

### Error Response (Wrong Type)
```json
{
  "success": false,
  "errors": {
    "type": ["The selected type is invalid."]
  }
}
```

**Note:** The backend will also log a warning if you use the wrong type for a reference_type. Check the Laravel logs!

---

## Statistics & Reporting

### Get Transaction Statistics
```http
GET /api/transactions/statistics?date_from=2025-11-01&date_to=2025-11-30
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 150,
    "completed": 140,
    "pending": 8,
    "failed": 2,
    "total_debits": 500000.00,      // Total money IN
    "total_credits": 200000.00,     // Total money OUT
    "completed_debits": 480000.00,  // Completed money IN
    "completed_credits": 190000.00, // Completed money OUT
    "net_balance": 290000.00,       // IN - OUT = Profit/Loss
    "by_type": {
      "debit": 85,    // 85 income transactions
      "credit": 65    // 65 expense transactions
    }
  }
}
```

---

## Filter by Type

### Get Only Debit Transactions (Money IN)
```http
GET /api/transactions?type=debit
```

### Get Only Credit Transactions (Money OUT)
```http
GET /api/transactions?type=credit
```

---

## Best Practices

### ✅ DO:
1. Always set `type` based on money flow direction
2. Use `"debit"` for customer payments
3. Use `"credit"` for business expenses
4. Add descriptive `description` fields
5. Include `metadata` for additional context
6. Set `status: "completed"` for immediate transactions

### ❌ DON'T:
1. Don't hardcode `type: "debit"` everywhere
2. Don't confuse debit with debt (different concepts!)
3. Don't use credit for incoming money
4. Don't forget to set `transaction_date`
5. Don't skip validation errors

---

## Testing Checklist

### Before Deploying
- [ ] Test creating debit transaction (customer payment)
- [ ] Test creating credit transaction (expense payment)
- [ ] Verify balance calculations are correct
- [ ] Check statistics show both debit and credit counts
- [ ] Confirm filters work (type=debit, type=credit)
- [ ] Test with different reference_types

---

## Support

If you're still confused:
1. Check the TRANSACTION_SYSTEM_DOCS.md for full technical details
2. Review the examples in this document
3. Remember: **Money IN = Debit, Money OUT = Credit**

**Last Updated:** November 17, 2025
