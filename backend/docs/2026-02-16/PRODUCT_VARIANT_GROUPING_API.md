# Product Variant Grouping API - Frontend Guide

**Date:** February 16, 2026  
**Feature:** Product Grouping by Base Name with Variants  
**Affected Endpoints:** Public Catalog API

---

## Overview

The public product catalog API now groups products by their `base_name`, showing one main product with all its variants in an array. This provides a cleaner browsing experience for e-commerce customers.

**Key Changes:**
- Products with the same `base_name` are grouped together
- Main product is selected (first variant or most expensive)
- Other variants are listed in a `variants` array
- Each variant includes images, pricing, and stock information

---

## API Endpoints

### 1. Get Products List (Grouped by Base Name)

**Endpoint:** `GET /api/catalog/products`

**Query Parameters:**
- `category_id` (optional) - Filter by category ID
- `search` (optional) - Search in product name, SKU, description
- `min_price` (optional) - Minimum selling price
- `max_price` (optional) - Maximum selling price
- `in_stock` (optional, boolean) - Show only in-stock products
- `sort_by` (optional) - Sort field: `price_asc`, `price_desc`, `newest`, `name`
- `per_page` (optional, default: 20) - Items per page
- `page` (optional, default: 1) - Page number

**Response Structure:**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "base_name": "Premium Cotton T-Shirt",
        "description": "Comfortable cotton t-shirt with premium fabric",
        "category": {
          "id": 5,
          "name": "Apparel"
        },
        "has_variants": true,
        "main_variant": {
          "id": 101,
          "name": "Premium Cotton T-Shirt-Blue-Medium",
          "variation_suffix": "-Blue-Medium",
          "sku": "TSH-BLUE-M",
          "selling_price": 1500.00,
          "stock_quantity": 50,
          "images": [
            {
              "id": 1,
              "url": "https://example.com/images/tshirt-blue-m-1.jpg",
              "is_primary": true
            },
            {
              "id": 2,
              "url": "https://example.com/images/tshirt-blue-m-2.jpg",
              "is_primary": false
            }
          ]
        },
        "variants": [
          {
            "id": 102,
            "name": "Premium Cotton T-Shirt-Red-Large",
            "variation_suffix": "-Red-Large",
            "sku": "TSH-RED-L",
            "selling_price": 1600.00,
            "stock_quantity": 30,
            "images": [
              {
                "id": 3,
                "url": "https://example.com/images/tshirt-red-l-1.jpg",
                "is_primary": true
              },
              {
                "id": 4,
                "url": "https://example.com/images/tshirt-red-l-2.jpg",
                "is_primary": false
              }
            ]
          },
          {
            "id": 103,
            "name": "Premium Cotton T-Shirt-Green-Small",
            "variation_suffix": "-Green-Small",
            "sku": "TSH-GREEN-S",
            "selling_price": 1400.00,
            "stock_quantity": 0,
            "images": [
              {
                "id": 5,
                "url": "https://example.com/images/tshirt-green-s-1.jpg",
                "is_primary": true
              }
            ]
          }
        ]
      },
      {
        "base_name": "Leather Wallet",
        "description": "Genuine leather wallet",
        "category": {
          "id": 8,
          "name": "Accessories"
        },
        "has_variants": false,
        "main_variant": {
          "id": 201,
          "name": "Leather Wallet",
          "variation_suffix": null,
          "sku": "WALLET-001",
          "selling_price": 2500.00,
          "stock_quantity": 15,
          "images": [
            {
              "id": 10,
              "url": "https://example.com/images/wallet-1.jpg",
              "is_primary": true
            }
          ]
        },
        "variants": []
      }
    ],
    "first_page_url": "http://api.example.com/api/catalog/products?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://api.example.com/api/catalog/products?page=5",
    "next_page_url": "http://api.example.com/api/catalog/products?page=2",
    "path": "http://api.example.com/api/catalog/products",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 95
  }
}
```

**Field Descriptions:**

| Field | Type | Description |
|-------|------|-------------|
| `base_name` | string | Product base name (common across variants) |
| `description` | string | Product description |
| `category` | object | Category information (id, name) |
| `has_variants` | boolean | Whether product has multiple variants |
| `main_variant` | object | Primary variant to display |
| `main_variant.id` | integer | Product ID |
| `main_variant.name` | string | Full product name (base_name + variation_suffix) |
| `main_variant.variation_suffix` | string\|null | Variant suffix (e.g., "-Red-Large") |
| `main_variant.sku` | string | Stock Keeping Unit |
| `main_variant.selling_price` | decimal | Price in BDT |
| `main_variant.stock_quantity` | integer | Available stock |
| `main_variant.images` | array | Up to 2 active product images |
| `variants` | array | Other variants of the same product |
| `variants[].id` | integer | Variant product ID |
| `variants[].name` | string | Variant full name |
| `variants[].variation_suffix` | string | Variant identifier |
| `variants[].sku` | string | Variant SKU |
| `variants[].selling_price` | decimal | Variant price |
| `variants[].stock_quantity` | integer | Variant stock |
| `variants[].images` | array | Up to 2 active images per variant |

**Image Object:**
- `id` (integer) - Image ID
- `url` (string) - Full image URL
- `is_primary` (boolean) - Whether this is the primary/featured image

---

### 2. Get Single Product with Variants

**Endpoint:** `GET /api/catalog/products/{identifier}`

**Path Parameters:**
- `identifier` - Product ID or SKU

**Response Structure:**

```json
{
  "success": true,
  "data": {
    "id": 101,
    "name": "Premium Cotton T-Shirt-Blue-Medium",
    "base_name": "Premium Cotton T-Shirt",
    "variation_suffix": "-Blue-Medium",
    "sku": "TSH-BLUE-M",
    "description": "Comfortable cotton t-shirt with premium fabric",
    "selling_price": 1500.00,
    "stock_quantity": 50,
    "category": {
      "id": 5,
      "name": "Apparel",
      "tax_percentage": 5.00
    },
    "images": [
      {
        "id": 1,
        "url": "https://example.com/images/tshirt-blue-m-1.jpg",
        "is_primary": true
      },
      {
        "id": 2,
        "url": "https://example.com/images/tshirt-blue-m-2.jpg",
        "is_primary": false
      }
    ],
    "variants": [
      {
        "id": 101,
        "name": "Premium Cotton T-Shirt-Blue-Medium",
        "variation_suffix": "-Blue-Medium",
        "sku": "TSH-BLUE-M",
        "selling_price": 1500.00,
        "stock_quantity": 50,
        "is_current": true,
        "images": [
          {
            "id": 1,
            "url": "https://example.com/images/tshirt-blue-m-1.jpg",
            "is_primary": true
          }
        ]
      },
      {
        "id": 102,
        "name": "Premium Cotton T-Shirt-Red-Large",
        "variation_suffix": "-Red-Large",
        "sku": "TSH-RED-L",
        "selling_price": 1600.00,
        "stock_quantity": 30,
        "is_current": false,
        "images": [
          {
            "id": 3,
            "url": "https://example.com/images/tshirt-red-l-1.jpg",
            "is_primary": true
          }
        ]
      }
    ]
  }
}
```

**Additional Fields in Single Product View:**
- `is_current` (boolean) - Indicates if this variant is the currently viewed one
- Single product view shows only 1 image per variant
- List view shows up to 2 images per variant

---

## Frontend Implementation Guide

### 1. Product Listing Page

**Display Strategy:**
1. Show `main_variant` as the primary product card
2. Use `main_variant.images[0]` (primary image) as the product thumbnail
3. Display `base_name` as the product title
4. Show `main_variant.selling_price` as the price
5. Indicate variant availability with a badge if `has_variants` is true

**Variant Indicator:**
```
If has_variants === true:
  Show badge: "+{variants.length} variants available"
