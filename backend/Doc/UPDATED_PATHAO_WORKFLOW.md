# Updated Pathao Delivery Workflow - Store → Warehouse → Customer

## Overview

The system now properly handles the real-world workflow where:
- **Phone orders** are always created at **warehouse** (never at store)
- **POS orders requiring delivery** are dispatched to **warehouse** first, then delivered via Pathao

## Complete Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                   ORDER CREATION                            │
└─────────────────────────────────────────────────────────────┘
                          |
                          v
              ┌───────────┴────────────┐
              |                        |
        PHONE ORDER                POS ORDER
     (Social Commerce)         (Counter Sale)
              |                        |
              v                        v
    ┌──────────────────┐      ┌────────────────┐
    │ Order at         │      │ Customer wants │
    │ WAREHOUSE        │      │ delivery?      │
    │ (store_id = W)   │      └────────┬───────┘
    └──────────────────┘               |
              |                ┌───────┴────────┐
              |                |                |
              |               YES               NO
              |                |                |
              |                v                v
              |      ┌──────────────────┐  Take Product
              |      │ Order at STORE A │  No Shipment ✅
              |      │ (store_id = A)   │
              |      └──────────────────┘
              |                |
              |                v
              |      Create Dispatch
              |      (Store A → Warehouse)
              |      for_pathao_delivery = true
              |                |
              v                v
    ┌──────────────────────────────────┐
    │     Products at WAREHOUSE         │
    └──────────────────────────────────┘
                    |
                    v
          Create Shipment from Warehouse
                    |
                    v
              Send to Pathao
                    |
                    v
         Deliver to Customer ✅
```

---

## Scenario 1: Phone Order (Social Commerce)

**Customer calls store, wants product delivered**

```http
# Step 1: Create order AT WAREHOUSE
POST /api/orders
{
  "order_type": "social_commerce",
  "store_id": 1,  // 👈 WAREHOUSE ID (not store)
  "customer": {
    "name": "Ali Hassan",
    "phone": "01987654321"
  },
  "items": [{
    "product_id": 3,
    "batch_id": 12,  // Batch at warehouse
    "quantity": 1,
    "unit_price": 35000.00
  }],
  "shipping_address": {
    "name": "Ali Hassan",
    "phone": "01987654321",
    "street": "Flat 5B, Road 11",
    "area": "Banani",
    "city": "Dhaka",
    "pathao_city_id": 1,
    "pathao_zone_id": 12,
    "pathao_area_id": 60
  }
}

# Step 2: Complete order (reduce warehouse inventory)
PATCH /api/orders/123/complete

# Step 3: Create shipment from warehouse (when ready)
POST /api/shipments
{
  "order_id": 123,
  "delivery_type": "home_delivery",
  "send_to_pathao": false  // Wait for confirmation
}

# Step 4: Send to Pathao (manual when ready)
POST /api/shipments/1/send-to-pathao

✅ Product ships from warehouse to customer
```

---

## Scenario 2: POS - Customer Wants Delivery

**Customer buys at Store A but wants home delivery**

```http
# Step 1: Create order AT STORE A
POST /api/orders
{
  "order_type": "counter",
  "store_id": 2,  // 👈 STORE A ID
  "customer": {
    "name": "Karim Ahmed",
    "phone": "01712345678"
  },
  "items": [{
    "product_id": 1,
    "batch_id": 5,  // Batch at Store A
    "quantity": 1,
    "unit_price": 75000.00
  }],
  "shipping_address": {
    "name": "Karim Ahmed",
    "phone": "01712345678",
    "street": "House 12, Road 5",
    "area": "Gulshan-2",
    "city": "Dhaka",
    "pathao_city_id": 1,
    "pathao_zone_id": 10,
    "pathao_area_id": 52
  },
  "payment": {
    "payment_method_id": 1,
    "amount": 75000.00,
    "payment_type": "full"
  }
}

# Step 2: Complete order (reduce Store A inventory)
PATCH /api/orders/123/complete

# Step 3: Create dispatch (Store A → Warehouse) with customer info
POST /api/dispatches
{
  "source_store_id": 2,  // Store A
  "destination_store_id": 1,  // Warehouse
  "for_pathao_delivery": true,  // 👈 Flag as Pathao delivery
  "customer_id": 10,
  "order_id": 123,
  "customer_delivery_info": {
    "delivery_type": "home_delivery",
    "package_weight": 0.5,
    "special_instructions": "Call before delivery",
    "delivery_address": {
      "name": "Karim Ahmed",
      "phone": "01712345678",
      "street": "House 12, Road 5",
      "area": "Gulshan-2",
      "city": "Dhaka",
      "pathao_city_id": 1,
      "pathao_zone_id": 10,
      "pathao_area_id": 52
    },
    "recipient_name": "Karim Ahmed",
    "recipient_phone": "01712345678",
    "cod_amount": 0  // Already paid at counter
  },
  "items": [{
    "product_batch_id": 5,
    "quantity": 1
  }],
  "notes": "POS order - for Pathao delivery to customer"
}

# Step 4: Approve dispatch
PATCH /api/dispatches/1/approve

# Step 5: Mark as dispatched (leaves Store A)
PATCH /api/dispatches/1/dispatch

# Step 6: Mark as delivered to warehouse
PATCH /api/dispatches/1/deliver

# Step 7: At warehouse - Create shipment from dispatch
POST /api/dispatches/1/create-shipment
{
  "send_to_pathao": true  // Send immediately or false for manual later
}

