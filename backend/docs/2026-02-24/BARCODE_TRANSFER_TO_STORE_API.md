# Product Transfer to Store API

**Date:** February 24, 2026  
**Feature:** Barcode Transfer/Migration Between Stores  
**Endpoint:** POST `/api/employee/barcodes/transfer-to-store`

---

## Overview

This API allows you to transfer a physical product (identified by barcode) from one store to another. The system automatically handles batch creation at the target store if needed.

**Use Case:** When you need to move inventory from one store location to another, simply scan the barcode and specify the target store ID.

---

## API Endpoint

### Transfer Product to Store

**URL:** `POST /api/employee/barcodes/transfer-to-store`

**Authentication:** Required (Employee JWT token)

**Permissions:** `products.view`, `products.manage_barcodes`

---

## Request

### Headers
```
Authorization: Bearer {employee_jwt_token}
Content-Type: application/json
```

### Request Body

```json
{
  "barcode": "123456789012",
  "store_id": 5
}
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `barcode` | string | Yes | The barcode of the physical product to transfer |
| `store_id` | integer | Yes | The ID of the target store to transfer to |

---

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Product transferred successfully",
  "data": {
    "barcode": "123456789012",
    "product": {
      "id": 101,
      "name": "Winter Jacket - Black",
      "sku": "WJ-BLK-001"
    },
    "from_store": {
      "id": 3,
      "name": "Dhanmondi Branch"
    },
    "to_store": {
      "id": 5,
      "name": "Gulshan Branch"
    },
    "batch": {
      "id": 245,
      "batch_number": "BATCH-2026-02-001",
      "quantity": 15,
      "sell_price": "5000.00"
    },
    "current_status": "in_warehouse",
    "transferred_at": "2026-02-24T15:30:00+06:00"
  }
}
```

### Error Responses

#### Barcode Not Found (404)

```json
{
  "success": false,
  "message": "Barcode not found"
}
```

#### Cannot Transfer Sold Product (422)

```json
{
  "success": false,
  "message": "Cannot transfer sold products that are with customer"
}
```

#### No Associated Batch (422)

```json
{
  "success": false,
  "message": "Barcode has no associated batch. Cannot transfer."
}
```

#### Validation Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "barcode": ["The barcode field is required."],
    "store_id": ["The store_id field is required."]
  }
}
```

#### Server Error (500)

```json
{
  "success": false,
  "message": "Transfer failed: {error_details}"
}
```

---

## What Happens Behind the Scenes

### 1. Barcode Lookup
- System finds the physical product by barcode
- Validates that the product exists and has a batch

### 2. Transfer Validation
- Checks if product is sold but not returned (blocks transfer)
- Ensures product has an associated batch

### 3. Batch Management
- **If batch exists at target store** (same product, same prices):
  - Uses existing batch
  - Increments batch quantity by 1
  
- **If no batch exists at target store**:
  - Creates new batch with same pricing as source batch
  - Copies cost_price, sell_price, tax settings
  - Initializes quantity to 0 (then increments by 1)

### 4. Inventory Updates
- Updates barcode's `current_store_id` to target store
- Updates barcode's `batch_id` to target store's batch
- Sets barcode status to `in_warehouse` (default for transfers)
- Increments target batch quantity by 1
- Decrements source batch quantity by 1 (if different batch)

### 5. Audit Trail
- Creates movement record in `product_movements` table
- Logs: from_store, to_store, timestamp, employee who performed transfer
- Movement type: `"transfer"`

---

## Business Rules

### ✅ Allowed Transfers

- Products in warehouse (`in_warehouse`)
- Products in shop (`in_shop`)
- Products on display (`on_display`)
- Products in transit (`in_transit`)
- Returned products (`in_return`)
- Defective products (marked as defective)

### ❌ Blocked Transfers

- Products with status `with_customer` (sold and delivered)
- Products with status `sold` (old status, treated same as `with_customer`)
- Products without an associated batch

---

## Example Usage

### Scenario 1: Transfer Product Between Stores

**Request:**
```bash
curl -X POST https://api.example.com/api/employee/barcodes/transfer-to-store \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "barcode": "BAR-2026-12345",
    "store_id": 7
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Product transferred successfully",
  "data": {
    "barcode": "BAR-2026-12345",
    "product": {
      "id": 55,
      "name": "Smart Watch - Silver",
      "sku": "SW-SLV-2026"
    },
    "from_store": {
      "id": 2,
      "name": "Banani Warehouse"
    },
    "to_store": {
      "id": 7,
      "name": "Uttara Showroom"
    },
    "batch": {
      "id": 189,
      "batch_number": "BATCH-2026-02-189",
      "quantity": 8,
      "sell_price": "12500.00"
    },
    "current_status": "in_warehouse",
    "transferred_at": "2026-02-24T15:30:00+06:00"
  }
}
```

### Scenario 2: Transfer Creates New Batch

If the target store doesn't have a batch for this product at the same price, the system automatically creates one.

**Request:**
```bash
curl -X POST https://api.example.com/api/employee/barcodes/transfer-to-store \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "barcode": "BAR-2026-67890",
    "store_id": 4
  }'
