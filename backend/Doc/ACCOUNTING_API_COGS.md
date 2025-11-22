# Accounting & Financial Metrics API Guide

**Last Updated:** November 22, 2025  
**Version:** 1.0  
**For Frontend Developers**

---

## Table of Contents

1. [Overview](#overview)
2. [Cost of Goods Sold (COGS)](#cost-of-goods-sold-cogs)
3. [Gross Margin & Profit Metrics](#gross-margin--profit-metrics)
4. [Order Financial Data](#order-financial-data)
5. [Dashboard Financial Metrics](#dashboard-financial-metrics)
6. [Expenses & Operating Costs](#expenses--operating-costs)
7. [Accounts Receivable & Payable](#accounts-receivable--payable)
8. [Reports & Analytics](#reports--analytics)
9. [Common Frontend Use Cases](#common-frontend-use-cases)
10. [Troubleshooting](#troubleshooting)

---

## Overview

This guide covers all financial and accounting endpoints in the Deshio ERP system. The backend now tracks:

- **COGS (Cost of Goods Sold)** - stored per order item at time of sale
- **Gross Margin** - revenue minus COGS
- **Net Profit** - gross margin minus expenses
- **Accounts Receivable** - outstanding customer payments
- **Accounts Payable** - outstanding expense payments
- **Cash Position** - real-time AR - AP snapshot

All monetary values are returned as **formatted strings** (e.g., `"1234.56"`) with 2 decimal places.

---

## Cost of Goods Sold (COGS)

### What is COGS?

COGS represents the **actual cost** the business paid to acquire/produce the products sold. It's calculated using the `cost_price` from the `ProductBatch` at the time of sale.

### Where COGS is Stored

- **Database Table:** `order_items`
- **Column:** `cogs` (decimal, nullable)
- **When Set:** 
  - At order item creation (from `batch.cost_price * quantity`)
  - Updated at order completion/confirmation (authoritative)

### How COGS is Calculated

```
COGS = batch.cost_price × quantity_sold
```

For a single product sold:
- Batch cost price: $50.00
- Quantity sold: 3 units
- **COGS = $50.00 × 3 = $150.00**

### Important Notes

- COGS can vary for the **same product** if sold from different batches (different suppliers, dates, purchase prices)
- Historical orders (created before Nov 22, 2025) may have `null` COGS → backend falls back to current `batch.cost_price`
- For accurate financial reports, ensure orders are **completed** (status = `confirmed` or `completed`)

---

## Gross Margin & Profit Metrics

### Definitions

| Metric | Formula | Meaning |
|--------|---------|---------|
| **Revenue** | `order.total_amount` | Total customer payment (after discounts, with tax) |
| **COGS** | Sum of `order_items.cogs` | Cost to acquire products sold |
| **Gross Margin** | `Revenue - COGS` | Profit before expenses |
| **Gross Margin %** | `(Gross Margin / Revenue) × 100` | Profitability percentage |
| **Operating Expenses** | Sum of approved expenses | Salaries, rent, utilities, etc. |
| **Net Profit** | `Gross Margin - Expenses` | Bottom-line profit |
| **Net Profit %** | `(Net Profit / Revenue) × 100` | Overall business profitability |

### Example Calculation

```
Order Total (Revenue):     $1,000.00
COGS:                        $600.00
-----------------------------------
Gross Margin:                $400.00
Gross Margin %:               40.00%

Operating Expenses:          $150.00
-----------------------------------
Net Profit:                  $250.00
Net Profit %:                 25.00%
```

---

## Order Financial Data

### Get Single Order Details

**Endpoint:** `GET /api/orders/{id}`

**Response Includes:**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_number": "ORD-20251122-001",
    "order_type": "counter",
    "status": "confirmed",
    "payment_status": "paid",
    
    // Customer & Store info...
    
    // Financial Summary (Available in ALL responses - list & detail)
    "subtotal": "950.00",
    "tax_amount": "50.00",
    "discount_amount": "0.00",
    "shipping_amount": "0.00",
    "total_amount": "1000.00",
    "paid_amount": "1000.00",
    "outstanding_amount": "0.00",
    
    // NEW: COGS & Margin (Available in ALL responses)
    "total_cogs": "600.00",
    "gross_margin": "400.00",
    "gross_margin_percentage": "40.00",
    
    // Detailed Items (only in GET /orders/{id})
    "items": [
      {
        "id": 456,
        "product_id": 78,
        "product_name": "iPhone 15 Pro",
        "product_sku": "IPHONE15PRO-256-BLK",
        "quantity": 2,
        "unit_price": "475.00",
        "discount_amount": "0.00",
        "tax_amount": "50.00",
        "total_amount": "1000.00",
        
        // Item-level COGS
        "cogs": "600.00",              // $300 cost × 2 units
        "item_gross_margin": "400.00"  // $1000 revenue - $600 COGS
      }
    ],
    
    "payments": [ /* ... */ ],
    "notes": "Customer wants delivery tomorrow",
    "confirmed_at": "2025-11-22 14:30:00"
  }
}
```

### Get Order List

**Endpoint:** `GET /api/orders`

**Query Parameters:**
- `order_type`: `counter`, `social_commerce`, `ecommerce`
- `status`: `pending`, `confirmed`, `completed`, `cancelled`
- `payment_status`: `pending`, `paid`, `partially_paid`, `overdue`
- `store_id`: Filter by store
- `date_from` / `date_to`: Date range filter
- `per_page`: Items per page (default 20)

**Response:** Array of orders with **full financial summary** (including `total_cogs`, `gross_margin`, `gross_margin_percentage`)

---

## Dashboard Financial Metrics

### Today's Metrics

**Endpoint:** `GET /api/dashboard/today-metrics?store_id={id}`

Returns comprehensive financial snapshot for today.

**Response:**

```json
{
  "success": true,
  "data": {
    "date": "2025-11-22",
    
    // Sales Metrics
    "total_sales": 25000.00,
    "paid_sales": 22000.00,
    "order_count": 45,
    "average_order_value": 555.56,
    
    // COGS & Margins
    "cost_of_goods_sold": 15000.00,
    "gross_margin": 10000.00,
    "gross_margin_percentage": 40.00,
    
    // Expenses & Net Profit
    "total_expenses": 3500.00,
    "net_profit": 6500.00,
    "net_profit_percentage": 26.00,
    
    // Cash Position
    "cash_snapshot": {
      "accounts_receivable": 3000.00,    // Customers owe us
      "accounts_payable": 1200.00,       // We owe suppliers/vendors
      "net_position": 1800.00            // AR - AP
    }
  }
}
```

**Frontend Display Example:**

```jsx
// Dashboard Card - Today's Performance
<div className="financial-summary">
  <MetricCard 
    label="Total Sales"
    value={data.total_sales}
    format="currency"
  />
  <MetricCard 
    label="COGS"
    value={data.cost_of_goods_sold}
    format="currency"
    color="orange"
  />
  <MetricCard 
    label="Gross Margin"
    value={data.gross_margin}
    percentage={data.gross_margin_percentage}
    format="currency"
    color="green"
  />
  <MetricCard 
    label="Net Profit"
    value={data.net_profit}
    percentage={data.net_profit_percentage}
    format="currency"
    color={data.net_profit >= 0 ? 'green' : 'red'}
  />
</div>
```

### Last 30 Days Sales

**Endpoint:** `GET /api/dashboard/last-30-days-sales?store_id={id}`

Returns daily sales data for trend charts.

**Response:**

```json
{
  "success": true,
  "data": {
    "period": {
      "start_date": "2025-10-23",
      "end_date": "2025-11-22"
    },
    "total_sales": 450000.00,
    "total_orders": 890,
    "daily_sales": [
      {
        "date": "2025-10-23",
        "day_name": "Wed",
        "total_sales": 15000.00,
        "paid_amount": 14000.00,
        "order_count": 32
      },
      // ... 29 more days
    ]
  }
}
```

**Frontend Chart Example:**

```jsx
import { LineChart } from 'recharts';

<LineChart data={data.daily_sales}>
  <XAxis dataKey="day_name" />
  <YAxis />
  <Line type="monotone" dataKey="total_sales" stroke="#8884d8" />
  <Line type="monotone" dataKey="paid_amount" stroke="#82ca9d" />
</LineChart>
```

### Sales by Channel

**Endpoint:** `GET /api/dashboard/sales-by-channel?period=today&store_id={id}`

**Periods:** `today`, `week`, `month`, `year`

**Response:**

```json
{
  "success": true,
  "data": {
    "period": "today",
    "total_sales": 25000.00,
    "total_orders": 45,
    "channels": [
      {
        "channel": "counter",
        "channel_label": "Store/Counter",
        "total_sales": 15000.00,
        "paid_amount": 14500.00,
        "order_count": 30,
        "percentage": 60.00
      },
      {
        "channel": "ecommerce",
        "channel_label": "E-commerce",
        "total_sales": 7000.00,
        "paid_amount": 7000.00,
        "order_count": 10,
        "percentage": 28.00
      },
      {
        "channel": "social_commerce",
        "channel_label": "Social Commerce",
        "total_sales": 3000.00,
        "paid_amount": 2500.00,
        "order_count": 5,
        "percentage": 12.00
      }
    ]
  }
}
```

### Top Stores by Sales

**Endpoint:** `GET /api/dashboard/top-stores-by-sales?period=today&limit=10`

**Response:**

```json
{
  "success": true,
  "data": {
    "period": "today",
    "total_sales_all_stores": 50000.00,
    "top_stores": [
      {
        "rank": 1,
        "store_id": 1,
        "store_name": "Main Store - Dhaka",
        "store_location": "Dhaka, Dhaka",
        "store_type": "retail",
        "total_sales": 25000.00,
        "paid_amount": 23000.00,
        "order_count": 45,
        "average_order_value": 555.56,
        "contribution_percentage": 50.00
      }
      // ... more stores
    ]
  }
}
```

### Inventory Age by Value

**Endpoint:** `GET /api/dashboard/inventory-age-by-value?store_id={id}`

Categorizes inventory by age (fresh, aging, stale).

**Response:**

```json
{
  "success": true,
  "data": {
    "total_inventory_value": 125000.00,
    "total_quantity": 5420,
    "total_batches": 342,
    "age_categories": [
      {
        "label": "0-30 days",
        "age_range": "0-30 days",
        "inventory_value": 85000.00,
        "quantity": 3800,
        "batch_count": 210,
        "percentage_of_total": 68.00
      },
      {
        "label": "31-60 days",
        "inventory_value": 25000.00,
        "quantity": 1000,
        "batch_count": 80,
        "percentage_of_total": 20.00
      },
      {
        "label": "61-90 days",
        "inventory_value": 10000.00,
        "quantity": 420,
        "batch_count": 35,
        "percentage_of_total": 8.00
      },
      {
        "label": "90+ days",
        "inventory_value": 5000.00,
        "quantity": 200,
        "batch_count": 17,
        "percentage_of_total": 4.00
      }
    ]
  }
}
```

**Use Case:** Identify slow-moving or dead stock to run promotions/clearance sales.

---

## Expenses & Operating Costs

### Get Expenses

**Endpoint:** `GET /api/expenses`

**Query Parameters:**
- `store_id`: Filter by store
- `expense_category_id`: Filter by category
- `status`: `pending`, `approved`, `rejected`, `cancelled`
- `payment_status`: `pending`, `paid`, `partially_paid`
- `date_from` / `date_to`: Date range

**Response:**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 15,
        "expense_number": "EXP-20251122-001",
        "category": {
          "id": 3,
          "name": "Utilities",
          "type": "operating"
        },
        "store": {
          "id": 1,
          "name": "Main Store"
        },
        "amount": 1500.00,
        "tax_amount": 0.00,
        "total_amount": 1500.00,
        "paid_amount": 1500.00,
        "outstanding_amount": 0.00,
        "status": "approved",
        "payment_status": "paid",
        "expense_date": "2025-11-22",
        "description": "November electricity bill",
        "created_at": "2025-11-22 10:00:00"
      }
      // ... more expenses
    ],
    "total": 342
  }
}
```

### Expense Categories

Common expense types tracked:

- **Operating Expenses:**
  - Salaries & Wages
  - Rent
  - Utilities (electricity, water, internet)
  - Office Supplies
  - Marketing & Advertising
  
- **Administrative:**
  - Software Subscriptions
  - Insurance
  - Legal & Professional Fees
  
- **COGS-related:**
  - Shipping & Freight
  - Packaging Materials
  - Import Duties

**Get Categories:** `GET /api/expense-categories`

---

## Accounts Receivable & Payable

### Accounts Receivable (AR)

**What it is:** Money customers owe the business (unpaid or partially paid orders).

**Calculated from:**
- All orders with `payment_status != 'paid'`
- Sum of `outstanding_amount` per order

**Endpoint:** Included in `GET /api/dashboard/today-metrics`

**Raw Data:** `GET /api/orders?payment_status=partially_paid&payment_status=pending`

**Frontend Display:**

```jsx
// AR Aging Report
<div className="ar-summary">
  <h3>Accounts Receivable: ${accounts_receivable}</h3>
  <div className="aging-breakdown">
    <div>Current (0-30 days): $12,000</div>
    <div>31-60 days: $5,000</div>
    <div className="overdue">60+ days: $2,000</div>
  </div>
</div>
```

### Accounts Payable (AP)

**What it is:** Money the business owes to vendors/suppliers (unpaid expenses).

**Calculated from:**
- All expenses with `payment_status != 'paid'`
- Sum of `outstanding_amount` per expense

**Endpoint:** Included in `GET /api/dashboard/today-metrics`

**Raw Data:** `GET /api/expenses?payment_status=partially_paid&payment_status=pending`

---

## Reports & Analytics

### Sales Summary Report

**Endpoint:** `GET /api/reports/sales-summary`

**Query Parameters:**
- `start_date`: Required (YYYY-MM-DD)
- `end_date`: Required (YYYY-MM-DD)
- `store_id`: Optional
- `order_type`: Optional (counter, ecommerce, social_commerce)
- `group_by`: Optional (day, week, month)

**Response:**

```json
{
  "success": true,
  "data": {
    "period": {
      "start_date": "2025-11-01",
      "end_date": "2025-11-22"
    },
    "summary": {
      "total_orders": 890,
      "total_revenue": 450000.00,
      "total_cogs": 270000.00,
      "gross_margin": 180000.00,
      "gross_margin_percentage": 40.00,
      "average_order_value": 505.62,
      "total_items_sold": 3450
    },
    "by_channel": {
      "counter": {
        "orders": 540,
        "revenue": 270000.00,
        "cogs": 162000.00,
        "margin": 108000.00
      },
      "ecommerce": {
        "orders": 250,
        "revenue": 125000.00,
        "cogs": 75000.00,
        "margin": 50000.00
      },
      "social_commerce": {
        "orders": 100,
        "revenue": 55000.00,
        "cogs": 33000.00,
        "margin": 22000.00
      }
    }
  }
}
```

### Profit & Loss Report

**Endpoint:** `GET /api/reports/profit-loss`

**Query Parameters:**
- `start_date`: Required
- `end_date`: Required
- `store_id`: Optional

**Response:**

```json
{
  "success": true,
  "data": {
    "period": {
      "start_date": "2025-11-01",
      "end_date": "2025-11-30"
    },
    "income": {
      "gross_sales": 450000.00,
      "returns_refunds": -5000.00,
      "net_sales": 445000.00
    },
    "cost_of_goods_sold": 270000.00,
    "gross_profit": 175000.00,
    "gross_profit_margin": 39.33,
    
    "operating_expenses": {
      "salaries": 50000.00,
      "rent": 15000.00,
      "utilities": 5000.00,
      "marketing": 8000.00,
      "other": 7000.00,
      "total": 85000.00
    },
    
    "operating_income": 90000.00,
    "net_profit": 90000.00,
    "net_profit_margin": 20.22
  }
}
```

---

## Common Frontend Use Cases

### 1. Order Details Page - Financial Section

```jsx
function OrderFinancialSummary({ order }) {
  return (
    <div className="order-financials">
      <h3>Financial Summary</h3>
      
      <div className="revenue-section">
        <Row label="Subtotal" value={order.subtotal} />
        <Row label="Tax" value={order.tax_amount} />
        <Row label="Discount" value={`-${order.discount_amount}`} />
        <Row label="Shipping" value={order.shipping_amount} />
        <Divider />
        <Row label="Total Revenue" value={order.total_amount} bold />
      </div>
      
      <div className="cost-section">
        <Row label="Cost of Goods (COGS)" value={order.total_cogs} color="orange" />
        <Divider />
        <Row 
          label="Gross Margin" 
          value={order.gross_margin}
          percentage={order.gross_margin_percentage}
          color="green"
          bold
        />
      </div>
      
      <div className="payment-section">
        <Row label="Paid Amount" value={order.paid_amount} color="green" />
        <Row label="Outstanding" value={order.outstanding_amount} color="red" />
      </div>
    </div>
  );
}
```

### 2. Dashboard - Financial Overview Widget

```jsx
function FinancialDashboard() {
  const { data, loading } = useFetch('/api/dashboard/today-metrics');
  
  if (loading) return <Spinner />;
  
  return (
    <div className="dashboard-grid">
      {/* Row 1: Key Metrics */}
      <MetricCard
        title="Today's Sales"
        value={formatCurrency(data.total_sales)}
        subtitle={`${data.order_count} orders`}
        icon={<DollarIcon />}
      />
      
      <MetricCard
        title="COGS"
        value={formatCurrency(data.cost_of_goods_sold)}
        percentage={`${(data.cost_of_goods_sold / data.total_sales * 100).toFixed(1)}%`}
        icon={<CostIcon />}
        color="orange"
      />
      
      <MetricCard
        title="Gross Margin"
        value={formatCurrency(data.gross_margin)}
        percentage={`${data.gross_margin_percentage}%`}
        icon={<TrendingUpIcon />}
        color="green"
      />
      
      <MetricCard
        title="Net Profit"
        value={formatCurrency(data.net_profit)}
        percentage={`${data.net_profit_percentage}%`}
        icon={<ProfitIcon />}
        color={data.net_profit >= 0 ? 'green' : 'red'}
      />
      
      {/* Row 2: Cash Position */}
      <CashPositionWidget
        receivable={data.cash_snapshot.accounts_receivable}
        payable={data.cash_snapshot.accounts_payable}
        netPosition={data.cash_snapshot.net_position}
      />
    </div>
  );
}
```

### 3. Order List Table - Include Margin Column

```jsx
function OrderListTable({ orders }) {
  return (
    <Table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Total</th>
          <th>COGS</th>
          <th>Margin</th>
          <th>Margin %</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        {orders.map(order => (
          <tr key={order.id}>
            <td>{order.order_number}</td>
            <td>{order.customer.name}</td>
            <td>{formatDate(order.order_date)}</td>
            <td>{formatCurrency(order.total_amount)}</td>
            <td className="text-orange">{formatCurrency(order.total_cogs)}</td>
            <td className="text-green">{formatCurrency(order.gross_margin)}</td>
            <td>{order.gross_margin_percentage}%</td>
            <td><Badge status={order.status} /></td>
          </tr>
        ))}
      </tbody>
    </Table>
  );
}
```

### 4. Profit Margin Chart (by Product)

```jsx
function ProductMarginChart() {
  const { data } = useFetch('/api/dashboard/today-top-products?limit=10');
  
  // Calculate margin per product
  const chartData = data.top_products.map(product => ({
    name: product.product_name,
    revenue: parseFloat(product.total_revenue),
    // Fetch COGS from order items or compute dynamically
    cogs: parseFloat(product.total_cogs || 0),
    margin: parseFloat(product.total_revenue) - parseFloat(product.total_cogs || 0)
  }));
  
  return (
    <BarChart data={chartData}>
      <XAxis dataKey="name" />
      <YAxis />
      <Bar dataKey="revenue" fill="#8884d8" name="Revenue" />
      <Bar dataKey="cogs" fill="#ff7f0e" name="COGS" />
      <Bar dataKey="margin" fill="#2ca02c" name="Margin" />
    </BarChart>
  );
}
```

---

## Troubleshooting

### COGS Shows as "0.00" or Null

**Causes:**
1. Order was created before Nov 22, 2025 (migration date)
2. Product batch has no `cost_price` set
3. Order is still `pending` (COGS calculated on completion)

**Solutions:**
- For new orders: Ensure batch `cost_price` is set when creating product batches
- For historical orders: Backend falls back to current `batch.cost_price × quantity`
- Run backfill script if available (ask backend team)

### Gross Margin Doesn't Match Expected Values

**Check:**
1. Are discounts applied at order level or item level?
2. Is tax included in revenue calculation?
3. Are returns/refunds deducted from revenue?

**Formula Verification:**
```
Revenue = subtotal + tax - discount + shipping
COGS = sum of (item.cogs)
Gross Margin = Revenue - COGS
```

### Dashboard Shows No Data

**Common Issues:**
- Date filter not applied correctly
- Store filter restricting data too much
- No orders created today (check `order_date`)

**Debug:**
```javascript
// Check raw API response
fetch('/api/dashboard/today-metrics?store_id=1')
  .then(r => r.json())
  .then(console.log);
```

### Accounts Receivable/Payable Mismatch

**Ensure:**
- Only include orders with `status != 'cancelled'`
- Only include expenses with `status != 'cancelled'` and `status != 'rejected'`
- Use `outstanding_amount` field, not `total_amount - paid_amount` (backend handles this)

---

## Quick Reference: Key Endpoints

| Feature | Endpoint | Method | Auth |
|---------|----------|--------|------|
| Today's Metrics | `/api/dashboard/today-metrics` | GET | Required |
| Order Details | `/api/orders/{id}` | GET | Required |
| Order List | `/api/orders` | GET | Required |
| Last 30 Days Sales | `/api/dashboard/last-30-days-sales` | GET | Required |
| Sales by Channel | `/api/dashboard/sales-by-channel` | GET | Required |
| Top Stores | `/api/dashboard/top-stores-by-sales` | GET | Required |
| Inventory Age | `/api/dashboard/inventory-age-by-value` | GET | Required |
| Expenses | `/api/expenses` | GET | Required |
| Sales Report | `/api/reports/sales-summary` | GET | Required |
| Profit & Loss | `/api/reports/profit-loss` | GET | Required |

---

## Need Help?

**Backend Team Contact:**
- For data issues: Check with backend devs
- For missing COGS: Ensure product batches have `cost_price` set
- For calculation questions: Review this guide's formulas

**Common Formulas:**
```
Gross Margin = Revenue - COGS
Gross Margin % = (Gross Margin / Revenue) × 100
Net Profit = Gross Margin - Operating Expenses
Net Profit % = (Net Profit / Revenue) × 100
ROI = (Net Profit / COGS) × 100
```

---

**Document Version:** 1.0  
**Last Updated:** November 22, 2025  
**Maintained By:** Backend Team
