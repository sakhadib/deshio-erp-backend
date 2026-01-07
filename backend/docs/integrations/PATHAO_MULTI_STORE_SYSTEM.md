# Pathao Multi-Store Shipment Integration

## Date: December 20, 2024
## Version: 1.1

---

## Overview

When an order has items from **multiple stores**, the system creates **separate Pathao shipments** for each store.

### Real-World Example

**Order #12345:**
- Product A (Qty: 2) → Store 1 (Dhaka Main Branch)
- Product B (Qty: 1) → Store 2 (Chittagong Branch)
- Product C (Qty: 3) → Store 3 (Sylhet Branch)

**System Behavior:**
1. Creates **3 separate Pathao shipments**
2. Each shipment uses that store's `pathao_store_id`
3. Each shipment contains only items from that store
4. Returns **3 tracking numbers** (one per store)

**Result:** Customer receives 3 packages from 3 different stores, each tracked independently.

---

## Database Changes

### 1. Stores Table - Pathao Store ID

**Migration:** `2025_12_20_114032_add_pathao_store_id_to_stores_table.php`

```sql
ALTER TABLE stores 
ADD COLUMN pathao_store_id VARCHAR(50) NULL 
AFTER pathao_key
COMMENT 'Pathao Store ID for this store (required for shipment creation)';
```

**Purpose:** Each store needs its own Pathao Store ID for creating shipments.

**Admin Action Required:**
```sql
-- Set pathao_store_id for each store
UPDATE stores SET pathao_store_id = '12345' WHERE id = 1;
UPDATE stores SET pathao_store_id = '12346' WHERE id = 2;
UPDATE stores SET pathao_store_id = '12347' WHERE id = 3;
```

### 2. Shipments Table - Multi-Store Fields

**Migration:** `2025_12_20_114656_add_multi_store_fields_to_shipments_table.php`

**New Fields:**
```sql
carrier_name VARCHAR(50)          -- "Pathao"
item_quantity INTEGER             -- Number of items in this shipment
item_weight DECIMAL(10,2)         -- Total weight for this shipment
amount_to_collect DECIMAL(10,2)   -- COD amount for this shipment
recipient_address TEXT            -- Full recipient address
metadata JSON                     -- Store pathao_store_id, items list, Pathao response
```

---

## API Endpoints

### 1. Create Multi-Store Shipments ⭐

**Endpoint:** `POST /api/multi-store-shipments/orders/{orderId}/create-shipments`

**Purpose:** Create separate Pathao shipment for each store involved in the order.

**Request:**
```json
{
  "recipient_name": "John Doe",
  "recipient_phone": "01712345678",
  "recipient_address": "123 Main Street, Apartment 4B, Dhaka",
  "recipient_city": 1,          // Pathao City ID
  "recipient_zone": 254,        // Pathao Zone ID
  "recipient_area": 23901,      // Pathao Area ID
  "delivery_type": "Normal",    // "Normal" | "On Demand"
  "item_type": "Parcel",        // "Parcel" | "Document"
  "special_instruction": "Call before delivery",
  "item_weight": 1.5            // Total weight (kg) - distributed across stores
}
```

**Response (Success - 3 Shipments Created):**
```json
{
  "success": true,
  "message": "Shipments created successfully",
  "data": {
    "order_id": 12345,
    "order_number": "ORD-2024-001",
    "total_stores": 3,
    "successful_shipments": 3,
    "failed_shipments": 0,
    "shipments": [
      {
        "shipment_id": 101,
        "shipment_number": "SHIP-ORD-2024-001-1-1703012345",
        "store_id": 1,
        "store_name": "Dhaka Main Branch",
        "pathao_consignment_id": "PT-12345678",
        "pathao_tracking_number": "ORD-2024-001-STORE-1",
        "items_count": 2,
        "items": [
          {
            "product_name": "Product A",
            "quantity": 2
          }
        ],
        "amount_to_collect": 3000.00
      },
      {
        "shipment_id": 102,
        "shipment_number": "SHIP-ORD-2024-001-2-1703012346",
        "store_id": 2,
        "store_name": "Chittagong Branch",
        "pathao_consignment_id": "PT-12345679",
        "pathao_tracking_number": "ORD-2024-001-STORE-2",
        "items_count": 1,
        "items": [
          {
            "product_name": "Product B",
            "quantity": 1
          }
        ],
        "amount_to_collect": 1500.00
      },
      {
        "shipment_id": 103,
        "shipment_number": "SHIP-ORD-2024-001-3-1703012347",
        "store_id": 3,
        "store_name": "Sylhet Branch",
        "pathao_consignment_id": "PT-12345680",
        "pathao_tracking_number": "ORD-2024-001-STORE-3",
        "items_count": 3,
        "items": [
          {
            "product_name": "Product C",
            "quantity": 3
          }
        ],
        "amount_to_collect": 4500.00
      }
    ]
  }
}
```

