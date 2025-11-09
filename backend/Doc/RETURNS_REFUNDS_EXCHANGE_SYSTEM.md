# Product Returns, Refunds & Exchange System

## Overview

Complete system for handling product returns, processing refunds, and managing exchanges.

**Key Features:**
- ✅ Employee can adjust refund amount (partial/full)
- ✅ Quality check workflow
- ✅ Multi-method refunds (cash, bank, store credit, etc.)
- ✅ Exchange = Refund + New Sale
- ✅ Inventory restoration
- ✅ Transaction tracking

---

## Workflow Diagram

```
CUSTOMER RETURN REQUEST
         ↓
┌────────────────────┐
│  Create Return     │  ← Customer/Employee initiates
│  Status: pending   │
└────────────────────┘
         ↓
┌────────────────────┐
│ Quality Check      │  ← Employee receives & inspects
│ Pass/Fail          │
└────────────────────┘
         ↓
┌────────────────────┐
│ Approve/Reject     │  ← Employee decides refund amount
│ Set Refund Amount  │    (can reduce from full price!)
└────────────────────┘
         ↓ (if approved)
┌────────────────────┐
│ Process Return     │  ← Restore inventory
│ Status: processed  │
└────────────────────┘
         ↓
┌────────────────────┐
│ Complete Return    │  ← Ready for refund
│ Status: completed  │
└────────────────────┘
         ↓
┌────────────────────┐
│ Create Refund      │  ← Choose refund method
│ Cash/Bank/Credit   │
└────────────────────┘
         ↓
┌────────────────────┐
│ Process & Complete │  ← Money transferred
│ Status: refunded   │
└────────────────────┘
         ↓
      DONE ✅
```

---

## Scenario 1: Simple Return with Full Refund

**Customer returns defective product, gets full refund**

### Step 1: Create Return Request

```http
POST /api/returns
Content-Type: application/json
Authorization: Bearer {token}

{
  "order_id": 123,
  "return_reason": "Product is defective",
  "return_type": "defective",
  "items": [
    {
      "order_item_id": 1,
      "quantity": 1,
      "reason": "Screen not working"
    }
  ],
  "customer_notes": "The phone screen stopped working after 2 days",
  "attachments": [
    "https://example.com/photos/defective-screen.jpg"
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Return created successfully",
  "data": {
    "id": 1,
    "return_number": "RET-20251104-0001",
    "order_id": 123,
    "customer_id": 10,
    "store_id": 2,
    "return_reason": "Product is defective",
    "return_type": "defective",
    "status": "pending",
    "total_return_value": "75000.00",
    "total_refund_amount": "75000.00",
    "processing_fee": "0.00",
    "return_items": [
      {
        "order_item_id": 1,
        "product_id": 5,
        "product_name": "iPhone 14 Pro",
        "quantity": 1,
        "unit_price": "75000.00",
        "total_price": "75000.00",
        "reason": "Screen not working"
      }
    ]
  }
}
```

### Step 2: Receive & Quality Check

```http
PATCH /api/returns/1
Content-Type: application/json

{
  "quality_check_passed": true,
  "quality_check_notes": "Confirmed - screen is indeed defective. Hardware issue.",
  "internal_notes": "Valid warranty claim"
}
```

### Step 3: Approve Return (Employee Keeps Full Refund)

```http
POST /api/returns/1/approve
Content-Type: application/json

{
  "total_refund_amount": 75000.00,
  "processing_fee": 0,
  "internal_notes": "Approved for full refund - manufacturing defect"
}
```

### Step 4: Process Return (Restore Inventory)

```http
POST /api/returns/1/process
Content-Type: application/json

{
  "restore_inventory": true
}
```

**Result:** Product added back to store inventory (batch updated)

### Step 5: Complete Return

```http
POST /api/returns/1/complete
```

### Step 6: Create Refund

```http
POST /api/refunds
Content-Type: application/json

{
  "return_id": 1,
  "refund_type": "full",
  "refund_method": "bank_transfer",
  "payment_reference": "Customer bank account: 1234567890",
  "refund_method_details": {
    "bank_name": "Dutch Bangla Bank",
    "account_number": "1234567890",
    "account_name": "Karim Ahmed"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Refund created successfully",
  "data": {
    "id": 1,
    "refund_number": "REF-20251104-0001",
    "return_id": 1,
    "order_id": 123,
    "customer_id": 10,
    "refund_type": "full",
    "original_amount": "75000.00",
    "refund_amount": "75000.00",
    "processing_fee": "0.00",
    "refund_method": "bank_transfer",
    "status": "pending"
  }
}
```

