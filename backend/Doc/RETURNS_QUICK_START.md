# 🚀 Returns & Refunds - Quick Start Guide

## ⚡ 3-Minute Setup

Your return and refund system is **already ready to use!** No migrations needed.

---

## 🎯 What You Asked For

### ✅ Employee Control Over Refund Amount
> "Customer gets back the full cost if not intervened by the employee. If employee decides the refund amount, that is to be refunded."

**How it works:**
```http
# Employee approves return and decides refund amount
POST /api/returns/{id}/approve
{
  "total_refund_amount": 60000.00  // ← Employee can reduce this!
}
```

**Default:** Full price refund  
**Employee can:** Reduce for any reason (missing items, damage, etc.)

---

### ✅ Exchange System
> "I bought something $1000. Now I returned. I want a $750 thing. So now I get that and $250 refund."

**Implementation: Refund + New Sale**
```bash
# 1. Return $1000 item (full refund)
POST /api/returns → POST /api/refunds

# 2. Buy $750 item (new order)
POST /api/orders

# Result: Customer gets $250 back ✅
```

---

## 📖 Basic Workflows

### Simple Return

```bash
# 1. Create return
POST /api/returns
{
  "order_id": 123,
  "return_reason": "Defective product",
  "return_type": "defective",
  "items": [
    {"order_item_id": 1, "quantity": 1}
  ]
}

# 2. Quality check
PATCH /api/returns/1
{"quality_check_passed": true}

# 3. Approve (employee decides amount)
POST /api/returns/1/approve
{"total_refund_amount": 75000.00}

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

# 7. Complete refund
POST /api/refunds/1/process
POST /api/refunds/1/complete

# DONE! Customer gets money ✅
```

---

### Exchange

```bash
# PART 1: Return old item ($1000)
POST /api/returns {...}
POST /api/returns/1/approve {"total_refund_amount": 1000.00}
POST /api/returns/1/process
POST /api/returns/1/complete

POST /api/refunds {"return_id": 1, "refund_method": "cash"}
POST /api/refunds/1/process
POST /api/refunds/1/complete

# Customer now has $1000 in hand

# PART 2: New order ($750)
POST /api/orders
{
  "items": [{"product_id": 8, "unit_price": 750.00}],
  "payment": {"amount": 750.00}
}

# Customer pays $750, keeps $250 ✅
```

---

## 📋 All Endpoints

### Returns
```
GET    /api/returns                    # List all
POST   /api/returns                    # Create
GET    /api/returns/{id}               # Get one
PATCH  /api/returns/{id}               # Update (quality check)
POST   /api/returns/{id}/approve       # Approve
POST   /api/returns/{id}/reject        # Reject
POST   /api/returns/{id}/process       # Process (restore inventory)
POST   /api/returns/{id}/complete      # Complete
GET    /api/returns/statistics         # Stats
```

### Refunds
```
GET    /api/refunds                    # List all
POST   /api/refunds                    # Create
GET    /api/refunds/{id}               # Get one
POST   /api/refunds/{id}/process       # Start processing
POST   /api/refunds/{id}/complete      # Complete (money transferred)
POST   /api/refunds/{id}/fail          # Mark failed
POST   /api/refunds/{id}/cancel        # Cancel
GET    /api/refunds/statistics         # Stats
```

---

## 💡 Common Scenarios

### Full Refund
```json
POST /api/returns/1/approve
{
  "total_refund_amount": 75000.00,  // Original price
  "internal_notes": "Full refund - defective product"
}
```

### Partial Refund (80%)
```json
POST /api/returns/1/approve
{
  "total_refund_amount": 60000.00,  // 80% of 75000
  "processing_fee": 500.00,
  "internal_notes": "Missing accessories"
}
```

### Store Credit
```json
POST /api/refunds
{
  "return_id": 1,
  "refund_type": "full",
  "refund_method": "store_credit"
}
// Returns: {"store_credit_code": "SC-A1B2C3D4"}
```

### Reject Return
```json
POST /api/returns/1/reject
{
  "rejection_reason": "Product damaged by customer misuse"
}
```

---

## 📊 Key Features

✅ **Employee Refund Control** - Adjust amount at approval  
✅ **Quality Check** - Inspect before approval  
✅ **Multiple Refund Methods** - Cash, bank, store credit, etc.  
✅ **Inventory Restoration** - Auto-restore on process  
✅ **Exchange Support** - Refund + new sale  
✅ **Statistics** - Track returns/refunds  
✅ **Audit Trail** - Full status history  

---

## 📚 Documentation

1. **RETURNS_REFUNDS_EXCHANGE_SYSTEM.md** - Complete guide (50+ pages)
2. **RETURNS_QUICK_REFERENCE.md** - Employee cheat sheet
3. **RETURNS_IMPLEMENTATION_SUMMARY.md** - Technical details
4. **RETURNS_QUICK_START.md** - This file

---

## 🎓 Example: $1000 → $750 Exchange

**Step-by-step:**

```bash
# Customer: "I want to exchange my $1000 laptop for a $750 mouse"

# Employee: Process return
curl -X POST /api/returns \
  -d '{"order_id": 100, "items": [{"order_item_id": 5, "quantity": 1}]}'
# Returns: return_id = 1

curl -X POST /api/returns/1/approve \
  -d '{"total_refund_amount": 1000.00}'

curl -X POST /api/returns/1/process
curl -X POST /api/returns/1/complete

# Employee: Create refund
curl -X POST /api/refunds \
  -d '{"return_id": 1, "refund_method": "cash"}'
# Returns: refund_id = 1

curl -X POST /api/refunds/1/process
curl -X POST /api/refunds/1/complete

# Employee: "Here's your $1000 back"
# Customer: Now has $1000 cash

# Employee: Create new order
curl -X POST /api/orders \
  -d '{
    "items": [{"product_id": 8, "unit_price": 750.00}],
    "payment": {"amount": 750.00}
  }'

# Employee: "That'll be $750"
# Customer: Pays $750, keeps $250

# NET RESULT: Customer exchanged items and got $250 back ✅
```

---

## ⚠️ Important Notes

1. **Models already exist** - No migration needed
2. **Authentication required** - Use JWT token
3. **Employee guard** - Some endpoints need `auth:employee`
4. **Inventory auto-restores** - On return processing
5. **Exchange = 2 transactions** - Return + New order

---

## 🎯 Test It Now!

```bash
# 1. Create a test return
POST /api/returns
{
  "order_id": 1,
  "return_reason": "Test return",
  "return_type": "other",
  "items": [{"order_item_id": 1, "quantity": 1}]
}

# 2. Check it was created
GET /api/returns

# 3. You're ready! 🎉
```

---

**System is live and ready to handle returns, refunds, and exchanges!** ✅

**Need help?** Check the detailed docs in:
- `RETURNS_REFUNDS_EXCHANGE_SYSTEM.md` for complete workflows
- `RETURNS_QUICK_REFERENCE.md` for quick commands
