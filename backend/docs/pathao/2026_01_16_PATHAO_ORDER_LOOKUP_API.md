# Pathao Order Lookup API

**Document Version:** 1.0  
**Last Updated:** January 16, 2026  
**Purpose:** API documentation for checking if orders were sent via Pathao courier

---

## Overview

These APIs allow you to quickly check whether an order (or multiple orders) was shipped via Pathao courier service. This is useful for:

- Displaying Pathao tracking information in order details
- Filtering orders by delivery method
- Generating Pathao-specific reports
- Integration with tracking systems

---

## Table of Contents

1. [Single Order Lookup](#single-order-lookup)
2. [Bulk Order Lookup](#bulk-order-lookup)
3. [Response Data Structure](#response-data-structure)
4. [Use Cases](#use-cases)
5. [Integration Examples](#integration-examples)

---

## Single Order Lookup

Check if a single order was sent via Pathao.

### Endpoint

```
GET /api/pathao/orders/lookup/{orderNumber}
```

### Parameters

| Parameter | Type | Location | Required | Description |
|-----------|------|----------|----------|-------------|
| orderNumber | string | path | ✅ Yes | The order number (e.g., ORD-20260116-001) |

### Headers

```
Authorization: Bearer {access_token}
Accept: application/json
```

### Example Request

```bash
GET /api/pathao/orders/lookup/ORD-20260116-001
```

### Success Response (200 OK)

**Order sent via Pathao:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-20260116-001",
    "order_id": 123,
    "is_sent_via_pathao": true,
    "pathao_consignment_id": "DC140126JAJGZS",
    "pathao_status": "Pending",
    "shipment_status": "pickup_requested"
  }
}
```

**Order NOT sent via Pathao:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-20260116-002",
    "order_id": 124,
    "is_sent_via_pathao": false,
    "pathao_consignment_id": null,
    "pathao_status": null,
    "shipment_status": null
  }
}
```

### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Order not found"
}
```

### Error Response (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Error checking order status: Database connection failed"
}
```

---

## Bulk Order Lookup

Check multiple orders at once (up to 100 orders per request).

### Endpoint

```
POST /api/pathao/orders/lookup/bulk
```

### Headers

```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
  "order_numbers": [
    "ORD-20260116-001",
    "ORD-20260116-002",
    "ORD-20260116-003"
  ]
}
```

### Request Body Schema

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| order_numbers | array | ✅ Yes | min:1, max:100 | Array of order numbers to check |
| order_numbers.* | string | ✅ Yes | - | Individual order number |

### Example Request

```bash
POST /api/pathao/orders/lookup/bulk
Content-Type: application/json

{
  "order_numbers": [
    "ORD-20260116-001",
    "ORD-20260116-002",
    "ORD-20260115-999"
  ]
}
```

### Success Response (200 OK)

```json
{
  "success": true,
  "total_requested": 3,
  "total_found": 2,
  "data": [
    {
      "order_number": "ORD-20260116-001",
      "order_id": 123,
      "is_sent_via_pathao": true,
      "found": true,
      "pathao_consignment_id": "DC140126JAJGZS",
      "pathao_status": "Pending",
      "shipment_status": "pickup_requested"
    },
    {
      "order_number": "ORD-20260116-002",
      "order_id": 124,
      "is_sent_via_pathao": false,
      "found": true,
      "pathao_consignment_id": null,
      "pathao_status": null,
      "shipment_status": null
    },
    {
      "order_number": "ORD-20260115-999",
      "order_id": null,
      "is_sent_via_pathao": false,
      "found": false,
      "error": "Order not found"
    }
  ]
}
```

### Validation Error Response (422 Unprocessable Entity)

**Missing order_numbers:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_numbers": [
      "The order numbers field is required."
    ]
  }
}
```

**Too many orders (>100):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_numbers": [
      "The order numbers must not have more than 100 items."
    ]
  }
}
```

**Empty array:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_numbers": [
      "The order numbers must have at least 1 items."
    ]
  }
}
```

### Error Response (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Error performing bulk lookup: Database connection failed"
}
```

---

## Response Data Structure

### Single Order Response Fields

