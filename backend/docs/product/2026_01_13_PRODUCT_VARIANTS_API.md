# Product Variants API Documentation

**Created:** January 13, 2026  
**Last Updated:** January 13, 2026  
**Version:** 1.0

---

## Overview

The Product Variants API manages product variations such as different sizes, colors, and other attributes. Each variant has its own SKU, barcode, pricing, and stock quantities while inheriting common information from the parent product.

---

## Architecture

### Variant Structure
```
Product (Parent)
  └── Variant 1: Size XL, Color Red
      ├── Unique SKU: PROD-001-XL-RED
      ├── Unique Barcode: 1234567890123
      ├── Price Adjustment: +5.00
      ├── Cost Price: 45.00
      └── Stock: 25 units

  └── Variant 2: Size L, Color Blue
      ├── Unique SKU: PROD-001-L-BLU
      ├── Unique Barcode: 1234567890124
      ├── Price Adjustment: 0.00
      ├── Cost Price: 40.00
      └── Stock: 30 units
```

### Inherited vs Variant-Specific Fields

**Inherited from Parent Product:**
- Name
- Description
- Category
- Vendor
- Brand
- Parent SKU

**Variant-Specific:**
- Variant SKU (unique)
- Barcode (unique)
- Attributes (Size, Color, etc.)
- Price Adjustment
- Cost Price
- Stock Quantity
- Reserved Quantity
- Reorder Point
- Image URL
- Active Status

---

## Base URL

```
/api/employee/products/{productId}/variants
```

All endpoints require authentication via Bearer token.

---

## Variant CRUD Operations

### 1. List Variants

Get all variants for a product.

**Endpoint:** `GET /api/employee/products/{productId}/variants`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `is_active` | boolean | No | Filter by active status |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 100,
      "product_id": 1,
      "sku": "PROD-001-XL-RED",
      "barcode": "1234567890123",
      "attributes": {
        "Size": "XL",
        "Color": "Red"
      },
      "price_adjustment": 5.00,
      "cost_price": 45.00,
      "stock_quantity": 25,
      "reserved_quantity": 3,
      "reorder_point": 10,
      "image_url": "https://example.com/variant-xl-red.jpg",
      "is_active": true,
      "is_default": true,
      "created_at": "2026-01-01T10:00:00.000000Z",
      "updated_at": "2026-01-13T14:30:00.000000Z",
      "available_stock": 22,
      "final_price": 50.00,
      "variant_name": "Size: XL, Color: Red",
      "product": {
        "id": 1,
        "name": "Running Shoes",
        "sku": "PROD-001"
      }
    },
    {
      "id": 101,
      "product_id": 1,
      "sku": "PROD-001-L-BLU",
      "barcode": "1234567890124",
      "attributes": {
        "Size": "L",
        "Color": "Blue"
      },
      "price_adjustment": 0.00,
      "cost_price": 40.00,
      "stock_quantity": 30,
      "reserved_quantity": 5,
      "reorder_point": 10,
      "image_url": "https://example.com/variant-l-blue.jpg",
      "is_active": true,
      "is_default": false,
      "available_stock": 25,
      "final_price": 45.00,
      "variant_name": "Size: L, Color: Blue"
    }
  ]
}
```

---

### 2. Get Single Variant

Retrieve detailed variant information.

**Endpoint:** `GET /api/employee/products/{productId}/variants/{variantId}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |
| `variantId` | integer | Yes | Variant ID |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 100,
    "product_id": 1,
    "sku": "PROD-001-XL-RED",
    "barcode": "1234567890123",
    "attributes": {
      "Size": "XL",
      "Color": "Red"
    },
    "price_adjustment": 5.00,
    "cost_price": 45.00,
    "stock_quantity": 25,
    "reserved_quantity": 3,
    "reorder_point": 10,
    "image_url": "https://example.com/variant-xl-red.jpg",
    "is_active": true,
    "is_default": true,
    "created_at": "2026-01-01T10:00:00.000000Z",
    "updated_at": "2026-01-13T14:30:00.000000Z",
    "product": {
      "id": 1,
      "name": "Running Shoes",
      "sku": "PROD-001",
      "description": "Premium running shoes",
      "category": {
        "id": 5,
        "name": "Footwear"
      }
    }
  }
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Variant not found"
}
```

---

### 3. Create Variant

Create a new variant for a product.

**Endpoint:** `POST /api/employee/products/{productId}/variants`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |

**Request Body:**
```json
{
  "sku": "PROD-001-M-GRN",
  "barcode": "1234567890125",
  "attributes": {
    "Size": "M",
    "Color": "Green"
  },
  "price_adjustment": 2.50,
  "cost_price": 42.00,
  "stock_quantity": 20,
  "reorder_point": 10,
  "image_url": "https://example.com/variant-m-green.jpg",
  "is_default": false
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `sku` | string | Yes | Must be unique across all variants |
| `barcode` | string | No | Must be unique if provided |
| `attributes` | object | Yes | Key-value pairs (e.g., {"Size": "M"}) |
| `price_adjustment` | decimal | No | Can be negative |
| `cost_price` | decimal | No | Must be >= 0 |
| `stock_quantity` | integer | No | Must be >= 0 |
| `reorder_point` | integer | No | Must be >= 0 |
| `image_url` | string | No | Must be valid URL |
| `is_default` | boolean | No | Only one default per product |

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Variant created successfully",
  "data": {
    "id": 102,
    "product_id": 1,
    "sku": "PROD-001-M-GRN",
    "barcode": "1234567890125",
    "attributes": {
      "Size": "M",
      "Color": "Green"
    },
    "price_adjustment": 2.50,
    "cost_price": 42.00,
    "stock_quantity": 20,
    "reserved_quantity": 0,
    "reorder_point": 10,
    "is_active": true,
    "is_default": false,
    "created_at": "2026-01-13T15:00:00.000000Z",
    "product": {
      "id": 1,
      "name": "Running Shoes"
    }
  }
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "errors": {
    "sku": ["The sku has already been taken."],
    "attributes": ["The attributes field is required."]
  }
}
```

---

### 4. Update Variant

Update existing variant information.

**Endpoint:** `PUT /api/employee/products/{productId}/variants/{variantId}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |
| `variantId` | integer | Yes | Variant ID |