### Step 7: Process Refund

```http
POST /api/refunds/1/process
```

### Step 8: Complete Refund (Money Transferred)

```http
POST /api/refunds/1/complete
Content-Type: application/json

{
  "transaction_reference": "TXN-BANK-20251104-001",
  "bank_reference": "DBBL-20251104-123456"
}
```

**Final Result:** ✅ Customer gets ৳75,000 back in bank account

---

## Scenario 2: Partial Refund (Employee Reduces Amount)

**Customer returns item after 10 days without box. Employee gives 80% refund**

### Step 1-2: Create Return & Quality Check (same as above)

### Step 3: Approve with Reduced Refund Amount

```http
POST /api/returns/2/approve
Content-Type: application/json

{
  "total_refund_amount": 60000.00,
  "processing_fee": 500.00,
  "internal_notes": "Missing original box and accessories. Reduced to 80% of original price."
}
```

**Key:** Employee changed refund from ৳75,000 to ৳60,000

### Step 4-8: Process normally

**Final Result:** ✅ Customer gets ৳60,000 (not full ৳75,000)

---

## Scenario 3: Exchange (Return + Buy New Product)

**Customer bought ৳1,000 item, wants to exchange for ৳750 item. Gets ৳250 refund.**

### Workflow: Refund Full Amount + Create New Order

#### Part A: Process Return & Refund

```http
# Step 1: Create return
POST /api/returns
{
  "order_id": 100,
  "return_reason": "Customer wants different model",
  "return_type": "unwanted",
  "items": [{"order_item_id": 5, "quantity": 1}]
}

# Steps 2-5: Approve, process, complete (get return ID: 5)

# Step 6: Create FULL refund
POST /api/refunds
{
  "return_id": 5,
  "refund_type": "full",
  "refund_method": "cash",
  "internal_notes": "Full refund for exchange"
}

# Steps 7-8: Process & complete refund
POST /api/refunds/5/process
POST /api/refunds/5/complete
{
  "transaction_reference": "EXCHANGE-20251104-001"
}
```

**Result:** Customer has ৳1,000 refunded (in hand)

#### Part B: Create New Order for Exchange Item

```http
POST /api/orders
Content-Type: application/json

{
  "order_type": "counter",
  "store_id": 2,
  "customer_id": 10,
  "items": [
    {
      "product_id": 8,
      "batch_id": 12,
      "quantity": 1,
      "unit_price": 750.00
    }
  ],
  "payment": {
    "payment_method_id": 1,
    "amount": 750.00,
    "payment_type": "full"
  },
  "notes": "Exchange order - Original order #100 returned"
}

# Complete the new order
PATCH /api/orders/150/complete
```

**Final Result:**
- ✅ Original ৳1,000 item returned
- ✅ New ৳750 item purchased
- ✅ Customer gets ৳250 cash back
- ✅ System treats it as: Refund ৳1,000 + Sale ৳750

---

## Scenario 4: Store Credit Refund

**Customer wants store credit instead of cash**

```http
# Create refund with store_credit method
POST /api/refunds
{
  "return_id": 3,
  "refund_type": "full",
  "refund_method": "store_credit",
  "customer_notes": "Customer prefers store credit for future purchase"
}
```

**Response includes:**
```json
{
  "refund_method": "store_credit",
  "store_credit_code": "SC-A1B2C3D4",
  "store_credit_expires_at": "2026-11-04T12:00:00.000000Z",
  "refund_amount": "75000.00"
}
```

**Usage:** Customer can use code `SC-A1B2C3D4` for ৳75,000 off next purchase (valid 1 year)

---

## Scenario 5: Rejected Return

**Customer return rejected due to misuse**

```http
# Step 1-2: Create return & quality check
POST /api/returns
PATCH /api/returns/4
{
  "quality_check_passed": false,
  "quality_check_notes": "Product shows signs of water damage - not covered by warranty"
}

# Step 3: Reject return
POST /api/returns/4/reject
{
  "rejection_reason": "Product damaged by water - not a manufacturing defect. Warranty void."
}
```

