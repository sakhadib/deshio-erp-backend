# Multi-Store Orders - Quick Reference

## Your Scenario Solution

**Problem:**
- Order: Product A + Product B + Product C
- Product A only in Branch X
- Product B only in Branch Y  
- Product C only in Branch Z

**Solution:** ✅ NEW Multi-Store APIs

---

## Quick Start

### 1. Check If Order Needs Multi-Store

```bash
GET /api/multi-store-orders/123/item-availability
```

**Response tells you:**
- Which stores have which products
- If order requires multiple stores
- If order can be fulfilled

---

### 2. Option A: Auto-Assign (Recommended)

```bash
POST /api/multi-store-orders/123/auto-assign
```

**System automatically:**
- Assigns each item to best available store
- Validates inventory at each store
- Updates order status

**Result:**
```
Item 1 (Product A) → Branch X
Item 2 (Product B) → Branch Y
Item 3 (Product C) → Branch Z
```

---

### 3. Option B: Manual Assign

```bash
POST /api/multi-store-orders/123/assign-items
{
  "assignments": [
    {"order_item_id": 456, "store_id": 1},
    {"order_item_id": 457, "store_id": 2},
    {"order_item_id": 458, "store_id": 3}
  ]
}
```

**You choose which store fulfills which item**

---

### 4. Each Store Fulfills Their Items

**Branch X:**
```bash
GET /api/multi-store-orders/stores/1/fulfillment-tasks
```
Sees: Product A from Order #123

**Branch Y:**
```bash
GET /api/multi-store-orders/stores/2/fulfillment-tasks
```
Sees: Product B from Order #123

**Branch Z:**
```bash
GET /api/multi-store-orders/stores/3/fulfillment-tasks
```
Sees: Product C from Order #123

---

## All New Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/multi-store-orders/requiring-multi-store` | GET | List orders needing multi-store |
| `/api/multi-store-orders/{id}/item-availability` | GET | Check which stores have which items |
| `/api/multi-store-orders/{id}/auto-assign` | POST | Auto-assign items to stores |
| `/api/multi-store-orders/{id}/assign-items` | POST | Manually assign items |
| `/api/multi-store-orders/stores/{id}/fulfillment-tasks` | GET | Get tasks for specific store |

---

## Database Change

**Added:** `order_items.store_id` column

**BEFORE:**
```
order.store_id = 5 (entire order from one store)
```

**NOW:**
```
order_items[0].store_id = 1 (Branch X)
order_items[1].store_id = 2 (Branch Y)
order_items[2].store_id = 3 (Branch Z)
```

---

## Status Flow

```
1. Order Created
   └─ store_id: NULL

2. Items Assigned (Auto or Manual)
   └─ status: "multi_store_assigned"
   └─ each item has store_id

3. Stores Fulfill
   └─ Each store fulfills their items

4. Order Complete
   └─ Ready for shipment
```

---

## Backwards Compatibility

✅ **Old orders still work**
- Existing single-store orders unchanged
- All old APIs work exactly as before
- New column defaults to NULL

✅ **No breaking changes**
- 100% additive
- New controller, new routes
- Old functionality intact

---

## Migration

Already done! ✅

```bash
php artisan migrate  # Already ran
```

Added `store_id` column to `order_items` table.

---

## Frontend Example

```javascript
// 1. Check availability
const availability = await fetch(
  `/api/multi-store-orders/123/item-availability`
).then(r => r.json());

if (availability.data.requires_multi_store) {
  // 2. Auto-assign
  const result = await fetch(
    `/api/multi-store-orders/123/auto-assign`,
    { method: 'POST' }
  ).then(r => r.json());
  
  console.log(`Assigned to ${result.data.total_stores} stores`);
  console.log(result.data.stores_involved);
}
```

---

### 4. Create Pathao Shipments (NEW) ⭐

After items assigned and fulfilled, create shipments:

```bash
POST /api/multi-store-shipments/orders/123/create-shipments
```

**Request:**
```json
{
  "recipient_name": "Customer Name",
  "recipient_phone": "01712345678",
  "recipient_address": "Full Address",
  "recipient_city": 1,
  "recipient_zone": 254,
  "recipient_area": 23901,
  "delivery_type": "Normal",
  "item_type": "Parcel",
  "item_weight": 1.5
}
```

**System automatically:**
- Creates separate Pathao shipment for each store
- Uses each store's pathao_store_id
- Returns 3 tracking numbers (one per store)

**Result:**
```
Store 1 (Branch X) → Tracking: PT-12345678
Store 2 (Branch Y) → Tracking: PT-12345679
Store 3 (Branch Z) → Tracking: PT-12345680
```

---

### 5. Track All Shipments

```bash
GET /api/multi-store-shipments/orders/123/track-all
```

**Returns real-time tracking for all shipments**

---

## Key Points

1. **Item-level assignment** - Each item can go to different store
2. **Auto or Manual** - System can decide, or you choose
3. **Store dashboard** - Each store sees only their items
4. **Multi-store Pathao** - Creates separate shipment per store ⭐ NEW
5. **Multiple tracking numbers** - One per store, all tracked together
6. **No breaking changes** - All existing code works
7. **Solves your problem** - Products A, B, C from different branches ✅

---

## Documentation

📖 **Multi-Store Order Management:** `docs/MULTI_STORE_ORDER_FULFILLMENT.md`  
📖 **Pathao Integration (FULL):** `docs/PATHAO_MULTI_STORE_INTEGRATION.md` ⭐ NEW  
📖 **Frontend Quick Start:** `docs/FRONTEND_PATHAO_QUICK_START.md` ⭐ NEW

---

## Complete Workflow

```
1. Customer orders (A + B + C)
   ↓
2. Auto-assign items to stores
   POST /api/multi-store-orders/{id}/auto-assign
   ↓
3. Each store fulfills their items
   ↓
4. Create Pathao shipments
   POST /api/multi-store-shipments/orders/{id}/create-shipments
   ↓
5. Customer gets 3 tracking numbers
   (one for each store)
```

---

**Ready to use!** 🚀

**Version:** 1.1 (With Pathao Integration)  
**Updated:** December 20, 2024
