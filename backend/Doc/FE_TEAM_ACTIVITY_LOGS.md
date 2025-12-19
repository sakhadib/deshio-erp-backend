# Activity Log System - Frontend Documentation

## Overview
Comprehensive activity logging system that tracks **WHO did WHAT and WHEN** for all database operations (Create, Update, Delete) across the entire ERP system.

**Key Features:**
- ✅ **Automatic logging** - No manual code in controllers needed
- ✅ **WHO** - Tracks which employee/customer made the change
- ✅ **WHEN** - Precise timestamps with human-readable format
- ✅ **WHAT** - Detailed before/after values for all changes
- ✅ **Advanced filtering** - By model, event, user, date range, search
- ✅ **Export** - CSV and Excel export with applied filters
- ✅ **Audit trail** - View complete history for any record

---

## Table of Contents
1. [Allowed Values Reference](#allowed-values-reference)
2. [API Endpoints](#api-endpoints)
3. [Filtering & Search](#filtering--search)
4. [Response Format](#response-format)
5. [Export Functionality](#export-functionality)
6. [Use Cases](#use-cases)
7. [Frontend Implementation Guide](#frontend-implementation-guide)
8. [UI/UX Recommendations](#uiux-recommendations)

---

## Allowed Values Reference

### Event Types (Allowed Values for `event` parameter)

| Value | Description | When It's Logged |
|-------|-------------|------------------|
| `created` | New record created | When a new order, product, customer, etc. is created |
| `updated` | Existing record modified | When any field is changed (status, price, quantity, etc.) |
| `deleted` | Record soft-deleted | When a record is deleted (soft delete) |

**Example Usage:**
```
GET /api/activity-logs?event=updated
GET /api/activity-logs?event=created&date_from=2025-12-19
```

---

### Model Types (Allowed Values for `subject_type` parameter)

**Complete list of all models with activity logging enabled:**

#### 📦 Orders & Sales
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Order` | Customer orders | `orders` |
| `OrderItem` | Individual order line items | `order_items` |
| `OrderPayment` | Order payment records | `order_payments` |

#### 🛍️ Products & Inventory
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Product` | Product catalog | `products` |
| `ProductBatch` | Product batches with expiry | `product_batches` |
| `ProductBarcode` | Product barcodes | `product_barcodes` |
| `ProductDispatch` | Dispatch/transfer orders | `product_dispatches` |
| `ProductDispatchItem` | Dispatch line items | `product_dispatch_items` |
| `ProductMovement` | Inventory movements | `product_movements` |
| `ProductPriceOverride` | Custom pricing rules | `product_price_overrides` |
| `ProductReturn` | Product returns | `product_returns` |
| `MasterInventory` | Master inventory records | `master_inventories` |
| `InventoryRebalancing` | Inventory adjustments | `inventory_rebalancings` |

#### 👥 People
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Customer` | Customers | `customers` |
| `Employee` | Employees | `employees` |
| `Vendor` | Vendors/Suppliers | `vendors` |

#### 🧾 Services
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Service` | Service catalog | `services` |
| `ServiceOrder` | Service orders | `service_orders` |
| `ServiceOrderItem` | Service order items | `service_order_items` |
| `ServiceOrderPayment` | Service payments | `service_order_payments` |

#### 💰 Financial
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Transaction` | Financial transactions | `transactions` |
| `Account` | Chart of accounts | `accounts` |
| `PaymentMethod` | Payment methods | `payment_methods` |
| `Expense` | Expenses | `expenses` |
| `ExpensePayment` | Expense payments | `expense_payments` |
| `ExpenseCategory` | Expense categories | `expense_categories` |

#### 🚚 Purchasing & Vendors
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `PurchaseOrder` | Purchase orders | `purchase_orders` |
| `PurchaseOrderItem` | PO line items | `purchase_order_items` |
| `VendorPayment` | Vendor payments | `vendor_payments` |
| `VendorPaymentItem` | Vendor payment items | `vendor_payment_items` |

#### 📦 Returns & Shipments
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Refund` | Refunds | `refunds` |
| `Shipment` | Shipments | `shipments` |

#### ⚙️ System & Configuration
| Model Name | Description | Log Name |
|------------|-------------|----------|
| `Store` | Store/Warehouse locations | `stores` |
| `Category` | Product categories | `categories` |
| `Role` | User roles | `roles` |

**Example Usage:**
```
GET /api/activity-logs?subject_type=Order
GET /api/activity-logs?subject_type=Product&event=updated
GET /api/activity-logs?subject_type=Customer&date_from=2025-12-01
```

---

### User Types (Allowed Values for `causer_type` parameter)

| Value | Description |
|-------|-------------|
| `Employee` | Internal staff/employees |
| `Customer` | External customers |

**Example Usage:**
```
GET /api/activity-logs?causer_type=Employee
GET /api/activity-logs?causer_type=Customer&event=created
```

---

### Sort Fields (Allowed Values for `sort_by` parameter)

| Value | Description |
|-------|-------------|
| `created_at` | Sort by timestamp (default) |
| `event` | Sort by event type (created, updated, deleted) |
| `subject_type` | Sort by model type alphabetically |

**Example Usage:**
```
GET /api/activity-logs?sort_by=created_at&sort_direction=desc
GET /api/activity-logs?sort_by=event&sort_direction=asc
```

---

### Sort Direction (Allowed Values for `sort_direction` parameter)

| Value | Description |
|-------|-------------|
| `asc` | Ascending (oldest first, A-Z) |
| `desc` | Descending (newest first, Z-A) - **default** |

---

### Pagination (Allowed Values for `per_page` parameter)

| Value | Description |
|-------|-------------|
| `25` | 25 items per page |
| `50` | 50 items per page (default) |
| `100` | 100 items per page |

---

## Quick Lookup Examples

### 1. View All Order Changes Today
```
GET /api/activity-logs?subject_type=Order&date_from=2025-12-19&date_to=2025-12-19
```

### 2. View All Product Updates by Employee #5
```
GET /api/activity-logs?subject_type=Product&event=updated&causer_type=Employee&causer_id=5
```

### 3. View Complete History for Order #123
```
GET /api/activity-logs/model/Order/123
```

### 4. View All Deletions This Month
```
GET /api/activity-logs?event=deleted&date_from=2025-12-01&date_to=2025-12-31
```

### 5. Search for Specific Order Number
```
GET /api/activity-logs?search=ORD-2025-001
```

### 6. View All Customer-Initiated Changes
```
GET /api/activity-logs?causer_type=Customer&event=created
```

### 7. View Inventory Adjustments
```
GET /api/activity-logs?subject_type=InventoryRebalancing
```

### 8. View All Changes by Date Range
```
GET /api/activity-logs?date_from=2025-12-01&date_to=2025-12-19&sort_by=created_at&sort_direction=desc
```

---

## API Endpoints

### 1. List Activity Logs with Filtering

**Endpoint:** `GET /api/activity-logs`

**Authentication:** Required (Bearer token)

**Query Parameters:**

| Parameter | Type | Required | Allowed Values | Description | Example |
|-----------|------|----------|----------------|-------------|---------|
| `event` | string | No | `created`, `updated`, `deleted` | Filter by action type | `updated` |
| `subject_type` | string | No | See [Model Types](#model-types-allowed-values-for-subject_type-parameter) above | Filter by model | `Order`, `Product`, `Customer` |
| `subject_id` | integer | No | Any valid record ID | Filter by specific record | `123` |
| `causer_type` | string | No | `Employee`, `Customer` | Filter by user type | `Employee` |
| `causer_id` | integer | No | Any valid user ID | Filter by specific user | `5` |
| `log_name` | string | No | Table name (lowercase, plural) | Filter by table | `orders`, `products` |
| `date_from` | date | No | YYYY-MM-DD format | Start date | `2025-12-01` |
| `date_to` | date | No | YYYY-MM-DD format | End date | `2025-12-31` |
| `search` | string | No | Any text | Search in description | `Order ORD-2025-001` |
| `sort_by` | string | No | `created_at`, `event`, `subject_type` | Sort field | `created_at` |
| `sort_direction` | string | No | `asc`, `desc` | Sort direction | `desc` (default) |
| `per_page` | integer | No | `25`, `50`, `100` | Items per page | `50` (default) |
| `page` | integer | No | Positive integer | Page number | `1` |

**Example Requests:**
```bash
# All order updates today
GET /api/activity-logs?event=updated&subject_type=Order&date_from=2025-12-19

# All product changes by employee #5
GET /api/activity-logs?subject_type=Product&causer_type=Employee&causer_id=5

# Search for specific order
GET /api/activity-logs?search=ORD-2025-001

# All deletions this month
GET /api/activity-logs?event=deleted&date_from=2025-12-01&date_to=2025-12-31
```

**Success Response (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "event": "updated",
      "description": "Updated Order: ORD-2025-001",
      "log_name": "orders",
      "subject": {
        "type": "Order",
        "full_type": "App\\Models\\Order",
        "id": 123,
        "data": {
          "id": 123,
          "order_number": "ORD-2025-001",
          "status": "confirmed"
        }
      },
      "causer": {
        "type": "Employee",
        "full_type": "App\\Models\\Employee",
        "id": 5,
        "name": "John Doe"
      },
      "changes": {
        "attributes": {
          "status": "confirmed",
          "updated_at": "2025-12-19 10:30:00"
        },
        "old": {
          "status": "pending",
          "updated_at": "2025-12-19 10:00:00"
        }
      },
      "metadata": {
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "url": "http://api.example.com/api/orders/123",
        "method": "PUT"
      },
      "created_at": "2025-12-19T10:30:00.000000Z",
      "created_at_human": "2 hours ago",
      "created_at_formatted": "2025-12-19 10:30:00"
    }
  ],
  "from": 1,
  "last_page": 5,
  "per_page": 50,
  "to": 50,
  "total": 250
}
```

---

### 2. View Single Activity Log

**Endpoint:** `GET /api/activity-logs/{id}`

**Example Request:**
```
GET /api/activity-logs/123
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "event": "updated",
    "description": "Updated Order: ORD-2025-001",
    "log_name": "orders",
    "batch_uuid": "9a5e7b3c-d4f1-4c2a-8b9e-1f2a3c4d5e6f",
    "subject": {
      "type": "Order",
      "full_type": "App\\Models\\Order",
      "id": 123,
      "data": { /* Full order object */ }
    },
    "causer": {
      "type": "Employee",
      "full_type": "App\\Models\\Employee",
      "id": 5,
      "name": "John Doe",
      "data": { /* Full employee object */ }
    },
    "properties": { /* All logged properties */ },
    "changes": {
      "attributes": { /* New values */ },
      "old": { /* Previous values */ }
    },
    "metadata": {
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "url": "http://api.example.com/api/orders/123",
      "method": "PUT"
    },
    "created_at": "2025-12-19T10:30:00.000000Z",
    "created_at_human": "2 hours ago",
    "created_at_formatted": "2025-12-19 10:30:00"
  }
}
```

---

### 3. Get Statistics

**Endpoint:** `GET /api/activity-logs/statistics`

**Query Parameters:**
- `date_from` - Start date (optional, defaults to last 30 days)
- `date_to` - End date (optional)

**Example Request:**
```
GET /api/activity-logs/statistics?date_from=2025-12-01&date_to=2025-12-31
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_activities": 15243,
    "by_event": {
      "created": 5120,
      "updated": 8923,
      "deleted": 1200
    },
    "by_model": [
      { "model": "Order", "count": 4532 },
      { "model": "Product", "count": 3201 },
      { "model": "Customer", "count": 2134 }
    ],
    "by_user": [
      { "user": "John Doe", "type": "Employee", "count": 2341 },
      { "user": "Jane Smith", "type": "Employee", "count": 1892 }
    ],
    "today": 234,
    "this_week": 1567,
    "this_month": 6789
  }
}
```

---

### 4. Get Logs for Specific Model Instance

**Endpoint:** `GET /api/activity-logs/model/{model}/{id}`

**Description:** Get complete activity history for a specific record (e.g., all changes made to Order #123)

**Example Request:**
```
GET /api/activity-logs/model/Order/123
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "event": "updated",
      "description": "Updated Order: ORD-2025-001",
      "causer": {
        "type": "Employee",
        "name": "John Doe"
      },
      "changes": {
        "attributes": { "status": "confirmed" },
        "old": { "status": "pending" }
      },
      "created_at": "2025-12-19T10:30:00.000000Z",
      "created_at_human": "2 hours ago"
    },
    {
      "id": 123,
      "event": "created",
      "description": "Created Order: ORD-2025-001",
      "causer": {
        "type": "Employee",
        "name": "Jane Smith"
      },
      "changes": {
        "attributes": { "order_number": "ORD-2025-001", "status": "pending" },
        "old": {}
      },
      "created_at": "2025-12-19T09:00:00.000000Z",
      "created_at_human": "3 hours ago"
    }
  ]
}
```

---

### 5. Get Available Models (for filter dropdown)

**Endpoint:** `GET /api/activity-logs/models`

**Description:** Returns all model types that have activity logging enabled. Use this to populate the "Model Type" dropdown filter.

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    { "label": "Account", "value": "Account", "full_name": "App\\Models\\Account" },
    { "label": "Category", "value": "Category", "full_name": "App\\Models\\Category" },
    { "label": "Customer", "value": "Customer", "full_name": "App\\Models\\Customer" },
    { "label": "Employee", "value": "Employee", "full_name": "App\\Models\\Employee" },
    { "label": "Expense", "value": "Expense", "full_name": "App\\Models\\Expense" },
    { "label": "ExpenseCategory", "value": "ExpenseCategory", "full_name": "App\\Models\\ExpenseCategory" },
    { "label": "ExpensePayment", "value": "ExpensePayment", "full_name": "App\\Models\\ExpensePayment" },
    { "label": "InventoryRebalancing", "value": "InventoryRebalancing", "full_name": "App\\Models\\InventoryRebalancing" },
    { "label": "MasterInventory", "value": "MasterInventory", "full_name": "App\\Models\\MasterInventory" },
    { "label": "Order", "value": "Order", "full_name": "App\\Models\\Order" },
    { "label": "OrderItem", "value": "OrderItem", "full_name": "App\\Models\\OrderItem" },
    { "label": "OrderPayment", "value": "OrderPayment", "full_name": "App\\Models\\OrderPayment" },
    { "label": "PaymentMethod", "value": "PaymentMethod", "full_name": "App\\Models\\PaymentMethod" },
    { "label": "Product", "value": "Product", "full_name": "App\\Models\\Product" },
    { "label": "ProductBarcode", "value": "ProductBarcode", "full_name": "App\\Models\\ProductBarcode" },
    { "label": "ProductBatch", "value": "ProductBatch", "full_name": "App\\Models\\ProductBatch" },
    { "label": "ProductDispatch", "value": "ProductDispatch", "full_name": "App\\Models\\ProductDispatch" },
    { "label": "ProductDispatchItem", "value": "ProductDispatchItem", "full_name": "App\\Models\\ProductDispatchItem" },
    { "label": "ProductMovement", "value": "ProductMovement", "full_name": "App\\Models\\ProductMovement" },
    { "label": "ProductPriceOverride", "value": "ProductPriceOverride", "full_name": "App\\Models\\ProductPriceOverride" },
    { "label": "ProductReturn", "value": "ProductReturn", "full_name": "App\\Models\\ProductReturn" },
    { "label": "PurchaseOrder", "value": "PurchaseOrder", "full_name": "App\\Models\\PurchaseOrder" },
    { "label": "PurchaseOrderItem", "value": "PurchaseOrderItem", "full_name": "App\\Models\\PurchaseOrderItem" },
    { "label": "Refund", "value": "Refund", "full_name": "App\\Models\\Refund" },
    { "label": "Role", "value": "Role", "full_name": "App\\Models\\Role" },
    { "label": "Service", "value": "Service", "full_name": "App\\Models\\Service" },
    { "label": "ServiceOrder", "value": "ServiceOrder", "full_name": "App\\Models\\ServiceOrder" },
    { "label": "ServiceOrderItem", "value": "ServiceOrderItem", "full_name": "App\\Models\\ServiceOrderItem" },
    { "label": "ServiceOrderPayment", "value": "ServiceOrderPayment", "full_name": "App\\Models\\ServiceOrderPayment" },
    { "label": "Shipment", "value": "Shipment", "full_name": "App\\Models\\Shipment" },
    { "label": "Store", "value": "Store", "full_name": "App\\Models\\Store" },
    { "label": "Transaction", "value": "Transaction", "full_name": "App\\Models\\Transaction" },
    { "label": "Vendor", "value": "Vendor", "full_name": "App\\Models\\Vendor" },
    { "label": "VendorPayment", "value": "VendorPayment", "full_name": "App\\Models\\VendorPayment" },
    { "label": "VendorPaymentItem", "value": "VendorPaymentItem", "full_name": "App\\Models\\VendorPaymentItem" }
  ]
}
```

