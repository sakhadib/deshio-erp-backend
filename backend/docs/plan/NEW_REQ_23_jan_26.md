## **1\) Goal and Scope**

### **Goal**

Implement a **rough ad-performance system** where marketers create “Ads/Campaigns” in ERP and attach **targeted products**. When a product sells, the system automatically credits that sale to all active campaigns that target that product, then reports “top performing ads”.

### **Out of Scope (explicit)**

* No Meta/Facebook APIs

* No click/spend/impressions metrics

* No manual selection of ad source at sale time

* No true causal attribution

### **Key constraint**

A single sale can be connected to **multiple campaigns** (overlap). The system must support:

* **Full Credit** (each matching campaign gets 100% credit)

* **Split Credit** (credit divided among matching campaigns so totals remain “honest”)

---

## **2\) Definitions**

### **Entities**

* **Ad Campaign**: ERP object created by marketer (represents “an ad” conceptually).

* **Targeted Products**: products (or variants) linked to a campaign.

* **Sale Time**: timestamp when we consider the sale “countable”.

* **Credits Snapshot**: stored rows that represent how much revenue/profit/units were credited to each campaign for each order item.

### **Attribution Rule (Product-Linked)**

When an **order item** is countable at time `T`:

1. Find all campaigns that target this product (or variant) **and** are active at time `T`

2. Credit the order item’s qty/revenue/profit to each matched campaign (full and/or split)

3. Store the result in a snapshot table so historical reports don’t change if marketer edits campaigns later

---

## **3\) Business Rules**

### **3.1 Campaign “Active at sale time”**

A campaign is considered active if all conditions are true at time `T`:

* `campaign.status = RUNNING`

* `campaign.starts_at <= T`

* `campaign.ends_at IS NULL OR campaign.ends_at >= T`

### **3.2 Product targeting “Effective at sale time”**

A campaign-product mapping is valid at time `T` if:

* `mapping.effective_from <= T`

* `mapping.effective_to IS NULL OR mapping.effective_to >= T`

Effective dating is required so removing a product later doesn’t rewrite history.

### **3.3 What gets credited**

Credits are computed at the **order item** level:

* `credited_qty`

* `credited_revenue`

* `credited_profit` (if available)  
   Optionally store:

* `order_id` for faster reporting

* `store_id` / channel\_id if you segment sales

### **3.4 Credit modes**

Given an order item with item totals:

* `item_qty`

* `item_revenue` (net after discounts)

* `item_profit` (net profit after COGS; optional)

and `k = number of matched campaigns`

**Full Credit**

* Each campaign gets: `item_qty`, `item_revenue`, `item_profit`

**Split Credit**

* Each campaign gets: `item_qty / k`, `item_revenue / k`, `item_profit / k`

Store both modes so UI can toggle. If only one mode is desired, implement split-only (recommended), but keeping both is best.

---

## **4\) Data Model (Database Schema)**

### **4.1 `ad_campaigns`**

Stores campaign metadata.

**Columns**

* `id` (PK)

* `name` (string)

* `platform` (enum/string, e.g., `facebook`, `instagram`, `other`)

* `status` (enum: `DRAFT`, `RUNNING`, `PAUSED`, `ENDED`)

* `starts_at` (datetime, required)

* `ends_at` (datetime, nullable)

* Optional manual planning fields:

  * `budget_type` (enum: `DAILY`, `LIFETIME`, nullable)

  * `budget_amount` (decimal, nullable)

  * `notes` (text, nullable)

* Audit:

  * `created_by`, `updated_by`

  * `created_at`, `updated_at`

**Indexes**

* `(status, starts_at, ends_at)`

* `(platform, status)`

---

### **4.2 `ad_campaign_products`**

Many-to-many mapping of campaign → product (or variant).

**Columns**

* `id` (PK)

* `campaign_id` (FK \-\> ad\_campaigns.id)

* `product_id` (FK \-\> products.id) OR `variant_id` (FK \-\> variants.id)

* `effective_from` (datetime, required)

  * default to campaign.starts\_at

* `effective_to` (datetime, nullable)

* Audit: `created_at`, `updated_at`, `created_by`

**Constraints**

* If you support variants: enforce exactly one of `product_id` or `variant_id` is set.

* Prevent duplicate active mappings: unique constraint on `(campaign_id, product_id, effective_from)` or a soft rule via validation.

**Indexes**

* `(product_id, effective_from, effective_to)`

* `(variant_id, effective_from, effective_to)` if variants used

* `(campaign_id)`

---

### **4.3 `order_item_campaign_credits` (snapshot table)**

Stores computed attribution at the time of sale.

**Columns**

* `id` (PK)

* `order_id` (FK \-\> orders.id) *(denormalize for reporting speed)*

* `order_item_id` (FK \-\> order\_items.id)

* `campaign_id` (FK \-\> ad\_campaigns.id)

