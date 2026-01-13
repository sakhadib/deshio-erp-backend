# Product Management API Documentation

**Created:** January 13, 2026  
**Last Updated:** January 13, 2026  
**Version:** 1.0

---

## Overview

The Product Management API provides comprehensive endpoints for managing products in the ERP system. Products support custom fields, multiple images, variants, batches, and barcodes for complete inventory tracking.

---

## Architecture

### Product Hierarchy
```
Product (Parent)
├── Common Info: name, description, category, vendor, brand, SKU
├── Custom Fields: Dynamic product attributes
├── Images: Multiple images with primary/active status
├── Variants: Size/color combinations with individual pricing/stock
├── Barcodes: Unique identifiers with location tracking
└── Batches: Stock quantities with pricing and expiry dates
```

### Key Models
- **Product**: Parent product with common information
- **ProductField**: Custom dynamic fields
- **ProductImage**: Product images
- **ProductVariant**: Size/color variations
- **ProductBarcode**: Unique barcode identifiers
- **ProductBatch**: Stock batches with pricing

---

## Base URL

```
/api/employee/products
```

All endpoints require authentication via Bearer token.

---

## Product CRUD Operations

### 1. List Products

Get paginated list of products with filters.

**Endpoint:** `GET /api/employee/products`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |
| `search` | string | No | Search by name, SKU, description |
| `category_id` | integer | No | Filter by category |
| `vendor_id` | integer | No | Filter by vendor |
| `sku` | string | No | Filter by SKU |
| `is_archived` | boolean | No | Show archived products |
| `sort_by` | string | No | Sort field (name, created_at) |
| `sort_order` | string | No | Sort direction (asc, desc) |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "category_id": 5,
        "vendor_id": 3,
        "brand": "Nike",
        "sku": "PROD-001",
        "name": "Running Shoes",
        "description": "Premium running shoes",
        "is_archived": false,
        "created_at": "2026-01-01T10:00:00.000000Z",
        "updated_at": "2026-01-13T14:30:00.000000Z",
        "category": {
          "id": 5,
          "name": "Footwear"
        },
        "vendor": {
          "id": 3,
          "name": "Sports Supplies Inc"
        },
        "custom_fields": [
          {
            "field_id": 1,
            "field_title": "Material",
            "field_type": "text",
            "value": "Synthetic Leather"
          }
        ],
        "images": [
          {
            "id": 10,
            "image_url": "https://example.com/image.jpg",
            "is_primary": true,
            "is_active": true
          }
        ],
        "variants_count": 12,
        "total_stock": 245
      }
    ],
    "first_page_url": "http://localhost:8000/api/employee/products?page=1",
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 67
  }
}
```

---

### 2. Get Single Product

Retrieve detailed product information.

**Endpoint:** `GET /api/employee/products/{id}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Product ID |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "category_id": 5,
    "vendor_id": 3,
    "brand": "Nike",
    "sku": "PROD-001",
    "name": "Running Shoes",
    "description": "Premium running shoes with air cushioning",
    "is_archived": false,
    "created_at": "2026-01-01T10:00:00.000000Z",
    "updated_at": "2026-01-13T14:30:00.000000Z",
    "category": {
      "id": 5,
      "name": "Footwear",
      "slug": "footwear"
    },
    "vendor": {
      "id": 3,
      "name": "Sports Supplies Inc",
      "email": "contact@sportssupplies.com"
    },
    "product_fields": [
      {
        "id": 1,
        "product_id": 1,
        "field_id": 1,
        "value": "Synthetic Leather",
        "field": {
          "id": 1,
          "title": "Material",
          "slug": "material",
          "type": "text"
        }
      }
    ],
    "images": [
      {
        "id": 10,
        "product_id": 1,
        "image_url": "https://example.com/image1.jpg",
        "alt_text": "Front view",
        "is_primary": true,
        "is_active": true,
        "sort_order": 1
      },
      {
        "id": 11,
        "product_id": 1,
        "image_url": "https://example.com/image2.jpg",
        "alt_text": "Side view",
        "is_primary": false,
        "is_active": true,
        "sort_order": 2
      }
    ],
    "variants": [
      {
        "id": 100,
        "product_id": 1,
        "sku": "PROD-001-42-BLK",
        "barcode": "1234567890123",
        "attributes": {
          "Size": "42",
          "Color": "Black"
        },
        "price_adjustment": 0.00,
        "cost_price": 45.00,
        "stock_quantity": 25,
        "reserved_quantity": 3,
        "available_stock": 22,
        "is_active": true,
        "is_default": true
      }
    ],
    "custom_fields": [
      {
        "field_id": 1,
        "field_title": "Material",
        "field_type": "text",
        "value": "Synthetic Leather",
        "raw_value": "Synthetic Leather"
      }
    ]
  }
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

