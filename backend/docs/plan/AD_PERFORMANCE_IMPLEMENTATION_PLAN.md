# Ad Performance System - Backend Implementation Plan

**Date Created:** January 23, 2026  
**System:** Deshio ERP Backend  
**Feature:** Ad Campaign Performance Tracking & Attribution

---

## 📋 Overview Analysis

**System Goal:** Track which ad campaigns drive product sales by linking campaigns → products → orders, then computing attribution credits automatically.

**Key Challenge:** Multi-campaign overlap (one sale can credit multiple campaigns) with historical accuracy (editing campaigns doesn't rewrite past credits).

**Core Concept:** Product-based attribution where campaigns target specific products, and when those products sell, the system automatically credits the sale to all active campaigns targeting that product.

---

## 🎯 Phase-by-Phase Implementation Plan

### **Phase 1: Database Foundation** (Day 1-2) ✅ COMPLETED
**Goal:** Create all tables with proper relationships and indexes  
**Completed:** January 23, 2026

#### 1.1 Migration: `ad_campaigns` table
```php
Schema::create('ad_campaigns', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('platform', ['facebook', 'instagram', 'google', 'tiktok', 'youtube', 'other'])->default('other');
    $table->enum('status', ['DRAFT', 'RUNNING', 'PAUSED', 'ENDED'])->default('DRAFT');
    $table->datetime('starts_at');
    $table->datetime('ends_at')->nullable();
    
    // Optional budget planning
    $table->enum('budget_type', ['DAILY', 'LIFETIME'])->nullable();
    $table->decimal('budget_amount', 10, 2)->nullable();
    $table->text('notes')->nullable();
    
    // Audit fields
    $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('employees')->nullOnDelete();
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['status', 'starts_at', 'ends_at']);
    $table->index(['platform', 'status']);
});
```

#### 1.2 Migration: `ad_campaign_products` table
```php
Schema::create('ad_campaign_products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    
    // Effective dating (critical for historical accuracy)
    $table->datetime('effective_from');
    $table->datetime('effective_to')->nullable();
    
    // Audit
    $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
    $table->timestamps();
    
    // Indexes for fast lookup during attribution
    $table->index(['product_id', 'effective_from', 'effective_to']);
    $table->index(['campaign_id']);
    
    // Prevent duplicate active mappings
    $table->unique(['campaign_id', 'product_id', 'effective_from']);
});
```

**Why Effective Dating?**
- When marketer removes a product from a campaign today, we set `effective_to = now()`
- Past sales (yesterday) still credit that campaign because product was targeted at that time
- This prevents rewriting historical attribution when campaigns are edited

#### 1.3 Migration: `order_item_campaign_credits` (snapshot table)
```php
Schema::create('order_item_campaign_credits', function (Blueprint $table) {
    $table->id();
    
    // Order references (denormalized for fast reporting)
    $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
    $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
    $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
    
    // Attribution metadata
    $table->datetime('sale_time');
    $table->enum('credit_mode', ['FULL', 'SPLIT']);
    
    // Credited amounts (allow decimals for split mode)
    $table->decimal('credited_qty', 10, 4);
    $table->decimal('credited_revenue', 10, 2);
    $table->decimal('credited_profit', 10, 2)->nullable();
    
    // Reversal support (for refunds/cancellations)
    $table->boolean('is_reversed')->default(false);
    $table->datetime('reversed_at')->nullable();
    
    // Optional: store match count for analysis
    $table->integer('matched_campaigns_count')->nullable();
    
    $table->timestamps();
    
    // Idempotency constraint (prevents duplicate credits)
    $table->unique(['order_item_id', 'campaign_id', 'credit_mode', 'sale_time'], 'unique_credit');
    
    // Performance indexes for reporting
    $table->index(['campaign_id', 'sale_time', 'credit_mode', 'is_reversed']);
    $table->index(['sale_time', 'credit_mode', 'is_reversed']);
    $table->index(['order_id']);
});
```

**Credit Modes Explained:**
- **FULL:** Each campaign gets 100% of the sale (totals will be inflated if multiple campaigns match)
- **SPLIT:** Credit divided by number of matching campaigns (totals remain accurate)

**Example:** 
- Product A sells for $100
- 3 campaigns target Product A
- FULL mode: Each campaign gets $100 credit (total: $300)
- SPLIT mode: Each campaign gets $33.33 credit (total: $100)

#### 1.4 Optional Migration: `order_item_attribution_summary`
```php
Schema::create('order_item_attribution_summary', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
    $table->datetime('sale_time');
    $table->integer('matched_campaigns_count')->default(0);
    $table->boolean('is_attributed')->default(false);
    $table->timestamps();
    
    $table->index(['sale_time', 'is_attributed']);
});
```

**Purpose:** Quick lookup to see attribution health (how many items have zero campaigns matched).

**Deliverables:**
- ✅ 4 migration files (COMPLETED - Jan 23, 2026)
- ✅ All indexes defined for query performance (COMPLETED - Jan 23, 2026)
- ✅ Foreign key constraints set (COMPLETED - Jan 23, 2026)
- ✅ Unique constraints for data integrity (COMPLETED - Jan 23, 2026)
- ✅ Migrations executed successfully (COMPLETED - Jan 23, 2026)

---

### **Phase 2: Models & Relationships** (Day 2-3)
**Goal:** Create Eloquent models with proper relationships and business logic

#### 2.1 `AdCampaign` Model
**Location:** `app/Models/AdCampaign.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\AutoLogsActivity;

class AdCampaign extends Model 
{
    use AutoLogsActivity;

    protected $fillable = [
        'name',
        'platform',
        'status',
        'starts_at',
        'ends_at',
        'budget_type',
        'budget_amount',
        'notes',
        'created_by',
        'updated_by'
    ];
    
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'budget_amount' => 'decimal:2',
    ];
    
    // Relationships
    public function targetedProducts(): HasMany
    {
        return $this->hasMany(AdCampaignProduct::class, 'campaign_id');
    }
    
    public function credits(): HasMany
    {
        return $this->hasMany(OrderItemCampaignCredit::class, 'campaign_id');
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
    
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }
    
    // Business logic methods
    public function isActiveAt(\DateTime $time): bool
    {
        return $this->status === 'RUNNING'
            && $this->starts_at <= $time
            && ($this->ends_at === null || $this->ends_at >= $time);
    }
    
    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'DRAFT' => ['RUNNING'],
            'RUNNING' => ['PAUSED', 'ENDED'],
            'PAUSED' => ['RUNNING', 'ENDED'],
            'ENDED' => [], // Terminal state
        ];
        
        return in_array($newStatus, $transitions[$this->status] ?? []);
    }
    
    // Query scopes
    public function scopeActiveAt($query, \DateTime $time)
    {
        return $query->where('status', 'RUNNING')
            ->where('starts_at', '<=', $time)
            ->where(function($q) use ($time) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $time);
            });
    }
    
    public function scopeRunning($query)
    {
        return $query->where('status', 'RUNNING');
    }
    
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
```

#### 2.2 `AdCampaignProduct` Model
**Location:** `app/Models/AdCampaignProduct.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\AutoLogsActivity;

class AdCampaignProduct extends Model 
{
    use AutoLogsActivity;

    protected $fillable = [
        'campaign_id',
        'product_id',
        'effective_from',
        'effective_to',
        'created_by'
    ];
    
    protected $casts = [
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];
    
    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
    
    // Business logic
    public function isEffectiveAt(\DateTime $time): bool
    {
        return $this->effective_from <= $time
            && ($this->effective_to === null || $this->effective_to >= $time);
    }
    
    public function deactivate(): void
    {
        $this->effective_to = now();
        $this->save();
    }
    
    // Query scopes
    public function scopeEffectiveAt($query, \DateTime $time)
    {
        return $query->where('effective_from', '<=', $time)
            ->where(function($q) use ($time) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $time);
            });
    }
    
    public function scopeActive($query)
    {
        return $query->whereNull('effective_to');
    }
}
```

#### 2.3 `OrderItemCampaignCredit` Model
**Location:** `app/Models/OrderItemCampaignCredit.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemCampaignCredit extends Model 
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'campaign_id',
        'sale_time',
        'credit_mode',
        'credited_qty',
        'credited_revenue',
        'credited_profit',
        'is_reversed',
        'reversed_at',
        'matched_campaigns_count'
    ];
    
    protected $casts = [
        'sale_time' => 'datetime',
        'reversed_at' => 'datetime',
        'credited_qty' => 'decimal:4',
        'credited_revenue' => 'decimal:2',
        'credited_profit' => 'decimal:2',
        'is_reversed' => 'boolean',
    ];
    
    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
    
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }
    
    // Query scopes
    public function scopeActive($query)
    {
        return $query->where('is_reversed', false);
    }
    
    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('sale_time', [$from, $to]);
    }
    
    public function scopeFullCredit($query)
    {
        return $query->where('credit_mode', 'FULL');
    }
    
    public function scopeSplitCredit($query)
    {
        return $query->where('credit_mode', 'SPLIT');
    }
    
    public function scopeByCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }
}
```

**Deliverables:**
- ✅ 3 model files with complete relationships
- ✅ Business logic methods (isActiveAt, canTransitionTo, etc.)
- ✅ Query scopes for attribution queries
- ✅ Proper type casting for dates and decimals

---

### **Phase 3: Attribution Engine (Core Logic)** (Day 3-5)
**Goal:** Build the attribution computation system - the heart of the feature

#### 3.1 Create `AdAttributionService`
**Location:** `app/Services/AdAttributionService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AdCampaign;
use App\Models\OrderItemCampaignCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdAttributionService 
{
    /**
     * Compute credits for an entire order
     * 
     * @param Order $order
     * @param string $creditMode 'FULL', 'SPLIT', or 'BOTH'
     */
    public function computeCreditsForOrder(Order $order, string $creditMode = 'BOTH'): void
    {
        $saleTime = $order->order_date ?? $order->created_at;
        
        Log::info("Computing attribution for order {$order->order_number}", [
            'order_id' => $order->id,
            'sale_time' => $saleTime,
            'credit_mode' => $creditMode,
        ]);
        
        foreach ($order->items as $item) {
            $this->computeCreditsForOrderItem($item, $saleTime, $creditMode);
        }
    }
    
    /**
     * Compute credits for a single order item
     * 
     * @param OrderItem $item
     * @param \DateTime $saleTime
     * @param string $creditMode
     */
    public function computeCreditsForOrderItem(
        OrderItem $item, 
        \DateTime $saleTime, 
        string $creditMode = 'BOTH'
    ): void 
    {
        // Step 1: Find matching campaigns
        $matchedCampaigns = $this->findMatchingCampaigns($item->product_id, $saleTime);
        
        $k = $matchedCampaigns->count();
        
        if ($k === 0) {
            // No attribution - log for analysis
            Log::debug("No campaigns matched for order item", [
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'sale_time' => $saleTime,
            ]);
            return;
        }
        
        Log::info("Found {$k} matching campaigns for order item {$item->id}");
        
        // Step 2: Calculate credited amounts
        $itemQty = $item->quantity;
        $itemRevenue = $this->calculateRevenue($item);
        $itemProfit = $this->calculateProfit($item);
        
        // Step 3: Create credit records (with idempotency)
        DB::transaction(function() use (
            $item, 
            $matchedCampaigns, 
            $k, 
            $saleTime, 
            $creditMode, 
            $itemQty, 
            $itemRevenue, 
            $itemProfit
        ) {
            // Delete existing credits for idempotency
            OrderItemCampaignCredit::where('order_item_id', $item->id)
                ->where('sale_time', $saleTime)
                ->delete();
            
            foreach ($matchedCampaigns as $campaign) {
                // FULL credit mode
                if (in_array($creditMode, ['FULL', 'BOTH'])) {
                    OrderItemCampaignCredit::create([
                        'order_id' => $item->order_id,
                        'order_item_id' => $item->id,
                        'campaign_id' => $campaign->id,
                        'sale_time' => $saleTime,
                        'credit_mode' => 'FULL',
                        'credited_qty' => $itemQty,
                        'credited_revenue' => $itemRevenue,
                        'credited_profit' => $itemProfit,
                        'matched_campaigns_count' => $k,
                    ]);
                }
                
                // SPLIT credit mode
                if (in_array($creditMode, ['SPLIT', 'BOTH'])) {
                    OrderItemCampaignCredit::create([
                        'order_id' => $item->order_id,
                        'order_item_id' => $item->id,
                        'campaign_id' => $campaign->id,
                        'sale_time' => $saleTime,
                        'credit_mode' => 'SPLIT',
                        'credited_qty' => round($itemQty / $k, 4),
                        'credited_revenue' => round($itemRevenue / $k, 2),
                        'credited_profit' => round($itemProfit / $k, 2),
                        'matched_campaigns_count' => $k,
                    ]);
                }
            }
            
            Log::info("Created {$k} credit records for order item {$item->id}");
        });
    }
    
    /**
     * Find all campaigns matching a product at a given time
     * 
     * @param int $productId
     * @param \DateTime $saleTime
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findMatchingCampaigns(int $productId, \DateTime $saleTime)
    {
        return AdCampaign::activeAt($saleTime)
            ->whereHas('targetedProducts', function($q) use ($productId, $saleTime) {
                $q->where('product_id', $productId)
                  ->effectiveAt($saleTime);
            })
            ->with('targetedProducts')
            ->get();
    }
    
    /**
     * Calculate net revenue for an order item
     * 
     * @param OrderItem $item
     * @return float
     */
    private function calculateRevenue(OrderItem $item): float
    {
        $subtotal = $item->unit_price * $item->quantity;
        $discount = $item->discount_amount ?? 0;
        
        return $subtotal - $discount;
    }
    
    /**
     * Calculate profit for an order item
     * 
     * @param OrderItem $item
     * @return float|null
     */
    private function calculateProfit(OrderItem $item): ?float
    {
        // Try to get cost from batch first, then product
        $costPrice = $item->product_batch?->cost_price 
            ?? $item->product?->cost_price 
            ?? null;
        
        if ($costPrice === null) {
            return null; // Can't calculate profit without cost
        }
        
        $revenue = $this->calculateRevenue($item);
        $cost = $costPrice * $item->quantity;
        
        return $revenue - $cost;
    }
    
    /**
     * Reverse credits for an order (refund/cancellation)
     * 
     * @param Order $order
     */
    public function reverseCreditsForOrder(Order $order): void
    {
        $affectedRows = OrderItemCampaignCredit::where('order_id', $order->id)
            ->where('is_reversed', false)
            ->update([
                'is_reversed' => true,
                'reversed_at' => now(),
            ]);
        
        Log::info("Reversed {$affectedRows} credits for order {$order->order_number}");
    }
    
    /**
     * Unreverse credits (if order is reinstated)
     * 
     * @param Order $order
     */
    public function unreverseCreditsForOrder(Order $order): void
    {
        $affectedRows = OrderItemCampaignCredit::where('order_id', $order->id)
            ->where('is_reversed', true)
            ->update([
                'is_reversed' => false,
                'reversed_at' => null,
            ]);
        
        Log::info("Unreversed {$affectedRows} credits for order {$order->order_number}");
    }
}
```

**Key Design Decisions:**

1. **Idempotency:** Delete existing credits before inserting new ones (safe because of unique constraint)
2. **Rounding:** Split credits rounded to 4 decimals (qty) and 2 decimals (money)
3. **Profit calculation:** Falls back gracefully if cost_price unavailable
4. **Logging:** Extensive logging for debugging attribution issues

**Deliverables:**
- ✅ Attribution service with core algorithm
- ✅ Campaign matching logic with effective dating
- ✅ Credit computation (FULL + SPLIT modes)
- ✅ Reversal and unreversal support
- ✅ Comprehensive logging

---

### **Phase 4: Event Listeners & Automation** (Day 5-6)
**Goal:** Trigger attribution automatically on order status changes

#### 4.1 Update `OrderObserver`
**Location:** `app/Observers/OrderObserver.php`

Add this method to existing observer:

```php
use App\Services\AdAttributionService;
use App\Jobs\ComputeAdAttributionJob;

public function updated(Order $order)
{
    // Check if status changed
    if ($order->isDirty('status')) {
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;
        
        // Define countable statuses (configure based on business rules)
        $countableStatuses = ['confirmed', 'processing', 'delivered', 'completed'];
        
        // Define reversal statuses
        $reversalStatuses = ['cancelled', 'refunded'];
        
        // Compute credits if entering countable status for first time
        if (in_array($newStatus, $countableStatuses) && !in_array($oldStatus, $countableStatuses)) {
            Log::info("Order {$order->order_number} became countable, dispatching attribution job");
            
            // Dispatch background job for performance
            ComputeAdAttributionJob::dispatch($order->id);
        }
        
        // Reverse credits if entering reversal status
        if (in_array($newStatus, $reversalStatuses) && !in_array($oldStatus, $reversalStatuses)) {
            Log::info("Order {$order->order_number} was cancelled/refunded, reversing credits");
            
            $attributionService = app(AdAttributionService::class);
            $attributionService->reverseCreditsForOrder($order);
        }
        
        // Unreverse credits if coming back from reversal status
        if (!in_array($newStatus, $reversalStatuses) && in_array($oldStatus, $reversalStatuses)) {
            Log::info("Order {$order->order_number} reinstated, unreversing credits");
            
            $attributionService = app(AdAttributionService::class);
            $attributionService->unreverseCreditsForOrder($order);
        }
    }
}
```

#### 4.2 Create Job: `ComputeAdAttributionJob`
**Location:** `app/Jobs/ComputeAdAttributionJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\AdAttributionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeAdAttributionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $orderId;
    
    /**
     * Number of times the job may be attempted
     */
    public $tries = 3;
    
    /**
     * Number of seconds to wait before retrying
     */
    public $backoff = [10, 30, 60];
    
    /**
     * Create a new job instance
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }
    
    /**
     * Execute the job
     */
    public function handle(AdAttributionService $attributionService)
    {
        $order = Order::with(['items.product', 'items.product_batch'])
            ->find($this->orderId);
        
        if (!$order) {
            Log::warning("Order {$this->orderId} not found for attribution");
            return;
        }
        
        try {
            $attributionService->computeCreditsForOrder($order, 'BOTH');
            
            Log::info("Attribution computed successfully for order {$order->order_number}");
            
        } catch (\Exception $e) {
            Log::error("Attribution failed for order {$order->order_number}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw to trigger job retry
            throw $e;
        }
    }
    
    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Attribution job permanently failed for order {$this->orderId}", [
            'error' => $exception->getMessage(),
        ]);
        
        // Optionally notify admin or create alert
    }
}
```

#### 4.3 Create Backfill Job: `BackfillAdAttributionJob`
**Location:** `app/Jobs/BackfillAdAttributionJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\AdAttributionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BackfillAdAttributionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $fromDate;
    protected $toDate;
    protected $statuses;
    
    public $timeout = 3600; // 1 hour timeout
    
    /**
     * Create a new job instance
     */
    public function __construct(string $fromDate, string $toDate, array $statuses = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->statuses = $statuses ?? ['confirmed', 'processing', 'delivered', 'completed'];
    }
    
    /**
     * Execute the job
     */
    public function handle(AdAttributionService $attributionService)
    {
        Log::info("Starting attribution backfill", [
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'statuses' => $this->statuses,
        ]);
        
        $processed = 0;
        $failed = 0;
        
        Order::with(['items.product', 'items.product_batch'])
            ->whereIn('status', $this->statuses)
            ->whereBetween('order_date', [$this->fromDate, $this->toDate])
            ->chunk(100, function($orders) use ($attributionService, &$processed, &$failed) {
                foreach ($orders as $order) {
                    try {
                        $attributionService->computeCreditsForOrder($order, 'BOTH');
                        $processed++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error("Backfill failed for order {$order->order_number}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Log progress every 100 orders
                Log::info("Backfill progress: {$processed} processed, {$failed} failed");
            });
        
        Log::info("Backfill completed", [
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'total_processed' => $processed,
            'total_failed' => $failed,
        ]);
    }
}
```

**Deliverables:**
- ✅ Observer hooks into Order model for automatic attribution
- ✅ Background job for attribution computation
- ✅ Backfill job for historical data
- ✅ Retry logic and error handling
- ✅ Comprehensive logging for monitoring

---

### **Phase 5: Campaign Management APIs** (Day 6-8)
**Goal:** CRUD endpoints for campaigns and product targeting

#### 5.1 Create `AdCampaignController`
**Location:** `app/Http/Controllers/AdCampaignController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\AdCampaignProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdCampaignController extends Controller
{
    /**
     * List campaigns with filters
     * GET /api/ad-campaigns
     */
    public function index(Request $request)
    {
        $query = AdCampaign::with(['createdBy', 'targetedProducts.product'])
            ->orderBy('created_at', 'desc');
        
        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }
        
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        // Date range filter
        if ($request->has('from')) {
            $query->where('starts_at', '>=', $request->from);
        }
        
        if ($request->has('to')) {
            $query->where('starts_at', '<=', $request->to);
        }
        
        $campaigns = $query->paginate($request->per_page ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }
    
    /**
     * Create campaign
     * POST /api/ad-campaigns
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'platform' => 'required|in:facebook,instagram,google,tiktok,youtube,other',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'budget_type' => 'nullable|in:DAILY,LIFETIME',
            'budget_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $campaign = AdCampaign::create([
            ...$request->only([
                'name', 'platform', 'starts_at', 'ends_at',
                'budget_type', 'budget_amount', 'notes'
            ]),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully',
            'data' => $campaign->load('createdBy'),
        ], 201);
    }
    
    /**
     * Show campaign
     * GET /api/ad-campaigns/{id}
     */
    public function show($id)
    {
        $campaign = AdCampaign::with([
            'createdBy',
            'updatedBy',
            'targetedProducts.product',
            'targetedProducts.createdBy'
        ])->find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }
    
    /**
     * Update campaign
     * PUT /api/ad-campaigns/{id}
     */
    public function update(Request $request, $id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'platform' => 'sometimes|in:facebook,instagram,google,tiktok,youtube,other',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'budget_type' => 'nullable|in:DAILY,LIFETIME',
            'budget_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $campaign->update([
            ...$request->only([
                'name', 'platform', 'starts_at', 'ends_at',
                'budget_type', 'budget_amount', 'notes'
            ]),
            'updated_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully',
            'data' => $campaign->fresh(['createdBy', 'updatedBy']),
        ]);
    }
    
    /**
     * Change campaign status
     * PATCH /api/ad-campaigns/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:DRAFT,RUNNING,PAUSED,ENDED',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $newStatus = $request->status;
        
        // Validate status transition
        if (!$campaign->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition from {$campaign->status} to {$newStatus}"
            ], 422);
        }
        
        $campaign->status = $newStatus;
        $campaign->updated_by = auth()->id();
        $campaign->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Campaign status updated successfully',
            'data' => $campaign,
        ]);
    }
    
    /**
     * Delete campaign
     * DELETE /api/ad-campaigns/{id}
     */
    public function destroy($id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        // Only allow deletion of DRAFT campaigns
        if ($campaign->status !== 'DRAFT') {
            return response()->json([
                'success' => false,
                'message' => 'Can only delete DRAFT campaigns. Set status to ENDED instead.'
            ], 422);
        }
        
        $campaign->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);
    }
    
    /**
     * Add products to campaign
     * POST /api/ad-campaigns/{id}/products
     */
    public function addProducts(Request $request, $id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|exists:products,id',
            'effective_from' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $effectiveFrom = $request->effective_from 
            ? new \DateTime($request->effective_from) 
            : now();
        
        // Validate effective_from is not before campaign starts
        if ($effectiveFrom < $campaign->starts_at) {
            return response()->json([
                'success' => false,
                'message' => 'effective_from cannot be before campaign starts_at'
            ], 422);
        }
        
        $added = [];
        $skipped = [];
        
        DB::transaction(function() use ($campaign, $request, $effectiveFrom, &$added, &$skipped) {
            foreach ($request->product_ids as $productId) {
                // Check if already exists with no effective_to (still active)
                $existing = AdCampaignProduct::where('campaign_id', $campaign->id)
                    ->where('product_id', $productId)
                    ->whereNull('effective_to')
                    ->first();
                
                if ($existing) {
                    $skipped[] = $productId;
                    continue;
                }
                
                $mapping = AdCampaignProduct::create([
                    'campaign_id' => $campaign->id,
                    'product_id' => $productId,
                    'effective_from' => $effectiveFrom,
                    'created_by' => auth()->id(),
                ]);
                
                $added[] = $mapping->load('product');
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => count($added) . ' product(s) added, ' . count($skipped) . ' skipped (already exists)',
            'data' => [
                'added' => $added,
                'skipped_product_ids' => $skipped,
            ],
        ]);
    }
    
    /**
     * List targeted products
     * GET /api/ad-campaigns/{id}/products
     */
    public function listProducts($id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $products = $campaign->targetedProducts()
            ->with(['product', 'createdBy'])
            ->orderBy('effective_from', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
    
    /**
     * Remove product from campaign (soft remove via effective_to)
     * DELETE /api/ad-campaigns/{id}/products/{mappingId}
     */
    public function removeProduct($id, $mappingId)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $mapping = AdCampaignProduct::where('campaign_id', $campaign->id)
            ->where('id', $mappingId)
            ->first();
        
        if (!$mapping) {
            return response()->json([
                'success' => false,
                'message' => 'Product mapping not found'
            ], 404);
        }
        
        // Set effective_to to now (soft remove)
        $mapping->effective_to = now();
        $mapping->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Product removed from campaign',
            'data' => $mapping,
        ]);
    }
}
```

**Deliverables:**
- ✅ Full CRUD controller (9 endpoints)
- ✅ Comprehensive validation rules
- ✅ Product targeting management with effective dating
- ✅ Status transition validation
- ✅ Proper error handling

---

### **Phase 6: Reporting APIs** (Day 8-10)
**Goal:** Build analytics endpoints for campaign performance

#### 6.1 Create `AdCampaignReportController`
**Location:** `app/Http/Controllers/AdCampaignReportController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\OrderItemCampaignCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdCampaignReportController extends Controller
{
    /**
     * Campaign Leaderboard
     * GET /api/ad-campaigns/reports/leaderboard
     */
    public function leaderboard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'mode' => 'required|in:FULL,SPLIT',
            'sort' => 'nullable|in:revenue,profit,units,orders',
            'platform' => 'nullable|in:facebook,instagram,google,tiktok,youtube,other',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $mode = $request->mode;
        $sortBy = $request->sort ?? 'revenue';
        
        $query = OrderItemCampaignCredit::active()
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', $mode)
            ->select([
                'campaign_id',
                DB::raw('SUM(credited_qty) as total_units'),
                DB::raw('SUM(credited_revenue) as total_revenue'),
                DB::raw('SUM(credited_profit) as total_profit'),
                DB::raw('COUNT(DISTINCT order_id) as order_count'),
            ])
            ->groupBy('campaign_id');
        
        // Sort
        $sortColumn = match($sortBy) {
            'revenue' => 'total_revenue',
            'profit' => 'total_profit',
            'units' => 'total_units',
            'orders' => 'order_count',
        };
        
        $query->orderBy($sortColumn, 'desc');
        
        $results = $query->get();
        
        // Enrich with campaign details
        $campaigns = $results->map(function($result) use ($mode) {
            $campaign = AdCampaign::with('createdBy')->find($result->campaign_id);
            
            return [
                'campaign_id' => $result->campaign_id,
                'campaign_name' => $campaign->name,
                'platform' => $campaign->platform,
                'status' => $campaign->status,
                'units' => (float) $result->total_units,
                'revenue' => (float) $result->total_revenue,
                'profit' => (float) $result->total_profit,
                'order_count' => (int) $result->order_count,
                'avg_order_value' => $result->order_count > 0 
                    ? round($result->total_revenue / $result->order_count, 2) 
                    : 0,
            ];
        });
        
        // Filter by platform if requested
        if ($request->has('platform')) {
            $campaigns = $campaigns->where('platform', $request->platform)->values();
        }
        
        return response()->json([
            'success' => true,
            'mode' => $mode,
            'date_range' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
            'data' => $campaigns,
        ]);
    }
    
    /**
     * Campaign Summary
     * GET /api/ad-campaigns/{id}/reports/summary
     */
    public function summary(Request $request, $id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'mode' => 'required|in:FULL,SPLIT',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $stats = OrderItemCampaignCredit::active()
            ->byCampaign($id)
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', $request->mode)
            ->select([
                DB::raw('SUM(credited_qty) as total_units'),
                DB::raw('SUM(credited_revenue) as total_revenue'),
                DB::raw('SUM(credited_profit) as total_profit'),
                DB::raw('COUNT(DISTINCT order_id) as order_count'),
                DB::raw('COUNT(DISTINCT order_item_id) as item_count'),
            ])
            ->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'metrics' => [
                    'units' => (float) ($stats->total_units ?? 0),
                    'revenue' => (float) ($stats->total_revenue ?? 0),
                    'profit' => (float) ($stats->total_profit ?? 0),
                    'order_count' => (int) ($stats->order_count ?? 0),
                    'item_count' => (int) ($stats->item_count ?? 0),
                    'avg_order_value' => $stats->order_count > 0 
                        ? round($stats->total_revenue / $stats->order_count, 2) 
                        : 0,
                ],
                'mode' => $request->mode,
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
            ],
        ]);
    }
    
    /**
     * Product Breakdown
     * GET /api/ad-campaigns/{id}/reports/products
     */
    public function productBreakdown(Request $request, $id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'mode' => 'required|in:FULL,SPLIT',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $results = OrderItemCampaignCredit::active()
            ->byCampaign($id)
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', $request->mode)
            ->join('order_items', 'order_item_campaign_credits.order_item_id', '=', 'order_items.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                DB::raw('SUM(order_item_campaign_credits.credited_qty) as total_units'),
                DB::raw('SUM(order_item_campaign_credits.credited_revenue) as total_revenue'),
                DB::raw('SUM(order_item_campaign_credits.credited_profit) as total_profit'),
                DB::raw('COUNT(DISTINCT order_item_campaign_credits.order_id) as order_count'),
            ])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_revenue', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
    
    /**
     * Orders List (Credited Orders)
     * GET /api/ad-campaigns/{id}/reports/orders
     */
    public function ordersList(Request $request, $id)
    {
        $campaign = AdCampaign::find($id);
        
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'mode' => 'required|in:FULL,SPLIT',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $query = OrderItemCampaignCredit::active()
            ->byCampaign($id)
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', $request->mode)
            ->with(['order.customer', 'order.store', 'orderItem.product'])
            ->orderBy('sale_time', 'desc');
        
        $credits = $query->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $credits,
        ]);
    }
    
    /**
     * Attribution Health Metrics
     * GET /api/ad-campaigns/reports/health
     */
    public function attributionHealth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Total order items in date range
        $totalOrderItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$request->from, $request->to])
            ->whereIn('orders.status', ['confirmed', 'processing', 'delivered', 'completed'])
            ->count();
        
        // Attributed items (SPLIT mode only to avoid double counting)
        $attributedItems = OrderItemCampaignCredit::active()
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', 'SPLIT')
            ->distinct('order_item_id')
            ->count('order_item_id');
        
        // Attribution distribution (how many campaigns per item)
        $distribution = OrderItemCampaignCredit::active()
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', 'SPLIT')
            ->select('matched_campaigns_count', DB::raw('COUNT(*) as item_count'))
            ->groupBy('matched_campaigns_count')
            ->orderBy('matched_campaigns_count')
            ->get();
        
        // Average campaigns per item
        $avgCampaignsPerItem = OrderItemCampaignCredit::active()
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', 'SPLIT')
            ->avg('matched_campaigns_count');
        
        // Revenue comparison
        $totalRevenue = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$request->from, $request->to])
            ->whereIn('orders.status', ['confirmed', 'processing', 'delivered', 'completed'])
            ->sum(DB::raw('order_items.unit_price * order_items.quantity'));
        
        $attributedRevenue = OrderItemCampaignCredit::active()
            ->inDateRange($request->from, $request->to)
            ->where('credit_mode', 'SPLIT')
            ->sum('credited_revenue');
        
        $unattributedRevenue = $totalRevenue - $attributedRevenue;
        
        return response()->json([
            'success' => true,
            'data' => [
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
                'items' => [
                    'total' => $totalOrderItems,
                    'attributed' => $attributedItems,
                    'unattributed' => $totalOrderItems - $attributedItems,
                    'attribution_rate' => $totalOrderItems > 0 
                        ? round(($attributedItems / $totalOrderItems) * 100, 2) 
                        : 0,
                ],
                'revenue' => [
                    'total' => (float) $totalRevenue,
                    'attributed' => (float) $attributedRevenue,
                    'unattributed' => (float) $unattributedRevenue,
                    'attribution_rate' => $totalRevenue > 0 
                        ? round(($attributedRevenue / $totalRevenue) * 100, 2) 
                        : 0,
                ],
                'overlap' => [
                    'avg_campaigns_per_item' => round($avgCampaignsPerItem, 2),
                    'distribution' => $distribution,
                ],
            ],
        ]);
    }
}
```

**Deliverables:**
- ✅ 5 comprehensive reporting endpoints
- ✅ Leaderboard with sorting and filtering
- ✅ Campaign-specific metrics
- ✅ Product performance breakdown
- ✅ Attribution health dashboard
- ✅ Proper aggregation queries with performance

---

### **Phase 7: Admin Utilities** (Day 10-11)
**Goal:** Build tools for debugging and maintenance

#### 7.1 Create `AdCampaignAdminController`
**Location:** `app/Http/Controllers/Admin/AdCampaignAdminController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AdCampaign;
use App\Models\Product;
use App\Services\AdAttributionService;
use App\Jobs\BackfillAdAttributionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdCampaignAdminController extends Controller
{
    protected $attributionService;
    
    public function __construct(AdAttributionService $attributionService)
    {
        $this->attributionService = $attributionService;
    }
    
    /**
     * Recompute attribution for single order
     * POST /api/admin/ad-campaigns/recompute-order/{orderId}
     */
    public function recomputeOrder($orderId)
    {
        $order = Order::with(['items.product'])->find($orderId);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        try {
            $this->attributionService->computeCreditsForOrder($order, 'BOTH');
            
            return response()->json([
                'success' => true,
                'message' => "Attribution recomputed for order {$order->order_number}",
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recomputation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Trigger backfill job
     * POST /api/admin/ad-campaigns/backfill
     */
    public function backfill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'statuses' => 'nullable|array',
            'statuses.*' => 'in:confirmed,processing,delivered,completed',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $statuses = $request->statuses ?? ['confirmed', 'processing', 'delivered', 'completed'];
        
        // Dispatch background job
        BackfillAdAttributionJob::dispatch(
            $request->from,
            $request->to,
            $statuses
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Backfill job queued successfully',
            'data' => [
                'from' => $request->from,
                'to' => $request->to,
                'statuses' => $statuses,
            ],
        ]);
    }
    
    /**
     * Debug attribution for hypothetical sale
     * GET /api/admin/ad-campaigns/debug-attribution
     */
    public function debugAttribution(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'sale_time' => 'required|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $productId = $request->product_id;
        $saleTime = new \DateTime($request->sale_time);
        
        // Find matching campaigns
        $matchedCampaigns = AdCampaign::activeAt($saleTime)
            ->whereHas('targetedProducts', function($q) use ($productId, $saleTime) {
                $q->where('product_id', $productId)
                  ->effectiveAt($saleTime);
            })
            ->with(['targetedProducts' => function($q) use ($productId) {
                $q->where('product_id', $productId);
            }])
            ->get();
        
        $product = Product::find($productId);
        
        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ],
                'sale_time' => $saleTime->format('Y-m-d H:i:s'),
                'matched_campaigns_count' => $matchedCampaigns->count(),
                'matched_campaigns' => $matchedCampaigns->map(function($campaign) {
                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'platform' => $campaign->platform,
                        'status' => $campaign->status,
                        'starts_at' => $campaign->starts_at->format('Y-m-d H:i:s'),
                        'ends_at' => $campaign->ends_at?->format('Y-m-d H:i:s'),
                        'product_mapping' => $campaign->targetedProducts->first(),
                    ];
                }),
                'credit_calculation_example' => [
                    'if_item_revenue' => 100,
                    'full_credit_per_campaign' => 100,
                    'split_credit_per_campaign' => $matchedCampaigns->count() > 0 
                        ? round(100 / $matchedCampaigns->count(), 2) 
                        : 0,
                ],
            ],
        ]);
    }
}
```

**Deliverables:**
- ✅ Recompute single order endpoint
- ✅ Backfill job trigger endpoint
- ✅ Debug/simulation endpoint for testing
- ✅ Admin-only access control

---

### **Phase 8: Routes Registration** (Day 11)
**Goal:** Register all routes properly

#### 8.1 Add Routes to `routes/api.php`

```php
use App\Http\Controllers\AdCampaignController;
use App\Http\Controllers\AdCampaignReportController;
use App\Http\Controllers\Admin\AdCampaignAdminController;

// Ad Campaign Management Routes (Protected)
Route::middleware('auth:api')->group(function () {
    
    // Campaign CRUD
    Route::prefix('ad-campaigns')->group(function () {
        Route::get('/', [AdCampaignController::class, 'index']);
        Route::post('/', [AdCampaignController::class, 'store']);
        Route::get('/{id}', [AdCampaignController::class, 'show']);
        Route::put('/{id}', [AdCampaignController::class, 'update']);
        Route::delete('/{id}', [AdCampaignController::class, 'destroy']);
        Route::patch('/{id}/status', [AdCampaignController::class, 'updateStatus']);
        
        // Product targeting
        Route::post('/{id}/products', [AdCampaignController::class, 'addProducts']);
        Route::get('/{id}/products', [AdCampaignController::class, 'listProducts']);
        Route::delete('/{id}/products/{mappingId}', [AdCampaignController::class, 'removeProduct']);
        
        // Reporting
        Route::prefix('reports')->group(function () {
            Route::get('/leaderboard', [AdCampaignReportController::class, 'leaderboard']);
            Route::get('/health', [AdCampaignReportController::class, 'attributionHealth']);
        });
        
        Route::get('/{id}/reports/summary', [AdCampaignReportController::class, 'summary']);
        Route::get('/{id}/reports/products', [AdCampaignReportController::class, 'productBreakdown']);
        Route::get('/{id}/reports/orders', [AdCampaignReportController::class, 'ordersList']);
    });
    
    // Admin utilities (add role check middleware)
    Route::middleware('role:admin')->prefix('admin/ad-campaigns')->group(function () {
        Route::post('/recompute-order/{orderId}', [AdCampaignAdminController::class, 'recomputeOrder']);
        Route::post('/backfill', [AdCampaignAdminController::class, 'backfill']);
        Route::get('/debug-attribution', [AdCampaignAdminController::class, 'debugAttribution']);
    });
});
```

**Deliverables:**
- ✅ All 15+ routes registered
- ✅ Proper middleware (auth, role checks)
- ✅ Clean route organization

---

### **Phase 9: Testing & Validation** (Day 11-12)
**Goal:** Comprehensive test coverage

#### 9.1 Unit Tests

**Test File:** `tests/Unit/AdCampaignTest.php`
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AdCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdCampaignTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_campaign_is_active_at_given_time()
    {
        $campaign = AdCampaign::factory()->create([
            'status' => 'RUNNING',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(5),
        ]);
        
        $this->assertTrue($campaign->isActiveAt(now()));
        $this->assertFalse($campaign->isActiveAt(now()->subDays(10)));
        $this->assertFalse($campaign->isActiveAt(now()->addDays(10)));
    }
    
    public function test_status_transitions()
    {
        $campaign = AdCampaign::factory()->create(['status' => 'DRAFT']);
        
        $this->assertTrue($campaign->canTransitionTo('RUNNING'));
        $this->assertFalse($campaign->canTransitionTo('PAUSED'));
        $this->assertFalse($campaign->canTransitionTo('ENDED'));
    }
}
```

**Test File:** `tests/Unit/AdAttributionServiceTest.php`
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AdAttributionService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AdCampaign;
use App\Models\AdCampaignProduct;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdAttributionServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected $attributionService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->attributionService = app(AdAttributionService::class);
    }
    
    public function test_single_campaign_match()
    {
        // Create product
        $product = Product::factory()->create();
        
        // Create campaign targeting product
        $campaign = AdCampaign::factory()->create([
            'status' => 'RUNNING',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(10),
        ]);
        
        AdCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'product_id' => $product->id,
            'effective_from' => now()->subDays(10),
        ]);
        
        // Create order with product
        $order = Order::factory()->create(['order_date' => now()]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);
        
        // Compute attribution
        $this->attributionService->computeCreditsForOrder($order, 'SPLIT');
        
        // Assert credits created
        $this->assertDatabaseHas('order_item_campaign_credits', [
            'order_item_id' => $orderItem->id,
            'campaign_id' => $campaign->id,
            'credit_mode' => 'SPLIT',
            'credited_qty' => 2,
            'credited_revenue' => 200,
        ]);
    }
    
    public function test_multiple_campaigns_split_credit()
    {
        $product = Product::factory()->create();
        
        // Create 3 campaigns targeting same product
        for ($i = 0; $i < 3; $i++) {
            $campaign = AdCampaign::factory()->create([
                'status' => 'RUNNING',
                'starts_at' => now()->subDays(10),
            ]);
            
            AdCampaignProduct::create([
                'campaign_id' => $campaign->id,
                'product_id' => $product->id,
                'effective_from' => now()->subDays(10),
            ]);
        }
        
        // Create order
        $order = Order::factory()->create(['order_date' => now()]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 300,
        ]);
        
        $this->attributionService->computeCreditsForOrder($order, 'SPLIT');
        
        // Each campaign should get 1/3 of credit
        $credits = \App\Models\OrderItemCampaignCredit::where('order_item_id', $orderItem->id)
            ->where('credit_mode', 'SPLIT')
            ->get();
        
        $this->assertCount(3, $credits);
        
        foreach ($credits as $credit) {
            $this->assertEquals(1, $credit->credited_qty); // 3 / 3
            $this->assertEquals(300, $credit->credited_revenue); // 900 / 3
        }
    }
    
    public function test_no_attribution_when_campaign_not_active()
    {
        $product = Product::factory()->create();
        
        // Create PAUSED campaign
        $campaign = AdCampaign::factory()->create([
            'status' => 'PAUSED',
            'starts_at' => now()->subDays(10),
        ]);
        
        AdCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'product_id' => $product->id,
            'effective_from' => now()->subDays(10),
        ]);
        
        // Create order
        $order = Order::factory()->create(['order_date' => now()]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        
        $this->attributionService->computeCreditsForOrder($order, 'SPLIT');
        
        // No credits should be created
        $this->assertDatabaseCount('order_item_campaign_credits', 0);
    }
}
```

