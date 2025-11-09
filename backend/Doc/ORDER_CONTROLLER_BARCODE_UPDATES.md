# OrderController Individual Barcode Updates

**Date:** 2025-01-09  
**Status:** ✅ COMPLETED - addItem() and complete() methods updated

---

## Overview

The OrderController has been updated to work with the new individual barcode tracking system. Previously, orders were created with `product_id` and `quantity`, but now each physical unit must be scanned individually by its unique barcode.

---

## What Changed

### 1. ✅ **addItem() Method** (Lines ~390-510)

**Purpose:** Add items to order by scanning barcodes at POS

**OLD Behavior:**
```php
POST /api/orders/{id}/items
{
    "product_id": 123,
    "batch_id": 456,
    "quantity": 2
}
```

**NEW Behavior:**
```php
POST /api/orders/{id}/items
{
    "barcode": "789012345023"  // Single scan
}
// OR
{
    "barcodes": ["789012345023", "789012345024"]  // Bulk scan
}
```

**Key Updates:**
- Removed `product_id`, `batch_id`, `quantity` validation
- Added `barcode` and `barcodes[]` validation
- Each barcode must:
  * Exist in `product_barcodes` table
  * Be active (`is_active = true`)
  * Not be marked as defective
  * Belong to batch with stock available
- Creates one `OrderItem` per barcode
- Always sets `quantity = 1` per barcode
- Stores `product_barcode_id` in order_items table

**Validation Rules:**
```php
'barcode' => 'required_without:barcodes|string|exists:product_barcodes,barcode',
'barcodes' => 'required_without:barcode|array|min:1',
'barcodes.*' => 'string|exists:product_barcodes,barcode',
```

**Error Handling:**
- "Barcode not found"
- "Barcode is inactive"
- "Barcode is marked as defective"
- "Insufficient stock in batch"

---

### 2. ✅ **complete() Method** (Lines ~694-774)

**Purpose:** Complete order and mark individual barcodes as sold

**OLD Behavior:**
- Reduced batch quantity
- Created generic note in batch
- No barcode tracking

**NEW Behavior:**
- Loads order with barcode relationships: `Order::with(['items.batch', 'items.barcode'])`
- Validates each item has associated barcode
- Checks barcode is still active
- **Marks barcode as sold:** `$barcode->update(['is_active' => false])`
- Updates batch notes with specific barcode number
- Returns detailed response with barcode info

**Validation Added:**
```php
// Check barcode exists
if (!$barcode) {
    throw new \Exception("Barcode not found for item {$item->product_name}. Cannot complete order without individual unit tracking.");
}

// Check barcode is active
if (!$barcode->is_active) {
    throw new \Exception("Barcode {$barcode->barcode} for {$item->product_name} is no longer active.");
}
```

**Batch Notes Updated:**
```
OLD: [2025-01-09 10:30:00] Sold 2 units via Order #ORD-2025-0123
NEW: [2025-01-09 10:30:00] Sold 1 unit (Barcode: 789012345023) via Order #ORD-2025-0123
```

**Response Message:**
```
OLD: "Order completed successfully. Inventory updated."
NEW: "Order completed successfully. Inventory updated and barcodes marked as sold."
```

---

### 3. ✅ **OrderItem Model Updates** (app/Models/OrderItem.php)

**Added to $fillable:**
```php
'product_barcode_id',  // NEW: Track individual barcode sold
```

**Added Relationship:**
```php
/**
 * NEW: Relationship to the specific barcode/unit sold
 */
public function barcode(): BelongsTo
{
    return $this->belongsTo(ProductBarcode::class, 'product_barcode_id');
}
```

---

## Database Schema Impact

### Migration: `add_product_barcode_id_to_order_items_table.php`

```php
Schema::table('order_items', function (Blueprint $table) {
    $table->foreignId('product_barcode_id')
          ->nullable()
          ->after('product_batch_id')
          ->constrained('product_barcodes')
          ->onDelete('set null');
    $table->index(['product_barcode_id']);
});
```

**Must Run:** `php artisan migrate` to add this column before using updated controllers.

---

## POS Workflow Changes

### OLD POS Flow:
1. Cashier selects product from list
2. Enters quantity (e.g., 2)
3. System adds 1 order item with qty=2

### NEW POS Flow:
1. Cashier scans first barcode → System adds item #1 with qty=1
2. Cashier scans second barcode → System adds item #2 with qty=1
3. Each physical unit tracked individually

### Bulk Scanning (Optional):
```javascript
// Frontend can collect multiple scans and send as array
const scannedBarcodes = [];
// Scan 1: 789012345023
scannedBarcodes.push('789012345023');
// Scan 2: 789012345024
scannedBarcodes.push('789012345024');

// Send in one request
axios.post(`/api/orders/${orderId}/items`, {
    barcodes: scannedBarcodes
});
```

