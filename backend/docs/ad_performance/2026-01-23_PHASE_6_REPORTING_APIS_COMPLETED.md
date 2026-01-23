# Phase 6: Reporting APIs - COMPLETED

**Date:** January 23, 2026  
**Status:** ✅ COMPLETED  
**Files Created:** 1 controller, 5 endpoints registered  
**Frontend Documentation:** Complete API reference below

---

## Overview

Reporting APIs provide comprehensive analytics and performance metrics for ad campaigns. These endpoints enable data-driven decision making by surfacing revenue attribution, campaign comparisons, product performance, and system health metrics.

---

## Authentication

All endpoints require JWT authentication:

```
Authorization: Bearer <your_jwt_token>
```

Middleware: `auth:api`

---

## API Endpoints

Base URL: `/api/ad-campaigns`

### 1. Campaign Leaderboard

**Endpoint:** `GET /api/ad-campaigns/reports/leaderboard`

**Description:** Compare all campaigns' performance in a ranked list. Sort by revenue, profit, units, or order count.

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from | date | Yes | Start date (YYYY-MM-DD) |
| to | date | Yes | End date (YYYY-MM-DD, must be >= from) |
| mode | string | Yes | Credit mode: FULL or SPLIT |
| sort | string | No | Sort by: revenue, profit, units, orders (default: revenue) |
| platform | string | No | Filter by platform: facebook, instagram, google, tiktok, youtube, other |

**Response:**

```json
{
  "success": true,
  "mode": "SPLIT",
  "date_range": {
    "from": "2026-01-01",
    "to": "2026-01-23"
  },
  "data": [
    {
      "campaign_id": 1,
      "campaign_name": "Spring Sale 2026",
      "platform": "facebook",
      "status": "RUNNING",
      "units": 245.5,
      "revenue": 24550.00,
      "profit": 12275.00,
      "order_count": 87,
      "avg_order_value": 282.18
    },
    {
      "campaign_id": 3,
      "campaign_name": "Instagram Launch",
      "platform": "instagram",
      "status": "RUNNING",
      "units": 189.0,
      "revenue": 18900.00,
      "profit": 9450.00,
      "order_count": 62,
      "avg_order_value": 304.84
    },
    {
      "campaign_id": 2,
      "campaign_name": "Google Ads Q1",
      "platform": "google",
      "status": "PAUSED",
      "units": 120.0,
      "revenue": 12000.00,
      "profit": 6000.00,
      "order_count": 45,
      "avg_order_value": 266.67
    }
  ]
}
```

**Key Metrics Explained:**

- **units**: Total quantity credited to the campaign (can be fractional in SPLIT mode)
- **revenue**: Total sales revenue credited (excluding costs)
- **profit**: Total profit credited (revenue - costs)
- **order_count**: Number of unique orders credited
- **avg_order_value**: Average revenue per order

**Use Cases:**

- Dashboard top performers widget
- Campaign comparison reports
- Platform effectiveness analysis
- Budget allocation decisions

---

### 2. Campaign Summary

**Endpoint:** `GET /api/ad-campaigns/{id}/reports/summary`

**Description:** Get detailed performance metrics for a specific campaign.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Campaign ID |

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from | date | Yes | Start date (YYYY-MM-DD) |
| to | date | Yes | End date (YYYY-MM-DD) |
| mode | string | Yes | Credit mode: FULL or SPLIT |

**Response:**

```json
{
  "success": true,
  "data": {
    "campaign": {
      "id": 1,
      "name": "Spring Sale 2026",
      "platform": "facebook",
      "status": "RUNNING",
      "starts_at": "2026-01-20T00:00:00.000000Z",
      "ends_at": "2026-02-20T00:00:00.000000Z",
      "budget_type": "DAILY",
      "budget_amount": "100.00",
      "notes": "Q1 campaign targeting new customers",
      "created_at": "2026-01-23T10:00:00.000000Z"
    },
    "metrics": {
      "units": 245.5,
      "revenue": 24550.00,
      "profit": 12275.00,
      "order_count": 87,
      "item_count": 102,
      "avg_order_value": 282.18
    },
    "mode": "SPLIT",
    "date_range": {
      "from": "2026-01-01",
      "to": "2026-01-23"
    }
  }
}
```

**Metrics Definitions:**

- **item_count**: Number of order line items credited (can be > order_count if orders have multiple items)
- All other metrics same as leaderboard

**Error Response (404):**

