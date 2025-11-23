# Store-to-Store Inventory Transfer - Barcode Scanning Guide

## Overview
এই গাইডে দেখানো হয়েছে **store-to-store inventory transfer** এর সময় কোথায় কোথায় **barcode scan** করতে হবে।

---

## Transfer Flow with Barcode Scanning

### Step 1: Create Inventory Rebalancing Request
**API:** `POST /api/inventory-rebalancing`

```json
{
  "product_id": 1,
  "quantity": 10,
  "source_store_id": 1,
  "destination_store_id": 2,
  "reason": "Stock rebalancing",
  "priority": "normal"
}
```

✅ **No barcode scanning needed** - শুধু request তৈরি হয়

---

### Step 2: Approve Rebalancing Request
**API:** `PATCH /api/inventory-rebalancing/{id}/approve`

```json
{
  "notes": "Approved for transfer"
}
```

✅ **No barcode scanning needed** - ProductDispatch automatically তৈরি হয়

---

### Step 3: Mark Dispatch as In Transit
**API:** `PATCH /api/dispatches/{id}/dispatch`

```json
{
  "dispatch_date": "2025-06-19",
  "expected_delivery_date": "2025-06-20",
  "transport_method": "company_vehicle",
  "driver_name": "John Doe",
  "driver_contact": "01712345678",
  "vehicle_number": "Dhaka Metro Ga 12-3456"
}
```

✅ **No barcode scanning needed** - Status "in_transit" এ যায়

---

### Step 4: 🎯 **SCAN BARCODES** (MOST IMPORTANT!)
এখানেই **individual barcode scan** করতে হবে!

**API:** `POST /api/dispatches/{dispatchId}/items/{itemId}/scan-barcode`

**Example:** যদি dispatch item এ 10 টা product থাকে, তাহলে **10 বার** scan করতে হবে

#### First Barcode Scan:
```json
POST /api/dispatches/1/items/5/scan-barcode
{
  "barcode": "8801234567890"
}

Response:
{
  "success": true,
  "message": "Barcode scanned successfully. 1 of 10 items scanned.",
  "data": {
    "barcode": "8801234567890",
    "scanned_count": 1,
    "required_quantity": 10,
    "remaining_count": 9,
    "all_scanned": false,
    "scanned_at": "2025-06-19T10:30:00Z",
    "scanned_by": "John Doe"
  }
}
```

#### Second Barcode Scan:
```json
POST /api/dispatches/1/items/5/scan-barcode
{
  "barcode": "8801234567891"
}

Response:
{
  "success": true,
  "message": "Barcode scanned successfully. 2 of 10 items scanned.",
  "data": {
    "scanned_count": 2,
    "required_quantity": 10,
    "remaining_count": 8,
    "all_scanned": false
  }
}
```

#### Continue until all 10 barcodes are scanned...

#### Last Barcode Scan:
```json
POST /api/dispatches/1/items/5/scan-barcode
{
  "barcode": "8801234567899"
}

Response:
{
  "success": true,
  "message": "Barcode scanned successfully. 10 of 10 items scanned.",
  "data": {
    "scanned_count": 10,
    "required_quantity": 10,
    "remaining_count": 0,
    "all_scanned": true ✅
  }
}
```

---

### Validation Rules During Barcode Scanning:

1. ❌ **Cannot scan if dispatch is not "in_transit"**
   ```json
   {
     "success": false,
     "message": "Barcodes can only be scanned when dispatch is in transit"
   }
   ```

2. ❌ **Barcode doesn't exist**
   ```json
   {
     "success": false,
     "message": "Barcode not found in system"
   }
   ```

3. ❌ **Wrong product**
   ```json
   {
     "success": false,
     "message": "Barcode does not match the product for this dispatch item"
   }
   ```

4. ❌ **Barcode not at source store**
   ```json
   {
     "success": false,
     "message": "Barcode is not currently at the source store"
   }
   ```

5. ❌ **Already scanned**
   ```json
   {
     "success": false,
     "message": "This barcode has already been scanned for this item"
   }
   ```

6. ❌ **All barcodes already scanned**
   ```json
   {
     "success": false,
     "message": "All required barcodes have already been scanned (10 of 10)"
   }
   ```

---

### View Scanned Barcodes
**API:** `GET /api/dispatches/{dispatchId}/items/{itemId}/scanned-barcodes`

