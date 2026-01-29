# SKU Auto-Generation Feature

**Date:** January 29, 2026  
**Type:** API Enhancement  
**Affected Endpoint:** `POST /api/products`

---

## Summary

The `sku` field is now **optional** when creating products. If not provided, the system automatically generates a **unique 9-digit number** as the SKU.

---

## Changes

### Before (Previous Behavior)
- `sku` was **required**
- API returned 422 validation error if `sku` was not provided

### After (New Behavior)
- `sku` is **optional**
- If omitted or null, system auto-generates a unique 9-digit SKU
- If provided, the supplied SKU is used as-is

---

## API Specification

### Create Product

**Endpoint:** `POST /api/products`

**Request Body:**

```json
{
  "category_id": 1,
  "vendor_id": 2,
  "name": "Classic T-Shirt",
  "brand": "BrandName",
  "description": "A comfortable cotton t-shirt",
  "sku": "TSHIRT-001",
  "custom_fields": [
    { "field_id": 1, "value": "Red" }
  ]
}
```

### Field Changes

| Field | Previous | New | Notes |
|-------|----------|-----|-------|
| `sku` | required, string | **nullable**, string, max:255 | Auto-generated if not provided |

---

## Auto-Generated SKU Format

| Property | Value |
|----------|-------|
| Length | 9 digits |
| Range | 100000000 - 999999999 |
| Type | Numeric string |
| Uniqueness | Checked against all products (including soft-deleted) |

**Examples of auto-generated SKUs:**
- `847293651`
- `192847563`
- `538291746`

---

## Usage Examples

### Example 1: With SKU (Manual)

**Request:**
```json
{
  "category_id": 1,
  "name": "Premium Widget",
  "sku": "WDG-PREM-001"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 42,
    "sku": "WDG-PREM-001",
    "name": "Premium Widget",
    ...
  }
}
```

### Example 2: Without SKU (Auto-Generated)

**Request:**
```json
{
  "category_id": 1,
  "name": "Basic Widget"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 43,
    "sku": "738291456",
    "name": "Basic Widget",
    ...
  }
}
```

### Example 3: With Null SKU (Auto-Generated)

**Request:**
```json
{
  "category_id": 1,
  "name": "Standard Widget",
  "sku": null
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 44,
    "sku": "294817365",
    "name": "Standard Widget",
    ...
  }
}
```

---

## Frontend Integration Notes

### Form Changes

1. **SKU Field:** Change from required to optional
2. **Placeholder:** Consider adding placeholder text like "Auto-generated if left empty"
3. **Validation:** Remove client-side required validation for SKU

### Example Form Implementation

```jsx
// Before
<input 
  name="sku" 
  required 
  placeholder="Enter SKU" 
/>

// After
<input 
  name="sku" 
  placeholder="Enter SKU (auto-generated if empty)" 
/>
```

### TypeScript Interface Update

```typescript
// Before
interface CreateProductRequest {
  category_id: number;
  name: string;
  sku: string;  // required
  // ...
}

// After
interface CreateProductRequest {
  category_id: number;
  name: string;
  sku?: string | null;  // optional
  // ...
}
```

---

## Validation Rules Summary

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `category_id` | integer | Yes | Must exist in categories |
| `vendor_id` | integer | No | Must exist in vendors if provided |
| `brand` | string | No | Max 255 characters |
| `sku` | string | **No** | Max 255 characters, auto-generated if empty |
| `name` | string | Yes | Max 255 characters |
| `description` | string | No | - |
| `custom_fields` | array | No | Array of field objects |

---

## Error Handling

This change does not introduce new error cases. The auto-generation is handled internally and is designed to always succeed.

**Fallback Mechanism:** If random generation fails after 10 attempts (highly unlikely), a timestamp-based SKU is generated as fallback.

---

## Backward Compatibility

✅ **Fully backward compatible**

- Existing API calls with `sku` provided will continue to work exactly as before
- Only affects calls where `sku` is omitted or null

---

## Related Files Modified

| File | Change |
|------|--------|
| `app/Models/Product.php` | Added `boot()` method with auto-generation logic |
| `app/Models/Product.php` | Added `generateUniqueSku()` static method |
| `app/Http/Controllers/ProductController.php` | Changed `sku` validation from `required` to `nullable` |

---

## Testing Checklist

- [ ] Create product without SKU → should auto-generate 9-digit SKU
- [ ] Create product with SKU → should use provided SKU
- [ ] Create product with `sku: null` → should auto-generate SKU
- [ ] Create product with `sku: ""` (empty string) → should auto-generate SKU
- [ ] Create multiple products without SKU → each should have unique SKU
- [ ] Verify auto-generated SKU is exactly 9 digits
- [ ] Verify auto-generated SKU doesn't conflict with existing SKUs

---

## Contact

For questions about this change, contact the backend team.
