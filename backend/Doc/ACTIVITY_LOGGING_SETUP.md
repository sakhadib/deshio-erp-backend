# Activity Logging System - Setup Guide

## Overview
Comprehensive system to track WHO did WHAT and WHEN for all database operations.

---

## ✅ What's Already Done

1. **Package Installed** - spatie/laravel-activitylog v4.10.2
2. **Database Tables** - `activity_log` table created with indexes
3. **API Routes** - 8 endpoints for viewing/filtering/exporting logs
4. **Controller** - Full ActivityLogController with filtering, search, export
5. **AutoLogsActivity Trait** - Reusable trait for any model
6. **Documentation** - Complete FE team documentation

---

## 🔧 How It Works

### Automatic Logging
When a model has the `AutoLogsActivity` trait, it automatically logs:
- **WHO**: Which employee/customer made the change
- **WHEN**: Precise timestamp
- **WHAT**: Before and after values for all changed fields

### Captured Information
- Event type (created, updated, deleted)
- User who performed the action
- IP address, user agent, URL, HTTP method
- Complete before/after state of the record
- Related model data

---

## 📝 Enable Logging on a Model

### Example: Order Model (Already Done)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AutoLogsActivity;  // Import the trait

class Order extends Model
{
    use AutoLogsActivity;  // Add this line
    
    // Rest of your model...
}
```

That's it! The Order model now automatically logs all changes.

---

## 🚀 Enable Logging on Other Models

### Quick Method: Add Trait Manually

For each model you want to log, add the trait:

```php
use App\Traits\AutoLogsActivity;

class YourModel extends Model
{
    use AutoLogsActivity;
}
```

### Models That Should Have Logging

**High Priority (Business Critical):**
- ✅ Order (already done)
- OrderItem
- OrderPayment
- Product
- ProductBatch
- ProductBarcode
- Customer
- Employee
- Transaction
- Expense
- ExpensePayment
- VendorPayment

**Medium Priority:**
- ProductDispatch
- Shipment
- ProductReturn
- Refund
- Store
- Vendor
- PaymentMethod
- Account

**Low Priority (Reference Data):**
- Category
- ProductImage
- Field
- Role
- Permission

**Exclude (Sensitive/Temporary):**
- PasswordResetToken
- EmailVerificationToken
- EmployeeSession
- EmployeeMFABackupCode

---

## 🎯 Testing the System

### Test 1: Create an Order (Should Log)

When you create an order via API:
```bash
POST /api/orders
```

The system will automatically create an activity log entry:
- event: "created"
- description: "Created Order: ORD-2025-001"
- causer: Current authenticated user
- attributes: All order fields

### Test 2: Update an Order (Should Log)

When you update an order:
```bash
PUT /api/orders/123
```

The system will log:
- event: "updated"
- old: Previous values
- attributes: New values
- Only changed fields are included

### Test 3: View the Logs

```bash
GET /api/activity-logs
```

You'll see:
```json
{
  "data": [
    {
      "event": "created",
      "description": "Created Order: ORD-2025-001",
      "causer": {
        "name": "John Doe",
        "type": "Employee"
      },
      "created_at_human": "2 minutes ago"
    }
  ]
}
```

---

## 📊 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/activity-logs` | GET | List all logs with filters |
| `/api/activity-logs/{id}` | GET | View single log details |
| `/api/activity-logs/statistics` | GET | Get statistics |
| `/api/activity-logs/model/{model}/{id}` | GET | Get history for a record |
| `/api/activity-logs/models` | GET | Get available models (for dropdown) |
| `/api/activity-logs/users` | GET | Get available users (for dropdown) |
| `/api/activity-logs/export/csv` | GET | Export to CSV |
| `/api/activity-logs/export/excel` | GET | Export to Excel |

---

## 🔍 Query Examples

### View all order creations today
```
GET /api/activity-logs?event=created&subject_type=Order&date_from=2025-12-19
```

### View who deleted customer #123
```
GET /api/activity-logs?event=deleted&subject_type=Customer&subject_id=123
```

### View all changes by employee #5
```
GET /api/activity-logs?causer_id=5&causer_type=Employee
```

### View complete history of Order #456
```
GET /api/activity-logs/model/Order/456
```

### Export December's logs to Excel
```
GET /api/activity-logs/export/excel?date_from=2025-12-01&date_to=2025-12-31
```

---

## 🎨 Custom Logging (Advanced)

### Exclude Sensitive Fields

In your model, override `getLoggableAttributes()`:

