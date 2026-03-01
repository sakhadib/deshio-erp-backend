# Product Force Delete API

**Date:** February 24, 2026  
**Priority:** HIGH  
**Status:** ✅ READY  
**Permission:** Admin Only (system.settings.edit)

---

## Purpose

Provides a way to permanently delete test products created on the production environment, including all associated stock, batches, barcodes, and related data.

**⚠️ WARNING:** This is a destructive operation that cannot be undone. Use only for cleaning up test data.

---

## API Endpoint

**URL:** `DELETE /api/employee/products/{id}/force-delete`

**Authentication:** Required (Employee JWT token)

**Permission Required:** `system.settings.edit` (Admin-level only)

**Note:** Super Admin role bypasses all permission checks automatically.

---

## Request

### Headers
```
Authorization: Bearer {admin_jwt_token}
Content-Type: application/json
```

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the product to permanently delete |

### Example Request

```bash
curl -X DELETE https://api.example.com/api/employee/products/123/force-delete \
  -H "Authorization: Bearer {admin_token}"
```

---

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Product and all related data permanently deleted",
  "data": {
    "product_id": 123,
    "product_name": "Test Product - Red - Large",
    "product_sku": "TEST123456",
    "deleted_at": "2026-02-24T16:45:00+06:00",
    "batches_deleted": 5,
    "barcodes_deleted": 12,
    "images_deleted": 3,
    "movements_deleted": 8,
    "order_items_affected": 2,
    "dispatch_item_barcodes_deleted": 4,
    "defective_products_deleted": 1,
    "rebalancings_deleted": 0,
    "master_inventories_deleted": 1,
    "cart_items_deleted": 0,
    "wishlist_items_deleted": 0,
    "collection_products_deleted": 0,
    "ad_campaign_products_deleted": 0,
    "fields_deleted": 2,
    "price_overrides_deleted": 1,
    "variants_deleted": 0,
    "purchase_order_items_deleted": 2,
    "empty_purchase_orders": 0
  }
}
```

### Error Responses

#### Product Not Found (404)

```json
{
  "success": false,
  "message": "Product not found"
}
```

#### Permission Denied (403)

```json
{
  "success": false,
  "message": "You do not have permission to perform this action",
  "required_permissions": ["system.settings.edit"]
}
```

#### Deletion Failed (500)

```json
{
  "success": false,
  "message": "Failed to delete product: {error_details}"
}
```

---

## What Gets Deleted

This endpoint permanently removes:

### 1. Product Data
- ✅ Product record (even if soft-deleted)
- ✅ Product fields (custom attributes)
- ✅ Product variants
- ✅ Product images
- ✅ Product price overrides

### 2. Inventory & Stock
- ✅ All product batches (stock records)
- ✅ All product barcodes (physical unit tracking)
- ✅ Product movements (transfer history)
- ✅ Master inventories
- ✅ Inventory rebalancings

### 3. Sales & Orders
- ✅ Order items containing this product
- ✅ Cart items
- ✅ Wishlist items

### 4. Dispatch & Quality
- ✅ Dispatch item barcodes (scan records)
- ✅ Defective product records

### 5. Marketing & Collections
- ✅ Collection associations
- ✅ Ad campaign associations

### 6. Purchase Orders
- ✅ Purchase order items deleted (items referencing this product)
- ✅ Purchase order totals automatically recalculated
- ⚠️ If purchase order has only this product, PO becomes empty (no items left)

---

## Safety Features

### Transaction Safety
- All deletions happen within a database transaction
- If any step fails, entire operation rolls back
- No partial deletions occur

### Permission Control
- Requires `system.settings.edit` permission
- Super Admin role automatically has access
- Regular employees cannot access this endpoint

### Audit Trail
- Deletion summary returned in response
- Shows exactly what was deleted and how many records
- Includes product details for verification

---

## Use Cases

### ✅ Appropriate Use

1. **Test Data Cleanup**
   - Remove products created during system testing
   - Clean up demo/training data from production

2. **Duplicate Product Removal**
   - Delete accidentally created duplicate products
   - Remove products created with wrong information

3. **Initial Setup Cleanup**
   - Remove placeholder products from initial deployment
   - Clean up sample data

### ❌ Inappropriate Use

1. **Regular Product Archival**
   - Use archive feature instead (PATCH /products/{id}/archive)
   - Archived products remain in database for historical records

2. **Products with Real Sales**
   - This deletes order history - use with extreme caution
   - Consider archiving instead if product has legitimate sales

3. **Discontinued Products**
   - Archive products when discontinued
   - Force delete only for test/erroneous data

---

## Best Practices

### Before Deletion

1. **Verify Product ID**
   - Double-check the product ID before deletion
   - Review product details to confirm it's test data

2. **Check Dependencies**
   - Review order items count in deletion summary
   - Ensure no critical sales data will be lost

3. **Document Reason**
   - Keep record of why deletion was needed
   - Note product details for reference

### After Deletion

1. **Review Deletion Summary**
   - Verify all expected data was removed
   - Check counts match expectations

2. **Clear Related Caches**
   - Frontend may need cache refresh
   - Consider clearing product catalog cache

3. **Audit Log**
   - Deletion is automatically logged via activity system
   - Check logs to confirm operation completed

---

## Difference from Regular Delete

### Regular Delete (DELETE /products/{id})

- ❌ Fails if product has any batches
- ❌ Fails if product has orders
- ✅ Safe for production use
- ✅ Prevents accidental data loss

### Force Delete (DELETE /products/{id}/force-delete)

- ✅ Deletes product regardless of dependencies
- ✅ Removes all batches and stock
- ✅ Deletes all barcodes
- ⚠️ Powerful but destructive
- ⚠️ Admin permission required

**Recommendation:** Use regular delete whenever possible. Reserve force delete for test data cleanup only.

---

## Technical Notes

### Database Constraints Handled

- Foreign key constraints are properly managed
- Purchase order items use `product_id = null` instead of deletion
- Cascade deletes handled in correct order to avoid constraint violations

### Execution Order

1. Purchase order items unlinked (if any)
2. Product movements deleted
3. Dispatch item barcodes deleted
4. Defective products deleted
5. Inventory rebalancings deleted
6. Master inventories deleted
7. Cart items deleted
8. Wishlist items deleted
9. Collection associations deleted
10. Ad campaign associations deleted
11. Order items cascade deleted (database handles)
12. Product batches deleted
13. Product barcodes deleted
14. Product images deleted
15. Product fields deleted
16. Product price overrides deleted
17. Product variants deleted
18. Product record force deleted

### Performance Considerations

- Large product datasets may take several seconds
- Transaction ensures atomicity
- Database load during deletion proportional to product age/usage

---

## Example Scenarios

### Scenario 1: Delete Single Test Product

**Situation:** Created a test product "Test Saree - Red" with 3 batches

**Request:**
```bash
DELETE /api/employee/products/456/force-delete
```

**Response:**
```json
{
  "success": true,
  "data": {
    "product_id": 456,
    "product_name": "Test Saree - Red",
    "batches_deleted": 3,
    "barcodes_deleted": 5,
    "movements_deleted": 2,
    "order_items_affected": 0
  }
}
```

**Result:** Product and all inventory completely removed.

### Scenario 2: Delete Product with Order History

**Situation:** Test product has 2 order items

**Response:**
```json
{
  "success": true,
  "data": {
    "product_id": 789,
    "product_name": "Test Product ABC",
    "order_items_affected": 2,
    "batches_deleted": 1
  }
}
```

**Impact:** Order records still exist but show product_id = null or deleted product name.

---

## Security Considerations

1. **Admin Only Access**
   - Only users with system admin permissions can use this
   - Regular employees are blocked

2. **Audit Trail**
   - All deletions logged in activity log
   - Who deleted what and when is tracked

3. **No Soft Delete**
   - Bypasses Laravel soft delete mechanism
   - Permanent removal from database

4. **Transaction Rollback**
   - If deletion fails midway, all changes revert
   - Database integrity maintained

---

## Troubleshooting

### "Product not found"
- Product ID doesn't exist
- Product may already be deleted

### "You do not have permission"
- User lacks `system.settings.edit` permission
- User is not admin/super-admin role

### "Failed to delete product: foreign key constraint"
- Unexpected database constraint
- May require manual investigation
- Contact backend developer

---

**Implemented By:** Copilot  
**Tested:** Pending QA verification  
**Deployment:** Backend v2.24.0