**Total Models with Logging: 34**

---

### 6. Get Available Users (for filter dropdown)

**Endpoint:** `GET /api/activity-logs/users`

**Description:** Returns all users who have performed logged activities. Use this to populate the "User" dropdown filter.

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    { "label": "John Doe (Employee)", "value": 1, "type": "Employee" },
    { "label": "Jane Smith (Employee)", "value": 2, "type": "Employee" },
    { "label": "Mike Johnson (Employee)", "value": 3, "type": "Employee" },
    { "label": "Customer A (Customer)", "value": 50, "type": "Customer" },
    { "label": "Customer B (Customer)", "value": 51, "type": "Customer" }
  ]
}
```

---

### 7. Export to CSV

**Endpoint:** `GET /api/activity-logs/export/csv`

**Query Parameters:** Same as list endpoint (all filters apply)

**Example Request:**
```
GET /api/activity-logs/export/csv?event=updated&date_from=2025-12-01
```

**Response:** CSV file download with filename `activity-logs-2025-12-19-103045.csv`

**CSV Columns:**
- ID
- Event
- Description
- Model
- Model ID
- User Type
- User Name
- IP Address
- Date Time

---

### 8. Export to Excel

**Endpoint:** `GET /api/activity-logs/export/excel`

**Query Parameters:** Same as list endpoint

**Example Request:**
```
GET /api/activity-logs/export/excel?subject_type=Order&date_from=2025-12-01
```

**Response:** Excel file download with filename `activity-logs-2025-12-19-103045.xlsx`

**Excel Columns:**
- ID
- Event
- Description
- Model
- Model ID
- User Type
- User Name
- IP Address
- URL
- Method
- Date Time
- Changes (formatted as "field: old → new")

---

## Filtering & Search

### Overview
The activity log API supports powerful filtering and search capabilities. All filter parameters can be combined.

**Important Notes:**
- All parameters are **optional** - omit them to get all logs
- Filters are **cumulative** - multiple filters applied together narrow results
- Date format must be **YYYY-MM-DD**
- Model names are **case-sensitive** (use exact values from [Model Types](#model-types-allowed-values-for-subject_type-parameter))
- Event types are **lowercase** (`created`, `updated`, `deleted`)

### How to Combine Filters

You can combine multiple filters to narrow down results:

```javascript
// Example 1: Get all order updates by employee #5 in December
GET /api/activity-logs?subject_type=Order&event=updated&causer_type=Employee&causer_id=5&date_from=2025-12-01&date_to=2025-12-31

