# Service Hard Delete API

**Document Created:** January 18, 2026  
**Feature:** Hard Delete for Services with Bulk Delete Support  
**Status:** ✅ Implemented

## Overview

This document describes the hard delete (force delete) functionality for the Service model, including single and bulk delete operations. This feature allows permanent deletion of services when needed, with proper validation to prevent deletion of services that have associated orders.

## Why Hard Delete?

- **Data Cleanup:** Remove test services or mistakes permanently
- **GDPR Compliance:** Complete data removal when required
- **Database Maintenance:** Clean up unused services to maintain database performance
- **Bulk Operations:** Efficiently delete multiple services at once

## API Endpoints

### 1. Force Delete Single Service

**Endpoint:** `DELETE /api/services/{id}/force`

**Description:** Permanently deletes a service and all its related data.

**Request:**
```http
DELETE /api/services/5/force
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Service 'Premium Dry Cleaning' (Code: SRV-2026-ABC123) permanently deleted",
  "deleted_service": {
    "name": "Premium Dry Cleaning",
    "code": "SRV-2026-ABC123"
  }
}
```

**Error Response (400) - Has Orders:**
```json
{
  "success": false,
  "message": "Cannot force delete service. It has 5 order(s) associated with it.",
  "order_count": 5
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Service not found"
}
```

---

### 2. Bulk Delete Services

**Endpoint:** `POST /api/services/bulk-delete`

**Description:** Delete multiple services at once (soft delete or hard delete).

**Request Body:**
```json
{
  "service_ids": [1, 2, 3, 4, 5],
  "force": true
}
```

**Parameters:**
- `service_ids` (array, required): Array of service IDs to delete
- `force` (boolean, optional): If `true`, performs hard delete. If `false` or omitted, performs soft delete

**Success Response (200):**
```json
{
  "success": true,
  "message": "Deleted 3 service(s), 2 failed",
  "data": {
    "deleted": [
      {
        "id": 1,
        "name": "Basic Laundry",
        "code": "SRV-2026-XYZ001",
        "type": "permanent"
      },
      {
        "id": 2,
        "name": "Express Cleaning",
        "code": "SRV-2026-XYZ002",
        "type": "permanent"
      },
      {
        "id": 4,
        "name": "Shirt Ironing",
        "code": "SRV-2026-XYZ004",
        "type": "permanent"
      }
    ],
    "failed": [
      {
        "id": 3,
        "name": "Premium Dry Clean",
        "code": "SRV-2026-XYZ003",
        "reason": "Has 12 order(s)"
      },
      {
        "id": 5,
        "name": "Alterations",
        "code": "SRV-2026-XYZ005",
        "reason": "Has 8 order(s)"
      }
    ],
    "summary": {
      "total_requested": 5,
      "deleted_count": 3,
      "failed_count": 2
    }
  }
}
```

---

## What Gets Deleted?

When a service is force deleted, the following data is permanently removed:

1. **Service Record** - The main service entry
2. **Service Fields** - All custom field values associated with the service
3. **Field Relationships** - Pivot table entries linking service to fields

**Protected Data:**
- **Service Orders** - Services with existing orders CANNOT be deleted
- Order history is preserved even if service is soft-deleted

---

## Validation Rules

### Single Force Delete
- ✅ Service must exist
- ✅ Service must have NO associated orders
- ✅ All related data (service fields, field pivots) is cleaned up

### Bulk Delete
- ✅ All service IDs must exist in database
- ✅ Each service is validated individually
- ✅ Services with orders are skipped (not deleted)
- ✅ Partial success is allowed (some succeed, some fail)

---

## Difference: Soft Delete vs Hard Delete

### Soft Delete (Regular DELETE)
```http
DELETE /api/services/{id}
```
- Marks service as deleted (`deleted_at` timestamp)
- Data remains in database
- Can be restored later
- Blocks deletion if service has orders

### Hard Delete (Force Delete)
```http
DELETE /api/services/{id}/force
```
- **Permanently removes** service from database
- **Cannot be restored**
- Cleans up all related data
- Still blocks deletion if service has orders

---

## Usage Examples

### Example 1: Delete Single Test Service

```bash
curl -X DELETE \
  https://api.example.com/api/services/123/force \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Example 2: Bulk Delete Multiple Services (Soft Delete)

```bash
curl -X POST \
  https://api.example.com/api/services/bulk-delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "service_ids": [1, 2, 3, 4],
    "force": false
  }'
```

### Example 3: Bulk Hard Delete

```bash
curl -X POST \
  https://api.example.com/api/services/bulk-delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "service_ids": [10, 11, 12],
    "force": true
  }'
