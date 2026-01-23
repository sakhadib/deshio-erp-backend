# Ad Performance System - Phase 1 Implementation

**Date Completed:** January 23, 2026  
**Phase:** Phase 1 - Database Foundation  
**Status:** ✅ Completed  

---

## Overview

Successfully implemented the database foundation for the Ad Performance Attribution System. This phase establishes the core data structures needed to track campaign performance and attribute sales to marketing campaigns.

---

## What Was Done

### 1. Database Tables Created

Four new tables were added to support the ad attribution system:

#### **ad_campaigns** 
Campaign management table storing marketing campaign metadata.

**Key Features:**
- Campaign lifecycle tracking (DRAFT → RUNNING → PAUSED → ENDED)
- Multi-platform support (Facebook, Instagram, Google, TikTok, YouTube, etc.)
- Date range management with starts_at and ends_at
- Optional budget tracking (daily or lifetime budgets)
- Audit trail with created_by and updated_by

**Performance Optimizations:**
- Composite index on (status, starts_at, ends_at) for active campaign queries
- Index on (platform, status) for platform-specific filtering

---

#### **ad_campaign_products**
Junction table linking campaigns to products with effective dating.

**Key Features:**
- Many-to-many relationship between campaigns and products
- **Effective dating system** (effective_from, effective_to) for historical accuracy
- Prevents rewriting past attribution when campaigns are edited
- Audit trail support

**Performance Optimizations:**
- Composite index on (product_id, effective_from, effective_to) for attribution queries
- Index on campaign_id for reverse lookups
- Unique constraint on (campaign_id, product_id, effective_from) to prevent duplicates

**Critical Design Decision:**
When a product is removed from a campaign, we set `effective_to = now()` instead of deleting the record. This ensures that past sales (before removal) still credit the campaign, maintaining historical accuracy.

---

#### **order_item_campaign_credits**
Snapshot table storing attribution credits (the core of the system).

**Key Features:**
- Immutable credit records (snapshots never change after creation)
- Dual credit modes: FULL and SPLIT
  - **FULL mode:** Each campaign gets 100% credit (totals inflated, shows reach)
  - **SPLIT mode:** Credit divided by match count (accurate totals)
- Reversal support for refunds/cancellations
- Denormalized order data for fast reporting

**Performance Optimizations:**
- Unique constraint on (order_item_id, campaign_id, credit_mode, sale_time) for idempotency
- Composite index on (campaign_id, sale_time, credit_mode, is_reversed) for leaderboard queries
- Index on (sale_time, credit_mode, is_reversed) for time-range reports
- Index on order_id for order-level queries

**Data Fields:**
- credited_qty: Product quantity credited (decimal to 4 places for split mode)
- credited_revenue: Net revenue credited (price - discounts)
- credited_profit: Calculated profit (revenue - cost)
- matched_campaigns_count: Number of campaigns that matched at sale time

---

#### **order_item_attribution_summary**
Optional health tracking table for attribution coverage metrics.

**Key Features:**
- One record per order item
- Tracks how many campaigns matched each sale
- Identifies un-attributed sales (matched_campaigns_count = 0)
- Quick dashboard queries for attribution health

**Performance Optimizations:**
- Unique constraint on order_item_id (one-to-one relationship)
- Index on (sale_time, is_attributed) for health dashboard queries

---

## 2. Migration Files Created

| File | Table | Purpose |
|------|-------|---------|
| `2026_01_23_000001_create_ad_campaigns_table.php` | ad_campaigns | Campaign metadata and lifecycle |
| `2026_01_23_000002_create_ad_campaign_products_table.php` | ad_campaign_products | Campaign-product targeting with effective dates |
| `2026_01_23_000003_create_order_item_campaign_credits_table.php` | order_item_campaign_credits | Attribution credit snapshots |
| `2026_01_23_000004_create_order_item_attribution_summary_table.php` | order_item_attribution_summary | Attribution health metrics |

---

## 3. Database Relationships

```
ad_campaigns (1) ──→ (*) ad_campaign_products
                            ↓ (*)
                        products

orders (1) ──→ (*) order_items
                      ↓ (*)
          order_item_campaign_credits
                      ↓ (*)
                 ad_campaigns

order_items (1) ──→ (1) order_item_attribution_summary
```

**Foreign Key Constraints:**
- All foreign keys use `cascadeOnDelete` to maintain referential integrity
- Employee references use `nullOnDelete` to preserve audit history

---

## 4. Migration Execution Results

```
✅ 2026_01_23_000001_create_ad_campaigns_table ..................... 132ms DONE
✅ 2026_01_23_000002_create_ad_campaign_products_table ............. 171ms DONE
✅ 2026_01_23_000003_create_order_item_campaign_credits_table ...... 185ms DONE
✅ 2026_01_23_000004_create_order_item_attribution_summary_table .... 75ms DONE
```

All migrations executed successfully without errors.

---

## Key Design Principles

### 1. Historical Accuracy
The effective dating system on `ad_campaign_products` ensures that editing campaigns today doesn't rewrite attribution for past sales. This is critical for accurate historical reporting.

### 2. Idempotency
The unique constraint on `order_item_campaign_credits` prevents duplicate credits if attribution jobs are retried. This ensures data integrity even with background job failures and retries.

### 3. Performance-First Indexing
All query patterns for attribution and reporting have corresponding indexes:
- Campaign matching queries: indexed on product_id + effective dates
- Leaderboard queries: indexed on campaign_id + sale_time + credit_mode
- Health dashboards: indexed on sale_time + is_attributed

### 4. Immutable Credits
Credits are snapshots that never change after creation. Reversals use the `is_reversed` flag instead of deleting records, maintaining a complete audit trail.

### 5. Dual Credit Modes
Storing both FULL and SPLIT credits provides maximum flexibility:
- **FULL:** Shows campaign reach (how many sales each campaign touched)
- **SPLIT:** Shows actual revenue impact (divided by overlap)

---

## Database Statistics

- **Total Tables Added:** 4
- **Total Indexes Created:** 11
- **Total Foreign Keys:** 11
- **Total Unique Constraints:** 3
- **Migration Execution Time:** 563ms

---

## Next Steps

With Phase 1 complete, the database foundation is in place. Ready to proceed to:

**Phase 2: Models & Relationships**
- Create Eloquent models for all 4 tables
- Define relationships and business logic methods
- Add query scopes for common operations
- Implement validation rules

**Estimated Time:** 1 day

---

## Rollback Procedure

If needed, rollback these migrations:

```bash
php artisan migrate:rollback --step=4
```

This will cleanly drop all 4 tables in reverse order.

---

## Notes

- All tables use Laravel's standard `id()` and `timestamps()` fields
- Decimal fields use appropriate precision: qty (10,4), money (10,2)
- Enum fields defined at database level for data integrity
- Table names follow Laravel pluralization conventions