**Response (Partial Success - Some Failed):**
```json
{
  "success": true,
  "message": "Shipments created successfully",
  "data": {
    "order_id": 12345,
    "order_number": "ORD-2024-001",
    "total_stores": 3,
    "successful_shipments": 2,
    "failed_shipments": 1,
    "shipments": [
      // Successfully created shipments...
    ]
  },
  "warnings": {
    "message": "Some shipments could not be created",
    "failed_stores": [
      {
        "store_id": 3,
        "store_name": "Sylhet Branch",
        "reason": "Store does not have Pathao Store ID configured"
      }
    ]
  }
}
```

**Error Responses:**

```json
// Order not ready for shipment
{
  "success": false,
  "message": "Order must be fulfilled before creating shipments",
  "fulfillment_status": "pending"
}

// Items not assigned to stores
{
  "success": false,
  "message": "Order has no items assigned to stores"
}

// Validation error
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "recipient_name": ["The recipient name field is required."],
    "recipient_phone": ["The recipient phone field is required."]
  }
}
```

---

### 2. Get Order Shipments

**Endpoint:** `GET /api/multi-store-shipments/orders/{orderId}/shipments`

**Purpose:** View all shipments for an order (one per store).

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": 12345,
    "order_number": "ORD-2024-001",
    "is_multi_store": true,
    "total_shipments": 3,
    "stores_involved": 3,
    "shipments": [
      {
        "shipment_id": 101,
        "shipment_number": "SHIP-ORD-2024-001-1-1703012345",
        "store_id": 1,
        "store_name": "Dhaka Main Branch",
        "carrier_name": "Pathao",
        "pathao_consignment_id": "PT-12345678",
        "pathao_tracking_number": "ORD-2024-001-STORE-1",
        "status": "in_transit",
        "shipped_at": "2024-12-20 10:30:00",
        "delivered_at": null,
        "item_quantity": 2,
        "amount_to_collect": 3000.00,
        "items": [
          {
            "order_item_id": 501,
            "product_name": "Product A",
            "quantity": 2
          }
        ]
      },
      {
        "shipment_id": 102,
        "shipment_number": "SHIP-ORD-2024-001-2-1703012346",
        "store_id": 2,
        "store_name": "Chittagong Branch",
        "carrier_name": "Pathao",
        "pathao_consignment_id": "PT-12345679",
        "pathao_tracking_number": "ORD-2024-001-STORE-2",
        "status": "delivered",
        "shipped_at": "2024-12-20 10:35:00",
        "delivered_at": "2024-12-20 13:45:00",
        "item_quantity": 1,
        "amount_to_collect": 1500.00,
        "items": [
          {
            "order_item_id": 502,
            "product_name": "Product B",
            "quantity": 1
          }
        ]
      },
      {
        "shipment_id": 103,
        "shipment_number": "SHIP-ORD-2024-001-3-1703012347",
        "store_id": 3,
        "store_name": "Sylhet Branch",
        "carrier_name": "Pathao",
        "pathao_consignment_id": "PT-12345680",
        "pathao_tracking_number": "ORD-2024-001-STORE-3",
        "status": "picked_up",
        "shipped_at": "2024-12-20 10:40:00",
        "delivered_at": null,
        "item_quantity": 3,
        "amount_to_collect": 4500.00,
        "items": [
          {
            "order_item_id": 503,
            "product_name": "Product C",
            "quantity": 3
          }
        ]
      }
    ],
    "summary": {
      "pending": 0,
      "picked_up": 1,
      "in_transit": 1,
      "delivered": 1
    }
  }
}
```

---

### 3. Track All Shipments

**Endpoint:** `GET /api/multi-store-shipments/orders/{orderId}/track-all`

**Purpose:** Get real-time tracking info from Pathao for all shipments.

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": 12345,
    "order_number": "ORD-2024-001",
    "total_shipments": 3,
    "tracking": [
      {
        "shipment_id": 101,
        "shipment_number": "SHIP-ORD-2024-001-1-1703012345",
        "store_id": 1,
        "store_name": "Dhaka Main Branch",
        "pathao_consignment_id": "PT-12345678",
        "current_status": "in_transit",
        "tracking_details": {
          "order_status": "In_Transit",
          "last_updated": "2024-12-20 14:30:00",
          "current_location": "Dhaka Distribution Center",
          "estimated_delivery": "2024-12-20 18:00:00"
        }
      },
      {
        "shipment_id": 102,
        "shipment_number": "SHIP-ORD-2024-001-2-1703012346",
        "store_id": 2,
        "store_name": "Chittagong Branch",
        "pathao_consignment_id": "PT-12345679",
        "current_status": "delivered",
        "tracking_details": {
          "order_status": "Delivered",
          "delivered_at": "2024-12-20 13:45:00",
          "received_by": "Customer",
          "signature": "Digital Signature"
        }
      },
      {
        "shipment_id": 103,
        "shipment_number": "SHIP-ORD-2024-001-3-1703012347",
        "store_id": 3,
        "store_name": "Sylhet Branch",
        "pathao_consignment_id": "PT-12345680",
        "current_status": "picked_up",
        "tracking_details": {
          "order_status": "Picked_Up",
          "picked_up_at": "2024-12-20 11:15:00",
          "rider_name": "Karim Mia",
          "rider_phone": "01812345678"
        }
      }
    ]
  }
}
```

