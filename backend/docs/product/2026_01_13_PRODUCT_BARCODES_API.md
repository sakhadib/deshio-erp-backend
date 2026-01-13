# Product Barcodes API Documentation

**Created:** January 13, 2026  
**Last Updated:** January 13, 2026  
**Version:** 1.0

---

## Overview

The Product Barcodes API manages unique barcode identifiers for products with comprehensive tracking capabilities. Each barcode can track its location, movement history, batch association, and current status across the entire supply chain.

---

## Architecture

### Barcode System Structure
```
Product
  └── Barcode 1: 1234567890123
      ├── Type: CODE128
      ├── Status: in_shop
      ├── Current Store: Main Store
      ├── Current Batch: Batch #1001
      ├── Movement History: [5 movements]
      └── Defective Status: No

  └── Barcode 2: 1234567890124 (Primary)
      ├── Type: EAN13
      ├── Status: with_customer
      ├── Order: #5678
      └── Sold Date: 2026-01-10
```

### Barcode Lifecycle States
- `in_warehouse` - In storage warehouse
- `in_shop` - On shop floor, available for sale
- `on_display` - On display/showcase
- `in_transit` - Being transferred between locations
- `in_shipment` - Out for delivery to customer
- `with_customer` - Sold and delivered
- `in_return` - Returned by customer
- `defective` - Marked as defective

---

## Base URL

```
/api/employee/barcodes
```

All endpoints require authentication via Bearer token.

---

## Barcode Operations

### 1. Scan Barcode

The primary endpoint for barcode scanning. Returns complete product and location information.

**Endpoint:** `POST /api/employee/barcodes/scan`

**Request Body:**
```json
{
  "barcode": "1234567890123"
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `barcode` | string | Yes | Valid barcode string |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "barcode_id": 10,
    "barcode": "1234567890123",
    "barcode_type": "CODE128",
    "is_defective": false,
    "product": {
      "id": 1,
      "name": "Running Shoes",
      "sku": "PROD-001",
      "description": "Premium running shoes",
      "category": {
        "id": 5,
        "name": "Footwear"
      },
      "vendor": {
        "id": 3,
        "name": "Sports Supplies Inc"
      }
    },
    "current_location": {
      "id": 1,
      "name": "Main Store",
      "address": "123 Main Street"
    },
    "current_batch": {
      "id": 100,
      "batch_number": "BATCH-1001",
      "quantity": 50,
      "cost_price": "45.00",
      "sell_price": "75.00",
      "status": "active",
      "expiry_date": "2027-12-31"
    },
    "is_available": true,
    "quantity_available": 50,
    "last_movement": {
      "type": "transfer",
      "from": "Warehouse A",
      "to": "Main Store",
      "date": "2026-01-10 14:30:00",
      "quantity": 50
    }
  }
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Barcode not found"
}
```

**Use Cases:**
- POS checkout scanning
- Inventory verification
- Stock receiving
- Product dispatch
- Returns processing

---

### 2. Batch Scan Multiple Barcodes

Scan multiple barcodes at once for inventory verification.

**Endpoint:** `POST /api/employee/barcodes/batch-scan`

**Request Body:**
```json
{
  "barcodes": [
    "1234567890123",
    "1234567890124",
    "1234567890125",
    "9999999999999"
  ]
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `barcodes` | array | Yes | Min 1 item |
| `barcodes.*` | string | Yes | Valid barcode string |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_scanned": 4,
    "found": 3,
    "not_found": 1,
    "results": [
      {
        "barcode": "1234567890123",
        "found": true,
        "product_name": "Running Shoes",
        "current_location": "Main Store",
        "quantity_available": 50
      },
      {
        "barcode": "1234567890124",
        "found": true,
        "product_name": "Basketball Shoes",
        "current_location": "Warehouse A",
        "quantity_available": 75
      },
      {
        "barcode": "1234567890125",
        "found": true,
        "product_name": "Tennis Shoes",
        "current_location": "Branch Store",
        "quantity_available": 30
      },
      {
        "barcode": "9999999999999",
        "found": false,
        "message": "Barcode not found in system"
      }
    ]
  }
}
```

**Use Cases:**
- Bulk inventory verification
- Stock count validation
- Receiving multiple items
- Dispatch verification

---

### 3. List All Barcodes

Get paginated list of barcodes with filters.

