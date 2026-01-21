# Order Intended Courier API Documentation

**Feature:** Order Courier Assignment & Lookup System  
**Date:** January 22, 2026  
**Version:** 1.0  
**Author:** Backend Team

---

## Overview

This feature adds the ability to assign and track intended courier services for orders. Employees can set the intended courier (e.g., pathao, sundarban, steadfast, redx) for each order, search and filter orders by courier, and perform individual or bulk courier lookups.

### Database Schema
- **Column:** `intended_courier` (VARCHAR 255, nullable, indexed)
- **Location:** `orders` table, after `carrier_name`
- **Index:** `idx_intended_courier` for optimized searching

### Common Courier Names
- pathao
- sundarban
- steadfast
- redx
- paperfly
- eCourier
- manual (for in-house delivery)

---

## API Endpoints

All endpoints require authentication via JWT token in the `Authorization: Bearer {token}` header.

### Base URL
```
http://localhost:8000/api
```

---

## 1. Set Intended Courier for Order

**Endpoint:** `PATCH /api/orders/{id}/set-courier`  
**Description:** Assign or update the intended courier for a specific order.

### Request

**Headers:**
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Path Parameters:**
- `id` (integer, required) - The order ID

**Body Parameters:**
```json
{
  "intended_courier": "pathao"
}
```

- `intended_courier` (string, required, max 100 chars) - The courier service name

### Response

**Success (200 OK):**
```json
{
  "success": true,
  "message": "Intended courier set successfully",
  "data": {
    "order_id": 123,
    "order_number": "ORD-20260122-0001",
    "intended_courier": "pathao",
    "status": "pending",
    "updated_at": "2026-01-22T10:30:00.000000Z"
  }
}
```

**Error (404 Not Found):**
```json
{
  "success": false,
  "message": "Order not found",
  "data": null
}
```

**Error (422 Validation Failed):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "intended_courier": [
      "The intended courier field is required.",
      "The intended courier may not be greater than 100 characters."
    ]
  }
}
```

### Frontend Integration Example

```javascript
// Set courier for order
async function setOrderCourier(orderId, courierName) {
  try {
    const response = await fetch(`/api/orders/${orderId}/set-courier`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        intended_courier: courierName
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      console.log('Courier set:', result.data.intended_courier);
      return result.data;
    } else {
      console.error('Error:', result.message);
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Failed to set courier:', error);
    throw error;
  }
}

// Usage
await setOrderCourier(123, 'pathao');
```

---

## 2. Get Orders by Courier (with Search & Filters)

**Endpoint:** `GET /api/orders/by-courier`  
**Description:** Retrieve paginated orders filtered by intended courier with advanced search and sorting capabilities.

### Request

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courier` | string | No | Filter by specific courier name. If omitted, returns courier summary. |
| `status` | string | No | Filter by order status (pending, confirmed, shipped, etc.) |
| `store_id` | integer | No | Filter by specific store |
| `date_from` | date | No | Filter orders from this date (format: YYYY-MM-DD) |
| `date_to` | date | No | Filter orders until this date (format: YYYY-MM-DD) |
| `search` | string | No | Search in order number, customer name, or phone |
| `sort_by` | string | No | Sort field (order_date, total_amount, intended_courier). Default: order_date |
| `sort_order` | string | No | Sort direction (asc, desc). Default: desc |
| `page` | integer | No | Page number for pagination. Default: 1 |
| `per_page` | integer | No | Items per page (max 100). Default: 15 |

### Response

**With Courier Parameter (200 OK):**
```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "order_number": "ORD-20260122-0001",
        "customer_name": "John Doe",
        "customer_phone": "+8801712345678",
        "store_name": "Deshi Store - Dhaka",
        "status": "pending",
        "payment_status": "paid",
        "total_amount": "2500.00",
        "intended_courier": "pathao",
        "carrier_name": null,
        "tracking_number": null,
        "order_date": "2026-01-22T08:00:00.000000Z",
        "shipping_address": {
          "street": "123 Main St",
          "city": "Dhaka",
          "postal_code": "1200"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/orders/by-courier?courier=pathao&page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost:8000/api/orders/by-courier?courier=pathao&page=5",
    "next_page_url": "http://localhost:8000/api/orders/by-courier?courier=pathao&page=2",
    "path": "http://localhost:8000/api/orders/by-courier",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 73
  }
}
```

**Without Courier Parameter - Summary (200 OK):**
```json
{
  "success": true,
  "message": "Courier summary retrieved successfully",
  "data": {
    "total_with_courier": 150,
    "total_without_courier": 25,
    "couriers": [
      {
        "intended_courier": "pathao",
        "order_count": 73
      },
      {
        "intended_courier": "steadfast",
        "order_count": 45
      },
      {
        "intended_courier": "sundarban",
        "order_count": 32
      }
    ]
  }
}
```

### Frontend Integration Example

```javascript
// Get orders by courier with filters
async function getOrdersByCourier(filters = {}) {
  const params = new URLSearchParams();
  
  // Add filters
  if (filters.courier) params.append('courier', filters.courier);
  if (filters.status) params.append('status', filters.status);
  if (filters.storeId) params.append('store_id', filters.storeId);
  if (filters.dateFrom) params.append('date_from', filters.dateFrom);
  if (filters.dateTo) params.append('date_to', filters.dateTo);
  if (filters.search) params.append('search', filters.search);
  if (filters.sortBy) params.append('sort_by', filters.sortBy);
  if (filters.sortOrder) params.append('sort_order', filters.sortOrder);
  if (filters.page) params.append('page', filters.page);
  if (filters.perPage) params.append('per_page', filters.perPage);
  
  try {
    const response = await fetch(`/api/orders/by-courier?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    });
    
    return await response.json();
  } catch (error) {
    console.error('Failed to fetch orders:', error);
    throw error;
  }
}

