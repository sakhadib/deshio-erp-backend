# Product Management System - Complete Documentation

## Overview
Comprehensive product management system with **dynamic custom fields** support. Products can have custom fields (defined in `fields` table) with values stored in `product_fields` pivot table. This allows flexible product attributes without modifying the database schema.

## Key Features
✅ **Dynamic Custom Fields** - Add any custom field to products (text, number, date, select, checkbox, etc.)  
✅ **Field Type Validation** - Automatic validation based on field type (email, URL, number, date, etc.)  
✅ **Batch Tracking Integration** - Full integration with product batches and inventory  
✅ **Category & Vendor Management** - Products linked to categories and vendors  
✅ **Archive/Restore** - Soft delete support for products  
✅ **Bulk Operations** - Update multiple products at once  
✅ **Advanced Search** - Search by custom field values  
✅ **Inventory Integration** - Real-time inventory value calculation  

---

## Database Structure

### Products Table
```
products
├── id
├── category_id (FK → categories)
├── vendor_id (FK → vendors)
├── sku (unique)
├── name
├── description
├── is_archived (soft delete flag)
├── timestamps
└── soft_deletes
```

### Product Fields Table (Pivot)
```
product_fields
├── id
├── product_id (FK → products)
├── field_id (FK → fields)
├── value (text - stores field value)
└── timestamps

UNIQUE(product_id, field_id)
```

### Fields Table
```
fields
├── id
├── title
├── type (text, number, email, url, date, select, radio, checkbox, file, json, boolean)
├── description
├── is_required
├── default_value
├── options (JSON - for select/radio/checkbox)
├── validation_rules
├── placeholder
├── order
├── is_active
└── timestamps
```

---

## API Endpoints

### 1. Get All Products
```http
GET /api/products
```

