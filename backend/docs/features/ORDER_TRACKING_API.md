# Order Tracking API - Physical Product Tracking

**Document Created:** January 8, 2026  
**Purpose:** Track physical product shipment and delivery status  
**Backend Support:** ✅ Fully Implemented

---

## Overview

This API allows both **customers** and **employees** to track the real-time status and location of physical products in orders. The system integrates with Pathao courier service and provides detailed tracking information including shipment status, estimated delivery, and order timeline.

---

## API Endpoints

### **1. Customer Order Tracking (Customer-Facing)**

```
GET /api/customer/orders/{orderNumber}/track
```

**Authentication:** Required (Customer JWT Token)

**Headers:**
```json
{
  "Authorization": "Bearer {customer_token}",
  "Content-Type": "application/json"
}
```

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `orderNumber` | string | Unique order number (e.g., "ORD-260108-1234") |

---

### **2. Employee Order Tracking (Internal)**

```
GET /api/employee/orders/{id}
```

**Authentication:** Required (Employee JWT Token)

**Headers:**
```json
{
  "Authorization": "Bearer {employee_token}",
  "Content-Type": "application/json"
}
```

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Order ID (internal database ID) |

---

### **3. Multi-Store Shipment Tracking**

For orders fulfilled by multiple stores:

```
GET /api/employee/multi-store-shipments/orders/{orderId}/track-all
```

**Authentication:** Required (Employee JWT Token)

---

## Request Examples

### **Customer Tracking Request**

```bash
GET /api/customer/orders/ORD-260108-1234/track
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### **Employee Tracking Request**

```bash
GET /api/employee/orders/45
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## Response Examples

### **Success Response - Customer View**

```json
{
  "success": true,
  "data": {
    "order": {
      "id": 45,
      "order_number": "ORD-260108-1234",
      "status": "shipped",
      "total_amount": 5500,
      "customer_id": 123,
      "created_at": "2026-01-08T10:30:00Z",
      "items": [
        {
          "id": 156,
          "product_id": 89,
          "product": {
            "id": 89,
            "name": "Samsung Galaxy S24",
            "sku": "PHONE-SAM-S24",
            "images": ["products/samsung-s24.jpg"]
          },
          "quantity": 1,
          "unit_price": 5000,
          "subtotal": 5000
        }
      ],
      "shipping_address": {
        "name": "Mr. Jack",
        "phone": "01712345678",
        "address": "123 Main Street",
        "city": "Dhaka",
        "area": "Gulshan",
        "postal_code": "1212"
      }
    },
    "tracking": {
      "current_status": "shipped",
      "tracking_number": "PATHAO-DH-20260108-567",
      "estimated_delivery": "2026-01-10",
      "last_updated": "2026-01-08T14:20:00Z",
      "steps": [
        {
          "status": "pending",
          "label": "Order Placed",
          "completed": true,
          "date": "2026-01-08T10:30:00Z"
        },
        {
          "status": "processing",
          "label": "Order Processing",
          "completed": true,
          "date": "2026-01-08T11:15:00Z"
        },
        {
          "status": "shipped",
          "label": "Order Shipped",
          "completed": true,
          "date": "2026-01-08T14:20:00Z"
        },
        {
          "status": "delivered",
          "label": "Order Delivered",
          "completed": false,
          "date": null
        }
      ]
    }
  }
}
```

### **Success Response - Employee View**

Employee view includes additional internal details:

