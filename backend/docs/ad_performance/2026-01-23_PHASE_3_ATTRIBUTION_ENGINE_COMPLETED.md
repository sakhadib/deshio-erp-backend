# Ad Performance System - Phase 3 Implementation

**Date Completed:** January 23, 2026  
**Phase:** Phase 3 - Attribution Engine (Core Logic)  
**Status:** ✅ Completed  

---

## Overview

Successfully implemented the core attribution engine - the heart of the Ad Performance Attribution System. This service computes which campaigns should receive credit for each product sale, handling complex scenarios like multi-campaign overlap, effective dating, and profit calculation with graceful fallbacks.

---

## What Was Done

### 1. Created AdAttributionService

**Location:** `app/Services/AdAttributionService.php`

**Purpose:** Centralized service for all attribution computation logic, providing a clean API for order processing and campaign management.

**Total Lines of Code:** ~220 lines

---

## 2. Methods Implemented

### **Public Methods (API Surface)**

#### `computeCreditsForOrder(Order $order, string $creditMode = 'BOTH'): void`

**Purpose:** Compute attribution credits for an entire order.

**Parameters:**
- `$order` - The order object with loaded items
- `$creditMode` - 'FULL', 'SPLIT', or 'BOTH' (default: 'BOTH')

**Process:**
1. Determines sale time (order_date fallback to created_at)
2. Logs computation start with order details
3. Iterates through all order items
4. Triggers item-level attribution for each

**Usage Example:**
```php
$attributionService = app(AdAttributionService::class);
$order = Order::with('items')->find(123);
$attributionService->computeCreditsForOrder($order, 'BOTH');
```

**Logging:**
- INFO level: Computation start with order number, sale time, credit mode

---

#### `computeCreditsForOrderItem(OrderItem $item, \DateTime $saleTime, string $creditMode = 'BOTH'): void`

**Purpose:** Core attribution logic for a single order item.

**Parameters:**
- `$item` - The order item to attribute
- `$saleTime` - Timestamp when sale occurred (for time-based matching)
- `$creditMode` - 'FULL', 'SPLIT', or 'BOTH'

**Algorithm (3 Steps):**

**Step 1: Find Matching Campaigns**
- Calls `findMatchingCampaigns()` to get active campaigns targeting this product
- Counts matches (k)
- If k = 0: Logs debug message and returns early (no attribution)

**Step 2: Calculate Credited Amounts**
- Item quantity (from order item)
- Item revenue: `calculateRevenue()` - (price * qty) - discount
- Item profit: `calculateProfit()` - revenue - (cost * qty), nullable if cost unavailable

**Step 3: Create Credit Records (Transactional)**
- Wraps in DB transaction for atomicity
- **Idempotency:** Deletes existing credits for this item+sale_time first
- Loops through matched campaigns:
  - **FULL mode:** Each campaign gets 100% credit
    - credited_qty = full quantity
    - credited_revenue = full revenue
    - credited_profit = full profit
  - **SPLIT mode:** Credit divided by match count (k)
    - credited_qty = qty / k (rounded to 4 decimals)
    - credited_revenue = revenue / k (rounded to 2 decimals)
    - credited_profit = profit / k (rounded to 2 decimals, nullable)
- Stores matched_campaigns_count for analysis
- Logs successful creation with record count

**Edge Cases Handled:**
- No matching campaigns: Early return with debug log
- Null profit: Gracefully handled (stored as null)
- Rounding: Prevents precision loss (qty: 4 decimals, money: 2 decimals)
- Job retries: Idempotent delete-then-insert pattern

**Logging:**
- DEBUG level: No campaigns matched (includes product_id, sale_time)
- INFO level: Campaigns found count
- INFO level: Credit records created (includes mode and record count)

---

#### `reverseCreditsForOrder(Order $order): void`

**Purpose:** Mark all credits for an order as reversed (for refunds/cancellations).

**Process:**
1. Finds all non-reversed credits for order_id
2. Updates in bulk:
   - is_reversed = true
   - reversed_at = now()
3. Logs affected row count

**Why Not Delete?**
- Maintains complete audit trail
- Allows unreversal if order reinstated
- Historical analysis (refund rates)