**Request Body:**
```json
{
  "price_adjustment": 3.00,
  "cost_price": 43.50,
  "stock_quantity": 30,
  "reorder_point": 15,
  "is_active": true
}
```

**Note:** All fields are optional. Only provided fields will be updated.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Variant updated successfully",
  "data": {
    "id": 100,
    "product_id": 1,
    "sku": "PROD-001-XL-RED",
    "price_adjustment": 3.00,
    "cost_price": 43.50,
    "stock_quantity": 30,
    "reorder_point": 15,
    "updated_at": "2026-01-13T15:30:00.000000Z"
  }
}
```

---

### 5. Delete Variant

Delete a variant (cannot delete default variant).

**Endpoint:** `DELETE /api/employee/products/{productId}/variants/{variantId}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |
| `variantId` | integer | Yes | Variant ID |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Variant deleted successfully"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Cannot delete default variant"
}
```

---

## Matrix Generation

### 6. Generate Variant Matrix

Automatically generate all possible variant combinations.

**Endpoint:** `POST /api/employee/products/{productId}/variants/generate-matrix`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |

**Request Body:**
```json
{
  "attributes": {
    "Size": ["S", "M", "L", "XL"],
    "Color": ["Red", "Blue", "Green"]
  },
  "base_price_adjustment": 0.00
}
```

**Process:**
The system will generate all combinations:
- S + Red
- S + Blue
- S + Green
- M + Red
- M + Blue
- ... (12 total combinations)

**Auto-Generated SKU Format:**
```
{parent_sku}-{first_2_letters_of_value_1}-{first_2_letters_of_value_2}
Example: PROD-001-S-RE (Size: S, Color: Red)
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "12 variants generated",
  "data": [
    {
      "id": 103,
      "product_id": 1,
      "sku": "PROD-001-S-RE",
      "attributes": {
        "Size": "S",
        "Color": "Red"
      },
      "price_adjustment": 0.00,
      "stock_quantity": 0
    },
    {
      "id": 104,
      "product_id": 1,
      "sku": "PROD-001-S-BL",
      "attributes": {
        "Size": "S",
        "Color": "Blue"
      },
      "price_adjustment": 0.00,
      "stock_quantity": 0
    }
    // ... 10 more variants
  ]
}
```

**Notes:**
- Skips combinations that already exist
- Sets stock to 0 (must be updated manually)
- Uses base_price_adjustment for all variants
- Does NOT generate barcodes automatically

---

## Variant Options Management

### 7. Get Variant Options

Get available variant option values (sizes, colors, etc.).

**Endpoint:** `GET /api/employee/variant-options`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | No | Filter by option name (Size, Color) |
| `type` | string | No | Filter by type (text, color, image) |
| `is_active` | boolean | No | Filter by active status |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "Size": [
      {
        "id": 1,
        "name": "Size",
        "value": "S",
        "type": "text",
        "display_value": "Small",
        "sort_order": 1,
        "is_active": true
      },
      {
        "id": 2,
        "name": "Size",
        "value": "M",
        "type": "text",
        "display_value": "Medium",
        "sort_order": 2,
        "is_active": true
      }
    ],
    "Color": [
      {
        "id": 10,
        "name": "Color",
        "value": "Red",
        "type": "color",
        "display_value": "#FF0000",
        "sort_order": 1,
        "is_active": true
      },
      {
        "id": 11,
        "name": "Color",
        "value": "Blue",
        "type": "color",
        "display_value": "#0000FF",
        "sort_order": 2,
        "is_active": true
      }
    ]
  }
}
```

---

### 8. Create Variant Option

Add a new variant option value.

**Endpoint:** `POST /api/employee/variant-options`

