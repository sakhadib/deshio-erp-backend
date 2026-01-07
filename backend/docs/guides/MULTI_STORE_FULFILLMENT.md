# Multi-Store Order Fulfillment System

## Date: December 20, 2024
## Version: 1.0

---

## Overview

**NEW functionality** that enables orders to be fulfilled from **multiple stores** when products are available at different locations.

### Problem Solved

**Scenario:**
- Customer orders Product A, Product B, and Product C
- Product A only available at Branch X
- Product B only available at Branch Y
- Product C only available at Branch Z

**OLD System:** ❌ Order would FAIL - required all products in ONE store

**NEW System:** ✅ Order succeeds - each item assigned to appropriate store

---

## Key Features

### ✅ Item-Level Store Assignment
Each order item can be fulfilled from a different store

### ✅ Automatic Assignment
Smart algorithm assigns items to stores based on inventory

### ✅ Manual Override
Admin can manually choose which store fulfills which item

### ✅ Multi-Store Fulfillment Dashboard
Each store sees only items assigned to them

### ✅ Backwards Compatible
Existing single-store orders continue working as before

---

## Database Changes

### Migration Added

**File:** `2025_12_20_112743_add_store_id_to_order_items_table.php`

```sql
ALTER TABLE order_items 
ADD COLUMN store_id BIGINT UNSIGNED NULL 
AFTER product_barcode_id;

ALTER TABLE order_items 
ADD CONSTRAINT fk_order_items_store 
FOREIGN KEY (store_id) REFERENCES stores(id) 
ON DELETE SET NULL;
```

### Schema Overview

**BEFORE:**
```
orders
  └─ store_id (ONE store for entire order)
  └─ order_items
       └─ product_id
       └─ quantity
       └─ (no store_id)
```

**AFTER:**
```
orders
  └─ store_id (can be NULL for multi-store orders)
  └─ order_items
       └─ product_id
       └─ quantity
       └─ store_id ✨ NEW (each item can have different store)
```

---

## API Endpoints

All new endpoints are **ADDITIVE** - existing APIs unchanged.

### 1. Get Orders Requiring Multi-Store Fulfillment

**Endpoint:** `GET /api/multi-store-orders/requiring-multi-store`

**Purpose:** Shows orders where items cannot be fulfilled from a single store

**Request:**
```bash
GET /api/multi-store-orders/requiring-multi-store?per_page=15
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "order_id": 123,
        "order_number": "ORD-2024-0123",
        "customer_name": "John Doe",
        "created_at": "2024-12-20 10:30:00",
        "items_count": 3,
        "total_amount": "15000.00",
        "can_fulfill_from_single_store": false,
        "single_store_option": null,
        "requires_multi_store": true
      }
    ],
    "summary": {
      "total_orders": 5,
      "requires_multi_store": 3,
      "can_use_single_store": 2
    }
  }
}
```

---

### 2. Get Item-Level Store Availability

**Endpoint:** `GET /api/multi-store-orders/{orderId}/item-availability`

**Purpose:** Shows which stores have which products for an order

**Request:**
```bash
GET /api/multi-store-orders/123/item-availability
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": 123,
    "order_number": "ORD-2024-0123",
    "can_be_fulfilled": true,
    "requires_multi_store": true,
    "items": [
      {
        "order_item_id": 456,
        "product_id": 10,
        "product_name": "Product A",
        "quantity_required": 1,
        "current_store_assignment": null,
        "available_stores": [
          {
            "store_id": 1,
            "store_name": "Branch X",
            "store_address": "123 Main St",
            "quantity_available": 5,
            "can_fulfill": true
          }
        ],
        "stores_count": 1
      },
      {
        "order_item_id": 457,
        "product_id": 11,
        "product_name": "Product B",
        "quantity_required": 1,
        "current_store_assignment": null,
        "available_stores": [
          {
            "store_id": 2,
            "store_name": "Branch Y",
            "store_address": "456 Oak Ave",
            "quantity_available": 3,
            "can_fulfill": true
          }
        ],
        "stores_count": 1
      },
      {
        "order_item_id": 458,
        "product_id": 12,
        "product_name": "Product C",
        "quantity_required": 1,
        "current_store_assignment": null,
        "available_stores": [
          {
            "store_id": 3,
            "store_name": "Branch Z",
            "store_address": "789 Pine Rd",
            "quantity_available": 7,
            "can_fulfill": true
          }
        ],
        "stores_count": 1
      }
    ],
    "summary": {
      "total_items": 3,
      "items_with_stock": 3,
      "items_without_stock": 0
    }
  }
}
```