| Field | Type | Always Present | Description |
|-------|------|----------------|-------------|
| success | boolean | ✅ Yes | Whether the API call succeeded |
| data | object | ✅ Yes | Order Pathao status data |
| data.order_number | string | ✅ Yes | The order number |
| data.order_id | integer | ✅ Yes | The order ID |
| data.is_sent_via_pathao | boolean | ✅ Yes | **Key field:** true if sent via Pathao, false otherwise |
| data.pathao_consignment_id | string\|null | ✅ Yes | Pathao consignment ID if sent via Pathao |
| data.pathao_status | string\|null | ✅ Yes | Current Pathao status (Pending, Pickup_Requested, etc.) |
| data.shipment_status | string\|null | ✅ Yes | Internal shipment status |

### Bulk Order Response Fields

| Field | Type | Always Present | Description |
|-------|------|----------------|-------------|
| success | boolean | ✅ Yes | Whether the API call succeeded |
| total_requested | integer | ✅ Yes | Number of order numbers requested |
| total_found | integer | ✅ Yes | Number of orders found in system |
| data | array | ✅ Yes | Array of order status objects |
| data[].order_number | string | ✅ Yes | The order number |
| data[].order_id | integer\|null | ✅ Yes | Order ID (null if not found) |
| data[].is_sent_via_pathao | boolean | ✅ Yes | **Key field:** true if sent via Pathao |
| data[].found | boolean | ✅ Yes | Whether order exists in system |
| data[].pathao_consignment_id | string\|null | ✅ Yes | Pathao consignment ID |
| data[].pathao_status | string\|null | ✅ Yes | Current Pathao status |
| data[].shipment_status | string\|null | ✅ Yes | Internal shipment status |
| data[].error | string | ❌ No | Error message (only if order not found) |

### Pathao Status Values

| Status | Description |
|--------|-------------|
| `Pending` | Order created in Pathao, awaiting pickup |
| `Pickup_Requested` | Pickup scheduled |
| `Pickup_Failed` | Pickup attempt failed |
| `In_Transit` | Package on the way to destination |
| `Delivered` | Successfully delivered to customer |
| `Returned` | Returned to sender |
| `Cancelled` | Order cancelled |

### Shipment Status Values

| Status | Description |
|--------|-------------|
| `pending` | Shipment created but not sent to Pathao |
| `pickup_requested` | Sent to Pathao, awaiting pickup |
| `in_transit` | Package picked up and on the way |
| `delivered` | Delivered to customer |
| `returned` | Returned to sender |
| `cancelled` | Shipment cancelled |

---

## Use Cases

### Use Case 1: Display Pathao Tracking in Order Details

**Frontend Implementation:**

```javascript
// Fetch order details
const order = await getOrderDetails(orderId);

// Check if sent via Pathao
const pathaoCheck = await fetch(
  `/api/pathao/orders/lookup/${order.order_number}`
);
const pathaoData = await pathaoCheck.json();

if (pathaoData.data.is_sent_via_pathao) {
  // Show Pathao tracking button
  showPathaoTracking(pathaoData.data.pathao_consignment_id);
} else {
  // Show regular shipping info
  showRegularShipping(order.tracking_number);
}
```

---

### Use Case 2: Filter Orders by Delivery Method

**Backend Query:**

```php
// Get all Pathao orders for today
$pathaoOrders = Order::whereHas('shipments', function($query) {
    $query->whereNotNull('pathao_consignment_id');
})
->whereDate('created_at', today())
->get();
```

**Frontend Filter:**

```javascript
// Bulk check which orders are Pathao
const orderNumbers = orders.map(o => o.order_number);
const bulkCheck = await fetch('/api/pathao/orders/lookup/bulk', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ order_numbers: orderNumbers })
});

const result = await bulkCheck.json();

// Filter to show only Pathao orders
const pathaoOrders = result.data
  .filter(o => o.is_sent_via_pathao)
  .map(o => o.order_number);
```

---

### Use Case 3: Generate Pathao Delivery Report

**Report Generation:**

```javascript
async function generatePathaoReport(startDate, endDate) {
  // Get all orders in date range
  const orders = await getOrders({ startDate, endDate });
  const orderNumbers = orders.map(o => o.order_number);
  
  // Bulk check Pathao status
  const chunks = chunkArray(orderNumbers, 100); // Max 100 per request
  const allResults = [];
  
  for (const chunk of chunks) {
    const response = await fetch('/api/pathao/orders/lookup/bulk', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_numbers: chunk })
    });
    const data = await response.json();
    allResults.push(...data.data);
  }
  
  // Calculate statistics
  const pathaoOrders = allResults.filter(o => o.is_sent_via_pathao);
  const totalOrders = allResults.length;
  const pathaoPercentage = (pathaoOrders.length / totalOrders) * 100;
  
  return {
    total_orders: totalOrders,
    pathao_orders: pathaoOrders.length,
    pathao_percentage: pathaoPercentage.toFixed(2),
    orders: pathaoOrders
  };
}
```

