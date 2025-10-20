<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_store_id',
        'destination_store_id',
        'dispatch_number',
        'status',
        'dispatch_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'carrier_name',
        'tracking_number',
        'total_cost',
        'total_value',
        'total_items',
        'notes',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'dispatch_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'approved_at' => 'datetime',
        'total_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'total_items' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dispatch) {
            if (empty($dispatch->dispatch_number)) {
                $dispatch->dispatch_number = static::generateDispatchNumber();
            }
            $dispatch->dispatch_date = $dispatch->dispatch_date ?? now();
        });
    }

    public function sourceStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'source_store_id');
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductDispatchItem::class, 'product_dispatch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeBySourceStore($query, $storeId)
    {
        return $query->where('source_store_id', $storeId);
    }

    public function scopeByDestinationStore($query, $storeId)
    {
        return $query->where('destination_store_id', $storeId);
    }

    public function scopeByDispatchNumber($query, $dispatchNumber)
    {
        return $query->where('dispatch_number', $dispatchNumber);
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
                    ->whereIn('status', ['pending', 'in_transit']);
    }

    public function scopeExpectedToday($query)
    {
        return $query->whereDate('expected_delivery_date', today())
                    ->whereIn('status', ['pending', 'in_transit']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isOverdue(): bool
    {
        return $this->expected_delivery_date && $this->expected_delivery_date->isPast()
               && in_array($this->status, ['pending', 'in_transit']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && is_null($this->approved_by);
    }

    public function canBeDispatched(): bool
    {
        return $this->status === 'pending' && !is_null($this->approved_by);
    }

    public function canBeDelivered(): bool
    {
        return $this->status === 'in_transit';
    }

    public function approve(Employee $employee)
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Dispatch cannot be approved in its current state.');
        }

        $this->update([
            'approved_by' => $employee->id,
            'approved_at' => now(),
        ]);

        return $this;
    }

    public function dispatch()
    {
        if (!$this->canBeDispatched()) {
            throw new \Exception('Dispatch cannot be sent in its current state.');
        }

        $this->update(['status' => 'in_transit']);

        // Update item statuses
        $this->items()->update(['status' => 'dispatched']);

        return $this;
    }

    public function deliver()
    {
        if (!$this->canBeDelivered()) {
            throw new \Exception('Dispatch cannot be delivered in its current state.');
        }

        $this->update([
            'status' => 'delivered',
            'actual_delivery_date' => now(),
        ]);

        // Process inventory movement for each item
        foreach ($this->items as $item) {
            $this->processInventoryMovement($item);
        }

        return $this;
    }

    public function cancel()
    {
        if (in_array($this->status, ['delivered', 'cancelled'])) {
            throw new \Exception('Cannot cancel a delivered or already cancelled dispatch.');
        }

        $this->update(['status' => 'cancelled']);

        return $this;
    }

    public function addItem(ProductBatch $batch, int $quantity)
    {
        if ($batch->store_id !== $this->source_store_id) {
            throw new \Exception('Batch does not belong to the source store.');
        }

        if ($batch->quantity < $quantity) {
            throw new \Exception('Insufficient quantity in batch.');
        }

        $item = $this->items()->create([
            'product_batch_id' => $batch->id,
            'quantity' => $quantity,
        ]);

        $this->updateTotals();

        return $item;
    }

    public function removeItem(ProductDispatchItem $item)
    {
        if ($item->dispatch_id !== $this->id) {
            throw new \Exception('Item does not belong to this dispatch.');
        }

        $item->delete();
        $this->updateTotals();

        return $this;
    }

    public function updateTotals()
    {
        $totals = $this->items()->selectRaw('
            COUNT(*) as total_items,
            SUM(total_cost) as total_cost,
            SUM(total_value) as total_value
        ')->first();

        $this->update([
            'total_items' => $totals->total_items ?? 0,
            'total_cost' => $totals->total_cost ?? 0,
            'total_value' => $totals->total_value ?? 0,
        ]);

        return $this;
    }

    public function getTotalWeightAttribute()
    {
        // Assuming products have weight, this would need to be implemented
        // based on your product model structure
        return $this->items->sum(function ($item) {
            // return $item->batch->product->weight * $item->quantity;
            return 0; // Placeholder
        });
    }

    public function getDeliveryStatusAttribute()
    {
        if ($this->isOverdue()) {
            return 'overdue';
        }

        if ($this->expected_delivery_date && $this->expected_delivery_date->isToday()) {
            return 'due_today';
        }

        return $this->status;
    }

    public function getFormattedTotalCostAttribute()
    {
        return $this->total_cost ? number_format((float) $this->total_cost, 2) : '0.00';
    }

    public function getFormattedTotalValueAttribute()
    {
        return $this->total_value ? number_format((float) $this->total_value, 2) : '0.00';
    }

    public function getItemsSummaryAttribute()
    {
        return $this->items->groupBy('batch.product.name')->map(function ($items, $productName) {
            return [
                'product_name' => $productName,
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('total_cost'),
                'total_value' => $items->sum('total_value'),
            ];
        });
    }

    public static function generateDispatchNumber(): string
    {
        do {
            $dispatchNumber = 'DSP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (static::where('dispatch_number', $dispatchNumber)->exists());

        return $dispatchNumber;
    }

    public static function getPendingCount()
    {
        return static::pending()->count();
    }

    public static function getInTransitCount()
    {
        return static::inTransit()->count();
    }

    public static function getOverdueCount()
    {
        return static::overdue()->count();
    }

    protected function processInventoryMovement(ProductDispatchItem $item)
    {
        $sourceBatch = $item->batch;
        $receivedQuantity = $item->received_quantity ?? $item->quantity;

        // Reduce quantity in source batch
        $sourceBatch->removeStock($item->quantity);

        // Create new batch at destination store
        $destinationBatch = ProductBatch::create([
            'product_id' => $sourceBatch->product_id,
            'batch_number' => $sourceBatch->batch_number . '-DST-' . $this->dispatch_number,
            'quantity' => $receivedQuantity,
            'cost_price' => $sourceBatch->cost_price,
            'sell_price' => $sourceBatch->sell_price,
            'availability' => true,
            'manufactured_date' => $sourceBatch->manufactured_date,
            'expiry_date' => $sourceBatch->expiry_date,
            'store_id' => $this->destination_store_id,
            'barcode_id' => $sourceBatch->barcode_id,
            'notes' => 'Received via dispatch ' . $this->dispatch_number,
            'is_active' => true,
        ]);

        // Record the movement
        ProductMovement::recordMovement([
            'product_batch_id' => $destinationBatch->id,
            'product_barcode_id' => $sourceBatch->barcode_id,
            'from_store_id' => $this->source_store_id,
            'to_store_id' => $this->destination_store_id,
            'product_dispatch_id' => $this->id,
            'movement_type' => 'dispatch',
            'quantity' => $receivedQuantity,
            'unit_cost' => $sourceBatch->cost_price,
            'unit_price' => $sourceBatch->sell_price,
            'reference_number' => $this->dispatch_number,
            'notes' => 'Product dispatch delivery',
            'performed_by' => $this->approved_by ?? $this->created_by,
        ]);

        // Update dispatch item to reference the new destination batch
        $item->update([
            'status' => 'received',
            'product_batch_id' => $destinationBatch->id,
        ]);

        return $destinationBatch;
    }
}