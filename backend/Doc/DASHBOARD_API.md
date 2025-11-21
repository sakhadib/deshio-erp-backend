# Dashboard API Documentation

## Overview

The Dashboard API provides comprehensive business intelligence and analytics for the ERP system. This API enables frontend applications to display real-time metrics, trends, and insights across sales, inventory, operations, and financial performance.

## Base URL

All dashboard endpoints are prefixed with `/api/dashboard` and require authentication via JWT token.

```
Base URL: https://your-domain.com/api/dashboard
Authorization: Bearer {jwt_token}
```

---

## Authentication

All dashboard endpoints require authentication. Include the JWT token in the Authorization header:

```http
GET /api/dashboard/today-metrics
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## API Endpoints

### 1. Today's Key Metrics

Get today's comprehensive business metrics including sales, profit margins, and cash position.

**Endpoint:** `GET /dashboard/today-metrics`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID

**Request Example:**
```http
GET /api/dashboard/today-metrics?store_id=3
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "date": "2025-11-21",
    "total_sales": 45698.50,
    "paid_sales": 42300.00,
    "order_count": 127,
    "average_order_value": 359.83,
    "cost_of_goods_sold": 28456.70,
    "gross_margin": 17241.80,
    "gross_margin_percentage": 37.72,
    "total_expenses": 8500.00,
    "net_profit": 8741.80,
    "net_profit_percentage": 19.13,
    "cash_snapshot": {
      "accounts_receivable": 125430.25,
      "accounts_payable": 87650.00,
      "net_position": 37780.25
    }
  }
}
```

**Response Fields:**
- `total_sales`: Total order amount for today
- `paid_sales`: Amount already paid by customers
- `order_count`: Number of orders placed today
- `average_order_value`: Average value per order
- `cost_of_goods_sold`: Total COGS for items sold today
- `gross_margin`: Sales minus COGS
- `gross_margin_percentage`: Gross margin as percentage of sales
- `total_expenses`: Total approved expenses for today
- `net_profit`: Gross margin minus expenses
- `net_profit_percentage`: Net profit as percentage of sales
- `cash_snapshot.accounts_receivable`: Total unpaid customer orders
- `cash_snapshot.accounts_payable`: Total unpaid vendor/expense bills
- `cash_snapshot.net_position`: Receivable minus payable

---

### 2. Last 30 Days Sales

Get daily sales data for the last 30 days with complete breakdown.

**Endpoint:** `GET /dashboard/last-30-days-sales`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID

**Request Example:**
```http
GET /api/dashboard/last-30-days-sales?store_id=2
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "period": {
      "start_date": "2025-10-22",
      "end_date": "2025-11-21"
    },
    "total_sales": 1234567.89,
    "total_orders": 3456,
    "daily_sales": [
      {
        "date": "2025-10-22",
        "day_name": "Tue",
        "total_sales": 38450.25,
        "paid_amount": 36200.00,
        "order_count": 95
      },
      {
        "date": "2025-10-23",
        "day_name": "Wed",
        "total_sales": 42300.50,
        "paid_amount": 40100.00,
        "order_count": 108
      },
      ...more days...
      {
        "date": "2025-11-21",
        "day_name": "Thu",
        "total_sales": 45698.50,
        "paid_amount": 42300.00,
        "order_count": 127
      }
    ]
  }
}
```

**Use Case:**
- Display sales trend chart/graph
- Compare day-over-day performance
- Identify peak sales days

---

### 3. Sales by Channel

Get sales breakdown by order channel (Store/Counter, E-commerce, Social Commerce).

**Endpoint:** `GET /dashboard/sales-by-channel`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID
- `period` (optional): Time period - `today` (default), `week`, `month`, `year`

**Request Example:**
```http
GET /api/dashboard/sales-by-channel?period=month
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "period": "month",
    "total_sales": 856432.75,
    "total_orders": 2345,
    "channels": [
      {
        "channel": "counter",
        "channel_label": "Store/Counter",
        "total_sales": 456789.50,
        "paid_amount": 445200.00,
        "order_count": 1234,
        "percentage": 53.33
      },
      {
        "channel": "ecommerce",
        "channel_label": "E-commerce",
        "total_sales": 287543.25,
        "paid_amount": 280100.00,
        "order_count": 789,
        "percentage": 33.58
      },
      {
        "channel": "social_commerce",
        "channel_label": "Social Commerce",
        "total_sales": 112100.00,
        "paid_amount": 108500.00,
        "order_count": 322,
        "percentage": 13.09
      }
    ]
  }
}
```

**Channel Types:**
- `counter`: Physical store/POS sales
- `ecommerce`: Online website orders
- `social_commerce`: Facebook/Instagram shop orders

---

### 4. Top Stores by Sales

Get ranking of best performing store locations.

**Endpoint:** `GET /dashboard/top-stores`

**Query Parameters:**
- `limit` (optional): Number of stores to return (default: 10)
- `period` (optional): Time period - `today` (default), `week`, `month`, `year`

**Request Example:**
```http
GET /api/dashboard/top-stores?limit=5&period=month
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "period": "month",
    "total_sales_all_stores": 1456789.50,
    "top_stores": [
      {
        "rank": 1,
        "store_id": 3,
        "store_name": "Gulshan Branch",
        "store_location": "Dhaka, Dhaka Division",
        "store_type": "flagship",
        "total_sales": 456789.25,
        "paid_amount": 445200.00,
        "order_count": 1234,
        "average_order_value": 370.24,
        "contribution_percentage": 31.36
      },
      {
        "rank": 2,
        "store_id": 5,
        "store_name": "Dhanmondi Outlet",
        "store_location": "Dhaka, Dhaka Division",
        "store_type": "retail",
        "total_sales": 342156.80,
        "paid_amount": 330500.00,
        "order_count": 987,
        "average_order_value": 346.65,
        "contribution_percentage": 23.49
      },
      ...more stores...
    ]
  }
}
```

**Use Case:**
- Identify top performing locations
- Compare store performance
- Allocate resources based on performance

---

### 5. Today's Top Products

Get best selling products for today by revenue.

**Endpoint:** `GET /dashboard/today-top-products`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID
- `limit` (optional): Number of products to return (default: 10)

**Request Example:**
```http
GET /api/dashboard/today-top-products?limit=5&store_id=3
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "date": "2025-11-21",
    "top_products": [
      {
        "rank": 1,
        "product_id": 123,
        "product_name": "Premium Cotton T-Shirt",
        "product_sku": "TSHIRT-001",
        "total_quantity_sold": 45,
        "total_revenue": 22500.00,
        "order_count": 28,
        "average_price": 500.00
      },
      {
        "rank": 2,
        "product_id": 456,
        "product_name": "Designer Jeans",
        "product_sku": "JEANS-052",
        "total_quantity_sold": 23,
        "total_revenue": 34500.00,
        "order_count": 21,
        "average_price": 1500.00
      },
      ...more products...
    ]
  }
}
```

**Use Case:**
- Identify trending products
- Plan restocking priorities
- Feature popular items in marketing

---

### 6. Slow Moving Products

Get products with low turnover rate for inventory optimization.

**Endpoint:** `GET /dashboard/slow-moving-products`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID
- `limit` (optional): Number of products to return (default: 10)
- `days` (optional): Lookback period in days (default: 90)

**Request Example:**
```http
GET /api/dashboard/slow-moving-products?limit=10&days=90
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "period_days": 90,
    "slow_moving_products": [
      {
        "rank": 1,
        "product_id": 789,
        "product_name": "Winter Jacket XL",
        "product_sku": "JACKET-089",
        "category": "Outerwear",
        "current_stock": 45,
        "stock_value": 67500.00,
        "quantity_sold": 2,
        "order_count": 2,
        "turnover_rate": 4.44,
        "days_of_supply": 2025
      },
      {
        "rank": 2,
        "product_id": 234,
        "product_name": "Formal Shirt Size XXL",
        "product_sku": "SHIRT-234",
        "category": "Shirts",
        "current_stock": 38,
        "stock_value": 30400.00,
        "quantity_sold": 3,
        "order_count": 3,
        "turnover_rate": 7.89,
        "days_of_supply": 1140
      },
      ...more products...
    ]
  }
}
```

**Response Fields:**
- `turnover_rate`: (Quantity sold / Current stock) × 100
- `days_of_supply`: Estimated days until stock runs out based on current sales rate

**Use Case:**
- Identify overstocked items
- Plan discounts/promotions
- Optimize inventory investment

---

### 7. Low Stock & Out of Stock Products

Get products that need restocking attention.

**Endpoint:** `GET /dashboard/low-stock-products`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID
- `threshold` (optional): Low stock threshold quantity (default: 10)

**Request Example:**
```http
GET /api/dashboard/low-stock-products?threshold=15
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "low_stock_threshold": 15,
    "summary": {
      "out_of_stock_count": 12,
      "low_stock_count": 28,
      "total_items": 40
    },
    "out_of_stock": [
      {
        "rank": 1,
        "product_id": 567,
        "product_name": "Popular T-Shirt Medium",
        "product_sku": "TSHIRT-567",
        "store_id": 3,
        "store_name": "Gulshan Branch",
        "current_stock": 0,
        "batch_count": 0,
        "status": "out_of_stock"
      },
      ...more products...
    ],
    "low_stock": [
      {
        "rank": 1,
        "product_id": 890,
        "product_name": "Best Seller Jeans",
        "product_sku": "JEANS-890",
        "store_id": 5,
        "store_name": "Dhanmondi Outlet",
        "current_stock": 8,
        "batch_count": 2,
        "status": "low_stock"
      },
      ...more products...
    ]
  }
}
```

**Use Case:**
- Prevent stockouts
- Create purchase orders
- Transfer inventory between stores

---

### 8. Inventory Age by Value

Get inventory aging analysis showing how long inventory has been sitting.

**Endpoint:** `GET /dashboard/inventory-age-by-value`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID

**Request Example:**
```http
GET /api/dashboard/inventory-age-by-value
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "total_inventory_value": 2456789.50,
    "total_quantity": 45678,
    "total_batches": 1234,
    "age_categories": [
      {
        "label": "0-30 days",
        "age_range": "0-30 days",
        "inventory_value": 1234567.25,
        "quantity": 23456,
        "batch_count": 567,
        "percentage_of_total": 50.25
      },
      {
        "label": "31-60 days",
        "age_range": "31-60 days",
        "inventory_value": 789432.80,
        "quantity": 14567,
        "batch_count": 389,
        "percentage_of_total": 32.14
      },
      {
        "label": "61-90 days",
        "age_range": "61-90 days",
        "inventory_value": 345678.25,
        "quantity": 6234,
        "batch_count": 198,
        "percentage_of_total": 14.07
      },
      {
        "label": "90+ days",
        "age_range": "90+ days",
        "inventory_value": 87111.20,
        "quantity": 1421,
        "batch_count": 80,
        "percentage_of_total": 3.54
      }
    ]
  }
}
```

**Use Case:**
- Identify aging inventory
- Calculate inventory holding costs
- Plan clearance sales for old stock

---

### 9. Operations Today

Get today's order fulfillment and operations metrics.

**Endpoint:** `GET /dashboard/operations-today`

**Query Parameters:**
- `store_id` (optional): Filter by specific store ID

**Request Example:**
```http
GET /api/dashboard/operations-today?store_id=3
Authorization: Bearer {token}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "date": "2025-11-21",
    "total_orders": 127,
    "operations_status": {
      "pending": {
        "label": "Pending",
        "count": 12,
        "description": "Orders awaiting confirmation"
      },
      "confirmed": {
        "label": "Confirmed",
        "count": 8,
        "description": "Orders confirmed, awaiting processing"
      },
      "processing": {
        "label": "Processing",
        "count": 23,
        "description": "Orders being prepared"
      },
      "ready_for_pickup": {
        "label": "Ready for Pickup",
        "count": 7,
        "description": "Orders ready for customer pickup"
      },
      "shipped": {
        "label": "Shipped",
        "count": 15,
        "description": "Orders in transit"
      },
      "delivered": {
        "label": "Delivered",
        "count": 58,
        "description": "Successfully delivered orders"
      },
      "cancelled": {
        "label": "Cancelled",
        "count": 4,
        "description": "Cancelled orders"
      }
    },
    "returns": {
      "count": 3,
      "return_rate": 2.36,
      "description": "Product returns initiated today"
    },
    "alerts": {
      "overdue_orders": 5,
      "requires_immediate_action": 12
    }
  }
}
```

**Order Status Flow:**
1. `pending` → Order placed, awaiting confirmation
2. `confirmed` → Confirmed by staff, ready to process
3. `processing` → Being prepared for shipment
4. `ready_for_pickup` → Customer can pick up
5. `shipped` → Sent for delivery
6. `delivered` → Successfully delivered

**Use Case:**
- Monitor order fulfillment pipeline
- Identify bottlenecks
- Track return rates
- Alert staff to urgent orders

---

## Error Responses

All endpoints follow a consistent error response format:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message"
}
```