#### 9.2 Feature Tests

**Test File:** `tests/Feature/AdCampaignApiTest.php`
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\AdCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdCampaignApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Employee::factory()->create();
    }
    
    public function test_can_create_campaign()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/ad-campaigns', [
                'name' => 'Winter Sale 2026',
                'platform' => 'facebook',
                'starts_at' => now()->toDateTimeString(),
            ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseHas('ad_campaigns', [
            'name' => 'Winter Sale 2026',
            'status' => 'DRAFT',
        ]);
    }
    
    public function test_can_update_campaign_status()
    {
        $campaign = AdCampaign::factory()->create([
            'status' => 'DRAFT',
        ]);
        
        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/ad-campaigns/{$campaign->id}/status", [
                'status' => 'RUNNING',
            ]);
        
        $response->assertOk();
        
        $this->assertDatabaseHas('ad_campaigns', [
            'id' => $campaign->id,
            'status' => 'RUNNING',
        ]);
    }
}
```

#### 9.3 Manual Testing Checklist

**Core Flows:**
- [ ] Create campaign in DRAFT status
- [ ] Add 3 products to campaign
- [ ] Set campaign to RUNNING
- [ ] Create order with one of the targeted products
- [ ] Verify credits are created automatically
- [ ] Check leaderboard shows campaign with correct metrics
- [ ] Remove product from campaign (set effective_to)
- [ ] Create another order with that product
- [ ] Verify NO new credits for removed product
- [ ] Cancel order, verify credits are reversed
- [ ] Set campaign to ENDED
- [ ] Create new order, verify NO credits

**Overlapping Campaigns:**
- [ ] Create 3 campaigns targeting Product A
- [ ] Create order with Product A
- [ ] Verify 3 SPLIT credits created (revenue divided by 3)
- [ ] Verify 3 FULL credits created (full revenue each)
- [ ] Check totals in SPLIT mode match actual revenue
- [ ] Check totals in FULL mode are 3x actual revenue

**Backfill:**
- [ ] Create campaign targeting historical data
- [ ] Run backfill job for last 7 days
- [ ] Verify credits created for matching orders
- [ ] Verify job completes without errors

**Deliverables:**
- ✅ 15+ unit tests
- ✅ 10+ feature tests  
- ✅ Manual test scenarios documented
- ✅ Test coverage >80%

---

### **Phase 10: Documentation** (Day 12-13)
**Goal:** Complete API docs and system guide

#### 10.1 API Documentation
**Create:** `docs/features/AD_PERFORMANCE_SYSTEM.md`

**Sections:**
1. **Overview & Terminology**
   - What is ad attribution?
   - Credit modes (FULL vs SPLIT)
   - Campaign lifecycle
   - Product targeting with effective dating

2. **Campaign Management**
   - Create/edit campaigns
   - Status transitions
   - Budget tracking (optional)

3. **Product Targeting**
   - Add/remove products
   - Effective dating explained
   - Historical accuracy guarantees

4. **Attribution Algorithm**
   - When credits are computed
   - Matching logic
   - Credit calculation formulas
   - Reversal handling

5. **Reporting**
   - Leaderboard usage
   - Interpreting metrics
   - FULL vs SPLIT comparison
   - Attribution health dashboard

6. **API Reference**
   - All endpoints with examples
   - Request/response schemas
   - Error codes

7. **Best Practices**
   - When to use FULL vs SPLIT
   - How to handle overlapping campaigns
   - Setting effective dates properly

8. **FAQ & Troubleshooting**
   - Why no credits for my order?
   - How to fix mis-attribution?
   - Performance considerations

#### 10.2 Technical Architecture Docs
**Create:** `docs/technical/AD_ATTRIBUTION_ARCHITECTURE.md`

**Sections:**
1. **System Architecture**
   - Components diagram
   - Data flow
   - Job queue architecture

2. **Database Schema**
   - Table structures
   - Relationships
   - Index strategy

3. **Attribution Algorithm**
   - Pseudocode
   - Edge cases
   - Performance characteristics

4. **Job Processing**
   - Queue configuration
   - Retry strategy
   - Error handling

5. **Performance Considerations**
   - Query optimization
   - Index usage
   - Scaling strategy

6. **Monitoring & Alerts**
   - Key metrics to track
   - Alert thresholds
   - Debugging tools

**Deliverables:**
- ✅ Complete API documentation (30+ pages)
- ✅ Technical architecture guide
- ✅ Frontend integration examples
- ✅ Troubleshooting guide

---

### **Phase 11: Deployment & Monitoring** (Day 13-14)
**Goal:** Production rollout with monitoring

#### 11.1 Pre-Deployment Checklist

**Database:**
- [ ] Run migrations on staging environment
- [ ] Verify all indexes created
- [ ] Test migration rollback procedure
- [ ] Check foreign key constraints

**Code:**
- [ ] All tests passing
- [ ] Code review completed
- [ ] Feature flag configured: `ad_attribution_enabled`
- [ ] Environment variables set

**Infrastructure:**
- [ ] Queue workers configured for `default` queue
- [ ] Increase worker count if needed
- [ ] Set up failed_jobs monitoring
- [ ] Configure log rotation

**Data:**
- [ ] Test backfill on sample data (last 7 days)
- [ ] Verify query performance on production-size data
- [ ] Check disk space for credits table growth

#### 11.2 Rollout Steps

**Phase 1: Deploy Code (Off-Peak Hours)**
```bash
# 1. Run migrations
php artisan migrate --force