### 3. Create Product

Create a new product with custom fields.

**Endpoint:** `POST /api/employee/products`

**Request Body:**
```json
{
  "category_id": 5,
  "vendor_id": 3,
  "brand": "Nike",
  "sku": "PROD-002",
  "name": "Basketball Shoes",
  "description": "High-top basketball shoes",
  "custom_fields": [
    {
      "field_id": 1,
      "value": "Genuine Leather"
    },
    {
      "field_id": 2,
      "value": "USA"
    }
  ]
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `category_id` | integer | Yes | Must exist in categories |
| `vendor_id` | integer | No | Must exist in vendors |
| `brand` | string | No | Max 255 characters |
| `sku` | string | Yes | Not unique (supports variations) |
| `name` | string | Yes | Max 255 characters |
| `description` | text | No | - |
| `custom_fields` | array | No | - |
| `custom_fields.*.field_id` | integer | Yes | Must exist, no duplicates |
| `custom_fields.*.value` | mixed | No | Validated by field type |

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 2,
    "category_id": 5,
    "vendor_id": 3,
    "brand": "Nike",
    "sku": "PROD-002",
    "name": "Basketball Shoes",
    "description": "High-top basketball shoes",
    "is_archived": false,
    "created_at": "2026-01-13T15:00:00.000000Z",
    "updated_at": "2026-01-13T15:00:00.000000Z",
    "custom_fields": [
      {
        "field_id": 1,
        "field_title": "Material",
        "field_type": "text",
        "value": "Genuine Leather"
      }
    ]
  }
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "category_id": ["The selected category id is invalid."]
  }
}
```

---

### 4. Update Product

Update existing product common information.

**Endpoint:** `PUT /api/employee/products/{id}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Product ID |

**Request Body:**
```json
{
  "name": "Updated Product Name",
  "description": "Updated description",
  "category_id": 6,
  "vendor_id": 4,
  "brand": "Adidas",
  "custom_fields": [
    {
      "field_id": 1,
      "value": "Cotton Blend"
    }
  ]
}
```

**Note:** All fields are optional. Only provided fields will be updated.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Product Name",
    "description": "Updated description",
    "category_id": 6,
    "vendor_id": 4,
    "brand": "Adidas",
    "updated_at": "2026-01-13T15:30:00.000000Z"
  }
}
```

---

### 5. Delete Product

Permanently delete a product (only if no batches/orders exist).

**Endpoint:** `DELETE /api/employee/products/{id}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Product ID |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Cannot delete product with existing batches. Archive it instead."
}
```

---

### 6. Archive Product

Soft delete a product (hide from active lists).

**Endpoint:** `PATCH /api/employee/products/{id}/archive`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product archived successfully",
  "data": {
    "id": 1,
    "is_archived": true
  }
}
```

---

### 7. Restore Product

Restore an archived product.

**Endpoint:** `PATCH /api/employee/products/{id}/restore`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product restored successfully",
  "data": {
    "id": 1,
    "is_archived": false
  }
}
```

---

## Bulk Operations

### 8. Bulk Update Products

Update multiple products at once.

**Endpoint:** `POST /api/employee/products/bulk-update`

**Request Body:**
```json
{
  "product_ids": [1, 2, 3, 4, 5],
  "action": "update_category",
  "category_id": 10
}
```

**Available Actions:**

#### Archive Multiple Products
```json
{
  "product_ids": [1, 2, 3],
  "action": "archive"
}
```

#### Restore Multiple Products
```json
{
  "product_ids": [1, 2, 3],
  "action": "restore"
}
```

#### Update Category
```json
{
  "product_ids": [1, 2, 3],
  "action": "update_category",
  "category_id": 10
}
```

#### Update Vendor
```json
{
  "product_ids": [1, 2, 3],
  "action": "update_vendor",
  "vendor_id": 5
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_ids` | array | Yes | All IDs must exist |
| `action` | string | Yes | archive, restore, update_category, update_vendor |
| `category_id` | integer | Conditional | Required if action=update_category |
| `vendor_id` | integer | Conditional | Required if action=update_vendor |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Updated category for 5 products"
}
```

---

## Custom Fields Management

### 9. Get Available Fields

Get list of all custom fields that can be assigned to products.

**Endpoint:** `GET /api/employee/products/available-fields`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Material",
      "slug": "material",
      "type": "text",
      "is_required": false,
      "is_active": true,
      "options": null
    },
    {
      "id": 2,
      "title": "Country of Origin",
      "slug": "country",
      "type": "select",
      "is_required": false,
      "is_active": true,
      "options": ["USA", "China", "Bangladesh", "Vietnam"]
    }
  ]
}
```

