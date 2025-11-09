# Pathao Courier Integration - Complete Delivery Management

## Overview

This system integrates **Pathao Courier** for seamless delivery management across all sales channels:

1. **POS with Delivery** - Customer buys in-store but wants delivery
2. **POS without Delivery** - Customer takes product immediately (no shipment needed)
3. **Social Commerce / Phone Orders** - Create shipment, send to Pathao when ready
4. **E-commerce** - Create shipment, send to Pathao when ready

## Key Features

✅ **Manual vs Automatic Pathao Submission** - Create shipments first, send to Pathao when ready  
✅ **Bulk Operations** - Send multiple shipments to Pathao at once  
✅ **Status Sync** - Sync delivery status from Pathao  
✅ **Delivery Type Support** - Normal (48hrs) or Express (12hrs)  
✅ **COD Integration** - Cash on Delivery amount tracking  
✅ **Area Lookup** - Get Pathao cities, zones, and areas  

---

## Sales Channel Workflows

### Channel 1: POS with Delivery (Case 1)

**Scenario**: Customer buys iPhone in store but wants home delivery

```http
# Step 1: Create order (POS counter sale)
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "Karim Ahmed",
    "phone": "01712345678",
    "address": "House 12, Road 5, Gulshan-2, Dhaka"
  },
  "items": [{
    "product_id": 1,
    "batch_id": 5,
    "quantity": 1,
    "unit_price": 75000.00
  }],
  "shipping_address": {
    "name": "Karim Ahmed",
    "phone": "01712345678",
    "street": "House 12, Road 5",
    "area": "Gulshan-2",
    "city": "Dhaka",
    "postal_code": "1212",
    "pathao_city_id": 1,
    "pathao_zone_id": 10,
    "pathao_area_id": 52
  },
  "payment": {
    "payment_method_id": 1,  // Full payment at counter
    "amount": 75000.00,
    "payment_type": "full"
  }
}

Response: { "order_id": 123 }

# Step 2: Complete order (reduce inventory)
PATCH /api/orders/123/complete

# Step 3: Create shipment
POST /api/shipments
{
  "order_id": 123,
  "delivery_type": "home_delivery",  // or "express"
  "package_weight": 0.5,
  "special_instructions": "Call before delivery",
  "send_to_pathao": true  // Immediately send to Pathao
}

Response:
{
  "success": true,
  "message": "Shipment created and sent to Pathao successfully",
  "data": {
    "id": 1,
    "shipment_number": "SHP-20250104-ABC123",
    "pathao_consignment_id": "P123456789",
    "pathao_tracking_number": "INV-987654",
    "status": "pickup_requested",
    "delivery_fee": "60.00"
  }
}
```

✅ **Customer paid at counter**  
✅ **Inventory reduced immediately**  
✅ **Pathao picks up and delivers**

---

### Channel 2: POS without Delivery (Case 2)

**Scenario**: Customer buys and takes product immediately

```http
# Step 1: Create order
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "customer": {
    "name": "Sarah Khan",
    "phone": "01812345678"
  },
  "items": [{
    "product_id": 2,
    "batch_id": 8,
    "quantity": 1,
    "unit_price": 45000.00
  }],
  "payment": {
    "payment_method_id": 1,
    "amount": 45000.00,
    "payment_type": "full"
  }
}

# Step 2: Complete order
PATCH /api/orders/1/complete

# NO SHIPMENT NEEDED - Customer left with product! ✅
```

---

### Channel 3: Phone Order (Social Commerce)

**Scenario**: Customer calls, orders product, delivery needed

```http
# Step 1: Create order (employee takes phone call)
POST /api/orders
{
  "order_type": "social_commerce",
  "store_id": 1,
  "customer": {
    "name": "Ali Hassan",
    "phone": "01987654321",
    "address": "Flat 5B, Banani, Dhaka"
  },
  "items": [{
    "product_id": 3,
    "batch_id": 12,
    "quantity": 1,
    "unit_price": 35000.00
  }],
  "shipping_address": {
    "name": "Ali Hassan",
    "phone": "01987654321",
    "street": "Flat 5B, Road 11",
    "area": "Banani",
    "city": "Dhaka",
    "postal_code": "1213",
    "pathao_city_id": 1,
    "pathao_zone_id": 12,
    "pathao_area_id": 60
  },
  "installment_plan": {
    "total_installments": 3,
    "installment_amount": 11700.00,
    "start_date": "2025-01-15"
  }
}

# Step 2: Create shipment (DON'T send to Pathao yet)
POST /api/shipments
{
  "order_id": 1,
  "delivery_type": "home_delivery",
  "package_weight": 1.0,
  "send_to_pathao": false  // WAIT until ready
}

# Step 3: Customer pays first installment
POST /api/orders/1/payments/simple
{
  "payment_method_id": 2,  // bKash
  "amount": 11700.00,
  "payment_type": "installment"
}

# Step 4: Complete order (reduce inventory)
PATCH /api/orders/1/complete

# Step 5: NOW send to Pathao (manually when ready)
POST /api/shipments/1/send-to-pathao

Response:
{
  "success": true,
  "data": {
    "pathao_consignment_id": "P123456790",
    "status": "pickup_requested",
    "delivery_fee": "60.00"
  }
}
```

