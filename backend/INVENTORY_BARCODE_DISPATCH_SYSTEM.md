# Complete Physical Inventory & Dispatch Management System

## Architecture Overview

This system implements a complete physical inventory tracking solution where:

1. **Product** = Product definition (like "iPhone 15 Pro" or "Samsung TV")
2. **ProductBatch** = Physical inventory units (100 units received from vendor at $500 cost)
3. **ProductBarcode** = Individual trackable identifiers (scan to get full product info)
4. **ProductDispatch** = Transfer of batches between stores/warehouses

## System Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                    COMPLETE INVENTORY FLOW                       │
└─────────────────────────────────────────────────────────────────┘

1. CREATE PRODUCT DEFINITION
   POST /api/products
   {
     "name": "iPhone 15 Pro",
     "sku": "IPH15PRO",
     "category_id": 1,
     "vendor_id": 5
   }

2. RECEIVE PHYSICAL INVENTORY (Create Batch)
   POST /api/batches
   {
     "product_id": 1,
     "store_id": 1,  // Main warehouse
     "quantity": 100,
     "cost_price": 500.00,
     "sell_price": 750.00,
     "manufactured_date": "2024-01-01",
     "expiry_date": "2026-01-01",
     "generate_barcodes": true,  // Auto-generates barcodes
     "barcode_type": "CODE128"
   }
   ✅ Creates batch with 100 units
   ✅ Generates primary barcode for batch
   ✅ Units now available at Main Warehouse

3. SCAN BARCODE (Point of Sale / Inventory Check)
   POST /api/barcodes/scan
   {
     "barcode": "123456789012"
   }
   📱 Returns:
   - Product details
   - Current location (which store)
   - Batch info (cost, price, quantity)
   - Movement history
   - Availability status

4. CREATE DISPATCH (Transfer to Branch Store)
   POST /api/dispatches
   {
     "source_store_id": 1,  // Main warehouse
     "destination_store_id": 2,  // Branch store
     "expected_delivery_date": "2024-12-25"
   }

5. ADD ITEMS TO DISPATCH
   POST /api/dispatches/{id}/items
   {
     "batch_id": 1,
     "quantity": 50  // Send 50 units to branch
   }

6. APPROVE DISPATCH
   PATCH /api/dispatches/{id}/approve
   ✅ Manager approves the transfer

7. MARK AS DISPATCHED (In Transit)
   PATCH /api/dispatches/{id}/dispatch
   ✅ Items are now in transit

8. MARK AS DELIVERED
   PATCH /api/dispatches/{id}/deliver
   {
     "items": [
       {
         "item_id": 1,
         "received_quantity": 50,
         "damaged_quantity": 0,
         "missing_quantity": 0
       }
     ]
   }
   ✅ Automatically creates new batch at destination store
   ✅ Reduces quantity in source batch
   ✅ Records movement in ProductMovement table
   ✅ New barcodes can track items at new location

9. SCAN BARCODE AGAIN
   POST /api/barcodes/scan
   {
     "barcode": "123456789012"
   }
   📱 Now shows:
   - Current location: Branch Store (updated)
   - Movement history: Main Warehouse → Branch Store
   - New batch info at branch
```

## API Endpoints Reference

### Product Batch Management (11 Endpoints)

```http
# List batches (with filters)
GET /api/batches?product_id=1&store_id=2&status=available

# Create new batch (receive physical inventory)
POST /api/batches
{
  "product_id": 1,
  "store_id": 1,
  "quantity": 100,
  "cost_price": 500.00,
  "sell_price": 750.00,
  "generate_barcodes": true
}

# Get batch details
GET /api/batches/{id}

# Update batch
PUT /api/batches/{id}
{
  "cost_price": 550.00,
  "sell_price": 800.00
}

# Adjust stock (inventory correction)
POST /api/batches/{id}/adjust-stock
{
  "adjustment": -5,  // Removed 5 damaged units
  "reason": "Found damaged during inspection"
}

# Get low stock batches
GET /api/batches/low-stock?threshold=10&store_id=1

# Get expiring soon batches
GET /api/batches/expiring-soon?days=30

# Get expired batches
GET /api/batches/expired

# Get batch statistics
GET /api/batches/statistics

# Deactivate batch
DELETE /api/batches/{id}
```

### Barcode Management (10 Endpoints)

```http
# **MOST IMPORTANT** - Scan barcode (get everything)
POST /api/barcodes/scan
{
  "barcode": "123456789012"
}
Response:
{
  "success": true,
  "data": {
    "barcode": "123456789012",
    "product": {
      "id": 1,
      "name": "iPhone 15 Pro",
      "sku": "IPH15PRO"
    },
    "current_location": {
      "id": 2,
      "name": "Downtown Branch",
      "address": "123 Main St"
    },
    "current_batch": {
      "batch_number": "BATCH-20241104-ABC123",
      "quantity": 45,
      "status": "available"
    },
    "is_available": true,
    "quantity_available": 45,
    "last_movement": {
      "type": "dispatch",
      "from": "Main Warehouse",
      "to": "Downtown Branch",
      "date": "2024-11-01 14:30:00"
    }
  }
}

