# Ad Performance System - Phase 2 Implementation

**Date Completed:** January 23, 2026  
**Phase:** Phase 2 - Models & Relationships  
**Status:** ✅ Completed  

---

## Overview

Successfully created all Eloquent models for the Ad Performance Attribution System. This phase establishes the data access layer with complete relationships, business logic methods, and query scopes for efficient database operations.

---

## What Was Done

### 1. Models Created

Three core models were implemented to handle campaign management and attribution:

---

#### **AdCampaign Model**
**Location:** `app/Models/AdCampaign.php`

**Purpose:** Manages marketing campaign lifecycle and metadata.

**Key Features:**

**Fillable Attributes:**
- Campaign details: name, platform, status
- Date management: starts_at, ends_at
- Budget tracking: budget_type, budget_amount
- Audit fields: created_by, updated_by
- Additional notes

**Type Casting:**
- `starts_at` and `ends_at` → datetime
- `budget_amount` → decimal:2 for precise currency handling

**Relationships:**
- `targetedProducts()` - HasMany → AdCampaignProduct (products targeted by this campaign)
- `credits()` - HasMany → OrderItemCampaignCredit (all attribution credits for this campaign)
- `createdBy()` - BelongsTo → Employee (who created the campaign)
- `updatedBy()` - BelongsTo → Employee (who last updated the campaign)

**Business Logic Methods:**

1. **`isActiveAt(\DateTime $time): bool`**
   - Checks if campaign is RUNNING at a specific time
   - Validates date range (starts_at ≤ time ≤ ends_at)
   - Critical for attribution matching logic

2. **`canTransitionTo(string $newStatus): bool`**
   - Enforces valid status transitions:
     - DRAFT → RUNNING
     - RUNNING → PAUSED, ENDED
     - PAUSED → RUNNING, ENDED
     - ENDED → (terminal state, no transitions)
   - Prevents invalid state changes

**Query Scopes:**

1. **`scopeActiveAt($query, \DateTime $time)`**
   - Finds campaigns running at specific timestamp
   - Used by attribution engine to match campaigns

2. **`scopeRunning($query)`**
   - Filters to only RUNNING campaigns
   - Quick access to active campaigns

3. **`scopePlatform($query, string $platform)`**
   - Filters campaigns by advertising platform
   - Supports platform-specific reporting

---

#### **AdCampaignProduct Model**
**Location:** `app/Models/AdCampaignProduct.php`

**Purpose:** Manages many-to-many relationship between campaigns and products with effective dating.

**Key Features:**

**Fillable Attributes:**
- campaign_id, product_id (foreign keys)
- effective_from, effective_to (date range for historical accuracy)
- created_by (audit trail)

**Type Casting:**
- `effective_from` and `effective_to` → datetime

**Relationships:**
- `campaign()` - BelongsTo → AdCampaign (parent campaign)
- `product()` - BelongsTo → Product (targeted product)
- `createdBy()` - BelongsTo → Employee (who added this targeting)

**Business Logic Methods:**

1. **`isEffectiveAt(\DateTime $time): bool`**
   - Checks if product targeting was active at specific time
   - Validates: effective_from ≤ time ≤ effective_to
   - Essential for historical attribution accuracy