✅ **Order created immediately**  
✅ **Shipment created but NOT sent to Pathao**  
✅ **Wait for payment/confirmation**  
✅ **Manually send to Pathao when ready**

---

### Channel 4: E-commerce

**Scenario**: Customer orders from website

```http
# Step 1: Create order (customer places online)
POST /api/orders
{
  "order_type": "ecommerce",
  "store_id": 1,  // Main warehouse
  "customer_id": 50,  // Registered customer
  "items": [{
    "product_id": 5,
    "batch_id": 20,
    "quantity": 2,
    "unit_price": 12000.00
  }],
  "shipping_address": {
    "name": "Fatima Rahman",
    "phone": "01612345678",
    "street": "123 Main Street",
    "area": "Chittagong",
    "city": "Chittagong",
    "postal_code": "4000",
    "pathao_city_id": 2,
    "pathao_zone_id": 45,
    "pathao_area_id": 200
  },
  "payment": {
    "payment_method_id": 5,  // Online gateway
    "amount": 24000.00,
    "payment_type": "full"
  }
}

# Step 2: Create shipment (after payment confirmation)
POST /api/shipments
{
  "order_id": 1,
  "delivery_type": "express",  // Fast delivery
  "package_weight": 2.0,
  "send_to_pathao": false  // Create first, send later
}

# Step 3: Complete order
PATCH /api/orders/1/complete

# Step 4: Send to Pathao when ready to ship
POST /api/shipments/1/send-to-pathao
```

---

## Bulk Operations

### Bulk Send to Pathao

**Scenario**: You have 50 pending shipments, send all to Pathao at once

```http
# Get pending shipments (not sent to Pathao yet)
GET /api/shipments?pending_pathao=true

Response:
{
  "data": [
    {"id": 1, "shipment_number": "SHP-001", "order_id": 123},
    {"id": 2, "shipment_number": "SHP-002", "order_id": 124},
    {"id": 3, "shipment_number": "SHP-003", "order_id": 125},
    ...
  ]
}

# Send all to Pathao in one request
POST /api/shipments/bulk-send-to-pathao
{
  "shipment_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
}

Response:
{
  "success": true,
  "message": "8 shipments sent successfully, 2 failed",
  "data": {
    "success": [
      {
        "shipment_id": 1,
        "shipment_number": "SHP-001",
        "pathao_consignment_id": "P123456"
      },
      {
        "shipment_id": 2,
        "shipment_number": "SHP-002",
        "pathao_consignment_id": "P123457"
      },
      ...
    ],
    "failed": [
      {
        "shipment_id": 9,
        "shipment_number": "SHP-009",
        "reason": "Already sent to Pathao"
      },
      {
        "shipment_id": 10,
        "shipment_number": "SHP-010",
        "reason": "Invalid delivery address"
      }
    ]
  }
}
```

### Bulk Status Sync

**Scenario**: Sync delivery status for all in-transit shipments

```http
# Sync all active shipments
POST /api/shipments/bulk-sync-pathao-status

# OR sync specific shipments
POST /api/shipments/bulk-sync-pathao-status
{
  "shipment_ids": [1, 2, 3, 4, 5]
}

Response:
{
  "success": true,
  "message": "5 shipments synced successfully",
  "data": {
    "success": [
      {
        "shipment_id": 1,
        "shipment_number": "SHP-001",
        "old_status": "In_transit",
        "new_status": "Delivered"
      },
      ...
    ],
    "failed": []
  }
}
```

---

## API Endpoints Reference

### Shipment Management (18 Endpoints)

