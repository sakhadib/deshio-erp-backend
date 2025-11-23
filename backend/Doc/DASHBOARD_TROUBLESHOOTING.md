# Dashboard API Troubleshooting Guide

**Created:** November 22, 2025  
**For:** Frontend Team

---

## Issue: Empty Response or 401/500 Errors

If you're seeing errors like:
```
Error fetching /dashboard/top-stores: {}
Error fetching /dashboard/slow-moving-products: {}
```

### Quick Checklist

1. **✅ Correct URL Format**
   - ❌ Wrong: `https://api.example.com/dashboard/top-stores`
   - ✅ Correct: `https://api.example.com/api/dashboard/top-stores`
   
   All dashboard endpoints require `/api/` prefix.

2. **✅ Authentication Required**
   All dashboard endpoints require authentication with `auth:api` middleware.
   
   ```javascript
   // Axios example
   axios.get('/api/dashboard/top-stores', {
     headers: {
       'Authorization': `Bearer ${token}`
     }
   })
   ```

3. **✅ Check Response Status**
   ```javascript
   try {
     const response = await axios.get('/api/dashboard/top-stores');
     console.log('Status:', response.status);
     console.log('Data:', response.data);
   } catch (error) {
     console.error('Status:', error.response?.status);
     console.error('Error:', error.response?.data);
   }
   ```

---

## All Dashboard Endpoints

| Endpoint | Full URL | Auth Required | Query Params |
|----------|----------|---------------|--------------|
| Today's Metrics | `GET /api/dashboard/today-metrics` | ✅ Yes | `?store_id={id}` |
| Last 30 Days Sales | `GET /api/dashboard/last-30-days-sales` | ✅ Yes | `?store_id={id}` |
| Sales by Channel | `GET /api/dashboard/sales-by-channel` | ✅ Yes | `?period=today&store_id={id}` |
| Top Stores | `GET /api/dashboard/top-stores` | ✅ Yes | `?period=today&limit=10` |
| Today's Top Products | `GET /api/dashboard/today-top-products` | ✅ Yes | `?store_id={id}&limit=10` |
| Slow Moving Products | `GET /api/dashboard/slow-moving-products` | ✅ Yes | `?store_id={id}&days=90&limit=10` |
| Low Stock Products | `GET /api/dashboard/low-stock-products` | ✅ Yes | `?store_id={id}&threshold=10` |
| Inventory Age | `GET /api/dashboard/inventory-age-by-value` | ✅ Yes | `?store_id={id}` |
| Operations Today | `GET /api/dashboard/operations-today` | ✅ Yes | `?store_id={id}` |

---

## Common Issues & Solutions

### 1. Getting Empty Object `{}`

**Cause:** Frontend is catching the error but not logging the actual error response.

**Fix:** Update error handling:
```javascript
// ❌ Bad - logs empty object
catch (error) {
  console.error('Error:', error.response?.data || error.message);
}

// ✅ Good - logs full error
catch (error) {
  console.error('Full error:', error);
  console.error('Response status:', error.response?.status);
  console.error('Response data:', error.response?.data);
  console.error('Error message:', error.message);
}
```

### 2. 401 Unauthorized Error

**Cause:** Missing or invalid authentication token.

**Fix:**
```javascript
// Ensure token is included in every request
const token = localStorage.getItem('authToken'); // or however you store it

axios.get('/api/dashboard/top-stores', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

### 3. 404 Not Found Error

**Cause:** Missing `/api/` prefix in URL.

**Fix:**
```javascript
// ❌ Wrong
fetch('/dashboard/top-stores')

// ✅ Correct
fetch('/api/dashboard/top-stores')
```

### 4. CORS Error

**Cause:** Backend not configured to allow frontend domain.

**Ask backend team to:**
- Check `config/cors.php`
- Ensure your frontend domain is allowed
- Verify credentials are allowed

### 5. Empty Data Arrays

**Cause:** No data exists for the requested period/store.

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "top_stores": []  // Empty array is valid
  }
}
```