```json
{
  "success": true,
  "data": {
    "id": 45,
    "order_number": "ORD-260108-1234",
    "status": "shipped",
    "payment_status": "paid",
    "total_amount": 5500,
    "paid_amount": 5500,
    "outstanding_amount": 0,
    "store_id": 1,
    "store": {
      "id": 1,
      "name": "Main Store - Dhaka",
      "address": "Gulshan, Dhaka"
    },
    "customer": {
      "id": 123,
      "name": "Mr. Jack",
      "phone": "01712345678",
      "email": "jack@example.com",
      "customer_type": "ecommerce"
    },
    "items": [
      {
        "id": 156,
        "product_id": 89,
        "quantity": 1,
        "unit_price": 5000,
        "product": {
          "name": "Samsung Galaxy S24",
          "sku": "PHONE-SAM-S24"
        },
        "barcodes_scanned": ["BCX123456789"],
        "fulfillment_status": "fulfilled"
      }
    ],
    "shipment": {
      "id": 78,
      "tracking_number": "PATHAO-DH-20260108-567",
      "courier_service": "pathao",
      "status": "in_transit",
      "pathao_consignment_id": "PATHAO567890",
      "estimated_delivery_date": "2026-01-10",
      "shipped_at": "2026-01-08T14:20:00Z",
      "delivery_address": {
        "name": "Mr. Jack",
        "phone": "01712345678",
        "address": "123 Main Street, Gulshan",
        "city": "Dhaka",
        "zone": "Dhaka Metro",
        "area": "Gulshan"
      }
    },
    "payments": [
      {
        "id": 234,
        "amount": 5500,
        "payment_method": "bKash",
        "status": "completed",
        "completed_at": "2026-01-08T10:35:00Z"
      }
    ],
    "created_at": "2026-01-08T10:30:00Z",
    "updated_at": "2026-01-08T14:20:00Z"
  }
}
```

### **Multi-Store Tracking Response**

For orders split across multiple stores:

```json
{
  "success": true,
  "data": {
    "order": {
      "id": 52,
      "order_number": "ORD-260108-5678",
      "status": "shipped",
      "total_stores": 2
    },
    "shipments": [
      {
        "shipment_id": 89,
        "store_id": 1,
        "store_name": "Main Store - Dhaka",
        "tracking_number": "PATHAO-DH-20260108-001",
        "status": "in_transit",
        "items": [
          {
            "product_name": "Samsung Galaxy S24",
            "quantity": 1
          }
        ],
        "estimated_delivery": "2026-01-10"
      },
      {
        "shipment_id": 90,
        "store_id": 3,
        "store_name": "Branch Store - Chittagong",
        "tracking_number": "PATHAO-CTG-20260108-002",
        "status": "pending",
        "items": [
          {
            "product_name": "iPhone 15 Pro",
            "quantity": 1
          }
        ],
        "estimated_delivery": "2026-01-12"
      }
    ],
    "tracking_summary": {
      "total_shipments": 2,
      "in_transit": 1,
      "pending": 1,
      "delivered": 0,
      "overall_status": "partially_shipped"
    }
  }
}
```

---

## Order Status Flow

### **Status Lifecycle**

```
pending → processing → shipped → delivered
                    ↓
                cancelled (at any stage before shipped)
```

| Status | Description | Customer Action |
|--------|-------------|-----------------|
| `pending` | Order received, awaiting payment confirmation | Can cancel |
| `processing` | Payment confirmed, preparing order | Can cancel (within 24h) |
| `shipped` | Order dispatched with courier | Cannot cancel |
| `delivered` | Order delivered to customer | Can initiate return (within 7 days) |
| `cancelled` | Order cancelled | - |

---

## Shipment Status Types

### **Pathao Integration Status**

| Status | Description |
|--------|-------------|
| `pending` | Shipment created but not yet picked up |
| `pickup_pending` | Awaiting courier pickup |
| `picked_up` | Courier picked up from warehouse |
| `in_transit` | On the way to customer |
| `out_for_delivery` | Out for delivery (final mile) |
| `delivered` | Successfully delivered |
| `cancelled` | Shipment cancelled |
| `returned` | Returned to sender |
| `on_hold` | Shipment on hold |

---

## Tracking Information Fields

### **Customer-Visible Fields**

| Field | Type | Description |
|-------|------|-------------|
| `current_status` | string | Current order status |
| `tracking_number` | string | Courier tracking number |
| `estimated_delivery` | date | Expected delivery date |
| `steps` | array | Timeline of order progress |
| `last_updated` | datetime | Last status update time |

### **Employee-Visible Fields (Additional)**

| Field | Type | Description |
|-------|------|-------------|
| `store_id` | integer | Fulfilling store ID |
| `barcodes_scanned` | array | Scanned product barcodes |
| `fulfillment_status` | string | Warehouse fulfillment status |
| `pathao_consignment_id` | string | Pathao internal tracking ID |
| `courier_service` | string | Courier service provider |
| `payment_status` | string | Payment status |
| `outstanding_amount` | decimal | Remaining balance |

---