```json
{
  "success": false,
  "message": "Campaign not found"
}
```

**Use Cases:**

- Campaign detail page header
- Performance summary cards
- Budget vs. actual tracking
- ROI calculations

---

### 3. Product Breakdown

**Endpoint:** `GET /api/ad-campaigns/{id}/reports/products`

**Description:** See which products performed best within a campaign.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Campaign ID |

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from | date | Yes | Start date |
| to | date | Yes | End date |
| mode | string | Yes | FULL or SPLIT |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "product_id": 10,
      "product_name": "Premium Widget",
      "product_sku": "WDG-001",
      "total_units": 85.5,
      "total_revenue": 8550.00,
      "total_profit": 4275.00,
      "order_count": 32
    },
    {
      "product_id": 15,
      "product_name": "Deluxe Widget",
      "product_sku": "WDG-002",
      "total_units": 60.0,
      "total_revenue": 9000.00,
      "total_profit": 4500.00,
      "order_count": 28
    },
    {
      "product_id": 20,
      "product_name": "Basic Widget",
      "product_sku": "WDG-003",
      "total_units": 100.0,
      "total_revenue": 7000.00,
      "total_profit": 3500.00,
      "order_count": 27
    }
  ]
}
```

**Sorted By:** Revenue (descending)

**Use Cases:**

- Identify best-performing products
- Optimize product targeting
- Inventory planning
- Product mix analysis
- Remove underperforming products

---

### 4. Orders List

**Endpoint:** `GET /api/ad-campaigns/{id}/reports/orders`

**Description:** List all orders that were credited to a campaign with full order details.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Campaign ID |

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from | date | Yes | Start date |
| to | date | Yes | End date |
| mode | string | Yes | FULL or SPLIT |
| per_page | integer | No | Items per page (default: 20) |
| page | integer | No | Page number |

**Response:**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_id": 1523,
        "order_item_id": 2847,
        "campaign_id": 1,
        "sale_time": "2026-01-22T14:30:00.000000Z",
        "credit_mode": "SPLIT",
        "credited_qty": 1.5,
        "credited_revenue": 150.00,
        "credited_profit": 75.00,
        "is_reversed": false,
        "reversed_at": null,
        "matched_campaigns_count": 2,
        "created_at": "2026-01-22T14:32:00.000000Z",
        "order": {
          "id": 1523,
          "order_number": "ORD-2026-01523",
          "order_date": "2026-01-22T14:30:00.000000Z",
          "status": "delivered",
          "total_amount": 300.00,
          "customer": {
            "id": 45,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1234567890"
          },
          "store": {
            "id": 2,
            "name": "Downtown Store",
            "code": "DS-01"
          }
        },
        "order_item": {
          "id": 2847,
          "quantity": 3,
          "unit_price": 100.00,
          "discount_amount": 0.00,
          "product": {
            "id": 10,
            "name": "Premium Widget",
            "sku": "WDG-001",
            "price": 100.00
          }
        }
      }
    ],
    "first_page_url": "http://api.example.com/api/ad-campaigns/1/reports/orders?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://api.example.com/api/ad-campaigns/1/reports/orders?page=5",
    "next_page_url": "http://api.example.com/api/ad-campaigns/1/reports/orders?page=2",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 87
  }
}
```

**Notes:**

- **matched_campaigns_count**: Shows how many campaigns matched this order item (campaign overlap)
- **credited_qty/revenue/profit**: The portion credited to this campaign (fractional in SPLIT mode)
- **is_reversed**: True if order was cancelled/refunded

**Use Cases:**

- Order-level attribution audit
- Customer behavior analysis
- Dispute resolution
- Order details drill-down
- Export credited orders

---

### 5. Attribution Health

**Endpoint:** `GET /api/ad-campaigns/reports/health`

**Description:** System health metrics showing attribution coverage and campaign overlap.

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from | date | Yes | Start date |
| to | date | Yes | End date |

**Response:**

```json
{
  "success": true,
  "data": {
    "date_range": {
      "from": "2026-01-01",
      "to": "2026-01-23"
    },
    "items": {
      "total": 1250,
      "attributed": 987,
      "unattributed": 263,
      "attribution_rate": 78.96
    },
    "revenue": {
      "total": 125000.00,
      "attributed": 98750.00,
      "unattributed": 26250.00,
      "attribution_rate": 79.00
    },
    "overlap": {
      "avg_campaigns_per_item": 1.85,
      "distribution": [
        {
          "campaign_count": 1,
          "item_count": 520
        },
        {
          "campaign_count": 2,
          "item_count": 387
        },
        {
          "campaign_count": 3,
          "item_count": 80
        }
      ]
    }
  }
}
```