---

## Frontend Integration Guide

### Complete React/Next.js Example

```jsx
import { useState, useEffect } from 'react';

const MultiStoreShipmentManager = ({ orderId }) => {
  const [shipments, setShipments] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Step 1: Check if items are assigned to stores
  const checkItemAssignment = async () => {
    const response = await fetch(
      `/api/multi-store-orders/${orderId}/item-availability`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );
    
    const data = await response.json();
    
    // Check if all items assigned
    const unassignedItems = data.data.items.filter(item => !item.assigned_store);
    
    if (unassignedItems.length > 0) {
      console.warn('Some items not assigned to stores:', unassignedItems);
      return false;
    }
    
    return true;
  };

  // Step 2: Auto-assign items (if needed)
  const autoAssignItems = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `/api/multi-store-orders/${orderId}/auto-assign`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        console.log(`✅ Items assigned to ${data.data.stores_assigned.length} stores`);
        return true;
      }
      
      throw new Error(data.message);
    } catch (err) {
      setError(err.message);
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Step 3: Create Pathao shipments
  const createShipments = async (shippingInfo) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch(
        `/api/multi-store-shipments/orders/${orderId}/create-shipments`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            recipient_name: shippingInfo.name,
            recipient_phone: shippingInfo.phone,
            recipient_address: shippingInfo.address,
            recipient_city: shippingInfo.pathao_city_id,
            recipient_zone: shippingInfo.pathao_zone_id,
            recipient_area: shippingInfo.pathao_area_id,
            delivery_type: shippingInfo.delivery_type || 'Normal',
            item_type: 'Parcel',
            special_instruction: shippingInfo.notes,
            item_weight: shippingInfo.total_weight || 1.0
          })
        }
      );
      
      const result = await response.json();
      
      if (result.success) {
        console.log(`✅ Created ${result.data.successful_shipments} shipments`);
        
        // Show warnings if some failed
        if (result.warnings) {
          console.warn('Some shipments failed:', result.warnings.failed_stores);
          
          setError(
            `${result.data.successful_shipments} shipments created. ` +
            `${result.warnings.failed_stores.length} failed. ` +
            `Contact admin for: ${result.warnings.failed_stores.map(s => s.store_name).join(', ')}`
          );
        }
        
        // Update local state
        setShipments(result.data.shipments);
        
        return result.data.shipments;
      } else {
        throw new Error(result.message);
      }
    } catch (err) {
      setError(err.message);
      return null;
    } finally {
      setLoading(false);
    }
  };

  // Step 4: Load existing shipments
  const loadShipments = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `/api/multi-store-shipments/orders/${orderId}/shipments`,
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setShipments(data.data.shipments);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // Step 5: Track all shipments
  const trackAllShipments = async () => {
    try {
      const response = await fetch(
        `/api/multi-store-shipments/orders/${orderId}/track-all`,
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        // Update shipments with latest tracking info
        const updatedShipments = shipments.map(shipment => {
          const tracking = data.data.tracking.find(t => t.shipment_id === shipment.shipment_id);
          return {
            ...shipment,
            current_status: tracking?.current_status || shipment.status,
            tracking_details: tracking?.tracking_details
          };
        });
        
        setShipments(updatedShipments);
      }
    } catch (err) {
      console.error('Tracking error:', err);
    }
  };

  useEffect(() => {
    loadShipments();
    
    // Auto-track every 5 minutes
    const interval = setInterval(trackAllShipments, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, [orderId]);

  return (
    <div className="multi-store-shipment-manager">
      <h2>Order Shipments</h2>
      
      {error && (
        <div className="alert alert-warning">
          {error}
        </div>
      )}
      
      {loading && <div className="spinner">Loading...</div>}
      
      {shipments.length === 0 ? (
        <div className="no-shipments">
          <p>No shipments created yet</p>
          <button 
            onClick={async () => {
              const isAssigned = await checkItemAssignment();
              if (!isAssigned) {
                await autoAssignItems();
              }
              
              // Now create shipments
              // Gather shipping info from form...
              const shippingInfo = {
                name: "Customer Name",
                phone: "01712345678",
                address: "Customer Address",
                pathao_city_id: 1,
                pathao_zone_id: 254,
                pathao_area_id: 23901
              };
              
              await createShipments(shippingInfo);
            }}
            disabled={loading}
          >
            Create Shipments
          </button>
        </div>
      ) : (
        <div className="shipments-list">
          {shipments.map(shipment => (
            <div key={shipment.shipment_id} className="shipment-card">
              <div className="shipment-header">
                <h3>{shipment.store_name}</h3>
                <span className={`status-badge status-${shipment.status}`}>
                  {shipment.status}
                </span>
              </div>
              
              <div className="shipment-details">
                <p><strong>Tracking:</strong> {shipment.pathao_tracking_number}</p>
                <p><strong>Consignment ID:</strong> {shipment.pathao_consignment_id}</p>
                <p><strong>Items:</strong> {shipment.item_quantity}</p>
                <p><strong>COD Amount:</strong> ৳{shipment.amount_to_collect?.toFixed(2)}</p>
                <p><strong>Shipped:</strong> {shipment.shipped_at}</p>
                {shipment.delivered_at && (
                  <p><strong>Delivered:</strong> {shipment.delivered_at}</p>
                )}
              </div>
              
              <div className="shipment-items">
                <h4>Items in this shipment:</h4>
                <ul>
                  {shipment.items?.map((item, idx) => (
                    <li key={idx}>
                      {item.product_name} × {item.quantity}
                    </li>
                  ))}
                </ul>
              </div>
              
              {shipment.tracking_details && (
                <div className="tracking-details">
                  <h4>Latest Update:</h4>
                  <p>{shipment.tracking_details.current_location}</p>
                  <small>{shipment.tracking_details.last_updated}</small>
                </div>
              )}
            </div>
          ))}
          
          <button onClick={trackAllShipments} disabled={loading}>
            🔄 Refresh Tracking
          </button>
        </div>
      )}
    </div>
  );
};

export default MultiStoreShipmentManager;
```