// Example 2: Get all product deletions
GET /api/activity-logs?subject_type=Product&event=deleted

// Example 3: Get all customer-initiated changes today
GET /api/activity-logs?causer_type=Customer&date_from=2025-12-19&date_to=2025-12-19

// Example 4: Search for specific order and get its updates
GET /api/activity-logs?search=ORD-2025-001&event=updated
```

### Filter by Event Type

**Allowed Values:** `created`, `updated`, `deleted`

```
GET /api/activity-logs?event=created    # Only new records
GET /api/activity-logs?event=updated    # Only modifications
GET /api/activity-logs?event=deleted    # Only deletions
```

### Filter by Model Type

**Allowed Values:** See complete list in [Model Types](#model-types-allowed-values-for-subject_type-parameter) section above (34 models total)

```
GET /api/activity-logs?subject_type=Order              # Only order changes
GET /api/activity-logs?subject_type=Product            # Only product changes
GET /api/activity-logs?subject_type=ProductBatch       # Only batch changes
GET /api/activity-logs?subject_type=Customer           # Only customer changes
GET /api/activity-logs?subject_type=Transaction        # Only transaction changes
```

### Filter by Specific Record

```
GET /api/activity-logs?subject_type=Order&subject_id=123    # All changes to Order #123
GET /api/activity-logs?subject_type=Product&subject_id=456  # All changes to Product #456
```

**Note:** Better to use the dedicated endpoint for record history:
```
GET /api/activity-logs/model/Order/123
```

### Filter by User Type

**Allowed Values:** `Employee`, `Customer`

```
GET /api/activity-logs?causer_type=Employee    # Only employee actions
GET /api/activity-logs?causer_type=Customer    # Only customer actions
```

### Filter by Specific User

```
GET /api/activity-logs?causer_type=Employee&causer_id=5    # Only Employee #5 actions
GET /api/activity-logs?causer_type=Customer&causer_id=100  # Only Customer #100 actions
```

### Filter by Date Range

```
# Single day
GET /api/activity-logs?date_from=2025-12-19&date_to=2025-12-19