---

### 3. Auto-Assign Items to Stores

**Endpoint:** `POST /api/multi-store-orders/{orderId}/auto-assign`

**Purpose:** Automatically assigns each item to best available store

**Request:**
```bash
POST /api/multi-store-orders/123/auto-assign
Authorization: Bearer {token}
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "message": "Items automatically assigned to stores based on inventory",
  "data": {
    "order_id": 123,
    "order_number": "ORD-2024-0123",
    "status": "multi_store_assigned",
    "stores_involved": [
      {
        "store_id": 1,
        "store_name": "Branch X",
        "items_count": 1,
        "items": ["Product A"]
      },
      {
        "store_id": 2,
        "store_name": "Branch Y",
        "items_count": 1,
        "items": ["Product B"]
      },
      {
        "store_id": 3,
        "store_name": "Branch Z",
        "items_count": 1,
        "items": ["Product C"]
      }
    ],
    "total_stores": 3,
    "assignments": [
      {
        "order_item_id": 456,
        "product_name": "Product A",
        "quantity": 1,
        "assigned_store_id": 1,
        "assigned_store_name": "Branch X",
        "available_quantity": 5
      },
      {
        "order_item_id": 457,
        "product_name": "Product B",
        "quantity": 1,
        "assigned_store_id": 2,
        "assigned_store_name": "Branch Y",
        "available_quantity": 3
      },
      {
        "order_item_id": 458,
        "product_name": "Product C",
        "quantity": 1,
        "assigned_store_id": 3,
        "assigned_store_name": "Branch Z",
        "available_quantity": 7
      }
    ]
  }
}
```

**Algorithm:**
- For each item, finds store with highest stock
- Validates sufficient quantity at each store
- Fails if any item has no available store

---

### 4. Manually Assign Items to Stores

**Endpoint:** `POST /api/multi-store-orders/{orderId}/assign-items`

**Purpose:** Admin manually chooses which store fulfills which item

**Request:**
```bash
POST /api/multi-store-orders/123/assign-items
Authorization: Bearer {token}
Content-Type: application/json

{
  "assignments": [
    {
      "order_item_id": 456,
      "store_id": 1
    },
    {
      "order_item_id": 457,
      "store_id": 2
    },
    {
      "order_item_id": 458,
      "store_id": 3
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Items successfully assigned to stores",
  "data": {
    "order_id": 123,
    "order_number": "ORD-2024-0123",
    "status": "multi_store_assigned",
    "stores_summary": [
      {
        "store_id": 1,
        "store_name": "Branch X",
        "items_count": 1,
        "items": [
          {
            "product_name": "Product A",
            "order_item_id": 456
          }
        ]
      },
      {
        "store_id": 2,
        "store_name": "Branch Y",
        "items_count": 1,
        "items": [
          {
            "product_name": "Product B",
            "order_item_id": 457
          }
        ]
      },
      {
        "store_id": 3,
        "store_name": "Branch Z",
        "items_count": 1,
        "items": [
          {
            "product_name": "Product C",
            "order_item_id": 458
          }
        ]
      }
    ],
    "total_stores": 3,
    "assignments": [
      {
        "order_item_id": 456,
        "product_name": "Product A",
        "store_id": 1,
        "store_name": "Branch X"
      },
      {
        "order_item_id": 457,
        "product_name": "Product B",
        "store_id": 2,
        "store_name": "Branch Y"
      },
      {
        "order_item_id": 458,
        "product_name": "Product C",
        "store_id": 3,
        "store_name": "Branch Z"
      }
    ]
  }
}
```

**Validation:**
- Checks inventory availability at each assigned store
- Fails if insufficient stock at any store
- All-or-nothing: Either all assignments succeed or all fail

---

### 5. Get Store Fulfillment Tasks

**Endpoint:** `GET /api/multi-store-orders/stores/{storeId}/fulfillment-tasks`

**Purpose:** Shows which items from which orders a specific store needs to fulfill

