# Dashboard Stores Summary API Documentation

**Created:** December 27, 2025  
**Version:** 1.0  
**Endpoint:** `GET /api/dashboard/stores-summary`

## Overview

This API provides comprehensive performance summaries for **all stores** in a single request. Perfect for executive dashboards, admin panels, and multi-store performance monitoring.

## Purpose

- View sales, inventory, and performance metrics across all stores
- Compare store performance side-by-side
- Monitor overall business health
- Identify top-performing and underperforming stores
- Track inventory levels across locations

---

## Endpoint

```
GET /api/dashboard/stores-summary
```

### Authentication

**Required:** Bearer Token (JWT)

```http
Authorization: Bearer {your-token}
```

---

## Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | No | `today` | Time period: `today`, `week`, `month`, `year` |
| `date_from` | date | No | - | Custom start date (YYYY-MM-DD). Overrides `period` |
| `date_to` | date | No | - | Custom end date (YYYY-MM-DD). Overrides `period` |

### Period Options

- **`today`**: Today's data (midnight to now)
- **`week`**: Current week (Monday to Sunday)
- **`month`**: Current month (1st to last day)
- **`year`**: Current year (Jan 1 to Dec 31)
- **Custom Range**: Use `date_from` and `date_to` for specific dates

---

## Response Structure

```json
{
  "success": true,
  "data": {
    "period": {
      "type": "today",
      "start_date": "2025-12-27",
      "end_date": "2025-12-27"
    },
    "overall_totals": {
      "total_sales": 458750.50,
      "total_orders": 342,
      "total_inventory_value": 2500000.00,
      "total_profit": 125430.25,
      "total_returns": 12
    },
    "stores": [
      {
        "store": {
          "id": 1,
          "name": "Main Store - Dhaka",
          "store_code": "ST-001",
          "store_type": "retail",
          "address": "123 Main St, Dhaka"
        },
        "sales": {
          "total_sales": 185420.00,
          "total_orders": 145,
          "avg_order_value": "1278.76",
          "paid_amount": 165000.00,
          "outstanding_amount": 20420.00,
          "orders_by_status": {
            "pending": 12,
            "confirmed": 8,
            "completed": 115,
            "shipped": 10
          },
          "orders_by_payment_status": {
            "paid": 110,
            "partially_paid": 25,
            "unpaid": 10
          },
          "orders_by_type": {
            "counter": 95,
            "ecommerce": 30,
            "social_commerce": 20
          }
        },
        "performance": {
          "gross_profit": 52340.50,
          "gross_margin_percentage": "28.23",
          "expenses": 12500.00,
          "net_profit": 39840.50,
          "net_margin_percentage": "21.48",
          "cogs": 133079.50
        },
        "inventory": {
          "total_value": 850000.00,
          "total_products": 450,
          "low_stock_count": 23,
          "out_of_stock_count": 5
        },
        "top_products": [
          {
            "product_id": 42,
            "product_name": "Premium Wireless Headphones",
            "sku": "TECH-001",
            "quantity_sold": 45,
            "revenue": 22500.00
          },
          {
            "product_id": 58,
            "product_name": "Smart Watch Pro",
            "sku": "TECH-015",
            "quantity_sold": 38,
            "revenue": 19000.00
          }
        ],
        "returns": {
          "total_returns": 5,
          "return_rate": "3.45"
        },
        "customers": {
          "unique_customers": 98,
          "repeat_customers": 32
        }
      },
      {
        "store": {
          "id": 2,
          "name": "Warehouse - Chittagong",
          "store_code": "WH-001",
          "store_type": "warehouse",
          "address": "456 Port Road, Chittagong"
        },
        "sales": {
          "total_sales": 273330.50,
          "total_orders": 197,
          "avg_order_value": "1387.46",
          "paid_amount": 250000.00,
          "outstanding_amount": 23330.50,
          "orders_by_status": {
            "pending": 8,
            "confirmed": 15,
            "completed": 165,
            "shipped": 9
          },
          "orders_by_payment_status": {
            "paid": 160,
            "partially_paid": 30,
            "unpaid": 7
          },
          "orders_by_type": {
            "counter": 120,
            "ecommerce": 55,
            "social_commerce": 22
          }
        },
        "performance": {
          "gross_profit": 73089.75,
          "gross_margin_percentage": "26.74",
          "expenses": 18500.00,
          "net_profit": 54589.75,
          "net_margin_percentage": "19.97",
          "cogs": 200240.75
        },
        "inventory": {
          "total_value": 1650000.00,
          "total_products": 680,
          "low_stock_count": 45,
          "out_of_stock_count": 12
        },
        "top_products": [
          {
            "product_id": 23,
            "product_name": "Gaming Laptop Ultra",
            "sku": "COMP-045",
            "quantity_sold": 15,
            "revenue": 45000.00
          },
          {
            "product_id": 67,
            "product_name": "4K Monitor",
            "sku": "DISP-022",
            "quantity_sold": 28,
            "revenue": 28000.00
          }
        ],
        "returns": {
          "total_returns": 7,
          "return_rate": "3.55"
        },
        "customers": {
          "unique_customers": 132,
          "repeat_customers": 48
        }
      }
    ],
    "store_count": 2
  }
}
```