**Result:** ❌ Return rejected, no refund issued, product returned to customer

---

## API Endpoints Reference

### Product Returns

```http
# List all returns
GET /api/returns
GET /api/returns?status=pending
GET /api/returns?store_id=2
GET /api/returns?customer_id=10
GET /api/returns?from_date=2025-11-01&to_date=2025-11-30
GET /api/returns?search=RET-001

# Get specific return
GET /api/returns/{id}

# Create return
POST /api/returns

# Update return (quality check)
PATCH /api/returns/{id}

# Approve return
POST /api/returns/{id}/approve

# Reject return
POST /api/returns/{id}/reject

# Process return (restore inventory)
POST /api/returns/{id}/process

# Complete return
POST /api/returns/{id}/complete

# Get statistics
GET /api/returns/statistics
GET /api/returns/statistics?from_date=2025-11-01&to_date=2025-11-30
```

### Refunds

```http
# List all refunds
GET /api/refunds
GET /api/refunds?status=completed
GET /api/refunds?refund_method=cash
GET /api/refunds?customer_id=10

# Get specific refund
GET /api/refunds/{id}

# Create refund
POST /api/refunds

# Process refund
POST /api/refunds/{id}/process

# Complete refund
POST /api/refunds/{id}/complete

# Fail refund
POST /api/refunds/{id}/fail

# Cancel refund
POST /api/refunds/{id}/cancel

# Get statistics
GET /api/refunds/statistics
```

---

## Return Status Flow

```
pending → approved → processed → completed → refunded
   ↓
rejected (end)
```

**Status Definitions:**
- `pending`: Waiting for approval
- `approved`: Approved by employee
- `rejected`: Rejected by employee (no refund)
- `processed`: Inventory restored
- `completed`: Ready for refund
- `refunded`: Refund completed

---

## Refund Status Flow

```
pending → processing → completed
   ↓           ↓
cancelled    failed
```

**Status Definitions:**
- `pending`: Created, waiting to process
- `processing`: Being processed (money transfer initiated)
- `completed`: Money successfully transferred
- `failed`: Transfer failed
- `cancelled`: Refund cancelled

---

## Refund Methods

| Method | Description | Example |
|--------|-------------|---------|
| `cash` | Cash refund at counter | Hand customer cash |
| `bank_transfer` | Bank account transfer | DBBL/bKash transfer |
| `card_refund` | Refund to card | Reverse card charge |
| `store_credit` | Store credit code | Future purchase discount |
| `gift_card` | Gift card | Physical/digital gift card |
| `digital_wallet` | bKash/Nagad/Rocket | Mobile wallet |
| `check` | Bank check | Issue check |
| `other` | Other method | Custom arrangement |

---

## Return Types

- `defective`: Manufacturing defect
- `damaged`: Damaged during shipping
- `wrong_item`: Wrong product sent
- `unwanted`: Customer changed mind
- `other`: Other reason

---

## Important Business Rules

### Employee Refund Adjustment
```http
# Employee can reduce refund amount at approval
POST /api/returns/1/approve
{
  "total_refund_amount": 60000.00,  // Original was 75000
  "processing_fee": 500.00,
  "internal_notes": "Missing accessories - 20% reduction"
}
```

✅ **Default:** `total_refund_amount` = `total_return_value` (full refund)  
✅ **Employee can change:** Set to any amount ≤ `total_return_value`  
✅ **Use cases:** Missing items, damaged packaging, restocking fee, etc.

### Partial Returns
```http
# Customer bought 5 items, returns 2
{
  "order_id": 100,
  "items": [
    {"order_item_id": 1, "quantity": 2}  // Only 2 out of 5
  ]
}
```

✅ Validates against available quantity (ordered - already returned)

### Inventory Restoration
```http
POST /api/returns/1/process
{
  "restore_inventory": true  // Default: true
}
```

✅ Adds quantity back to original batch  
✅ Creates ProductMovement record for tracking  
✅ Updates MasterInventory

### Exchange Workflow
```
1. Process full return (৳1000)
2. Create full refund (৳1000)
3. Create new order (৳750)
4. Customer gets difference (৳250)
```

