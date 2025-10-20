<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_batch_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'product_options',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'product_options' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->total_amount)) {
                $item->total_amount = ($item->quantity * $item->unit_price) - $item->discount_amount + $item->tax_amount;
            }
        });

        static::updating(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price', 'discount_amount', 'tax_amount'])) {
                $item->total_amount = ($item->quantity * $item->unit_price) - $item->discount_amount + $item->tax_amount;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function getFormattedOptionsAttribute()
    {
        if (!$this->product_options) {
            return '';
        }

        return collect($this->product_options)->map(function ($value, $key) {
            return ucfirst($key) . ': ' . $value;
        })->join(', ');
    }

    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    public function getFinalAmountAttribute()
    {
        return $this->subtotal - $this->discount_amount + $this->tax_amount;
    }

    public function updateQuantity($newQuantity)
    {
        $this->quantity = $newQuantity;
        $subtotal = bcmul((string)$newQuantity, (string)$this->unit_price, 2);
        $afterDiscount = bcsub($subtotal, (string)$this->discount_amount, 2);
        $this->total_amount = (float) bcadd($afterDiscount, (string)$this->tax_amount, 2);
        $this->save();

        $this->order->calculateTotals();

        return $this;
    }

    public function applyDiscount($discountAmount)
    {
        $this->discount_amount = $discountAmount;
        $subtotal = bcmul((string)$this->quantity, (string)$this->unit_price, 2);
        $afterDiscount = bcsub($subtotal, (string)$discountAmount, 2);
        $this->total_amount = bcadd($afterDiscount, (string)$this->tax_amount, 2);
        $this->save();

        $this->order->calculateTotals();

        return $this;
    }

    public function applyTax($taxAmount)
    {
        $this->tax_amount = $taxAmount;
        $subtotal = bcmul((string)$this->quantity, (string)$this->unit_price, 2);
        $afterDiscount = bcsub($subtotal, (string)$this->discount_amount, 2);
        $this->total_amount = (float) bcadd($afterDiscount, (string)$taxAmount, 2);
        $this->save();

        $this->order->calculateTotals();

        return $this;
    }

    public function isAvailable(): bool
    {
        if (!$this->batch) {
            return false;
        }

        return $this->batch->isAvailable() && $this->batch->quantity >= $this->quantity;
    }

    public function getAvailabilityStatusAttribute()
    {
        if (!$this->batch) {
            return 'batch_not_found';
        }

        if (!$this->batch->isAvailable()) {
            return 'out_of_stock';
        }

        if ($this->batch->quantity < $this->quantity) {
            return 'insufficient_stock';
        }

        return 'available';
    }

    public function reserveStock()
    {
        if (!$this->batch || !$this->isAvailable()) {
            throw new \Exception('Stock not available for reservation');
        }

        // Here you could implement stock reservation logic
        // For now, we'll just check availability
        return true;
    }

    public function releaseStock()
    {
        // Release reserved stock
        return true;
    }
}