* `sale_time` (datetime) — the time used for “active campaign” checks

* `credit_mode` (enum: `FULL`, `SPLIT`)  
   *(or store both in separate numeric columns; enum rows is easier for aggregation)*

* Credited amounts:

  * `credited_qty` (decimal; allow fractional for split)

  * `credited_revenue` (decimal)

  * `credited_profit` (decimal, nullable if profit not available)

* `is_reversed` (boolean default false) *(for refunds/returns)*

* `reversed_at` (datetime nullable)

* `created_at`

**Uniqueness / Idempotency**  
 To ensure re-processing doesn’t duplicate:

* Unique index on `(order_item_id, campaign_id, credit_mode, sale_time)`  
   or better:

* Unique index on `(order_item_id, campaign_id, credit_mode, version_key)` where version\_key changes on recompute.

**Indexes**

* `(campaign_id, sale_time, credit_mode)`

* `(order_id)`

* `(order_item_id)`

* `(sale_time)`

---

## **5\) When to compute credits (Attribution Trigger)**

### **5.1 “Countable sale” definition**

Choose one order milestone as the default:

**Recommended (balanced):**

* Compute credits when order becomes **PAID** or **CONFIRMED** (whichever is reliable in Deshio)

**Alternative (more conservative):**

* Compute credits when order becomes **DELIVERED** (less noise from cancellations)

If your workflow has frequent cancellations before delivery, prefer DELIVERED or support reversal.

### **5.2 Trigger points**

Implement listeners/hooks for order status transitions:

* `OrderStatusChanged(old, new)`

* If `new` enters a configured “countable statuses set” → compute credits

* If `new` enters a configured “non-countable/refund statuses set” → reverse credits or recompute to zero

**Config example**

* Countable: `confirmed`, `processing`, `delivered` (pick one)

* Reversal: `cancelled`, `refunded`, `returned`

---

## **6\) Attribution Computation Algorithm (Detailed)**

For an order with `order_items[]` and chosen `sale_time`:

### **Step A — Gather item totals**

For each order item:

* `item_qty`

* `item_net_revenue` (after discounts; consistent definition required)

* `item_profit` (optional: net revenue \- COGS \- per-item costs)

### **Step B — Find matching campaigns**

Query matching campaigns for each item:

Match conditions:

* campaign.status \= RUNNING

* campaign.starts\_at \<= sale\_time \<= campaign.ends\_at (or ends\_at null)

* mapping matches product/variant and mapping effective range contains sale\_time

Return list of `campaign_id`s.

### **Step C — Produce credits rows**

Let `k = count(matched_campaigns)`

If `k == 0`: do nothing (item remains unattributed)

If `k > 0`:

* Write FULL credits rows (one row per campaign) if FULL mode enabled

* Write SPLIT credits rows (one row per campaign) if SPLIT mode enabled

  * `credited_qty = item_qty / k`

  * `credited_revenue = item_net_revenue / k`

  * `credited_profit = item_profit / k`

### **Step D — Idempotent write strategy**

You must avoid duplicates if the trigger fires twice.

Recommended approach:

1. In a DB transaction:

   * delete existing credits for this `order_id` OR for each `order_item_id` where `sale_time` equals current sale\_time

   * insert newly computed credits

2. Commit

If you want to keep history of recomputes, use a `credits_version` on the order and include it in a unique index instead of deleting.

---

## **7\) Handling Campaign/Product Edits Without Rewriting History**

### **7.1 Editing targeted products**

Do **not** hard-delete mappings. Use effective dating.

* To “remove” a product from a campaign today:

  * set `effective_to = now()` for that mapping

* To “add” a product:

  * create mapping row with `effective_from = now()`

This ensures old orders keep attributing based on the mapping at the time of sale.

### **7.2 Editing campaign start/end**

Campaign schedule changes should affect future attributions only. Past credits are snapshots and should remain.

---

## **8\) Refunds / Returns / Cancellations**

You have two options.

### **Option A (simplest): reverse credits**

When order transitions to a reversal status:

* Mark all related credits as reversed:

  * `is_reversed = true`

  * `reversed_at = now()`

Reporting queries must exclude reversed credits.

**Pros:** easy, preserves audit  
 **Cons:** doesn’t handle partial refunds cleanly

### **Option B (better): adjustment credits**

If partial refunds exist, insert negative credits for the refunded amount/qty.

* `credited_revenue = -refunded_amount_share`

* `credited_qty = -refunded_qty_share`

**Pros:** accurate for partials  
 **Cons:** more complex

**Recommended for v1:** Option A unless partial refunds are common.

---

## **9\) Reporting Requirements (Queries the UI will need)**

All metrics are derived from `order_item_campaign_credits` filtered by:

* date range on `sale_time`

* `is_reversed = false`

* campaign\_id filter (for drilldowns)

* optional credit\_mode \= `SPLIT` or `FULL`