**Metric Definitions:**

**Items:**
- **total**: All order items in date range with countable status (confirmed, processing, shipped, delivered)
- **attributed**: Items that matched at least one campaign
- **unattributed**: Items with no campaign match
- **attribution_rate**: Percentage of items attributed

**Revenue:**
- **total**: Total revenue from all countable orders
- **attributed**: Revenue credited to campaigns (SPLIT mode to avoid double counting)
- **unattributed**: Revenue not attributed to any campaign
- **attribution_rate**: Percentage of revenue attributed

**Overlap:**
- **avg_campaigns_per_item**: Average number of campaigns matched per item
- **distribution**: Breakdown showing how many items matched 1, 2, 3+ campaigns

**Health Indicators:**

| Attribution Rate | Status | Action |
|------------------|--------|--------|
| 90-100% | Excellent | Maintain current targeting |
| 70-89% | Good | Review unattributed products |
| 50-69% | Fair | Expand campaign product targeting |
| < 50% | Poor | Urgent review needed |

**Use Cases:**

- System health monitoring
- Campaign coverage analysis
- Identify targeting gaps
- Attribution strategy optimization
- Executive dashboards

---

## Credit Modes Explained

### FULL Mode

**Behavior:** Each campaign gets 100% credit for the sale.

**Example:**
- Product A sells: 1 unit, $100 revenue
- 3 campaigns target Product A
- **Result:** Each campaign gets 1 unit, $100 revenue

**Total Credits:** 3 units, $300 revenue

**Use Cases:**
- Campaign impact analysis (how much each campaign influences)
- Marketing attribution (give full credit to each touchpoint)
- Overlap analysis (totals will exceed actual sales)

### SPLIT Mode

**Behavior:** Credit is divided equally among matching campaigns.

**Example:**
- Product A sells: 1 unit, $100 revenue
- 3 campaigns target Product A
- **Result:** Each campaign gets 0.33 units, $33.33 revenue

**Total Credits:** 1 unit, $100 revenue

**Use Cases:**
- Financial reporting (accurate totals)
- Budget allocation (fair distribution)
- ROI calculations (precise metrics)
- Performance comparison (normalized values)

### Which Mode to Use?

**Frontend Recommendation:**

1. **Default to SPLIT mode** for most reports (accurate totals)
2. **Offer mode toggle** for power users
3. **Show warning** when FULL mode is selected: "Totals will exceed actual sales due to campaign overlap"
4. **Use SPLIT for health metrics** to avoid inflated numbers

---

## Response Patterns

### Success Response

```json
{
  "success": true,
  "data": { ... }
}
```

### Validation Error (422)

```json
{
  "success": false,
  "errors": {
    "from": ["The from field is required."],
    "mode": ["The selected mode is invalid."]
  }
}
```

### Not Found (404)

```json
{
  "success": false,
  "message": "Campaign not found"
}
```

---

## Frontend Integration Tips

### Date Range Picker

Provide preset ranges for better UX:

```javascript
const presets = [
  { label: 'Today', from: today, to: today },
  { label: 'Yesterday', from: yesterday, to: yesterday },
  { label: 'Last 7 Days', from: sevenDaysAgo, to: today },
  { label: 'Last 30 Days', from: thirtyDaysAgo, to: today },
  { label: 'This Month', from: startOfMonth, to: today },
  { label: 'Last Month', from: startOfLastMonth, to: endOfLastMonth },
  { label: 'This Quarter', from: startOfQuarter, to: today },
  { label: 'Custom', from: null, to: null }
];
```

### Credit Mode Selector

```javascript
const modes = [
  { 
    value: 'SPLIT', 
    label: 'Split Credit', 
    description: 'Divide credit among campaigns (accurate totals)' 
  },
  { 
    value: 'FULL', 
    label: 'Full Credit', 
    description: 'Each campaign gets 100% credit (shows impact)' 
  }
];
```

### Loading States

All endpoints may take 2-5 seconds for large date ranges:

```javascript
const [loading, setLoading] = useState(false);
const [data, setData] = useState(null);

async function fetchLeaderboard(params) {
  setLoading(true);
  try {
    const response = await api.get('/ad-campaigns/reports/leaderboard', { params });
    setData(response.data.data);
  } catch (error) {
    handleError(error);
  } finally {
    setLoading(false);
  }
}
```

