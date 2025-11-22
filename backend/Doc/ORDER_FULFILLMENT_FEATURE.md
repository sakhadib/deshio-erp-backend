# Order Fulfillment Feature

## Overview
This document describes the new **warehouse fulfillment workflow** implemented for social commerce and e-commerce orders.

## Problem Statement
**Social Commerce Scenario:**
- Employee selling via social media (Facebook, WhatsApp) works **from home**
- No physical access to products during order creation
- Cannot scan barcodes at point of sale
- Need separate fulfillment step at warehouse

**Solution:**
Two-step order process:
1. **Create Order** - Social commerce employee creates order WITHOUT barcodes
2. **Fulfill Order** - Warehouse staff scans barcodes at end of day to fulfill order

---

## Order Types & Fulfillment Requirements

| Order Type | Fulfillment Required | Barcode Scanning |
|-----------|---------------------|------------------|
| `counter` | ❌ No | At POS during order creation |
| `social_commerce` | ✅ Yes | At warehouse after order creation |
| `ecommerce` | ✅ Yes | At warehouse after order creation |

---

## Fulfillment Status Values

| Status | Description |
|--------|-------------|
| `null` | Counter orders (no fulfillment needed) |
| `pending_fulfillment` | Awaiting barcode scanning at warehouse |
| `fulfilled` | Barcodes scanned, ready for shipment |

---

## Database Schema Changes

### Migration: `add_fulfillment_status_to_orders_table`

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('fulfillment_status', 30)->nullable()
          ->after('payment_status')
          ->comment('null=not_required, pending_fulfillment, fulfilled');
    
    $table->timestamp('fulfilled_at')->nullable();
    $table->foreignId('fulfilled_by')->nullable()
          ->constrained('employees')
          ->onDelete('set null');
});
```

**New Columns:**
- `fulfillment_status` - Tracks fulfillment state
- `fulfilled_at` - Timestamp when order was fulfilled
- `fulfilled_by` - Employee who scanned barcodes

---

## Order Lifecycle Flow

### Counter Order (Immediate)
```
1. Create Order (scan barcodes at POS)
   └─> fulfillment_status: null
   └─> order_items.product_barcode_id: assigned

2. Complete Order
   └─> Reduces inventory
   └─> Marks barcodes as sold
```

### Social Commerce / E-commerce (Deferred)
```
1. Create Order (NO barcodes)
   └─> fulfillment_status: pending_fulfillment
   └─> order_items.product_barcode_id: null

2. Fulfill Order (warehouse scans barcodes)
   └─> fulfillment_status: fulfilled
   └─> order_items.product_barcode_id: assigned
   └─> fulfilled_at: timestamp
   └─> fulfilled_by: employee_id

3. Complete Order
   └─> Reduces inventory
   └─> Marks barcodes as sold

4. Create Shipment (optional)
   └─> Integrates with Pathao for delivery