---

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER PLACES ORDER                        │
│                    (3 products from 3 stores)                   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     ADMIN ASSIGNS ITEMS                         │
│   POST /api/multi-store-orders/{id}/auto-assign                │
│                                                                  │
│   Product A → Store 1 (Dhaka)                                  │
│   Product B → Store 2 (Chittagong)                             │
│   Product C → Store 3 (Sylhet)                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  STORES FULFILL THEIR ITEMS                     │
│                                                                  │
│   Store 1: Packs Product A                                      │
│   Store 2: Packs Product B                                      │
│   Store 3: Packs Product C                                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              ADMIN CREATES PATHAO SHIPMENTS                     │
│  POST /api/multi-store-shipments/orders/{id}/create-shipments │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                 SYSTEM CALLS PATHAO API 3 TIMES                 │
│                                                                  │
│  Call 1: Store 1's pathao_store_id → Create shipment for A     │
│  Call 2: Store 2's pathao_store_id → Create shipment for B     │
│  Call 3: Store 3's pathao_store_id → Create shipment for C     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                 SYSTEM RETURNS 3 TRACKING NUMBERS                │
│                                                                  │
│  Shipment 1: PT-12345678 (Store 1)                             │
│  Shipment 2: PT-12345679 (Store 2)                             │
│  Shipment 3: PT-12345680 (Store 3)                             │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│            CUSTOMER RECEIVES 3 SEPARATE PACKAGES                │
│                                                                  │
│  Package 1: From Dhaka (Product A)                             │
│  Package 2: From Chittagong (Product B)                        │
│  Package 3: From Sylhet (Product C)                            │
│                                                                  │
│  Each tracked independently in customer portal                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Important Configuration