# 2. Deploy code with feature flag OFF
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Restart services
php artisan queue:restart
sudo systemctl reload php-fpm
```

**Phase 2: Backfill Historical Data**
```bash
# Start with small date range
php artisan tinker
>>> dispatch(new \App\Jobs\BackfillAdAttributionJob('2026-01-16', '2026-01-22'));

# Monitor job queue
php artisan queue:monitor default --max=100

# Check for failures
php artisan queue:failed
```

**Phase 3: Enable Feature**
```bash
# Turn on feature flag
# In .env: AD_ATTRIBUTION_ENABLED=true
php artisan config:cache

# Test with new order
# Verify attribution works
```

**Phase 4: Monitor (24-48 hours)**
- Watch job queue length
- Check error logs
- Verify attribution accuracy
- Monitor database performance

#### 11.3 Monitoring Setup

**Key Metrics to Track:**
1. **Job Queue Health**
   - Queue length (should stay < 100)
   - Job processing time (should be < 5 seconds)
   - Failed job count (should be 0)

2. **Attribution Accuracy**
   - Daily attributed vs total revenue
   - Items with zero campaigns matched
   - Average campaigns per order item

3. **Database Performance**
   - Query time for leaderboard endpoint
   - Credits table size
   - Index usage statistics

4. **System Load**
   - Queue worker CPU usage
   - Database connections
   - Memory usage

**Alert Thresholds:**
- Queue length > 500: Warning
- Failed jobs > 10 in 1 hour: Critical
- Attribution rate < 50%: Warning
- Leaderboard query > 3 seconds: Warning

#### 11.4 Rollback Procedure

If critical issues arise:

```bash
# 1. Disable feature flag
# In .env: AD_ATTRIBUTION_ENABLED=false
php artisan config:cache

