# Individual Barcode Location & Status Tracking System

**Date:** 2025-11-10  
**Version:** 2.0  
**Status:** Implementation Complete

---

## Table of Contents

1. [Overview](#overview)
2. [The Problem We're Solving](#the-problem)
3. [System Architecture](#system-architecture)
4. [Barcode Status States](#barcode-status-states)
5. [Location Tracking](#location-tracking)
6. [Complete History Tracking](#complete-history-tracking)
7. [Database Schema](#database-schema)
8. [API Usage Examples](#api-examples)
9. [Dispatch with Individual Barcodes](#dispatch-flow)
10. [Tracking Queries](#tracking-queries)

---

## Overview

Every physical product unit in the ERP system has a unique barcode. This document describes how we track:

- **WHERE** each physical unit is currently located (which store, warehouse, in transit, etc.)
- **WHAT STATUS** each unit is in (on display, in shipment, sold, etc.)
- **COMPLETE HISTORY** of every movement and status change

### Key Benefits:

✅ **Exact Location**: Know precisely where each physical item is  
✅ **Real-time Status**: Track if item is on display, in transit, sold, etc.  
✅ **Complete Audit Trail**: Full history of every movement  
✅ **Loss Prevention**: Identify missing units immediately  
✅ **Customer Service**: Track specific item a customer purchased  
✅ **Inventory Accuracy**: Reconcile physical vs system inventory

---

## The Problem

### ❌ OLD SYSTEM (Quantity-Based Tracking):

```
Batch #123: 100 Sarees → Dispatch 50 to Store A
Result: Know 50 units moved, but...
- Which 50 specific sarees?
- Where is saree with specific pattern defect?
- If customer returns, which exact unit came back?
```

**Problems:**
- Can't track individual units
- No way to identify specific defective item
- Can't locate a particular physical product
- Returns processing is ambiguous
- Theft detection is impossible

### ✅ NEW SYSTEM (Individual Barcode Tracking):

```
Batch #123: 100 Sarees
├── Barcode 789012345001 → Store A, on_display, Shelf-3
├── Barcode 789012345002 → Store B, in_warehouse, Bin-A12
├── Barcode 789012345003 → In Transit to Store C
├── Barcode 789012345004 → With Customer (Sold in Order #456)
└── Barcode 789012345005 → Defective (Return from Customer)
```

**Benefits:**
- Track each unit's exact location
- Know status of every physical item
- Complete movement history per unit
- Identify specific items for returns, warranty
- Detect missing/stolen items

---

## System Architecture

### Core Components:

```
┌─────────────────────────────────────────────────────────────┐
│                    ProductBarcode Model                      │
│                                                              │
│  - barcode (unique identifier)                              │
│  - current_store_id (WHERE it is)                           │
│  - current_status (WHAT STATE it's in)                      │
│  - location_updated_at (WHEN it changed)                    │
│  - location_metadata (ADDITIONAL DETAILS)                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ Every movement creates
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  ProductMovement Model                       │
│                                                              │
│  - product_barcode_id (which physical unit)                 │
│  - from_store_id → to_store_id (movement path)              │
│  - status_before → status_after (state change)              │
│  - movement_type (sale, dispatch, return, etc.)             │
│  - reference_type & reference_id (what caused it)           │
│  - movement_date (when it happened)                         │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow Example:

```
1. BATCH CREATED
   └─> 100 barcodes generated
       └─> Each barcode: current_status = "in_warehouse"
                         current_store_id = Source Store

2. DISPATCH TO STORE
   └─> Scan 20 barcodes for dispatch
       └─> Each barcode: current_status = "in_transit"
                         Dispatch ID stored in metadata
       └─> ProductMovement created for each barcode

3. DISPATCH DELIVERED
   └─> Each barcode: current_status = "in_shop"
                     current_store_id = Destination Store
       └─> ProductMovement updated

4. PLACE ON DISPLAY
   └─> Barcode 001: current_status = "on_display"
                    metadata = {shelf: "A-3", section: "Women's"}

5. CUSTOMER PURCHASE
   └─> Barcode 001 scanned at POS
       └─> current_status = "with_customer"
           is_active = false (sold)
           metadata = {order_id, customer_id, sold_at}
       └─> ProductMovement: movement_type = "sale"

6. CUSTOMER RETURN
   └─> Barcode 001 returned
       └─> current_status = "in_return"
           is_active = true (available again)
           metadata = {return_id, return_reason}
```

---

## Barcode Status States

### Complete Status Enum:

| Status | Description | Available for Sale? | is_active |
|--------|-------------|---------------------|-----------|
| `in_warehouse` | Stored in warehouse/backroom | ✅ Yes | true |
| `in_shop` | Available in shop inventory | ✅ Yes | true |
| `on_display` | Currently on display floor | ✅ Yes | true |
| `in_transit` | Moving between locations | ❌ No | true |
| `in_shipment` | Packaged for customer delivery | ❌ No | true |
| `with_customer` | Sold and delivered | ❌ No | false |
| `in_return` | Being returned by customer | ❌ No | true |
| `defective` | Marked as defective | ❌ No | false |
| `repair` | Sent for repair | ❌ No | true |
| `vendor_return` | Returned to vendor | ❌ No | false |
| `disposed` | Disposed/written off | ❌ No | false |

### Status Transitions:

```
in_warehouse ──┬──> in_shop ──┬──> on_display ──> (scan at POS) ──> with_customer
               │               │
               │               └──> in_transit ──> (delivered) ──> in_shop
               │
               └──> in_transit ──> (dispatch)  ──> in_warehouse (destination)

with_customer ──> in_return ──┬──> in_shop (if good condition)
                              └──> defective (if damaged)

defective ──┬──> repair ──> in_shop (if repaired)
            ├──> vendor_return
            └──> disposed
```

### Checking Status in Code:

```php
// Check if available for sale
$barcode->isAvailableForSale(); // true if in_shop, on_display, or in_warehouse

// Check specific statuses
$barcode->isCurrentlyInTransit();  // current_status === 'in_transit'
$barcode->isWithCustomer();        // current_status === 'with_customer'

// Query barcodes by status
ProductBarcode::inWarehouse()->get();
ProductBarcode::onDisplay()->get();
ProductBarcode::availableForSale()->get();

// Query by location
ProductBarcode::atStore($storeId)->get();
ProductBarcode::atStore($storeId)->inShop()->get();
```

---

## Location Tracking

### Current Location Fields:

```php
ProductBarcode:
  - current_store_id     // Foreign key to stores table
  - current_status       // Enum (see above)
  - location_updated_at  // Timestamp of last change
  - location_metadata    // JSON with additional details
```

### Location Metadata Examples:

```json
// When on display
{
  "display_started_at": "2025-11-10 14:30:00",
  "shelf": "A-3",
  "section": "Women's Clothing",
  "mannequin_id": 5
}

// When in transit
{
  "transit_started_at": "2025-11-10 08:00:00",
  "dispatch_id": 123,
  "expected_arrival": "2025-11-11 10:00:00",
  "carrier": "Company Truck #7"
}

// When in shipment
{
  "shipment_id": 456,
  "shipment_started_at": "2025-11-10 16:00:00",
  "tracking_number": "PATHAO-12345",
  "courier": "Pathao"
}

// When sold
{
  "sold_at": "2025-11-10 18:45:00",
  "order_id": 789,
  "customer_id": 101,
  "sale_price": 2500.00
}

// When returned
{
  "returned_at": "2025-11-15 10:00:00",
  "return_id": 12,
  "return_reason": "Size issue",
  "condition": "good"
}
```

### Updating Location:

```php
// Generic location update
$barcode->updateLocation(
    storeId: $destinationStore->id,
    status: 'in_shop',
    metadata: ['shelf' => 'B-5', 'bin' => 'A12']
);

// Shorthand methods
$barcode->moveToWarehouse($storeId, ['bin' => 'A12']);
$barcode->moveToShop($storeId);
$barcode->placeOnDisplay($storeId, ['shelf' => 'A-3']);
$barcode->markInTransit($toStoreId, $dispatchId);
$barcode->markInShipment($shipmentId, $trackingNumber);
$barcode->markSold($orderId, $customerId);
$barcode->markReturned($returnId, $reason);
```

---

## Complete History Tracking

### ProductMovement Record Structure:

Every location/status change creates a ProductMovement record:

```php
ProductMovement:
  - product_barcode_id   // Which physical unit
  - from_store_id        // Starting location
  - to_store_id          // Ending location
  - status_before        // Status before movement
  - status_after         // Status after movement
  - movement_type        // sale, dispatch, return, transfer, etc.
  - reference_type       // order, dispatch, return, shipment
  - reference_id         // ID of the reference record
  - movement_date        // When it happened
  - performed_by         // Employee who performed action
  - notes                // Additional details
```

### Complete History Query:

```php
// Get full history of a barcode
$history = $barcode->getDetailedLocationHistory();

// Returns:
[
    {
        "id": 156,
        "date": "2025-11-10 18:45:00",
        "from_store": "Main Store",
        "to_store": "Main Store",
        "movement_type": "sale",
        "status_before": "on_display",
        "status_after": "with_customer",
        "reference_type": "order",
        "reference_id": 789,
        "performed_by": "John Doe",
        "notes": "Sold via Order #ORD-2025-0789"
    },
    {
        "id": 142,
        "date": "2025-11-08 14:30:00",
        "from_store": "Main Store",
        "to_store": "Main Store",
        "movement_type": "adjustment",
        "status_before": "in_shop",
        "status_after": "on_display",
        "reference_type": null,
        "reference_id": null,
        "performed_by": "Jane Smith",
        "notes": "Placed on display floor - Shelf A-3"
    },
    {
        "id": 98,
        "date": "2025-11-05 10:00:00",
        "from_store": "Warehouse",
        "to_store": "Main Store",
        "movement_type": "dispatch",
        "status_before": "in_transit",
        "status_after": "in_shop",
        "reference_type": "dispatch",
        "reference_id": 45,
        "performed_by": "Mike Johnson",
        "notes": "Delivered via Dispatch #DSP-20251105-ABC123"
    }
]
```

### History Visualization:

```
Barcode: 789012345001 (Blue Silk Saree)

Timeline:
═══════════════════════════════════════════════════════════════

Nov 1, 2025  │ CREATED in Warehouse (Batch #123)
10:00 AM     │ Status: in_warehouse
             │ Location: Central Warehouse
             ▼

Nov 5, 2025  │ DISPATCHED to Main Store
08:00 AM     │ Status: in_warehouse → in_transit
             │ Dispatch: #DSP-20251105-ABC123
             ▼

Nov 5, 2025  │ DELIVERED to Main Store
10:00 AM     │ Status: in_transit → in_shop
             │ Location: Main Store - Backroom
             ▼

Nov 8, 2025  │ PLACED ON DISPLAY
02:30 PM     │ Status: in_shop → on_display
             │ Location: Shelf A-3, Women's Section
             ▼

Nov 10, 2025 │ SOLD to Customer
06:45 PM     │ Status: on_display → with_customer
             │ Order: #ORD-2025-0789
             │ Customer: Sarah Ahmed
             │ Price: ৳2,500
             ▼

Nov 15, 2025 │ RETURNED by Customer
10:00 AM     │ Status: with_customer → in_return
             │ Return: #RET-2025-0012
             │ Reason: Size issue
             ▼

Nov 15, 2025 │ INSPECTED & RESTOCKED
11:30 AM     │ Status: in_return → in_shop
             │ Condition: Good
             │ Location: Main Store - Backroom
             ▼
```

---

## Database Schema

### Migration: `add_barcode_location_tracking.php`

```php
// product_barcodes table - ADD columns
Schema::table('product_barcodes', function (Blueprint $table) {
    $table->foreignId('current_store_id')->nullable();
    $table->enum('current_status', [
        'in_warehouse', 'in_shop', 'on_display', 'in_transit',
        'in_shipment', 'with_customer', 'in_return', 'defective',
        'repair', 'vendor_return', 'disposed'
    ])->default('in_warehouse');
    $table->timestamp('location_updated_at')->nullable();
    $table->json('location_metadata')->nullable();
});

// product_dispatch_items table - ADD column
Schema::table('product_dispatch_items', function (Blueprint $table) {
    $table->foreignId('product_barcode_id')->nullable();
});

// product_movements table - ADD columns
Schema::table('product_movements', function (Blueprint $table) {
    $table->string('reference_type')->nullable();
    $table->unsignedBigInteger('reference_id')->nullable();
    $table->enum('status_before', [...])->nullable();
    $table->enum('status_after', [...])->nullable();
});
```

### Indexes for Performance:

```sql
CREATE INDEX idx_current_store ON product_barcodes(current_store_id);
CREATE INDEX idx_current_status ON product_barcodes(current_status);
CREATE INDEX idx_store_status ON product_barcodes(current_store_id, current_status);
CREATE INDEX idx_location_updated ON product_barcodes(location_updated_at);

CREATE INDEX idx_barcode_id ON product_dispatch_items(product_barcode_id);

CREATE INDEX idx_reference ON product_movements(reference_type, reference_id);
CREATE INDEX idx_status_before ON product_movements(status_before);
CREATE INDEX idx_status_after ON product_movements(status_after);
```

---

## API Examples

### 1. Get Current Location of Barcode

```http
GET /api/barcodes/{barcode}/location
```

**Response:**
```json
{
  "success": true,
  "data": {
    "barcode": "789012345001",
    "product": {
      "id": 10,
      "name": "Blue Silk Saree",
      "sku": "SAREE-001"
    },
    "current_store": {
      "id": 5,
      "name": "Main Retail Store",
      "type": "retail",
      "address": "123 Main Street, Dhaka"
    },
    "current_status": "on_display",
    "status_label": "On Display Floor",
    "is_active": true,
    "is_defective": false,
    "is_available_for_sale": true,
    "location_updated_at": "2025-11-08T14:30:00Z",
    "location_metadata": {
      "shelf": "A-3",
      "section": "Women's Clothing",
      "display_started_at": "2025-11-08 14:30:00"
    },
    "batch": {
      "id": 123,
      "batch_number": "BATCH-2025-001",
      "quantity": 95
    }
  }
}
```

### 2. Get Complete History of Barcode

```http
GET /api/barcodes/{barcode}/history
```

**Response:**
```json
{
  "success": true,
  "data": {
    "barcode": "789012345001",
    "total_movements": 5,
    "history": [
      {
        "id": 156,
        "date": "2025-11-10T18:45:00Z",
        "from_store": "Main Store",
        "to_store": "Main Store",
        "movement_type": "sale",
        "status_before": "on_display",
        "status_after": "with_customer",
        "reference_type": "order",
        "reference_id": 789,
        "performed_by": "John Doe",
        "notes": "Sold via Order #ORD-2025-0789"
      },
      // ... more history entries
    ]
  }
}
```

### 3. Track All Barcodes in a Store

```http
GET /api/inventory/store/{storeId}/barcodes
```

**Query Parameters:**
- `status` - Filter by status (in_shop, on_display, etc.)
- `product_id` - Filter by product
- `available_only` - Only show available for sale

**Response:**
```json
{
  "success": true,
  "data": {
    "store": {
      "id": 5,
      "name": "Main Retail Store"
    },
    "summary": {
      "total_barcodes": 1250,
      "in_warehouse": 450,
      "in_shop": 600,
      "on_display": 200,
      "available_for_sale": 1250
    },
    "barcodes": [
      {
        "barcode": "789012345001",
        "product_name": "Blue Silk Saree",
        "current_status": "on_display",
        "location_metadata": {
          "shelf": "A-3"
        }
      },
      // ... more barcodes
    ]
  }
}
```

### 4. Find Missing/Unaccounted Barcodes

```http
GET /api/inventory/missing-barcodes
```

**Logic:**
- Physical count doesn't match system count
- Barcode hasn't moved in > 90 days
- Barcode status is in_transit but dispatch was >7 days ago

**Response:**
```json
{
  "success": true,
  "data": {
    "total_missing": 15,
    "categories": {
      "in_transit_too_long": 5,
      "no_movement_90_days": 8,
      "count_discrepancy": 2
    },
    "barcodes": [
      {
        "barcode": "789012345050",
        "product_name": "Red Cotton Saree",
        "last_location": "Warehouse A",
        "last_status": "in_transit",
        "last_movement": "2025-10-01T10:00:00Z",
        "days_since_movement": 40,
        "alert_reason": "in_transit_too_long"
      }
    ]
  }
}
```

---

## Dispatch Flow

### OLD Flow (Quantity-Based):

```
1. Create Dispatch: Source Store → Destination Store
2. Add Item: Product Batch #123, Quantity: 20
   └─> System: Reduce batch quantity by 20
3. Mark Dispatched
4. Mark Delivered
   └─> System: Create new batch at destination with 20 units
```

**Problem:** Which 20 specific units? Can't track individual items.

### NEW Flow (Barcode-Based):

```
1. Create Dispatch: Source Store → Destination Store

2. Add Items (SCAN BARCODES):
   ├─> Scan barcode 789012345001
   │   └─> Barcode status: in_warehouse → in_transit
   │   └─> Create dispatch item with barcode_id
   ├─> Scan barcode 789012345002
   │   └─> Barcode status: in_warehouse → in_transit
   │   └─> Create dispatch item with barcode_id
   └─> ... (scan all 20 barcodes)

3. Mark Dispatched:
   └─> Each barcode:
       ├─> current_status remains "in_transit"
       ├─> location_metadata updated with dispatch details
       └─> ProductMovement created

4. Mark Delivered:
   └─> Each barcode:
       ├─> current_status: in_transit → in_shop
       ├─> current_store_id: Source → Destination
       ├─> location_updated_at: now()
       └─> ProductMovement updated
```

### API: Add Item to Dispatch (New)

```http
POST /api/dispatches/{id}/items
Content-Type: application/json

{
  "barcode": "789012345001"
}
```

**OR Bulk Scan:**

```http
POST /api/dispatches/{id}/items
Content-Type: application/json

{
  "barcodes": [
    "789012345001",
    "789012345002",
    "789012345003"
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "3 items added to dispatch",
  "data": {
    "items": [
      {
        "id": 1,
        "product_barcode_id": 1001,
        "barcode": "789012345001",
        "product_name": "Blue Silk Saree",
        "status": "pending",
        "barcode_status": "in_transit"
      },
      // ... more items
    ],
    "dispatch": {
      "id": 45,
      "dispatch_number": "DSP-20251110-ABC123",
      "total_items": 3,
      "status": "pending"
    }
  }
}
```

### API: Mark Dispatch as Delivered

```http
PATCH /api/dispatches/{id}/deliver
```

**What Happens:**
1. For each barcode in dispatch:
   - Update `current_status`: in_transit → in_shop
   - Update `current_store_id`: Destination Store
   - Update `location_updated_at`: now()
   - Create ProductMovement record
2. Update dispatch status: in_transit → delivered

**Response:**
```json
{
  "success": true,
  "message": "Dispatch delivered. 3 barcodes updated.",
  "data": {
    "dispatch": {
      "id": 45,
      "status": "delivered",
      "actual_delivery_date": "2025-11-10T15:00:00Z"
    },
    "barcodes_updated": [
      {
        "barcode": "789012345001",
        "old_status": "in_transit",
        "new_status": "in_shop",
        "old_store": "Central Warehouse",
        "new_store": "Main Retail Store"
      },
      // ... more barcodes
    ]
  }
}
```

---

## Tracking Queries

### Query: All Barcodes Currently in Transit

```php
$inTransitBarcodes = ProductBarcode::inTransit()
    ->with(['product', 'currentStore', 'batch'])
    ->get();

foreach ($inTransitBarcodes as $barcode) {
    echo "Barcode: {$barcode->barcode}\n";
    echo "Product: {$barcode->product->name}\n";
    echo "From: " . ($barcode->location_metadata['from_store'] ?? 'Unknown') . "\n";
    echo "To: {$barcode->currentStore->name}\n";
    echo "Since: {$barcode->location_updated_at->diffForHumans()}\n\n";
}
```

### Query: All Barcodes at a Store on Display

```php
$displayBarcodes = ProductBarcode::atStore($storeId)
    ->onDisplay()
    ->with('product')
    ->get();

$bySection = $displayBarcodes->groupBy(function ($barcode) {
    return $barcode->location_metadata['section'] ?? 'Unknown';
});

foreach ($bySection as $section => $barcodes) {
    echo "{$section}: {$barcodes->count()} items\n";
}
```

### Query: Barcode Movement History for Date Range

```php
$movements = ProductMovement::where('product_barcode_id', $barcodeId)
    ->whereBetween('movement_date', [$startDate, $endDate])
    ->with(['fromStore', 'toStore', 'performedBy'])
    ->orderBy('movement_date', 'desc')
    ->get();
```

### Query: Find Barcodes That Haven't Moved in X Days

```php
$stagnantBarcodes = ProductBarcode::where('location_updated_at', '<', now()->subDays(90))
    ->where('current_status', '!=', 'with_customer')  // Exclude sold items
    ->where('current_status', '!=', 'disposed')
    ->with(['product', 'currentStore'])
    ->get();
```

### Query: Trace Product Journey from Creation to Sale

```php
$barcode = ProductBarcode::where('barcode', '789012345001')->first();

$journey = [
    'created' => $barcode->created_at,
    'batch' => $barcode->batch->batch_number,
    'history' => $barcode->getDetailedLocationHistory(),
    'current_location' => $barcode->getCurrentLocationDetails(),
];

// Visualize journey
foreach ($journey['history'] as $movement) {
    echo sprintf(
        "%s: %s → %s (%s → %s) via %s\n",
        $movement['date'],
        $movement['from_store'] ?? 'Origin',
        $movement['to_store'],
        $movement['status_before'],
        $movement['status_after'],
        $movement['reference_type'] ?? 'manual'
    );
}
```

---

## Summary

### ✅ What We Track:

1. **Current Location** - Exact store/warehouse where each barcode is
2. **Current Status** - State of each unit (in_shop, on_display, in_transit, etc.)
3. **Complete History** - Every movement, every status change
4. **Metadata** - Additional details (shelf, bin, reason for return, etc.)
5. **Audit Trail** - Who moved it, when, why

### ✅ Key Capabilities:

- **Find any barcode** instantly - know exactly where it is
- **Track movement history** - complete audit trail from creation to disposal
- **Identify missing items** - barcodes that haven't moved as expected
- **Customer service** - locate specific item a customer purchased
- **Returns processing** - verify exact item being returned
- **Loss prevention** - detect theft or missing inventory
- **Inventory accuracy** - reconcile physical vs system counts
- **Display management** - know what's on display floor vs backroom

### 🚀 Next Steps:

1. **Run Migration:** `php artisan migrate`
2. **Update Dispatch Controller** to use barcode scanning
3. **Update Frontend POS** to scan barcodes during dispatch
4. **Train Staff** on barcode scanning workflow
5. **Generate Reports** on barcode movements and locations

**This system gives you COMPLETE VISIBILITY into every physical product unit in your business!** 🎉
