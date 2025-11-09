# Quick Reference: Barcode Scanning & Physical Inventory

## Core Concept

```
Product (Definition)
    ↓
ProductBatch (Physical Units in Store)
    ↓
ProductBarcode (Scannable Identifier)
    ↓
ProductDispatch (Movement Between Stores)
```

## Most Important Endpoints

### 🔍 Scan Barcode (The Core Feature)
```http
POST /api/barcodes/scan
{
  "barcode": "123456789012"
}
```
**Returns everything you need:**
- Product details (name, SKU, category, vendor)
- Current location (which store)
- Batch info (quantity, prices, status)
- Movement history
- Availability

---

## Common Workflows

### 📦 Receive Inventory from Vendor

```http
POST /api/batches
{
  "product_id": 1,
  "store_id": 1,
  "quantity": 100,
  "cost_price": 500.00,
  "sell_price": 750.00,
  "generate_barcodes": true
}
```

### 🚚 Transfer to Another Store

```http
# 1. Create dispatch
POST /api/dispatches
{
  "source_store_id": 1,
  "destination_store_id": 2
}

# 2. Add items
POST /api/dispatches/{id}/items
{
  "batch_id": 1,
  "quantity": 50
}

# 3. Approve → Dispatch → Deliver
PATCH /api/dispatches/{id}/approve
PATCH /api/dispatches/{id}/dispatch
PATCH /api/dispatches/{id}/deliver
```

### 🛒 Point of Sale

```http
# Scan at checkout
POST /api/barcodes/scan
{
  "barcode": "scanned_code"
}

# Reduce stock after sale
POST /api/batches/{id}/adjust-stock
{
  "adjustment": -1,
  "reason": "Sold"
}
```

### 📊 Check Stock Levels

```http
# Low stock items
GET /api/batches/low-stock?threshold=10

# Expiring soon
GET /api/batches/expiring-soon?days=30

# Statistics
GET /api/batches/statistics
```

### 📍 Track Product Location

```http
# Current location
GET /api/barcodes/{barcode}/location

# Movement history
GET /api/barcodes/{barcode}/history
```

### ✅ Inventory Verification

```http
POST /api/barcodes/batch-scan
{
  "barcodes": ["123", "456", "789"]
}
```

---

## Response Examples

### Scanning a Barcode
```json
{
  "success": true,
  "data": {
    "barcode": "123456789012",
    "product": {
      "name": "iPhone 15 Pro",
      "sku": "IPH15PRO"
    },
    "current_location": {
      "name": "Downtown Branch"
    },
    "current_batch": {
      "quantity": 45,
      "sell_price": "750.00",
      "status": "available"
    },
    "is_available": true,
    "last_movement": {
      "type": "dispatch",
      "from": "Main Warehouse",
      "to": "Downtown Branch"
    }
  }
}
```

### Batch Statistics
```json
{
  "total_batches": 156,
  "low_stock_batches": 15,
  "expiring_soon_batches": 12,
  "total_inventory_value": "1500000.00",
  "by_store": [
    {
      "store_name": "Main Warehouse",
      "total_units": 8900,
      "inventory_value": "890000.00"
    }
  ]
}
```

---

## Status Reference

### Batch Status
- `available` - In stock, not expired
- `low_stock` - Below threshold
- `out_of_stock` - Quantity = 0
- `expired` - Past expiry date
- `inactive` - Deactivated

### Dispatch Status
1. `pending` - Created, adding items
2. `approved` - Manager approved (ready to send)
3. `in_transit` - On the way
4. `delivered` - Received (inventory updated)
5. `cancelled` - Cancelled

---

## Quick Tips

✅ **Always** generate barcodes when creating batches  
✅ **Scan** barcodes at every touchpoint  
✅ **Approve** dispatches before sending  
✅ **Track** damaged/missing items during delivery  
✅ **Monitor** expiring items regularly  

❌ Don't dispatch more than available quantity  
❌ Don't skip approval step  
❌ Don't delete batches with movements (deactivate instead)  

---

## All Endpoints Summary

**Batches**: 11 endpoints - Create, list, update, adjust stock, statistics, low stock, expiring, expired  
**Barcodes**: 10 endpoints - Scan, batch scan, history, location, generate, list  
**Dispatches**: 11 endpoints - Create, add items, approve, dispatch, deliver, cancel, statistics  

**Total**: 32 endpoints for complete physical inventory management

---

## Next Steps

1. Test barcode scanning: `POST /api/barcodes/scan`
2. Create a test batch: `POST /api/batches`
3. Try a dispatch: `POST /api/dispatches`
4. Check statistics: `GET /api/batches/statistics`

See `INVENTORY_BARCODE_DISPATCH_SYSTEM.md` for detailed documentation.