**Common HTTP Status Codes:**
- `200 OK`: Request successful
- `401 Unauthorized`: Missing or invalid authentication token
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation error
- `500 Internal Server Error`: Server error

---

## Data Filtering

### Store-Specific Data

Most endpoints support filtering by store ID:

```http
GET /api/dashboard/today-metrics?store_id=3
GET /api/dashboard/last-30-days-sales?store_id=5
GET /api/dashboard/operations-today?store_id=2
```

**Use Case:**
- Multi-store businesses can view per-location metrics
- Managers can focus on their assigned stores
- Compare performance across locations

### Time Period Filtering

Some endpoints support period filtering:

```http
GET /api/dashboard/sales-by-channel?period=today
GET /api/dashboard/sales-by-channel?period=week
GET /api/dashboard/sales-by-channel?period=month
GET /api/dashboard/sales-by-channel?period=year
```

**Available Periods:**
- `today`: Current day
- `week`: Current week (Monday to Sunday)
- `month`: Current calendar month
- `year`: Current calendar year

---

## Frontend Integration Examples

### React/Vue.js Example

```javascript
// Fetch today's metrics
const fetchTodayMetrics = async (storeId = null) => {
  try {
    const url = storeId 
      ? `/api/dashboard/today-metrics?store_id=${storeId}`
      : '/api/dashboard/today-metrics';
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (!response.ok) throw new Error('Failed to fetch metrics');
    
    const data = await response.json();
    return data.data;
  } catch (error) {
    console.error('Error fetching today\'s metrics:', error);
    throw error;
  }
};

// Fetch last 30 days sales for chart
const fetchSalesChart = async () => {
  try {
    const response = await fetch('/api/dashboard/last-30-days-sales', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });
    
    const data = await response.json();
    
    // Transform for chart library (e.g., Chart.js)
    return {
      labels: data.data.daily_sales.map(day => day.date),
      datasets: [{
        label: 'Daily Sales',
        data: data.data.daily_sales.map(day => day.total_sales)
      }]
    };
  } catch (error) {
    console.error('Error fetching sales chart:', error);
  }
};
```

