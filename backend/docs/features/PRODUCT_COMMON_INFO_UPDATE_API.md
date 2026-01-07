# Product Common Info Update API

**Date:** January 8, 2025  
**Purpose:** Documentation for updating product common information that applies across all product variations  
**Audience:** Frontend Developers

---

## Overview

The product system uses a **parent-child architecture** where:
- **Parent Product** stores common information shared across all variations (name, description, category, vendor, brand, SKU)
- **Product Variants** store variation-specific information (attributes, pricing, stock, images, individual SKUs/barcodes)

When you update the parent product's common information, it automatically affects all variants because variants reference the parent via `product_id` foreign key.

---

## Product Structure

### Common Fields (Parent Product)
These fields are stored in the `products` table and apply to ALL variants:

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| `name` | string | Product name | Yes |
| `description` | text | Product description | No |
| `category_id` | integer | Category reference | Yes |
| `vendor_id` | integer | Vendor reference | No |
| `brand` | string | Brand name | No |
| `sku` | string | Parent SKU (supports variations) | Yes |
| `is_archived` | boolean | Archive status | Yes |

### Variant-Specific Fields (Product Variants)
These fields are stored in the `product_variants` table and are unique per variant:

| Field | Type | Description |
|-------|------|-------------|
| `sku` | string | Unique variant SKU |
| `barcode` | string | Unique barcode |
| `attributes` | JSON | Variation attributes (e.g., `{"Size": "XL", "Color": "Red"}`) |
| `price_adjustment` | decimal | Price adjustment from base |
| `cost_price` | decimal | Variant cost price |
| `stock_quantity` | integer | Available stock |
| `reserved_quantity` | integer | Reserved stock |
| `image_url` | string | Variant-specific image |
| `is_active` | boolean | Variant active status |
| `is_default` | boolean | Default variant flag |

---

## API Endpoints

### 1. Update Single Product Common Info

Updates common information for a product, affecting all its variants.

**Endpoint:** `PUT /api/employee/products/{id}`

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Updated Product Name",
  "description": "Updated product description",
  "category_id": 5,
  "vendor_id": 3,
  "brand": "New Brand Name",
  "sku": "PROD-001",
  "custom_fields": [
    {
      "field_id": 1,
      "value": "Custom value"
    }
  ]
}
```

**Validation Rules:**
- `category_id`: Must exist in categories table
- `vendor_id`: Optional, must exist in vendors table if provided
- `brand`: Optional, max 255 characters
- `sku`: Required, string (Note: Not unique - supports variations)
- `name`: Required, max 255 characters
- `description`: Optional, text
- `custom_fields`: Optional array
  - `field_id`: Must exist in fields table, no duplicates
  - `value`: Optional

**Response (Success - 200 OK):**
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 123,
    "category_id": 5,
    "vendor_id": 3,
    "brand": "New Brand Name",
    "sku": "PROD-001",
    "name": "Updated Product Name",
    "description": "Updated product description",
    "is_archived": false,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-08T14:30:00.000000Z",
    "category": {
      "id": 5,
      "name": "Electronics"
    },
    "vendor": {
      "id": 3,
      "name": "Tech Supplies Inc"
    },
    "variants": [
      {
        "id": 456,
        "product_id": 123,
        "sku": "PROD-001-XL-RED",
        "attributes": {
          "Size": "XL",
          "Color": "Red"
        },
        "price_adjustment": 5.00,
        "cost_price": 45.00,
        "stock_quantity": 100,
        "is_active": true
      },
      {
        "id": 457,
        "product_id": 123,
        "sku": "PROD-001-L-BLUE",
        "attributes": {
          "Size": "L",
          "Color": "Blue"
        },
        "price_adjustment": 0.00,
        "cost_price": 40.00,
        "stock_quantity": 75,
        "is_active": true
      }
    ],
    "custom_fields": [
      {
        "field_id": 1,
        "field_title": "Material",
        "field_type": "text",
        "value": "Custom value",
        "raw_value": "Custom value"
      }
    ]
  }
}
```

**Response (Error - 422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "category_id": ["The selected category id is invalid."],
    "name": ["The name field is required."]
  }
}
```

**Response (Error - 404 Not Found):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

### 2. Bulk Update Products Common Info

Updates common fields for multiple products at once.

**Endpoint:** `POST /api/employee/products/bulk-update`

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "product_ids": [123, 124, 125],
  "action": "update_category",
  "category_id": 5
}
```

**Available Actions:**

#### Action: `archive`
Archives multiple products (sets `is_archived = true`).
```json
{
  "product_ids": [123, 124, 125],
  "action": "archive"
}
```

#### Action: `restore`
Restores archived products (sets `is_archived = false`).
```json
{
  "product_ids": [123, 124, 125],
  "action": "restore"
}
```

#### Action: `update_category`
Updates category for multiple products.
```json
{
  "product_ids": [123, 124, 125],
  "action": "update_category",
  "category_id": 5
}
```

#### Action: `update_vendor`
Updates vendor for multiple products.
```json
{
  "product_ids": [123, 124, 125],
  "action": "update_vendor",
  "vendor_id": 3
}
```

**Validation Rules:**
- `product_ids`: Required array, all IDs must exist
- `action`: Required, must be one of: `archive`, `restore`, `update_category`, `update_vendor`
- `category_id`: Required if action is `update_category`, must exist
- `vendor_id`: Required if action is `update_vendor`, must exist

**Response (Success - 200 OK):**
```json
{
  "success": true,
  "message": "Updated category for 3 products"
}
```