```http
# List shipments
GET /api/shipments
GET /api/shipments?status=pending
GET /api/shipments?pending_pathao=true
GET /api/shipments?store_id=1
GET /api/shipments?search=SHP-001

# Get shipment details
GET /api/shipments/{id}

# Create shipment from order
POST /api/shipments
{
  "order_id": 123,
  "delivery_type": "home_delivery|express",
  "package_weight": 2.5,
  "special_instructions": "Handle with care",
  "send_to_pathao": false  // Default: false (manual send later)
}

# Send shipment to Pathao
POST /api/shipments/{id}/send-to-pathao

# Sync Pathao status
GET /api/shipments/{id}/sync-pathao-status

# Cancel shipment
PATCH /api/shipments/{id}/cancel
{
  "reason": "Customer cancelled"
}

# Bulk operations
POST /api/shipments/bulk-send-to-pathao
{
  "shipment_ids": [1, 2, 3, 4, 5]
}

POST /api/shipments/bulk-sync-pathao-status
{
  "shipment_ids": [1, 2, 3]  // Optional: sync all if omitted
}

# Statistics
GET /api/shipments/statistics
GET /api/shipments/statistics?store_id=1
```

### Pathao Helper Endpoints (5 Endpoints)

```http
# Get Pathao cities
GET /api/shipments/pathao/cities

Response:
{
  "data": [
    {"city_id": 1, "city_name": "Dhaka"},
    {"city_id": 2, "city_name": "Chittagong"},
    ...
  ]
}

# Get Pathao zones for city
GET /api/shipments/pathao/zones/{cityId}

Response:
{
  "data": [
    {"zone_id": 10, "zone_name": "Gulshan"},
    {"zone_id": 12, "zone_name": "Banani"},
    ...
  ]
}

# Get Pathao areas for zone
GET /api/shipments/pathao/areas/{zoneId}

Response:
{
  "data": [
    {"area_id": 52, "area_name": "Gulshan-2"},
    {"area_id": 60, "area_name": "Banani DOHS"},
    ...
  ]
}

# Get Pathao stores
GET /api/shipments/pathao/stores

# Create Pathao store
POST /api/shipments/pathao/stores
{
  "name": "Main Store",
  "contact_name": "John Doe",
  "contact_number": "01712345678",
  "address": "123 Main St",
  "secondary_contact": "01812345678",
  "city_id": 1,
  "zone_id": 10,
  "area_id": 52
}
```

---

## Configuration

### 1. Environment Variables

Add to `.env`:

```env
# Pathao Configuration
PATHAO_SANDBOX=true  # Use false for production
PATHAO_CLIENT_ID=your_client_id
PATHAO_CLIENT_SECRET=your_client_secret
PATHAO_USERNAME=your_username
PATHAO_PASSWORD=your_password
```

### 2. Store Configuration

Each store needs Pathao store ID:

```sql
-- Add pathao_store_id to stores table
ALTER TABLE stores ADD COLUMN pathao_store_id INT NULL;

-- Update your store
UPDATE stores SET pathao_store_id = 1 WHERE id = 1;
```

### 3. Customer Address Format

When creating orders with delivery, include Pathao area IDs:

```json
{
  "shipping_address": {
    "name": "Customer Name",
    "phone": "01712345678",
    "street": "House 12, Road 5",
    "area": "Gulshan-2",
    "city": "Dhaka",
    "postal_code": "1212",
    "pathao_city_id": 1,      // Get from /api/shipments/pathao/cities
    "pathao_zone_id": 10,     // Get from /api/shipments/pathao/zones/1
    "pathao_area_id": 52      // Get from /api/shipments/pathao/areas/10
  }
}
```

---

## Complete Use Cases

### Use Case 1: POS with Immediate Delivery

```bash
# 1. Customer pays at counter
POST /api/orders {...}

# 2. Complete order
PATCH /api/orders/1/complete

# 3. Create and send shipment immediately
POST /api/shipments
{
  "order_id": 1,
  "delivery_type": "express",
  "send_to_pathao": true  # ✅ Immediate
}

✅ Pathao receives request
✅ Rider picks up from store
✅ Delivers to customer
```

### Use Case 2: Phone Order with Manual Pathao Submission

```bash
# 1. Employee takes phone order
POST /api/orders {...}

# 2. Create shipment (don't send yet)
POST /api/shipments
{
  "order_id": 1,
  "send_to_pathao": false  # ✅ Wait
}

# 3. Wait for customer confirmation/payment
POST /api/orders/1/payments/simple {...}

# 4. Complete order
PATCH /api/orders/1/complete

# 5. Manager manually sends to Pathao
POST /api/shipments/1/send-to-pathao

✅ Flexible workflow
✅ Confirm before shipping
```

### Use Case 3: Bulk Daily Shipments

