# Order Barcode Workflow - Fixed

## Issue Summary
FE team reported: "On edit order, barcode is mandatory" - but social commerce and ecommerce orders don't know barcodes at order creation time.

## Root Cause
The `addItem()` method in OrderController required barcodes for ALL order types, but social_commerce and ecommerce orders don't have barcodes until fulfillment.

## Solution Implemented
Updated `addItem()` method to support TWO modes:
1. **Barcode scanning** (for POS/counter orders)
2. **Product selection** (for social commerce/ecommerce orders)

---

## Complete Workflow by Order Type

### 1. POS/Counter Orders
**Workflow:**
```
Customer picks products → Employee scans barcodes → Create order → Immediate fulfillment
```

**API Calls:**

**Create Order:**
```json
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "items": [
    {
      "product_id": 1,
      "batch_id": 5,
      "barcode": "123456789012",  // ✅ Barcode scanned at POS
      "quantity": 1,
      "unit_price": 500.00
    }
  ]
}
```

**Add Item to Existing Order (POS):**
```json
POST /api/orders/{id}/items
{
  "barcode": "123456789013"  // Scan barcode
}
```
OR scan multiple:
```json
POST /api/orders/{id}/items
{
  "barcodes": ["123456789013", "123456789014"]
}
```

---

### 2. Social Commerce Orders
**Workflow:**
```
Employee creates order → Don't know branch/batch/barcode yet
↓
Later: Warehouse employee selects barcodes and fulfills order
```

**API Calls:**

**Create Order:**
```json
POST /api/orders
{
  "order_type": "social_commerce",
  "store_id": 1,
  "customer": {
    "name": "John Doe",
    "phone": "+8801712345678"
  },
  "items": [
    {
      "product_id": 1,
      "batch_id": 5,  // Optional - can be assigned later
      "quantity": 2,
      "unit_price": 500.00
      // ❌ NO barcode field - assigned during fulfillment
    }
  ]
}
```

**Add Item to Existing Order (Social Commerce):** ✅ FIXED
```json
POST /api/orders/{id}/items
{
  "product_id": 1,
  "batch_id": 5,     // Optional - auto-selects oldest batch if omitted
  "quantity": 2,
  "unit_price": 500.00,
  "discount_amount": 0
}
```

**Later - Fulfill Order (Assign Barcodes):**
```json
POST /api/orders/{id}/fulfill
{
  "fulfillments": [
    {
      "order_item_id": 123,
      "barcodes": ["123456789013", "123456789014"]  // Warehouse scans barcodes now
    }
  ]
}
```

---

### 3. E-commerce Orders
**Workflow:**
```
Customer adds to cart → Creates order online → Don't know branch/batch/barcode yet
↓
Later: Warehouse employee selects barcodes and fulfills order
```

**API Calls:**

**Create Order (from cart):**
```json
POST /api/orders
{
  "order_type": "ecommerce",
  "store_id": 1,  // May be null initially, assigned later
  "customer_id": 50,
  "items": [
    {
      "product_id": 1,
      "quantity": 3,
      "unit_price": 500.00
      // ❌ NO batch_id - assigned when order assigned to store
      // ❌ NO barcode - assigned during fulfillment
    }
  ],
  "shipping_address": {...}
}
```

**Admin Assigns to Store:**
```json
PUT /api/orders/{id}/assign-to-store
{
  "store_id": 2
}
```

**Add Item to Existing Order (E-commerce):** ✅ FIXED
```json
POST /api/orders/{id}/items
{
  "product_id": 1,
  "quantity": 1,
  "unit_price": 500.00
}
```

**Later - Fulfill Order (Assign Barcodes):**
```json
POST /api/orders/{id}/fulfill
{
  "fulfillments": [
    {
      "order_item_id": 124,
      "barcodes": ["123456789015", "123456789016", "123456789017"]
    }
  ]
}
```

---

## API Reference - Add Item to Order

### Endpoint
`POST /api/orders/{id}/items`

### Request Body - Two Modes

#### Mode 1: Barcode Scanning (Counter/POS Orders)
```json
{
  "barcode": "123456789012"
}
```
OR multiple barcodes:
```json
{
  "barcodes": ["123456789012", "123456789013"]
}
```

**Optional fields:**
- `unit_price` - Override batch sell price
- `discount_amount` - Apply item discount

**Behavior:**
- Each barcode = 1 unit
- Validates barcode is active, not defective
- Validates batch has stock
- Validates barcode belongs to order's store
- Creates order item with `product_barcode_id` set

---

#### Mode 2: Product Selection (Social Commerce/E-commerce Orders)
```json
{
  "product_id": 1,
  "batch_id": 5,      // Optional - auto-selects FIFO batch if omitted
  "quantity": 2,
  "unit_price": 500.00,  // Optional - uses batch sell price if omitted
  "discount_amount": 0
}
```

**Required fields:**
- `product_id` - Product to add
- `quantity` - Number of units

**Optional fields:**
- `batch_id` - Specific batch to use (if omitted, uses FIFO: oldest batch with sufficient stock)
- `unit_price` - Price per unit (if omitted, uses batch sell price)
- `discount_amount` - Item discount

**Behavior:**
- Validates product exists
- If `batch_id` provided, validates it has sufficient stock at order's store
- If `batch_id` omitted, auto-selects oldest batch (FIFO) with sufficient stock
- Creates order item with `product_barcode_id` = NULL (assigned during fulfillment)
- If same product+batch already in order, increases quantity instead of creating new line

---

## Validation Rules

### Create Order - Items Array
```php
'items.*.product_id' => 'required|exists:products,id',
'items.*.batch_id' => 'nullable|exists:product_batches,id',  // Optional
'items.*.barcode' => 'nullable|string|exists:product_barcodes,barcode',  // Optional
'items.*.quantity' => 'required|integer|min:1',
'items.*.unit_price' => 'required|numeric|min:0',
```

