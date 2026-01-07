# Frontend Implementation Guide - Social Commerce & Multi-Store Orders

## Overview
This guide covers the critical changes for handling social commerce orders and multi-store Pathao shipments.

---

## 1. Social Commerce Order Creation

### Key Change
**DO NOT send `store_id` or `batch_id` for social commerce orders at creation time.**

### API Endpoint
```
POST /api/orders
```

### Request Body (Social Commerce)
```json
{
  "order_type": "social_commerce",
  "customer_id": 123,
  "store_id": null,  // ❌ NULL for social commerce
  "items": [
    {
      "product_id": 456,
      "quantity": 2,
      "unit_price": 1500,
      "batch_id": null,  // ❌ NULL - batch assigned at scanning
      "discount_amount": 0
    }
  ],
  "discount_amount": 0,
  "shipping_amount": 100
}
```

### Important Notes
- ✅ **Stock is NOT deducted at order creation**
- ✅ Stock deducted when warehouse scans barcode
- ✅ Backend handles store assignment separately
- ✅ No batch validation errors

---

## 2. Counter/POS Orders (No Change)

### Request Body (Counter Orders - UNCHANGED)
```json
{
  "order_type": "counter",
  "customer_id": 123,
  "store_id": 5,  // ✅ Required for counter orders
  "items": [
    {
      "product_id": 456,
      "quantity": 2,
      "unit_price": 1500,
      "batch_id": 789,  // ✅ Required for counter orders
      "discount_amount": 0
    }
  ],
  "discount_amount": 0
}
```

### Important Notes
- ✅ Stock IS deducted immediately (existing behavior)
- ✅ Batch required at creation
- ✅ No changes to existing POS flow

---

## 3. Multi-Store Pathao Shipments

### Key Change
**Each store now has its own Pathao store ID. Multi-store orders create separate shipments.**

### Store Management

#### Creating/Updating Stores
```
POST /api/stores
PUT /api/stores/{id}
```

**Request Body:**
```json
{
  "name": "Store Alpha",
  "pathao_key": "12345"  // Send this (same as before)
  // Backend auto-syncs to pathao_store_id internally
}
```

**Important:**
- ✅ Send `pathao_key` as usual
- ✅ Backend handles `pathao_store_id` internally
- ✅ No schema changes needed on frontend

---

### Creating Multi-Store Shipments

#### Endpoint
```
POST /api/multi-store-shipments
```

#### Request Body
```json
{
  "order_id": 123
}
```

#### Response
```json
{
  "success": true,
  "message": "Multi-store shipments created successfully",
  "data": {
    "order_id": 123,
    "shipments": [
      {
        "store_id": 1,
        "store_name": "Store Alpha",
        "pathao_consignment_id": "PATH123456",
        "items_count": 2
      },
      {
        "store_id": 2,
        "store_name": "Store Beta",
        "pathao_consignment_id": "PATH789012",
        "items_count": 1
      }
    ],
    "total_stores": 2,
    "total_items": 3
  }
}
```

**What Happens:**
1. ✅ System groups order items by store
2. ✅ Creates separate Pathao shipment for each store
3. ✅ Uses each store's `pathao_store_id` (not env default)
4. ✅ Returns multiple tracking numbers

---

## 4. Store Assignment for Social Commerce

### Endpoint
```
POST /api/multi-store-orders/{orderId}/assign-stores
```

### Request Body
```json
{
  "store_assignments": [
    {
      "order_item_id": 1,
      "store_id": 3
    },
    {
      "order_item_id": 2,
      "store_id": 5
    }
  ]
}
```

**What Changes:**
- ✅ Assigns store to each order item
- ✅ Order status updated (e.g., `pending` → `processing`)
- ✅ No stock deduction yet (happens at scanning)

---

## 5. Warehouse Barcode Scanning

### Endpoint
```
POST /api/store/fulfillment/orders/{orderId}/scan-barcode
```

### Request Body
```json
{
  "order_item_id": 1,
  "barcode": "ABC123456789"
}
```

**What Happens:**
1. ✅ Validates barcode belongs to correct product
2. ✅ Assigns `product_batch_id` to order item
3. ✅ **Deducts stock from batch** (first and only time for social commerce)
4. ✅ Updates barcode status to `in_shipment`
5. ✅ Order fulfillment tracked (fulfilled_at, fulfilled_by)
6. ✅ Order status updated when all items scanned

---

## Summary of Flow Changes

### Social Commerce Flow
```
1. Create Order (no store, no batch) → Stock NOT deducted
2. Assign Store → Still no stock deduction
3. Scan Barcode → Stock deducted HERE (once and only once)
4. Create Shipment → Multiple shipments per store
```

### Counter Order Flow (UNCHANGED)
```
1. Create Order (with store + batch) → Stock deducted immediately
2. Process Payment
3. Complete
```

---

## Key Implementation Details

### Stock Deduction Logic (Backend Handles This)
The backend now has smart stock deduction:
- **Counter orders**: Stock deducted at order creation (immediate)
- **Social commerce WITHOUT store_id**: Stock deducted at barcode scanning
- **Social commerce WITH store_id**: Stock deducted at order creation
- **Double deduction prevention**: Backend checks if batch was already assigned before deducting

### Order Status Flow
```
pending → processing → picking → fulfilled
```
- Status transitions handled by backend
- Frontend should display status, not enforce transitions

---

## Testing Checklist

- [ ] Social commerce orders created without store_id/batch_id
- [ ] No batch validation errors for social commerce
- [ ] Counter orders still work with immediate stock deduction
- [ ] Store assignment updates order items correctly
- [ ] Barcode scanning deducts stock only once
- [ ] Multi-store shipments create separate Pathao consignments
- [ ] Each shipment uses correct store's pathao_store_id

---

## API Compatibility
✅ **100% Backward Compatible**
- Counter/POS orders work exactly as before
- Social commerce is additive functionality
- No breaking changes to existing endpoints
- Store management unchanged (pathao_key same as before)

---

## Questions?
Contact backend team for clarification on order flows or multi-store shipment logic.