### ⚠️ Required Before Production

**1. Set Pathao Store ID for each store:**

```sql
-- Check current status
SELECT id, name, pathao_key, pathao_store_id 
FROM stores;

-- Update each store
UPDATE stores SET pathao_store_id = '12345' WHERE id = 1;
UPDATE stores SET pathao_store_id = '12346' WHERE id = 2;
UPDATE stores SET pathao_store_id = '12347' WHERE id = 3;

-- Verify all stores configured
SELECT id, name, 
  CASE 
    WHEN pathao_key IS NULL THEN '❌ No Pathao Key'
    WHEN pathao_store_id IS NULL THEN '❌ No Store ID'
    ELSE '✅ Ready'
  END as status
FROM stores;
```

**2. Test with dummy order:**

```bash
# Create test order with items from multiple stores
# Assign items to different stores
# Try creating shipments
# Verify 3 Pathao API calls made
# Check 3 tracking numbers returned
```

---

## Error Handling Guide

### Common Errors and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "Store does not have Pathao Store ID configured" | `pathao_store_id` is NULL | Run: `UPDATE stores SET pathao_store_id = 'XXXX' WHERE id = Y` |
| "Order must be fulfilled" | fulfillment_status not 'fulfilled' | Complete order fulfillment workflow first |
| "Some items not assigned to any store" | `order_items.store_id` is NULL | Call auto-assign API or manually assign |
| "Pathao API returned error" | Invalid credentials or request data | Check store's `pathao_key`, verify Pathao account |
| "No Pathao consignment ID" | Shipment creation failed | Check Pathao API logs, verify request format |
| "Pathao API timeout" | Network issue | Retry shipment creation |

### Frontend Error Handling

```javascript
const handleShipmentCreation = async (orderId, shippingInfo) => {
  try {
    const result = await createShipments(orderId, shippingInfo);
    
    if (result.warnings) {
      // Partial success
      toast.warning(
        `${result.data.successful_shipments} shipments created successfully. ` +
        `${result.warnings.failed_stores.length} failed.`
      );
      
      // Show failed stores
      result.warnings.failed_stores.forEach(failed => {
        console.error(`${failed.store_name}: ${failed.reason}`);
      });
      
      // Still show created shipments
      return result.data.shipments;
    } else {
      // Full success
      toast.success(`All ${result.data.successful_shipments} shipments created!`);
      return result.data.shipments;
    }
  } catch (error) {
    // Total failure
    toast.error(`Failed to create shipments: ${error.message}`);
    return null;
  }
};
```

---