```

---

## Frontend Integration Guide

### React/Vue Example: Force Delete Service

```javascript
const forceDeleteService = async (serviceId) => {
  try {
    const response = await axios.delete(
      `/api/services/${serviceId}/force`,
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    );
    
    if (response.data.success) {
      console.log('Service permanently deleted:', response.data.deleted_service);
      // Refresh service list
      fetchServices();
    }
  } catch (error) {
    if (error.response?.status === 400) {
      alert(`Cannot delete: ${error.response.data.message}`);
    } else {
      console.error('Delete failed:', error);
    }
  }
};
```

### React/Vue Example: Bulk Delete

```javascript
const bulkDeleteServices = async (serviceIds, hardDelete = false) => {
  try {
    const response = await axios.post(
      '/api/services/bulk-delete',
      {
        service_ids: serviceIds,
        force: hardDelete
      },
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    );
    
    const { data } = response.data;
    console.log(`Deleted: ${data.summary.deleted_count}`);
    console.log(`Failed: ${data.summary.failed_count}`);
    
    // Show results
    if (data.failed.length > 0) {
      data.failed.forEach(fail => {
        console.warn(`${fail.name}: ${fail.reason}`);
      });
    }
    
    // Refresh list
    fetchServices();
    
  } catch (error) {
    console.error('Bulk delete failed:', error);
  }
};

// Usage
bulkDeleteServices([1, 2, 3], true); // Hard delete
bulkDeleteServices([4, 5, 6], false); // Soft delete
```

---

## Safety Considerations

### ⚠️ Important Warnings

1. **No Undo** - Hard delete is permanent and cannot be reversed
2. **Order Protection** - Services with orders cannot be deleted (prevents data integrity issues)
3. **Cascade Cleanup** - All related service fields and relationships are removed
4. **Audit Trail** - Consider logging deletions for compliance/audit purposes

### Best Practices

1. **Confirm Before Delete**
   ```javascript
   if (confirm('Permanently delete this service? This cannot be undone!')) {
     await forceDeleteService(id);
   }
   ```

2. **Check Order Count First**
   ```javascript
   const service = await getService(id);
   if (service.order_count > 0) {
     alert('Cannot delete service with existing orders');
     return;
   }
   ```

3. **Use Soft Delete by Default**
   - Only use hard delete for test data or confirmed cleanup
   - Soft delete preserves data integrity and audit trails

4. **Bulk Delete Confirmation**
   ```javascript
   const count = selectedServices.length;
   if (confirm(`Delete ${count} services permanently?`)) {
     await bulkDeleteServices(selectedServices, true);
   }
   ```

---

## Error Handling

### Common Error Codes

| Status | Reason | Solution |
|--------|--------|----------|
| 400 | Service has orders | Deactivate service instead, or handle orders first |
| 404 | Service not found | Check service ID is correct |
| 422 | Validation error | Check request format and required fields |
| 401 | Unauthorized | Verify authentication token |
| 500 | Server error | Check logs, contact support |

---

## Testing

### Test Cases

1. **Force Delete Service Without Orders**
   - ✅ Should delete successfully
   - ✅ Should return deleted service info
   - ✅ Should remove all related data

2. **Force Delete Service With Orders**
   - ✅ Should fail with 400 error
   - ✅ Should show order count
   - ✅ Should not delete any data

3. **Bulk Delete Mixed Services**
   - ✅ Should delete services without orders
   - ✅ Should skip services with orders
   - ✅ Should return detailed results

4. **Bulk Delete With Invalid IDs**
   - ✅ Should fail validation
   - ✅ Should return 422 error

---

## Database Schema Impact

### Tables Affected

```sql
-- Main table
DELETE FROM services WHERE id = ?;

-- Related tables
DELETE FROM service_fields WHERE service_id = ?;
DELETE FROM field_service WHERE service_id = ?;
```

### Constraints
- Foreign key on `service_orders.service_id` prevents deletion if orders exist
- Cascade delete handles service_fields and pivot tables

---

## Migration Notes

No database migration required. This feature uses existing tables and relationships.

---

## Related Documentation

- [Service Orders API](./SERVICE_ORDERS_API.md)
- [Service Orders Implementation](./SERVICE_ORDERS_IMPLEMENTATION.md)
- [Category Hard Delete API](./CATEGORY_HARD_DELETE_API.md)

---

## Implementation Details

**Files Modified:**
- `app/Http/Controllers/ServiceController.php` - Added `forceDestroy()` and `bulkDelete()` methods
- `routes/api.php` - Added routes for force delete and bulk delete

**Code Location:**
- Controller: `app/Http/Controllers/ServiceController.php:169-326`
- Routes: `routes/api.php:553-576`

---

## Support

For issues or questions:
- Check error messages for specific guidance
- Review validation rules above
- Ensure services have no orders before force delete
- Contact backend team for assistance

---

**Last Updated:** January 18, 2026  
**Version:** 1.0  
**Maintained By:** Backend Development Team