---

### Use Case 4: Order List Page with Pathao Badge

**Component Example:**

```jsx
function OrderList({ orders }) {
  const [pathaoStatus, setPathaoStatus] = useState({});
  
  useEffect(() => {
    // Check which orders are Pathao
    const checkPathaoStatus = async () => {
      const orderNumbers = orders.map(o => o.order_number);
      const response = await fetch('/api/pathao/orders/lookup/bulk', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_numbers: orderNumbers })
      });
      const data = await response.json();
      
      // Create lookup map
      const statusMap = {};
      data.data.forEach(item => {
        statusMap[item.order_number] = item.is_sent_via_pathao;
      });
      setPathaoStatus(statusMap);
    };
    
    checkPathaoStatus();
  }, [orders]);
  
  return (
    <table>
      <thead>
        <tr>
          <th>Order Number</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Delivery</th>
        </tr>
      </thead>
      <tbody>
        {orders.map(order => (
          <tr key={order.id}>
            <td>{order.order_number}</td>
            <td>{order.customer_name}</td>
            <td>{order.total_amount}</td>
            <td>
              {pathaoStatus[order.order_number] ? (
                <span className="badge badge-pathao">
                  🚚 Pathao
                </span>
              ) : (
                <span className="badge badge-regular">
                  📦 Regular
                </span>
              )}
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```

---

## Integration Examples

### React/Next.js Hook

```javascript
// hooks/usePathaoStatus.js
import { useState, useEffect } from 'react';

export function usePathaoStatus(orderNumber) {
  const [isPathao, setIsPathao] = useState(false);
  const [loading, setLoading] = useState(true);
  const [consignmentId, setConsignmentId] = useState(null);
  
  useEffect(() => {
    if (!orderNumber) return;
    
    const checkStatus = async () => {
      try {
        setLoading(true);
        const response = await fetch(
          `/api/pathao/orders/lookup/${orderNumber}`
        );
        const data = await response.json();
        
        if (data.success) {
          setIsPathao(data.data.is_sent_via_pathao);
          setConsignmentId(data.data.pathao_consignment_id);
        }
      } catch (error) {
        console.error('Error checking Pathao status:', error);
      } finally {
        setLoading(false);
      }
    };
    
    checkStatus();
  }, [orderNumber]);
  
  return { isPathao, loading, consignmentId };
}

// Usage:
function OrderDetails({ order }) {
  const { isPathao, loading, consignmentId } = usePathaoStatus(
    order.order_number
  );
  
  if (loading) return <div>Loading...</div>;
  
  return (
    <div>
      <h2>Order {order.order_number}</h2>
      {isPathao && (
        <div className="pathao-info">
          <p>Shipped via Pathao Courier</p>
          <p>Consignment ID: {consignmentId}</p>
          <button onClick={() => trackPathao(consignmentId)}>
            Track Shipment
          </button>
        </div>
      )}
    </div>
  );
}
```

---

### Vue.js Composable

```javascript
// composables/usePathaoStatus.js
import { ref, watch } from 'vue';

export function usePathaoStatus(orderNumber) {
  const isPathao = ref(false);
  const loading = ref(false);
  const consignmentId = ref(null);
  const error = ref(null);
  
  const checkStatus = async () => {
    if (!orderNumber.value) return;
    
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch(
        `/api/pathao/orders/lookup/${orderNumber.value}`
      );
      const data = await response.json();
      
      if (data.success) {
        isPathao.value = data.data.is_sent_via_pathao;
        consignmentId.value = data.data.pathao_consignment_id;
      } else {
        error.value = data.message;
      }
    } catch (err) {
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  };
  
  watch(orderNumber, checkStatus, { immediate: true });
  
  return { isPathao, loading, consignmentId, error, checkStatus };
}
```

---

### PHP Backend Service

