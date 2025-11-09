# System Architecture: Physical Inventory Management

## Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         INVENTORY SYSTEM ARCHITECTURE                    │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────┐
│   Product    │ (Definition: "iPhone 15 Pro")
│──────────────│
│ id           │
│ name         │
│ sku          │──┐
│ category_id  │  │
│ vendor_id    │  │
└──────────────┘  │
                  │
                  │ has many
                  │
                  ▼
         ┌──────────────────┐
         │  ProductBatch    │ (Physical: "100 units @ Store #1")
         │──────────────────│
         │ id               │
         │ product_id       │
         │ batch_number     │──┐
         │ quantity         │  │
         │ cost_price       │  │
         │ sell_price       │  │
         │ store_id         │──┼─────────┐
         │ barcode_id       │──┼────┐    │
         │ expiry_date      │  │    │    │
         │ is_active        │  │    │    │
         └──────────────────┘  │    │    │
                              │    │    │
                has many      │    │    │ belongs to
                              │    │    │
                              │    │    ▼
    ┌──────────────────────┐  │    │  ┌──────────────┐
    │ ProductDispatchItem  │  │    │  │    Store     │
    │──────────────────────│  │    │  │──────────────│
    │ id                   │  │    │  │ id           │
    │ dispatch_id          │──┼────┼──│ name         │
    │ batch_id             │◀─┘    │  │ address      │
    │ quantity             │       │  │ phone        │
    │ received_quantity    │       │  └──────────────┘
    │ damaged_quantity     │       │
    │ status               │       │
    └──────────────────────┘       │
              │                    │
              │ belongs to         │
              │                    │
              ▼                    │
    ┌──────────────────────┐       │
    │  ProductDispatch     │       │
    │──────────────────────│       │
    │ id                   │       │
    │ dispatch_number      │       │
    │ source_store_id      │───────┤
    │ destination_store_id │───────┤
    │ status               │       │
    │ total_items          │       │
    │ total_cost           │       │
    │ approved_by          │       │
    └──────────────────────┘       │
                                   │
                                   │ has one
                                   │
                                   ▼
                         ┌───────────────────┐
                         │ ProductBarcode    │ (Identifier)
                         │───────────────────│
                         │ id                │
                         │ product_id        │
                         │ barcode           │──┐
                         │ type              │  │
                         │ is_primary        │  │
                         │ is_active         │  │
                         └───────────────────┘  │
                                               │
                                               │ tracks
                                               │
                                               ▼
                                    ┌───────────────────────┐
                                    │  ProductMovement      │ (Audit)
                                    │───────────────────────│
                                    │ id                    │
                                    │ batch_id              │
                                    │ barcode_id            │
                                    │ from_store_id         │
                                    │ to_store_id           │
                                    │ dispatch_id           │
                                    │ movement_type         │
                                    │ quantity              │
                                    │ movement_date         │
                                    │ performed_by          │
                                    └───────────────────────┘
```

## Data Flow: Barcode Scan

```
┌────────────────────────────────────────────────────────────────┐
│                    BARCODE SCAN FLOW                           │
└────────────────────────────────────────────────────────────────┘

1. User scans barcode
   📱 POST /api/barcodes/scan { "barcode": "123456" }
        │
        ▼
2. ProductBarcode::scanBarcode("123456")
        │
        ├─► Find ProductBarcode by barcode
        │
        ├─► Load Product (name, SKU, category, vendor)
        │
        ├─► Get current location via ProductMovement
        │        └─► Latest movement with to_store_id
        │
        ├─► Get current batch
        │        └─► Latest batch associated with barcode
        │
        ├─► Get location history
        │        └─► All ProductMovement records
        │
        └─► Return complete scan result
                 │
                 ▼
3. Response to user
   ✅ Product: iPhone 15 Pro
   ✅ Location: Downtown Branch
   ✅ Quantity: 45 units
   ✅ Price: $750
   ✅ Status: Available
   ✅ Last moved: 2024-11-01 from Main Warehouse
```

## Data Flow: Create and Dispatch

```
┌────────────────────────────────────────────────────────────────┐
│              CREATE BATCH → DISPATCH → DELIVER                 │
└────────────────────────────────────────────────────────────────┘

