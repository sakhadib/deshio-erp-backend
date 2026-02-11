# Category-Based Tax System - Frontend Integration Guide

**Date:** February 11, 2026  
**Impact:** Category Management, Product Display, Batch Creation  
**Breaking Changes:** None (Backward compatible)

## What Changed

### Before ❌
Tax was defined at **batch level**. Each batch could have different tax percentages, even for the same product.

### Now ✅
Tax is defined at **category level**. All products under a category inherit the same tax percentage.

### Priority Rule
**Category Tax > Batch Tax**

- If category has tax > 0% → Use category tax
- If category has no tax but batch has tax → Use batch tax (fallback for old data)
- If both are 0% → No tax applied

---

## API Changes

### 1. Categories API

#### GET `/api/categories`
**Response now includes:**
```json
{
  "id": 1,
  "title": "Electronics",
  "description": "Electronic products",
  "slug": "electronics",
  "order": 1,
  "is_active": true,
  "tax_percentage": "15.00",  // ← NEW FIELD
  "parent_id": null,
  "created_at": "2026-02-11T10:00:00Z",
  "updated_at": "2026-02-11T10:00:00Z"
}
```

#### POST `/api/categories`
**Now accepts:**
```json
{
  "title": "Electronics",
  "description": "Electronic products",
  "color": "#3B82F6",
  "icon": "laptop",
  "order": 1,
  "parent_id": null,
  "tax_percentage": 15.0  // ← NEW FIELD (optional, 0-100)
}
```

#### PUT `/api/categories/{id}`
**Now accepts:**
```json
{
  "title": "Electronics Updated",
  "tax_percentage": 18.0  // ← Can update tax percentage
}
```

**Validation Rules:**
- `tax_percentage`: Optional, numeric, min: 0, max: 100
- Accepts decimal values (e.g., 15.5 for 15.5%)

---

## Frontend Changes Required

### 1. Category Management Forms

#### Category Create/Edit Form
**Add new field:**
- Field name: `tax_percentage`
- Input type: Number
- Min: 0
- Max: 100
- Step: 0.01 (allows decimals like 15.5%)
- Default: 0
- Label: "Tax Percentage (%)" or "VAT/Tax Rate (%)"
- Help text: "Tax rate applied to all products in this category"

**Field placement:** Add after "Order" field or before "Parent Category"

#### Category List/Table View
**Add column:**
- Column: "Tax %"
- Display: `{category.tax_percentage}%` (e.g., "15.00%")
- Sort: Allow sorting by tax percentage
- Filter: Allow filtering by tax rate ranges

#### Category Details View
**Display tax info:**
- Show tax percentage prominently
- Add info badge: "Applies to all products in this category"

---

### 2. Product Display Changes

#### Product Details/Edit Page
**Show inherited tax (Read-only):**
- Label: "Tax Rate (from Category)"
- Value: `{product.category.tax_percentage}%`
- Style: Display as read-only info, not editable field
- Location: In pricing section or product details section

**Example info display:**
```
Product: iPhone 15 Pro
Category: Electronics
Tax Rate: 15% (inherited from category)
```

#### Product List/Table View
**Optional enhancement:**
- Add "Tax %" column showing inherited tax from category
- Show: `{product.category.tax_percentage}%`

---

### 3. Batch Creation/Edit Forms

#### REMOVE Tax Input
**Previously had:** 
- Field: `tax_percentage` input on batch creation

**Now:**
- **REMOVE** the tax percentage input field from batch forms
- Tax is inherited from product's category automatically

#### Show Inherited Tax Info
**Add informational text:**
- Display: "Tax: {product.category.tax_percentage}% (inherited from category)"
- Style: Read-only text, not input field
- Location: In pricing section along with cost_price and sell_price

**Example:**
```
Cost Price: ₹ [input field]
Sell Price: ₹ [input field]
Tax Rate: 15% (from Electronics category) [read-only info]
```

---

### 4. Order/Checkout Display

#### No Changes Required ✅
Order calculation is handled by backend. Tax extraction is automatic.

**What backend does:**
- Reads category tax for each product
- Calculates tax from inclusive price
- Returns `tax_amount` in order response

**Your existing code continues to work** - just display the `tax_amount` from API response.

---

## Data Flow

### When User Creates Category:
1. User enters `tax_percentage` in category form (e.g., 15)
2. Frontend sends to: `POST /api/categories` with `tax_percentage: 15.0`
3. Backend stores in `categories.tax_percentage`
4. ✅ All products in this category now have 15% tax

### When User Creates Product:
1. User selects category (e.g., "Electronics" with 15% tax)
2. Frontend shows: "Tax: 15% (inherited from category)"
3. Product automatically inherits category tax
4. No tax field needed in product form

### When User Creates Batch:
1. Backend reads product → category → tax_percentage
2. Backend auto-calculates base_price and tax_amount
3. ✅ Tax applied automatically from category

### When Order is Created:
1. Backend reads batch → product → category → tax_percentage
2. Backend extracts tax from item prices
3. Returns order with `tax_amount` per item
4. ✅ Frontend displays the returned tax amounts

---

## Migration Impact on Existing Data