```bash
# Morning: Multiple orders created
POST /api/orders {...}  # Order 1
POST /api/orders {...}  # Order 2
POST /api/orders {...}  # Order 3
...

# Morning: Create shipments for all
POST /api/shipments {"order_id": 1, "send_to_pathao": false}
POST /api/shipments {"order_id": 2, "send_to_pathao": false}
POST /api/shipments {"order_id": 3, "send_to_pathao": false}
...

# Afternoon: Bulk send to Pathao (after all orders confirmed)
POST /api/shipments/bulk-send-to-pathao
{
  "shipment_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
}

✅ Efficient batch processing
✅ Single Pathao pickup for all
```

### Use Case 4: E-commerce Auto-Ship

```bash
# Payment confirmed webhook
POST /api/orders {...}  # Create order
PATCH /api/orders/1/complete  # Reduce inventory

# Create and auto-send shipment
POST /api/shipments
{
  "order_id": 1,
  "delivery_type": "home_delivery",
  "send_to_pathao": true  # ✅ Automatic
}

✅ Fully automated
✅ Fast fulfillment
```

---

## Shipment Status Flow

```
Order Created
    ↓
Shipment Created (status: pending)
    ↓
[Option A: send_to_pathao=true immediately]
[Option B: Manual POST /shipments/{id}/send-to-pathao later]
    ↓
Sent to Pathao (status: pickup_requested)
    ↓
Pathao Rider Picks Up (status: picked_up)
    ↓
In Transit (status: in_transit)
    ↓
Delivered (status: delivered)

OR

Returned (status: returned)
Cancelled (status: cancelled)
```

---

## Delivery Type Mapping

```json
{
  "home_delivery": {
    "pathao_delivery_type": 48,  // Normal (48 hours)
    "description": "Standard delivery"
  },
  "express": {
    "pathao_delivery_type": 12,  // Express (12 hours)
    "description": "Same-day delivery"
  }
}
```

---

## COD (Cash on Delivery)

```http
# Create shipment with COD
POST /api/shipments
{
  "order_id": 123,
  "delivery_type": "home_delivery",
  ...
}

# System automatically sets:
# - cod_amount = order.outstanding_amount (if not fully paid)
# - cod_amount = 0 (if order fully paid)
```

**Example:**
- Order Total: ৳50,000
- Paid at Counter: ৳20,000
- COD Amount: ৳30,000 ✅

---

## Statistics & Reports

```http
GET /api/shipments/statistics

Response:
{
  "total_shipments": 450,
  "pending_shipments": 20,
  "in_transit_shipments": 85,
  "delivered_shipments": 320,
  "returned_shipments": 15,
  "cancelled_shipments": 10,
  "pending_pathao_submissions": 12,  // Created but not sent to Pathao
  "in_transit_with_pathao": 85,
  "total_delivery_fee": "27000.00",
  "total_cod_amount": "150000.00",
  "average_delivery_fee": "60.00"
}
```

---

## Best Practices

### For POS Sales
1. **With Delivery**: Create order → Complete → Create shipment → Send to Pathao immediately
2. **Without Delivery**: Create order → Complete → No shipment needed

### For Phone/Social Orders
1. Create order
2. Create shipment with `send_to_pathao: false`
3. Wait for payment confirmation
4. Complete order
5. Manually send to Pathao when ready

### For E-commerce
1. Create order (after payment gateway confirmation)
2. Create shipment with `send_to_pathao: false`
3. Complete order (reduce inventory)
4. Auto-send to Pathao OR manual send

### For Bulk Processing
1. Create all orders throughout the day
2. Create shipments with `send_to_pathao: false`
3. End of day: Use bulk send operation
4. One Pathao pickup for all shipments

---

## Error Handling

### Common Errors

```json
// Shipment already sent to Pathao
{
  "success": false,
  "message": "Shipment already sent to Pathao"
}

// Invalid Pathao area IDs
{
  "success": false,
  "message": "Failed to send to Pathao: Invalid area_id"
}

// Order already has active shipment
{
  "success": false,
  "message": "Order already has an active shipment"
}

// Cannot send non-pending shipment
{
  "success": false,
  "message": "Only pending shipments can be sent to Pathao"
}
```

---

## Testing Checklist

- [ ] Create POS order with delivery (immediate Pathao send)
- [ ] Create POS order without delivery (no shipment)
- [ ] Create phone order with manual Pathao send later
- [ ] Create e-commerce order with auto Pathao send
- [ ] Bulk send 10 shipments to Pathao
- [ ] Bulk sync status for all in-transit shipments
- [ ] Get Pathao cities, zones, areas
- [ ] Create Pathao store
- [ ] Cancel shipment
- [ ] Sync individual shipment status
- [ ] Test COD calculation
- [ ] Test delivery fee calculation
- [ ] Filter pending Pathao submissions

---

**System Ready**: Complete Pathao courier integration with flexible manual/automatic workflows and bulk operations! 🚚📦