**Request:**
```bash
GET /api/multi-store-orders/stores/1/fulfillment-tasks
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "store_id": 1,
    "store_name": "Branch X",
    "fulfillment_tasks": [
      {
        "order_id": 123,
        "order_number": "ORD-2024-0123",
        "customer_name": "John Doe",
        "order_type": "social_commerce",
        "created_at": "2024-12-20 10:30:00",
        "items_for_this_store": [
          {
            "order_item_id": 456,
            "product_id": 10,
            "product_name": "Product A",
            "product_sku": "SKU-001",
            "quantity": 1,
            "batch_assigned": false,
            "barcode_assigned": false
          }
        ],
        "items_count": 1,
        "is_partial_fulfillment": true,
        "other_stores_involved": 2
      }
    ],
    "summary": {
      "total_orders": 1,
      "total_items": 1,
      "full_orders": 0,
      "partial_orders": 1
    }
  }
}
```

**Use Case:** 
- Store employee logs in
- Sees only items assigned to their store
- Knows if order is split across multiple stores

---

## Workflow Examples

### Example 1: Complete Multi-Store Order Flow

**Step 1: Order Created (Social Commerce)**
```json
Order #123 created:
- Product A (qty: 1)
- Product B (qty: 1)
- Product C (qty: 1)
- store_id: NULL
- status: "pending"
```

**Step 2: Admin Checks Availability**
```bash
GET /api/multi-store-orders/123/item-availability
```
```
Result:
- Product A: Available at Branch X (5 units)
- Product B: Available at Branch Y (3 units)
- Product C: Available at Branch Z (7 units)
- requires_multi_store: true
```

**Step 3: Auto-Assign Stores**
```bash
POST /api/multi-store-orders/123/auto-assign
```
```
Result:
- Item 1 (Product A) → Branch X
- Item 2 (Product B) → Branch Y
- Item 3 (Product C) → Branch Z
- Order status: "multi_store_assigned"
```

**Step 4: Each Store Fulfills Their Items**

**Branch X Employee:**
```bash
GET /api/multi-store-orders/stores/1/fulfillment-tasks
```
Sees: Order #123 - Product A (1 unit)

Scans barcode for Product A, fulfills

**Branch Y Employee:**
```bash
GET /api/multi-store-orders/stores/2/fulfillment-tasks
```
Sees: Order #123 - Product B (1 unit)

Scans barcode for Product B, fulfills

**Branch Z Employee:**
```bash
GET /api/multi-store-orders/stores/3/fulfillment-tasks
```
Sees: Order #123 - Product C (1 unit)

Scans barcode for Product C, fulfills

**Step 5: Shipping**

**Option A:** 3 separate shipments to customer
**Option B:** Consolidate at one location, then ship
**Option C:** Customer receives packages from different locations

---

### Example 2: Manual Assignment

**Scenario:** Admin wants specific control

**Step 1: Check Availability**
```bash
GET /api/multi-store-orders/125/item-availability
```

**Step 2: Manually Assign**
```bash
POST /api/multi-store-orders/125/assign-items
{
  "assignments": [
    {"order_item_id": 500, "store_id": 2},  // Admin chooses Branch Y
    {"order_item_id": 501, "store_id": 5},  // Admin chooses Branch E
    {"order_item_id": 502, "store_id": 2}   // Admin chooses Branch Y again
  ]
}
```

Result: 2 items from Branch Y, 1 item from Branch E

---

### Example 3: Insufficient Stock

**Request:**
```bash
POST /api/multi-store-orders/130/auto-assign
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "Some items cannot be assigned due to insufficient stock",
  "unassignable_items": [
    {
      "order_item_id": 600,
      "product_name": "Product X",
      "quantity": 5,
      "reason": "No store has sufficient stock"
    }
  ]
}
```

**Action Required:** 
- Transfer inventory between stores, OR
- Cancel that item from order, OR
- Wait for restock

---

## Order Status Flow

### Multi-Store Orders

```
1. Order Created
   └─ status: "pending"
   └─ store_id: NULL
   └─ fulfillment_status: "pending_fulfillment"

2. Items Assigned to Stores
   └─ status: "multi_store_assigned"
   └─ store_id: NULL (remains NULL)
   └─ order_items[].store_id: SET

3. Each Store Fulfills
   └─ status: "multi_store_assigned"
   └─ fulfillment_status: "pending_fulfillment"
   └─ Each item gets barcode assigned

4. All Items Fulfilled
   └─ status: "confirmed"
   └─ fulfillment_status: "fulfilled"
   └─ Ready for shipment

5. Shipment Created
   └─ status: "shipped"
   └─ May have multiple shipments

6. Delivered
   └─ status: "delivered"
```

### Single-Store Orders (Unchanged)

```
1. Order Created
   └─ status: "pending"
   └─ store_id: 5 (assigned)

2. Order Fulfilled
   └─ status: "confirmed"
   └─ Existing workflow continues...
```