**Endpoint:** `GET /api/employee/barcodes`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 20) |
| `product_id` | integer | No | Filter by product |
| `type` | string | No | Filter by type (CODE128, EAN13, QR) |
| `is_active` | boolean | No | Filter by active status |
| `is_primary` | boolean | No | Filter by primary status |
| `search` | string | No | Search barcode value |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 10,
        "barcode": "1234567890123",
        "type": "CODE128",
        "is_primary": true,
        "is_active": true,
        "product": {
          "id": 1,
          "name": "Running Shoes",
          "sku": "PROD-001"
        },
        "current_location": "Main Store",
        "movement_count": 5,
        "generated_at": "2026-01-01 10:00:00"
      },
      {
        "id": 11,
        "barcode": "1234567890124",
        "type": "EAN13",
        "is_primary": false,
        "is_active": true,
        "product": {
          "id": 1,
          "name": "Running Shoes",
          "sku": "PROD-001"
        },
        "current_location": "Warehouse A",
        "movement_count": 3,
        "generated_at": "2026-01-02 14:30:00"
      }
    ],
    "first_page_url": "http://localhost:8000/api/employee/barcodes?page=1",
    "from": 1,
    "last_page": 10,
    "per_page": 20,
    "to": 20,
    "total": 195
  }
}
```

---

### 4. Generate Barcode(s)

Generate new barcode(s) for a product.

**Endpoint:** `POST /api/employee/barcodes/generate`

**Request Body:**
```json
{
  "product_id": 1,
  "type": "CODE128",
  "make_primary": false,
  "quantity": 5
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_id` | integer | Yes | Must exist in products table |
| `type` | string | No | CODE128, EAN13, QR (default: CODE128) |
| `make_primary` | boolean | No | Set as primary barcode (default: false) |
| `quantity` | integer | No | Number to generate (1-100, default: 1) |

**Barcode Types:**

| Type | Description | Format | Use Case |
|------|-------------|--------|----------|
| `CODE128` | Standard linear barcode | Numeric/Alphanumeric | General purpose |
| `EAN13` | International product code | 13 digits | Retail products |
| `QR` | 2D matrix barcode | Alphanumeric | Mobile scanning |

**Response (201 Created):**
```json
{
  "success": true,
  "message": "5 barcode(s) generated successfully",
  "data": {
    "product": {
      "id": 1,
      "name": "Running Shoes",
      "sku": "PROD-001"
    },
    "barcodes": [
      {
        "id": 15,
        "barcode": "123456789012",
        "type": "CODE128",
        "is_primary": true,
        "formatted": "123456789012"
      },
      {
        "id": 16,
        "barcode": "123456789013",
        "type": "CODE128",
        "is_primary": false,
        "formatted": "123456789013"
      },
      {
        "id": 17,
        "barcode": "123456789014",
        "type": "CODE128",
        "is_primary": false,
        "formatted": "123456789014"
      },
      {
        "id": 18,
        "barcode": "123456789015",
        "type": "CODE128",
        "is_primary": false,
        "formatted": "123456789015"
      },
      {
        "id": 19,
        "barcode": "123456789016",
        "type": "CODE128",
        "is_primary": false,
        "formatted": "123456789016"
      }
    ]
  }
}
```

**Notes:**
- Barcodes are automatically unique
- First barcode can be set as primary
- Generated barcodes are active by default
- Associated with current store context

---

### 5. Get Product Barcodes

Get all barcodes for a specific product.

**Endpoint:** `GET /api/employee/products/{productId}/barcodes`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Product ID |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "Running Shoes",
      "sku": "PROD-001"
    },
    "barcode_count": 3,
    "barcodes": [
      {
        "id": 10,
        "barcode": "1234567890123",
        "type": "CODE128",
        "is_primary": true,
        "is_active": true,
        "current_location": "Main Store",
        "movement_count": 5,
        "generated_at": "2026-01-01 10:00:00"
      },
      {
        "id": 11,
        "barcode": "1234567890124",
        "type": "EAN13",
        "is_primary": false,
        "is_active": true,
        "current_location": "Warehouse A",
        "movement_count": 3,
        "generated_at": "2026-01-02 14:30:00"
      },
      {
        "id": 12,
        "barcode": "1234567890125",
        "type": "CODE128",
        "is_primary": false,
        "is_active": false,
        "current_location": null,
        "movement_count": 0,
        "generated_at": "2026-01-03 09:15:00"
      }
    ]
  }
}
```

---

### 6. Make Barcode Primary

Set a barcode as the primary barcode for its product.

**Endpoint:** `PATCH /api/employee/barcodes/{id}/make-primary`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Barcode ID |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Barcode set as primary",
  "data": {
    "barcode": "1234567890124",
    "product_id": 1,
    "is_primary": true
  }
}
```

**Notes:**
- Previous primary barcode automatically set to non-primary
- Only one primary barcode per product
- Primary barcode shown first in listings

---

### 7. Deactivate Barcode

Deactivate a barcode (soft delete).

**Endpoint:** `DELETE /api/employee/barcodes/{id}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Barcode ID |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Barcode deactivated successfully"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Cannot deactivate the only active barcode for this product"
}
```

**Notes:**
- Cannot deactivate if it's the only active barcode
- Deactivated barcodes hidden from scans
- Can be reactivated if needed

---

## Location Tracking

### 8. Get Barcode Location History

Get complete movement history for a barcode.

**Endpoint:** `GET /api/employee/barcodes/{barcode}/history`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `barcode` | string | Yes | Barcode value |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "barcode": "1234567890123",
    "product": {
      "id": 1,
      "name": "Running Shoes",
      "sku": "PROD-001"
    },
    "movement_count": 5,
    "history": [
      {
        "id": 501,
        "type": "transfer",
        "from_store": {
          "id": 2,
          "name": "Warehouse A"
        },
        "to_store": {
          "id": 1,
          "name": "Main Store"
        },
        "batch": {
          "id": 100,
          "batch_number": "BATCH-1001"
        },
        "quantity": 50,
        "date": "2026-01-10 14:30:00",
        "reference": "DISP-2026-001",
        "notes": "Regular stock transfer",
        "performed_by": {
          "id": 5,
          "name": "John Doe"
        }
      },
      {
        "id": 502,
        "type": "sale",
        "from_store": {
          "id": 1,
          "name": "Main Store"
        },
        "to_store": null,
        "batch": {
          "id": 100,
          "batch_number": "BATCH-1001"
        },
        "quantity": 1,
        "date": "2026-01-11 16:45:00",
        "reference": "ORD-2026-5678",
        "notes": "Sold to customer",
        "performed_by": {
          "id": 7,
          "name": "Jane Smith"
        }
      }
    ]
  }
}
```

