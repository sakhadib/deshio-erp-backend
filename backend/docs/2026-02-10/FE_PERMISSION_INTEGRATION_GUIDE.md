# Permission System - Frontend Integration Guide

## Overview

The backend now enforces role-based permissions on all API endpoints. If a user doesn't have the required permission, the API will return a `403 Forbidden` error.

---

## Quick Summary

| Scenario | HTTP Status | Response |
|----------|-------------|----------|
| Valid token + has permission | 200 | Normal response |
| Valid token + **no permission** | **403** | `{ success: false, message: "You do not have permission..." }` |
| Invalid/expired token | 401 | `{ success: false, message: "Unauthenticated" }` |
| Super Admin role | 200 | Always allowed (bypasses all checks) |

---

## Response Format

### 403 Forbidden (No Permission)
```json
{
    "success": false,
    "message": "You do not have permission to perform this action",
    "required_permissions": ["employees.create", "employees.edit"]
}
```

### 401 Unauthorized (Not Logged In)
```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

---

## How to Handle in Frontend

### 1. Global Axios Interceptor (Recommended)

```javascript
// api.js or axios config
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/toast';

const api = axios.create({
    baseURL: process.env.VUE_APP_API_URL,
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const { toast } = useToast();
        
        if (error.response?.status === 403) {
            // Permission denied
            toast.error('You do not have permission to perform this action');
            
            // Optional: Log which permissions were needed
            console.warn('Required permissions:', error.response.data.required_permissions);
            
            // Optional: Redirect to dashboard or show permission error page
            // router.push('/dashboard');
        }
        
        if (error.response?.status === 401) {
            // Not authenticated - logout and redirect
            const authStore = useAuthStore();
            authStore.logout();
            window.location.href = '/login';
        }
        
        return Promise.reject(error);
    }
);

export default api;
```

### 2. Per-Component Error Handling

```javascript
// In component
async function createEmployee() {
    try {
        await api.post('/employees', employeeData);
        toast.success('Employee created!');
    } catch (error) {
        if (error.response?.status === 403) {
            toast.error('You need "Create Employee" permission');
        } else {
            toast.error('Failed to create employee');
        }
    }
}
```

---

## Showing/Hiding UI Elements Based on Permissions

### Get User Permissions

After login, the `/me` endpoint returns the user's role and permissions:

```javascript
// GET /api/me
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": {
        "id": 1,
        "title": "Manager",
        "slug": "manager",
        "permissions": [
            { "id": 1, "slug": "employees.view", "title": "View Employees" },
            { "id": 2, "slug": "employees.create", "title": "Create Employees" },
            { "id": 5, "slug": "orders.view", "title": "View Orders" },
            // ... more permissions
        ]
    }
}
```

### Permission Helper (Vue/React)

```javascript
// stores/auth.js (Pinia example)
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        permissions: [],
    }),
    
    actions: {
        setUser(userData) {
            this.user = userData;
            this.permissions = userData.role?.permissions?.map(p => p.slug) || [];
        },
        
        // Check single permission
        hasPermission(permission) {
            // Super admin bypass
            if (this.user?.role?.slug === 'super-admin') return true;
            return this.permissions.includes(permission);
        },
        
        // Check if user has ANY of the permissions
        hasAnyPermission(permissions) {
            if (this.user?.role?.slug === 'super-admin') return true;
            return permissions.some(p => this.permissions.includes(p));
        },
        
        // Check if user has ALL permissions
        hasAllPermissions(permissions) {
            if (this.user?.role?.slug === 'super-admin') return true;
            return permissions.every(p => this.permissions.includes(p));
        },
    },
});
```

### Conditional Rendering

```vue
<template>
    <!-- Show button only if user can create employees -->
    <button v-if="authStore.hasPermission('employees.create')" @click="openCreateModal">
        Add Employee
    </button>
    
    <!-- Show delete button only if user can delete -->
    <button 
        v-if="authStore.hasPermission('employees.delete')" 
        @click="deleteEmployee(emp.id)"
        class="btn-danger"
    >
        Delete
    </button>
    
    <!-- Show entire section only with any product permission -->
    <div v-if="authStore.hasAnyPermission(['products.view', 'products.create'])">
        <ProductsList />
    </div>
</template>
```

### Vue Directive (Optional)

```javascript
// directives/permission.js
export default {
    mounted(el, binding) {
        const authStore = useAuthStore();
        const permission = binding.value;
        
        if (!authStore.hasPermission(permission)) {
            el.style.display = 'none';
            // Or: el.parentNode?.removeChild(el);
        }
    }
};