```

**What Happens:**
1. System checks if Store #4 has a batch for this product
2. No matching batch found
3. System creates new batch at Store #4 with:
   - Same cost_price as source batch
   - Same sell_price as source batch
   - Same tax settings
   - Quantity: 1 (just this transferred item)
4. Barcode is assigned to new batch
5. Transfer completes successfully

---

## UI Implementation Suggestions

### Basic Transfer Form

```html
<!-- HTML Structure -->
<form id="transfer-form">
  <div class="form-group">
    <label>Scan Barcode</label>
    <input 
      type="text" 
      id="barcode-input"
      placeholder="Scan or enter barcode"
      autofocus
    />
  </div>
  
  <div class="form-group">
    <label>Target Store</label>
    <select id="store-select">
      <option value="">Select store...</option>
      <option value="1">Dhanmondi Branch</option>
      <option value="2">Gulshan Branch</option>
      <option value="3">Uttara Showroom</option>
    </select>
  </div>
  
  <button type="submit">Transfer Product</button>
</form>

<div id="result-display"></div>
```

### Display Transfer Result

After successful transfer, show:

```
✅ Transfer Successful

Product: Winter Jacket - Black (WJ-BLK-001)
Barcode: BAR-2026-12345

Transferred From: Dhanmondi Branch
Transferred To: Gulshan Branch

New Location: Gulshan Branch - Warehouse
Batch: BATCH-2026-02-245 (15 items in stock)

Transferred at: Feb 24, 2026 3:30 PM
```

### Error Handling

```
❌ Transfer Failed

Cannot transfer this product because it has been sold and is with the customer.

Please ensure the product is returned before attempting transfer.
```

---

## Integration Notes

### 1. Barcode Scanner Integration

If using a physical barcode scanner:
- Scanner should input to the barcode text field
- Auto-submit form on barcode scan (detect Enter key)
- Show loading state during API call

### 2. Batch Updates

After transfer:
- Refresh source store inventory (quantity decreased)
- Refresh target store inventory (quantity increased)
- Update batch list if showing batch details

### 3. Movement History

The transfer creates a movement record that appears in:
- Product movement history (GET `/api/employee/barcodes/{barcode}/history`)
- Store transfer logs
- Inventory audit reports

---

## Testing Checklist

- [ ] Transfer product from Store A to Store B successfully
- [ ] Transfer creates new batch when none exists at target
- [ ] Transfer uses existing batch when prices match
- [ ] Cannot transfer sold product (error message shown)
- [ ] Cannot transfer product without batch (error message shown)
- [ ] Barcode not found error handled gracefully
- [ ] Invalid store ID error handled
- [ ] Source store quantity decreases by 1
- [ ] Target store quantity increases by 1
- [ ] Movement record created in database
- [ ] Transfer history appears in barcode history

---

## Frequently Asked Questions

**Q: What happens if the product doesn't exist at the target store?**  
A: The system automatically creates a new batch at the target store with the same pricing as the source batch.

**Q: Can I transfer multiple products at once?**  
A: Currently no. You need to transfer one barcode at a time. For bulk transfers, call the API multiple times.

**Q: What if the target store has the same product but at a different price?**  
A: The system creates a new batch. Batches are matched by: product_id + store_id + sell_price + cost_price.

**Q: Can I transfer defective products?**  
A: Yes. The transfer will work regardless of defective status. The defective flag moves with the barcode.

**Q: What happens to the source store's inventory?**  
A: The source batch quantity is decreased by 1. If the batch becomes empty (quantity = 0), it remains in the database for historical records.

**Q: Can I transfer to the same store the product is already at?**  
A: Yes, but it's redundant. The system will process it but the product remains at the same store.

**Q: Does this affect orders or shipments?**  
A: No. This only transfers physical inventory between stores. Orders and shipments are separate.

---

## Related APIs

- **Scan Barcode:** `POST /api/employee/barcodes/scan`
- **Get Barcode History:** `GET /api/employee/barcodes/{barcode}/history`
- **Get Current Location:** `GET /api/employee/barcodes/{barcode}/location`
- **Get Product Barcodes:** `GET /api/employee/products/{productId}/barcodes`

---

**Status:** ✅ Ready for implementation

**Backend:** v2.24.0