STEP 1: RECEIVE INVENTORY
━━━━━━━━━━━━━━━━━━━━━━━━━
POST /api/batches
{
  "product_id": 1,
  "store_id": 1,  // Main Warehouse
  "quantity": 100,
  "cost_price": 500,
  "generate_barcodes": true
}
        │
        ▼
┌─────────────────────────────┐
│ ProductBatch Created        │
│ - batch_number: BATCH-xxx   │
│ - quantity: 100             │
│ - store_id: 1               │
└─────────────────────────────┘
        │
        ▼
┌─────────────────────────────┐
│ ProductBarcode Generated    │
│ - barcode: 123456789012     │
│ - product_id: 1             │
│ - is_primary: true          │
└─────────────────────────────┘

STEP 2: CREATE DISPATCH
━━━━━━━━━━━━━━━━━━━━━━━
POST /api/dispatches
{
  "source_store_id": 1,      // Main Warehouse
  "destination_store_id": 2   // Downtown Branch
}
        │
        ▼
┌─────────────────────────────┐
│ ProductDispatch Created     │
│ - status: pending           │
│ - dispatch_number: DSP-xxx  │
└─────────────────────────────┘

STEP 3: ADD ITEMS
━━━━━━━━━━━━━━━━━
POST /api/dispatches/1/items
{
  "batch_id": 1,
  "quantity": 50
}
        │
        ▼
┌─────────────────────────────┐
│ ProductDispatchItem Created │
│ - quantity: 50              │
│ - unit_cost: 500            │
│ - status: pending           │
└─────────────────────────────┘

STEP 4: APPROVE
━━━━━━━━━━━━━━━
PATCH /api/dispatches/1/approve
        │
        ▼
┌─────────────────────────────┐
│ Dispatch Updated            │
│ - approved_by: employee_id  │
│ - approved_at: now()        │
└─────────────────────────────┘

STEP 5: DISPATCH (In Transit)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PATCH /api/dispatches/1/dispatch
        │
        ▼
┌─────────────────────────────┐
│ Dispatch Updated            │
│ - status: in_transit        │
└─────────────────────────────┘
        │
        ▼
┌─────────────────────────────┐
│ DispatchItems Updated       │
│ - status: dispatched        │
└─────────────────────────────┘

STEP 6: DELIVER
━━━━━━━━━━━━━━━
PATCH /api/dispatches/1/deliver
{
  "items": [{
    "item_id": 1,
    "received_quantity": 50,
    "damaged_quantity": 0
  }]
}
        │
        ▼
┌─────────────────────────────┐
│ Source Batch Updated        │
│ Store #1                    │
│ - quantity: 100 → 50        │
└─────────────────────────────┘
        │
        ▼
┌─────────────────────────────┐
│ New Batch Created           │
│ Store #2                    │
│ - quantity: 50              │
│ - cost_price: 500           │
│ - batch_number: BATCH-xx-DST│
└─────────────────────────────┘
        │
        ▼
┌─────────────────────────────┐
│ ProductMovement Created     │
│ - from_store_id: 1          │
│ - to_store_id: 2            │
│ - quantity: 50              │
│ - movement_type: dispatch   │
│ - reference: DSP-xxx        │
└─────────────────────────────┘
        │
        ▼
┌─────────────────────────────┐
│ DispatchItem Updated        │
│ - status: received          │
│ - received_quantity: 50     │
└─────────────────────────────┘

NOW SCAN BARCODE:
━━━━━━━━━━━━━━━━
POST /api/barcodes/scan
{"barcode": "123456789012"}
        │
        ▼