**Request Body:**
```json
{
  "name": "Size",
  "value": "XXL",
  "type": "text",
  "display_value": "Extra Extra Large",
  "sort_order": 5
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | Yes | Max 100 characters (Size, Color, etc.) |
| `value` | string | Yes | Max 100 characters (S, M, Red, etc.) |
| `type` | string | Yes | text, color, image |
| `display_value` | string | No | Display label or hex color |
| `sort_order` | integer | No | For ordering options |

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Variant option created successfully",
  "data": {
    "id": 20,
    "name": "Size",
    "value": "XXL",
    "type": "text",
    "display_value": "Extra Extra Large",
    "sort_order": 5,
    "is_active": true
  }
}
```

---

## Statistics

### 9. Get Variant Statistics

Get statistics for all variants of a product.

**Endpoint:** `GET /api/employee/products/{productId}/variants/statistics`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `productId` | integer | Yes | Parent product ID |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_variants": 12,
    "active_variants": 10,
    "low_stock_variants": 3,
    "total_stock": 245,
    "total_reserved": 28,
    "available_stock": 217
  }
}
```

---

## Computed Attributes

### Available in Variant Objects

**available_stock:**
```php
available_stock = stock_quantity - reserved_quantity
```

**final_price:**
```php
final_price = parent_product.selling_price + price_adjustment
```

**variant_name:**
```php
variant_name = "Size: XL, Color: Red"  // Formatted from attributes
```

---

## Use Cases & Workflows

### Use Case 1: Creating Fashion Product with Size/Color Matrix

**Step 1: Create Parent Product**
```http
POST /api/employee/products
{
  "name": "Cotton T-Shirt",
  "sku": "TSHIRT-001",
  "category_id": 10
}
```

**Step 2: Generate Variant Matrix**
```http
POST /api/employee/products/1/variants/generate-matrix
{
  "attributes": {
    "Size": ["S", "M", "L", "XL"],
    "Color": ["White", "Black", "Navy"]
  }
}
```

**Step 3: Update Individual Variant Prices/Stock**
```http
PUT /api/employee/products/1/variants/100
{
  "cost_price": 12.00,
  "stock_quantity": 50
}
```

---

### Use Case 2: Managing Single Product with Multiple Attributes

**Create Variant Manually:**
```http
POST /api/employee/products/1/variants
{
  "sku": "LAPTOP-001-16GB-512GB",
  "attributes": {
    "RAM": "16GB",
    "Storage": "512GB SSD"
  },
  "price_adjustment": 150.00,
  "cost_price": 750.00,
  "stock_quantity": 10
}
```

---

### Use Case 3: Setting Default Variant

When creating/updating a variant with `is_default: true`:
- All other variants' `is_default` set to `false`
- Only one default variant per product
- Default variant shown first in listings

---

## Best Practices

### 1. SKU Naming Convention
- Use parent SKU as prefix
- Add attribute abbreviations
- Keep consistent format
- Example: `PROD-001-XL-RED`

### 2. Price Management
- Use `price_adjustment` for variants
- Negative values for discounts
- Positive values for premium options
- Base price stored in parent product

### 3. Stock Management
- Set `reorder_point` for low stock alerts
- Monitor `reserved_quantity` (pending orders)
- Use `available_stock` for sell-able quantity
- Update stock via batches, not directly

### 4. Matrix Generation
- Plan attribute combinations before generation
- Review and update generated variants
- Set prices and stock after generation
- Generate barcodes separately

### 5. Default Variant
- Always have one default variant
- Default shown in product listings
- Cannot delete default variant
- Set most popular as default

---

## Error Handling

### Common Errors

**Cannot Delete Default Variant:**
```json
{
  "success": false,
  "message": "Cannot delete default variant"
}
```

**Duplicate SKU:**
```json
{
  "success": false,
  "errors": {
    "sku": ["The sku has already been taken."]
  }
}
```

**Invalid Parent Product:**
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Missing Attributes:**
```json
{
  "success": false,
  "errors": {
    "attributes": ["The attributes field is required."]
  }
}
```

---

## Integration Notes

### Frontend Display Example
```javascript
// Display variant selector
variants.forEach(variant => {
  const price = parentProduct.base_price + variant.price_adjustment;
  const label = `${variant.variant_name} - $${price}`;
  const inStock = variant.available_stock > 0;
  
  // Show option with price and stock status
  addOption(label, variant.id, inStock);
});
```

### Stock Validation
```javascript
// Before adding to cart
if (variant.available_stock < requestedQuantity) {
  showError('Insufficient stock');
  return;
}

// Check against available_stock, not stock_quantity
// available_stock = stock_quantity - reserved_quantity
```

---

## Related Documentation

- [Product API](./2026_01_13_PRODUCT_API.md) - Parent product management
- [Product Barcodes API](./2026_01_13_PRODUCT_BARCODES_API.md) - Barcode generation
- [Product Batches API](./2026_01_13_PRODUCT_BATCHES_API.md) - Stock management
- [Product Common Info Update](../features/PRODUCT_COMMON_INFO_UPDATE_API.md) - Bulk updates

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-13 | 1.0 | Initial comprehensive documentation |

---

## Support

For questions or issues with this API, contact the backend development team.
