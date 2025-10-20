<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDispatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_dispatch_id',
        'product_batch_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_value',
        'status',
        'received_quantity',
        'damaged_quantity',
        'missing_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'missing_quantity' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $batch = $item->batch;
            $item->unit_cost = $batch->cost_price;
            $item->unit_price = $batch->sell_price;
            $item->total_cost = $item->quantity * $batch->cost_price;
            $item->total_value = $item->quantity * $batch->sell_price;
        });

        static::updating(function ($item) {
            if ($item->isDirty(['quantity'])) {
                $batch = $item->batch;
                $item->total_cost = $item->quantity * $batch->cost_price;
                $item->total_value = $item->quantity * $batch->sell_price;
            }
        });
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(ProductDispatch::class, 'product_dispatch_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function product()
    {
        return $this->batch->product();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDispatched($query)
    {
        return $query->where('status', 'dispatched');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeDamaged($query)
    {
        return $query->where('status', 'damaged');
    }

    public function scopeMissing($query)
    {
        return $query->where('status', 'missing');
    }

    public function isFullyReceived(): bool
    {
        return $this->status === 'received' && $this->received_quantity === $this->quantity;
    }

    public function hasDiscrepancy(): bool
    {
        return $this->received_quantity !== $this->quantity;
    }

    public function getDiscrepancyAmount(): int
    {
        return $this->quantity - ($this->received_quantity ?? 0);
    }

    public function getDamagedPercentage(): float
    {
        if (!$this->received_quantity || $this->received_quantity == 0) {
            return 0;
        }

        return round(($this->damaged_quantity / $this->received_quantity) * 100, 2);
    }

    public function getMissingPercentage(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }

        return round(($this->missing_quantity / $this->quantity) * 100, 2);
    }

    public function markAsReceived($receivedQuantity, $damagedQuantity = 0, $missingQuantity = 0)
    {
        $this->update([
            'status' => 'received',
            'received_quantity' => $receivedQuantity,
            'damaged_quantity' => $damagedQuantity,
            'missing_quantity' => $missingQuantity,
        ]);

        return $this;
    }

    public function markAsDamaged($damagedQuantity)
    {
        $this->update([
            'status' => 'damaged',
            'damaged_quantity' => $damagedQuantity,
        ]);

        return $this;
    }

    public function markAsMissing($missingQuantity)
    {
        $this->update([
            'status' => 'missing',
            'missing_quantity' => $missingQuantity,
        ]);

        return $this;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'gray',
            'dispatched' => 'blue',
            'received' => 'green',
            'damaged' => 'orange',
            'missing' => 'red',
            default => 'gray',
        };
    }

    public function getFormattedTotalCostAttribute()
    {
        return $this->total_cost ? number_format((float) $this->total_cost, 2) : '0.00';
    }

    public function getFormattedTotalValueAttribute()
    {
        return $this->total_value ? number_format((float) $this->total_value, 2) : '0.00';
    }
}