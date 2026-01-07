# Category Hard Delete API

**Document Created:** January 8, 2026  
**Purpose:** Permanent category deletion endpoint  
**Backend Support:** ✅ Implemented

---

## Overview

A new API endpoint has been added to **permanently delete** categories from the database. This is different from the existing delete endpoint which only soft-deletes (deactivates) categories.

---

## API Endpoint

### **Hard Delete Category**

```
DELETE /api/employee/categories/{id}/hard-delete
```

**Authentication:** Required (JWT Bearer Token)

**Headers:**
```json
{
  "Authorization": "Bearer {employee_token}",
  "Content-Type": "application/json"
}
```

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Category ID to permanently delete |

---

## Request Example

```bash
DELETE /api/employee/categories/5/hard-delete
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## Response Examples

### **Success Response (200)**

```json
{
  "success": true,
  "message": "Category 'Electronics' has been permanently deleted"
}
```

### **Error: Category Has Subcategories (400)**

```json
{
  "success": false,
  "message": "Cannot permanently delete category with subcategories. Delete subcategories first."
}
```

### **Error: Category Has Products (400)**

```json
{
  "success": false,
  "message": "Cannot permanently delete category with associated products. Remove products first."
}
```

### **Error: Category Not Found (404)**

```json
{
  "message": "No query results for model [App\\Models\\Category] {id}"
}
```

---

## Differences Between Delete Endpoints

| Feature | DELETE `/categories/{id}` | DELETE `/categories/{id}/hard-delete` |
|---------|--------------------------|--------------------------------------|
| **Action** | Soft delete (deactivate) | Permanent deletion |
| **Database** | Sets `is_active = false` | Removes from database completely |
| **Reversible** | Yes (can reactivate) | ❌ No (permanent) |
| **Image Deletion** | ❌ No | ✅ Yes (removes from storage) |
| **Use Case** | Hide category temporarily | Remove category forever |

---

## Business Rules

### **Pre-Deletion Validations:**

1. **No Subcategories**
   - Category cannot have any child categories
   - Must delete or move all subcategories first
   - Checks both active and soft-deleted subcategories

2. **No Associated Products**
   - Category cannot have any products assigned to it
   - Must remove or reassign products to other categories first

3. **Image Cleanup**
   - If category has an image, it will be automatically deleted from storage
   - Image path: `storage/app/public/categories/{image_name}`

---

## Frontend Implementation Guide

### **Step 1: Add Confirmation Dialog**

Before calling the API, show a strong warning:

```javascript
async function confirmHardDelete(categoryId, categoryName) {
  const confirmed = await showConfirmDialog({
    title: 'Permanently Delete Category?',
    message: `Are you sure you want to PERMANENTLY delete "${categoryName}"? This action cannot be undone.`,
    type: 'danger',
    confirmText: 'Yes, Delete Forever',
    cancelText: 'Cancel'
  });
  
  if (confirmed) {
    await hardDeleteCategory(categoryId);
  }
}
```

### **Step 2: Call Hard Delete API**

```javascript
async function hardDeleteCategory(categoryId) {
  try {
    const response = await fetch(
      `/api/employee/categories/${categoryId}/hard-delete`,
      {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${employeeToken}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (response.ok && data.success) {
      showSuccessToast(data.message);
      // Refresh category list
      await fetchCategories();
    } else {
      showErrorToast(data.message);
    }
    
  } catch (error) {
    showErrorToast('Failed to delete category. Please try again.');
    console.error('Hard delete error:', error);
  }
}
```

### **Step 3: Handle Validation Errors**

```javascript
async function hardDeleteCategory(categoryId) {
  try {
    const response = await fetch(
      `/api/employee/categories/${categoryId}/hard-delete`,
      { method: 'DELETE', headers: {...} }
    );
    
    const data = await response.json();
    
    if (!response.ok) {
      if (response.status === 400) {
        // Validation error - show specific message
        if (data.message.includes('subcategories')) {
          showErrorDialog({
            title: 'Cannot Delete',
            message: 'This category has subcategories. Please delete or move them first.'
          });
        } else if (data.message.includes('products')) {
          showErrorDialog({
            title: 'Cannot Delete',
            message: 'This category has associated products. Please remove or reassign them first.'
          });
        }
      }
      return;
    }
    
    showSuccessToast('Category permanently deleted');
    await fetchCategories();
    
  } catch (error) {
    showErrorToast('An error occurred');
  }
}
```

---

## UI/UX Recommendations

### **1. Separate Delete Actions**

Make the hard delete visually distinct from soft delete:

```
Category Actions Menu:
├─ Edit Category
├─ Deactivate (soft delete) ← Safe, reversible
└─ Delete Permanently ← Dangerous, requires confirmation
```

### **2. Visual Warning**

Use red/danger colors for the permanent delete button:

```html
<!-- Soft Delete (Safe) -->
<button class="btn-warning">
  Deactivate Category
</button>

<!-- Hard Delete (Dangerous) -->
<button class="btn-danger">
  <Icon name="trash" />
  Delete Forever
</button>
```

### **3. Multi-Step Confirmation**

For extra safety, consider:
1. First click → Show warning modal
2. User types category name to confirm
3. Final confirmation → Execute deletion

```javascript
const typedName = prompt(`Type "${categoryName}" to confirm deletion:`);
if (typedName === categoryName) {
  await hardDeleteCategory(categoryId);
}
```

### **4. Pre-Check Before Delete**

Optionally, fetch category details first to show blockers:

```javascript
async function checkBeforeDelete(categoryId) {
  const category = await fetchCategory(categoryId);
  
  const blockers = [];
  if (category.subcategories_count > 0) {
    blockers.push(`Has ${category.subcategories_count} subcategories`);
  }
  if (category.products_count > 0) {
    blockers.push(`Has ${category.products_count} products`);
  }
  
  if (blockers.length > 0) {
    showWarning(`Cannot delete: ${blockers.join(', ')}`);
    return false;
  }
  
  return confirmHardDelete(categoryId, category.title);
}
```

---

## Common Scenarios

### **Scenario 1: Delete Empty Category**

```
Category: "Test Category"
Subcategories: 0
Products: 0

Action: Can be deleted immediately
Result: ✅ Success
```

### **Scenario 2: Category with Subcategories**

```
Category: "Electronics"
Subcategories: 3 (Phones, Laptops, Tablets)
Products: 0

Action: Cannot delete
Result: ❌ Error - Delete subcategories first
```

### **Scenario 3: Category with Products**

```
Category: "Phones"
Subcategories: 0
Products: 25

Action: Cannot delete
Result: ❌ Error - Remove products first
```

### **Scenario 4: Soft-Deleted Category**

```
Category: "Old Category" (deleted_at: 2025-12-01)
Status: Soft deleted

Action: Can still be hard deleted
Result: ✅ Success - Permanently removed
```

---

## Testing Checklist

- [ ] Hard delete empty category - Should succeed
- [ ] Try to delete category with subcategories - Should fail with error
- [ ] Try to delete category with products - Should fail with error
- [ ] Verify category image is deleted from storage
- [ ] Verify category is removed from database (not just marked deleted)
- [ ] Test with soft-deleted category - Should still work
- [ ] Verify error messages are user-friendly
- [ ] Test confirmation dialog shows proper warning

---

## Related Endpoints

### **Get All Categories**
```
GET /api/employee/categories
```

### **Soft Delete Category (Deactivate)**
```
DELETE /api/employee/categories/{id}
```

### **Reactivate Category**
```
PATCH /api/employee/categories/{id}/activate
```

---

## Important Notes

⚠️ **Warning:** This is a destructive operation that cannot be undone!

✅ **Best Practice:** Always soft delete first, then hard delete later if needed

🔒 **Permission:** Ensure only authorized employees can perform hard deletes

📸 **Image Cleanup:** Category images are automatically deleted from storage

🔗 **Dependencies:** Check subcategories and products before deletion

---

## Error Handling Summary

| Error Code | Cause | Solution |
|-----------|-------|----------|
| 400 | Has subcategories | Delete or move subcategories first |
| 400 | Has products | Remove or reassign products |
| 404 | Category not found | Check category ID is valid |
| 401 | Unauthorized | Check authentication token |
| 500 | Server error | Contact backend team |

---

**Document Version:** 1.0  
**Last Updated:** January 8, 2026  
**Status:** Production Ready ✅