---

## Response Fields Explained

### Overall Totals
| Field | Description |
|-------|-------------|
| `total_sales` | Sum of all sales across all stores |
| `total_orders` | Total number of orders across all stores |
| `total_inventory_value` | Total inventory value across all stores |
| `total_profit` | Total gross profit across all stores |
| `total_returns` | Total product returns across all stores |

### Store Object
| Field | Description |
|-------|-------------|
| `id` | Store database ID |
| `name` | Store name |
| `store_code` | Unique store code |
| `store_type` | Type: `retail`, `warehouse`, `outlet`, etc. |
| `address` | Physical address |

### Sales Metrics
| Field | Description |
|-------|-------------|
| `total_sales` | Total revenue for the period |
| `total_orders` | Number of orders (excluding cancelled) |
| `avg_order_value` | Average order value (sales / orders) |
| `paid_amount` | Amount already collected |
| `outstanding_amount` | Amount still owed |
| `orders_by_status` | Breakdown: pending, confirmed, completed, etc. |
| `orders_by_payment_status` | Breakdown: paid, partially_paid, unpaid |
| `orders_by_type` | Breakdown: counter, ecommerce, social_commerce |

### Performance Metrics
| Field | Description |
|-------|-------------|
| `gross_profit` | Sales - COGS (Cost of Goods Sold) |
| `gross_margin_percentage` | (Gross Profit / Sales) × 100 |
| `expenses` | Operating expenses for the period |
| `net_profit` | Gross Profit - Expenses |
| `net_margin_percentage` | (Net Profit / Sales) × 100 |
| `cogs` | Total cost of goods sold |

### Inventory Metrics
| Field | Description |
|-------|-------------|
| `total_value` | Total inventory value at selling price |
| `total_products` | Number of unique products in stock |
| `low_stock_count` | Products with quantity < 10 |
| `out_of_stock_count` | Products with quantity ≤ 0 |

### Top Products (per store)
Returns top 5 products by quantity sold during the period.

| Field | Description |
|-------|-------------|
| `product_id` | Product database ID |
| `product_name` | Product name |
| `sku` | Stock Keeping Unit |
| `quantity_sold` | Total units sold |
| `revenue` | Total revenue from this product |

### Returns
| Field | Description |
|-------|-------------|
| `total_returns` | Number of product returns |
| `return_rate` | (Returns / Orders) × 100 |

### Customers
| Field | Description |
|-------|-------------|
| `unique_customers` | Number of unique customers who ordered |
| `repeat_customers` | Customers with more than 1 order |

---

## Example Requests

### 1. Today's Summary (Default)
```bash
curl -X GET "http://localhost:8000/api/dashboard/stores-summary" \
  -H "Authorization: Bearer {token}"
```

### 2. This Week's Summary
```bash
curl -X GET "http://localhost:8000/api/dashboard/stores-summary?period=week" \
  -H "Authorization: Bearer {token}"
```

### 3. This Month's Summary
```bash
curl -X GET "http://localhost:8000/api/dashboard/stores-summary?period=month" \
  -H "Authorization: Bearer {token}"
```