### Axios Example

```javascript
import axios from 'axios';

// Configure axios instance
const api = axios.create({
  baseURL: 'https://your-domain.com/api',
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
});

// Fetch operations today
const getOperationsToday = async (storeId) => {
  try {
    const { data } = await api.get('/dashboard/operations-today', {
      params: { store_id: storeId }
    });
    return data.data;
  } catch (error) {
    console.error('Error fetching operations:', error);
    throw error;
  }
};

// Fetch slow moving products
const getSlowMovingProducts = async (limit = 10, days = 90) => {
  try {
    const { data } = await api.get('/dashboard/slow-moving-products', {
      params: { limit, days }
    });
    return data.data.slow_moving_products;
  } catch (error) {
    console.error('Error fetching slow moving products:', error);
    throw error;
  }
};
```

---

## Performance Considerations

### Caching Recommendations

For optimal performance, consider caching dashboard data:

```javascript
// Cache today's metrics for 5 minutes
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

const getCachedMetrics = () => {
  const cached = localStorage.getItem('dashboard_metrics');
  if (!cached) return null;
  
  const { data, timestamp } = JSON.parse(cached);
  if (Date.now() - timestamp > CACHE_DURATION) return null;
  
  return data;
};

const fetchTodayMetrics = async () => {
  // Check cache first
  const cached = getCachedMetrics();
  if (cached) return cached;
  
  // Fetch fresh data
  const data = await api.get('/dashboard/today-metrics');
  
  // Cache the result
  localStorage.setItem('dashboard_metrics', JSON.stringify({
    data: data.data,
    timestamp: Date.now()
  }));
  
  return data.data;
};
```

