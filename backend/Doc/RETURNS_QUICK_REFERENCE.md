# Returns & Refunds - Quick Reference

## 🎯 Key Concept

**Return**: Physical product coming back  
**Refund**: Money going back to customer  
**Exchange**: Return (full refund) + New Sale

---

## 📋 Employee Workflow

### Daily Operations

```bash
# 1. View pending returns
GET /api/returns?status=pending

# 2. Check a specific return
GET /api/returns/1

# 3. Approve/Reject with custom refund amount
POST /api/returns/1/approve
{
  "total_refund_amount": 60000.00,  # Employee decides!
  "processing_fee": 500.00,
  "internal_notes": "Missing accessories"
}

# 4. Process (restore inventory)
POST /api/returns/1/process

# 5. Complete
POST /api/returns/1/complete

# 6. Create refund
POST /api/refunds
{
  "return_id": 1,
  "refund_type": "full",
  "refund_method": "cash"
}

# 7. Complete refund (give money)
POST /api/refunds/1/process
POST /api/refunds/1/complete
```

---

## 💡 Employee Powers

### 1. Adjust Refund Amount
```json
{
  "total_refund_amount": 60000.00  // ← Employee decides
}
```
- Full refund: Use original price
- Partial: Reduce for missing items, damage, etc.
- Zero: Reject instead

### 2. Set Processing Fee
```json
{
  "processing_fee": 500.00  // ← Restocking fee
}
```

### 3. Quality Check Decision
```json
{
  "quality_check_passed": false,  // ← Reject later
  "quality_check_notes": "Water damage detected"
}
```

---

## 🔄 Exchange Process

**Customer wants to exchange ৳1,000 item for ৳750 item:**

### Method: Full Refund + New Sale

```bash
# STEP 1: Process return for ৳1,000 item
POST /api/returns {...}
POST /api/returns/1/approve {"total_refund_amount": 1000.00}
POST /api/returns/1/process
POST /api/returns/1/complete

# STEP 2: Create full refund
POST /api/refunds {"return_id": 1, "refund_type": "full", "refund_method": "cash"}
POST /api/refunds/1/process
POST /api/refunds/1/complete  # ← Customer has ৳1,000

# STEP 3: Create new order for ৳750 item
POST /api/orders {
  "items": [{"product_id": 8, "unit_price": 750.00}],
  "payment": {"amount": 750.00}
}
POST /api/orders/150/complete  # ← Customer pays ৳750

# RESULT: Customer gets ৳1,000 - ৳750 = ৳250 back ✅
```

---

## 📊 Common Scenarios

### Full Refund (Defective Product)
```http
POST /api/returns/1/approve
{
  "total_refund_amount": 75000.00,  // Original price
  "processing_fee": 0,
  "internal_notes": "Manufacturing defect - full refund"
}
```

### Partial Refund (Missing Accessories)
```http
POST /api/returns/2/approve
{
  "total_refund_amount": 60000.00,  // 80% of original
  "processing_fee": 500.00,
  "internal_notes": "Missing charger and box - 20% deduction"
}
```

### Reject (Customer Misuse)
```http
POST /api/returns/3/reject
{
  "rejection_reason": "Product damaged by water - not covered"
}
```

### Store Credit
```http
POST /api/refunds
{
  "return_id": 4,
  "refund_type": "full",
  "refund_method": "store_credit"
}
# Returns: store_credit_code = "SC-A1B2C3D4"
```

---

## 🔍 Search & Filter

```bash
# Pending returns
GET /api/returns?status=pending

# Returns by store
GET /api/returns?store_id=2

# Returns by customer
GET /api/returns?customer_id=10

# Date range
GET /api/returns?from_date=2025-11-01&to_date=2025-11-30

# Search return number
GET /api/returns?search=RET-001

# Statistics
GET /api/returns/statistics
GET /api/refunds/statistics
```

---

## ⚡ Quick Actions

### Approve & Process in One Go
```bash
# Receive return
PATCH /api/returns/1
{"quality_check_passed": true}

# Approve
POST /api/returns/1/approve
{"total_refund_amount": 75000.00}

# Process
POST /api/returns/1/process

# Complete
POST /api/returns/1/complete

# Create & complete refund
POST /api/refunds
{"return_id": 1, "refund_type": "full", "refund_method": "cash"}

POST /api/refunds/1/process
POST /api/refunds/1/complete
{"transaction_reference": "CASH-001"}

# DONE! ✅
```

---

## 📝 Status Cheat Sheet

### Return Statuses
- `pending` → Need to approve/reject
- `approved` → Ready to process
- `rejected` → No refund
- `processed` → Inventory restored
- `completed` → Ready for refund
- `refunded` → Money returned

### Refund Statuses
- `pending` → Created, not started
- `processing` → Money transfer started
- `completed` → Money transferred ✅
- `failed` → Transfer failed ❌
- `cancelled` → Refund cancelled

---

## 💰 Refund Methods

| Method | Use Case |
|--------|----------|
| `cash` | Counter refund |
| `bank_transfer` | Bank account |
| `card_refund` | Card reversal |
| `store_credit` | Future purchase |
| `digital_wallet` | bKash/Nagad |

---

## ⚠️ Important Rules

1. **Employee Decides Refund Amount**
   - Can reduce from full price
   - Must be ≤ original price
   - Set at approval time

2. **Quality Check Required**
   - Must pass before approval
   - Employee inspects product

3. **Exchange = 2 Transactions**
   - Return old (refund ৳1000)
   - Buy new (pay ৳750)
   - Net: ৳250 refund

4. **Inventory Auto-Restored**
   - When processing return
   - Goes back to original batch
   - ProductMovement created

---

## 🎓 Example Walkthrough

**Scenario:** Customer returns ৳50,000 laptop (missing charger). Give ৳45,000 refund.

```bash
# Create return
POST /api/returns
{
  "order_id": 100,
  "return_reason": "Product incomplete",
  "return_type": "other",
  "items": [{"order_item_id": 5, "quantity": 1}],
  "customer_notes": "Charger missing"
}
# Returns: {"id": 10, "total_refund_amount": "50000.00"}

# Quality check
PATCH /api/returns/10
{
  "quality_check_passed": true,
  "quality_check_notes": "Laptop works fine, charger indeed missing"
}

# Approve with reduced amount
POST /api/returns/10/approve
{
  "total_refund_amount": 45000.00,  # ← Reduced from 50000
  "processing_fee": 0,
  "internal_notes": "Charger costs ৳5000, deducted from refund"
}

# Process
POST /api/returns/10/process
{"restore_inventory": true}

# Complete
POST /api/returns/10/complete

# Create cash refund
POST /api/refunds
{
  "return_id": 10,
  "refund_type": "full",  # Full of the approved amount (৳45000)
  "refund_method": "cash"
}
# Returns: {"id": 8, "refund_amount": "45000.00"}

# Process refund
POST /api/refunds/8/process

# Complete refund (give ৳45,000 cash)
POST /api/refunds/8/complete
{"transaction_reference": "CASH-20251104-001"}

# DONE! Customer gets ৳45,000 (not ৳50,000) ✅
```

---

**System ready! Returns, refunds, and exchanges fully operational.** 🎉