**Response (Error - 422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "product_ids": ["The product ids field is required."],
    "category_id": ["The category id field is required when action is update_category."]
  }
}
```

**Response (Error - 500 Internal Server Error):**
```json
{
  "success": false,
  "message": "Bulk update failed: Database error message"
}
```

---

## Important Notes

### 1. Cascade Effect
When you update parent product common info:
- All variants automatically inherit the new common information
- Variant-specific fields (attributes, pricing, stock) remain unchanged
- No need to update each variant individually

### 2. SKU Handling
- **Parent SKU**: Can be shared across multiple products with different variations (via ProductFields)
- **Variant SKU**: Must be unique per variant
- Example: Parent SKU "T-SHIRT-001" → Variant SKUs "T-SHIRT-001-XL-RED", "T-SHIRT-001-L-BLUE"

### 3. Custom Fields
- Custom fields are product-level (not variant-level)
- Updated via the single product update endpoint
- Supports various field types: text, number, date, select, checkbox, etc.
- Field validation is automatic based on field type

### 4. Transaction Safety
- All updates use database transactions
- If any validation fails, entire update is rolled back
- No partial updates occur

### 5. Archive vs Delete
- **Archive** (`is_archived = true`): Soft hide, can be restored
- **Delete** (DELETE endpoint): Only allowed if no batches/orders exist
- Archived products are excluded from most queries by default

---

## Frontend Implementation Guide

### Updating Common Info Workflow

1. **Fetch Product with Variants**
   ```javascript
   GET /api/employee/products/{id}
   // Returns product with all variants and common info
   ```

2. **Display Common Info Form**
   ```javascript
   // Common fields to show:
   - Name (text input)
   - Description (textarea)
   - Category (dropdown)
   - Vendor (dropdown)
   - Brand (text input)
   - SKU (text input)
   - Custom Fields (dynamic based on field type)
   ```

3. **Update Common Info**
   ```javascript
   PUT /api/employee/products/{id}
   Body: { name, description, category_id, vendor_id, brand }
   ```

4. **Refresh Product Display**
   ```javascript
   // After successful update, re-fetch product to show updated info
   GET /api/employee/products/{id}
   ```

### Bulk Update Workflow

1. **Select Multiple Products**
   ```javascript
   // Collect product IDs from table selection
   const selectedIds = [123, 124, 125];
   ```

2. **Choose Bulk Action**
   ```javascript
   // Show modal/dropdown with options:
   - Archive Products
   - Restore Products
   - Update Category
   - Update Vendor
   ```

3. **Execute Bulk Update**
   ```javascript
   POST /api/employee/products/bulk-update
   Body: {
     product_ids: selectedIds,
     action: 'update_category',
     category_id: newCategoryId
   }
   ```

4. **Refresh Product List**
   ```javascript
   // After successful bulk update, refresh the product list
   GET /api/employee/products
   ```

---

## Example Use Cases

### Case 1: Change Product Category (Affects All Variants)

A clothing store wants to move a t-shirt product from "Men's Clothing" to "Unisex Clothing". All sizes and colors should reflect this change.

**Request:**
```http
PUT /api/employee/products/123
Content-Type: application/json

{
  "category_id": 8
}
```

**Result:**
- Product 123 moved to category 8
- All variants (XL-Red, L-Blue, M-Black, etc.) now show under "Unisex Clothing"
- Variant attributes, pricing, and stock unchanged

---

### Case 2: Update Brand Name (Affects All Variants)

A vendor changed their brand name from "OldBrand" to "NewBrand".

**Request:**
```http
PUT /api/employee/products/123
Content-Type: application/json

{
  "brand": "NewBrand"
}
```

**Result:**
- Product 123 brand updated to "NewBrand"
- All variants display "NewBrand" when shown
- No variant-specific changes

---

### Case 3: Bulk Category Update

Move 50 products from "Discontinued" category to "Clearance Sale" category.

**Request:**
```http
POST /api/employee/products/bulk-update
Content-Type: application/json

{
  "product_ids": [123, 124, 125, ..., 172],
  "action": "update_category",
  "category_id": 15
}
```

**Result:**
- All 50 products moved to category 15
- All their variants now show under "Clearance Sale"
- Single transaction ensures consistency

---

## Testing Checklist

- [ ] Update single product name - verify all variants show new name
- [ ] Update product description - verify all variants inherit description
- [ ] Change product category - verify all variants move to new category
- [ ] Change product vendor - verify all variants show new vendor
- [ ] Update product brand - verify all variants display new brand
- [ ] Update with invalid category_id - verify validation error
- [ ] Update non-existent product - verify 404 error
- [ ] Update custom fields - verify field type validation
- [ ] Bulk update category for 10 products - verify all updated
- [ ] Bulk archive products - verify products hidden from active lists
- [ ] Bulk restore products - verify products reappear
- [ ] Bulk update with invalid product_ids - verify validation error
- [ ] Verify transaction rollback on error - no partial updates

---

## Error Handling

### Validation Errors (422)
- Check `errors` object for field-specific messages
- Display validation errors next to relevant form fields
- Common errors: missing required fields, invalid IDs, duplicate field_ids

### Not Found Errors (404)
- Product doesn't exist or was deleted
- Redirect user to product list or show error message

### Server Errors (500)
- Database transaction failed
- Show generic error message
- Log error details for debugging
- No changes were applied (rollback successful)

---

## Related Documentation

- [Product Variants API](./PRODUCT_VARIANTS_API.md) - Managing variant-specific information
- [Custom Fields Guide](./CUSTOM_FIELDS_GUIDE.md) - Working with custom product fields
- [Category Management](./CATEGORY_MANAGEMENT.md) - Category CRUD operations
- [Vendor Management](./VENDOR_MANAGEMENT.md) - Vendor CRUD operations

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2025-01-08 | 1.0 | Initial documentation created |

---

## Support

For questions or issues with this API, contact the backend development team.