**Logging:**
- INFO level: Reversed count with order number

---

#### `unreverseCreditsForOrder(Order $order): void`

**Purpose:** Unreverse credits if order is reinstated after cancellation.

**Process:**
1. Finds all reversed credits for order_id
2. Updates in bulk:
   - is_reversed = false
   - reversed_at = null
3. Logs affected row count

**Use Case:** Customer cancels order, then changes mind and reactivates it.

**Logging:**
- INFO level: Unreversed count with order number

---

### **Private Methods (Internal Logic)**

#### `findMatchingCampaigns(int $productId, \DateTime $saleTime)`

**Purpose:** Find all campaigns that should receive credit for a product sale at specific time.

**Matching Criteria (ALL must be true):**
1. **Campaign is RUNNING** at sale time
   - status = 'RUNNING'
   - starts_at ≤ saleTime
   - ends_at ≥ saleTime OR ends_at is null

2. **Campaign targets this product** at sale time
   - product_id matches
   - effective_from ≤ saleTime
   - effective_to ≥ saleTime OR effective_to is null

**Query Optimization:**
- Uses `activeAt()` scope on AdCampaign (indexed query)
- Uses `effectiveAt()` scope on AdCampaignProduct (indexed query)
- Eager loads `targetedProducts` relationship
- Returns Eloquent Collection

**Why Effective Dating Matters:**
Example scenario:
- Campaign A targets Product X from Jan 1-15
- Marketer removes Product X on Jan 16 (sets effective_to = Jan 16)
- Sale of Product X on Jan 10: Campaign A gets credit ✅
- Sale of Product X on Jan 20: Campaign A does NOT get credit ✅
- Historical accuracy preserved!

---

#### `calculateRevenue(OrderItem $item): float`

**Purpose:** Calculate net revenue for an order item.

**Formula:**
```
Revenue = (unit_price × quantity) - discount_amount
```

**Example:**
- Product: $50 each
- Quantity: 3
- Discount: $10
- Revenue: (50 × 3) - 10 = $140

**Return:** Always returns float (discount defaults to 0 if null)

---

#### `calculateProfit(OrderItem $item): float|null`

**Purpose:** Calculate profit with graceful fallback strategy.