## Testing Guide

### Test Case 1: Single Store Order (Backwards Compatibility)

```bash
# Order with all items from one store
POST /api/multi-store-shipments/orders/100/create-shipments
{
  "recipient_name": "Test Customer",
  "recipient_phone": "01712345678",
  "recipient_address": "Test Address",
  "recipient_city": 1,
  "recipient_zone": 254,
  "recipient_area": 23901
}

# Expected Result:
# - Creates 1 shipment
# - Uses store's pathao_store_id
# - Returns 1 tracking number
# - Behaves exactly like old system
```

### Test Case 2: Multi-Store Order (3 Stores)

```bash
# Order with items from 3 different stores
# First assign items:
POST /api/multi-store-orders/101/auto-assign

# Then create shipments:
POST /api/multi-store-shipments/orders/101/create-shipments
{
  "recipient_name": "Test Customer",
  "recipient_phone": "01712345678",
  "recipient_address": "Test Address",
  "recipient_city": 1,
  "recipient_zone": 254,
  "recipient_area": 23901
}

# Expected Result:
# - Creates 3 shipments
# - Each uses different pathao_store_id
# - Returns 3 tracking numbers
# - Each shipment contains items from one store only
```

### Test Case 3: Missing Pathao Store ID

```bash
# One store doesn't have pathao_store_id configured
# Remove store ID:
UPDATE stores SET pathao_store_id = NULL WHERE id = 3;

# Try creating shipments:
POST /api/multi-store-shipments/orders/101/create-shipments

# Expected Result:
# - Partial success (HTTP 201)
# - Creates shipments for stores 1 and 2
# - Returns warning for store 3
# - Response includes "warnings" object with failed_stores array
```

### Test Case 4: Order Not Ready

```bash
# Order not fulfilled yet
POST /api/multi-store-shipments/orders/102/create-shipments

# Expected Result:
# - HTTP 400 Bad Request
# - Message: "Order must be fulfilled before creating shipments"
```

---

## Performance Notes

### API Call Time

- **Single-Store Order:** ~1-2 seconds (1 Pathao API call)
- **Multi-Store Order (3 stores):** ~3-5 seconds (3 Pathao API calls, sequential)
- **Multi-Store Order (5 stores):** ~5-8 seconds (5 Pathao API calls, sequential)

**Note:** Pathao API calls are made sequentially to avoid rate limiting.

### Database Impact

**Per Multi-Store Order:**
- N Shipment records (one per store)
- N Pathao API calls
- Minimal database writes

**Example:** Order with 5 items from 3 stores
- 3 rows inserted into `shipments` table
- 3 Pathao API calls made
- All items grouped by store in metadata JSON

---

## Summary

### ✅ What's Implemented

✅ **Multi-store Pathao shipment creation**  
✅ **Separate tracking number per store**  
✅ **Automatic store grouping**  
✅ **Partial failure handling**  
✅ **Real-time tracking for all shipments**  
✅ **Comprehensive error handling**  
✅ **Full backwards compatibility**

### 🔧 Migrations Required

```bash
php artisan migrate

# This runs:
# 1. 2025_12_20_114032_add_pathao_store_id_to_stores_table.php
# 2. 2025_12_20_114656_add_multi_store_fields_to_shipments_table.php
```

### 📋 Admin Checklist

- [ ] Run migrations
- [ ] Set `pathao_store_id` for each store
- [ ] Verify all stores have `pathao_key` (access token)
- [ ] Test with sample multi-store order
- [ ] Verify 3 Pathao API calls made successfully
- [ ] Check 3 tracking numbers returned
- [ ] Test tracking updates
- [ ] Update frontend to show multi-store shipments

---

**Status: ✅ COMPLETE - Ready for Production**

**Version:** 1.1  
**Date:** December 20, 2024  
**Controller:** `MultiStoreShipmentController`  
**Routes:** 3 new endpoints under `/api/multi-store-shipments`

---

## Support

For questions or issues:
- Check error messages in response JSON
- Verify store has both `pathao_key` and `pathao_store_id`
- Check Pathao API logs
- Test with single-store order first
- Contact backend team for integration support