**Movement Types:**
- `sale` - Sold to customer
- `return` - Returned by customer
- `dispatch` - Dispatched between stores
- `transfer` - Internal transfer
- `adjustment` - Stock adjustment
- `defective` - Marked as defective

---

### 9. Get Current Barcode Location

Get the current location and batch of a barcode.

**Endpoint:** `GET /api/employee/barcodes/{barcode}/location`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `barcode` | string | Yes | Barcode value |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "barcode": "1234567890123",
    "product": {
      "id": 1,
      "name": "Running Shoes",
      "sku": "PROD-001"
    },
    "current_location": {
      "id": 1,
      "name": "Main Store",
      "address": "123 Main Street, City",
      "phone": "+1234567890"
    },
    "current_batch": {
      "id": 100,
      "batch_number": "BATCH-1001",
      "quantity_available": 48,
      "status": "active"
    }
  }
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Barcode not found"
}
```

---

## Advanced Features

### Barcode Defective Marking

Barcodes can be marked as defective through the ProductBarcode model:

```php
// Via model method
$barcode = ProductBarcode::where('barcode', '1234567890123')->first();
$barcode->markAsDefective([
    'reason' => 'Product damaged',
    'reported_by' => 'Employee Name',
    'notes' => 'Water damage detected'
]);
```

### Location Update Methods

Available in ProductBarcode model:

```php
// Move to warehouse
$barcode->moveToWarehouse($storeId, ['shelf' => 'A-5', 'bin' => '12']);

// Move to shop floor
$barcode->moveToShop($storeId, ['section' => 'Electronics']);

// Place on display
$barcode->placeOnDisplay($storeId, ['display_name' => 'Window Display']);

// Mark in transit
$barcode->markInTransit($toStoreId, $dispatchId);

// Mark in shipment
$barcode->markInShipment($shipmentId, $trackingNumber);

// Mark sold
$barcode->markSold($orderId, $customerId);