// Usage Examples:

// Get all Pathao orders
const pathaoOrders = await getOrdersByCourier({ 
  courier: 'pathao' 
});

// Get pending Steadfast orders with search
const results = await getOrdersByCourier({
  courier: 'steadfast',
  status: 'pending',
  search: 'John',
  sortBy: 'total_amount',
  sortOrder: 'desc',
  page: 1,
  perPage: 20
});

// Get courier summary (no courier parameter)
const summary = await getOrdersByCourier();
console.log('Total orders with courier:', summary.data.total_with_courier);
console.log('Courier breakdown:', summary.data.couriers);
```

---

## 3. Single Order Courier Lookup

**Endpoint:** `GET /api/orders/lookup-courier/{orderId}`  
**Description:** Retrieve courier information for a specific order.

### Request

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Path Parameters:**
- `orderId` (integer, required) - The order ID

### Response

**Success (200 OK):**
```json
{
  "success": true,
  "message": "Order courier lookup successful",
  "data": {
    "order_id": 123,
    "order_number": "ORD-20260122-0001",
    "intended_courier": "pathao",
    "status": "pending",
    "customer_name": "John Doe",
    "customer_phone": "+8801712345678",
    "store_name": "Deshi Store - Dhaka",
    "total_amount": "2500.00",
    "order_date": "2026-01-22T08:00:00.000000Z"
  }
}
```

**Error (404 Not Found):**
```json
{
  "success": false,
  "message": "Order not found",
  "data": null
}
```

### Frontend Integration Example

```javascript
// Lookup courier for single order
async function lookupOrderCourier(orderId) {
  try {
    const response = await fetch(`/api/orders/lookup-courier/${orderId}`, {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    });
    
    const result = await response.json();
    
    if (result.success) {
      return result.data;
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Lookup failed:', error);
    throw error;
  }
}

// Usage
const courierInfo = await lookupOrderCourier(123);
console.log(`Order ${courierInfo.order_number} -> ${courierInfo.intended_courier}`);
```

---

## 4. Bulk Order Courier Lookup

**Endpoint:** `POST /api/orders/bulk-lookup-courier`  
**Description:** Retrieve courier information for multiple orders at once.

### Request

**Headers:**
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Body Parameters:**
```json
{
  "order_ids": [123, 456, 789, 1011]
}
```

- `order_ids` (array, required) - Array of order IDs (max 100 IDs per request)

### Response

**Success (200 OK):**
```json
{
  "success": true,
  "message": "Bulk lookup completed",
  "data": {
    "total_found": 3,
    "total_requested": 4,
    "orders": [
      {
        "order_id": 123,
        "order_number": "ORD-20260122-0001",
        "intended_courier": "pathao",
        "status": "pending",
        "customer_name": "John Doe",
        "customer_phone": "+8801712345678",
        "store_name": "Deshi Store - Dhaka",
        "total_amount": "2500.00"
      },
      {
        "order_id": 456,
        "order_number": "ORD-20260122-0002",
        "intended_courier": "steadfast",
        "status": "confirmed",
        "customer_name": "Jane Smith",
        "customer_phone": "+8801798765432",
        "store_name": "Deshi Store - Chittagong",
        "total_amount": "3200.00"
      },
      {
        "order_id": 789,
        "order_number": "ORD-20260122-0003",
        "intended_courier": null,
        "status": "pending",
        "customer_name": "Bob Wilson",
        "customer_phone": "+8801656789012",
        "store_name": "Deshi Store - Dhaka",
        "total_amount": "1800.00"
      }
    ]
  }
}
```

**Error (422 Validation Failed):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_ids": [
      "The order ids field is required.",
      "The order ids must be an array.",
      "The order ids may not have more than 100 items."
    ]
  }
}
```

### Frontend Integration Example

```javascript
// Bulk lookup courier information
async function bulkLookupCouriers(orderIds) {
  // Validate array size
  if (orderIds.length > 100) {
    throw new Error('Maximum 100 order IDs allowed per request');
  }
  
  try {
    const response = await fetch('/api/orders/bulk-lookup-courier', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        order_ids: orderIds
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      return result.data;
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Bulk lookup failed:', error);
    throw error;
  }
}

// Usage
const orderIds = [123, 456, 789, 1011];
const results = await bulkLookupCouriers(orderIds);

console.log(`Found ${results.total_found} out of ${results.total_requested} orders`);

// Process results
results.orders.forEach(order => {
  console.log(`Order ${order.order_number}: ${order.intended_courier || 'No courier set'}`);
});

// Group by courier
const groupedByCourier = results.orders.reduce((acc, order) => {
  const courier = order.intended_courier || 'unassigned';
  if (!acc[courier]) acc[courier] = [];
  acc[courier].push(order);
  return acc;
}, {});

console.log('Grouped by courier:', groupedByCourier);
```

---

## 5. Get Available Couriers

**Endpoint:** `GET /api/orders/available-couriers`  
**Description:** Retrieve a list of all courier services that have been used, with order counts.

### Request

**Headers:**
```
Authorization: Bearer {jwt_token}
```

### Response

**Success (200 OK):**
```json
{
  "success": true,
  "message": "Available couriers retrieved successfully",
  "data": [
    {
      "courier_name": "pathao",
      "order_count": 73
    },
    {
      "courier_name": "steadfast",
      "order_count": 45
    },
    {
      "courier_name": "sundarban",
      "order_count": 32
    },
    {
      "courier_name": "redx",
      "order_count": 18
    },
    {
      "courier_name": "paperfly",
      "order_count": 12
    }
  ]
}
```

### Frontend Integration Example

```javascript
// Get list of available couriers
async function getAvailableCouriers() {
  try {
    const response = await fetch('/api/orders/available-couriers', {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    });
    
    const result = await response.json();
    
    if (result.success) {
      return result.data;
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Failed to fetch couriers:', error);
    throw error;
  }
}

// Usage - populate dropdown
async function populateCourierDropdown() {
  const couriers = await getAvailableCouriers();
  const selectElement = document.getElementById('courier-select');
  
  // Clear existing options
  selectElement.innerHTML = '<option value="">Select Courier</option>';
  
  // Add courier options
  couriers.forEach(courier => {
    const option = document.createElement('option');
    option.value = courier.courier_name;
    option.textContent = `${courier.courier_name} (${courier.order_count} orders)`;
    selectElement.appendChild(option);
  });
}
```

---

## Complete Frontend Example: Order Courier Management Component

```javascript
class OrderCourierManager {
  constructor(authToken) {
    this.authToken = authToken;
    this.baseUrl = '/api/orders';
  }
  
  // Helper method for API calls
  async apiCall(endpoint, method = 'GET', body = null) {
    const options = {
      method,
      headers: {
        'Authorization': `Bearer ${this.authToken}`,
        'Content-Type': 'application/json'
      }
    };
    
    if (body) {
      options.body = JSON.stringify(body);
    }
    
    const response = await fetch(endpoint, options);
    const result = await response.json();
    
    if (!result.success) {
      throw new Error(result.message || 'API call failed');
    }
    
    return result.data;
  }
  
  // Set courier for an order
  async setCourier(orderId, courierName) {
    return await this.apiCall(
      `${this.baseUrl}/${orderId}/set-courier`,
      'PATCH',
      { intended_courier: courierName }
    );
  }
  
  // Get orders by courier with filters
  async getOrdersByCourier(filters = {}) {
    const params = new URLSearchParams(filters);
    return await this.apiCall(`${this.baseUrl}/by-courier?${params}`);
  }
  
  // Lookup single order
  async lookupOrder(orderId) {
    return await this.apiCall(`${this.baseUrl}/lookup-courier/${orderId}`);
  }
  
  // Bulk lookup
  async bulkLookup(orderIds) {
    if (orderIds.length > 100) {
      throw new Error('Maximum 100 orders per request');
    }
    return await this.apiCall(
      `${this.baseUrl}/bulk-lookup-courier`,
      'POST',
      { order_ids: orderIds }
    );
  }
  
  // Get available couriers
  async getAvailableCouriers() {
    return await this.apiCall(`${this.baseUrl}/available-couriers`);
  }
  
  // Get courier summary
  async getCourierSummary() {
    return await this.apiCall(`${this.baseUrl}/by-courier`);
  }
}

// Usage Example
const manager = new OrderCourierManager(localStorage.getItem('auth_token'));

// Set courier
await manager.setCourier(123, 'pathao');

// Get all Pathao orders
const pathaoOrders = await manager.getOrdersByCourier({ 
  courier: 'pathao',
  status: 'pending',
  page: 1
});

// Lookup specific orders
const orderInfo = await manager.lookupOrder(123);

// Bulk lookup
const bulkResults = await manager.bulkLookup([123, 456, 789]);

// Get available couriers for dropdown
const couriers = await manager.getAvailableCouriers();

// Get summary dashboard
const summary = await manager.getCourierSummary();
console.log(`Orders with courier: ${summary.total_with_courier}`);
console.log(`Orders without courier: ${summary.total_without_courier}`);
```

---

## UI/UX Recommendations

### 1. Order List View
- Add "Courier" column to order tables
- Show courier badge with color coding:
  - Pathao: Blue
  - Steadfast: Green
  - Sundarban: Orange
  - RedX: Red
  - No courier: Gray

### 2. Order Detail View
- Add "Set Courier" button/dropdown
- Show current courier with edit option
- Display courier assignment history (if implemented)

### 3. Courier Dashboard
- Summary cards showing:
  - Total orders by courier
  - Pending orders by courier
  - Delivered orders by courier
- Filter bar with courier dropdown
- Search functionality

### 4. Bulk Operations
- Checkbox selection for multiple orders
- "Assign Courier" bulk action
- Progress indicator for bulk operations

---

## Error Handling

### Common Error Codes

| Status Code | Description | Action |
|-------------|-------------|--------|
| 401 | Unauthorized | Redirect to login |
| 404 | Order not found | Show error message, refresh list |
| 422 | Validation error | Display field-specific errors |
| 500 | Server error | Show generic error, retry option |

### Error Handling Example

```javascript
async function handleCourierOperation(operation) {
  try {
    const result = await operation();
    showSuccessMessage('Operation completed successfully');
    return result;
  } catch (error) {
    if (error.status === 401) {
      // Unauthorized - redirect to login
      window.location.href = '/login';
    } else if (error.status === 404) {
      showErrorMessage('Order not found. Please refresh the page.');
    } else if (error.status === 422) {
      // Validation errors
      displayValidationErrors(error.errors);
    } else {
      showErrorMessage('An unexpected error occurred. Please try again.');
      console.error('Operation failed:', error);
    }
    throw error;
  }
}

// Usage
await handleCourierOperation(() => 
  manager.setCourier(123, 'pathao')
);
```

---

## Performance Considerations

### Indexing
- The `intended_courier` column is indexed for optimal query performance
- Filtering and sorting by courier is highly optimized

### Pagination
- Always use pagination for large result sets
- Default page size: 15 items
- Maximum page size: 100 items

### Caching Recommendations
- Cache available couriers list (updates infrequently)
- Cache courier summary (refresh every 5 minutes)
- Don't cache individual order courier data (changes frequently)

---

## Testing Checklist

### Backend Testing
- ✅ Column added with proper index
- ✅ All 5 API endpoints functional
- ✅ Validation working correctly
- ✅ Pagination working as expected
- ✅ Filtering and sorting accurate
- ✅ Bulk operations handle edge cases

### Frontend Testing
- [ ] Can set courier for individual order
- [ ] Can filter orders by courier
- [ ] Can search within filtered results
- [ ] Sorting works correctly
- [ ] Pagination displays correctly
- [ ] Bulk lookup handles 100+ IDs gracefully
- [ ] Available couriers dropdown populates
- [ ] Error messages display appropriately
- [ ] Loading states work correctly

---

## Migration Notes

### Database Changes
```sql
-- Column added
ALTER TABLE orders ADD COLUMN intended_courier VARCHAR(255) NULL AFTER carrier_name;

-- Index added for performance
ALTER TABLE orders ADD INDEX idx_intended_courier (intended_courier);

-- Also fixed order_date column default value issue
ALTER TABLE orders MODIFY order_date TIMESTAMP NULL DEFAULT NULL;
```

### Rollback (if needed)
```sql
ALTER TABLE orders DROP INDEX idx_intended_courier;
ALTER TABLE orders DROP COLUMN intended_courier;
```

---

## Support & Questions

For backend issues or questions about these APIs, contact the backend development team.

**Last Updated:** January 22, 2026  
**Documentation Version:** 1.0