```json
Response:
{
  "success": true,
  "data": {
    "dispatch_item_id": 5,
    "required_quantity": 10,
    "scanned_count": 10,
    "remaining_count": 0,
    "scanned_barcodes": [
      {
        "id": 123,
        "barcode": "8801234567890",
        "product": {
          "id": 1,
          "name": "iPhone 15 Pro"
        },
        "current_store": {
          "id": 1,
          "name": "Main Warehouse"
        },
        "scanned_at": "2025-06-19T10:30:00Z",
        "scanned_by": "John Doe"
      },
      // ... 9 more barcodes
    ]
  }
}
```

---

### Step 5: Mark Dispatch as Delivered
**API:** `PATCH /api/dispatches/{id}/deliver`

⚠️ **Important:** এই API তখনই কাজ করবে যখন **সব barcode scan** করা হয়ে গেছে!

```json
{
  "items": [
    {
      "item_id": 5,
      "received_quantity": 10,
      "damaged_quantity": 0,
      "missing_quantity": 0
    }
  ]
}
```

#### ✅ Success (All barcodes scanned):
```json
{
  "success": true,
  "message": "Dispatch delivered successfully. Inventory movements have been processed.",
  "data": { ... }
}
```

#### ❌ Error (Missing barcode scans):
```json
{
  "success": false,
  "message": "Cannot deliver dispatch: Not all barcodes have been scanned",
  "items_with_missing_barcodes": [
    {
      "item_id": 5,
      "product": "iPhone 15 Pro",
      "required": 10,
      "scanned": 7,
      "missing": 3
    }
  ]
}
```

---

## What Happens After Delivery?

When `deliver` API is called and **all barcodes are scanned**:

1. ✅ **Each scanned barcode's location is updated**
   - `ProductBarcode.store_id` changes from source_store to destination_store

2. ✅ **Individual ProductMovement records are created**
   - One record per barcode (not batch level!)
   - Each movement shows: barcode → from_store → to_store

3. ✅ **Batch inventory is updated**
   - Source batch quantity reduced
   - New batch created at destination store

4. ✅ **Dispatch status changes to "delivered"**

---

## Database Changes

### New Table: `product_dispatch_item_barcodes`
```sql
CREATE TABLE product_dispatch_item_barcodes (
  id BIGINT PRIMARY KEY,
  product_dispatch_item_id BIGINT,  -- Which dispatch item
  product_barcode_id BIGINT,        -- Which barcode was scanned
  scanned_at TIMESTAMP,             -- When it was scanned
  scanned_by BIGINT,                -- Who scanned it
  UNIQUE(product_dispatch_item_id, product_barcode_id)  -- Prevent duplicate scans
);
```

This table tracks **which specific barcodes** were scanned for each dispatch item.

---

## Frontend Implementation Guide

### UI Flow:

1. **Dispatch List Screen**
   - Show dispatches with status "in_transit"
   - Button: "Scan Items" for each dispatch

2. **Barcode Scanning Screen**
   - Show all dispatch items
   - For each item:
     - Product name
     - Required quantity: 10
     - Scanned: 7 ✅
     - Remaining: 3 ⏳
   - Barcode scanner input
   - List of scanned barcodes with timestamps

3. **Scanning UX:**
   ```
   [Product: iPhone 15 Pro]
   [Quantity: 10]
   
   Scanned: 7/10 ████████░░ 70%
   
   [Barcode Input: ____________] [Scan]
   
   Scanned Barcodes:
   ✓ 8801234567890 - 10:30 AM
   ✓ 8801234567891 - 10:31 AM
   ✓ 8801234567892 - 10:32 AM
   ...
   ```

4. **Delivery Confirmation**
   - Only show "Mark as Delivered" button when all items have complete scans
   - Show warning if any item has missing barcode scans

---

## Summary

**কোথায় barcode scan করতে হবে?**

1. ❌ **Rebalancing request** তৈরির সময় - NO
2. ❌ **Approve** করার সময় - NO
3. ❌ **In Transit** mark করার সময় - NO
4. ✅ **In Transit অবস্থায়, Delivery confirm করার আগে** - **YES! এখানেই scan করতে হবে!**
5. ❌ **Delivery confirm** করার সময় - NO (already scanned হয়ে যাবে)

**মূল নিয়ম:**
- ProductDispatch যখন **"in_transit"** status এ থাকে, তখনই barcode scan করতে হবে
- প্রতিটা item এর জন্য required quantity অনুযায়ী **individual barcode** scan করতে হবে
- সব barcode scan না করে **delivery confirm করা যাবে না**
- Scan করা প্রতিটা barcode এর **movement track** হবে

এই system এ এখন **individual barcode level** এ inventory tracking সম্পূর্ণভাবে কাজ করবে! 🎉