### Caching Strategy

Reports data can be cached for 5-15 minutes:

```javascript
const cacheKey = `leaderboard_${from}_${to}_${mode}`;
const cached = localStorage.getItem(cacheKey);

if (cached && Date.now() - cached.timestamp < 5 * 60 * 1000) {
  return JSON.parse(cached.data);
}
```

### Chart Visualization Examples

**Campaign Leaderboard → Bar Chart:**
```javascript
const chartData = {
  labels: leaderboard.map(c => c.campaign_name),
  datasets: [{
    label: 'Revenue',
    data: leaderboard.map(c => c.revenue),
    backgroundColor: 'rgba(54, 162, 235, 0.5)'
  }]
};
```

**Product Breakdown → Pie Chart:**
```javascript
const chartData = {
  labels: products.map(p => p.product_name),
  datasets: [{
    data: products.map(p => p.total_revenue),
    backgroundColor: generateColors(products.length)
  }]
};
```

**Attribution Health → Gauge:**
```javascript
const attributionRate = healthData.items.attribution_rate;
const color = attributionRate >= 90 ? 'green' 
            : attributionRate >= 70 ? 'yellow' 
            : 'red';
```

---

## Performance Considerations

### Query Performance

- **Indexed fields:** `sale_time`, `campaign_id`, `credit_mode`, `is_reversed`
- **Typical response time:** 100-500ms for 30-day range
- **Large datasets:** Use pagination (orders list endpoint)

### Optimization Tips

1. **Narrow date ranges** for faster queries
2. **Cache frequently accessed reports**
3. **Use SPLIT mode** for health metrics (faster than FULL)
4. **Paginate** order lists instead of fetching all

---

## Common Use Cases

### Dashboard Widget

```javascript
// Top 5 campaigns this month
GET /api/ad-campaigns/reports/leaderboard?from=2026-01-01&to=2026-01-31&mode=SPLIT&sort=revenue
```

### Campaign Detail Page

```javascript
// Campaign overview + product breakdown
Promise.all([
  api.get(`/ad-campaigns/${id}/reports/summary`, { params }),
  api.get(`/ad-campaigns/${id}/reports/products`, { params })
]);
```

### System Health Monitor

```javascript
// Weekly health check
GET /api/ad-campaigns/reports/health?from=2026-01-16&to=2026-01-23
```

### Export Credited Orders

```javascript
// Fetch all pages for CSV export
async function exportOrders(campaignId, params) {
  let page = 1;
  let allOrders = [];
  
  while (true) {
    const response = await api.get(`/ad-campaigns/${campaignId}/reports/orders`, {
      params: { ...params, page, per_page: 100 }
    });
    
    allOrders.push(...response.data.data.data);
    
    if (page >= response.data.data.last_page) break;
    page++;
  }
  
  return convertToCSV(allOrders);
}
```

---

## Testing Checklist

- [ ] Leaderboard with all sort options (revenue, profit, units, orders)
- [ ] Leaderboard with platform filter
- [ ] Leaderboard with FULL vs SPLIT mode comparison
- [ ] Campaign summary for existing campaign
- [ ] Campaign summary for non-existent campaign (404)
- [ ] Product breakdown sorted by revenue
- [ ] Orders list with pagination
- [ ] Orders list showing reversed orders
- [ ] Attribution health with various date ranges
- [ ] Attribution health showing unattributed items/revenue
- [ ] Handle date validation errors (to < from)
- [ ] Handle invalid mode parameter
- [ ] Test with campaigns that have no sales (empty results)
- [ ] Test with date range having no orders (zero metrics)

---

## Related Documentation

- **Phase 1:** Database Foundation
- **Phase 2:** Models & Relationships
- **Phase 3:** Attribution Engine
- **Phase 4:** Event Automation
- **Phase 5:** Campaign Management APIs
- **Phase 7:** Admin Utilities (coming soon)

---

## Contact

For questions about these APIs, contact the backend team or refer to the complete implementation plan.

**Implementation Date:** January 23, 2026  
**Controller:** `app/Http/Controllers/AdCampaignReportController.php`  
**Routes:** `routes/api.php` (reporting section)  
**Total Endpoints:** 5  
**Aggregate Queries:** All endpoints use optimized GROUP BY with indexes