**Query Parameters:**
- `category_id` - Filter by category
- `vendor_id` - Filter by vendor
- `is_archived` - Filter by archived status (default: false)
- `search` - Search in name, SKU, description
- `field_search` - Search by custom field value
- `field_id` - Field ID for custom field search
- `sort_by` - Sort field (name, sku, created_at, updated_at)
- `sort_direction` - Sort direction (asc, desc)
- `per_page` - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "category_id": 5,
        "vendor_id": 2,
        "sku": "PROD-001",
        "name": "Laptop Dell XPS 15",
        "description": "High-performance laptop",
        "is_archived": false,
        "category": {
          "id": 5,
          "name": "Electronics"
        },
        "vendor": {
          "id": 2,
          "name": "Tech Supplies Inc"
        },
        "custom_fields": [
          {
            "field_id": 1,
            "field_title": "Processor",
            "field_type": "text",
            "value": "Intel i7 12th Gen",
            "raw_value": "Intel i7 12th Gen"
          },
          {
            "field_id": 2,
            "field_title": "RAM",
            "field_type": "number",
            "value": 16,
            "raw_value": "16"
          },
          {
            "field_id": 3,
            "field_title": "Color",
            "field_type": "select",
            "value": "Silver",
            "raw_value": "Silver"
          },
          {
            "field_id": 4,
            "field_title": "Warranty (years)",
            "field_type": "number",
            "value": 3,
            "raw_value": "3"
          }
        ]
      }
    ],
    "total": 125,
    "per_page": 15
  }
}
```

### 2. Get Single Product
```http
GET /api/products/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "sku": "PROD-001",
    "name": "Laptop Dell XPS 15",
    "description": "High-performance laptop",
    "category": {...},
    "vendor": {...},
    "custom_fields": [
      {
        "field_id": 1,
        "field_title": "Processor",
        "field_type": "text",
        "value": "Intel i7 12th Gen"
      }
    ],
    "images": [...],
    "barcodes": [...],
    "batches": [
      {
        "id": 10,
        "batch_number": "BATCH-2024-001",
        "quantity": 50,
        "cost_price": 1200.00,
        "sell_price": 1500.00,
        "store": {
          "id": 2,
          "name": "Main Warehouse"
        }
      }
    ],
    "inventory_summary": {
      "total_quantity": 150,
      "available_batches": 3,
      "lowest_price": 1450.00,
      "highest_price": 1550.00,
      "average_price": 1500.00
    }
  }
}
```

### 3. Create Product with Custom Fields
```http
POST /api/products
```

**Request:**
```json
{
  "category_id": 5,
  "vendor_id": 2,
  "sku": "PROD-002",
  "name": "MacBook Pro 16",
  "description": "Apple MacBook Pro with M2 chip",
  "custom_fields": [
    {
      "field_id": 1,
      "value": "Apple M2 Pro"
    },
    {
      "field_id": 2,
      "value": 32
    },
    {
      "field_id": 3,
      "value": "Space Gray"
    },
    {
      "field_id": 5,
      "value": ["Thunderbolt 4", "MagSafe 3", "HDMI"]
    },
    {
      "field_id": 6,
      "value": "2024-10-15"
    },
    {
      "field_id": 7,
      "value": "support@apple.com"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 2,
    "sku": "PROD-002",
    "name": "MacBook Pro 16",
    "product_fields": [...]
  }
}
```

### 4. Update Product
```http
PUT /api/products/{id}
```

**Request:**
```json
{
  "name": "MacBook Pro 16-inch M2",
  "description": "Updated description",
  "custom_fields": [
    {
      "field_id": 1,
      "value": "Apple M2 Max"
    },
    {
      "field_id": 2,
      "value": 64
    }
  ]
}
```

### 5. Update Single Custom Field
```http
POST /api/products/{id}/custom-fields
```

**Request:**
```json
{
  "field_id": 2,
  "value": 64
}
```

**Response:**
```json
{
  "success": true,
  "message": "Custom field updated successfully",
  "data": {...}
}
```

### 6. Remove Custom Field
```http
DELETE /api/products/{id}/custom-fields/{fieldId}
```

**Response:**
```json
{
  "success": true,
  "message": "Custom field removed successfully"
}
```

### 7. Archive Product
```http
PATCH /api/products/{id}/archive
```

**Response:**
```json
{
  "success": true,
  "message": "Product archived successfully"
}
```

### 8. Restore Archived Product
```http
PATCH /api/products/{id}/restore
```

**Response:**
```json
{
  "success": true,
  "message": "Product restored successfully",
  "data": {...}
}
```

### 9. Delete Product Permanently
```http
DELETE /api/products/{id}
```

**Note:** Cannot delete products with existing batches. Archive them instead.

**Response:**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

### 10. Get Available Fields
```http
GET /api/products/available-fields
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Processor",
      "type": "text",
      "description": "CPU/Processor type",
      "is_required": true,
      "options": null,
      "validation_rules": "required|string",
      "placeholder": "e.g., Intel i7",
      "order": 1,
      "is_active": true
    },
    {
      "id": 2,
      "title": "RAM (GB)",
      "type": "number",
      "is_required": true,
      "validation_rules": "required|numeric|min:1",
      "order": 2
    },
    {
      "id": 3,
      "title": "Color",
      "type": "select",
      "options": ["Black", "Silver", "Gold", "Space Gray"],
      "is_required": false,
      "order": 3
    },
    {
      "id": 5,
      "title": "Ports",
      "type": "checkbox",
      "options": ["USB-C", "USB-A", "HDMI", "Thunderbolt 4", "MagSafe 3"],
      "is_required": false,
      "order": 5
    }
  ]
}
```

### 11. Search Products by Custom Field
```http
POST /api/products/search-by-field
```

**Request:**
```json
{
  "field_id": 2,
  "value": "16",
  "operator": ">="
}
```

**Query Parameters:**
- `per_page` - Items per page

**Operators:** `=`, `like`, `>`, `<`, `>=`, `<=` (default: `like`)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Laptop Dell XPS 15",
        "custom_fields": [
          {
            "field_id": 2,
            "field_title": "RAM (GB)",
            "value": 16
          }
        ]
      },
      {
        "id": 2,
        "name": "MacBook Pro 16",
        "custom_fields": [
          {
            "field_id": 2,
            "field_title": "RAM (GB)",
            "value": 32
          }
        ]
      }
    ]
  }
}
```

### 12. Bulk Update Products
```http
POST /api/products/bulk-update
```