2. **`deactivate(): void`**
   - Soft-removes product from campaign
   - Sets effective_to = now() (doesn't delete record)
   - Preserves historical data integrity

**Query Scopes:**

1. **`scopeEffectiveAt($query, \DateTime $time)`**
   - Finds product targeting active at specific timestamp
   - Used during attribution to determine if product was targeted

2. **`scopeActive($query)`**
   - Filters to currently active targeting (effective_to is null)
   - Used for campaign management UI

**Critical Design Feature:**
The effective dating system ensures that when a marketer removes a product from a campaign today, past sales (from when the product WAS targeted) still credit that campaign. This prevents rewriting history.

---

#### **OrderItemCampaignCredit Model**
**Location:** `app/Models/OrderItemCampaignCredit.php`

**Purpose:** Stores immutable attribution credit snapshots.

**Key Features:**

**Fillable Attributes:**
- order_id, order_item_id, campaign_id (foreign keys)
- sale_time (when sale occurred)
- credit_mode (FULL or SPLIT)
- credited_qty, credited_revenue, credited_profit (credited amounts)
- is_reversed, reversed_at (refund handling)
- matched_campaigns_count (overlap tracking)

**Type Casting:**
- `sale_time`, `reversed_at` → datetime
- `credited_qty` → decimal:4 (supports fractional quantities in SPLIT mode)
- `credited_revenue`, `credited_profit` → decimal:2 (currency precision)
- `is_reversed` → boolean

**Relationships:**
- `order()` - BelongsTo → Order (parent order)
- `orderItem()` - BelongsTo → OrderItem (specific order line item)
- `campaign()` - BelongsTo → AdCampaign (credited campaign)

**Query Scopes:**

1. **`scopeActive($query)`**
   - Filters to non-reversed credits
   - Used for accurate reporting (excludes refunded sales)

2. **`scopeInDateRange($query, $from, $to)`**
   - Filters credits by sale_time date range
   - Essential for time-period reports (daily, weekly, monthly)

3. **`scopeFullCredit($query)`**
   - Filters to FULL credit mode only
   - Used when showing campaign reach metrics

4. **`scopeSplitCredit($query)`**
   - Filters to SPLIT credit mode only
   - Used when showing accurate revenue attribution

5. **`scopeByCampaign($query, int $campaignId)`**
   - Filters to specific campaign
   - Used for campaign-specific reports

**Design Principle:**
Credits are immutable snapshots. Once created, they never change. Refunds/cancellations use the `is_reversed` flag instead of deletion, maintaining complete audit trail.

---

## 2. Relationships Established

### Campaign → Products (Many-to-Many)
```
AdCampaign (1) ──→ (*) AdCampaignProduct ←── (*) Product
```
- Campaigns can target multiple products
- Products can be in multiple campaigns
- Effective dating prevents historical data conflicts

### Campaign → Credits (One-to-Many)
```
AdCampaign (1) ──→ (*) OrderItemCampaignCredit
```
- Each campaign accumulates many credits over time
- Efficient leaderboard queries via indexed relationships

### Order Item → Credits (One-to-Many)
```
OrderItem (1) ──→ (*) OrderItemCampaignCredit
```
- Each order item can credit multiple campaigns
- Handles campaign overlap naturally

### Employee Audit Trail
```
Employee (1) ──→ (*) AdCampaign (created_by, updated_by)
Employee (1) ──→ (*) AdCampaignProduct (created_by)
```
- Full audit trail for campaign changes
- Accountability and compliance

---

## 3. Business Logic Implemented

### Campaign Lifecycle Management

**Status Transition Validation:**
```
DRAFT → RUNNING → PAUSED → ENDED
         ↓
       ENDED
```
- `canTransitionTo()` enforces valid state changes
- Prevents invalid operations (e.g., ENDED → RUNNING)

**Active Campaign Detection:**
- `isActiveAt()` checks status + date range
- Used by attribution engine for campaign matching

### Effective Dating System

**Product Targeting History:**
- `isEffectiveAt()` validates time-based targeting
- `deactivate()` soft-removes without data loss
- Historical accuracy preserved for past attribution

### Attribution Query Optimization

**Scope Chaining:**
```php
// Example: Find active SPLIT credits for campaign in Q1
OrderItemCampaignCredit::active()
    ->splitCredit()
    ->byCampaign(123)
    ->inDateRange('2026-01-01', '2026-03-31')
    ->get();
```
- Composable scopes for complex queries
- Database-level filtering (efficient)

---

## 4. Type Safety & Data Integrity

### Automatic Type Casting

**Dates:**
- All date fields cast to DateTime objects
- Eliminates manual parsing errors
- Consistent date comparison operations

**Decimals:**
- qty: 4 decimal places (supports fractional splits)
- money: 2 decimal places (standard currency precision)
- Prevents floating-point calculation errors

**Booleans:**
- `is_reversed` cast to boolean
- Clean conditional logic in application code

### Mass Assignment Protection

All models use `$fillable` arrays:
- Explicit whitelisting of assignable attributes
- Protects against mass assignment vulnerabilities
- Laravel best practice compliance

---

## 5. Code Quality Features

### Namespacing
All models properly namespaced under `App\Models` for PSR-4 autoloading.

### Return Type Declarations
Business logic methods use strict return types:
- `bool` for validation methods
- `void` for actions
- Eloquent relationships use proper type hints

### Query Builder Consistency
All scopes return `$query` for chainability, following Laravel conventions.

### Code Readability
- Clear method names describing intent
- Comments on critical business logic
- Consistent formatting and structure

---

## Statistics

- **Models Created:** 3
- **Relationships Defined:** 10
- **Business Logic Methods:** 4
- **Query Scopes:** 10
- **Type Casts:** 11
- **Total Lines of Code:** ~350

---

## Testing Readiness

All models are now ready for:
- Unit testing (business logic validation)
- Relationship testing (database integrity)
- Scope testing (query correctness)
- Integration testing (attribution workflows)

**Test coverage will be implemented in Phase 9.**

---

## Next Steps

With Phase 2 complete, the data access layer is ready. Proceed to:

**Phase 3: Attribution Engine (Core Logic)**
- Create AdAttributionService
- Implement campaign matching algorithm
- Build credit computation logic (FULL + SPLIT modes)
- Handle profit calculation with fallbacks
- Implement reversal/unreversal operations

**Estimated Time:** 2-3 days

---

## Usage Examples

### Check if Campaign is Active
```php
$campaign = AdCampaign::find(1);
$isActive = $campaign->isActiveAt(now());
```

### Find Running Campaigns Targeting a Product
```php
$campaigns = AdCampaign::running()
    ->whereHas('targetedProducts', function($q) use ($productId) {
        $q->where('product_id', $productId)
          ->active();
    })
    ->get();
```

### Get Campaign Performance (SPLIT mode)
```php
$stats = OrderItemCampaignCredit::active()
    ->splitCredit()
    ->byCampaign($campaignId)
    ->inDateRange('2026-01-01', '2026-01-31')
    ->selectRaw('
        SUM(credited_qty) as total_units,
        SUM(credited_revenue) as total_revenue,
        SUM(credited_profit) as total_profit
    ')
    ->first();
```

### Soft-Remove Product from Campaign
```php
$mapping = AdCampaignProduct::find(1);
$mapping->deactivate(); // Sets effective_to = now()
```

---

## Notes

- Models do NOT use the `AutoLogsActivity` trait (removed during implementation) for simplicity
- All models follow Laravel conventions and best practices
- Foreign key relationships match migration constraints
- Query scopes designed for real-world reporting needs
- Business logic methods are fully testable without database

---

**Phase 2 Status:** ✅ Complete and ready for Phase 3
