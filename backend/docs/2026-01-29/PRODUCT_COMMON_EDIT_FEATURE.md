# Product Common Edit Feature - Frontend Documentation

**Date:** January 29, 2026  
**Feature:** Magic "Common Edit" - Update base name across all product variations  
**Audience:** Frontend Developers

---

## 🎯 Problem Solved

**Before:** If you had 6 products like:
- saree-red-30
- saree-red-40
- saree-green-30
- saree-green-40
- saree-green-50
- saree-red-50

Renaming "saree" to "sharee" required editing each product individually.

**After:** Edit the base name ONCE, and ALL variations update automatically:
- sharee-red-30
- sharee-red-40
- sharee-green-30
- sharee-green-40
- sharee-green-50
- sharee-red-50

---

## 📊 New Database Structure

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `name` | string | Full display name (auto-computed) | "saree-red-30" |
| `base_name` | string | Core product name (editable) | "saree" |
| `variation_suffix` | string | Variation identifier | "-red-30" |

**Formula:** `name = base_name + variation_suffix`

---

## 📡 New API Endpoints

### 1. Update Common Info (Magic Edit)

Updates the base_name (and optional common fields) for ALL products in a SKU group.

```
PUT /api/employee/products/{id}/common-info
```

**Request:**
```json
{
  "base_name": "sharee",
  "description": "Updated description for all variations",
  "category_id": 5,
  "vendor_id": 3,
  "brand": "New Brand"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Updated common info for 6 product(s) in SKU group 'SAREE-001'",
  "data": {
    "sku": "SAREE-001",
    "new_base_name": "sharee",
    "products_updated": 6,
    "products": [
      {
        "id": 1,
        "name": "sharee-red-30",
        "base_name": "sharee",
        "variation_suffix": "-red-30",
        "sku": "SAREE-001"
      },
      {
        "id": 2,
        "name": "sharee-red-40",
        "base_name": "sharee",
        "variation_suffix": "-red-40",
        "sku": "SAREE-001"
      }
      // ... all 6 products
    ]
  }
}
```

**Fields Updated Across All SKU Group:**
| Field | Required | Description |
|-------|----------|-------------|
| `base_name` | ✅ Yes | New base name for all products |
| `description` | ❌ No | Common description |
| `category_id` | ❌ No | Move all to new category |
| `vendor_id` | ❌ No | Change vendor for all |
| `brand` | ❌ No | Update brand for all |

---

### 2. Get SKU Group (View All Variations)

Get all products that share the same SKU (all variations).

```
GET /api/employee/products/{id}/sku-group
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sku": "SAREE-001",
    "base_name": "saree",
    "total_variations": 6,
    "products": [
      {
        "id": 1,
        "name": "saree-red-30",
        "base_name": "saree",
        "variation_suffix": "-red-30",
        "sku": "SAREE-001",
        "category": { "id": 1, "name": "Clothing" },
        "vendor": { "id": 2, "name": "Supplier Co" }
      },
      // ... all variations
    ]
  }
}
```

---

### 3. Create Product with Base Name (Updated)

When creating products, you can now specify `base_name` and `variation_suffix` separately.

```
POST /api/employee/products
```

**Option A: Using base_name + variation_suffix (Recommended for variations)**
```json
{
  "category_id": 1,
  "sku": "SAREE-001",
  "base_name": "saree",
  "variation_suffix": "-red-30",
  "custom_fields": [
    { "field_id": 1, "value": "Red" },
    { "field_id": 2, "value": "30" }
  ]
}
```
Result: `name = "saree-red-30"`

**Option B: Using name only (Backward compatible)**
```json
{
  "category_id": 1,
  "name": "saree-red-30"
}
```
Result: `base_name = "saree-red-30"`, `variation_suffix = ""`

---

## 🔄 Frontend Workflow

### Creating Product Variations

```javascript
// Step 1: Create first variation
await api.post('/products', {
  category_id: 1,
  sku: 'SAREE-001',  // Same SKU for all variations
  base_name: 'saree',
  variation_suffix: '-red-30',
  custom_fields: [
    { field_id: 1, value: 'Red' },
    { field_id: 2, value: '30' }
  ]
});

// Step 2: Create more variations with SAME SKU
await api.post('/products', {
  category_id: 1,
  sku: 'SAREE-001',  // Same SKU!
  base_name: 'saree',
  variation_suffix: '-red-40',
  custom_fields: [
    { field_id: 1, value: 'Red' },
    { field_id: 2, value: '40' }
  ]
});

// Step 3: Create more...
await api.post('/products', {
  category_id: 1,
  sku: 'SAREE-001',
  base_name: 'saree',
  variation_suffix: '-green-30',
  custom_fields: [...]
});
```

### Common Edit Workflow

