<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'batch_number',
        'quantity',
        'cost_price',
        'sell_price',
        'availability',
        'manufactured_date',
        'expiry_date',
        'store_id',
        'barcode_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'availability' => 'boolean',
        'manufactured_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_number)) {
                $batch->batch_number = static::generateBatchNumber();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function barcode(): BelongsTo
    {
        return $this->belongsTo(ProductBarcode::class, 'barcode_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability', true)->where('quantity', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', now());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByBatchNumber($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function isExpired(): bool
    {
        return !is_null($this->expiry_date) && $this->expiry_date <= now();
    }

    public function isAvailable(): bool
    {
        return $this->availability && $this->quantity > 0 && !$this->isExpired();
    }

    public function isLowStock($threshold = 10): bool
    {
        return $this->quantity <= $threshold && $this->quantity > 0;
    }

    public function calculateProfitMargin()
    {
        if ($this->cost_price == 0) {
            return 0;
        }

        return round((($this->sell_price - $this->cost_price) / $this->cost_price) * 100, 2);
    }

    public function getTotalValue()
    {
        return $this->quantity * $this->cost_price;
    }

    public function getSellValue()
    {
        return $this->quantity * $this->sell_price;
    }

    public static function generateBatchNumber(): string
    {
        do {
            $batchNumber = 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (static::where('batch_number', $batchNumber)->exists());

        return $batchNumber;
    }

    public static function findByBarcode($barcode)
    {
        return static::whereHas('barcode', function ($query) use ($barcode) {
            $query->where('barcode', $barcode);
        })->first();
    }

    public function updateQuantity($newQuantity)
    {
        $this->update(['quantity' => $newQuantity]);

        // Auto-update availability based on quantity
        if ($newQuantity <= 0) {
            $this->update(['availability' => false]);
        } elseif ($newQuantity > 0 && !$this->availability) {
            $this->update(['availability' => true]);
        }

        return $this;
    }

    public function addStock($amount)
    {
        return $this->updateQuantity($this->quantity + $amount);
    }

    public function removeStock($amount)
    {
        return $this->updateQuantity(max(0, $this->quantity - $amount));
    }

    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if (!$this->availability) {
            return 'unavailable';
        }

        if ($this->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        return 'available';
    }

    public function getLocationHistory()
    {
        return ProductMovement::byBatch($this->id)
                             ->with(['fromStore', 'toStore', 'performedBy'])
                             ->orderBy('movement_date', 'desc')
                             ->get();
    }

    public function getCurrentLocation()
    {
        return $this->store;
    }

    public function getMovementCount()
    {
        return ProductMovement::byBatch($this->id)->count();
    }

    public function getLastMovement()
    {
        return ProductMovement::byBatch($this->id)
                             ->orderBy('movement_date', 'desc')
                             ->first();
    }
}