# Dashboard API Quick Reference

## 🚀 Quick Start

```javascript
const API_BASE = 'https://your-domain.com/api/dashboard';
const token = localStorage.getItem('jwt_token');

const headers = {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
};
```

---

## 📊 All Endpoints

### 1️⃣ Today's Metrics
```javascript
GET /api/dashboard/today-metrics?store_id={optional}

// Response: Total sales, profit, order count, cash position
```

### 2️⃣ Last 30 Days Sales
```javascript
GET /api/dashboard/last-30-days-sales?store_id={optional}

// Response: Array of 30 daily sales with dates
// Perfect for line/bar charts
```

### 3️⃣ Sales by Channel
```javascript
GET /api/dashboard/sales-by-channel?period={today|week|month|year}&store_id={optional}

// Response: Counter, E-commerce, Social Commerce breakdown
// Perfect for pie/donut charts
```

### 4️⃣ Top Stores
```javascript
GET /api/dashboard/top-stores?limit={10}&period={today|week|month|year}

// Response: Store rankings with sales data
// Perfect for horizontal bar charts
```

### 5️⃣ Today's Top Products
```javascript
GET /api/dashboard/today-top-products?limit={10}&store_id={optional}

// Response: Best selling products by revenue
// Perfect for product cards/lists
```

### 6️⃣ Slow Moving Products
```javascript
GET /api/dashboard/slow-moving-products?limit={10}&days={90}&store_id={optional}

// Response: Products with low turnover rate
// Perfect for alerts/action items
```

### 7️⃣ Low Stock & OOS
```javascript
GET /api/dashboard/low-stock-products?threshold={10}&store_id={optional}

// Response: Products needing restock
// Perfect for inventory alerts
```

### 8️⃣ Inventory Age
```javascript
GET /api/dashboard/inventory-age-by-value?store_id={optional}

// Response: Inventory categorized by age (0-30, 31-60, 61-90, 90+ days)
// Perfect for stacked bar charts
```

### 9️⃣ Operations Today
```javascript
GET /api/dashboard/operations-today?store_id={optional}

// Response: Order status pipeline and return rate
// Perfect for status badges and funnel charts
```

---

## 🎨 Frontend Components