```php
// app/Services/PathaoLookupService.php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class PathaoLookupService
{
    /**
     * Check if order was sent via Pathao
     */
    public function isOrderSentViaPathao(string $orderNumber): bool
    {
        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            return false;
        }
        
        return $order->shipments()
            ->whereNotNull('pathao_consignment_id')
            ->exists();
    }
    
    /**
     * Get Pathao orders from a collection
     */
    public function filterPathaoOrders(Collection $orders): Collection
    {
        return $orders->filter(function($order) {
            return $order->shipments()
                ->whereNotNull('pathao_consignment_id')
                ->exists();
        });
    }
    
    /**
     * Bulk check multiple orders
     */
    public function bulkCheck(array $orderNumbers): array
    {
        $orders = Order::with(['shipments' => function($query) {
            $query->whereNotNull('pathao_consignment_id');
        }])
        ->whereIn('order_number', $orderNumbers)
        ->get();
        
        $results = [];
        foreach ($orderNumbers as $orderNumber) {
            $order = $orders->firstWhere('order_number', $orderNumber);
            
            $results[] = [
                'order_number' => $orderNumber,
                'is_sent_via_pathao' => $order && $order->shipments->isNotEmpty(),
            ];
        }
        
        return $results;
    }
}
```

---

## Performance Considerations

### Caching Strategy

For high-traffic applications, consider caching Pathao status:

```javascript
// Cache for 5 minutes
const cacheKey = `pathao_status_${orderNumber}`;
let cachedData = cache.get(cacheKey);

if (!cachedData) {
  const response = await fetch(`/api/pathao/orders/lookup/${orderNumber}`);
  cachedData = await response.json();
  cache.set(cacheKey, cachedData, 300); // 5 minutes
}

return cachedData;
```

### Batch Processing

For checking large numbers of orders, use the bulk endpoint with batching:

```javascript
function chunkArray(array, chunkSize) {
  const chunks = [];
  for (let i = 0; i < array.length; i += chunkSize) {
    chunks.push(array.slice(i, i + chunkSize));
  }
  return chunks;
}

async function checkAllOrders(orderNumbers) {
  const chunks = chunkArray(orderNumbers, 100); // Max 100 per request
  const results = [];
  
  for (const chunk of chunks) {
    const response = await fetch('/api/pathao/orders/lookup/bulk', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_numbers: chunk })
    });
    const data = await response.json();
    results.push(...data.data);
  }
  
  return results;
}
```

---

## Best Practices

1. **Use Bulk Endpoint for Multiple Orders**
   - Don't call single lookup in a loop
   - Batch requests up to 100 orders at a time

2. **Handle Not Found Orders**
   - Check the `found` field in bulk responses
   - Display appropriate error messages

3. **Cache Results**
   - Pathao status doesn't change frequently
   - Cache for 5-15 minutes to reduce load

4. **Error Handling**
   - Always handle network failures gracefully
   - Show fallback UI when API is unavailable

5. **Loading States**
   - Show loading indicators during API calls
   - Don't block UI while fetching status

---

## Troubleshooting

### Issue: is_sent_via_pathao always returns false

**Possible Causes:**
1. Order hasn't been sent to Pathao yet
2. Shipment was created but Pathao integration failed
3. Order was sent via different courier

**Solution:**
- Check order's shipment table for `pathao_consignment_id`
- Verify Pathao credentials are configured
- Check shipment logs for errors

---

### Issue: Bulk lookup returns "Order not found"

**Possible Causes:**
1. Wrong order number format
2. Order deleted or archived
3. Order from different store/tenant

**Solution:**
- Verify order number is exact match
- Check if order exists in orders table
- Ensure proper store context

---

## Related Documentation

- [Pathao API Setup Guide](../integrations/PATHAO_API_SETUP_GUIDE.md)
- [Pathao Frontend Complete Guide](../integrations/PATHAO_FRONTEND_COMPLETE_GUIDE.md)
- [Pathao Multi-Store System](../integrations/PATHAO_MULTI_STORE_SYSTEM.md)
- [Order Tracking API](../features/ORDER_TRACKING_API.md)

---

## Changelog

**Version 1.0 (January 16, 2026)**
- Initial release
- Single order lookup endpoint
- Bulk order lookup endpoint
- Complete documentation with examples

---

**Questions or Issues?**  
Contact the backend team or refer to the main [Pathao Integration Documentation](../integrations/README.md).