# Date range
GET /api/activity-logs?date_from=2025-12-01&date_to=2025-12-31

# From date (all logs from December 1st onward)
GET /api/activity-logs?date_from=2025-12-01

# Until date (all logs up to December 31st)
GET /api/activity-logs?date_to=2025-12-31
```

### Search by Description

Search for keywords in the log description (case-insensitive):

```
GET /api/activity-logs?search=ORD-2025-001          # Find logs mentioning this order
GET /api/activity-logs?search=john                  # Find logs with "john"
GET /api/activity-logs?search=confirmed             # Find status changes to confirmed
```

### Sorting

**Allowed Sort Fields:** `created_at`, `event`, `subject_type`  
**Allowed Directions:** `asc` (ascending), `desc` (descending, default)

```
# Latest first (default)
GET /api/activity-logs?sort_by=created_at&sort_direction=desc

# Oldest first
GET /api/activity-logs?sort_by=created_at&sort_direction=asc

# Sort by event type alphabetically
GET /api/activity-logs?sort_by=event&sort_direction=asc

# Sort by model type
GET /api/activity-logs?sort_by=subject_type&sort_direction=asc
```

### Pagination

**Allowed Page Sizes:** `25`, `50` (default), `100`

```
# Get first page (50 items)
GET /api/activity-logs?per_page=50&page=1