```javascript
// Step 1: Get all variations in SKU group
const { data } = await api.get(`/products/${productId}/sku-group`);
console.log(data.total_variations); // 6
console.log(data.base_name); // "saree"

// Step 2: Show edit form with base_name
// User changes "saree" to "sharee"

// Step 3: Update common info - ALL 6 products update!
await api.put(`/products/${productId}/common-info`, {
  base_name: 'sharee'
});

// Result: All 6 products now have updated names
// saree-red-30 → sharee-red-30
// saree-green-40 → sharee-green-40
// etc.
```

### UI Suggestions

```
┌─────────────────────────────────────────────────────┐
│ Edit Product Group                                   │
├─────────────────────────────────────────────────────┤
│ SKU: SAREE-001                                       │
│ Total Variations: 6                                  │
│                                                      │
│ Base Name: [saree________] ← Edit this!             │
│                                                      │
│ Variations:                                          │
│   • saree-red-30                                     │
│   • saree-red-40                                     │
│   • saree-green-30                                   │
│   • saree-green-40                                   │
│   • saree-green-50                                   │
│   • saree-red-50                                     │
│                                                      │
│ [Preview Changes]  [Save Common Info]               │
│                                                      │
│ Preview: All names will become "sharee-*"           │
└─────────────────────────────────────────────────────┘
```

---

## 📋 TypeScript Interfaces

```typescript
// Product with base_name support
interface Product {
  id: number;
  sku: string;
  name: string;           // Display name (auto-computed)
  base_name: string;      // Editable base name
  variation_suffix: string; // Variation identifier
  description?: string;
  category_id: number;
  vendor_id?: number;
  brand?: string;
  is_archived: boolean;
  category?: Category;
  vendor?: Vendor;
  product_fields?: ProductField[];
}

// Create product request
interface CreateProductRequest {
  category_id: number;
  sku?: string;  // Auto-generated if not provided
  // Option A: Use base_name + variation_suffix
  base_name?: string;
  variation_suffix?: string;
  // Option B: Use name directly (backward compatible)
  name?: string;
  description?: string;
  vendor_id?: number;
  brand?: string;
  custom_fields?: { field_id: number; value: any }[];
}

// Common info update request
interface UpdateCommonInfoRequest {
  base_name: string;      // Required - new base name
  description?: string;   // Optional - update for all
  category_id?: number;   // Optional - move all
  vendor_id?: number;     // Optional - change vendor
  brand?: string;         // Optional - update brand
}

// SKU group response
interface SkuGroupResponse {
  success: true;
  data: {
    sku: string;
    base_name: string;
    total_variations: number;
    products: Product[];
  };
}

// Common info update response
interface UpdateCommonInfoResponse {
  success: true;
  message: string;
  data: {
    sku: string;
    new_base_name: string;
    products_updated: number;
    products: Product[];
  };
}
```

---

## ⚠️ Important Notes

### 1. SKU is the Grouping Key
Products are grouped by SKU. All products with the same SKU are considered "variations" of the same product.

### 2. Backward Compatibility
- Existing products have `base_name = name` and `variation_suffix = ''`
- Old create requests still work (just send `name`)
- Single product edit (`PUT /products/{id}`) still only affects one product

### 3. What Gets Updated in Common Edit
| Field | Updated? |
|-------|----------|
| `base_name` | ✅ Yes - For all products in SKU group |
| `name` | ✅ Yes - Auto-recomputed as base_name + variation_suffix |
| `description` | ✅ Yes - If provided |
| `category_id` | ✅ Yes - If provided |
| `vendor_id` | ✅ Yes - If provided |
| `brand` | ✅ Yes - If provided |
| `variation_suffix` | ❌ No - Stays unique per product |
| `custom_fields` | ❌ No - Stays unique per product |

### 4. When to Use Which API

| Use Case | API |
|----------|-----|
| Edit ONE product completely | `PUT /products/{id}` |
| Edit base name for ALL variations | `PUT /products/{id}/common-info` |
| View all variations | `GET /products/{id}/sku-group` |
| Bulk archive/category change | `POST /products/bulk-update` |

---

## 🧪 Testing Checklist

- [ ] Create 3 products with same SKU, different variation_suffix
- [ ] Verify all have same base_name
- [ ] Call `/sku-group` - verify returns all 3
- [ ] Call `/common-info` with new base_name - verify all 3 names updated
- [ ] Verify variation_suffix preserved after common edit
- [ ] Test backward compatible create (only `name`, no base_name)
- [ ] Test common edit with category_id - verify all moved
- [ ] Test common edit with vendor_id - verify all updated

---

## 🎉 Summary

| Before | After |
|--------|-------|
| Edit 6 products individually | Edit once, all 6 update |
| No concept of "base name" | `base_name` + `variation_suffix` = `name` |
| Manual naming | Automatic display name generation |

**The magic is:** Change `base_name` from "saree" to "sharee" via `/common-info` API → All products in SKU group automatically become "sharee-red-30", "sharee-green-40", etc.
