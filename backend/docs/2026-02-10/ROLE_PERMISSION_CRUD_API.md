# Role & Permission Management API

## Overview

Complete CRUD APIs for managing roles, permissions, and assigning permissions to roles.

---

## Roles API

### Base URL: `/api/roles`

### List All Roles
```
GET /api/roles
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `is_active` | boolean | Filter by active status |
| `guard_name` | string | Filter by guard (`api` or `web`) |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Super Admin",
            "slug": "super-admin",
            "description": "Full system access",
            "guard_name": "web",
            "level": 100,
            "is_active": true,
            "is_default": false,
            "permissions": [
                { "id": 1, "slug": "employees.view", "title": "View Employees" },
                { "id": 2, "slug": "employees.create", "title": "Create Employees" }
            ]
        }
    ]
}
```

---

### Create Role
```
POST /api/roles
```

**Request Body:**
```json
{
    "title": "Store Manager",
    "description": "Manages store operations",
    "guard_name": "web",
    "level": 70,
    "is_active": true,
    "is_default": false,
    "permission_ids": [1, 2, 5, 10]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | Yes | Role name |
| `description` | string | No | Role description |
| `guard_name` | string | Yes | `api` or `web` |
| `level` | integer | No | Priority level (higher = more senior) |
| `is_active` | boolean | No | Enable/disable role (default: true) |
| `is_default` | boolean | No | Default role for new employees |
| `permission_ids` | array | No | Array of permission IDs to assign |

**Response (201):**
```json
{
    "success": true,
    "message": "Role created successfully",
    "data": {
        "id": 5,
        "title": "Store Manager",
        "slug": "store-manager",
        "permissions": [...]
    }
}
```

---

### Get Single Role
```
GET /api/roles/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Super Admin",
        "slug": "super-admin",
        "description": "Full system access",
        "guard_name": "web",
        "level": 100,
        "is_active": true,
        "is_default": false,
        "permissions": [...]
    }
}
```

---

### Update Role
```
PUT /api/roles/{id}
```

**Request Body:**
```json
{
    "title": "Senior Manager",
    "description": "Updated description",
    "level": 75,
    "is_active": true
}
```

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | New role name (slug auto-updates) |
| `description` | string | Role description |
| `level` | integer | Priority level |
| `is_active` | boolean | Enable/disable role |
| `is_default` | boolean | Default role for new employees |

> Note: `guard_name` cannot be changed after creation.

---

### Delete Role
```
DELETE /api/roles/{id}
```

**Success Response:**
```json
{
    "success": true,
    "message": "Role deleted successfully"
}
```

**Error Response (role in use):**
```json
{
    "success": false,
    "message": "Cannot delete role assigned to 5 employees"
}
```

---

### Assign Permissions to Role
```
POST /api/roles/{id}/permissions
```

**Request Body:**
```json
{
    "permission_ids": [1, 2, 3, 5, 10, 15]
}
```

> This **replaces** all existing permissions with the new list (sync).

**Response:**
```json
{
    "success": true,
    "message": "Permissions assigned successfully",
    "data": {
        "id": 1,
        "title": "Manager",
        "permissions": [
            { "id": 1, "slug": "employees.view" },
            { "id": 2, "slug": "employees.create" },
            ...
        ]
    }
}
```

---

### Remove Permissions from Role
```
DELETE /api/roles/{id}/permissions
```

**Request Body:**
```json
{
    "permission_ids": [3, 5]
}
```

> This **removes** only the specified permissions.

**Response:**
```json
{
    "success": true,
    "message": "Permissions removed successfully",
    "data": {
        "id": 1,
        "title": "Manager",
        "permissions": [...remaining permissions...]
    }
}
```

---

### Get Role Statistics
```
GET /api/roles/statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_roles": 8,
        "active_roles": 7,
        "inactive_roles": 1,
        "by_guard": {
            "web": 6,
            "api": 2
        }
    }
}
```

---

## Permissions API

### Base URL: `/api/permissions`

### List All Permissions
```
GET /api/permissions
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `is_active` | boolean | Filter by active status |
| `module` | string | Filter by module (e.g., `employees`, `products`) |
| `guard_name` | string | Filter by guard (`api` or `web`) |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "View Employees",
            "slug": "employees.view",
            "description": "Can view employee list and details",
            "module": "employees",
            "guard_name": "web",
            "is_active": true
        },
        {
            "id": 2,
            "title": "Create Employees",
            "slug": "employees.create",
            ...
        }
    ]
}
```

---

### Create Permission
```
POST /api/permissions
```

**Request Body:**
```json
{
    "title": "Export Reports",
    "description": "Can export reports to CSV/Excel",
    "module": "reports",
    "guard_name": "web",
    "is_active": true
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | Yes | Permission name |
| `description` | string | No | Description |
| `module` | string | Yes | Module name (e.g., `employees`, `orders`) |
| `guard_name` | string | Yes | `api` or `web` |
| `is_active` | boolean | No | Enable/disable (default: true) |

> Slug is auto-generated: `{module}-{title}` → `reports-export-reports`

**Response (201):**
```json
{
    "success": true,
    "message": "Permission created successfully",
    "data": {
        "id": 50,
        "title": "Export Reports",
        "slug": "reports-export-reports",
        "module": "reports",
        "guard_name": "web",
        "is_active": true
    }
}
```

---

### Get Single Permission
```
GET /api/permissions/{id}
```

---

### Update Permission
```
PUT /api/permissions/{id}
```

**Request Body:**
```json
{
    "title": "Export All Reports",
    "description": "Updated description",
    "module": "reports",
    "is_active": true
}
```

> Note: `guard_name` cannot be changed after creation.

---

### Delete Permission
```
DELETE /api/permissions/{id}
```

**Response:**
```json
{
    "success": true,
    "message": "Permission deleted successfully"
}
```

---

### Get Permissions by Module
```
GET /api/permissions/by-module
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "module": "employees",
            "permissions": "View Employees,Create Employees,Edit Employees,Delete Employees",
            "count": 4
        },
        {
            "module": "products",
            "permissions": "View Products,Create Products,Edit Products,Delete Products",
            "count": 4
        }
    ]
}
```

---

### Get Permission Statistics
```
GET /api/permissions/statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_permissions": 85,
        "active_permissions": 85,
        "by_module": {
            "employees": 6,
            "products": 8,
            "orders": 5,
            "inventory": 3,
            ...
        },
        "by_guard": {
            "web": 85,
            "api": 0
        }
    }
}
```

---

## Assign Role to Employee

### Change Employee Role
```
PATCH /api/employees/{id}/role
```

**Request Body:**
```json
{
    "role_id": 3
}
```

**Response:**
```json
{
    "success": true,
    "message": "Employee role changed successfully",
    "data": {
        "id": 5,
        "name": "John Doe",
        "role": {
            "id": 3,
            "title": "Manager",
            "slug": "manager"
        }
    }
}
```

---

## Common Workflows

### 1. Create a New Role with Permissions

```javascript
// Step 1: Get all available permissions
const { data: permissions } = await api.get('/permissions');

// Step 2: Select permissions you want (e.g., by module)
const orderPermissions = permissions.filter(p => p.module === 'orders');
const customerPermissions = permissions.filter(p => p.module === 'customers');

// Step 3: Create role with permissions
const response = await api.post('/roles', {
    title: 'Sales Representative',
    description: 'Can manage orders and customers',
    guard_name: 'web',
    level: 50,
    permission_ids: [
        ...orderPermissions.map(p => p.id),
        ...customerPermissions.map(p => p.id)
    ]
});
```

### 2. Add Permissions to Existing Role

```javascript
// Get current role
const { data: role } = await api.get('/roles/3');
const currentPermissionIds = role.permissions.map(p => p.id);

// Add new permissions
const newPermissions = [15, 16, 17];
const allPermissions = [...currentPermissionIds, ...newPermissions];

// Sync all permissions
await api.post('/roles/3/permissions', {
    permission_ids: allPermissions
});
```

### 3. Remove Specific Permissions from Role

```javascript
// Remove only specific permissions (keeps others)
await api.delete('/roles/3/permissions', {
    data: {
        permission_ids: [15, 16]
    }
});
```

### 4. Assign Role to Employee

```javascript
await api.patch('/employees/5/role', {
    role_id: 3
});
```

---

## Permission Required

| Endpoint | Required Permission |
|----------|---------------------|
| All `/roles/*` endpoints | `roles.view`, `roles.create`, `roles.edit`, or `roles.delete` |
| All `/permissions/*` endpoints | `permissions.manage` |
| `PATCH /employees/{id}/role` | `employees.manage_roles` |

---

## Best Practices

1. **Don't delete permissions in production** - This may break roles that use them
2. **Use meaningful module names** - Group related permissions together
3. **Set appropriate level** - Higher level = more senior role
4. **Test role changes** - Verify users can still access required features
5. **Document custom permissions** - Keep track of new permissions added

---

## Existing Modules

Common modules already seeded in the system:
- `dashboard` - Dashboard access
- `employees` - Employee management
- `products` - Product catalog
- `product_batches` - Batch management
- `categories` - Category management
- `orders` - Order management
- `customers` - Customer management
- `inventory` - Inventory operations
- `purchase_orders` - Purchase order management
- `vendors` - Vendor management
- `expenses` - Expense tracking
- `accounts` - Chart of accounts
- `transactions` - Financial transactions
- `reports` - Reporting
- `roles` - Role management
- `permissions` - Permission management
- `stores` - Store management
- `shipments` - Shipment/delivery
- `services` - Service management
- `service_orders` - Service orders

---

## Error Codes

| Status | Meaning |
|--------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad request (e.g., role in use) |
| 403 | Permission denied |
| 404 | Role/Permission not found |
| 422 | Validation error |