```php
class Employee extends Model
{
    use AutoLogsActivity;
    
    protected function getLoggableAttributes(): array
    {
        $attributes = parent::getLoggableAttributes();
        
        // Exclude password from logs
        return array_diff($attributes, ['password', 'remember_token']);
    }
}
```

### Custom Log Description

Override `getLogDescription()`:

```php
protected function getLogDescription(string $eventName): string
{
    return match($eventName) {
        'created' => "New order {$this->order_number} created",
        'updated' => "Order {$this->order_number} updated",
        'deleted' => "Order {$this->order_number} cancelled",
        default => parent::getLogDescription($eventName)
    };
}
```

### Custom Identifier

Override `getLogIdentifier()`:

```php
protected function getLogIdentifier(): string
{
    // Use order number instead of ID
    return $this->order_number ?? "#{$this->id}";
}
```

---

## 🔐 Security Features

### Authentication Detection
- Automatically detects `auth:api` (employees) and `auth:customer` (customers)
- Falls back to "System" if no auth

### IP Tracking
- Records IP address of the request
- Useful for security audits

### User Agent Tracking
- Records browser/device information
- Helps identify suspicious activity

### URL & Method Tracking
- Records which endpoint was called
- Records HTTP method (GET, POST, PUT, DELETE)

---

## 📈 Performance Considerations

### Database Indexing
The `activity_log` table has indexes on:
- `subject_type`, `subject_id` - Fast model lookups
- `causer_type`, `causer_id` - Fast user lookups
- `log_name` - Fast table lookups
- `created_at` - Fast date filtering

### Query Optimization
- Paginated by default (50 items per page)
- Export limited to 10,000 records
- Uses eager loading for relationships

### Log Cleanup (Optional)
Consider implementing log rotation:
```php
// Archive logs older than 1 year
Activity::where('created_at', '<', now()->subYear())->delete();
```

---

## 🐛 Troubleshooting

### Issue: Logs not appearing

**Check 1:** Does model have the trait?
```php
use App\Traits\AutoLogsActivity;

class YourModel extends Model
{
    use AutoLogsActivity;  // Must have this
}
```

**Check 2:** Are you authenticated?
```php
// Logs will show "System" if not authenticated
// Make sure API requests include Bearer token
```

**Check 3:** Are you making actual changes?
```php
// Logs are only created if data actually changes
// Updating with same values = no log entry
```

### Issue: Sensitive data in logs

**Solution:** Exclude fields in model:
```php
protected function getLoggableAttributes(): array
{
    $attributes = parent::getLoggableAttributes();
    return array_diff($attributes, ['password', 'card_number']);
}
```

### Issue: Too many logs

**Solution:** Be selective about which models to log. Don't log:
- Session data
- Temporary tokens
- Cache tables
- High-frequency updates (views, clicks)

---

## ✅ Quick Start Checklist

1. [x] Package installed (spatie/laravel-activitylog)
2. [x] Migrations run (`activity_log` table created)
3. [x] `AutoLogsActivity` trait created
4. [x] Routes added to `api.php`
5. [x] `ActivityLogController` created
6. [x] Order model updated with trait (example)
7. [ ] Add trait to other critical models
8. [ ] Test API endpoints
9. [ ] Build FE pages (list, filters, export)
10. [ ] Deploy to production

---

## 📚 Documentation Files

1. **FE_TEAM_ACTIVITY_LOGS.md** - Complete API documentation for frontend team
2. **ACTIVITY_LOGGING_SETUP.md** - This file (setup guide)

---

## 🎯 Next Steps

### For Backend Team:
1. Add `AutoLogsActivity` trait to remaining models
2. Test logging on staging environment
3. Monitor log table size and performance
4. Set up log archiving/cleanup job (optional)

### For Frontend Team:
1. Read `FE_TEAM_ACTIVITY_LOGS.md`
2. Create Activity Logs page with filters
3. Add Activity History widget to detail pages
4. Implement export functionality
5. Add statistics dashboard widget

---

## 🎉 Benefits

✅ **Audit Compliance** - Complete trail of all changes
✅ **Debugging** - See exactly what changed and when
✅ **Security** - Track who did what
✅ **Customer Service** - Verify order changes
✅ **Performance Tracking** - See employee activity
✅ **Accountability** - Clear ownership of actions
✅ **Zero Code Changes** - Just add trait to models

---

**Status:** ✅ Fully Implemented and Ready to Use
**Date:** December 19, 2025
