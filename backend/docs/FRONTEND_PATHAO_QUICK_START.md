# Multi-Store Pathao Integration - Frontend Quick Start

## 🚀 Quick Start Guide for Frontend Developers

---

## 3 New API Endpoints

### 1. Create Shipments (Main API)
```
POST /api/multi-store-shipments/orders/{orderId}/create-shipments
```

### 2. Get Shipments
```
GET /api/multi-store-shipments/orders/{orderId}/shipments
```

### 3. Track Shipments
```
GET /api/multi-store-shipments/orders/{orderId}/track-all
```

---

## Complete Flow (5 Steps)

### Step 1: Check if Items Assigned to Stores
```javascript
const response = await fetch(
  `/api/multi-store-orders/${orderId}/item-availability`,
  { headers: { 'Authorization': `Bearer ${token}` } }
);
const data = await response.json();

const needsAssignment = data.data.items.some(item => !item.assigned_store);
```

### Step 2: Auto-Assign Items (if needed)
```javascript
if (needsAssignment) {
  await fetch(
    `/api/multi-store-orders/${orderId}/auto-assign`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );
}
```

### Step 3: Create Pathao Shipments ⭐
```javascript
const response = await fetch(
  `/api/multi-store-shipments/orders/${orderId}/create-shipments`,
  {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      recipient_name: "Customer Name",
      recipient_phone: "01712345678",
      recipient_address: "Full Address",
      recipient_city: 1,         // Pathao City ID
      recipient_zone: 254,       // Pathao Zone ID
      recipient_area: 23901,     // Pathao Area ID
      delivery_type: "Normal",   // or "On Demand"
      item_type: "Parcel",
      special_instruction: "Call before delivery",
      item_weight: 1.5           // kg
    })
  }
);

const result = await response.json();

// SUCCESS: result.data.shipments contains all created shipments
// Each shipment has: store_name, pathao_tracking_number, items[]
```

### Step 4: Display Shipments
```javascript
const response = await fetch(
  `/api/multi-store-shipments/orders/${orderId}/shipments`,
  { headers: { 'Authorization': `Bearer ${token}` } }
);

const data = await response.json();

// data.data.shipments = array of shipments (one per store)
// data.data.is_multi_store = true/false
// data.data.stores_involved = number of stores
```

### Step 5: Track in Real-Time
```javascript
const response = await fetch(
  `/api/multi-store-shipments/orders/${orderId}/track-all`,
  { headers: { 'Authorization': `Bearer ${token}` } }
);

const data = await response.json();

// data.data.tracking = array with latest Pathao tracking info
// Each has: current_status, tracking_details
```

---

## Response Examples

### Create Shipments Response (Success)
```json
{
  "success": true,
  "message": "Shipments created successfully",
  "data": {
    "order_id": 12345,
    "total_stores": 3,
    "successful_shipments": 3,
    "shipments": [
      {
        "shipment_id": 101,
        "store_name": "Dhaka Main Branch",
        "pathao_tracking_number": "ORD-2024-001-STORE-1",
        "items": [
          { "product_name": "Product A", "quantity": 2 }
        ],
        "amount_to_collect": 3000.00
      },
      {
        "shipment_id": 102,
        "store_name": "Chittagong Branch",
        "pathao_tracking_number": "ORD-2024-001-STORE-2",
        "items": [
          { "product_name": "Product B", "quantity": 1 }
        ],
        "amount_to_collect": 1500.00
      },
      {
        "shipment_id": 103,
        "store_name": "Sylhet Branch",
        "pathao_tracking_number": "ORD-2024-001-STORE-3",
        "items": [
          { "product_name": "Product C", "quantity": 3 }
        ],
        "amount_to_collect": 4500.00
      }
    ]
  }
}
```

### Partial Success (Some Failed)
```json
{
  "success": true,
  "data": {
    "successful_shipments": 2,
    "failed_shipments": 1,
    "shipments": [ /* Created shipments */ ]
  },
  "warnings": {
    "message": "Some shipments could not be created",
    "failed_stores": [
      {
        "store_name": "Sylhet Branch",
        "reason": "Store does not have Pathao Store ID configured"
      }
    ]
  }
}
```

---

## React Component Example