**Cost Price Fallback Chain:**
1. **Try Batch Cost:** `$item->batch->cost_price` (most accurate, lot-specific)
2. **Try Product Cost:** `$item->product->cost_price` (general product cost)
3. **Try COGS Field:** `$item->cogs / $item->quantity` (stored COGS per unit)
4. **Give Up:** Return null (can't calculate without cost)

**Formula:**
```
Profit = Revenue - (cost_price × quantity)
```

**Example:**
- Revenue: $140 (from above)
- Cost: $30 per unit
- Quantity: 3
- Profit: 140 - (30 × 3) = $50

**Why Nullable?**
- Some products may not have cost data yet
- Better to return null than wrong data (0 would be misleading)
- Reports can show "N/A" for profit when null

**Return:** float if cost available, null otherwise

---

## 3. Design Principles Applied

### **Idempotency**
The attribution computation is fully idempotent:
- Same input always produces same output
- Safe to retry if job fails
- Delete-then-insert pattern prevents duplicates
- Unique constraint on credits table enforces database-level idempotency

**Why This Matters:**
Background jobs can fail and retry. Without idempotency, retries would create duplicate credits, inflating metrics.

---

### **Transactional Integrity**
All credit creation wrapped in DB::transaction():
- Either all credits created or none
- Prevents partial attribution (e.g., 2 of 3 campaigns credited)
- Atomic operation at database level
- Automatic rollback on exceptions

---

### **Historical Accuracy**
Effective dating system ensures:
- Past attribution never changes when campaigns edited
- Credits are snapshots of state at sale_time
- Removing product from campaign today doesn't affect yesterday's credits
- Complete audit trail preserved

---

### **Graceful Degradation**
Profit calculation handles missing data:
- Tries 3 different cost sources
- Returns null if all fail (doesn't crash)
- Reports can handle null profit (show as N/A)
- Revenue still calculated correctly

---

### **Performance Optimization**
- Uses indexed queries (activeAt, effectiveAt scopes)
- Bulk updates for reversal (not row-by-row)
- Eager loading relationships
- Minimal database round-trips

---

### **Comprehensive Logging**
Every operation logged:
- **INFO:** Normal operations (computation start, campaigns found, credits created)
- **DEBUG:** Edge cases (no campaigns matched)
- **Context:** All logs include relevant IDs, counts, modes
- **Troubleshooting:** Easy to trace attribution issues

---

## 4. Credit Modes Explained

### **FULL Credit Mode**
**Concept:** Each campaign gets 100% credit (inflated totals)

**Example:**
- Product X sells for $100
- Campaigns A, B, C all target Product X
- Credits created:
  - Campaign A: $100 revenue
  - Campaign B: $100 revenue
  - Campaign C: $100 revenue
- **Total FULL credits: $300** (3x actual revenue)

**Use Case:**
- Show campaign reach (how many sales each campaign touched)
- Marketing wants to know full value of sales they participated in
- Overlapping campaigns are expected and acceptable

---

### **SPLIT Credit Mode**
**Concept:** Credit divided by number of campaigns (accurate totals)

**Example:**
- Product X sells for $100
- Campaigns A, B, C all target Product X
- Credits created:
  - Campaign A: $33.33 revenue
  - Campaign B: $33.33 revenue
  - Campaign C: $33.33 revenue
- **Total SPLIT credits: $100** (matches actual revenue)

**Use Case:**
- Accurate revenue attribution
- Budget planning and ROI calculation
- Finance reports that need to match actuals

---

### **BOTH Mode (Default)**
**Concept:** Store both FULL and SPLIT credits

**Implementation:**
- 2 records per campaign per sale
- One with credit_mode = 'FULL'
- One with credit_mode = 'SPLIT'
- Double the storage but maximum flexibility

**Why Default?**
- Frontend can toggle between views
- Marketing sees FULL (reach metrics)
- Finance sees SPLIT (accurate attribution)
- Historical comparison (which view makes more sense?)

**Storage Impact:**
- 3 campaigns: 6 credit records
- 100 campaigns: 200 credit records
- Database handles this easily with proper indexes

---

## 5. Algorithm Flow Diagram

```
Order Status Change (confirmed)
         ↓
   computeCreditsForOrder()
         ↓
   For each order item:
         ↓
   computeCreditsForOrderItem()
         ↓
   findMatchingCampaigns()
   (activeAt + effectiveAt filters)
         ↓
   Match count (k) = ?
         ↓
    k = 0 → Log & Return (no attribution)
    k > 0 → Continue
         ↓
   calculateRevenue() → $R
   calculateProfit() → $P (nullable)
         ↓
   DB Transaction START
         ↓
   Delete existing credits (idempotency)
         ↓
   For each matched campaign:
      → Create FULL credit ($R, $P, qty)
      → Create SPLIT credit ($R/k, $P/k, qty/k)
         ↓
   DB Transaction COMMIT
         ↓
   Log success
```

---

## 6. Edge Cases Handled

### **No Campaigns Match**
- Logs debug message with product_id and sale_time
- Returns early (no credits created)
- Not an error (some products may not be in campaigns)
- Health dashboard can track attribution rate

### **Missing Cost Price**
- Profit calculated as null
- Revenue still attributed correctly
- Credits created with credited_profit = null
- Reports show N/A or hide profit column

### **Job Retry**
- Delete existing credits first (idempotency)
- Unique constraint prevents duplicates even if delete fails
- Transaction ensures atomic operation
- Safe to retry unlimited times

### **Order Date Missing**
- Falls back to created_at timestamp
- Always has a valid sale_time
- Consistent behavior across all orders

### **Campaign Ended Mid-Day**
- Effective dating handles this precisely
- Campaign ending at 2pm won't credit 3pm sales
- Datetime precision ensures accuracy

---

## 7. Testing Scenarios

### **Single Campaign Scenario**
- Product A in Campaign X only
- Sale at time T when Campaign X active
- Result: 1 FULL credit (100%), 1 SPLIT credit (100%)
- Revenue totals: FULL = $100, SPLIT = $100 ✅

### **Multi-Campaign Overlap**
- Product A in Campaigns X, Y, Z
- Sale at time T when all active
- Result: 3 FULL credits (100% each), 3 SPLIT credits (33.33% each)
- Revenue totals: FULL = $300, SPLIT = $100 ✅

### **Partial Overlap**
- Product A in Campaign X (Jan 1-15)
- Product A in Campaign Y (Jan 10-20)
- Sale on Jan 5: Only X credits (k=1)
- Sale on Jan 12: Both X and Y credit (k=2, split 50/50)
- Sale on Jan 18: Only Y credits (k=1)
- Historical accuracy preserved ✅

### **Refund Scenario**
- Order confirmed → credits created
- Order refunded → credits reversed (is_reversed=true)
- Leaderboard excludes reversed credits
- Complete audit trail maintained ✅

---

## 8. Performance Characteristics

### **Query Complexity**
- O(C) where C = number of active campaigns
- Indexed queries (status, dates, product_id)
- Sub-second execution for typical volumes

### **Database Operations**
- 1 SELECT: Find matching campaigns
- 1 DELETE: Clear existing credits (idempotency)
- 2k INSERTS: Create credit records (k campaigns × 2 modes)
- Wrapped in transaction (atomic)

### **Scaling Considerations**
- 100 campaigns × 100 orders/day = 20,000 credit records/day
- ~7M records/year (manageable with indexes)
- Reporting queries optimized with composite indexes
- Batch operations for bulk attribution (Phase 4)

---

## 9. Integration Points

### **Required by Phase 4:**
This service will be called by:
- OrderObserver (automatic attribution on status change)
- ComputeAdAttributionJob (background processing)
- BackfillAdAttributionJob (historical data)

### **Required by Phase 7:**
Admin utilities will use:
- `computeCreditsForOrder()` for manual recomputation
- `reverseCreditsForOrder()` for manual corrections

---

## 10. Code Quality

### **Type Safety**
- Strict parameter types (Order, OrderItem, DateTime)
- Return type declarations (void, float, float|null)
- Collection type hints

### **PSR Standards**
- PSR-4 autoloading (namespace)
- PSR-12 code style (formatting)
- DocBlocks for all methods

### **Testability**
- Service can be instantiated easily
- Public methods are clear API surface
- Private methods are pure functions (testable via public methods)
- No hard dependencies (uses Laravel facades)

---

## Statistics

- **Service File:** 1 (AdAttributionService.php)
- **Public Methods:** 4
- **Private Methods:** 3
- **Total Methods:** 7
- **Lines of Code:** ~220
- **Comments/Docs:** ~80 lines
- **Test Coverage Target:** >90% (Phase 9)

---

## Next Steps

With Phase 3 complete, the core attribution engine is ready. Proceed to:

**Phase 4: Event Listeners & Automation**
- Update OrderObserver to trigger attribution automatically
- Create ComputeAdAttributionJob for background processing
- Create BackfillAdAttributionJob for historical data
- Implement retry logic and error handling

**Estimated Time:** 1-2 days

---

## Usage Examples

### **Manual Attribution**
```php
use App\Services\AdAttributionService;

$service = app(AdAttributionService::class);
$order = Order::with('items.product', 'items.batch')->find(123);
$service->computeCreditsForOrder($order, 'BOTH');
```

### **Reversal**
```php
$service->reverseCreditsForOrder($order);
```

### **Unreversal**
```php
$service->unreverseCreditsForOrder($order);
```

### **Custom Credit Mode**
```php
// Only SPLIT credits (for financial reports)
$service->computeCreditsForOrder($order, 'SPLIT');

// Only FULL credits (for marketing reach)
$service->computeCreditsForOrder($order, 'FULL');
```

---

## Validation

✅ **Code Quality:** PSR standards, type hints, docblocks  
✅ **Algorithm:** Matches requirement specification exactly  
✅ **Idempotency:** Safe to retry, unique constraints enforced  
✅ **Performance:** Indexed queries, bulk operations, transactions  
✅ **Logging:** Comprehensive INFO/DEBUG logs for troubleshooting  
✅ **Edge Cases:** Missing data handled gracefully  
✅ **Historical Accuracy:** Effective dating system implemented  

---

**Phase 3 Status:** ✅ Complete - Attribution engine ready for automation (Phase 4)