# Batch scan (inventory verification)
POST /api/barcodes/batch-scan
{
  "barcodes": ["123", "456", "789"]
}

# Get barcode movement history
GET /api/barcodes/{barcode}/history

# Get current location
GET /api/barcodes/{barcode}/location

# List all barcodes
GET /api/barcodes?product_id=1

# Generate new barcodes
POST /api/barcodes/generate
{
  "product_id": 1,
  "type": "CODE128",
  "quantity": 10
}

# Get product barcodes
GET /api/products/{productId}/barcodes

# Make barcode primary
PATCH /api/barcodes/{id}/make-primary

# Deactivate barcode
DELETE /api/barcodes/{id}
```

### Dispatch Management (11 Endpoints)

```http
# List dispatches
GET /api/dispatches?status=in_transit&source_store_id=1

# Create dispatch
POST /api/dispatches
{
  "source_store_id": 1,
  "destination_store_id": 2,
  "expected_delivery_date": "2024-12-25",
  "carrier_name": "DHL"
}

# Get dispatch details
GET /api/dispatches/{id}

# Add item to dispatch
POST /api/dispatches/{id}/items
{
  "batch_id": 1,
  "quantity": 50
}

# Remove item from dispatch
DELETE /api/dispatches/{dispatchId}/items/{itemId}

# Approve dispatch
PATCH /api/dispatches/{id}/approve

# Mark as dispatched (in transit)
PATCH /api/dispatches/{id}/dispatch

# Mark as delivered (processes inventory movements)
PATCH /api/dispatches/{id}/deliver
{
  "items": [
    {
      "item_id": 1,
      "received_quantity": 48,
      "damaged_quantity": 2,
      "missing_quantity": 0
    }
  ]
}

# Cancel dispatch
PATCH /api/dispatches/{id}/cancel

# Get statistics
GET /api/dispatches/statistics
```

## Real-World Use Cases

### Use Case 1: Electronics Store Chain

**Scenario**: Receive 100 iPhones at main warehouse, send 30 to downtown branch

```bash
# Step 1: Create product (if not exists)
POST /api/products
{
  "name": "iPhone 15 Pro 256GB",
  "sku": "IPH15PRO256",
  "category_id": 1,
  "vendor_id": 5
}

# Step 2: Receive physical inventory
POST /api/batches
{
  "product_id": 1,
  "store_id": 1,  // Main warehouse
  "quantity": 100,
  "cost_price": 800.00,
  "sell_price": 1200.00,
  "generate_barcodes": true
}
# Response includes primary_barcode: "849372849273"

# Step 3: Create dispatch to branch
POST /api/dispatches
{
  "source_store_id": 1,
  "destination_store_id": 2,
  "expected_delivery_date": "2024-11-10"
}

# Step 4: Add items
POST /api/dispatches/1/items
{
  "batch_id": 1,
  "quantity": 30
}

# Step 5: Approve & dispatch
PATCH /api/dispatches/1/approve
PATCH /api/dispatches/1/dispatch

# Step 6: Receive at branch
PATCH /api/dispatches/1/deliver
{
  "items": [{
    "item_id": 1,
    "received_quantity": 30,
    "damaged_quantity": 0
  }]
}

# Now scan barcode at POS in branch store
POST /api/barcodes/scan
{
  "barcode": "849372849273"
}
# Shows current location: Downtown Branch
# Shows 30 units available
```

### Use Case 2: Pharmacy with Expiry Tracking

```bash
# Receive medicine batch with expiry
POST /api/batches
{
  "product_id": 5,
  "store_id": 1,
  "quantity": 500,
  "cost_price": 10.00,
  "sell_price": 15.00,
  "manufactured_date": "2024-01-01",
  "expiry_date": "2025-01-01",
  "generate_barcodes": true
}

# Check expiring soon (30 days)
GET /api/batches/expiring-soon?days=30

# Check expired batches
GET /api/batches/expired

# When scanning at counter
POST /api/barcodes/scan
{
  "barcode": "123456"
}
# Response includes expiry_date
# System can warn if expired
```

### Use Case 3: Supermarket Inventory Count

```bash
# Scan multiple items during stock take
POST /api/barcodes/batch-scan
{
  "barcodes": [
    "123456789012",
    "234567890123",
    "345678901234"
  ]
}

Response:
{
  "total_scanned": 3,
  "found": 3,
  "not_found": 0,
  "results": [
    {
      "barcode": "123456789012",
      "found": true,
      "product_name": "Coca Cola 2L",
      "current_location": "Main Store",
      "quantity_available": 144
    },
    ...
  ]
}
```

## Key Features

### 1. Automatic Inventory Movements
When dispatch is delivered:
- ✅ Source batch quantity reduced automatically
- ✅ New batch created at destination
- ✅ ProductMovement records created
- ✅ Barcode location updated

### 2. Stock Validation
- Cannot dispatch more than available quantity
- Cannot dispatch from wrong store
- Cannot add items to approved dispatch
- Cannot deliver without approval

### 3. Expiry Management
- Track manufactured and expiry dates
- Get alerts for expiring batches
- Filter expired inventory
- Days until expiry calculation

### 4. Barcode Tracking
- Scan to get complete product info
- Track movement history
- Current location tracking
- Batch association

### 5. Multi-Store Support
- Track inventory per store
- Transfer between stores
- Store-specific stock levels
- Dispatch tracking

## Status Flows

### Batch Status
```
available → (quantity > 0, not expired)
low_stock → (quantity <= threshold)
out_of_stock → (quantity = 0)
expired → (expiry_date passed)
inactive → (manually deactivated)
```

### Dispatch Status
```
pending → (created, adding items)
  ↓ (approve)