✅ Shipment created from warehouse with customer delivery info
✅ Pathao picks up from warehouse and delivers to customer
```

---

## Scenario 3: POS - Customer Takes Product

**Customer buys and leaves with product**

```http
# Step 1: Create order
POST /api/orders
{
  "order_type": "counter",
  "store_id": 2,  // Store A
  "customer": {...},
  "items": [{...}],
  "payment": {full payment}
  // NO shipping_address
}

# Step 2: Complete order
PATCH /api/orders/123/complete

# DONE ✅ - No dispatch, no shipment
```

---

## Bulk Operations for Warehouse

### End of Day: Convert Multiple Dispatches to Shipments

```http
# Step 1: Get dispatches pending shipment creation
GET /api/dispatches/pending-shipment?warehouse_id=1

Response:
{
  "success": true,
  "message": "15 dispatches pending shipment creation",
  "data": [
    {
      "id": 1,
      "dispatch_number": "DSP-001",
      "source_store": "Store A",
      "warehouse": "Main Warehouse",
      "customer": {"name": "Ali Hassan", "phone": "01712345678"},
      "order": {"order_number": "ORD-001", "total_amount": "75000.00"},
      "delivery_info": {...},
      "delivered_at": "2025-11-04 15:30:00"
    },
    ...
  ]
}

# Step 2: Bulk create shipments from dispatches
POST /api/dispatches/bulk-create-shipment
{
  "dispatch_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
  "send_to_pathao": true  // Send all to Pathao immediately
}

Response:
{
  "success": true,
  "message": "10 shipments created successfully, 0 failed",
  "data": {
    "success": [
      {
        "dispatch_id": 1,
        "dispatch_number": "DSP-001",
        "shipment_id": 1,
        "shipment_number": "SHP-001",
        "pathao_consignment_id": "P123456"
      },
      ...
    ],
    "failed": []
  }
}
```

---

## API Endpoints Reference

### New Dispatch Endpoints (3 new)

```http
# Get dispatches pending shipment creation
GET /api/dispatches/pending-shipment
GET /api/dispatches/pending-shipment?warehouse_id=1

# Create shipment from single dispatch
POST /api/dispatches/{id}/create-shipment
{
  "send_to_pathao": true  // Optional: immediate send
}

# Bulk create shipments from multiple dispatches
POST /api/dispatches/bulk-create-shipment
{
  "dispatch_ids": [1, 2, 3, 4, 5],
  "send_to_pathao": false  // Optional: manual send later
}
```

---

## Data Flow Diagram

```
POS SALE WITH DELIVERY:
Store A Inventory (100 units)
    ↓ (order completed)
Store A Inventory (99 units)
    ↓ (dispatch created: for_pathao_delivery=true)
Dispatch in transit to warehouse
    ↓ (dispatch delivered)
Warehouse Inventory (1 unit)
    ↓ (create shipment from dispatch)
Shipment created (with customer info from dispatch)
    ↓ (send to Pathao)
Pathao picks up from warehouse
    ↓
Customer receives ✅

PHONE ORDER:
Warehouse Inventory (100 units)
    ↓ (order completed at warehouse)
Warehouse Inventory (99 units)
    ↓ (create shipment)
Shipment created
    ↓ (send to Pathao)
Pathao picks up from warehouse
    ↓
Customer receives ✅
```

---

## Key Fields in ProductDispatch

```php
[
    'for_pathao_delivery' => true,  // Flag: this dispatch is for customer delivery
    'customer_id' => 10,             // Customer receiving the product
    'order_id' => 123,               // Original order
    'shipment_id' => 5,              // Created shipment (null until created)
    'customer_delivery_info' => [    // Complete delivery details
        'delivery_type' => 'home_delivery',
        'package_weight' => 0.5,
        'special_instructions' => 'Call before delivery',
        'delivery_address' => [
            'name' => 'Customer Name',
            'phone' => '01712345678',
            'street' => 'House 12',
            'area' => 'Gulshan-2',
            'city' => 'Dhaka',
            'pathao_city_id' => 1,
            'pathao_zone_id' => 10,
            'pathao_area_id' => 52,
        ],
        'recipient_name' => 'Customer Name',
        'recipient_phone' => '01712345678',
        'cod_amount' => 0,  // If already paid
    ]
]
```

---

## Warehouse Daily Operations

### Morning:
```bash
# Check dispatches received overnight
GET /api/dispatches/pending-shipment?warehouse_id=1
```

### Throughout Day:
```bash
# As dispatches arrive, create individual shipments
POST /api/dispatches/{id}/create-shipment
{send_to_pathao: false}  # Don't send yet
```

### End of Day:
```bash
# Bulk send all pending shipments to Pathao
POST /api/shipments/bulk-send-to-pathao
{shipment_ids: [1,2,3,...,20]}
```

---

## Summary of Changes

### What Changed:
1. **Phone orders** → Always created at warehouse (store_id = warehouse)
2. **POS with delivery** → Create dispatch (store → warehouse) → Create shipment → Pathao
3. **ProductDispatch** → New fields for customer delivery tracking
4. **New endpoints** → Convert dispatches to shipments

### What Stays Same:
- POS without delivery → No dispatch, no shipment ✅
- Shipment/Pathao integration → Works same way
- Bulk operations → Still supported

---

## Migration Required

```bash
php artisan migrate
```

Adds fields to `product_dispatches`:
- `for_pathao_delivery` (boolean)
- `customer_id` (foreign key)
- `order_id` (foreign key)
- `customer_delivery_info` (json)
- `shipment_id` (foreign key)

---

**System Ready**: Proper workflow where all deliveries go through warehouse! 🏬 → 📦 → 🚚 → 🏠
