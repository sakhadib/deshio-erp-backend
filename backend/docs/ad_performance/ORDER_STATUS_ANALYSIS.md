# Order Status Analysis - Ad Attribution System

**Date:** January 23, 2026  
**Purpose:** Verify Order status values before implementing Phase 4 automation  

---

## Database Schema (orders table)

### Status Field Enum Values
```sql
enum('status', [
    'pending',
    'confirmed',
    'processing',
    'ready_for_pickup',
    'shipped',
    'delivered',
    'cancelled',
    'refunded'
]) DEFAULT 'pending'
```

### Payment Status Enum Values
```sql
enum('payment_status', [
    'pending',
    'paid',
    'failed',
    'refunded'
]) DEFAULT 'pending'
```

---

## Order Model Methods

### Status Check Methods
- `isPending()` - status === 'pending'
- `isConfirmed()` - status === 'confirmed'
- `isProcessing()` - status === 'processing'
- `isShipped()` - status === 'shipped'
- `isDelivered()` - status === 'delivered'
- `isCancelled()` - status === 'cancelled'

### Query Scopes
- `scopePending()` - where status = 'pending'
- `scopeConfirmed()` - where status = 'confirmed'
- `scopeProcessing()` - where status = 'processing'
- `scopeShipped()` - where status = 'shipped'
- `scopeDelivered()` - where status = 'delivered'
- `scopeCancelled()` - where status = 'cancelled'

---

## Phase 4 Implementation Adjustments

Based on actual system values, Phase 4 should use:

### ✅ Countable Statuses (Trigger Attribution)
**Recommended:** `['confirmed', 'processing', 'delivered']`

These statuses exist in the system:
- ✅ `confirmed` - Order confirmed by customer/staff
- ✅ `processing` - Order being fulfilled
- ✅ `delivered` - Order received by customer

**Note:** The plan mentioned `'completed'` which does NOT exist. System uses `'delivered'` as final successful state.

### ✅ Reversal Statuses (Reverse Attribution)
**Recommended:** `['cancelled', 'refunded']`

These statuses exist in the system:
- ✅ `cancelled` - Order cancelled
- ✅ `refunded` - Order refunded

### 🔍 Additional Status Values to Consider

**`'ready_for_pickup'`** - Between processing and delivered
- Should this trigger attribution? Probably YES (customer committed)
- Add to countable statuses if needed

**`'shipped'`** - Between processing and delivered
- Should this trigger attribution? Probably YES (order confirmed and sent)
- Add to countable statuses if needed

**`'pending'`** - Initial state
- Should NOT trigger attribution (not yet confirmed)
- Only after customer confirms order

---

## Recommended Configuration for Phase 4

### Option A: Conservative (Trigger on Confirmed Only)
```php
$countableStatuses = ['confirmed'];
$reversalStatuses = ['cancelled', 'refunded'];
```

**Pros:**
- Attribution happens early (fast feedback)
- Most orders that reach confirmed will complete
- Simple logic

**Cons:**
- Some confirmed orders may still cancel
- Will need reversals occasionally

---

### Option B: Balanced (Trigger on Confirmed + Processing + Shipped + Delivered)
```php
$countableStatuses = ['confirmed', 'processing', 'shipped', 'delivered'];
$reversalStatuses = ['cancelled', 'refunded'];
```

**Pros:**
- Attribution at first commit point (confirmed)
- Covers all fulfillment stages
- Captures ready_for_pickup implicitly (if it transitions to delivered)

**Cons:**
- Need reversal handling for cancellations

---

### Option C: Ultra-Safe (Trigger on Delivered Only)
```php
$countableStatuses = ['delivered'];
$reversalStatuses = ['refunded'];
```

**Pros:**
- Most accurate (only count completed sales)
- Minimal reversals needed

**Cons:**
- Delayed reporting (days/weeks after order created)
- Less timely feedback for marketers

---

## Recommended Choice: **Option B (Balanced)**

Use these exact values in Phase 4 implementation:

```php
// In OrderObserver or config
$countableStatuses = ['confirmed', 'processing', 'shipped', 'delivered'];
$reversalStatuses = ['cancelled', 'refunded'];
```

### Trigger Logic
```php
// Entering countable status for first time → Compute attribution
if (in_array($newStatus, $countableStatuses) 
    && !in_array($oldStatus, $countableStatuses)) {
    // Dispatch ComputeAdAttributionJob
}

// Entering reversal status → Reverse credits
if (in_array($newStatus, $reversalStatuses) 
    && !in_array($oldStatus, $reversalStatuses)) {
    // Reverse credits
}

// Coming back from reversal → Unreverse credits
if (!in_array($newStatus, $reversalStatuses) 
    && in_array($oldStatus, $reversalStatuses)) {
    // Unreverse credits
}
```

---

## Status Transition Flows

### Happy Path
```
pending → confirmed → processing → shipped → delivered
           ↑ ATTRIBUTION TRIGGERED HERE
```

### Cancellation Before Fulfillment
```
pending → confirmed → cancelled
           ↑ ATTRIBUTE   ↑ REVERSE
```

### Refund After Delivery
```
pending → confirmed → processing → delivered → refunded
           ↑ ATTRIBUTE                          ↑ REVERSE
```

### Ready for Pickup Flow
```
pending → confirmed → processing → ready_for_pickup → delivered
           ↑ ATTRIBUTE                                  (no re-trigger)
```

---

## Key Findings Summary

1. ✅ System uses `'confirmed'` (not `'confirmed'` - good!)
2. ✅ System uses `'cancelled'` (not `'canceled'`)
3. ✅ System uses `'refunded'` status (perfect for reversals)
4. ❌ System does NOT have `'completed'` status (use `'delivered'` instead)
5. ✅ Status values are properly enum-constrained in database
6. ✅ Order model has helper methods for all status checks
7. ⚠️ `'ready_for_pickup'` exists but wasn't in original plan - consider adding to countables
8. ⚠️ `'shipped'` exists but wasn't in original plan - should likely be countable too

---

## Action Items for Phase 4

1. ✅ Use `['confirmed', 'processing', 'shipped', 'delivered']` as countable statuses
2. ✅ Use `['cancelled', 'refunded']` as reversal statuses
3. ✅ Remove any references to `'completed'` status from plan
4. ✅ Test transitions thoroughly with actual status values
5. ✅ Consider environment variable for configuration flexibility

---

## Environment Variable Recommendation

Add to `.env` for easy configuration:

```env
AD_ATTRIBUTION_COUNTABLE_STATUSES=confirmed,processing,shipped,delivered
AD_ATTRIBUTION_REVERSAL_STATUSES=cancelled,refunded
```

This allows changing trigger points without code deployment.

---

**Verification Status:** ✅ Complete - Ready to proceed with Phase 4 using actual system status values