**Request:**
```json
{
  "product_ids": [1, 2, 3, 4, 5],
  "action": "update_category",
  "category_id": 7
}
```

**Actions:**
- `archive` - Archive multiple products
- `restore` - Restore multiple products
- `update_category` - Change category for multiple products
- `update_vendor` - Change vendor for multiple products

**Response:**
```json
{
  "success": true,
  "message": "Updated category for 5 products"
}
```

### 13. Get Product Statistics
```http
GET /api/products/stats?from_date=2024-01-01&to_date=2024-12-31
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_products": 250,
    "active_products": 235,
    "archived_products": 15,
    "by_category": [
      {
        "category": "Electronics",
        "count": 85
      },
      {
        "category": "Furniture",
        "count": 45
      }
    ],
    "by_vendor": [
      {
        "vendor": "Tech Supplies Inc",
        "count": 120
      },
      {
        "vendor": "Office Depot",
        "count": 65
      }
    ],
    "recently_added": [...],
    "total_inventory_value": 2850000.00
  }
}
```

---

## Field Types and Validation

### Supported Field Types

| Type | Description | Example Value | Validation |
|------|-------------|---------------|------------|
| `text` | Short text | "Intel i7" | string |
| `textarea` | Long text | "Detailed description..." | string |
| `number` | Numeric value | 16, 3.5 | numeric |
| `email` | Email address | "support@example.com" | valid email |
| `url` | Website URL | "https://example.com" | valid URL |
| `date` | Date | "2024-10-15" | valid date |
| `select` | Dropdown (single) | "Silver" | Must be in options array |
| `radio` | Radio button | "Option A" | Must be in options array |
| `checkbox` | Multiple selection | ["USB-C", "HDMI"] | Array, all must be in options |
| `boolean` | True/False | true, false | boolean |
| `file` | File upload | "path/to/file" | file |
| `json` | JSON data | {"key": "value"} | valid JSON |

### Field Definition Example

```json
{
  "title": "Storage Capacity",
  "type": "select",
  "description": "Hard drive or SSD capacity",
  "is_required": true,
  "options": ["128GB", "256GB", "512GB", "1TB", "2TB"],
  "validation_rules": "required",
  "placeholder": "Select storage",
  "order": 4,
  "is_active": true
}
```

---

## Usage Examples

### Example 1: Create Product with Multiple Field Types

```json
POST /api/products
{
  "category_id": 5,
  "vendor_id": 2,
  "sku": "LAPTOP-001",
  "name": "Gaming Laptop ASUS ROG",
  "description": "High-performance gaming laptop",
  "custom_fields": [
    {
      "field_id": 1,
      "value": "AMD Ryzen 9 7950X"
    },
    {
      "field_id": 2,
      "value": 32
    },
    {
      "field_id": 3,
      "value": "RGB Black"
    },
    {
      "field_id": 4,
      "value": 2
    },
    {
      "field_id": 5,
      "value": ["USB-C", "HDMI", "Thunderbolt 4", "Ethernet"]
    },
    {
      "field_id": 6,
      "value": "2024-10-01"
    },
    {
      "field_id": 7,
      "value": "support@asus.com"
    },
    {
      "field_id": 8,
      "value": "https://asus.com/rog"
    },
    {
      "field_id": 9,
      "value": true
    }
  ]
}
```

### Example 2: Search Products by RAM >= 16GB

```json
POST /api/products/search-by-field
{
  "field_id": 2,
  "value": "16",
  "operator": ">="
}
```

### Example 3: Update Product Category in Bulk

```json
POST /api/products/bulk-update
{
  "product_ids": [10, 11, 12, 13, 14],
  "action": "update_category",
  "category_id": 8
}
```

### Example 4: Add Custom Field to Existing Product

```json
POST /api/products/5/custom-fields
{
  "field_id": 10,
  "value": "Extended warranty included"
}
```

---

## Custom Fields System Architecture