### **9.1 Campaign leaderboard (per date range)**

Aggregate per campaign:

* sum(credited\_qty) as units

* sum(credited\_revenue) as revenue

* sum(credited\_profit) as profit

* count(distinct order\_id) as order\_count (optional)

Sort by revenue/profit/units.

### **9.2 Campaign detail: product breakdown**

Aggregate per campaign and product\_id:

* sum(credited\_qty), sum(credited\_revenue), sum(credited\_profit)

### **9.3 Attribution overlap (health metric)**

Compute:

* average matched campaigns per sold item  
   This can be computed during attribution (store `k` on each credit row or on a separate per-item attribution summary table).

**Optional table:** `order_item_attribution_summary`

* `order_item_id`

* `sale_time`

* `matched_campaign_count` (k)

---

## **10\) API Endpoints (Backend Contract)**

### **10.1 Campaign CRUD**

* `POST /api/ad-campaigns`

* `GET /api/ad-campaigns`

* `GET /api/ad-campaigns/{id}`

* `PUT /api/ad-campaigns/{id}`

* `PATCH /api/ad-campaigns/{id}/status` (RUNNING/PAUSED/ENDED)

### **10.2 Manage targeted products**

* `POST /api/ad-campaigns/{id}/products`

  * body: `{ product_ids: [], effective_from? }`

* `DELETE /api/ad-campaigns/{id}/products/{mapping_id}`

  * should set `effective_to = now()` (soft remove)

### **10.3 Reporting**

* `GET /api/ad-campaigns/reports/leaderboard?from=...&to=...&mode=SPLIT&sort=profit`

* `GET /api/ad-campaigns/{id}/reports/summary?from=...&to=...&mode=SPLIT`

* `GET /api/ad-campaigns/{id}/reports/products?from=...&to=...&mode=SPLIT`

* `GET /api/ad-campaigns/{id}/reports/orders?from=...&to=...&mode=SPLIT` *(returns list of orders/items credited)*

### **10.4 Admin utilities (optional but very useful)**

* `POST /api/ad-campaigns/recompute-order/{order_id}`

* `POST /api/ad-campaigns/backfill?from=...&to=...` *(queues job)*

---

## **11\) Background Jobs / Performance Strategy**

### **11.1 Synchronous vs async**

If order creation must be fast:

* enqueue attribution computation job on “order countable” event

* job computes credits and writes snapshot

If volume is small:

* compute synchronously in transaction after order status change

### **11.2 Backfill job**

Needed when you deploy this feature and want historical reporting.

* iterate orders in range that are countable

* compute credits (idempotent)

### **11.3 Indexing and query shaping**

Ensure high-performance leaderboard by indexing `order_item_campaign_credits` on:

* `(sale_time, credit_mode, is_reversed)`

* `(campaign_id, sale_time, credit_mode, is_reversed)`

---

## **12\) Validation and Guardrails**

### **12.1 Campaign sanity**

* starts\_at required

* ends\_at \>= starts\_at (if present)

* status transitions allowed:

  * DRAFT → RUNNING

  * RUNNING ↔ PAUSED

  * RUNNING/PAUSED → ENDED

### **12.2 Targeted product mapping rules**

* cannot add mapping with effective range outside campaign range unless explicitly allowed

* prevent duplicate overlapping effective windows for same product in same campaign (recommended)

### **12.3 Money rounding rules (Split mode)**

Define rounding rules to avoid penny drift:

* store decimals with sufficient precision (e.g., 4–6 decimal places internally)

* in reports round at display time

* optionally allocate remainder cents to the first campaign to keep per-item totals exact

---

## **13\) Testing Checklist**

### **Core**

* Single campaign targets product → sale credits correctly

* Multiple campaigns target same product → k computed, split credits correct

* Campaign not running at sale time → no credit

* Mapping effective window outside sale time → no credit

* Editing mapping today does not affect yesterday’s credits

### **Status transitions**

* Order becomes countable → credits created once

* Trigger fired twice → still only one set of credits (idempotent)

* Order cancelled/refunded → credits reversed/excluded from reports

### **Reporting**

* Leaderboard totals match expected for SPLIT mode (should equal real totals if no unattributed items)

* FULL mode inflates totals as expected (documented behavior)

---

## **14\) Rollout Plan (Recommended)**

1. Deploy migrations \+ models

2. Implement campaign CRUD \+ product mapping

3. Implement attribution computation on order “countable” transition (async job recommended)

4. Implement leaderboard endpoints

5. Run backfill job for last N days (optional)

6. Add admin “recompute order” endpoint for debugging

---

## **15\) Notes to Include in UI (so stakeholders don’t misinterpret)**

Backend should return metadata in reports:

* `mode = FULL|SPLIT`

* `overlap_score` (avg campaigns per sold item)

* `unattributed_revenue` (revenue from items with k=0)

This keeps everyone honest about the “rough” nature.