This is **not an error** - it means there's no data for that period.

---

## Testing Endpoints

### Using Browser DevTools

1. Open your app
2. Open DevTools (F12)
3. Go to Network tab
4. Trigger the dashboard API call
5. Click on the request
6. Check:
   - Request URL (should have `/api/`)
   - Request Headers (should have `Authorization: Bearer ...`)
   - Response status code
   - Response body

### Using Postman

```
GET https://your-backend-url.com/api/dashboard/top-stores?period=today

Headers:
  Authorization: Bearer YOUR_TOKEN_HERE
  Accept: application/json
```

### Using cURL

```bash
curl -X GET "https://your-backend-url.com/api/dashboard/top-stores?period=today" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## Sample Working Code

### React/Next.js Example

```tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

// Configure axios instance
const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL, // e.g., https://api.example.com
  headers: {
    'Accept': 'application/json',
  }
});

// Add auth token to every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

function DashboardPage() {
  const [topStores, setTopStores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchDashboard() {
      try {
        setLoading(true);
        
        // Call with /api/ prefix
        const response = await api.get('/api/dashboard/top-stores', {
          params: {
            period: 'today',
            limit: 10
          }
        });
        
        console.log('Success:', response.data);
        setTopStores(response.data.data.top_stores);
        setError(null);
        
      } catch (err) {
        console.error('Full error:', err);
        console.error('Response:', err.response);
        console.error('Status:', err.response?.status);
        console.error('Data:', err.response?.data);
        
        setError(err.response?.data?.message || err.message || 'Unknown error');
      } finally {
        setLoading(false);
      }
    }

    fetchDashboard();
  }, []);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  
  return (
    <div>
      <h1>Top Stores</h1>
      {topStores.length === 0 ? (
        <p>No data available for today</p>
      ) : (
        <ul>
          {topStores.map(store => (
            <li key={store.store_id}>
              #{store.rank} - {store.store_name}: ${store.total_sales}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export default DashboardPage;
```

### Vanilla JavaScript Example

```javascript
const API_BASE_URL = 'https://your-api-url.com';
const authToken = localStorage.getItem('authToken');

async function fetchTopStores() {
  try {
    const response = await fetch(`${API_BASE_URL}/api/dashboard/top-stores?period=today`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });

    // Check if response is OK
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(`HTTP ${response.status}: ${errorData.message || 'Unknown error'}`);
    }

    const data = await response.json();
    console.log('Top stores:', data.data.top_stores);
    return data.data.top_stores;
    
  } catch (error) {
    console.error('Error fetching top stores:', error);
    throw error;
  }
}

// Call it
fetchTopStores()
  .then(stores => {
    console.log('Received stores:', stores);
    // Update UI here
  })
  .catch(error => {
    console.error('Failed:', error);
    // Show error to user
  });
```

---

## Expected Response Formats

### Top Stores
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
    ]
  }
}
```

### Slow Moving Products
```json
{
  "success": true,
  "data": {
    "period_days": 90,
    "slow_moving_products": [
      {
        "rank": 1,
        "product_id": 42,
        "product_name": "Old Model Phone",
        "product_sku": "PHONE-OLD-001",
        "category": "Electronics",
        "current_stock": 50,
        "stock_value": 5000.00,
        "quantity_sold": 2,
        "order_count": 2,
        "turnover_rate": 4.00,
        "days_of_supply": 2250
      }
    ]
  }
}
```

---

## Backend Status

✅ **All endpoints are working and tested**  
✅ **Routes are registered correctly**  
✅ **Empty data handling added**  
✅ **Error logging enabled**

If you're still having issues after checking this guide, please provide:
1. Full error message from browser console
2. Network tab screenshot showing the request
3. Response status code
4. Whether you're sending the Authorization header

---

**Last Updated:** November 22, 2025  
**Backend Version:** Laravel 11.x  
**Contact:** Backend Team