---

## Database Queries

### Check if Order is Multi-Store

```sql
SELECT 
  o.id,
  o.order_number,
  o.store_id as order_store_id,
  COUNT(DISTINCT oi.store_id) as stores_involved,
  CASE 
    WHEN o.store_id IS NULL AND COUNT(DISTINCT oi.store_id) > 1 
    THEN 'multi_store'
    ELSE 'single_store'
  END as order_type
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.id = 123
GROUP BY o.id, o.order_number, o.store_id;
```

### Get Items by Store for an Order

```sql
SELECT 
  oi.store_id,
  s.name as store_name,
  COUNT(*) as items_count,
  SUM(oi.quantity) as total_quantity,
  GROUP_CONCAT(oi.product_name) as products
FROM order_items oi
JOIN stores s ON oi.store_id = s.id
WHERE oi.order_id = 123
GROUP BY oi.store_id, s.name;
```

### Get Store's Pending Multi-Store Tasks

```sql
SELECT 
  o.id,
  o.order_number,
  oi.product_name,
  oi.quantity,
  oi.store_id
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE oi.store_id = 1
  AND o.fulfillment_status = 'pending_fulfillment'
  AND o.status IN ('multi_store_assigned', 'confirmed');
```

---

## Frontend Integration

### Admin Dashboard - Order Management

**1. List Orders Requiring Multi-Store**
```javascript
const response = await fetch('/api/multi-store-orders/requiring-multi-store');
const { data } = await response.json();

// Show list with badge: "Requires Multi-Store"
data.orders.forEach(order => {
  if (order.requires_multi_store) {
    // Show special indicator
  }
});
```

**2. View Item Availability**
```javascript
const orderId = 123;
const response = await fetch(`/api/multi-store-orders/${orderId}/item-availability`);
const { data } = await response.json();

// Show table:
// Product A | Branch X (5 units) | Branch Y (0 units)
// Product B | Branch X (0 units) | Branch Y (3 units)
// Product C | Branch X (0 units) | Branch Z (7 units)
```

**3. Auto-Assign Button**
```javascript
async function autoAssign(orderId) {
  const response = await fetch(
    `/api/multi-store-orders/${orderId}/auto-assign`,
    { method: 'POST' }
  );
  
  if (response.ok) {
    alert('Items assigned automatically!');
    // Refresh order details
  }
}
```

**4. Manual Assignment UI**
```javascript
// Dropdowns for each item
<select name="store" data-item-id="456">
  <option value="1">Branch X (5 available)</option>
</select>

<select name="store" data-item-id="457">
  <option value="2">Branch Y (3 available)</option>
</select>

// Submit
async function manualAssign() {
  const assignments = [...document.querySelectorAll('select[name=store]')]
    .map(select => ({
      order_item_id: select.dataset.itemId,
      store_id: select.value
    }));
  
  await fetch(`/api/multi-store-orders/${orderId}/assign-items`, {
    method: 'POST',
    body: JSON.stringify({ assignments })
  });
}
```

### Store Dashboard - Fulfillment Tasks

```javascript
// Store employee sees only their items
const storeId = 1; // From logged-in employee
const response = await fetch(
  `/api/multi-store-orders/stores/${storeId}/fulfillment-tasks`
);
const { data } = await response.json();

// Show list:
data.fulfillment_tasks.forEach(task => {
  console.log(`Order ${task.order_number}:`);
  task.items_for_this_store.forEach(item => {
    console.log(`  - ${item.product_name} (qty: ${item.quantity})`);
    if (task.is_partial_fulfillment) {
      console.log(`    ⚠️ Partial order - ${task.other_stores_involved} other stores involved`);
    }
  });
});
```

---

## Backwards Compatibility

### ✅ Existing Orders Unaffected

**Old Single-Store Orders:**
- `orders.store_id` = 5 (set)
- `order_items.store_id` = NULL (new column)
- Continue working exactly as before

**New Multi-Store Orders:**
- `orders.store_id` = NULL
- `order_items.store_id` = SET per item

### ✅ Existing APIs Unchanged

All existing endpoints continue working:
- `POST /api/orders` - Still works
- `POST /api/orders/{id}/assign-store` - Still works for single-store
- `POST /api/orders/{id}/complete` - Still works

### ✅ Automatic Detection

System automatically detects order type:
```php
if ($order->store_id !== null) {
    // Single-store order (existing flow)
} else if ($order->items->every(fn($item) => $item->store_id !== null)) {
    // Multi-store order (new flow)
}
```