### Existing Categories
- All existing categories now have `tax_percentage: 0.00` (default)
- **Action Required:** Update existing categories with appropriate tax rates
- Use category edit API to set tax percentages

### Existing Products
- Continue to work normally
- Will inherit tax from category once category tax is set
- No changes needed in product data

### Existing Batches
- Old batches with batch-level tax still work as fallback
- Category tax takes priority if set
- No data loss

### Existing Orders
- Remain unchanged
- Tax already calculated and stored
- No retroactive changes

---

## UI/UX Recommendations

### Category Management
**Visual indicators:**
- Show badge on categories with tax configured
- Highlight categories with 0% tax (might need setup)
- Color-code tax rates (e.g., red for high tax, green for low)

**Bulk operations:**
- Add "Set Tax %" bulk action for multiple categories
- Allow tax rate update for category groups

### Product Pages
**Clarity:**
- Make it clear tax is inherited, not editable at product level
- Show category name with tax rate: "Electronics (15% tax)"
- Link to category edit if user wants to change tax

### Batch Forms
**Simplification:**
- Remove tax input complexity
- Show clear indicator tax is automatic
- Display calculated base_price and tax_amount after entering sell_price (optional)

---

## API Testing Examples

### Test 1: Create Category with Tax
```http
POST /api/categories
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Electronics",
  "slug": "electronics",
  "tax_percentage": 15.0
}

Response: 200 OK
{
  "success": true,
  "category": {
    "id": 1,
    "title": "Electronics",
    "tax_percentage": "15.00",
    ...
  }
}
```

### Test 2: Update Category Tax
```http
PUT /api/categories/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "tax_percentage": 18.0
}

Response: 200 OK
{
  "success": true,
  "category": {
    "id": 1,
    "tax_percentage": "18.00",
    ...
  }
}
```

### Test 3: Get Category with Tax
```http
GET /api/categories/1
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "category": {
    "id": 1,
    "title": "Electronics",
    "tax_percentage": "15.00",
    ...
  }
}
```

---

## Validation & Error Handling

### Tax Percentage Validation
**Backend validates:**
- Must be numeric
- Min: 0
- Max: 100
- Accepts decimals (e.g., 15.5)

**Frontend should:**
- Validate before submit: 0 ≤ value ≤ 100
- Show error if out of range
- Allow decimal input (step: 0.01)
- Default to 0 if empty

**Error example:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "tax_percentage": [
      "The tax percentage must be between 0 and 100."
    ]
  }
}
```

---

## FAQs for Frontend Team

### Q: Do I need to show batch-level tax anywhere?
**A:** No. You can safely remove batch tax fields. Backend handles fallback automatically.

### Q: What if a category has no tax set?
**A:** Display "0%" or "No tax". Backend defaults to 0 when not set.

### Q: Can admins change category tax after products are added?
**A:** Yes. New tax applies to all future orders. Old orders remain unchanged.

### Q: Do I need to change order/invoice displays?
**A:** No. Backend calculates tax and returns in order response. Your current display logic works.

### Q: Should I show tax in product search results?
**A:** Optional. You can show `product.category.tax_percentage` if helpful for users.

### Q: What about products without categories?
**A:** Products must have categories in this system. If product has no category (edge case), tax defaults to 0%.

### Q: Can I still send `tax_percentage` in batch creation?
**A:** Yes, but it will be overridden by category tax if category has tax > 0. Better to not send it.

---

## Implementation Checklist

### Phase 1: Category Management (Priority: HIGH)
- [ ] Add `tax_percentage` field to category create form
- [ ] Add `tax_percentage` field to category edit form
- [ ] Add "Tax %" column to category list table
- [ ] Add tax display to category details view
- [ ] Test category create/update with tax values

### Phase 2: Product Display (Priority: MEDIUM)
- [ ] Show inherited tax on product details page (read-only)
- [ ] Add tax info to product edit page
- [ ] Update product list to show category tax (optional)
- [ ] Test that tax displays correctly for products

### Phase 3: Batch Forms (Priority: HIGH)
- [ ] Remove tax_percentage input from batch create form
- [ ] Remove tax_percentage input from batch edit form
- [ ] Add "Tax inherited from category" info text
- [ ] Test batch creation without tax field

### Phase 4: Data Setup (Priority: HIGH)
- [ ] Update all existing categories with appropriate tax rates
- [ ] Verify tax displays correctly across all screens
- [ ] Test order creation with new tax system

### Phase 5: Documentation (Priority: LOW)
- [ ] Update internal frontend docs
- [ ] Train team on new tax system
- [ ] Update user guides if needed

---

## Support

### Backend API Reference
- Full migration doc: `/backend/CATEGORY_TAX_MIGRATION.md`
- Base URL: `{API_URL}/api`
- Auth: Bearer token in Authorization header

### Questions?
Contact backend team with any technical questions about the API endpoints or tax calculation logic.

---

**Summary:**
- ✅ Add tax field to category forms
- ✅ Remove tax field from batch forms
- ✅ Display inherited tax on product pages
- ✅ No changes needed for orders/checkout
- ✅ Backward compatible - no breaking changes

**Timeline:** Can be implemented incrementally. Category management is highest priority.