✅ Treated as separate transactions  
✅ Clean audit trail  
✅ Proper inventory management

---

## Statistics Endpoints

### Return Statistics
```http
GET /api/returns/statistics?from_date=2025-11-01&to_date=2025-11-30

Response:
{
  "total_returns": 45,
  "pending": 5,
  "approved": 3,
  "rejected": 2,
  "processed": 10,
  "completed": 15,
  "refunded": 10,
  "total_return_value": "500000.00",
  "total_refund_amount": "450000.00",
  "total_processing_fees": "5000.00",
  "by_reason": [
    {"return_reason": "defective", "count": 20},
    {"return_reason": "unwanted", "count": 15},
    ...
  ]
}
```

### Refund Statistics
```http
GET /api/refunds/statistics?from_date=2025-11-01

Response:
{
  "total_refunds": 40,
  "pending": 5,
  "processing": 2,
  "completed": 30,
  "failed": 2,
  "cancelled": 1,
  "total_refund_amount": "450000.00",
  "total_processing_fees": "5000.00",
  "by_method": [
    {"refund_method": "cash", "count": 15, "total": "200000.00"},
    {"refund_method": "bank_transfer", "count": 10, "total": "150000.00"},
    {"refund_method": "store_credit", "count": 5, "total": "100000.00"}
  ]
}
```

---

## Complete Exchange Example

**Scenario:** Customer bought laptop for ৳85,000. Wants to exchange for mouse (৳1,500). Gets ৳83,500 back.

```bash
# PART 1: RETURN & REFUND LAPTOP
# ----------------------------------

# 1. Create return
curl -X POST http://api.example.com/api/returns \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 200,
    "return_reason": "Customer wants to exchange",
    "return_type": "unwanted",
    "items": [{"order_item_id": 10, "quantity": 1}]
  }'
# Returns: return_id = 10

# 2. Quality check
curl -X PATCH http://api.example.com/api/returns/10 \
  -d '{"quality_check_passed": true}'

# 3. Approve full refund
curl -X POST http://api.example.com/api/returns/10/approve \
  -d '{"total_refund_amount": 85000.00}'

# 4. Process (restore laptop inventory)
curl -X POST http://api.example.com/api/returns/10/process

# 5. Complete
curl -X POST http://api.example.com/api/returns/10/complete

# 6. Create refund
curl -X POST http://api.example.com/api/refunds \
  -d '{
    "return_id": 10,
    "refund_type": "full",
    "refund_method": "cash"
  }'
# Returns: refund_id = 8

# 7. Process refund
curl -X POST http://api.example.com/api/refunds/8/process

# 8. Complete refund (give ৳85,000 cash)
curl -X POST http://api.example.com/api/refunds/8/complete

# PART 2: NEW SALE (MOUSE)
# --------------------------

# 9. Create new order
curl -X POST http://api.example.com/api/orders \
  -d '{
    "order_type": "counter",
    "store_id": 2,
    "customer_id": 15,
    "items": [{"product_id": 50, "batch_id": 20, "quantity": 1, "unit_price": 1500.00}],
    "payment": {"payment_method_id": 1, "amount": 1500.00, "payment_type": "full"},
    "notes": "Exchange - original order #200"
  }'
# Returns: order_id = 205

# 10. Complete order
curl -X PATCH http://api.example.com/api/orders/205/complete

# RESULT:
# ✅ Laptop returned (৳85,000 refunded)
# ✅ Mouse purchased (৳1,500 paid)
# ✅ Net refund to customer: ৳83,500 cash
```

---

## Summary

### ✅ What's Implemented:

1. **Full Return Workflow**
   - Create → Quality Check → Approve/Reject → Process → Complete
   
2. **Employee Refund Control**
   - Can reduce refund amount from full price
   - Set processing fees
   - Add internal notes

3. **Multi-Method Refunds**
   - Cash, bank transfer, card, store credit, etc.
   - Transaction tracking

4. **Exchange Support**
   - Return old product (full refund)
   - Create new order
   - Customer gets difference

5. **Inventory Management**
   - Auto-restore returned items
   - ProductMovement tracking

6. **Statistics & Reporting**
   - Return/refund analytics
   - By status, method, reason

---

**System Ready for Returns, Refunds & Exchanges!** 🔄💰