### Polling Strategy

For real-time updates, implement smart polling:

```javascript
let pollingInterval;

const startDashboardPolling = (callback, interval = 60000) => {
  // Initial fetch
  callback();
  
  // Poll every minute
  pollingInterval = setInterval(callback, interval);
};

const stopDashboardPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
};

// Usage
startDashboardPolling(async () => {
  const metrics = await fetchTodayMetrics();
  updateDashboardUI(metrics);
}, 60000); // Update every minute
```

---

## Business Intelligence Use Cases

### 1. Executive Dashboard
Display key metrics at a glance:
- Today's sales and profit
- Order count and average value
- Cash position (AR/AP)
- Top performing stores
- Critical alerts (low stock, pending orders)

### 2. Sales Analytics Dashboard
Track sales performance:
- 30-day sales trend chart
- Channel breakdown (counter/ecommerce/social)
- Store performance ranking
- Top selling products
- Sales targets vs actual

### 3. Inventory Management Dashboard
Optimize inventory:
- Low stock alerts
- Out of stock items
- Slow moving products
- Inventory aging analysis
- Restock recommendations

### 4. Operations Dashboard
Monitor fulfillment:
- Order pipeline status
- Pending approvals
- Shipping status
- Return rate tracking
- Overdue order alerts

---

## Data Refresh Frequency

**Recommended refresh intervals:**
- Today's Metrics: Every 5-10 minutes
- Last 30 Days Sales: Every 30 minutes
- Channel/Store Performance: Every 15 minutes
- Top Products: Every 10 minutes
- Inventory Metrics: Every 30 minutes
- Operations Status: Every 5 minutes (critical)

---

## Support & Troubleshooting

### Common Issues

**1. No data returned:**
- Check authentication token validity
- Verify store_id exists and user has access
- Ensure date range is valid

**2. Slow response times:**
- Implement client-side caching
- Reduce polling frequency
- Filter by store_id when possible

**3. Incorrect calculations:**
- Verify timezone settings
- Check order status filters
- Confirm payment status mapping

### Getting Help

For technical support or feature requests:
- Email: dev@deshio.com
- GitHub Issues: https://github.com/sakhadib/deshio-erp-backend
- Documentation: https://docs.deshio.com

---

**Last Updated:** November 21, 2025  
**API Version:** 1.0  
**Compatible with:** Laravel 11.x, ERP Backend v2.0+