# Barcode Location Tracking - Specialized View Endpoints

This document covers the specialized view endpoints added to the Barcode Location Tracking system. These endpoints provide optimized queries for common business operations and reporting needs.

## Table of Contents

1. [Overview](#overview)
2. [Endpoints](#endpoints)
   - [View Barcodes by Product](#1-view-barcodes-by-product)
   - [View Barcodes by Batch](#2-view-barcodes-by-batch)
   - [View Sales by Date Range](#3-view-sales-by-date-range)
   - [Compare Multiple Stores](#4-compare-multiple-stores)
   - [View Recently Added Barcodes](#5-view-recently-added-barcodes)
3. [Use Cases](#use-cases)
4. [Integration Examples](#integration-examples)

---

## Overview

These specialized endpoints complement the general barcode tracking APIs by providing focused views for specific business needs:

- **Product-centric view**: See all units of a product across locations
- **Batch tracking**: Monitor entire production batches
- **Sales reporting**: Track sales performance over time
- **Store comparison**: Compare inventory across multiple stores
- **Recent activity**: Monitor new inventory additions

All endpoints require authentication via JWT Bearer token.

---

## Endpoints

### 1. View Barcodes by Product

View all barcodes for a specific product with location and status breakdown.

**Endpoint:** `GET /api/barcode-tracking/by-product/{productId}`

**Path Parameters:**
- `productId` (integer, required) - The ID of the product

**Query Parameters:**
- `status` (string, optional) - Filter by status (in_warehouse, in_shop, on_display, etc.)
- `store_id` (integer, optional) - Filter by specific store
- `available_only` (boolean, optional) - Show only barcodes available for sale
- `per_page` (integer, optional) - Items per page (default: 50)

**Response:**

```json
{
  "success": true,
  "data": {
    "product": {
      "id": 123,
      "name": "Designer Saree Collection 2024",
      "sku": "SAREE-2024-001"
    },
    "summary": {
      "total_units": 150,
      "active": 145,
      "inactive": 5,
      "available_for_sale": 120,
      "sold": 30
    },
    "status_breakdown": {
      "in_warehouse": 50,
      "in_shop": 60,
      "on_display": 10,
      "with_customer": 30
    },
    "store_distribution": [
      {
        "store_id": 1,
        "store_name": "Main Showroom",
        "count": 80,
        "available": 70
      },
      {
        "store_id": 2,
        "store_name": "Mall Branch",
        "count": 40,
        "available": 30
      }
    ],
    "filters": {
      "status": null,
      "store_id": null,
      "available_only": false
    },
    "barcodes": [
      {
        "id": 1001,
        "barcode": "SAR2024001001",
        "current_store": {
          "id": 1,
          "name": "Main Showroom"
        },
        "current_status": "on_display",
        "status_label": "On Display",
        "location_updated_at": "2024-01-15T10:30:00Z",
        "is_active": true,
        "is_available_for_sale": true
      }
      // ... more barcodes
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 150,
      "last_page": 3
    }
  }
}
```

**Use Case Example:**
```bash
# See all units of a product currently on display
GET /api/barcode-tracking/by-product/123?status=on_display

# Find available units at specific store
GET /api/barcode-tracking/by-product/123?store_id=1&available_only=true
```

---

### 2. View Barcodes by Batch

View all barcodes from a specific production batch with current locations.

**Endpoint:** `GET /api/barcode-tracking/by-batch/{batchId}`

**Path Parameters:**
- `batchId` (integer, required) - The ID of the batch

**Query Parameters:**
- `status` (string, optional) - Filter by status
- `store_id` (integer, optional) - Filter by specific store
- `available_only` (boolean, optional) - Show only available barcodes

**Response:**

```json
{
  "success": true,
  "data": {
    "batch": {
      "id": 45,
      "batch_number": "BATCH-2024-001",
      "product": {
        "id": 123,
        "name": "Designer Saree Collection 2024",
        "sku": "SAREE-2024-001"
      },
      "original_quantity": 100
    },
    "summary": {
      "total_units": 100,
      "active": 98,
      "available_for_sale": 85,
      "sold": 15,
      "defective": 2
    },
    "status_breakdown": [
      {
        "status": "in_shop",
        "count": 50
      },
      {
        "status": "on_display",
        "count": 35
      },
      {
        "status": "with_customer",
        "count": 15
      }
    ],
    "store_distribution": [
      {
        "store_id": 1,
        "store_name": "Main Showroom",
        "count": 60
      },
      {
        "store_id": 2,
        "store_name": "Mall Branch",
        "count": 25
      }
    ],
    "filters": {
      "status": null,
      "store_id": null,
      "available_only": false
    },
    "barcodes": [
      {
        "id": 1001,
        "barcode": "SAR2024001001",
        "current_store": {
          "id": 1,
          "name": "Main Showroom"
        },
        "current_status": "in_shop",
        "status_label": "In Shop Inventory",
        "is_active": true,
        "is_defective": false,
        "is_available_for_sale": true,
        "location_updated_at": "2024-01-15T10:30:00Z"
      }
      // ... more barcodes
    ]
  }
}
```

**Use Case Example:**
```bash
# Check quality/status of entire batch
GET /api/barcode-tracking/by-batch/45

# Find defective units in batch
GET /api/barcode-tracking/by-batch/45?status=defective
```

---

### 3. View Sales by Date Range

View all barcodes sold within a specific date range with order details.

**Endpoint:** `GET /api/barcode-tracking/sales`

**Query Parameters:**
- `from_date` (date, required) - Start date (YYYY-MM-DD)
- `to_date` (date, required) - End date (YYYY-MM-DD)
- `store_id` (integer, optional) - Filter by specific store
- `product_id` (integer, optional) - Filter by specific product
- `per_page` (integer, optional) - Items per page (default: 50)

**Response:**

```json
{
  "success": true,
  "data": {
    "date_range": {
      "from": "2024-01-01",
      "to": "2024-01-31"
    },
    "filters": {
      "store_id": null,
      "product_id": null
    },
    "summary": {
      "total_sales": 245,
      "unique_products_sold": 45,
      "daily_average": 7.9
    },
    "sales_by_date": [
      {
        "date": "2024-01-01",
        "count": 12
      },
      {
        "date": "2024-01-02",
        "count": 8
      }
      // ... more dates
    ],
    "sales": [
      {
        "id": 5001,
        "sale_date": "2024-01-15T14:30:00Z",
        "barcode": {
          "id": 1001,
          "barcode": "SAR2024001001",
          "product": {
            "id": 123,
            "name": "Designer Saree Collection 2024",
            "sku": "SAREE-2024-001"
          }
        },
        "store": {
          "id": 1,
          "name": "Main Showroom"
        },
        "sold_by": {
          "id": 10,
          "name": "John Doe"
        },
        "reference_type": "order",
        "reference_id": 1234,
        "notes": null
      }
      // ... more sales
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 245,
      "last_page": 5
    }
  }
}
```

**Use Case Example:**
```bash
# Monthly sales report
GET /api/barcode-tracking/sales?from_date=2024-01-01&to_date=2024-01-31

# Store-specific sales for product
GET /api/barcode-tracking/sales?from_date=2024-01-01&to_date=2024-01-31&store_id=1&product_id=123

# Weekly sales performance
GET /api/barcode-tracking/sales?from_date=2024-01-15&to_date=2024-01-21
```

---

### 4. Compare Multiple Stores

Compare barcode distribution and status across multiple stores.

**Endpoint:** `POST /api/barcode-tracking/compare-stores`

**Request Body:**
```json
{
  "store_ids": [1, 2, 3],
  "product_id": 123,  // optional
  "status": "in_shop"  // optional
}
```

**Body Parameters:**
- `store_ids` (array, required) - Array of store IDs to compare (minimum 2)
- `product_id` (integer, optional) - Filter by specific product
- `status` (string, optional) - Filter by specific status

**Response:**

```json
{
  "success": true,
  "data": {
    "filters": {
      "store_ids": [1, 2, 3],
      "product_id": 123,
      "status": null
    },
    "total_barcodes": 250,
    "store_comparison": [
      {
        "store": {
          "id": 1,
          "name": "Main Showroom",
          "type": "retail"
        },
        "summary": {
          "total_units": 120,
          "available_for_sale": 100,
          "on_display": 40,
          "in_warehouse": 60
        },
        "status_breakdown": [
          {
            "status": "in_shop",
            "count": 80
          },
          {
            "status": "on_display",
            "count": 40
          }
        ],
        "product_breakdown": [
          {
            "product_id": 123,
            "product_name": "Designer Saree Collection 2024",
            "count": 120
          }
        ]
      },
      {
        "store": {
          "id": 2,
          "name": "Mall Branch",
          "type": "retail"
        },
        "summary": {
          "total_units": 80,
          "available_for_sale": 70,
          "on_display": 30,
          "in_warehouse": 40
        },
        "status_breakdown": [
          {
            "status": "in_shop",
            "count": 50
          },
          {
            "status": "on_display",
            "count": 30
          }
        ],
        "product_breakdown": [
          {
            "product_id": 123,
            "product_name": "Designer Saree Collection 2024",
            "count": 80
          }
        ]
      }
      // ... more stores
    ]
  }
}
```

**Use Case Example:**
```bash
# Compare inventory levels across all showrooms
POST /api/barcode-tracking/compare-stores
{
  "store_ids": [1, 2, 3, 4]
}

# Compare availability of specific product across stores
POST /api/barcode-tracking/compare-stores
{
  "store_ids": [1, 2, 3],
  "product_id": 123,
  "status": "on_display"
}
```

---

### 5. View Recently Added Barcodes

View barcodes added within the last X days.

**Endpoint:** `GET /api/barcode-tracking/recent`

**Query Parameters:**
- `days` (integer, optional) - Number of days to look back (default: 7)
- `store_id` (integer, optional) - Filter by specific store
- `product_id` (integer, optional) - Filter by specific product
- `per_page` (integer, optional) - Items per page (default: 50)

**Response:**

```json
{
  "success": true,
  "data": {
    "period": {
      "days": 7,
      "since": "2024-01-08T00:00:00Z"
    },
    "filters": {
      "store_id": null,
      "product_id": null
    },
    "summary": {
      "total_new_barcodes": 85,
      "daily_average": 12.14
    },
    "by_date": [
      {
        "date": "2024-01-15",
        "count": 15
      },
      {
        "date": "2024-01-14",
        "count": 12
      }
      // ... more dates
    ],
    "barcodes": [
      {
        "id": 2001,
        "barcode": "SAR2024002001",
        "product": {
          "id": 125,
          "name": "New Collection Item",
          "sku": "SAREE-2024-002"
        },
        "batch": {
          "id": 50,
          "batch_number": "BATCH-2024-002"
        },
        "current_store": {
          "id": 1,
          "name": "Main Showroom"
        },
        "current_status": "in_warehouse",
        "created_at": "2024-01-15T09:30:00Z"
      }
      // ... more barcodes
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 85,
      "last_page": 2
    }
  }
}
```

**Use Case Example:**
```bash
# View all new stock in last week
GET /api/barcode-tracking/recent?days=7

# Check recent arrivals at specific store
GET /api/barcode-tracking/recent?days=3&store_id=1

# Monitor new units of specific product
GET /api/barcode-tracking/recent?days=30&product_id=123
```

---

## Use Cases

### 1. Product Inventory Management

**Scenario:** Manager wants to see where all units of a specific product are located

```javascript
// Get complete product view
const response = await fetch('/api/barcode-tracking/by-product/123', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();

console.log(`Total units: ${data.data.summary.total_units}`);
console.log(`Available for sale: ${data.data.summary.available_for_sale}`);
console.log(`Store distribution:`, data.data.store_distribution);
```

### 2. Batch Quality Control

**Scenario:** QC team needs to check status of entire production batch

```javascript
// Check batch status
const response = await fetch('/api/barcode-tracking/by-batch/45', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();

if (data.data.summary.defective > 0) {
  console.log(`Warning: ${data.data.summary.defective} defective units in batch`);
}

// Get detailed view of defective items
const defectiveResponse = await fetch('/api/barcode-tracking/by-batch/45?status=defective', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

### 3. Sales Performance Analysis

**Scenario:** Generate monthly sales report by product and store

```javascript
// Get January sales data
const response = await fetch(
  '/api/barcode-tracking/sales?from_date=2024-01-01&to_date=2024-01-31&store_id=1',
  {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }
);

const data = await response.json();

console.log(`Total sales: ${data.data.summary.total_sales}`);
console.log(`Daily average: ${data.data.summary.daily_average}`);
console.log(`Products sold: ${data.data.summary.unique_products_sold}`);

// Visualize sales_by_date for charts
data.data.sales_by_date.forEach(day => {
  console.log(`${day.date}: ${day.count} units`);
});
```

### 4. Multi-Store Comparison

**Scenario:** Compare inventory levels across multiple showrooms

```javascript
// Compare 3 stores
const response = await fetch('/api/barcode-tracking/compare-stores', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    store_ids: [1, 2, 3],
    product_id: 123
  })
});

const data = await response.json();

data.data.store_comparison.forEach(store => {
  console.log(`${store.store.name}:`);
  console.log(`  Total: ${store.summary.total_units}`);
  console.log(`  Available: ${store.summary.available_for_sale}`);
  console.log(`  On Display: ${store.summary.on_display}`);
});
```

### 5. New Stock Monitoring

**Scenario:** Track recently received inventory

```javascript
// Check last 3 days of arrivals
const response = await fetch('/api/barcode-tracking/recent?days=3', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();

console.log(`New arrivals: ${data.data.summary.total_new_barcodes}`);
console.log(`Daily average: ${data.data.summary.daily_average}`);

// Group by date
data.data.by_date.forEach(day => {
  console.log(`${day.date}: ${day.count} new units`);
});
```

---

## Integration Examples

### React Component - Product Inventory View

```javascript
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const ProductInventoryView = ({ productId }) => {
  const [data, setData] = useState(null);
  const [filters, setFilters] = useState({
    status: '',
    store_id: '',
    available_only: false
  });

  useEffect(() => {
    fetchProductBarcodes();
  }, [productId, filters]);

  const fetchProductBarcodes = async () => {
    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.store_id) params.append('store_id', filters.store_id);
    if (filters.available_only) params.append('available_only', 'true');

    const response = await axios.get(
      `/api/barcode-tracking/by-product/${productId}?${params}`,
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    setData(response.data.data);
  };

  if (!data) return <div>Loading...</div>;

  return (
    <div className="product-inventory">
      <h2>{data.product.name}</h2>
      
      <div className="summary-cards">
        <div className="card">
          <h3>Total Units</h3>
          <p>{data.summary.total_units}</p>
        </div>
        <div className="card">
          <h3>Available</h3>
          <p>{data.summary.available_for_sale}</p>
        </div>
        <div className="card">
          <h3>Sold</h3>
          <p>{data.summary.sold}</p>
        </div>
      </div>

      <div className="store-distribution">
        <h3>Store Distribution</h3>
        {data.store_distribution.map(store => (
          <div key={store.store_id} className="store-item">
            <span>{store.store_name}</span>
            <span>{store.count} units ({store.available} available)</span>
          </div>
        ))}
      </div>

      <div className="filters">
        <select 
          value={filters.status} 
          onChange={e => setFilters({...filters, status: e.target.value})}
        >
          <option value="">All Statuses</option>
          <option value="in_warehouse">In Warehouse</option>
          <option value="in_shop">In Shop</option>
          <option value="on_display">On Display</option>
        </select>
      </div>

      <div className="barcode-list">
        {data.barcodes.map(barcode => (
          <div key={barcode.id} className="barcode-item">
            <span className="barcode">{barcode.barcode}</span>
            <span className="status">{barcode.status_label}</span>
            <span className="store">{barcode.current_store?.name}</span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ProductInventoryView;
```

### Sales Dashboard Component

```javascript
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

const SalesDashboard = () => {
  const [salesData, setSalesData] = useState(null);
  const [dateRange, setDateRange] = useState({
    from_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    to_date: new Date().toISOString().split('T')[0]
  });

  useEffect(() => {
    fetchSalesData();
  }, [dateRange]);

  const fetchSalesData = async () => {
    const response = await axios.get('/api/barcode-tracking/sales', {
      params: dateRange,
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });

    setSalesData(response.data.data);
  };

  if (!salesData) return <div>Loading...</div>;

  return (
    <div className="sales-dashboard">
      <h2>Sales Report</h2>
      
      <div className="summary">
        <div className="stat">
          <label>Total Sales</label>
          <span>{salesData.summary.total_sales}</span>
        </div>
        <div className="stat">
          <label>Daily Average</label>
          <span>{salesData.summary.daily_average}</span>
        </div>
        <div className="stat">
          <label>Products Sold</label>
          <span>{salesData.summary.unique_products_sold}</span>
        </div>
      </div>

      <div className="chart">
        <h3>Sales Trend</h3>
        <LineChart width={800} height={300} data={salesData.sales_by_date}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="date" />
          <YAxis />
          <Tooltip />
          <Line type="monotone" dataKey="count" stroke="#8884d8" />
        </LineChart>
      </div>

      <div className="sales-list">
        <h3>Recent Sales</h3>
        {salesData.sales.map(sale => (
          <div key={sale.id} className="sale-item">
            <span>{sale.barcode.barcode}</span>
            <span>{sale.barcode.product.name}</span>
            <span>{sale.store.name}</span>
            <span>{new Date(sale.sale_date).toLocaleDateString()}</span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SalesDashboard;
```

---

## Best Practices

1. **Use Appropriate Endpoints**
   - Use specialized endpoints for focused views
   - Use advanced search for complex multi-criteria filtering
   - Combine endpoints for comprehensive dashboards

2. **Optimize Performance**
   - Use pagination for large result sets
   - Apply filters to reduce data transfer
   - Cache frequently accessed data

3. **Handle Errors**
   - Check for 404 responses (product/batch not found)
   - Validate date ranges before submitting
   - Handle network errors gracefully

4. **Date Range Queries**
   - Keep date ranges reasonable (< 90 days recommended)
   - Use date format: YYYY-MM-DD
   - Ensure to_date >= from_date

5. **Store Comparison**
   - Limit to 2-10 stores for best performance
   - Use product_id filter when comparing specific items
   - Consider caching comparison results

---

## Error Responses

### Product Not Found
```json
{
  "success": false,
  "message": "Product not found"
}
```
**HTTP Status:** 404

### Batch Not Found
```json
{
  "success": false,
  "message": "Batch not found"
}
```
**HTTP Status:** 404

### Invalid Date Range
```json
{
  "success": false,
  "errors": {
    "to_date": ["The to date must be a date after or equal to from date."]
  }
}
```
**HTTP Status:** 422

### Invalid Store IDs
```json
{
  "success": false,
  "errors": {
    "store_ids": ["The store ids field is required and must contain at least 2 items."],
    "store_ids.0": ["The selected store id is invalid."]
  }
}
```
**HTTP Status:** 422

---

## Next Steps

1. **Run Migration**: `php artisan migrate` to apply database schema
2. **Test Endpoints**: Use Postman or similar to test each endpoint
3. **Integrate Frontend**: Build UI components using the integration examples
4. **Monitor Performance**: Track query times and optimize as needed
5. **Add Indexes**: Monitor slow queries and add indexes if needed

For more information, see:
- [BARCODE_LOCATION_TRACKING_SYSTEM.md](./BARCODE_LOCATION_TRACKING_SYSTEM.md) - System architecture
- [BARCODE_TRACKING_API.md](./BARCODE_TRACKING_API.md) - Core API documentation