# Get 25 items per page
GET /api/activity-logs?per_page=25&page=1

# Get 100 items per page
GET /api/activity-logs?per_page=100&page=1

# Navigate to page 3
GET /api/activity-logs?per_page=50&page=3
```

### Common Filter Combinations

```bash
# 1. Audit trail for specific order
GET /api/activity-logs?subject_type=Order&subject_id=123

# 2. All inventory adjustments this month
GET /api/activity-logs?subject_type=InventoryRebalancing&date_from=2025-12-01&date_to=2025-12-31

# 3. All deletions by employees
GET /api/activity-logs?event=deleted&causer_type=Employee

# 4. All product price changes by specific employee
GET /api/activity-logs?subject_type=ProductPriceOverride&causer_id=5

# 5. Customer activity today
GET /api/activity-logs?causer_type=Customer&date_from=2025-12-19&date_to=2025-12-19

# 6. Monthly report - all order modifications
GET /api/activity-logs?subject_type=Order&event=updated&date_from=2025-12-01&date_to=2025-12-31&per_page=100
```

---

## Frontend Implementation Guide

### 1. Activity Log List Page

```javascript
// Fetch logs with filters
async function fetchActivityLogs(filters = {}) {
  const params = new URLSearchParams(filters);
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`/api/activity-logs?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Usage
const filters = {
  event: 'updated',
  subject_type: 'Order',
  date_from: '2025-12-01',
  per_page: 50,
  page: 1
};

fetchActivityLogs(filters).then(data => {
  console.log(`Total logs: ${data.total}`);
  data.data.forEach(log => {
    console.log(`${log.causer.name} ${log.event} ${log.subject.type} #${log.subject.id}`);
  });
});
```

### 2. Statistics Dashboard

```javascript
async function fetchLogStatistics(dateRange = {}) {
  const params = new URLSearchParams(dateRange);
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`/api/activity-logs/statistics?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const result = await response.json();
  return result.data;
}

// Usage
fetchLogStatistics({ date_from: '2025-12-01', date_to: '2025-12-31' })
  .then(stats => {
    console.log(`Total activities: ${stats.total_activities}`);
    console.log(`Created: ${stats.by_event.created}`);
    console.log(`Updated: ${stats.by_event.updated}`);
    console.log(`Deleted: ${stats.by_event.deleted}`);
    console.log(`Today: ${stats.today}`);
  });
```

### 3. Record History (Activity Timeline)

```javascript
// Get all activity history for a specific record
async function fetchRecordHistory(modelType, modelId) {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`/api/activity-logs/model/${modelType}/${modelId}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const result = await response.json();
  return result.data;
}