// Usage in template
<button v-permission="'employees.create'">Add Employee</button>
```

---

## Permission Slugs Reference

### Employee Management
| Permission Slug | Description |
|-----------------|-------------|
| `employees.view` | View employee list and details |
| `employees.create` | Create new employees |
| `employees.edit` | Edit employee information |
| `employees.delete` | Delete employees |
| `employees.manage_roles` | Assign/change employee roles |

### Product Management
| Permission Slug | Description |
|-----------------|-------------|
| `products.view` | View product catalog |
| `products.create` | Create new products |
| `products.edit` | Edit product information |
| `products.delete` | Delete products |
| `products.import` | Import products from CSV |
| `products.export` | Export products to CSV |
| `products.manage_images` | Upload/manage product images |
| `products.manage_barcodes` | Generate/manage barcodes |

### Order Management
| Permission Slug | Description |
|-----------------|-------------|
| `orders.view` | View orders |
| `orders.create` | Create new orders |
| `orders.edit` | Edit orders |
| `orders.delete` | Delete/cancel orders |
| `orders.fulfill` | Fulfill orders (barcode scanning) |

### Inventory Management
| Permission Slug | Description |
|-----------------|-------------|
| `inventory.view` | View inventory levels |
| `inventory.adjust` | Manually adjust stock |
| `inventory.view_movements` | View movement history |

### Purchase Orders
| Permission Slug | Description |
|-----------------|-------------|
| `purchase_orders.view` | View purchase orders |
| `purchase_orders.create` | Create purchase orders |
| `purchase_orders.edit` | Edit purchase orders |
| `purchase_orders.delete` | Delete purchase orders |
| `purchase_orders.approve` | Approve purchase orders |
| `purchase_orders.receive` | Receive inventory from PO |

### Financial
| Permission Slug | Description |
|-----------------|-------------|
| `expenses.view` | View expenses |
| `expenses.create` | Create expenses |
| `expenses.approve` | Approve expenses |
| `accounts.view` | View chart of accounts |
| `transactions.view` | View transactions |
| `reports.view` | View reports |
| `reports.export` | Export reports |

### Customer Management
| Permission Slug | Description |
|-----------------|-------------|
| `customers.view` | View customers |
| `customers.create` | Create customers |
| `customers.edit` | Edit customers |
| `customers.delete` | Delete customers |

### Store Management
| Permission Slug | Description |
|-----------------|-------------|
| `stores.view` | View stores |
| `stores.create` | Create stores |
| `stores.edit` | Edit stores |
| `stores.delete` | Delete stores |

### Vendor Management
| Permission Slug | Description |
|-----------------|-------------|
| `vendors.view` | View vendors |
| `vendors.create` | Create vendors |
| `vendors.edit` | Edit vendors |
| `vendors.delete` | Delete vendors |

### Shipments
| Permission Slug | Description |
|-----------------|-------------|
| `shipments.view` | View shipments |
| `shipments.create` | Create shipments |
| `shipments.manage` | Manage Pathao integration |

### System
| Permission Slug | Description |
|-----------------|-------------|
| `roles.view` | View roles |
| `roles.create` | Create roles |
| `roles.edit` | Edit roles |
| `roles.delete` | Delete roles |
| `permissions.manage` | Manage permissions |
| `dashboard.view` | View dashboard |
| `dashboard.analytics` | View analytics |
| `activity_logs.view` | View activity logs |
| `recycle_bin.view` | View recycle bin |
| `recycle_bin.restore` | Restore deleted items |
| `recycle_bin.delete` | Permanently delete |

---

## Role Examples

### Super Admin (`super-admin`)
- **Bypasses ALL permission checks**
- Can access everything
- Should be assigned to system administrators only

### Manager (`manager`)
- Typical permissions: All view + create + edit
- Usually cannot delete or access system settings

### Sales Rep (`sales-rep`)
- Typical: `orders.*`, `customers.*`, `products.view`
- Cannot manage inventory or financials

### Warehouse Staff (`warehouse-staff`)
- Typical: `inventory.*`, `product_dispatches.*`, `shipments.*`
- Cannot access orders or financials

### Viewer (`viewer`)
- Only `*.view` permissions
- Read-only access

---

## Testing Tips

1. **Create test accounts** with different roles
2. **Test each role** by logging in and verifying:
   - Correct API errors (403 vs 200)
   - UI shows/hides correct buttons
3. **Test Super Admin** can access everything
4. **Test permission removal** - remove a permission from a role and verify access is denied

---

## Common Mistakes to Avoid

1. ❌ **Don't rely only on frontend hiding** - Backend WILL reject unauthorized requests
2. ❌ **Don't hardcode role names** - Use permission slugs instead
3. ❌ **Don't cache permissions forever** - Refresh on login and periodically
4. ✅ **DO show friendly error messages** - "You need permission X" is better than generic error
5. ✅ **DO disable buttons instead of hiding** - Sometimes users need to know the action exists

---

## Troubleshooting

### "403 Forbidden" on all requests
- Check if user's role has any permissions assigned
- Verify role is active (`is_active: true`)
- Check if the employee is assigned to a role

### User can't see their permissions
- Ensure `/me` endpoint returns `role.permissions`
- Check if permissions are being loaded on login

### Super Admin getting 403
- Verify role slug is exactly `super-admin` (with hyphen)
- Check employee is assigned to the super-admin role

---

## Contact

Backend Team - For permission issues or new permission requests
