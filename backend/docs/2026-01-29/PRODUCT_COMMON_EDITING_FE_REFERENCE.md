# Product Common Editing - Frontend Reference

**Date:** January 29, 2026  
**Purpose:** Quick reference for frontend developers to edit product common info across variants  
**Full Documentation:** See [PRODUCT_COMMON_INFO_UPDATE_API.md](../features/PRODUCT_COMMON_INFO_UPDATE_API.md)

---

## 🎯 Quick Summary

When you edit a **parent product's common fields**, ALL variants automatically inherit those changes. No need to edit each variant individually.

| Field | Stored In | Applies To |
|-------|-----------|------------|
| `name`, `description`, `brand` | `products` table | All variants |
| `category_id`, `vendor_id` | `products` table | All variants |
| `sku` (parent) | `products` table | All variants |
| Custom Fields | `product_fields` table | Product only |
| Variant `sku`, `attributes`, `price`, `stock` | `product_variants` table | Specific variant |

---

## 📡 API Endpoints

### 1. Single Product Update (Common Info)

**Use when:** Editing one product's name, description, category, vendor, brand, or custom fields.

```
PUT /api/employee/products/{id}
```

**Request:**
```json
{
  "name": "Updated T-Shirt",
  "description": "Premium cotton t-shirt",
  "category_id": 5,
  "vendor_id": 3,
  "brand": "Fashion Brand",
  "custom_fields": [
    { "field_id": 1, "value": "Cotton" },
    { "field_id": 2, "value": "Machine Wash" }
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 123,
    "name": "Updated T-Shirt",
    "category": { "id": 5, "name": "Clothing" },
    "vendor": { "id": 3, "name": "Supplier Co" },
    "product_fields": [...]
  }
}
```

**Notes:**
- All fields are optional - send only what you want to update
- `custom_fields` replaces/creates fields by `field_id`

---

### 2. Bulk Update (Multiple Products)

**Use when:** Archiving, restoring, or changing category/vendor for multiple products at once.

```
POST /api/employee/products/bulk-update
```

**Actions Available:**

| Action | Description | Required Field |
|--------|-------------|----------------|
| `archive` | Hide products from active lists | - |
| `restore` | Un-hide archived products | - |
| `update_category` | Move products to different category | `category_id` |
| `update_vendor` | Change vendor for products | `vendor_id` |

**Example: Archive Multiple Products**
```json
{
  "product_ids": [123, 124, 125],
  "action": "archive"
}
```

**Example: Bulk Category Change**
```json
{
  "product_ids": [123, 124, 125],
  "action": "update_category",
  "category_id": 10
}
```

**Response:**
```json
{
  "success": true,
  "message": "Updated category for 3 products"
}
```

---

### 3. Update Single Custom Field

**Use when:** Updating just one custom field without sending all fields.

```
POST /api/employee/products/{id}/custom-fields
```

**Request:**
```json
{
  "field_id": 1,
  "value": "New Material Value"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Custom field updated successfully",
  "data": { "product_fields": [...] }
}
```

---

### 4. Remove Custom Field

**Use when:** Deleting a custom field from a product.

```
DELETE /api/employee/products/{id}/custom-fields/{fieldId}
```

---

### 5. Archive/Restore Single Product

**Archive:**
```
PATCH /api/employee/products/{id}/archive
```

**Restore:**
```
PATCH /api/employee/products/{id}/restore
```

---

## 🔄 Typical Workflows

### Edit Product Common Info
```
1. GET /api/employee/products/{id}           → Fetch current data
2. Display edit form with common fields
3. PUT /api/employee/products/{id}           → Save changes
4. GET /api/employee/products/{id}           → Refresh display
```

### Bulk Archive Products
```
1. User selects checkboxes on product list
2. Collect selected IDs: [123, 124, 125]
3. POST /api/employee/products/bulk-update
   Body: { product_ids: [...], action: "archive" }
4. Refresh product list
```

### Change Category for All Selected
```
1. Select products on list
2. Choose "Change Category" action
3. Select new category from dropdown
4. POST /api/employee/products/bulk-update
   Body: { product_ids: [...], action: "update_category", category_id: X }
5. Refresh list
```

---

## ⚠️ Important Notes

### 1. Variant Inheritance
When you update parent product:
- ✅ Variants inherit common info (name, description, category, vendor, brand)
- ❌ Variants keep their own: `sku`, `attributes`, `price_adjustment`, `stock_quantity`

### 2. SKU Behavior
- **Parent SKU:** Can be shared (e.g., "TSHIRT-001" for all sizes/colors)
- **Variant SKU:** Must be unique (e.g., "TSHIRT-001-XL-RED")
- If parent SKU not provided on create, auto-generates 9-digit number

### 3. Transaction Safety
- All operations use database transactions
- If any part fails, entire operation rolls back
- No partial updates occur

### 4. Archive vs Delete
| Action | Effect | Reversible |
|--------|--------|------------|
| Archive | Hides from active lists | Yes (restore) |
| Delete | Permanent removal | Only if no orders/batches |

---

## 📋 Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "category_id": ["The selected category id is invalid."]
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Server Error (500):**
```json
{
  "success": false,
  "message": "Bulk update failed: Error details"
}
```

---

## 🧪 Testing Checklist

- [ ] Update single product name → verify reflected in product detail
- [ ] Update category → verify variants show under new category
- [ ] Update custom field → verify field value saved correctly
- [ ] Bulk archive 3 products → verify all hidden from active list
- [ ] Bulk restore → verify products reappear
- [ ] Bulk change vendor → verify all products show new vendor
- [ ] Submit with invalid category_id → verify 422 error shown
- [ ] Update non-existent product → verify 404 error handled

---

## 📚 Related APIs

| Feature | Endpoint | Documentation |
|---------|----------|---------------|
| Product CRUD | `/api/employee/products/*` | [PRODUCT_API.md](../product/2026_01_13_PRODUCT_API.md) |
| Variant Management | `/api/employee/products/{id}/variants/*` | [PRODUCT_VARIANTS_API.md](../product/2026_01_13_PRODUCT_VARIANTS_API.md) |
| SKU Auto-Generation | Product create | [SKU_AUTO_GENERATION_API_CHANGE.md](./SKU_AUTO_GENERATION_API_CHANGE.md) |

---

## TypeScript Interfaces

```typescript
// Single Product Update
interface UpdateProductRequest {
  name?: string;
  description?: string;
  category_id?: number;
  vendor_id?: number | null;
  brand?: string | null;
  sku?: string;
  custom_fields?: {
    field_id: number;
    value: string | number | boolean | null;
  }[];
}

// Bulk Update
interface BulkUpdateRequest {
  product_ids: number[];
  action: 'archive' | 'restore' | 'update_category' | 'update_vendor';
  category_id?: number;  // Required if action = 'update_category'
  vendor_id?: number;    // Required if action = 'update_vendor'
}

// Custom Field Update
interface UpdateCustomFieldRequest {
  field_id: number;
  value: string | number | boolean | null;
}
```

---

**Author:** Backend Team  
**Last Updated:** January 29, 2026