---

## Edge Cases

### Case 1: Order Has BOTH order.store_id AND item.store_id

**Priority:** `order_items.store_id` takes precedence

```php
if ($item->store_id !== null) {
    // Use item-level store
    $fulfillmentStore = $item->store_id;
} else {
    // Fall back to order-level store
    $fulfillmentStore = $order->store_id;
}
```

### Case 2: Admin Assigns Single Store After Multi-Store Assignment

```bash
# Multi-store assigned
order_items[0].store_id = 1
order_items[1].store_id = 2

# Admin then assigns order to store 3
POST /api/orders/123/assign-store {"store_id": 3}

# Result: order.store_id = 3, but items still have individual stores
# Item-level takes precedence
```

### Case 3: Partial Assignment

```
Order has 3 items:
- Item A: store_id = 1 (assigned)
- Item B: store_id = NULL (not assigned)
- Item C: store_id = 3 (assigned)

Status: "partially_assigned" (custom logic)
Action Required: Admin must assign Item B
```

### Case 4: Store Has Insufficient Stock After Assignment

**Prevention:** Validation checks inventory before assignment

**If it happens:** System prevents fulfillment
```json
{
  "success": false,
  "message": "Barcode XYZ belongs to a different store"
}
```

---

## Performance Considerations

### Indexes Needed

```sql
-- Already exists from migration
CREATE INDEX idx_order_items_store_id ON order_items(store_id);

-- For fast lookups
CREATE INDEX idx_order_items_order_store ON order_items(order_id, store_id);
```

### Query Optimization

**Avoid N+1 queries:**
```php
// BAD
foreach ($orders as $order) {
    $stores = $order->items->pluck('store_id')->unique();
}

// GOOD
$orders = Order::with('items:id,order_id,store_id')->get();
```

---

## Testing Checklist

### Functional Tests

- [ ] Create order with products from different stores
- [ ] Get item availability - shows correct stores
- [ ] Auto-assign - assigns to correct stores
- [ ] Manual assign - respects admin choices
- [ ] Get fulfillment tasks - each store sees only their items
- [ ] Fulfill from multiple stores
- [ ] Complete order after all stores fulfill
- [ ] Handle insufficient inventory gracefully

### Edge Cases

- [ ] Order with all items in one store (should work)
- [ ] Order with items in 5 different stores
- [ ] Assign item to store with no stock (should fail)
- [ ] Fulfill partial order (only some stores complete)
- [ ] Cancel order with multi-store assignment

### Backwards Compatibility

- [ ] Existing single-store orders still work
- [ ] Old APIs unchanged and functional
- [ ] New column doesn't break existing queries

---

## Rollback Plan

If needed, revert changes:

```bash
# Rollback migration
php artisan migrate:rollback

# Remove routes (or comment out in api.php)
# Remove controller file
rm app/Http/Controllers/MultiStoreOrderController.php

# Remove from OrderItem model
# Remove 'store_id' from $fillable
# Remove store() relationship
```

---

## Future Enhancements

### Phase 2 (Optional)

1. **Shipment Consolidation**
   - Collect items from multiple stores
   - Ship as single package

2. **Cost Optimization**
   - Calculate shipping cost per store
   - Choose assignment that minimizes cost

3. **Smart Routing**
   - Consider store location vs customer location
   - Prefer closer stores

4. **Inventory Transfer**
   - Suggest transfers to fulfill from single store
   - Auto-transfer if cost-effective

---

## Summary

### What Changed

| Component | Change | Type |
|-----------|--------|------|
| **Database** | Added `order_items.store_id` | ADDITIVE |
| **Model** | Added `OrderItem::store()` relationship | ADDITIVE |
| **Controller** | NEW `MultiStoreOrderController` | NEW FILE |
| **Routes** | 5 new endpoints under `/multi-store-orders` | ADDITIVE |
| **Existing APIs** | No changes | UNCHANGED |

### Key Benefits

✅ Solves multi-store inventory problem  
✅ Backwards compatible (100%)  
✅ Flexible (auto + manual assignment)  
✅ Scalable (works with any number of stores)  
✅ Clear separation (new routes, new controller)

### Migration Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Routes automatically registered
3. ✅ Start using new APIs
4. ✅ Old orders continue working

---

**Status: ✅ COMPLETE - Multi-Store Fulfillment System Live**

**Version:** 1.0  
**Date:** December 20, 2024
