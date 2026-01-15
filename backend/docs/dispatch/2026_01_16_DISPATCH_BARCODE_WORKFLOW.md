# Dispatch & Transfer Workflow - Complete Guide

**Document Version:** 1.0  
**Last Updated:** January 16, 2026  
**Status:** ✅ Production Ready

---

## Table of Contents

1. [Overview](#overview)
2. [Complete Workflow](#complete-workflow)
3. [Store A: Source Store (Sending)](#store-a-source-store-sending)
4. [Store B: Destination Store (Receiving)](#store-b-destination-store-receiving)
5. [API Endpoints Reference](#api-endpoints-reference)
6. [Data Structures](#data-structures)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)

---

## Overview

The dispatch system enables physical product transfers between stores with **mandatory barcode scanning** at both source and destination stores. This ensures complete traceability and accurate inventory management.

### Key Features
- ✅ **Mandatory barcode scanning** at source before sending
- ✅ **Individual unit tracking** during transit
- ✅ **Barcode verification** at destination during receiving
- ✅ **Real-time inventory updates** at both stores
- ✅ **Full audit trail** of product movements

### Business Flow
```
Store A (Source)                    Transit                    Store B (Destination)
─────────────────                 ─────────                   ──────────────────────
1. Create Dispatch                                            
2. Add Items                                                  
3. Scan Each Barcode              ────────►                  
4. Approve Dispatch                                           
5. Send Products                  ────────►                   6. Receive Shipment
                                  (in_transit)                7. Scan Each Barcode
                                                              8. Stock Shelves
                                                              9. Complete Delivery
```

---

## Complete Workflow

### Phase 1: Preparation (Store A)
**Status:** `pending`

1. **Create Dispatch**
2. **Add Products** (items with quantities)
3. **Scan Physical Barcodes** (mandatory for each unit)

### Phase 2: Approval & Sending (Store A)
**Status:** `pending` → `in_transit`

4. **Approve Dispatch** (manager approval)
5. **Send Products** (mark as dispatched)
   - Barcodes change status: `reserved` → `in_transit`

### Phase 3: Receiving (Store B)
**Status:** `in_transit` → `delivered`

6. **Receive Shipment** (dispatch arrives)
7. **Scan Barcodes** (verify each unit received)
8. **Complete Delivery** (when all items received)
   - Barcodes change status: `in_transit` → `available`
   - Inventory automatically updated

---

## Store A: Source Store (Sending)

### Step 1: Create Dispatch

**Endpoint:** `POST /api/dispatches`

**Request:**
```json
{
  "source_store_id": 1,
  "destination_store_id": 2,
  "expected_delivery_date": "2026-01-20",
  "carrier_name": "DHL Express",
  "tracking_number": "DHL123456",
  "notes": "Fragile items - handle with care"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Dispatch created successfully",
  "data": {
    "id": 15,
    "dispatch_number": "DSP-20260116-001",
    "status": "pending",
    "source_store": {
      "id": 1,
      "name": "Main Warehouse"
    },
    "destination_store": {
      "id": 2,
      "name": "Retail Store - Gulshan"
    },
    "created_at": "2026-01-16T10:30:00Z"
  }
}
```

---

### Step 2: Add Items to Dispatch

**Endpoint:** `POST /api/dispatches/{id}/items`

**Request:**
```json
{
  "batch_id": 45,
  "quantity": 10
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item added to dispatch successfully",
  "data": {
    "dispatch_item": {
      "id": 23,
      "product": {
        "id": 12,
        "name": "iPhone 15 Pro",
        "sku": "IPH15PRO-256"
      },
      "batch_number": "BATCH-20260110-001",
      "quantity": 10,
      "unit_cost": "85000.00",
      "total_cost": "850000.00"
    },
    "dispatch_totals": {
      "total_items": 1,
      "total_cost": "850000.00"
    }
  }
}
```

**Note:** Repeat this step for each product you want to add to the dispatch.

---

### Step 3: Scan Physical Barcodes (MANDATORY)

**Endpoint:** `POST /api/dispatches/{id}/items/{itemId}/scan-barcode`

**⚠️ Critical:** You MUST scan all physical barcodes before sending. If you added 10 units, you must scan 10 individual barcodes.

**Request:**
```json
{
  "barcode": "8801234567890"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Barcode scanned successfully. 5 of 10 items scanned.",
  "data": {
    "barcode": "8801234567890",
    "scanned_count": 5,
    "required_quantity": 10,
    "remaining_count": 5,
    "all_scanned": false,
    "scanned_at": "2026-01-16T10:35:22Z",
    "scanned_by": "John Doe"
  }
}
```

**Error Responses:**

❌ **Barcode not found:**
```json
{
  "success": false,
  "message": "Barcode not found in system"
}
```

❌ **Wrong product:**
```json
{
  "success": false,
  "message": "Barcode does not match the product for this dispatch item"
}
```

❌ **Not at source store:**
```json
{
  "success": false,
  "message": "Barcode is not currently at the source store"
}
```

❌ **Already scanned:**
```json
{
  "success": false,
  "message": "This barcode has already been scanned for this item"
}
```

❌ **Quota reached:**
```json
{
  "success": false,
  "message": "All required barcodes have already been scanned (10 of 10)"
}
```

---

### Step 3a: View Scanned Barcodes

**Endpoint:** `GET /api/dispatches/{id}/items/{itemId}/scanned-barcodes`

**Response:**
```json
{
  "success": true,
  "data": {
    "dispatch_item_id": 23,
    "required_quantity": 10,
    "scanned_count": 5,
    "remaining_count": 5,
    "all_scanned": false,
    "scanned_barcodes": [
      {
        "id": 101,
        "barcode": "8801234567890",
        "scanned_at": "2026-01-16T10:35:22Z",
        "scanned_by": "John Doe"
      },
      {
        "id": 102,
        "barcode": "8801234567891",
        "scanned_at": "2026-01-16T10:35:45Z",
        "scanned_by": "John Doe"
      }
      // ... 3 more barcodes
    ]
  }
}
```

**Frontend UI Suggestion:**
- Show progress: "Scanned: 5/10 items"
- Display scanned barcode list in real-time
- Show green checkmark when `all_scanned: true`
- Disable "Send Products" button until all items scanned

---

### Step 4: Approve Dispatch

**Endpoint:** `PATCH /api/dispatches/{id}/approve`

**Request:** No body required

**Response:**
```json
{
  "success": true,
  "message": "Dispatch approved successfully",
  "data": {
    "id": 15,
    "dispatch_number": "DSP-20260116-001",
    "status": "pending",
    "approved_by": {
      "id": 5,
      "name": "Manager Sarah"
    },
    "approved_at": "2026-01-16T11:00:00Z"
  }
}
```

---

### Step 5: Send Products (Mark as Dispatched)

**Endpoint:** `PATCH /api/dispatches/{id}/dispatch`

**⚠️ This will fail if not all barcodes are scanned!**

**Request:** No body required

**Success Response:**
```json
{
  "success": true,
  "message": "Dispatch marked as in transit successfully",
  "data": {
    "id": 15,
    "dispatch_number": "DSP-20260116-001",
    "status": "in_transit",
    "dispatch_date": "2026-01-16T11:05:00Z",
    "items": [
      {
        "id": 23,
        "product_name": "iPhone 15 Pro",
        "quantity": 10,
        "status": "dispatched",
        "scanned_barcodes_count": 10
      }
    ]
  }
}
```

**Error Response (Missing Barcodes):**
```json
{
  "success": false,
  "message": "Cannot dispatch item without scanning barcodes. Please scan 10 barcode(s) for this item before sending."
}
```

**What Happens:**
- Dispatch status: `pending` → `in_transit`
- All scanned barcodes status: `reserved` → `in_transit`
- Items marked as `dispatched`
- Products are now physically on the way to Store B

---

## Store B: Destination Store (Receiving)

### Step 6: View Incoming Dispatch

**Endpoint:** `GET /api/dispatches/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "dispatch_number": "DSP-20260116-001",
    "status": "in_transit",
    "source_store": {
      "id": 1,
      "name": "Main Warehouse"
    },
    "destination_store": {
      "id": 2,
      "name": "Retail Store - Gulshan"
    },
    "dispatch_date": "2026-01-16T11:05:00Z",
    "expected_delivery_date": "2026-01-20",
    "tracking_number": "DHL123456",
    "items": [
      {
        "id": 23,
        "product": {
          "id": 12,
          "name": "iPhone 15 Pro",
          "sku": "IPH15PRO-256"
        },
        "quantity": 10,
        "received_quantity": 0,
        "status": "dispatched"
      }
    ]
  }
}
```

---

### Step 7: Scan Barcodes to Receive

**Endpoint:** `POST /api/dispatches/{id}/items/{itemId}/receive-barcode`

**⚠️ Important:** Scan each physical barcode as you unpack the shipment.

**Request:**
```json
{
  "barcode": "8801234567890"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Barcode received successfully. 1 of 10 items received.",
  "data": {
    "barcode": "8801234567890",
    "received_count": 1,
    "total_sent": 10,
    "remaining_count": 9,
    "all_received": false,
    "received_at": "2026-01-20T14:30:00Z",
    "received_by": "Store Manager",
    "current_store": {
      "id": 2,
      "name": "Retail Store - Gulshan"
    }
  }
}
```

**Error Responses:**

❌ **Barcode not sent in this dispatch:**
```json
{
  "success": false,
  "message": "This barcode was not sent in this dispatch"
}
```

❌ **Already received:**
```json
{
  "success": false,
  "message": "This barcode has already been received at destination"
}
```

❌ **Wrong dispatch status:**
```json
{
  "success": false,
  "message": "Barcodes can only be received when dispatch is in transit"
}
```

**What Happens:**
- Barcode status: `in_transit` → `available`
- Barcode location: Store A → Store B
- Item's `received_quantity` incremented
- Barcode can now be sold from Store B

---

### Step 7a: View Received Barcodes

**Endpoint:** `GET /api/dispatches/{id}/items/{itemId}/received-barcodes`

**Response:**
```json
{
  "success": true,
  "data": {
    "dispatch_item_id": 23,
    "total_sent": 10,
    "received_count": 7,
    "missing_count": 3,
    "all_received": false,
    "received_barcodes": [
      {
        "id": 101,
        "barcode": "8801234567890",
        "received_at": "2026-01-20T14:30:00Z",
        "received_by_id": 8,
        "current_store": {
          "id": 2,
          "name": "Retail Store - Gulshan"
        }
      }
      // ... 6 more barcodes
    ],
    "missing_barcodes": [
      {
        "id": 108,
        "barcode": "8801234567897",
        "status": "in_transit",
        "last_known_location": "In Transit"
      }
      // ... 2 more barcodes
    ]
  }
}
```

**Frontend UI Suggestion:**
- Show progress: "Received: 7/10 items"
- List received barcodes with timestamps
- Highlight missing barcodes
- Enable "Complete Delivery" button when `all_received: true`

---

### Step 8: Complete Delivery

**Endpoint:** `PATCH /api/dispatches/{id}/deliver`

**⚠️ Recommended:** Complete delivery after receiving all items.

**Request:** No body required

**Response:**
```json
{
  "success": true,
  "message": "Dispatch marked as delivered successfully",
  "data": {
    "id": 15,
    "dispatch_number": "DSP-20260116-001",
    "status": "delivered",
    "actual_delivery_date": "2026-01-20T15:00:00Z",
    "items": [
      {
        "id": 23,
        "product_name": "iPhone 15 Pro",
        "quantity": 10,
        "received_quantity": 10,
        "damaged_quantity": 0,
        "missing_quantity": 0,
        "status": "delivered"
      }
    ]
  }
}
```

**What Happens:**
- Dispatch status: `in_transit` → `delivered`
- Creates new product batch at destination store
- Updates inventory quantities at both stores
- Records product movement in system
- Full audit trail created

---

## API Endpoints Reference

### Dispatch Management

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/dispatches` | List all dispatches with filters | ✅ |
| POST | `/api/dispatches` | Create new dispatch | ✅ |
| GET | `/api/dispatches/{id}` | Get dispatch details | ✅ |
| POST | `/api/dispatches/{id}/items` | Add item to dispatch | ✅ |
| DELETE | `/api/dispatches/{id}/items/{itemId}` | Remove item from dispatch | ✅ |
| PATCH | `/api/dispatches/{id}/approve` | Approve dispatch | ✅ Manager |
| PATCH | `/api/dispatches/{id}/dispatch` | Send dispatch (mark in_transit) | ✅ Manager |
| PATCH | `/api/dispatches/{id}/deliver` | Complete delivery | ✅ |
| PATCH | `/api/dispatches/{id}/cancel` | Cancel dispatch | ✅ Manager |

### Barcode Scanning (Source Store)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/dispatches/{id}/items/{itemId}/scan-barcode` | Scan barcode before sending | ✅ |
| GET | `/api/dispatches/{id}/items/{itemId}/scanned-barcodes` | List scanned barcodes | ✅ |

### Barcode Receiving (Destination Store)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/dispatches/{id}/items/{itemId}/receive-barcode` | Scan barcode when receiving | ✅ |
| GET | `/api/dispatches/{id}/items/{itemId}/received-barcodes` | List received barcodes | ✅ |

### Statistics

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/dispatches/statistics` | Get dispatch statistics | ✅ |

---

## Data Structures

### Dispatch Status Flow

```
pending → in_transit → delivered
   ↓
cancelled (can cancel from pending only)
```

### Barcode Status Flow (During Dispatch)

```
available → reserved → in_transit → available
(at source)  (scanned)  (sent)      (at destination)
```

### Dispatch Object

```json
{
  "id": 15,
  "dispatch_number": "DSP-20260116-001",
  "status": "in_transit",
  "source_store_id": 1,
  "destination_store_id": 2,
  "dispatch_date": "2026-01-16T11:05:00Z",
  "expected_delivery_date": "2026-01-20",
  "actual_delivery_date": null,
  "carrier_name": "DHL Express",
  "tracking_number": "DHL123456",
  "total_items": 10,
  "total_cost": "850000.00",
  "total_value": "950000.00",
  "notes": "Fragile items",
  "created_by": 3,
  "approved_by": 5,
  "approved_at": "2026-01-16T11:00:00Z",
  "created_at": "2026-01-16T10:30:00Z",
  "updated_at": "2026-01-16T11:05:00Z"
}
```

### Dispatch Item Object

```json
{
  "id": 23,
  "product_dispatch_id": 15,
  "product_batch_id": 45,
  "quantity": 10,
  "unit_cost": "85000.00",
  "unit_price": "95000.00",
  "total_cost": "850000.00",
  "total_value": "950000.00",
  "status": "dispatched",
  "received_quantity": 7,
  "damaged_quantity": 0,
  "missing_quantity": 3,
  "notes": null
}
```

---

## Error Handling

### Common Error Codes

| Status Code | Meaning | Common Causes |
|-------------|---------|---------------|
| 404 | Not Found | Invalid dispatch ID or item ID |
| 422 | Unprocessable Entity | Validation errors, business logic violations |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | Insufficient permissions |

### Validation Errors

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "source_store_id": ["The source store id field is required."],
    "destination_store_id": ["The destination store id field is required."]
  }
}
```

### Business Logic Errors

```json
{
  "success": false,
  "message": "Cannot dispatch item without scanning barcodes. Please scan 10 barcode(s) for this item before sending."
}
```

---

## Best Practices

### For Frontend Developers

1. **Always Validate Before Actions**
   - Check `scanned_count === required_quantity` before enabling "Send" button
   - Check `received_count === total_sent` before suggesting "Complete Delivery"

2. **Real-time Progress Indicators**
   ```javascript
   // Example progress calculation
   const progress = (scannedCount / requiredQuantity) * 100;
   // Show: "Scanned: 7/10 (70%)"
   ```

3. **Barcode Scanner Integration**
   ```javascript
   // Listen for barcode scanner input
   barcodeScanner.on('scan', async (barcode) => {
     try {
       const result = await scanBarcode(dispatchId, itemId, barcode);
       showSuccess(result.message);
       updateProgress(result.data);
     } catch (error) {
       showError(error.message);
       playErrorSound();
     }
   });
   ```

4. **Handle Missing Items**
   - Allow marking items as "damaged" or "missing"
   - Show warning if `received_quantity < quantity`
   - Generate discrepancy report

5. **Multi-Item Dispatches**
   - Show separate progress for each item
   - Allow scanning items in any order
   - Highlight which items are complete

### For Store Staff

1. **Source Store Checklist:**
   - [ ] Create dispatch
   - [ ] Add all items
   - [ ] Scan ALL physical barcodes (mandatory)
   - [ ] Get manager approval
   - [ ] Pack items securely
   - [ ] Mark as dispatched
   - [ ] Hand over to carrier

2. **Destination Store Checklist:**
   - [ ] Verify dispatch details
   - [ ] Unpack carefully
   - [ ] Scan each barcode as you unpack
   - [ ] Report any damages immediately
   - [ ] Complete delivery when all items received
   - [ ] Stock shelves

---

## Troubleshooting

### Issue: Cannot send dispatch

**Error:** "Cannot dispatch item without scanning barcodes"

**Solution:** 
- Go back to Step 3
- Use `GET /api/dispatches/{id}/items/{itemId}/scanned-barcodes` to check progress
- Scan remaining barcodes until `all_scanned: true`

---

### Issue: Barcode not found during receiving

**Error:** "This barcode was not sent in this dispatch"

**Possible Causes:**
1. Barcode not scanned at source store
2. Wrong dispatch being received
3. Barcode typo/scanning error

**Solution:**
- Verify dispatch number on package
- Check sent barcode list: `GET /api/dispatches/{id}/items/{itemId}/scanned-barcodes`
- Contact source store if barcode should be in the dispatch

---

### Issue: Missing items after delivery

**Scenario:** Only 7 of 10 items received

**Steps:**
1. Check received barcodes: `GET /api/dispatches/{id}/items/{itemId}/received-barcodes`
2. Review `missing_barcodes` array
3. Contact carrier with tracking number
4. Complete delivery anyway (system records the discrepancy)
5. File claim if items are lost

---

## Frontend UI Examples

### Scanning Progress Component

```jsx
<DispatchItemProgress 
  itemId={23}
  productName="iPhone 15 Pro"
  requiredQuantity={10}
  scannedCount={7}
  onScanBarcode={handleScan}
/>

// Displays:
// iPhone 15 Pro
// Progress: 7/10 (70%) [====----]
// [Scan Barcode] button
// List of scanned barcodes
```

### Receiving Dashboard

```jsx
<ReceivingDashboard 
  dispatchId={15}
  dispatchNumber="DSP-20260116-001"
  items={[
    { name: "iPhone 15 Pro", total: 10, received: 7, missing: 3 },
    { name: "iPad Air", total: 5, received: 5, missing: 0 }
  ]}
  onReceiveBarcode={handleReceive}
  onComplete={handleComplete}
/>

// Shows:
// Dispatch: DSP-20260116-001
// Overall: 12/15 items received (80%)
// 
// iPhone 15 Pro: 7/10 ⚠️
// iPad Air: 5/5 ✓
//
// [Scan Barcode] [Complete Delivery]
```

---

## Conclusion

This dispatch system ensures:
- ✅ **100% traceability** of physical products
- ✅ **Accurate inventory** at all locations
- ✅ **Prevention of errors** through mandatory scanning
- ✅ **Real-time visibility** of in-transit items
- ✅ **Audit compliance** with complete movement history

**Important:** Barcode scanning is **MANDATORY** at both ends. This is not optional - it's a core feature that ensures system integrity.

---

**Questions?** Contact the backend team or refer to:
- [Product Barcode API Documentation](../product/2026_01_13_PRODUCT_BARCODES_API.md)
- [Multi-Store Fulfillment Guide](../guides/MULTI_STORE_FULFILLMENT.md)