```

**Stock Display:**
```
If main_variant.stock_quantity > 0:
  Show "In Stock"
Else:
  Check if ANY variant has stock > 0:
    Show "Available in other variants"
  Else:
    Show "Out of Stock"
```

### 2. Product Detail Page

**URL Structure:**
- Use product ID: `/products/{id}`
- Use SKU: `/products/{sku}`

**Variant Selection:**
1. Display all variants in a selection UI (dropdown, buttons, or swatches)
2. Parse `variation_suffix` to extract attributes (e.g., "-Red-Large" → Color: Red, Size: Large)
3. When user selects a variant, navigate to `/products/{variant.id}` or update state
4. Highlight current variant with `is_current: true`

**Image Gallery:**
- Show all images from the current product
- Each variant has its own images
- Load variant images when user switches variants

**Add to Cart:**
- Use the current `product.id` (not base_name)
- Submit `product_id` and `quantity` to cart API
- Each variant is a separate product in the database

### 3. Search & Filters

**Search Behavior:**
- Searches in `base_name`, `name`, and `sku`
- Returns grouped results (one card per base_name)
- All variants are searchable, but result shows grouped view

**Filter Examples:**

```
// Get products in category 5
GET /api/catalog/products?category_id=5

// Search for "t-shirt"
GET /api/catalog/products?search=t-shirt