```
┌──────────────┐         ┌─────────────────┐         ┌──────────────────┐
│   FIELDS     │         │ PRODUCT_FIELDS  │         │    PRODUCTS      │
│  (Master)    │◄────────│    (Pivot)      │────────►│                  │
├──────────────┤         ├─────────────────┤         ├──────────────────┤
│ id           │         │ product_id      │         │ id               │
│ title        │         │ field_id        │         │ category_id      │
│ type         │         │ value           │         │ vendor_id        │
│ is_required  │         │ timestamps      │         │ sku              │
│ options      │         └─────────────────┘         │ name             │
│ order        │                                     │ description      │
│ is_active    │                                     │ is_archived      │
└──────────────┘                                     └──────────────────┘

WORKFLOW:
1. Admin creates Fields (once)
2. Product created with selected fields and values
3. Values stored in product_fields pivot table
4. Field definitions remain centralized
```

---

## Business Rules

1. **SKU Uniqueness**: Each product must have unique SKU
2. **Required Fields**: If field is marked `is_required`, value must be provided
3. **Field Type Validation**: Values validated based on field type (email, URL, number, etc.)
4. **Archive Instead of Delete**: Products with batches cannot be deleted, only archived
5. **Soft Deletes**: Products use Laravel soft deletes
6. **Custom Field Flexibility**: Can add/remove custom fields from products anytime
7. **Field Options**: Select/radio/checkbox fields must have values from predefined options
8. **Field Ordering**: Fields displayed in order specified by `order` column

---

## Integration with Other Systems

### Product Batches
- Each product can have multiple batches
- Batches track inventory in different warehouses
- Each batch has own cost_price and sell_price

### Purchase Orders
- Products ordered from vendors through purchase orders
- Receiving PO creates product batches

### Orders & Sales
- Products sold through orders
- Price can vary by batch

### Inventory
- Real-time inventory tracked through batches
- Total inventory value calculated from batches

---

## Model Methods

### Product Model

**Custom Field Methods:**
- `getFieldValue($fieldSlug)` - Get value of specific field
- `setFieldValue($fieldSlug, $value)` - Set value of specific field
- `getAllFieldValues()` - Get all field values as array
- `attachField($field, $value)` - Attach field to product
- `detachField($field)` - Remove field from product

**Inventory Methods:**
- `getTotalInventory($storeId)` - Get total quantity across all batches
- `getCurrentBatchPrice($storeId)` - Get current batch price
- `getLowestBatchPrice($storeId)` - Get lowest price across batches
- `getHighestBatchPrice($storeId)` - Get highest price across batches
- `getAverageBatchPrice($storeId)` - Get average price across batches

**Image/Barcode Methods:**
- `primaryImage()` - Get primary product image
- `primaryBarcode()` - Get primary barcode
- `generateBarcode($type, $makePrimary)` - Generate new barcode

---

## Testing Guide

### Test Case 1: Create Product with Custom Fields
1. Create field definitions (Processor, RAM, Color, etc.)
2. Create product with multiple custom fields
3. Verify fields are stored correctly
4. Check field value parsing (number, boolean, JSON, etc.)

### Test Case 2: Field Validation
1. Try to create product without required field → Should fail
2. Try invalid email format → Should fail
3. Try invalid URL → Should fail
4. Try select option not in list → Should fail

### Test Case 3: Search by Custom Field
1. Create products with various RAM values (8, 16, 32, 64)
2. Search for RAM >= 16
3. Verify only products with RAM 16+ returned

### Test Case 4: Bulk Operations
1. Create 10 products in Category A
2. Bulk update to move to Category B
3. Verify all products moved

### Test Case 5: Archive/Restore
1. Archive product
2. Verify not shown in default listing
3. Search with is_archived=true → Product appears
4. Restore product → Back in default listing

---

## Summary

✅ **Complete product management** with CRUD operations  
✅ **Dynamic custom fields** - no schema changes needed  
✅ **Type validation** - email, URL, number, date, select, etc.  
✅ **Advanced search** - by field values with operators  
✅ **Bulk operations** - update multiple products at once  
✅ **Inventory integration** - real-time inventory tracking  
✅ **Flexible architecture** - add/remove fields anytime  
✅ **Archive/restore** - soft delete support  
✅ **Statistics & analytics** - comprehensive reporting  

**Total Endpoints:** 13  
**Total Custom Field Types:** 12  
**Integration Points:** Batches, Orders, POs, Inventory  

System ready to use! 🚀