// Usage - Show order history
fetchRecordHistory('Order', 123).then(logs => {
  logs.forEach(log => {
    console.log(`${log.created_at_human}: ${log.description} by ${log.causer.name}`);
    if (log.event === 'updated') {
      Object.keys(log.changes.old).forEach(field => {
        const oldValue = log.changes.old[field];
        const newValue = log.changes.attributes[field];
        console.log(`  ${field}: ${oldValue} → ${newValue}`);
      });
    }
  });
});
```

### 4. Export Filtered Results

```javascript
// Export CSV
function exportLogsToCSV(filters = {}) {
  const params = new URLSearchParams(filters);
  const token = localStorage.getItem('auth_token');
  const url = `/api/activity-logs/export/csv?${params}`;
  
  // Create download link
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', '');
  link.style.display = 'none';
  
  // Add authorization header via fetch and blob
  fetch(url, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  })
  .then(response => response.blob())
  .then(blob => {
    const url = window.URL.createObjectURL(blob);
    link.href = url;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  });
}

// Export Excel
function exportLogsToExcel(filters = {}) {
  const params = new URLSearchParams(filters);
  const token = localStorage.getItem('auth_token');
  const url = `/api/activity-logs/export/excel?${params}`;
  
  fetch(url, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  })
  .then(response => response.blob())
  .then(blob => {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', '');
    link.click();
    window.URL.revokeObjectURL(url);
  });
}