### Dashboard Layout Example

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Dashboard = () => {
  const [todayMetrics, setTodayMetrics] = useState(null);
  const [salesTrend, setSalesTrend] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
    
    // Refresh every 5 minutes
    const interval = setInterval(fetchDashboardData, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [metrics, trend] = await Promise.all([
        axios.get('/api/dashboard/today-metrics'),
        axios.get('/api/dashboard/last-30-days-sales')
      ]);
      
      setTodayMetrics(metrics.data.data);
      setSalesTrend(trend.data.data);
      setLoading(false);
    } catch (error) {
      console.error('Dashboard error:', error);
    }
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="dashboard">
      {/* KPI Cards */}
      <div className="kpi-row">
        <KPICard 
          title="Today's Sales" 
          value={`৳${todayMetrics.total_sales.toLocaleString()}`}
          subtitle={`${todayMetrics.order_count} orders`}
        />
        <KPICard 
          title="Gross Margin" 
          value={`${todayMetrics.gross_margin_percentage}%`}
          subtitle={`৳${todayMetrics.gross_margin.toLocaleString()}`}
        />
        <KPICard 
          title="Net Profit" 
          value={`${todayMetrics.net_profit_percentage}%`}
          subtitle={`৳${todayMetrics.net_profit.toLocaleString()}`}
        />
        <KPICard 
          title="Cash Position" 
          value={`৳${todayMetrics.cash_snapshot.net_position.toLocaleString()}`}
          subtitle="AR - AP"
        />
      </div>

      {/* Sales Trend Chart */}
      <div className="chart-section">
        <SalesChart data={salesTrend.daily_sales} />
      </div>

      {/* More sections... */}
    </div>
  );
};
```

---

## 📈 Chart.js Examples

### Line Chart - Sales Trend

```javascript
const SalesChart = ({ data }) => {
  const chartData = {
    labels: data.map(d => d.date),
    datasets: [{
      label: 'Daily Sales',
      data: data.map(d => d.total_sales),
      borderColor: 'rgb(75, 192, 192)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.4
    }]
  };

  const options = {
    responsive: true,
    plugins: {
      legend: { display: true },
      tooltip: {
        callbacks: {
          label: (context) => `৳${context.parsed.y.toLocaleString()}`
        }
      }
    }
  };

  return <Line data={chartData} options={options} />;
};
```

### Pie Chart - Sales by Channel

```javascript
const ChannelChart = ({ data }) => {
  const chartData = {
    labels: data.channels.map(c => c.channel_label),
    datasets: [{
      data: data.channels.map(c => c.total_sales),
      backgroundColor: [
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 206, 86, 0.8)'
      ]
    }]
  };

  return <Pie data={chartData} />;
};
```

### Bar Chart - Top Stores

```javascript
const TopStoresChart = ({ data }) => {
  const chartData = {
    labels: data.top_stores.map(s => s.store_name),
    datasets: [{
      label: 'Sales',
      data: data.top_stores.map(s => s.total_sales),
      backgroundColor: 'rgba(54, 162, 235, 0.8)'
    }]
  };

  const options = {
    indexAxis: 'y', // Horizontal bar
    responsive: true
  };

  return <Bar data={chartData} options={options} />;
};
```

---

## 🎯 KPI Card Component

```jsx
const KPICard = ({ title, value, subtitle, trend, color = 'blue' }) => {
  return (
    <div className={`kpi-card bg-${color}-50 border-${color}-200`}>
      <div className="kpi-header">
        <h3 className="text-sm font-medium text-gray-600">{title}</h3>
        {trend && (
          <span className={`trend ${trend > 0 ? 'text-green-600' : 'text-red-600'}`}>
            {trend > 0 ? '↑' : '↓'} {Math.abs(trend)}%
          </span>
        )}
      </div>
      <div className="kpi-value text-3xl font-bold">{value}</div>
      <div className="kpi-subtitle text-sm text-gray-500">{subtitle}</div>
    </div>
  );
};

// Usage
<KPICard 
  title="Today's Sales" 
  value="৳45,698" 
  subtitle="127 orders"
  trend={12.5}
