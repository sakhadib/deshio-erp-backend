# Permission Middleware Implementation

## Overview
Implemented role-based permission enforcement on all API routes. Previously, any authenticated employee could access all endpoints. Now access is controlled by permissions assigned to roles.

## What Was Done

### 1. Created CheckPermission Middleware
**File:** `app/Http/Middleware/CheckPermission.php`

```php
// Usage in routes:
Route::prefix('employees')
    ->middleware('permission:employees.view,employees.create,employees.edit')
    ->group(function () { ... });
```

- Accepts multiple permission slugs (user needs ANY of them to access)
- Returns 403 with clear error message if denied
- Returns 401 if not authenticated

### 2. Registered Middleware
**File:** `app/Http/Kernel.php`

Added alias:
```php
'permission' => \App\Http\Middleware\CheckPermission::class,
```

### 3. Applied to Routes
**File:** `routes/api.php`

All major route groups now have permission middleware:

| Route Prefix | Required Permissions |
|--------------|---------------------|
| `/employees` | `employees.view`, `employees.create`, `employees.edit`, `employees.delete` |
| `/vendors` | `vendors.view`, `vendors.create`, `vendors.edit`, `vendors.delete` |
| `/stores` | `stores.view`, `stores.create`, `stores.edit`, `stores.delete` |
| `/categories` | `categories.view`, `categories.create`, `categories.edit`, `categories.delete` |
| `/customers` | `customers.view`, `customers.create`, `customers.edit`, `customers.delete` |
| `/products` | `products.view`, `products.create`, `products.edit`, `products.delete` |
| `/orders` | `orders.view`, `orders.create`, `orders.edit`, `orders.delete`, `orders.fulfill` |
| `/services` | `services.view`, `services.create`, `services.edit`, `services.delete` |
| `/service-orders` | `service_orders.view`, `service_orders.create`, `service_orders.edit` |
| `/expenses` | `expenses.view`, `expenses.create`, `expenses.edit`, `expenses.delete`, `expenses.approve` |
| `/expense-categories` | `expense_categories.view`, `expense_categories.create`, `expense_categories.edit`, `expense_categories.delete` |
| `/roles` | `roles.view`, `roles.create`, `roles.edit`, `roles.delete` |
| `/permissions` | `permissions.manage` |
| `/dashboard` | `dashboard.view`, `dashboard.analytics` |
| `/reports` | `reports.view`, `reports.sales`, `reports.inventory`, `reports.financial` |
| `/accounting` | `accounting.view`, `accounting.manage` |
| `/accounts` | `accounts.view`, `accounts.create`, `accounts.edit`, `accounts.delete` |
| `/transactions` | `transactions.view`, `transactions.create`, `transactions.edit` |
| `/promotions` | `promotions.view`, `promotions.create`, `promotions.edit`, `promotions.delete` |
| `/purchase-orders` | `purchase_orders.view`, `purchase_orders.create`, `purchase_orders.edit`, `purchase_orders.delete`, `purchase_orders.approve`, `purchase_orders.receive` |
| `/vendor-payments` | `vendor_payments.view`, `vendor_payments.create`, `vendor_payments.manage` |
| `/batches` | `product_batches.view`, `product_batches.create`, `product_batches.edit`, `product_batches.delete` |
| `/barcodes` | `products.view`, `products.manage_barcodes` |
| `/dispatches` | `product_dispatches.view`, `product_dispatches.create`, `product_dispatches.edit`, `product_dispatches.approve` |
| `/shipments` | `shipments.view`, `shipments.create`, `shipments.manage` |
| `/returns` | `returns.view`, `returns.create`, `returns.approve`, `returns.process` |
| `/refunds` | `refunds.view`, `refunds.create`, `refunds.approve` |
| `/defective-products` | `inventory.view`, `inventory.adjust` |
| `/inventory` | `inventory.view`, `inventory.adjust`, `inventory.view_movements` |
| `/inventory-rebalancing` | `inventory_rebalancing.view`, `inventory_rebalancing.create`, `inventory_rebalancing.approve` |
| `/barcode-tracking` | `inventory.view`, `inventory.view_movements` |
| `/recycle-bin` | `recycle_bin.view`, `recycle_bin.restore`, `recycle_bin.delete` |
| `/contact-messages` | `contact_messages.view`, `contact_messages.manage` |
| `/activity-logs` | `activity_logs.view` |
| `/business-history` | `activity_logs.view` |
| `/lookup` | `inventory.view`, `orders.view` |
| `/reporting` | `reports.view`, `reports.export` |
| `/ad-campaigns` | `ad_campaigns.view`, `ad_campaigns.create`, `ad_campaigns.edit`, `ad_campaigns.delete` |
| `/collections` | `collections.view`, `collections.create`, `collections.edit`, `collections.delete` |
| `/price-overrides` | `price_overrides.view`, `price_overrides.create`, `price_overrides.edit`, `price_overrides.approve` |
| `/fields` | `fields.view`, `fields.create`, `fields.edit`, `fields.delete` |

## How It Works

1. User logs in → gets JWT token
2. User makes API request with token
3. `auth:api` middleware validates token
4. `permission:xxx` middleware checks:
   - **First:** Is user's role `super-admin`? → ALLOW (bypass all checks)
   - **Then:** Does user's role have any of the listed permissions? → ALLOW/DENY
5. If YES → request proceeds
6. If NO → 403 Forbidden response

## Super Admin Bypass

Users with role slug `super-admin`, `super_admin`, or `superadmin` bypass ALL permission checks automatically. This is hardcoded in the middleware - no permissions need to be assigned.

```php
// From CheckPermission.php
if ($user->role && in_array($user->role->slug, ['super_admin', 'super-admin', 'superadmin'])) {
    return $next($request);
}
```

## Error Response Example

```json
{
    "success": false,
    "message": "You do not have permission to perform this action",
    "required_permissions": ["employees.create", "employees.edit"]
}
```

## Important Notes

### For Super Admin
The `super-admin` role **automatically bypasses all permission checks**. No need to assign permissions - just ensure the role slug is `super-admin`.

Already exists in:
- `database/seeders/RolesSeeder.php` - Creates role with slug `super-admin`
- `database/seeders/EmployeesSeeder.php` - Creates super admin employee
- `deshio.sql` - Already has the role seeded

### For Store Employees
Create limited roles like "Store Clerk" with only:
- `orders.view`, `orders.create`
- `products.view`
- `customers.view`
- etc.

### Unprotected Routes
These routes remain public (no auth required):
- `POST /login`
- `POST /signup`
- `POST /guest-checkout`
- `POST /contact-messages`
- Customer auth routes (`/customer-auth/*`)

These routes only need authentication (no permission check):
- `POST /logout`
- `POST /refresh`
- `GET /me`

## Testing

```bash
# Test with user who has permission
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/employees

# Should return 200 OK if user's role has employees.view

# Test with user who lacks permission
# Should return 403 Forbidden
```

## Deployment Checklist

1. ✅ Middleware created
2. ✅ Middleware registered in Kernel
3. ✅ Routes updated with middleware
4. ✅ Super Admin bypass built into middleware
5. ⚠️ **VERIFY:** Ensure `super-admin` role exists in database
6. ⚠️ **VERIFY:** Ensure admin accounts have `role_id` pointing to super-admin role
7. 💡 If using fresh database: Run `php artisan db:seed --class=RolesSeeder` then `php artisan db:seed --class=EmployeesSeeder`