// Price range 1000-2000
GET /api/catalog/products?min_price=1000&max_price=2000

// Only in-stock products
GET /api/catalog/products?in_stock=true

// Sort by price (low to high)
GET /api/catalog/products?sort_by=price_asc

// Combine filters
GET /api/catalog/products?category_id=5&search=cotton&min_price=1000&in_stock=true&sort_by=newest
```

---

## Data Handling Best Practices

### 1. Variant Management

**Extracting Variant Attributes:**

Products use `variation_suffix` to indicate variant properties:
- Format: `"-{Attribute1}-{Attribute2}-{Attribute3}"`
- Example: `"-Red-Large"` or `"-128GB-Black"`

**Parsing Logic:**
```
variation_suffix: "-Red-Large"
Split by "-" and remove empty strings
Result: ["Red", "Large"]

You may need to map these to attribute types:
- Color: Red
- Size: Large
```

### 2. Price Display

**Variant Price Range:**

If displaying price range for products with variants:

```
prices = [main_variant.selling_price, ...variants.map(v => v.selling_price)]
min_price = Math.min(...prices)
max_price = Math.max(...prices)

If min_price === max_price:
  Display: "৳{min_price}"
Else:
  Display: "৳{min_price} - ৳{max_price}"
```

### 3. Stock Aggregation

**Total Stock Across Variants:**

```
total_stock = main_variant.stock_quantity + variants.reduce((sum, v) => sum + v.stock_quantity, 0)
```

**Availability Status:**
- `In Stock` - main_variant has stock
- `{count} variants available` - other variants have stock
- `Out of Stock` - no variants have stock

---

## Error Handling

### Common Errors

**404 - Product Not Found**
```json
{
  "success": false,
  "message": "Product not found"
}
```

**500 - Server Error**
```json
{
  "success": false,
  "message": "An error occurred while fetching products"
}
```

---

## Migration Notes

### For Existing Implementations

**Products Without Variants:**
- `base_name` will be null or same as `name`
- `variation_suffix` will be null
- `has_variants` will be false
- `variants` array will be empty
- Display logic remains the same

**Backward Compatibility:**
- All existing product IDs remain valid
- SKU-based lookups work as before
- Cart and order APIs unchanged (use product ID)

**Testing Checklist:**
- [ ] Product list displays grouped products correctly
- [ ] Variant indicators show when applicable
- [ ] Single product page loads all variants
- [ ] Variant selection updates images and price
- [ ] Add to cart uses correct product ID
- [ ] Search returns grouped results
- [ ] Filters work with grouped products
- [ ] Pagination works correctly
- [ ] Out-of-stock variants handled properly
- [ ] Products without variants display normally

---

## Example Use Cases

### Use Case 1: T-Shirt with Color and Size Variants

**API Response:**
- `base_name`: "Premium Cotton T-Shirt"
- Main variant: Blue-Medium (most expensive or first)
- Variants: Red-Large, Green-Small, Blue-Large, etc.

**Frontend Display:**
- Show product card with base name
- Badge: "+5 variants available"
- On detail page: Color selector (Red, Blue, Green) + Size selector (S, M, L)

### Use Case 2: Electronics with Storage Variants

**API Response:**
- `base_name`: "Smartphone X"
- Main variant: 128GB-Black
- Variants: 64GB-Black, 256GB-Blue, 128GB-White

**Frontend Display:**
- Product card shows 128GB-Black
- Badge: "+3 variants available"
- Detail page: Storage selector (64GB, 128GB, 256GB) + Color selector

### Use Case 3: Single Product (No Variants)

**API Response:**
- `base_name`: "Leather Wallet" (or null)
- `has_variants`: false
- `variants`: []

**Frontend Display:**
- Regular product card
- No variant badge
- Detail page shows single product info

---

## Technical Notes

### Image Limits
- **List view:** Up to 2 images per product/variant
- **Detail view:** Up to 1 image per variant in variants array
- **Current product:** All images available in main `images` array
- Only `is_active: true` images are returned

### Performance Considerations
- Products are grouped at query level for efficiency
- Use pagination to limit data transfer
- Consider lazy loading variant images on selection
- Cache product data for faster browsing

### API Authentication
- These endpoints are **public** (no authentication required)
- Use cart and checkout APIs to complete purchases

---

## Support & Questions

For implementation questions or issues:
1. Check existing products in `/api/catalog/products` for real examples
2. Test with products that have multiple variants
3. Verify backward compatibility with single products
4. Contact backend team for API changes or updates

**Last Updated:** February 16, 2026  
**Related Docs:** Product Search System, Order API