### Add Item to Order
**Barcode scanning:**
```php
'barcode' => 'nullable|string|exists:product_barcodes,barcode',
'barcodes' => 'nullable|array|min:1',
'barcodes.*' => 'string|exists:product_barcodes,barcode',
```

**Product selection:**
```php
'product_id' => 'nullable|exists:products,id',
'batch_id' => 'nullable|exists:product_batches,id',
'quantity' => 'required_with:product_id|integer|min:1',
'unit_price' => 'nullable|numeric|min:0',
'discount_amount' => 'nullable|numeric|min:0',
```

**Rules:**
- Must provide EITHER barcode(s) OR product_id (not both)
- If providing product_id, must also provide quantity
- Cannot mix both methods in one request

---

## Order Status Flow

### Counter Orders
```
pending → completed (immediate)
```
- No fulfillment step (already fulfilled at POS)
- `fulfillment_status` = NULL

### Social Commerce / E-commerce Orders
```
pending → confirmed → assigned_to_store → picking → packing → ready_for_pickup → completed
         ↓
    fulfillment_status:
    pending_fulfillment → partially_fulfilled → fulfilled
```

---

## Frontend Implementation Guide

### POS/Counter System
```javascript
// Scan barcode and add to order
async function addItemByBarcode(orderId, barcode) {
  const response = await fetch(`/api/orders/${orderId}/items`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ barcode: barcode })
  });
  
  return await response.json();
}

// Or scan multiple barcodes at once
async function addItemsByBarcodes(orderId, barcodes) {
  const response = await fetch(`/api/orders/${orderId}/items`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ barcodes: barcodes })
  });
  
  return await response.json();
}
```

### Social Commerce / E-commerce System
```javascript
// Add item by product selection (no barcode)
async function addItemByProduct(orderId, productId, quantity, unitPrice) {
  const response = await fetch(`/api/orders/${orderId}/items`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId,
      quantity: quantity,
      unit_price: unitPrice
      // batch_id is optional - system auto-selects
    })
  });
  
  return await response.json();
}

// With specific batch
async function addItemByProductAndBatch(orderId, productId, batchId, quantity, unitPrice) {
  const response = await fetch(`/api/orders/${orderId}/items`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId,
      batch_id: batchId,
      quantity: quantity,
      unit_price: unitPrice
    })
  });
  
  return await response.json();
}
```

### Fulfillment (Warehouse System)
```javascript
// Warehouse employee scans barcodes to fulfill order
async function fulfillOrder(orderId, fulfillments) {
  const response = await fetch(`/api/orders/${orderId}/fulfill`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      fulfillments: [
        {
          order_item_id: 123,
          barcodes: ["123456789013", "123456789014"]  // Must match item quantity
        }
      ]
    })
  });
  
  return await response.json();
}
```

---

## Error Messages

### Common Errors

**1. Trying to use both methods:**
```json
{
  "success": false,
  "message": "Cannot provide both barcode and product_id. Choose one method."
}
```

**2. Neither method provided:**
```json
{
  "success": false,
  "message": "Please provide either barcode(s) or product_id to add item"
}
```

**3. Barcode not found:**
```json
{
  "success": false,
  "message": "Barcode 123456789999 not found"
}
```

**4. Insufficient stock:**
```json
{
  "success": false,
  "message": "No batch available with sufficient stock (5 units) at this store"
}
```

**5. Barcode inactive:**
```json
{
  "success": false,
  "message": "Barcode 123456789013 is not available (inactive)"
}
```

**6. Wrong store:**
```json
{
  "success": false,
  "message": "Product from batch BATCH-001 not available at this store"
}
```

---

## Testing

### Test 1: Counter Order (Barcode Required)
```bash
# Create counter order
curl -X POST http://localhost/api/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_type": "counter",
    "store_id": 1,
    "items": [
      {
        "product_id": 1,
        "batch_id": 5,
        "barcode": "123456789012",
        "quantity": 1,
        "unit_price": 500.00
      }
    ]
  }'

# Add item with barcode
curl -X POST http://localhost/api/orders/1/items \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"barcode": "123456789013"}'
```

### Test 2: Social Commerce Order (No Barcode)
```bash
# Create social commerce order (no barcode)
curl -X POST http://localhost/api/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_type": "social_commerce",
    "store_id": 1,
    "customer": {
      "name": "John Doe",
      "phone": "+8801712345678"
    },
    "items": [
      {
        "product_id": 1,
        "batch_id": 5,
        "quantity": 2,
        "unit_price": 500.00
      }
    ]
  }'

# Add item by product (no barcode)
curl -X POST http://localhost/api/orders/2/items \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "unit_price": 500.00
  }'

# Later - fulfill order (assign barcodes)
curl -X POST http://localhost/api/orders/2/fulfill \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fulfillments": [
      {
        "order_item_id": 10,
        "barcodes": ["123456789013", "123456789014"]
      }
    ]
  }'
```

---

## Summary of Changes

### Fixed Files
- **app/Http/Controllers/OrderController.php** - `addItem()` method

### What Changed
1. Made barcode optional in `addItem()` method
2. Added support for adding items by `product_id + quantity`
3. Auto-selects FIFO batch if `batch_id` not provided
4. Updates existing order item quantity if same product+batch already exists
5. Validates that only ONE method (barcode OR product) is used per request

### Backward Compatibility
✅ All existing counter order flows still work (barcode scanning)
✅ All existing social commerce/ecommerce order creation still works
✅ New: Can now edit social commerce/ecommerce orders without barcodes

---

**Status:** ✅ Issue Fixed
**Date:** December 15, 2025
**Tested:** Pending verification by FE team