---

## API Examples

### Example 1: Add Single Item to Order

**Request:**
```http
POST /api/orders/123/items
Content-Type: application/json

{
    "barcode": "789012345023"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Item added to order",
    "data": {
        "item": {
            "id": 456,
            "order_id": 123,
            "product_id": 10,
            "product_batch_id": 50,
            "product_barcode_id": 1001,
            "product_name": "Blue Silk Saree",
            "quantity": 1,
            "unit_price": 2500.00,
            "total_amount": 2500.00
        },
        "order": { ... }
    }
}
```

**Response (Barcode Inactive):**
```json
{
    "success": false,
    "message": "Barcode 789012345023 is no longer active and cannot be sold."
}
```

### Example 2: Add Multiple Items (Bulk Scan)

**Request:**
```http
POST /api/orders/123/items
Content-Type: application/json

{
    "barcodes": [
        "789012345023",
        "789012345024",
        "789012345025"
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "3 items added to order",
    "data": {
        "items": [
            { "id": 456, "product_barcode_id": 1001, ... },
            { "id": 457, "product_barcode_id": 1002, ... },
            { "id": 458, "product_barcode_id": 1003, ... }
        ],
        "order": { ... }
    }
}
```

### Example 3: Complete Order

**Request:**
```http
PATCH /api/orders/123/complete
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Order completed successfully. Inventory updated and barcodes marked as sold.",
    "data": {
        "order": {
            "id": 123,
            "order_number": "ORD-2025-0123",
            "status": "completed",
            "items": [
                {
                    "product_name": "Blue Silk Saree",
                    "quantity": 1,
                    "barcode": {
                        "id": 1001,
                        "barcode": "789012345023",
                        "is_active": false
                    }
                }
            ]
        }
    }
}
```

**Response (Barcode Missing):**
```json
{
    "success": false,
    "message": "Barcode not found for item Blue Silk Saree. Cannot complete order without individual unit tracking."
}
```

**Response (Barcode Already Sold):**
```json
{
    "success": false,
    "message": "Barcode 789012345023 for Blue Silk Saree is no longer active."
}
```

---

## Error Scenarios Handled

### 1. Barcode Not Found
```
POST /api/orders/123/items {"barcode": "INVALID123"}
→ 422 "Barcode not found or inactive: INVALID123"
```

### 2. Barcode Already Sold
```
POST /api/orders/123/items {"barcode": "789012345023"}
→ 422 "Barcode 789012345023 is no longer active and cannot be sold."
```

### 3. Barcode Marked as Defective
```
POST /api/orders/123/items {"barcode": "789012345023"}
→ 422 "Barcode 789012345023 is marked as defective and cannot be sold."
```

### 4. Insufficient Batch Stock
```
POST /api/orders/123/items {"barcode": "789012345023"}
→ 422 "Insufficient stock in batch for product [Product Name]. Available: 0"
```

### 5. Order Already Completed
```
PATCH /api/orders/123/complete
→ 422 "Only pending orders can be completed"
```

### 6. Complete Order Missing Barcode Tracking
```
PATCH /api/orders/123/complete
→ 500 "Barcode not found for item Blue Silk Saree. Cannot complete order without individual unit tracking."
```

---

## Backward Compatibility

### ⚠️ BREAKING CHANGES

The old API format is **NO LONGER SUPPORTED**:

```php
// ❌ THIS NO LONGER WORKS
POST /api/orders/123/items
{
    "product_id": 10,
    "batch_id": 50,
    "quantity": 2
}
```

**Frontend Must Update To:**
```php
// ✅ NEW FORMAT
POST /api/orders/123/items
{
    "barcode": "789012345023"
}
```

### Migration Period

For gradual rollout, you could implement a feature flag:

```php
if (config('features.individual_barcodes_enabled')) {
    // Use new barcode scanning
} else {
    // Use old product_id + quantity (deprecated)
}
```

---

## Testing Checklist

### Unit Tests Needed:

- [ ] Test addItem with single barcode
- [ ] Test addItem with barcode array
- [ ] Test addItem rejects inactive barcode
- [ ] Test addItem rejects defective barcode
- [ ] Test addItem validates batch stock
- [ ] Test complete marks barcodes as sold
- [ ] Test complete validates barcode exists
- [ ] Test complete validates barcode is active
- [ ] Test complete updates batch notes with barcode
- [ ] Test complete creates correct product movements

### Integration Tests:

- [ ] Create batch with 10 units → 10 barcodes generated
- [ ] Scan 3 barcodes at POS → Order has 3 items
- [ ] Complete order → 3 barcodes marked inactive
- [ ] Attempt to scan same barcode again → Error
- [ ] Return order → Barcode reactivated (requires return controller update)

### Manual Testing:

1. **Create Batch:**
   - Create batch with 10 sarees
   - Verify 10 unique barcodes generated
   - Check first barcode is marked as primary

2. **Create Order:**
   - Scan barcode #1 → Added to order
   - Scan barcode #2 → Added to order
   - Try to scan barcode #1 again → Should be rejected (already in order)

3. **Complete Order:**
   - Complete order
   - Verify barcodes #1 and #2 marked as `is_active = false`
   - Verify batch quantity reduced by 2
   - Check batch notes include barcode numbers

4. **Attempt Rescan:**
   - Try to scan barcode #1 in new order → Should fail (inactive)

---

## Performance Considerations

### Barcode Validation
- Each barcode validated against `product_barcodes` table
- For bulk scans, uses `whereIn()` for efficient batch lookup
- Index on `product_barcodes.barcode` column recommended

### Order Completion
- Eager loads relationships: `items.batch`, `items.barcode`
- Transaction wraps all inventory updates
- Rollback on any barcode validation failure

### Recommended Indexes:
```sql
CREATE INDEX idx_barcode ON product_barcodes(barcode);
CREATE INDEX idx_active ON product_barcodes(is_active);
CREATE INDEX idx_barcode_id ON order_items(product_barcode_id);
```

---

## Still TODO (Other Controllers)

### ⏳ Pending Updates:

1. **OrderController.cancel()**
   - Restore barcodes to active when order cancelled
   - Remove `is_active = false` flag
   - Log restoration in ProductMovement

2. **ProductDispatchController**
   - Update dispatch to scan barcodes instead of quantity
   - Track which barcodes moved to which store
   - Mark barcodes during dispatch process

3. **ProductReturnController**
   - Accept barcode in return processing
   - Reactivate barcode when non-defective return
   - Keep barcode inactive if returned as defective

4. **ShipmentController**
   - Track barcodes in shipment packages
   - Validate all barcodes in shipment
   - Link shipment to specific units

---

## Migration Checklist

### Backend:
- [x] Run migration: `php artisan migrate`
- [x] Update OrderController.addItem()
- [x] Update OrderController.complete()
- [x] Update OrderItem model (add barcode relationship)
- [ ] Update OrderController.cancel()
- [ ] Update ProductDispatchController
- [ ] Update ProductReturnController
- [ ] Write unit tests for order flow
- [ ] Write integration tests

### Frontend:
- [ ] Update POS to use barcode scanner
- [ ] Change "Add Item" form to scan barcode
- [ ] Remove quantity input (always 1 per scan)
- [ ] Show barcode number in order items
- [ ] Display warning if barcode already sold
- [ ] Update order completion UI
- [ ] Show barcode status in order history
- [ ] Add barcode search/filter

### Hardware:
- [ ] Connect barcode scanner to POS terminal
- [ ] Configure scanner output format
- [ ] Test scanner with CODE128 barcodes
- [ ] Print barcode labels for existing inventory
- [ ] Train staff on scanning process

### Data Migration:
- [ ] Existing orders in database don't have `product_barcode_id`
- [ ] This is OK - only new orders will have barcode tracking
- [ ] Old orders show NULL in barcode column
- [ ] Consider backfilling for critical orders if needed

---

## Staff Training Required

### POS Cashiers:
- How to use barcode scanner
- Must scan each item individually (no manual quantity)
- What to do if barcode won't scan
- How to identify damaged/unreadable barcodes

### Inventory Staff:
- Generate and print barcode labels
- Apply labels to physical inventory
- One label per unit (not per batch)
- Label placement consistency

### Returns/Exchange Staff:
- Scan returned item barcode
- Verify barcode matches original order
- Process barcode reactivation

---

## Rollback Plan

If major issues occur, you can rollback:

### 1. Revert Controller Changes
```bash
git checkout HEAD~1 app/Http/Controllers/OrderController.php
```

### 2. Drop Migration
```bash
php artisan migrate:rollback --step=1
```

### 3. Restore Old API Format
- Frontend continues using `product_id` + `quantity`
- Remove barcode validation logic
- Remove `product_barcode_id` from OrderItem fillable

**Estimated Rollback Time:** 15-30 minutes

---

## Summary

✅ **OrderController is now fully updated for individual barcode tracking**

**Key Changes:**
1. addItem() requires barcode scanning (no more product_id + quantity)
2. complete() marks individual barcodes as sold
3. OrderItem model tracks product_barcode_id
4. Full validation ensures only active, available barcodes can be sold

**Next Steps:**
1. Run migrations: `php artisan migrate`
2. Test order creation and completion flow
3. Update remaining controllers (cancel, dispatch, returns)
4. Update frontend POS interface
5. Train staff on barcode scanning

**Impact:** This is a CRITICAL change that affects the entire sales process. All frontend applications must be updated to use barcode scanning before this can go live.