## Frontend Implementation Guide

### **Step 1: Display Order Tracking Page**

```javascript
async function loadOrderTracking(orderNumber) {
  try {
    const response = await fetch(
      `/api/customer/orders/${orderNumber}/track`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${customerToken}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.success) {
      displayTrackingInfo(data.data);
    }
    
  } catch (error) {
    console.error('Tracking error:', error);
    showErrorMessage('Unable to load tracking information');
  }
}
```

### **Step 2: Render Tracking Timeline**

```javascript
function displayTrackingInfo(trackingData) {
  const { order, tracking } = trackingData;
  
  // Display order summary
  document.getElementById('order-number').textContent = order.order_number;
  document.getElementById('order-status').textContent = tracking.current_status;
  document.getElementById('tracking-number').textContent = tracking.tracking_number;
  document.getElementById('estimated-delivery').textContent = 
    formatDate(tracking.estimated_delivery);
  
  // Render timeline steps
  const timeline = document.getElementById('tracking-timeline');
  timeline.innerHTML = '';
  
  tracking.steps.forEach((step, index) => {
    const stepElement = createTimelineStep(step, index);
    timeline.appendChild(stepElement);
  });
}

function createTimelineStep(step, index) {
  const div = document.createElement('div');
  div.className = `timeline-step ${step.completed ? 'completed' : 'pending'}`;
  
  div.innerHTML = `
    <div class="step-icon">
      ${step.completed ? '✓' : index + 1}
    </div>
    <div class="step-content">
      <h4>${step.label}</h4>
      <p class="step-status">${step.status}</p>
      ${step.date ? `<p class="step-date">${formatDateTime(step.date)}</p>` : ''}
    </div>
  `;
  
  return div;
}
```

### **Step 3: Real-Time Tracking Updates**

```javascript
// Poll for updates every 60 seconds
let trackingInterval;

function startTrackingPolling(orderNumber) {
  // Initial load
  loadOrderTracking(orderNumber);
  
  // Poll every 60 seconds
  trackingInterval = setInterval(() => {
    loadOrderTracking(orderNumber);
  }, 60000); // 60 seconds
}

function stopTrackingPolling() {
  if (trackingInterval) {
    clearInterval(trackingInterval);
  }
}

// Start polling when page loads
window.addEventListener('load', () => {
  const orderNumber = getOrderNumberFromURL();
  startTrackingPolling(orderNumber);
});

// Stop polling when leaving page
window.addEventListener('beforeunload', stopTrackingPolling);
```

### **Step 4: Multi-Store Order Tracking**

For orders with multiple shipments:

```javascript
async function loadMultiStoreTracking(orderId) {
  const response = await fetch(
    `/api/employee/multi-store-shipments/orders/${orderId}/track-all`,
    {
      headers: {
        'Authorization': `Bearer ${employeeToken}`,
        'Content-Type': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    displayMultiStoreTracking(data.data);
  }
}

function displayMultiStoreTracking(trackingData) {
  const { shipments, tracking_summary } = trackingData;
  
  // Display summary
  document.getElementById('total-shipments').textContent = 
    tracking_summary.total_shipments;
  document.getElementById('overall-status').textContent = 
    tracking_summary.overall_status;
  
  // Render each shipment
  const container = document.getElementById('shipments-container');
  container.innerHTML = '';
  
  shipments.forEach(shipment => {
    const card = createShipmentCard(shipment);
    container.appendChild(card);
  });
}
```

---

## UI/UX Recommendations

### **1. Visual Progress Indicator**

```
Order Placed ✓ → Processing ✓ → Shipped ● → Delivered ○
```

- **Completed**: Green checkmark
- **Current**: Pulsing blue dot
- **Pending**: Gray circle

### **2. Estimated Delivery Prominence**

Display estimated delivery date prominently:

```
Expected Delivery: January 10, 2026
2 days remaining
```

### **3. Tracking Number Copy Button**

Allow customers to copy tracking number:

```
Tracking: PATHAO-DH-20260108-567 [Copy]
```

### **4. Courier Link (if available)**

Provide external tracking link:

```
Track on Pathao Website →
```

### **5. Delivery Address Display**

Show delivery address for confirmation:

```
Delivering to:
Mr. Jack
123 Main Street, Gulshan
Dhaka - 1212
01712345678
```

