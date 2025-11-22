# COGS Accounting Integration

## Problem Overview

Previously, the system was storing COGS (Cost of Goods Sold) values in `order_items.cogs` but **NOT** posting them to the accounting system (`transactions` table). This meant:

❌ Trial balance didn't show COGS expenses
❌ Income statements didn't reflect COGS
❌ P&L reports were incomplete
❌ Gross profit calculations were incorrect

## Solution Implemented

Added automatic COGS accounting transaction creation when orders are completed.

### Double-Entry Bookkeeping

When an order is completed, the system now creates two accounting transactions:

1. **Debit: Cost of Goods Sold (Expense)**
   - Account ID: 17
   - Type: `Debit`
   - Increases COGS expense
   - Amount: Total of all `order_items.cogs` for the order

2. **Credit: Inventory (Asset)**
   - Account ID: 4
   - Type: `Credit`
   - Decreases inventory asset value
   - Amount: Same as COGS debit

### Example Transaction

**Order #ORD-20250122-ABC123**
- Item 1: 2 units × ৳350 cost = ৳700 COGS
- Item 2: 3 units × ৳200 cost = ৳600 COGS
- **Total COGS: ৳1,300**

**Accounting Entries Created:**

| Date | Account | Type | Amount | Description |
|------|---------|------|--------|-------------|
| 2025-01-22 | Cost of Goods Sold (#17) | Debit | ৳1,300 | COGS - Order ORD-20250122-ABC123 |
| 2025-01-22 | Inventory (#4) | Credit | ৳1,300 | COGS - Order ORD-20250122-ABC123 |

## Technical Implementation

### 1. Transaction Model Enhancement

Added new static method in `app/Models/Transaction.php`:

```php
public static function createFromOrderCOGS(Order $order): self
{
    // Calculate total COGS from all order items
    $totalCOGS = $order->items->sum('cogs');
    
    // Create double-entry transactions
    // Debit COGS expense + Credit Inventory asset
}
```

### 2. OrderController Integration

Updated `app/Http/Controllers/OrderController.php` `complete()` method:

```php
// After inventory reduction
try {
    $orderWithItems = $order->fresh(['items']);
    Transaction::createFromOrderCOGS($orderWithItems);
    \Log::info('COGS Transactions Created', [...]);
} catch (\Exception $e) {
    \Log::error('Failed to create COGS transactions', [...]);
    // Order completion continues even if COGS transaction fails
}
```

### 3. Account Resolution

Added helper methods in Transaction model:

```php
// Get COGS expense account (ID: 17)
public static function getCOGSAccountId(): ?int

// Get Inventory asset account (ID: 4)
public static function getInventoryAccountId(): ?int
```

## Verification Steps

### 1. Check COGS Data in Order Items

```sql
SELECT 
    oi.id,
    oi.product_name,
    oi.quantity,
    oi.cogs,
    (oi.quantity * oi.cogs) as total_item_cogs,
    o.order_number,
    o.status
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE o.status = 'completed'
ORDER BY o.id DESC
LIMIT 10;
```

### 2. Check COGS Accounting Transactions

```sql
SELECT 
    t.id,
    t.transaction_date,
    t.type,
    a.name as account_name,
    t.amount,
    t.description,
    t.reference_type,
    t.reference_id
FROM transactions t
JOIN accounts a ON a.id = t.account_id
WHERE t.reference_type = 'App\\Models\\Order'
  AND a.id IN (4, 17)  -- Inventory and COGS accounts
ORDER BY t.created_at DESC
LIMIT 20;
```

### 3. Check Trial Balance

```sql
-- COGS Total (Should show as Debit)
SELECT 
    'COGS' as account,
    SUM(CASE WHEN type = 'Debit' THEN amount ELSE 0 END) as debits,
    SUM(CASE WHEN type = 'Credit' THEN amount ELSE 0 END) as credits,
    SUM(CASE WHEN type = 'Debit' THEN amount ELSE -amount END) as balance
FROM transactions
WHERE account_id = 17  -- Cost of Goods Sold
  AND status = 'completed';

-- Inventory Total (Should decrease by COGS credits)
SELECT 
    'Inventory' as account,
    SUM(CASE WHEN type = 'Debit' THEN amount ELSE 0 END) as debits,
    SUM(CASE WHEN type = 'Credit' THEN amount ELSE 0 END) as credits,
    SUM(CASE WHEN type = 'Debit' THEN amount ELSE -amount END) as balance
FROM transactions
WHERE account_id = 4  -- Inventory
  AND status = 'completed';
```

## Impact on Financial Reports

### Income Statement (P&L)

**Before Fix:**
```
Sales Revenue:        ৳100,000
- COGS:              ৳0         ❌ Missing!
= Gross Profit:      ৳100,000   ❌ Wrong!
- Operating Expenses: ৳20,000
= Net Profit:         ৳80,000    ❌ Overstated!
```

**After Fix:**
```
Sales Revenue:        ৳100,000
- COGS:              ৳60,000    ✅ Correct!
= Gross Profit:      ৳40,000    ✅ Correct!
- Operating Expenses: ৳20,000
= Net Profit:         ৳20,000    ✅ Correct!
```

### Balance Sheet

**Before Fix:**
```
Assets:
  Inventory:          ৳150,000   ❌ Overstated (never decreased)
```

**After Fix:**
```
Assets:
  Inventory:          ৳90,000    ✅ Correct (decreased by COGS credits)
```

## Testing Procedure

### 1. Create and Complete New Order

```bash
# 1. Create a test order
POST /api/orders
{
  "order_type": "counter",
  "customer_id": 1,
  "store_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "unit_price": 1000
    }
  ]
}

# 2. Complete the order
POST /api/orders/{id}/complete
```

### 2. Verify COGS Transactions Created

```bash
# Check transactions table
php artisan tinker
>>> $order = Order::latest()->first();
>>> $cogsTransactions = Transaction::where('reference_type', 'App\Models\Order')
...     ->where('reference_id', $order->id)
...     ->whereIn('account_id', [4, 17])
...     ->get();
>>> $cogsTransactions->count(); // Should be 2 (one debit, one credit)
```

### 3. Verify Logs

```bash
# Check Laravel logs for COGS transaction creation
tail -f storage/logs/laravel.log | grep "COGS"
```

Expected log output:
```
[2025-01-22 10:30:45] local.INFO: COGS Transactions Created {
  "order_id": 123,
  "order_number": "ORD-20250122-ABC123",
  "total_cogs": 1300
}
```

## Error Handling

The COGS transaction creation is **non-blocking**:
- If COGS transaction fails, the order completion still succeeds
- Failure is logged for manual correction
- Admin can manually create missing COGS transactions

### Manual COGS Transaction Creation

If COGS transactions are missing for any order:

```php
// In tinker
$order = Order::find(123);
Transaction::createFromOrderCOGS($order);
```

## Historical Data Migration

For orders completed **before** this fix was implemented, their COGS is stored in `order_items.cogs` but not in the accounting system.

### Option 1: One-Time Migration Script

Create a migration to post COGS for all historical completed orders:

```php
$completedOrders = Order::where('status', 'completed')
    ->whereDoesntHave('transactions', function($q) {
        $q->whereIn('account_id', [4, 17]); // COGS/Inventory accounts
    })
    ->get();

foreach ($completedOrders as $order) {
    try {
        Transaction::createFromOrderCOGS($order);
        echo "Created COGS transactions for Order #{$order->order_number}\n";
    } catch (\Exception $e) {
        echo "Failed for Order #{$order->order_number}: {$e->getMessage()}\n";
    }
}
```

### Option 2: Frontend "Recalculate COGS" Button

Add admin functionality to recalculate and post COGS for specific date ranges.

## Accounts Reference

| ID | Account Name | Type | Sub Type | Purpose |
|----|-------------|------|----------|---------|
| 4 | Inventory | Asset | current_asset | Tracks inventory value |
| 17 | Cost of Goods Sold | Expense | cost_of_goods_sold | Tracks COGS expense |

## Related Files

- `app/Models/Transaction.php` - COGS transaction creation logic
- `app/Http/Controllers/OrderController.php` - Integration point
- `app/Models/Order.php` - Order model with items relationship
- `app/Models/OrderItem.php` - COGS storage

## Summary

✅ **Problem Fixed**: COGS now properly posted to accounting system
✅ **Double-Entry**: Debit COGS expense, Credit Inventory asset
✅ **Automated**: Happens automatically on order completion
✅ **Safe**: Non-blocking error handling
✅ **Verifiable**: Can check transactions table and trial balance
✅ **Logged**: All COGS transaction creation logged

**Financial reports (P&L, trial balance, income statement) now accurately reflect COGS and gross profit!**
