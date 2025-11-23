# E-Commerce Catalog API - Fixed Schema Issues

**Date:** November 22, 2025  
**Issue:** Column `is_active` not found error in `/api/catalog/products`  
**Status:** ✅ FIXED

---

## Problem

The frontend was receiving this error:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_active' in 'where clause'
```

**Root Cause:**  
The `EcommerceCatalogController` was querying columns that don't exist in the `products` table:
- ❌ `is_active` (doesn't exist - use `is_archived` instead)
- ❌ `status` (doesn't exist)  
- ❌ `stock_quantity` (doesn't exist - stock is in `product_batches` table)
- ❌ `selling_price` (doesn't exist - prices are in `product_batches` table)
- ❌ `original_price` (doesn't exist)
- ❌ `is_featured` (doesn't exist)
- ❌ `slug` (doesn't exist)
- ❌ `tags` (doesn't exist)

---

## Actual Products Table Schema

```sql
products table:
  - id
  - category_id (FK to categories)
  - vendor_id (FK to vendors)
  - sku
  - name
  - description (text, nullable)
  - is_archived (boolean, default false)
  - deleted_at (soft deletes)
  - created_at
  - updated_at
```

**Important:**  
- Products don't have direct price/stock
- Price and stock are stored in `product_batches` table
- Each product can have multiple batches with different prices and quantities
- Each batch is tied to a specific store

---

## What Was Fixed

### 1. All Catalog Endpoints Updated

✅ **GET /api/catalog/products** - Product listing  
✅ **GET /api/catalog/products/{id}** - Single product details  
✅ **GET /api/catalog/categories** - Category listing  
✅ **GET /api/catalog/featured-products** - Featured products  
✅ **GET /api/catalog/search** - Product search  
✅ **GET /api/catalog/price-range** - Min/max price range  
✅ **GET /api/catalog/new-arrivals** - Recent products  

### 2. Changes Made

#### Filter by Archive Status (not is_active)
```php
// ❌ Old (broken)
->where('is_active', true)

// ✅ New (fixed)
->where('is_archived', false)
```

#### Get Stock from Batches
```php
// ✅ Fixed approach
->with(['batches' => function ($q) {
    $q->where('quantity', '>', 0)->orderBy('sell_price', 'asc');
}])
->whereHas('batches', function ($q) {
    $q->where('quantity', '>', 0);
})
```

#### Calculate Price and Stock
```php
// Get lowest price from available batches
$lowestBatch = $product->batches->sortBy('sell_price')->first();
$totalStock = $product->batches->sum('quantity');

$selling_price = $lowestBatch ? $lowestBatch->sell_price : 0;
$in_stock = $totalStock > 0;
```

---

## New Response Format

### Product List Response

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "sku": "PROD-001",
        "description": "Full description...",
        "short_description": "First 150 chars...",
        "selling_price": 1500.00,
        "cost_price": 1000.00,
        "stock_quantity": 50,
        "in_stock": true,
        "images": [
          {
            "id": 1,
            "url": "https://...",
            "alt_text": "Alt text",
            "is_primary": true
          }
        ],
        "category": {
          "id": 1,
          "name": "Category Name"
        },
        "created_at": "2025-11-22T10:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 12,
      "total": 120,
      "from": 1,
      "to": 12
    },
    "filters_applied": {
      "category": null,
      "min_price": null,
      "max_price": null,
      "search": null,
      "in_stock": true,
      "sort_by": "created_at",
      "sort_order": "desc"
    }
  }
}
```

### Single Product Response

```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "Product Name",
      "sku": "PROD-001",
      "description": "Full description",
      "selling_price": 1500.00,
      "cost_price": 1000.00,
      "stock_quantity": 50,
      "in_stock": true,
      "images": [...],
      "category": {
        "id": 1,
        "name": "Category Name"
      },
      "vendor": {
        "id": 1,
        "name": "Vendor Business Name"
      },
      "batches": [
        {
          "id": 1,
          "sell_price": 1500.00,
          "quantity": 30,
          "store_id": 1
        },
        {
          "id": 2,
          "sell_price": 1600.00,
          "quantity": 20,
          "store_id": 2
        }
      ],
      "created_at": "2025-11-22T10:00:00Z",
      "updated_at": "2025-11-22T12:00:00Z"
    },
    "related_products": [...]
  }
}
```

---

## Frontend Migration Guide

### 1. Remove References to Non-Existent Fields

❌ **Remove these from your code:**
- `product.slug` - doesn't exist anymore
- `product.original_price` - doesn't exist
- `product.discount_percentage` - doesn't exist  
- `product.is_featured` - doesn't exist
- `product.tags` - doesn't exist
- `product.specifications` - doesn't exist
- `product.care_instructions` - doesn't exist
- `product.warranty_info` - doesn't exist
- `product.weight` - doesn't exist
- `product.dimensions` - doesn't exist