/>
```

---

## 🔔 Alert Components

### Low Stock Alert

```jsx
const LowStockAlert = () => {
  const [lowStock, setLowStock] = useState(null);

  useEffect(() => {
    fetchLowStock();
  }, []);

  const fetchLowStock = async () => {
    const { data } = await axios.get('/api/dashboard/low-stock-products');
    setLowStock(data.data);
  };

  if (!lowStock) return null;

  return (
    <div className="alert alert-warning">
      <h3>⚠️ Low Stock Alert</h3>
      <p>{lowStock.summary.out_of_stock_count} products out of stock</p>
      <p>{lowStock.summary.low_stock_count} products running low</p>
      <button onClick={() => navigateTo('/inventory')}>
        View Details
      </button>
    </div>
  );
};
```

### Operations Alert

```jsx
const OperationsAlert = () => {
  const [ops, setOps] = useState(null);

  useEffect(() => {
    fetchOperations();
    const interval = setInterval(fetchOperations, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  const fetchOperations = async () => {
    const { data } = await axios.get('/api/dashboard/operations-today');
    setOps(data.data);
  };

  if (!ops) return null;

  const pendingCount = ops.operations_status.pending.count;
  const overdueCount = ops.alerts.overdue_orders;

  return (
    <div className="operations-summary">
      <div className="status-badge">
        <span className="badge badge-yellow">{pendingCount} Pending</span>
        <span className="badge badge-red">{overdueCount} Overdue</span>
        <span className="badge badge-blue">
          {ops.operations_status.processing.count} Processing
        </span>
      </div>
    </div>
  );
};
```

---

## 🔄 Data Fetching Patterns

### Single Fetch

```javascript
const fetchTodayMetrics = async (storeId = null) => {
  try {
    const params = storeId ? { store_id: storeId } : {};
    const { data } = await axios.get('/api/dashboard/today-metrics', { params });
    return data.data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
};
```

### Parallel Fetch

```javascript
const fetchAllDashboardData = async (storeId = null) => {
  try {
    const params = storeId ? { store_id: storeId } : {};
    
    const [metrics, sales, channels, stores, products] = await Promise.all([
      axios.get('/api/dashboard/today-metrics', { params }),
      axios.get('/api/dashboard/last-30-days-sales', { params }),
      axios.get('/api/dashboard/sales-by-channel', { params: { ...params, period: 'month' } }),
      axios.get('/api/dashboard/top-stores', { params: { ...params, limit: 5 } }),
      axios.get('/api/dashboard/today-top-products', { params: { ...params, limit: 5 } })
    ]);

    return {
      metrics: metrics.data.data,
      sales: sales.data.data,
      channels: channels.data.data,
      stores: stores.data.data,
      products: products.data.data
    };
  } catch (error) {
    console.error('Error fetching dashboard:', error);
    throw error;
  }
};
```

### With Caching

```javascript
const CACHE_KEY = 'dashboard_metrics';
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

const getCachedData = (key) => {
  const cached = localStorage.getItem(key);
  if (!cached) return null;
  
  const { data, timestamp } = JSON.parse(cached);
  if (Date.now() - timestamp > CACHE_DURATION) {
    localStorage.removeItem(key);
    return null;
  }
  
  return data;
};

const setCachedData = (key, data) => {
  localStorage.setItem(key, JSON.stringify({
    data,
    timestamp: Date.now()
  }));
};

const fetchWithCache = async (endpoint, params = {}) => {
  const cacheKey = `${CACHE_KEY}_${endpoint}_${JSON.stringify(params)}`;
  
  // Check cache
  const cached = getCachedData(cacheKey);
  if (cached) return cached;
  
  // Fetch fresh
  const { data } = await axios.get(`/api/dashboard/${endpoint}`, { params });
  
  // Cache result
  setCachedData(cacheKey, data.data);
  
  return data.data;
};

// Usage
const metrics = await fetchWithCache('today-metrics', { store_id: 3 });
```

---

## 🎨 Tailwind CSS Styling

### Dashboard Grid

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <!-- KPI Cards -->
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-sm font-medium text-gray-500">Today's Sales</h3>
    <p class="text-3xl font-bold text-gray-900 mt-2">৳45,698</p>
    <p class="text-sm text-gray-600 mt-1">127 orders</p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <!-- Charts -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">Sales Trend</h2>
    <!-- Chart component -->
  </div>
  
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">Sales by Channel</h2>
    <!-- Pie chart -->
  </div>
</div>
```

---

## ⚡ Performance Tips

1. **Batch API Calls**
```javascript
// ✅ Good - Parallel requests
Promise.all([api1(), api2(), api3()]);

// ❌ Bad - Sequential requests
await api1();
await api2();
await api3();
```

2. **Implement Caching**
```javascript
// Cache frequently accessed data
localStorage.setItem('dashboard_cache', JSON.stringify(data));
```

3. **Smart Polling**
```javascript
// Different refresh rates for different data
setInterval(() => fetchCriticalData(), 5 * 60 * 1000);  // 5 min
setInterval(() => fetchNormalData(), 15 * 60 * 1000);   // 15 min
```

4. **Lazy Load Charts**
```javascript
// Load chart libraries only when needed
const Chart = React.lazy(() => import('react-chartjs-2'));
```

---

## 🐛 Error Handling

```javascript
const DashboardContainer = () => {
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const data = await axios.get('/api/dashboard/today-metrics');
      // Handle success
      
    } catch (err) {
      if (err.response?.status === 401) {
        // Unauthorized - redirect to login
        redirectToLogin();
      } else if (err.response?.status === 403) {
        // Forbidden - show permission error
        setError('You don\'t have permission to view this dashboard');
      } else {
        // Other errors
        setError('Failed to load dashboard data');
      }
    } finally {
      setLoading(false);
    }
  };

  if (error) return <ErrorMessage message={error} />;
  if (loading) return <LoadingSpinner />;
  
  return <DashboardContent />;
};
```

---

## 🎯 Complete Dashboard Example

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Line, Pie, Bar } from 'react-chartjs-2';

const CompleteDashboard = () => {
  const [data, setData] = useState({
    metrics: null,
    sales: null,
    channels: null,
    operations: null,
    lowStock: null
  });
  const [storeFilter, setStoreFilter] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchAllData();
    const interval = setInterval(fetchAllData, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, [storeFilter]);

  const fetchAllData = async () => {
    try {
      const params = storeFilter ? { store_id: storeFilter } : {};
      
      const [metrics, sales, channels, operations, lowStock] = await Promise.all([
        axios.get('/api/dashboard/today-metrics', { params }),
        axios.get('/api/dashboard/last-30-days-sales', { params }),
        axios.get('/api/dashboard/sales-by-channel', { params }),
        axios.get('/api/dashboard/operations-today', { params }),
        axios.get('/api/dashboard/low-stock-products', { params })
      ]);

      setData({
        metrics: metrics.data.data,
        sales: sales.data.data,
        channels: channels.data.data,
        operations: operations.data.data,
        lowStock: lowStock.data.data
      });
      
      setLoading(false);
    } catch (error) {
      console.error('Dashboard error:', error);
    }
  };

  if (loading) return <LoadingSpinner />;

  return (
    <div className="dashboard p-6">
      {/* Store Filter */}
      <div className="mb-6">
        <select 
          value={storeFilter || ''} 
          onChange={(e) => setStoreFilter(e.target.value || null)}
          className="form-select"
        >
          <option value="">All Stores</option>
          <option value="1">Store 1</option>
          <option value="2">Store 2</option>
        </select>
      </div>

      {/* KPI Cards Row */}
      <div className="grid grid-cols-4 gap-4 mb-6">
        <KPICard 
          title="Today's Sales" 
          value={`৳${data.metrics.total_sales.toLocaleString()}`}
          subtitle={`${data.metrics.order_count} orders`}
          color="blue"
        />
        <KPICard 
          title="Gross Margin" 
          value={`${data.metrics.gross_margin_percentage}%`}
          subtitle={`৳${data.metrics.gross_margin.toLocaleString()}`}
          color="green"
        />
        <KPICard 
          title="Net Profit" 
          value={`${data.metrics.net_profit_percentage}%`}
          subtitle={`৳${data.metrics.net_profit.toLocaleString()}`}
          color="purple"
        />
        <KPICard 
          title="Cash Position" 
          value={`৳${data.metrics.cash_snapshot.net_position.toLocaleString()}`}
          subtitle="AR - AP"
          color="yellow"
        />
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-2 gap-6 mb-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">30 Day Sales Trend</h2>
          <Line data={prepareSalesChartData(data.sales)} />
        </div>
        
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">Sales by Channel</h2>
          <Pie data={prepareChannelChartData(data.channels)} />
        </div>
      </div>

      {/* Operations & Alerts Row */}
      <div className="grid grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">Operations Today</h2>
          <OperationsStatus data={data.operations} />
        </div>
        
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-4">Inventory Alerts</h2>
          <LowStockList data={data.lowStock} />
        </div>
      </div>
    </div>
  );
};

export default CompleteDashboard;
```

---

**Quick Reference Guide**  
**Version**: 1.0  
**Last Updated**: November 21, 2025