// Mark returned
$barcode->markReturned($returnId, $reason);
```

---

## Use Cases & Workflows

### Use Case 1: POS Checkout Flow

**Step 1: Scan barcode**
```http
POST /api/employee/barcodes/scan
{"barcode": "1234567890123"}
```

**Step 2: Verify availability**
```javascript
if (scanResult.data.is_available && 
    scanResult.data.quantity_available > 0) {
    // Add to cart
    addToCart(scanResult.data.product);
}
```

**Step 3: After sale**
```php
// System automatically creates movement record
// Barcode status updated to 'with_customer'
```

---

### Use Case 2: Stock Receiving

**Step 1: Scan multiple barcodes**
```http
POST /api/employee/barcodes/batch-scan
{
  "barcodes": ["123", "456", "789"]
}
```

**Step 2: Verify counts**
```javascript
if (scanResult.data.found === expectedCount) {
    // All items received
    confirmReceipt();
}
```

---

### Use Case 3: Inventory Audit

**Step 1: Get all barcodes for store**
```http
GET /api/employee/barcodes?per_page=100
```

**Step 2: Physical count scan**
```http
POST /api/employee/barcodes/batch-scan
{"barcodes": physicalCountBarcodes}
```

**Step 3: Compare results**
```javascript
const systemCount = allBarcodes.length;
const physicalCount = scanResult.data.found;
const discrepancy = systemCount - physicalCount;
```

---

### Use Case 4: Product Dispatch

**Step 1: Scan items for dispatch**
```http
POST /api/employee/barcodes/batch-scan
{"barcodes": dispatchItems}
```

**Step 2: Verify all found**
```javascript
if (scanResult.data.not_found === 0) {
    // All items ready for dispatch
    createDispatch(scanResult.data.results);
}
```

**Step 3: Update locations**
```php
// System automatically updates barcode status to 'in_transit'
// Creates movement records
```

---

## Best Practices

### 1. Barcode Generation
- Generate barcodes when product created
- Use appropriate barcode type for use case
- Set primary barcode for main product identifier
- Generate multiple for serialized items

### 2. Scanning Operations
- Always handle 404 responses (barcode not found)
- Check `is_available` before processing
- Verify `quantity_available` for stock
- Use batch scan for efficiency

### 3. Location Tracking
- Review movement history for disputes
- Monitor current location for inventory
- Use location metadata for detailed tracking
- Track performed_by for accountability

### 4. Primary Barcode
- Only one primary per product
- Use for labels and displays
- Can change if needed
- Never deactivate without replacement

### 5. Defective Items
- Mark as defective immediately
- Include detailed reason and notes
- Remove from available inventory
- Track for warranty/returns

---

## Error Handling

### Common Errors

**Barcode Not Found:**
```json
{
  "success": false,
  "message": "Barcode not found"
}
```

**Cannot Deactivate Last Barcode:**
```json
{
  "success": false,
  "message": "Cannot deactivate the only active barcode for this product"
}
```

**Invalid Barcode Format:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "barcode": ["The barcode field is required."]
  }
}
```

**Product Not Found:**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

## Integration Notes

### Frontend Scanner Integration

```javascript
// Barcode scanner handler
function handleBarcodeScan(scannedCode) {
  fetch('/api/employee/barcodes/scan', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ barcode: scannedCode })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      displayProduct(data.data);
      checkAvailability(data.data.is_available);
    } else {
      showError('Barcode not found');
    }
  });
}
```

### Print Barcode Labels

```javascript
// Generate printable barcode label
function printBarcodeLabel(barcodeData) {
  const label = {
    barcode: barcodeData.barcode,
    product_name: barcodeData.product.name,
    sku: barcodeData.product.sku,
    price: barcodeData.current_batch.sell_price,
    type: barcodeData.barcode_type  // For rendering
  };
  
  // Send to label printer
  printLabel(label);
}
```

---

## Related Documentation

- [Product API](./2026_01_13_PRODUCT_API.md) - Parent product management
- [Product Variants API](./2026_01_13_PRODUCT_VARIANTS_API.md) - Variant management
- [Product Batches API](./2026_01_13_PRODUCT_BATCHES_API.md) - Stock batch management
- [Dispatch System](../features/DISPATCH_BARCODE_SYSTEM.md) - Product dispatch tracking

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-13 | 1.0 | Initial comprehensive documentation |

---

## Support

For questions or issues with this API, contact the backend development team.