# 2. Clear job queue (if needed)
php artisan queue:flush

# 3. Rollback migrations (if needed)
php artisan migrate:rollback --step=4

# 4. Deploy previous code version
git checkout <previous-commit>
composer install
php artisan config:cache
```

**Deliverables:**
- ✅ Staged rollout plan
- ✅ Monitoring dashboards configured
- ✅ Alert rules set up
- ✅ Rollback procedure documented
- ✅ 24-hour monitoring completed

---

## 📊 Implementation Timeline Summary

| Phase | Duration | Dependencies | Risk Level | Priority | Status |
|-------|----------|--------------|------------|----------|--------|
| 1. Database Foundation | 1-2 days | None | Low | Critical | ✅ DONE (Jan 23) |
| 2. Models & Relationships | 1 day | Phase 1 | Low | Critical | ⏳ Ready |
| 3. Attribution Engine | 2-3 days | Phase 2 | **High** | Critical |
| 4. Event Automation | 1-2 days | Phase 3 | Medium | Critical |
| 5. Campaign Management APIs | 2-3 days | Phase 2 | Low | High |
| 6. Reporting APIs | 2-3 days | Phase 3 | Medium | High |
| 7. Admin Utilities | 1-2 days | Phase 3 | Low | Medium |
| 8. Routes Registration | 0.5 days | Phase 5-7 | Low | Critical |
| 9. Testing | 1-2 days | All | Medium | High |
| 10. Documentation | 1-2 days | All | Low | Medium |
| 11. Deployment | 1-2 days | All | **High** | Critical |

**Total Estimated Time:** 13-21 days (2-3 weeks for single developer)

**Parallel Work Opportunities:**
- Phases 5 & 6 can be developed in parallel (2 developers)
- Phase 7 can start while Phase 9 is ongoing
- Phase 10 can start after Phase 6

---

## ⚠️ Critical Decisions Needed Before Starting

### Decision 1: Attribution Trigger Point
**Question:** When should we compute attribution credits?

**Options:**
- **A) On `confirmed` status** (Recommended)
  - Pros: Fast feedback, fewer cancelled orders
  - Cons: Some orders may still be cancelled
  
- **B) On `delivered` status** (Conservative)
  - Pros: Most accurate, fewer reversals needed
  - Cons: Delayed reporting, less timely feedback

- **C) On `paid` status**
  - Pros: Financial accuracy
  - Cons: May not apply if payment comes later

**Recommendation:** Start with `confirmed`, add reversal handling for cancellations.

---

### Decision 2: Credit Modes to Store
**Question:** Should we store FULL, SPLIT, or both?

**Options:**
- **A) BOTH modes** (Recommended)
  - Pros: Maximum flexibility, UI can toggle
  - Cons: 2x storage, 2x write operations
  
- **B) SPLIT only** (Efficient)
  - Pros: Accurate totals, less storage
  - Cons: Can't do FULL mode analysis later

**Recommendation:** Store BOTH. Storage is cheap, flexibility is valuable.

---

### Decision 3: Job Processing Strategy
**Question:** Sync or async attribution computation?

**Options:**
- **A) Always async** (Recommended)
  - Pros: Order API stays fast
  - Cons: Slight delay in reporting
  
- **B) Sync for small orders, async for large**
  - Pros: Balance of speed and immediacy
  - Cons: More complex logic

**Recommendation:** Always async with background jobs.

---

### Decision 4: Backfill Scope
**Question:** How much historical data to backfill on launch?

**Options:**
- **A) Last 30 days** (Recommended)
  - Provides meaningful initial reports
  - Manageable processing time
  
- **B) Last 90 days**
  - Better trend analysis
  - Longer processing time
  
- **C) All time**
  - Complete historical view
  - May take hours/days

**Recommendation:** Start with 30 days, expand if needed.

---

### Decision 5: Access Control
**Question:** Who can create/manage campaigns?

**Options:**
- **A) Marketing team only** (Recommended)
  - Create role: `marketing_manager`
  - Clear separation of concerns
  
- **B) Any authenticated user**
  - More flexible
  - Risk of misuse

**Recommendation:** Restrict to marketing team with proper role checks.

---

## 🚨 High-Risk Areas & Mitigation

### Risk 1: Attribution Computation Performance
**Issue:** Computing credits could slow down order processing.

**Mitigation:**
- ✅ Use background jobs (async processing)
- ✅ Optimize campaign matching queries with proper indexes
- ✅ Batch process orders during backfill
- ✅ Monitor job queue length and processing time

---

### Risk 2: Idempotency Failures
**Issue:** Job retries could create duplicate credits.

**Mitigation:**
- ✅ Unique constraint on credits table
- ✅ Delete-then-insert pattern in transaction
- ✅ Comprehensive logging for debugging
- ✅ Failed job monitoring and alerts

---

### Risk 3: Historical Accuracy
**Issue:** Editing campaigns could rewrite past attribution.

**Mitigation:**
- ✅ Effective dating on product mappings
- ✅ Credits are snapshots (never change after creation)
- ✅ Clear UI warnings about historical effects
- ✅ Audit logging on all campaign changes

---

### Risk 4: Report Performance
**Issue:** Aggregating millions of credits could timeout.

**Mitigation:**
- ✅ Proper indexes on credits table
- ✅ Database-level aggregation (not in-memory)
- ✅ Pagination on order lists
- ✅ Optional caching layer for frequently-accessed reports

---

### Risk 5: Data Integrity
**Issue:** Orphaned credits if orders/campaigns deleted.

**Mitigation:**
- ✅ Foreign key constraints with `cascadeOnDelete`
- ✅ Soft deletes on campaigns
- ✅ Prevent deletion of campaigns with credits
- ✅ Regular data integrity checks

---

## 🎯 Success Criteria

**MVP is complete when:**
- ✅ All 4 database tables created and indexed
- ✅ 3 core models with relationships working
- ✅ Attribution service computes credits correctly
- ✅ Order status changes trigger attribution automatically
- ✅ 9 campaign management APIs functional
- ✅ 5 reporting APIs returning accurate data
- ✅ Backfill job processes historical orders
- ✅ Tests achieve >80% code coverage
- ✅ API documentation complete
- ✅ Deployed to production with monitoring

**Launch Checklist:**
- [ ] Marketing team trained on campaign creation
- [ ] Dashboard shows accurate attribution metrics
- [ ] Leaderboard loads in < 2 seconds
- [ ] Job queue processing smoothly (< 100 pending)
- [ ] Zero failed jobs in last 24 hours
- [ ] Attribution rate > 70% (70% of revenue attributed to campaigns)
- [ ] Alerts configured and tested
- [ ] Rollback procedure validated

---

## 📝 Notes for Implementation

### Code Organization
```
app/
├── Models/
│   ├── AdCampaign.php
│   ├── AdCampaignProduct.php
│   └── OrderItemCampaignCredit.php
├── Services/
│   └── AdAttributionService.php
├── Jobs/
│   ├── ComputeAdAttributionJob.php
│   └── BackfillAdAttributionJob.php
├── Observers/
│   └── OrderObserver.php (update existing)
└── Http/Controllers/
    ├── AdCampaignController.php
    ├── AdCampaignReportController.php
    └── Admin/
        └── AdCampaignAdminController.php