### 4. Custom Date Range
```bash
curl -X GET "http://localhost:8000/api/dashboard/stores-summary?date_from=2025-12-01&date_to=2025-12-25" \
  -H "Authorization: Bearer {token}"
```

### 5. Full Year Summary
```bash
curl -X GET "http://localhost:8000/api/dashboard/stores-summary?period=year" \
  -H "Authorization: Bearer {token}"
```

---

## Frontend Integration Examples

### React Component - Stores Dashboard

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const StoresDashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState('today');

  useEffect(() => {
    fetchStoresSummary();
  }, [period]);

  const fetchStoresSummary = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/dashboard/stores-summary', {
        params: { period },
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      });
      setData(response.data.data);
    } catch (error) {
      console.error('Error fetching stores summary:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (!data) return <div>No data available</div>;

  return (
    <div className="stores-dashboard">
      <header>
        <h1>Multi-Store Dashboard</h1>
        <select value={period} onChange={(e) => setPeriod(e.target.value)}>
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="year">This Year</option>
        </select>
      </header>

      {/* Overall Totals */}
      <div className="overall-totals">
        <div className="metric-card">
          <h3>Total Sales</h3>
          <p className="amount">৳{data.overall_totals.total_sales.toLocaleString()}</p>
        </div>
        <div className="metric-card">
          <h3>Total Orders</h3>
          <p className="count">{data.overall_totals.total_orders}</p>
        </div>
        <div className="metric-card">
          <h3>Total Profit</h3>
          <p className="amount">৳{data.overall_totals.total_profit.toLocaleString()}</p>
        </div>
        <div className="metric-card">
          <h3>Inventory Value</h3>
          <p className="amount">৳{data.overall_totals.total_inventory_value.toLocaleString()}</p>
        </div>
      </div>

      {/* Individual Store Cards */}
      <div className="stores-grid">
        {data.stores.map((store) => (
          <StoreCard key={store.store.id} store={store} />
        ))}
      </div>
    </div>
  );
};

const StoreCard = ({ store }) => {
  return (
    <div className="store-card">
      <div className="store-header">
        <h2>{store.store.name}</h2>
        <span className="store-code">{store.store.store_code}</span>
      </div>

      <div className="store-metrics">
        {/* Sales */}
        <div className="metric-section">
          <h3>Sales</h3>
          <p>Revenue: ৳{store.sales.total_sales.toLocaleString()}</p>
          <p>Orders: {store.sales.total_orders}</p>
          <p>Avg Order: ৳{parseFloat(store.sales.avg_order_value).toLocaleString()}</p>
          <p>Outstanding: ৳{store.sales.outstanding_amount.toLocaleString()}</p>
        </div>

        {/* Performance */}
        <div className="metric-section">
          <h3>Performance</h3>
          <p>Gross Profit: ৳{store.performance.gross_profit.toLocaleString()}</p>
          <p>Gross Margin: {store.performance.gross_margin_percentage}%</p>
          <p>Net Profit: ৳{store.performance.net_profit.toLocaleString()}</p>
          <p>Net Margin: {store.performance.net_margin_percentage}%</p>
        </div>

        {/* Inventory */}
        <div className="metric-section">
          <h3>Inventory</h3>
          <p>Total Value: ৳{store.inventory.total_value.toLocaleString()}</p>
          <p>Products: {store.inventory.total_products}</p>
          <p className="warning">Low Stock: {store.inventory.low_stock_count}</p>
          <p className="danger">Out of Stock: {store.inventory.out_of_stock_count}</p>
        </div>

        {/* Top Products */}
        <div className="metric-section">
          <h3>Top Products</h3>
          <ul>
            {store.top_products.map((product, idx) => (
              <li key={product.product_id}>
                {idx + 1}. {product.product_name} ({product.quantity_sold} sold)
              </li>
            ))}
          </ul>
        </div>

        {/* Returns & Customers */}
        <div className="metric-section">
          <h3>Returns & Customers</h3>
          <p>Returns: {store.returns.total_returns} ({store.returns.return_rate}%)</p>
          <p>Unique Customers: {store.customers.unique_customers}</p>
          <p>Repeat Customers: {store.customers.repeat_customers}</p>
        </div>
      </div>
    </div>
  );
};

export default StoresDashboard;
```

### Comparison Chart Component

```jsx
import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const StoresComparisonChart = ({ stores }) => {
  const chartData = stores.map(store => ({
    name: store.store.name,
    sales: store.sales.total_sales,
    profit: store.performance.net_profit,
    orders: store.sales.total_orders,
  }));

  return (
    <ResponsiveContainer width="100%" height={400}>
      <BarChart data={chartData}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="name" />
        <YAxis />
        <Tooltip />
        <Legend />
        <Bar dataKey="sales" fill="#8884d8" name="Total Sales" />
        <Bar dataKey="profit" fill="#82ca9d" name="Net Profit" />
      </BarChart>
    </ResponsiveContainer>
  );
};

export default StoresComparisonChart;
```

### Performance Table Component

```jsx
const StoresPerformanceTable = ({ stores }) => {
  // Sort stores by sales (descending)
  const sortedStores = [...stores].sort((a, b) => 
    b.sales.total_sales - a.sales.total_sales
  );

  return (
    <table className="performance-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Store</th>
          <th>Sales</th>
          <th>Orders</th>
          <th>Avg Order</th>
          <th>Gross Margin</th>
          <th>Net Margin</th>
          <th>Returns</th>
        </tr>
      </thead>
      <tbody>
        {sortedStores.map((store, index) => (
          <tr key={store.store.id}>
            <td>{index + 1}</td>
            <td>{store.store.name}</td>
            <td>৳{store.sales.total_sales.toLocaleString()}</td>
            <td>{store.sales.total_orders}</td>
            <td>৳{parseFloat(store.sales.avg_order_value).toLocaleString()}</td>
            <td>{store.performance.gross_margin_percentage}%</td>
            <td>{store.performance.net_margin_percentage}%</td>
            <td>{store.returns.return_rate}%</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};
```

---

## Common Use Cases

### 1. Executive Dashboard
Display high-level metrics across all stores for management overview.

### 2. Store Performance Comparison
Compare sales, profit margins, and efficiency across locations.

### 3. Inventory Management
Identify stores with low stock or excess inventory.

### 4. Resource Allocation
Determine which stores need more inventory or staff based on performance.

### 5. Trend Analysis
Track performance trends over different time periods (day/week/month/year).

### 6. Problem Identification
Quickly spot underperforming stores or inventory issues.

---

## Performance Considerations

### Caching Recommendations
```javascript
// Cache for 5 minutes
const CACHE_KEY = `stores-summary-${period}`;
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

const getCachedData = () => {
  const cached = localStorage.getItem(CACHE_KEY);
  if (cached) {
    const { data, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp < CACHE_DURATION) {
      return data;
    }
  }
  return null;
};

const setCachedData = (data) => {
  localStorage.setItem(CACHE_KEY, JSON.stringify({
    data,
    timestamp: Date.now()
  }));
};
```

### Loading States
Always implement proper loading indicators as this endpoint aggregates data from multiple stores.

### Error Handling
```javascript
try {
  const response = await axios.get('/api/dashboard/stores-summary');
  setData(response.data.data);
} catch (error) {
  if (error.response?.status === 401) {
    // Redirect to login
    window.location.href = '/login';
  } else {
    // Show error message
    setError('Failed to load dashboard data. Please try again.');
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Error fetching stores summary",
  "error": "Database connection failed"
}
```

---

## Notes

- **Excludes Cancelled Orders**: All metrics exclude orders with `status = 'cancelled'`
- **Real-time Data**: Data is calculated in real-time (not pre-aggregated)
- **COGS Calculation**: Uses stored `cogs` field from order_items, falls back to batch cost_price
- **Inventory Value**: Calculated using current selling prices from batches
- **Low Stock Threshold**: Products with quantity < 10
- **Response Time**: May vary based on number of stores and data volume (typically 1-3 seconds)

---

## Related Endpoints

- `GET /api/dashboard/today-metrics` - Single store metrics
- `GET /api/dashboard/top-stores` - Top performing stores only
- `GET /api/dashboard/last-30-days-sales` - Sales trend over time
- `GET /api/reports/dashboard` - Alternative comprehensive report

---

## Support

For issues or feature requests, contact the backend development team.

**Last Updated:** December 27, 2025