### **6. Order Items Summary**

Show items being shipped:

```
📦 1x Samsung Galaxy S24
💰 Total: 5,500 TK
```

---

## Error Handling

### **Common Errors**

**1. Order Not Found (404)**

```json
{
  "success": false,
  "message": "Order not found",
  "error": "No query results for model [App\\Models\\Order]"
}
```

**Solution:** Verify order number is correct

**2. Unauthorized Access (401)**

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Solution:** Check authentication token is valid

**3. Wrong Customer (403)**

Customer trying to track someone else's order:

```json
{
  "success": false,
  "message": "Order not found"
}
```

**Note:** System returns 404 instead of 403 for security reasons

---

## Tracking Scenarios

### **Scenario 1: Standard E-commerce Order**

```
Day 1 (10:30 AM): Order Placed
Day 1 (11:15 AM): Payment Confirmed → Processing
Day 1 (2:20 PM): Shipped with Pathao
Day 3 (Expected): Delivery
```

### **Scenario 2: Multi-Store Order**

```
Main Store (Dhaka):
  - Phone shipped Day 1
  - Expected: Day 3

Branch Store (Chittagong):
  - Accessories shipped Day 2
  - Expected: Day 5
```

### **Scenario 3: Express Delivery**

```
Day 1 (Morning): Order + Express Shipping
Day 1 (Evening): Shipped
Day 2 (Morning): Delivered (within 24 hours)
```

### **Scenario 4: Order Cancellation**

```
Day 1: Order Placed
Day 1 (within 24h): Customer cancels
Status: Cancelled
Refund: Processed automatically
```

---

## Testing Checklist

### **Customer Testing**
- [ ] Track order with valid order number
- [ ] View all tracking steps
- [ ] Check estimated delivery date is accurate
- [ ] Verify tracking number displayed
- [ ] Test real-time updates (status changes)
- [ ] Try to track another customer's order (should fail)
- [ ] Track order without authentication (should fail)

### **Employee Testing**
- [ ] View order with shipment details
- [ ] See Pathao integration status
- [ ] Check barcode tracking information
- [ ] View multiple store shipments
- [ ] Track order from any store
- [ ] View payment and fulfillment status

### **Multi-Store Testing**
- [ ] Track order with multiple shipments
- [ ] View status of each store's shipment
- [ ] Check different estimated deliveries
- [ ] Verify item breakdown per store

---

## Integration with External Services

### **Pathao Courier Integration**

The system integrates with Pathao API for:
- Creating shipments
- Getting tracking updates
- Real-time status synchronization

**Pathao Status Mapping:**

| Pathao Status | System Status |
|---------------|---------------|
| Pickup Pending | pending |
| Picked Up | shipped |
| In Transit | shipped |
| Out for Delivery | shipped |
| Delivered | completed |
| Cancelled | cancelled |
| Returned | returned |

---

## Performance Considerations

### **Caching Strategy**

- Cache tracking data for 60 seconds
- Refresh on manual request
- Use polling interval: 60 seconds

### **Optimization Tips**

1. **Lazy Load Images**: Load product images on demand
2. **Paginate History**: Limit tracking steps to recent events
3. **Mobile Optimization**: Simplify UI for mobile devices
4. **Offline Support**: Show cached data when offline

---

## Related APIs

### **Get Order Details**
```
GET /api/customer/orders/{orderNumber}
GET /api/employee/orders/{id}
```

### **Get Order List**
```
GET /api/customer/orders
GET /api/employee/orders
```

### **Cancel Order**
```
POST /api/customer/orders/{orderNumber}/cancel
POST /api/employee/orders/{id}/cancel
```

### **Shipment Details**
```
GET /api/employee/shipments/{id}
```

---

## Support Information

**For Tracking Issues:**
- Verify order number format: `ORD-YYMMDD-XXXX`
- Check authentication token validity
- Ensure order belongs to authenticated customer
- Contact support with order number for assistance

**Courier Issues:**
- Pathao tracking may have delay (up to 30 minutes)
- Status sync runs automatically every hour
- Manual sync available via employee panel

---

**Document Version:** 1.0  
**Last Updated:** January 8, 2026  
**Status:** Production Ready ✅