---

### 10. Update Custom Field

Update or create a custom field value for a product.

**Endpoint:** `POST /api/employee/products/{id}/custom-fields`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Product ID |

**Request Body:**
```json
{
  "field_id": 1,
  "value": "100% Cotton"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Custom field updated successfully",
  "data": {
    "field_id": 1,
    "field_title": "Material",
    "value": "100% Cotton"
  }
}
```

---

### 11. Remove Custom Field

Remove a custom field from a product.

**Endpoint:** `DELETE /api/employee/products/{id}/custom-fields/{fieldId}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Product ID |
| `fieldId` | integer | Yes | Field ID |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Custom field removed successfully"
}
```

---

### 12. Search by Custom Field

Find products by custom field values.

**Endpoint:** `POST /api/employee/products/search-by-field`

**Request Body:**
```json
{
  "field_id": 1,
  "value": "Cotton",
  "operator": "like"
}
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `field_id` | integer | Yes | Custom field ID |
| `value` | mixed | Yes | Search value |
| `operator` | string | No | =, like, >, <, >=, <= (default: like) |
| `per_page` | integer | No | Results per page (default: 15) |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "T-Shirt",
        "sku": "TSHIRT-001",
        "custom_fields": [
          {
            "field_id": 1,
            "field_title": "Material",
            "value": "100% Cotton"
          }
        ]
      }
    ],
    "total": 25
  }
}
```

---

## Statistics

### 13. Get Product Statistics

Get comprehensive product statistics.

**Endpoint:** `GET /api/employee/products/stats`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `from_date` | date | No | Start date (Y-m-d) |
| `to_date` | date | No | End date (Y-m-d) |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_products": 567,
    "active_products": 523,
    "archived_products": 44,
    "by_category": [
      {
        "category": "Electronics",
        "count": 120
      },
      {
        "category": "Clothing",
        "count": 245
      }
    ],
    "by_vendor": [
      {
        "vendor": "Tech Supplies Inc",
        "count": 89
      }
    ],
    "recently_added": [
      {
        "id": 567,
        "name": "Latest Product",
        "created_at": "2026-01-13T10:00:00Z"
      }
    ],
    "total_inventory_value": 125450.75
  }
}
```

---

## Error Handling

### Common Error Responses

**400 Bad Request:**
```json
{
  "success": false,
  "message": "Invalid request parameters"
}
```

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "message": "You don't have permission to perform this action"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Product not found"
}
```

**422 Unprocessable Entity:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "An error occurred while processing your request"
}
```

---

## Best Practices

### 1. Product Creation Workflow
1. Create product with basic info
2. Add custom fields if needed
3. Upload product images
4. Create variants (if applicable)
5. Generate barcodes
6. Add initial stock batches

### 2. Bulk Operations
- Use bulk update for mass category/vendor changes
- Maximum 100 products per bulk operation
- Operations are transactional (all or nothing)

### 3. Custom Fields
- Validate field types before submission
- Required fields must have values
- Select/radio fields must use predefined options

### 4. Archive vs Delete
- **Archive**: Recommended for products with history
- **Delete**: Only for products without batches/orders
- Archived products excluded from active queries

### 5. SKU Management
- SKU not unique (supports variations via ProductFields)
- Use consistent SKU naming convention
- Variant SKUs should derive from parent SKU

---

## Related Documentation

- [Product Variants API](./2026_01_13_PRODUCT_VARIANTS_API.md) - Manage size/color variations
- [Product Barcodes API](./2026_01_13_PRODUCT_BARCODES_API.md) - Barcode generation and tracking
- [Product Images API](./2026_01_13_PRODUCT_IMAGES_API.md) - Image management
- [Product Batches API](./2026_01_13_PRODUCT_BATCHES_API.md) - Stock batch management
- [Product Common Info Update](../features/PRODUCT_COMMON_INFO_UPDATE_API.md) - Bulk updates affecting variants

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-13 | 1.0 | Initial comprehensive documentation |

---

## Support

For questions or issues with this API, contact the backend development team.