```

---

## API Endpoints

### 1. Create Order
**POST** `/api/orders`

**Request Body:**
```json
{
  "order_type": "social_commerce",
  "customer": {
    "name": "John Doe",
    "phone": "01712345678",
    "address": "Dhaka, Bangladesh"
  },
  "store_id": 1,
  "items": [
    {
      "product_id": 10,
      "batch_id": 25,
      "quantity": 2,
      "unit_price": 1500.00
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_number": "ORD-2025-001234",
    "fulfillment_status": "pending_fulfillment",
    "status": "pending",
    "total_amount": 3000.00,
    "note": "This order requires warehouse fulfillment before shipment"
  }
}
```

---

### 2. Fulfill Order (NEW)
**PATCH** `/api/orders/{id}/fulfill`

**Request Body:**
```json
{
  "fulfillments": [
    {
      "order_item_id": 123,
      "barcodes": ["BARCODE-001", "BARCODE-002"]
    },
    {
      "order_item_id": 124,
      "barcodes": ["BARCODE-003"]
    }
  ]
}
```

**Important:**
- Number of barcodes must match item quantity
- All barcodes must be active (not sold/defective)
- Barcodes must belong to correct product/batch
- Barcodes must belong to correct store

**Response:**
```json
{
  "success": true,
  "message": "Order fulfilled successfully. Ready for shipment.",
  "data": {
    "order_number": "ORD-2025-001234",
    "fulfillment_status": "fulfilled",
    "fulfilled_at": "2025-01-22 15:30:45",
    "fulfilled_by": "Warehouse Staff Name",
    "fulfilled_items": [
      {
        "item_id": 123,
        "product_name": "Product A",
        "original_quantity": 2,
        "barcodes": ["BARCODE-001", "BARCODE-002"]
      }
    ],
    "next_step": "Create shipment for delivery"
  }
}
```

**Error Responses:**

*Order doesn't require fulfillment:*
```json
{
  "success": false,
  "message": "This order type does not require fulfillment. Counter orders are fulfilled immediately."
}
```

*Order already fulfilled:*
```json
{
  "success": false,
  "message": "Order cannot be fulfilled. Current status: pending, Fulfillment status: fulfilled"
}
```

*Barcode quantity mismatch:*
```json
{
  "success": false,
  "message": "Fulfillment failed: Item 'Product A' requires 2 barcode(s), but 1 provided"
}
```

*Invalid barcode:*
```json
{
  "success": false,
  "message": "Fulfillment failed: Barcode BARCODE-001 is not active (already sold or deactivated)"
}
```

---

### 3. Complete Order (UPDATED)
**PATCH** `/api/orders/{id}/complete`

**New Validation:**
- For social_commerce/ecommerce orders: Must be fulfilled before completion
- Returns error if not fulfilled

**Error Response:**
```json
{
  "success": false,
  "message": "Order must be fulfilled before completion. Please scan barcodes at warehouse first.",
  "hint": "Call POST /api/orders/123/fulfill with barcode scans"
}
```

---

## Order Model Methods

### Fulfillment Status Checks
```php
// Check if order needs fulfillment
$order->needsFulfillment(); // true for social_commerce/ecommerce

// Check if order is pending fulfillment
$order->isPendingFulfillment(); // true if status = pending_fulfillment

// Check if order is fulfilled
$order->isFulfilled(); // true if status = fulfilled

// Check if order can be fulfilled now
$order->canBeFulfilled(); // validates eligibility
```

### Fulfill Order
```php
$employee = Employee::find(auth()->id());
$order->fulfill($employee);
```

### Relationships
```php
$order->fulfilledBy; // Employee who fulfilled the order
```

---

## Frontend Implementation Guide

### 1. Order Creation UI
**For Social Commerce:**
```javascript
// Create order WITHOUT barcode field
const createSocialOrder = async (orderData) => {
  const response = await api.post('/orders', {
    order_type: 'social_commerce',
    customer: orderData.customer,
    store_id: orderData.storeId,
    items: orderData.items.map(item => ({
      product_id: item.productId,
      batch_id: item.batchId,
      quantity: item.quantity,
      unit_price: item.unitPrice,
      // NO barcode field for social commerce
    }))
  });
  
  if (response.data.data.fulfillment_status === 'pending_fulfillment') {
    showNotification('Order created. Warehouse fulfillment required.');
  }
  
  return response.data;
};
```

### 2. Warehouse Fulfillment UI
**Barcode Scanner Interface:**
```javascript
const fulfillOrder = async (orderId, fulfillments) => {
  try {
    const response = await api.patch(`/orders/${orderId}/fulfill`, {
      fulfillments: fulfillments
    });
    
    showSuccess('Order fulfilled successfully');
    return response.data;
  } catch (error) {
    if (error.response?.data?.message) {
      showError(error.response.data.message);
    }
    throw error;
  }
};

// Example usage:
const scannedFulfillments = [
  {
    order_item_id: 123,
    barcodes: ['BARCODE-001', 'BARCODE-002'] // Scan with handheld scanner
  }
];

await fulfillOrder(orderId, scannedFulfillments);
```

### 3. Order Status Display
```javascript
const getStatusBadge = (order) => {
  if (order.fulfillment_status === 'pending_fulfillment') {
    return {
      text: 'Awaiting Fulfillment',
      color: 'warning',
      icon: 'warehouse',
      action: 'Scan Barcodes'
    };
  }
  
  if (order.fulfillment_status === 'fulfilled') {
    return {
      text: 'Fulfilled',
      color: 'success',
      icon: 'check-circle',
      action: 'Ready for Shipment'
    };
  }
  
  // Counter orders (no fulfillment)
  return {
    text: 'Processed',
    color: 'info',
    icon: 'receipt'
  };
};
```

### 4. Order Completion Validation
```javascript
const completeOrder = async (orderId) => {
  try {
    const response = await api.patch(`/orders/${orderId}/complete`);
    showSuccess('Order completed successfully');
    return response.data;
  } catch (error) {
    if (error.response?.status === 422) {
      // Fulfillment required
      showError(error.response.data.message);
      showHint(error.response.data.hint);
      
      // Redirect to fulfillment page
      router.push(`/orders/${orderId}/fulfill`);
    }
    throw error;
  }
};
```

---

## Workflow Examples

### Social Commerce Order (WhatsApp Sale)

**Step 1: Employee creates order from home**
```bash
POST /api/orders
{
  "order_type": "social_commerce",
  "customer": {"name": "Customer A", "phone": "01712345678"},
  "store_id": 1,
  "items": [
    {"product_id": 10, "batch_id": 25, "quantity": 2, "unit_price": 1500}
  ]
}

Response:
{
  "order_number": "ORD-2025-001234",
  "fulfillment_status": "pending_fulfillment" ✅
}
```

**Step 2: Warehouse staff scans barcodes at end of day**
```bash
PATCH /api/orders/1234/fulfill
{
  "fulfillments": [
    {
      "order_item_id": 123,
      "barcodes": ["789012345023", "789012345024"]
    }
  ]
}

Response:
{
  "fulfillment_status": "fulfilled" ✅,
  "fulfilled_at": "2025-01-22 18:00:00",
  "fulfilled_by": "Warehouse Manager"
}
```

**Step 3: Complete order and reduce inventory**
```bash
PATCH /api/orders/1234/complete

Response:
{
  "status": "completed" ✅,
  "inventory_reduced": true
}
```

---

## Testing Checklist

### Counter Order (No Fulfillment)
- [ ] Create counter order with barcodes
- [ ] Verify `fulfillment_status` is `null`
- [ ] Complete order immediately
- [ ] Verify inventory reduced

### Social Commerce Order (With Fulfillment)
- [ ] Create social order WITHOUT barcodes
- [ ] Verify `fulfillment_status` is `pending_fulfillment`
- [ ] Try to complete order → Should fail with fulfillment error
- [ ] Fulfill order by scanning barcodes
- [ ] Verify `fulfillment_status` is `fulfilled`
- [ ] Verify barcodes assigned to order items
- [ ] Complete order successfully
- [ ] Verify inventory reduced

### E-commerce Order (With Fulfillment)
- [ ] Create ecommerce order WITHOUT barcodes
- [ ] Verify `fulfillment_status` is `pending_fulfillment`
- [ ] Fulfill order
- [ ] Create shipment with Pathao
- [ ] Complete order

### Fulfillment Validations
- [ ] Try to fulfill with wrong quantity of barcodes → Error
- [ ] Try to fulfill with inactive barcode → Error
- [ ] Try to fulfill with defective barcode → Error
- [ ] Try to fulfill with barcode from wrong store → Error
- [ ] Try to fulfill with barcode from wrong product → Error
- [ ] Try to fulfill already fulfilled order → Error
- [ ] Try to fulfill counter order → Error

---

## Barcode Scanning Best Practices

### Handheld Scanner Setup
1. Configure scanner to send Enter/Tab after each scan
2. Use batch scanning mode for multiple units
3. Validate barcode format before submitting

### Mobile Scanner (Optional)
```javascript
// Using ZXing or similar barcode scanning library
const scanBarcode = async () => {
  const result = await BarcodeScanner.scan();
  if (result.text) {
    addBarcodeToFulfillment(result.text);
  }
};
```

### Error Handling
- Always validate barcode exists before submitting
- Show clear error messages for invalid barcodes
- Allow manual barcode entry as fallback
- Log all scan attempts for audit trail

---

## Business Rules

1. **Counter Orders:**
   - Barcodes scanned at POS during creation
   - No fulfillment step required
   - Inventory reduced immediately on completion

2. **Social Commerce Orders:**
   - Created WITHOUT barcodes (employee works remotely)
   - MUST be fulfilled at warehouse before completion
   - Fulfillment assigns physical units to order

3. **E-commerce Orders:**
   - Created WITHOUT barcodes (online orders)
   - MUST be fulfilled at warehouse before completion
   - Can create shipment after fulfillment

4. **Barcode Assignment:**
   - Counter: During order creation
   - Social/Ecommerce: During fulfillment

5. **Inventory Reduction:**
   - Happens during `complete()` for ALL order types
   - NOT during fulfillment (fulfillment only assigns barcodes)

---

## Migration Path

### Existing Orders
- All existing orders have `fulfillment_status = null`
- They can be completed normally (backward compatible)
- No migration needed for historical data

### New Orders (After Feature Deployment)
- Counter orders: `fulfillment_status = null` (no change)
- Social/Ecommerce: `fulfillment_status = pending_fulfillment`
- Frontend must handle fulfillment workflow

---

## Troubleshooting

### Error: "Order must be fulfilled before completion"
**Cause:** Social/ecommerce order not fulfilled yet  
**Solution:** Call `/orders/{id}/fulfill` endpoint with barcodes

### Error: "Barcode not active"
**Cause:** Barcode already sold or deactivated  
**Solution:** Scan different barcode from same batch

### Error: "Insufficient stock"
**Cause:** Batch doesn't have enough units  
**Solution:** Check inventory, rebalance stock, or cancel order

### Error: "Barcode belongs to different store"
**Cause:** Barcode from wrong store location  
**Solution:** Use barcode from correct store's inventory

---

## Support & Questions

For technical support or questions about the fulfillment feature, contact:
- Backend Team: [Your Team Contact]
- API Documentation: `/api/documentation`
- Postman Collection: [Link to collection]

---

**Last Updated:** January 22, 2025  
**Version:** 1.0  
**Status:** ✅ Implemented & Tested