✅ Current Location: Downtown Branch (Store #2)
✅ Quantity: 50 units
✅ Movement History:
   - 2024-11-04: Main Warehouse → Downtown Branch (50 units)
```

## Controller Architecture

```
┌────────────────────────────────────────────────────────────────┐
│                    CONTROLLER LAYER                            │
└────────────────────────────────────────────────────────────────┘

ProductBatchController (11 endpoints)
├── index()               List batches with filters
├── show()                Get batch details
├── create()              Create batch + generate barcodes
├── update()              Update batch info
├── adjustStock()         Add/remove stock (inventory correction)
├── getLowStock()         Alert on low stock
├── getExpiringSoon()     Alert on expiring items
├── getExpired()          List expired items
├── getStatistics()       Inventory analytics
└── destroy()             Deactivate batch

ProductBarcodeController (10 endpoints)
├── scan()                🔥 CORE: Scan and get everything
├── batchScan()           Scan multiple barcodes
├── getHistory()          Movement history for barcode
├── getCurrentLocation()  Where is this barcode now?
├── index()               List all barcodes
├── generate()            Generate new barcodes
├── getProductBarcodes()  Get all barcodes for product
├── makePrimary()         Set as primary barcode
└── deactivate()          Deactivate barcode

ProductDispatchController (11 endpoints)
├── index()               List dispatches with filters
├── show()                Get dispatch details
├── create()              Create new dispatch
├── addItem()             Add batch to dispatch
├── removeItem()          Remove item from dispatch
├── approve()             Manager approval
├── markDispatched()      Set as in_transit
├── markDelivered()       🔥 Process inventory movements
├── cancel()              Cancel dispatch
└── getStatistics()       Dispatch analytics
```

## State Machine

```
┌────────────────────────────────────────────────────────────────┐
│                    DISPATCH STATE MACHINE                      │
└────────────────────────────────────────────────────────────────┘

        ┌─────────┐
        │ PENDING │ ◄─── create()
        └────┬────┘
             │
             │ approve()
             │ (Manager approval required)
             │ (Must have items)
             │
             ▼
        ┌─────────┐
        │APPROVED │
        └────┬────┘
             │
             │ dispatch()
             │ (Mark as sent)
             │
             ▼
        ┌───────────┐
        │IN_TRANSIT │
        └─────┬─────┘
             │
             │ deliver()
             │ (Process inventory movements)
             │ (Create new batches at destination)
             │ (Record movements)
             │
             ▼
        ┌──────────┐
        │DELIVERED │ ✅ COMPLETE
        └──────────┘

             │
             │ (At any point before delivered)
             │
             ▼
        ┌──────────┐
        │CANCELLED │
        └──────────┘
```

## Integration Map

```
┌────────────────────────────────────────────────────────────────┐
│                    SYSTEM INTEGRATIONS                         │
└────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│  Purchase Orders    │
│  (Vendor System)    │
└──────────┬──────────┘
           │
           │ When PO received:
           │ Create ProductBatch
           │
           ▼
┌─────────────────────┐
│  Product Batches    │ ◄─── Manual inventory receiving
│  (This System)      │
└──────────┬──────────┘
           │
           ├─► Generate Barcodes
           │
           ├─► Create Dispatches
           │   └─► Transfer between stores
           │
           ├─► Track Movements
           │   └─► Audit trail
           │
           └─► Adjust Stock
               └─► Sales, damages, returns
                   │
                   ▼
           ┌─────────────────┐
           │   Orders/POS    │
           │   (Sales)       │
           └─────────────────┘
                   │
                   │ Scan barcode at checkout
                   │ Get price, check availability
                   │ Reduce batch quantity
                   │
                   ▼
           ┌─────────────────┐
           │  Transactions   │
           └─────────────────┘
```

## Performance Considerations

```
OPTIMIZATIONS:
━━━━━━━━━━━━━━
1. Barcode Scan (Most Frequent Operation)
   ✅ Index on product_barcodes.barcode (unique)
   ✅ Eager load relationships (product, store, batch)
   ✅ Cache current location (avoid multiple movement queries)

2. Batch Queries
   ✅ Index on (product_id, store_id)
   ✅ Index on expiry_date for expiring/expired queries
   ✅ Index on quantity for low_stock queries

3. Dispatch Processing
   ✅ Use database transactions for deliver()
   ✅ Batch insert for multiple movements
   ✅ Queue heavy operations (notifications, reports)

4. Movement History
   ✅ Partition by date for large datasets
   ✅ Index on (product_barcode_id, movement_date)
   ✅ Archive old movements

SCALING:
━━━━━━━
- 1000 batches = ~50ms query time
- 10000 barcodes = ~100ms scan time
- 100 dispatches/day = ~1GB movements/year
```

---

**Complete System**: 32 endpoints across 3 controllers managing physical inventory with barcode tracking and inter-store dispatch functionality.