approved → (manager approved)
  ↓ (dispatch)
in_transit → (on the way)
  ↓ (deliver)
delivered → (received, inventory updated)

cancelled → (at any point before delivered)
```

## Integration Points

### With Purchase Orders
```bash
# When PO is received, create batch
POST /api/purchase-orders/{id}/receive

# This internally creates ProductBatch
POST /api/batches
{
  "product_id": po_item.product_id,
  "quantity": received_quantity,
  "cost_price": po_item.unit_price,
  ...
}
```

### With Orders/POS
```bash
# At checkout, scan barcode
POST /api/barcodes/scan
{
  "barcode": "scanned_barcode"
}

# Get product, price, check availability
# Process sale
# Reduce batch quantity
POST /api/batches/{id}/adjust-stock
{
  "adjustment": -1,
  "reason": "Sold to customer"
}
```

## Statistics & Reports

### Batch Statistics
```bash
GET /api/batches/statistics

Response:
{
  "total_batches": 156,
  "active_batches": 143,
  "available_batches": 120,
  "low_stock_batches": 15,
  "out_of_stock_batches": 8,
  "expiring_soon_batches": 12,
  "expired_batches": 3,
  "total_inventory_value": "1500000.00",
  "total_sell_value": "2250000.00",
  "total_units": 15420,
  "by_store": [
    {
      "store_name": "Main Warehouse",
      "batch_count": 89,
      "total_units": 8900,
      "inventory_value": "890000.00"
    }
  ]
}
```

### Dispatch Statistics
```bash
GET /api/dispatches/statistics

Response:
{
  "total_dispatches": 45,
  "pending": 5,
  "in_transit": 12,
  "delivered": 25,
  "cancelled": 3,
  "overdue": 2,
  "expected_today": 3,
  "total_value_in_transit": "450000.00"
}
```

## Error Handling

### Common Errors

```json
// Insufficient quantity
{
  "success": false,
  "message": "Insufficient quantity in batch. Available: 10"
}

// Wrong store
{
  "success": false,
  "message": "Batch does not belong to the source store"
}

// Barcode not found
{
  "success": false,
  "message": "Barcode not found in system"
}

// Invalid state
{
  "success": false,
  "message": "Dispatch cannot be approved in its current state"
}
```

## Testing Workflow

### 1. Basic Flow Test
```bash
# Create product
POST /api/products {...}

# Create batch
POST /api/batches {...}

# Scan barcode
POST /api/barcodes/scan {...}

# Check location
GET /api/barcodes/{barcode}/location
```

### 2. Dispatch Flow Test
```bash
# Create dispatch
POST /api/dispatches {...}

# Add items
POST /api/dispatches/1/items {...}

# Approve
PATCH /api/dispatches/1/approve

# Dispatch
PATCH /api/dispatches/1/dispatch

# Deliver
PATCH /api/dispatches/1/deliver {...}

# Verify movement
GET /api/barcodes/{barcode}/history
```

### 3. Expiry Test
```bash
# Create expiring batch
POST /api/batches {expiry_date: "2024-12-31"}

# Check expiring
GET /api/batches/expiring-soon?days=60

# Check expired
GET /api/batches/expired
```

## Database Schema Reference

### product_batches
- id, product_id, batch_number (unique)
- quantity, cost_price, sell_price
- store_id, barcode_id
- manufactured_date, expiry_date
- availability, is_active

### product_barcodes
- id, product_id, barcode (unique)
- type (CODE128, EAN13, QR)
- is_primary, is_active
- generated_at

### product_dispatches
- id, dispatch_number (unique)
- source_store_id, destination_store_id
- status, dispatch_date, expected_delivery_date
- total_cost, total_value, total_items
- created_by, approved_by

### product_dispatch_items
- id, product_dispatch_id, product_batch_id
- quantity, received_quantity, damaged_quantity
- unit_cost, unit_price, status

### product_movements
- id, product_batch_id, product_barcode_id
- from_store_id, to_store_id
- movement_type, quantity, movement_date
- reference_number, notes

## Best Practices

1. **Always generate barcodes** when creating batches
2. **Scan barcodes** at every touchpoint (receive, dispatch, deliver, sell)
3. **Track movements** for audit trail
4. **Check expiry dates** before dispatch
5. **Validate quantities** before creating dispatches
6. **Use batch scan** for inventory verification
7. **Monitor low stock** and expiring items regularly
8. **Record damaged/missing** items during delivery

---

**System Ready**: All 32 endpoints operational for complete physical inventory management with barcode tracking and inter-store dispatch functionality.