```jsx
const ShipmentButton = ({ orderId, shippingInfo }) => {
  const [loading, setLoading] = useState(false);
  const [shipments, setShipments] = useState([]);
  const [error, setError] = useState(null);

  const handleCreateShipments = async () => {
    setLoading(true);
    setError(null);

    try {
      // Step 1: Auto-assign items
      await fetch(`/api/multi-store-orders/${orderId}/auto-assign`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      // Step 2: Create shipments
      const response = await fetch(
        `/api/multi-store-shipments/orders/${orderId}/create-shipments`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            recipient_name: shippingInfo.name,
            recipient_phone: shippingInfo.phone,
            recipient_address: shippingInfo.address,
            recipient_city: shippingInfo.pathao_city_id,
            recipient_zone: shippingInfo.pathao_zone_id,
            recipient_area: shippingInfo.pathao_area_id,
            delivery_type: 'Normal',
            item_type: 'Parcel',
            item_weight: 1.5
          })
        }
      );

      const result = await response.json();

      if (result.success) {
        setShipments(result.data.shipments);
        
        // Show warning if partial success
        if (result.warnings) {
          setError(
            `${result.data.successful_shipments} shipments created. ` +
            `${result.warnings.failed_stores.length} failed.`
          );
        } else {
          alert(`✅ ${result.data.successful_shipments} shipments created!`);
        }
      } else {
        setError(result.message);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <button onClick={handleCreateShipments} disabled={loading}>
        {loading ? 'Creating...' : 'Create Pathao Shipments'}
      </button>

      {error && <div className="alert alert-warning">{error}</div>}

      {shipments.length > 0 && (
        <div className="shipments-list">
          <h3>Created Shipments:</h3>
          {shipments.map(s => (
            <div key={s.shipment_id} className="shipment-card">
              <h4>{s.store_name}</h4>
              <p>Tracking: {s.pathao_tracking_number}</p>
              <p>Items: {s.items_count}</p>
              <p>COD: ৳{s.amount_to_collect}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
```

---

## UI/UX Recommendations

### 1. Show Multi-Store Warning
```jsx
{isMultiStore && (
  <div className="alert alert-info">
    ℹ️ This order will be shipped from {storeCount} different locations.
    You will receive {storeCount} separate tracking numbers.
  </div>
)}
```

### 2. Display Store-Specific Shipments
```jsx
<div className="shipment-timeline">
  {shipments.map(shipment => (
    <div className="timeline-item">
      <div className="store-badge">{shipment.store_name}</div>
      <div className="tracking-number">{shipment.pathao_tracking_number}</div>
      <div className="status">{shipment.status}</div>
      <ul className="items-list">
        {shipment.items.map(item => (
          <li>{item.product_name} × {item.quantity}</li>
        ))}
      </ul>
    </div>
  ))}
</div>
```

### 3. Real-Time Status Updates
```jsx
useEffect(() => {
  const trackShipments = async () => {
    const response = await fetch(
      `/api/multi-store-shipments/orders/${orderId}/track-all`,
      { headers: { 'Authorization': `Bearer ${token}` } }
    );
    const data = await response.json();
    
    // Update UI with latest statuses
    setShipments(prevShipments =>
      prevShipments.map(shipment => {
        const tracking = data.data.tracking.find(
          t => t.shipment_id === shipment.shipment_id
        );
        return {
          ...shipment,
          status: tracking?.current_status || shipment.status
        };
      })
    );
  };

  // Track every 5 minutes
  const interval = setInterval(trackShipments, 5 * 60 * 1000);
  return () => clearInterval(interval);
}, [orderId]);
```

---

## Error Handling

### Handle Common Errors
```javascript
try {
  const response = await createShipments(orderId, shippingInfo);
  
  if (!response.ok) {
    const error = await response.json();
    
    if (error.message.includes('not fulfilled')) {
      alert('⚠️ Order must be fulfilled first!');
    } else if (error.message.includes('not assigned')) {
      alert('⚠️ Items not assigned to stores. Auto-assigning...');
      await autoAssignItems(orderId);
      // Retry
      return createShipments(orderId, shippingInfo);
    } else {
      alert(`❌ Error: ${error.message}`);
    }
  }
} catch (err) {
  console.error('Shipment creation failed:', err);
  alert('❌ Network error. Please try again.');
}
```

---

## Key Points

### ✅ Multi-Store Support
- Order with items from 3 stores → Creates 3 shipments
- Each shipment has separate tracking number
- Each tracked independently

### ✅ Backwards Compatible
- Single-store order → Creates 1 shipment (as before)
- No changes needed for existing orders

### ✅ Partial Success Handling
- Some stores may fail (e.g., missing Pathao Store ID)
- API returns both successful and failed shipments
- Show warnings to user, but don't block flow

### ✅ Real-Time Tracking
- Use track-all endpoint for live updates
- Poll every 5 minutes
- Update UI with latest status

---

## Testing Checklist

- [ ] Test single-store order (should work as before)
- [ ] Test multi-store order (3 stores)
- [ ] Verify 3 tracking numbers returned
- [ ] Test partial failure scenario
- [ ] Test tracking updates
- [ ] Test UI displays all shipments correctly
- [ ] Test error messages display properly

---

## Common Questions

**Q: How many Pathao API calls are made?**  
A: One per store. Order with 3 stores = 3 Pathao API calls.

**Q: What if one store's shipment fails?**  
A: Partial success. System creates shipments for successful stores and returns warning for failed ones.

**Q: Do I need to change existing code?**  
A: No. Single-store orders work exactly as before. Multi-store is additive.

**Q: How do I know if order is multi-store?**  
A: Check `is_multi_store` field in shipments response, or count unique `store_id` in order items.

**Q: Can customer track all shipments together?**  
A: Yes. Use `/track-all` endpoint to get all tracking info in one call.

---

**Ready to integrate? 🚀**

Full documentation: See `PATHAO_MULTI_STORE_INTEGRATION.md`

**Version:** 1.1  
**Date:** December 20, 2024