// Usage
exportLogsToCSV({ event: 'updated', subject_type: 'Order', date_from: '2025-12-01' });
exportLogsToExcel({ subject_type: 'Product', date_from: '2025-12-01' });
```

### 5. Filter Dropdown Data

```javascript
// Get model types for dropdown
async function fetchAvailableModels() {
  const token = localStorage.getItem('auth_token');
  const response = await fetch('/api/activity-logs/models', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  const result = await response.json();
  return result.data;
}

// Get users for dropdown
async function fetchAvailableUsers() {
  const token = localStorage.getItem('auth_token');
  const response = await fetch('/api/activity-logs/users', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  const result = await response.json();
  return result.data;
}

// Usage - Populate dropdowns
Promise.all([fetchAvailableModels(), fetchAvailableUsers()])
  .then(([models, users]) => {
    // Populate model dropdown
    models.forEach(model => {
      console.log(`Option: ${model.label} (${model.value})`);
    });
    
    // Populate user dropdown
    users.forEach(user => {
      console.log(`Option: ${user.label} - ${user.type}`);
    });
  });
```

---

## UI/UX Recommendations

### 1. Main Activity Log Page

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│  Activity Logs                                    Export ▼  │
├─────────────────────────────────────────────────────────────┤
│  Filters:                                                   │
│  [ Event ▼ ] [ Model ▼ ] [ User ▼ ] [ Date Range ] [Search]│
│  [ Show 50 ▼ per page ]                                     │
├─────────────────────────────────────────────────────────────┤
│  Statistics: 15,243 total  │  Today: 234  │  This Week: 1567│
├─────────────────────────────────────────────────────────────┤
│  Time          Event    User        Action                  │
│  ───────────────────────────────────────────────────────────│
│  2 hours ago   Updated  John Doe    Order #ORD-2025-001    │
│                                      status: pending → conf  │
│  3 hours ago   Created  Jane Smith  Product #SKU-12345      │
│  5 hours ago   Deleted  Admin       Customer #123           │
├─────────────────────────────────────────────────────────────┤
│  Showing 1-50 of 15,243     [<] [1] [2] [3] ... [305] [>]  │
└─────────────────────────────────────────────────────────────┘
```

**Components:**

1. **Filter Bar**
   - Event Type dropdown (All, Created, Updated, Deleted)
   - Model Type dropdown (populated from `/api/activity-logs/models`)
   - User dropdown (populated from `/api/activity-logs/users`)
   - Date Range picker (From - To)
   - Search box (searches description)
   - Clear Filters button

2. **Export Menu**
   - Export as CSV button
   - Export as Excel button
   - Export respects current filters

3. **Statistics Bar**
   - Quick overview of activity counts
   - Clickable to filter (e.g., click "Today: 234" filters to today's logs)

4. **Activity List**
   - Expandable rows (click to see full details)
   - Color-coded events:
     - 🟢 Created (green)
     - 🟡 Updated (yellow)
     - 🔴 Deleted (red)
   - Show WHO (user avatar + name)
   - Show WHEN (relative time + hover for exact timestamp)
   - Show WHAT (description + changed fields)

5. **Pagination**
   - Items per page selector (25, 50, 100)
   - Page number navigation

---

### 2. Record History Widget

**Usage:** Embed in detail pages (Order details, Product details, etc.)

```
┌──────────────────────────────────────┐
│  Activity History                    │
├──────────────────────────────────────┤
│  ● 2 hours ago - John Doe            │
│    Updated status: pending → confirmed│
│                                      │
│  ● 3 hours ago - Jane Smith          │
│    Updated shipping address          │
│                                      │
│  ● 5 hours ago - Jane Smith          │
│    Created this order                │
└──────────────────────────────────────┘
```

**Implementation:**
```javascript
// Call this in order/product/customer detail pages
fetchRecordHistory('Order', orderId).then(logs => {
  renderHistoryWidget(logs);
});
```

---

### 3. Dashboard Widget

**Statistics Overview:**

```
┌──────────────────────────────────────────────┐
│  Today's Activity                            │
├──────────────────────────────────────────────┤
│  234 total activities                        │
│  ● 89 Created  ● 132 Updated  ● 13 Deleted  │
│                                              │
│  Top Models:                                 │
│  1. Orders (45)                              │
│  2. Products (32)                            │
│  3. Customers (18)                           │
│                                              │
│  Most Active Users:                          │
│  1. John Doe (42 actions)                    │
│  2. Jane Smith (31 actions)                  │
│                                              │
│  [View All Logs →]                           │
└──────────────────────────────────────────────┘
```

---

### 4. Filter Presets

Create quick filter buttons:

- **Today's Activity** - `date_from=today`
- **This Week** - `date_from=week_start`
- **My Activity** - `causer_id=current_user_id`
- **Order Changes** - `subject_type=Order`
- **Deleted Records** - `event=deleted`
- **Critical Changes** - `subject_type=Order&event=deleted` or `subject_type=Product&event=deleted`

---

## Use Cases

### Use Case 1: Audit Trail for Compliance

**Scenario:** Management needs to see who deleted a customer record.

**Steps:**
1. Go to Activity Logs page
2. Filter: `event=deleted`, `subject_type=Customer`
3. Search for customer name or ID
4. View result: Shows WHO deleted it, WHEN, and previous data

**API Call:**
```
GET /api/activity-logs?event=deleted&subject_type=Customer&search=John+Smith
```

---

### Use Case 2: Track Order Changes

**Scenario:** Customer complains order status wasn't updated. Need to verify.

**Steps:**
1. Go to Order details page
2. View "Activity History" widget
3. See complete timeline of all changes
4. Verify WHO changed status and WHEN

**API Call:**
```
GET /api/activity-logs/model/Order/123
```

---

### Use Case 3: Employee Performance

**Scenario:** Manager wants to see how many orders John processed today.

**Steps:**
1. Go to Activity Logs
2. Filter: `event=created`, `subject_type=Order`, `causer_id=5`, `date_from=today`
3. View count and export

**API Call:**
```
GET /api/activity-logs?event=created&subject_type=Order&causer_id=5&date_from=2025-12-19
```

---

### Use Case 4: Monthly Report

**Scenario:** Generate monthly activity report for management.

**Steps:**
1. Go to Activity Logs
2. Filter: `date_from=2025-12-01`, `date_to=2025-12-31`
3. Click "Statistics" to see summary
4. Click "Export to Excel" for detailed report

**API Calls:**
```
GET /api/activity-logs/statistics?date_from=2025-12-01&date_to=2025-12-31
GET /api/activity-logs/export/excel?date_from=2025-12-01&date_to=2025-12-31
```

---

### Use Case 5: Inventory Reconciliation

**Scenario:** Product quantity doesn't match. Need to find who changed it.

**Steps:**
1. Go to Product details page
2. View Activity History
3. Filter logs showing quantity changes
4. Identify the change event with before/after values

**API Call:**
```
GET /api/activity-logs/model/Product/456
```

Then in frontend, filter results:
```javascript
const quantityChanges = logs.filter(log => 
  log.changes.old.quantity !== log.changes.attributes.quantity
);
```

---

## Response Structure Reference

### Log Entry Structure

```typescript
interface ActivityLog {
  id: number;
  event: 'created' | 'updated' | 'deleted';
  description: string;
  log_name: string;
  
  subject: {
    type: string;        // Short model name (e.g., "Order")
    full_type: string;   // Full class name
    id: number;
    data: object | null; // Full model (null if deleted)
  };
  
  causer: {
    type: string | null;   // "Employee" or "Customer"
    full_type: string | null;
    id: number | null;
    name: string;          // "John Doe" or "System"
  };
  
  changes: {
    attributes: object;  // New values
    old: object;         // Previous values
  };
  
  metadata: {
    ip_address: string | null;
    user_agent: string | null;
    url: string | null;
    method: string | null;  // "GET", "POST", "PUT", "DELETE"
  };
  
  created_at: string;           // ISO 8601 format
  created_at_human: string;     // "2 hours ago"
  created_at_formatted: string; // "2025-12-19 10:30:00"
}
```

---

## Best Practices

### 1. Performance

- Use pagination (don't load all logs at once)
- Default to last 30 days if no date filter
- Export limited to 10,000 records to prevent timeout

### 2. User Experience

- Show relative times ("2 hours ago") with tooltip for exact time
- Color-code events (green=created, yellow=updated, red=deleted)
- Auto-refresh dashboard statistics every 5 minutes
- Highlight changes in detail view (old value → new value)

### 3. Filtering

- Provide preset filters for common use cases
- Remember user's last filter selection
- Show result count immediately
- Allow clearing all filters at once

### 4. Export

- Include applied filters in filename (e.g., `activity-logs-Order-2025-12.xlsx`)
- Show loading indicator during export
- Confirm export success with download prompt
- Limit exports to reasonable size (10k records)

---

## Security & Permissions

**Access Control:**
- Only authenticated employees can view activity logs
- Consider role-based permissions (only managers see all logs)
- Customers cannot access system-wide activity logs
- Sensitive fields (passwords) are never logged

**Data Retention:**
- Logs are kept indefinitely for audit purposes
- Consider archiving old logs (> 1 year) to separate storage
- Implement log cleanup policies per company requirements

---

## Testing Guide

### Test 1: View Recent Logs
```bash
curl -X GET "http://localhost/api/activity-logs?per_page=10" \
  -H "Authorization: Bearer $TOKEN"
```

### Test 2: Filter by Event
```bash
curl -X GET "http://localhost/api/activity-logs?event=created&subject_type=Order" \
  -H "Authorization: Bearer $TOKEN"
```

### Test 3: View Model History
```bash
curl -X GET "http://localhost/api/activity-logs/model/Order/123" \
  -H "Authorization: Bearer $TOKEN"
```

### Test 4: Export CSV
```bash
curl -X GET "http://localhost/api/activity-logs/export/csv?date_from=2025-12-01" \
  -H "Authorization: Bearer $TOKEN" \
  --output activity-logs.csv
```

### Test 5: Statistics
```bash
curl -X GET "http://localhost/api/activity-logs/statistics" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Troubleshooting

### Issue: No logs appearing

**Solution:** Check if model has `AutoLogsActivity` trait:
```php
use App\Traits\AutoLogsActivity;

class Order extends Model
{
    use AutoLogsActivity;
    // ...
}
```

### Issue: Export taking too long

**Solution:** Limit export to specific date range. Exports are capped at 10,000 records.

### Issue: Can't see WHO made the change

**Solution:** Verify authentication guard is correct (api or customer). System uses Auth::guard('api') by default.

---

## Implementation Checklist

- [ ] Add Activity Logs menu item to navigation
- [ ] Create Activity Logs list page with filters
- [ ] Implement pagination
- [ ] Add export buttons (CSV/Excel)
- [ ] Create Statistics dashboard widget
- [ ] Add Activity History widget to detail pages (Order, Product, Customer)
- [ ] Implement filter dropdowns (populate from API)
- [ ] Add date range picker
- [ ] Implement search functionality
- [ ] Color-code events
- [ ] Show relative timestamps with tooltips
- [ ] Add loading states
- [ ] Test all filters
- [ ] Test exports with various filters
- [ ] Document for internal team

---

**Status:** ✅ Fully Implemented
**Package:** spatie/laravel-activitylog v4.10.2
**Database Table:** `activity_log`
**Last Updated:** December 19, 2025

---

## Support

For technical issues or questions about the activity logging system, contact the backend development team or refer to the [Spatie Activity Log documentation](https://spatie.be/docs/laravel-activitylog).