database/migrations/
├── 2026_01_23_000001_create_ad_campaigns_table.php
├── 2026_01_23_000002_create_ad_campaign_products_table.php
├── 2026_01_23_000003_create_order_item_campaign_credits_table.php
└── 2026_01_23_000004_create_order_item_attribution_summary_table.php (optional)

tests/
├── Unit/
│   ├── AdCampaignTest.php
│   ├── AdCampaignProductTest.php
│   └── AdAttributionServiceTest.php
└── Feature/
    ├── AdCampaignApiTest.php
    ├── AdReportingApiTest.php
    └── AdAttributionFlowTest.php

docs/
├── features/
│   └── AD_PERFORMANCE_SYSTEM.md
└── technical/
    └── AD_ATTRIBUTION_ARCHITECTURE.md
```

### Environment Variables
```env
# Ad Attribution Configuration
AD_ATTRIBUTION_ENABLED=true
AD_ATTRIBUTION_COUNTABLE_STATUSES=confirmed,processing,delivered,completed
AD_ATTRIBUTION_CREDIT_MODE=BOTH  # FULL, SPLIT, or BOTH
```

### Queue Configuration
```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
    ],
],
```

---

## 🚀 Next Steps

1. **Get Decisions Confirmed**
   - Attribution trigger point
   - Credit modes to store
   - Backfill scope
   - Access control roles

2. **Set Up Development Environment**
   - Create feature branch: `feature/ad-performance-system`
   - Set up local database
   - Configure queue worker

3. **Start Phase 1**
   - Create migration files
   - Review schema with team
   - Run migrations on dev

4. **Proceed Sequentially**
   - Complete each phase fully before moving to next
   - Test thoroughly at each step
   - Commit frequently with clear messages

**Ready to begin? Let's start with Phase 1: Database Foundation.**

---

**Document Version:** 1.0  
**Last Updated:** January 23, 2026  
**Author:** Backend Development Team  
**Status:** Planning Phase