### 2. Update Product URLs

```typescript
// ❌ Old (using slug)
const productUrl = `/products/${product.slug}`;

// ✅ New (using ID)
const productUrl = `/products/${product.id}`;
```

### 3. Update Price Display

```typescript
// ❌ Old (had original_price and discount)
<div>
  <del>${product.original_price}</del>
  <span>${product.selling_price}</span>
  <span>{product.discount_percentage}% OFF</span>
</div>

// ✅ New (just selling_price)
<div>
  <span>${product.selling_price}</span>
</div>
```

### 4. Update Stock Display

```typescript
// ✅ Stock still works the same
{product.in_stock ? (
  <span>{product.stock_quantity} in stock</span>
) : (
  <span>Out of stock</span>
)}
```

### 5. Update Category References

```typescript
// ❌ Old (had slug)
const categoryUrl = `/category/${product.category.slug}`;

// ✅ New (just use name or ID)
const categoryUrl = `/category/${product.category.id}`;
```

---

## API Query Parameters (Still Work!)

All these query parameters still work correctly:

### GET /api/catalog/products

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | int | 12 | Items per page (max 50) |
| `page` | int | 1 | Page number |
| `category` | string | - | Filter by category name |
| `min_price` | decimal | - | Minimum price filter |
| `max_price` | decimal | - | Maximum price filter |
| `sort_by` | string | created_at | Sort field: `created_at` or `name` |
| `sort_order` | string | desc | `asc` or `desc` |
| `search` | string | - | Search in name/description/SKU |
| `in_stock` | boolean | true | Show only in-stock items |

**Example:**
```
GET /api/catalog/products?category=sharee&min_price=1000&max_price=5000&sort_by=name&in_stock=true
```

### GET /api/catalog/search

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | ✅ Yes | Search query (min 2 chars) |
| `per_page` | int | - | Items per page (max 50) |

**Example:**
```
GET /api/catalog/search?q=sharee&per_page=20
```

### GET /api/catalog/featured-products

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | int | 8 | Number of products (max 20) |

### GET /api/catalog/new-arrivals

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | int | 8 | Number of products (max 20) |
| `days` | int | 30 | Products added in last X days |

---

## Testing the Fix

### 1. Test Product Listing

```bash
curl -X GET "http://localhost:8000/api/catalog/products?category=sharee&per_page=5"
```

**Expected:**  
✅ 200 OK with product list  
✅ Each product has: `selling_price`, `stock_quantity`, `in_stock`  
✅ No `is_active` column error

### 2. Test Single Product

```bash
curl -X GET "http://localhost:8000/api/catalog/products/1"
```

**Expected:**  
✅ 200 OK with product details  
✅ Includes `batches` array with prices per store  
✅ Related products included

### 3. Test Search

```bash
curl -X GET "http://localhost:8000/api/catalog/search?q=shirt"
```

**Expected:**  
✅ 200 OK with search results  
✅ Includes suggestions array  
✅ Products sorted by relevance

### 4. Test Categories

```bash
curl -X GET "http://localhost:8000/api/catalog/categories"
```

**Expected:**  
✅ 200 OK with category tree  
✅ Product counts per category  
✅ Nested children categories

---

## Database Understanding

### Product-Batch Relationship

```
products (1) ----< (many) product_batches (many) >---- (1) stores
```

**Example:**
```
Product: "Blue Shirt" (ID: 1, SKU: SHIRT-BLUE)
  ├─ Batch #1: $1500, Qty: 30, Store: Main Store
  ├─ Batch #2: $1600, Qty: 20, Store: Branch Store  
  └─ Batch #3: $1400, Qty: 10, Store: Warehouse

Catalog shows: $1400 (lowest price), 60 total stock
```

---

## Important Notes

1. **Prices Vary by Store**: Same product can have different prices at different stores
2. **Stock is Store-Specific**: Total stock = sum of all batch quantities
3. **Lowest Price Shown**: Catalog always shows the lowest available price
4. **Archive vs Delete**: Use `is_archived` for soft hiding products, `deleted_at` for permanent removal
5. **No Slugs**: Products are identified by ID, not slug (simpler, more reliable)

---

## Need More Features?

If you need any of the removed fields back (like slug, tags, is_featured), let the backend team know and we can:
1. Add a migration to create those columns
2. Update the Product model
3. Update the catalog controller

But for now, the system works with the current schema.

---

## Summary

✅ **Fixed:** All catalog endpoints now work with correct schema  
✅ **Tested:** PHP syntax valid, no compilation errors  
✅ **Updated:** Response format matches actual database structure  
✅ **Documented:** This guide for frontend integration  

**Status:** Ready for frontend testing!

---

Last Updated: November 22, 2025  
Contact: Backend Team
