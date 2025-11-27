<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * SKU (Stock Keeping Unit) is not unique to support product variations.
     * Multiple products can share the same SKU with different variations 
     * defined through ProductFields (e.g., color, size, storage).
     * 
     * Each physical item is uniquely tracked via ProductBarcode.
     * Stock is managed through ProductBatch.
     */

    protected $fillable = [
        'category_id',
        'vendor_id',
        'brand',
        'sku',
        'name',
        'description',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function productFields(): HasMany
    {
        return $this->hasMany(ProductField::class);
    }

    public function fields()
    {
        return $this->belongsToMany(Field::class, 'product_fields')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeBySku($query, $sku)
    {
        return $query->where('sku', $sku);
    }

    public function getFieldValue($fieldSlug)
    {
        $productField = $this->productFields()
                            ->whereHas('field', function ($query) use ($fieldSlug) {
                                $query->where('slug', $fieldSlug);
                            })
                            ->first();

        return $productField ? $productField->parsed_value : null;
    }

    public function setFieldValue($fieldSlug, $value)
    {
        $field = Field::where('slug', $fieldSlug)->first();

        if (!$field) {
            return false;
        }

        $productField = $this->productFields()
                            ->where('field_id', $field->id)
                            ->first();

        if ($productField) {
            $productField->parsed_value = $value;
            $productField->save();
        } else {
            ProductField::create([
                'product_id' => $this->id,
                'field_id' => $field->id,
                'value' => $value,
            ]);
        }

        return true;
    }

    public function getAllFieldValues()
    {
        return $this->productFields->mapWithKeys(function ($productField) {
            return [$productField->field->slug => $productField->parsed_value];
        });
    }

    public function attachField(Field $field, $value = null)
    {
        if (!$this->fields()->where('field_id', $field->id)->exists()) {
            $this->fields()->attach($field->id, ['value' => $value]);
        }
    }

    public function detachField(Field $field)
    {
        $this->fields()->detach($field->id);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function activeImages()
    {
        return $this->images()->active()->ordered();
    }

    public function primaryImage()
    {
        return $this->images()->primary()->active()->first();
    }

    public function getPrimaryImageUrlAttribute()
    {
        $primaryImage = $this->primaryImage();
        return $primaryImage ? $primaryImage->image_url : null;
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function activeBarcodes()
    {
        return $this->barcodes()->active();
    }

    public function primaryBarcode()
    {
        return $this->barcodes()->primary()->active()->first();
    }

    public function getPrimaryBarcodeAttribute()
    {
        $primaryBarcode = $this->primaryBarcode();
        return $primaryBarcode ? $primaryBarcode->barcode : null;
    }

    public function generateBarcode($type = 'CODE128', $makePrimary = false)
    {
        return ProductBarcode::createForProduct($this, $type, $makePrimary);
    }

    public function priceOverrides(): HasMany
    {
        return $this->hasMany(ProductPriceOverride::class);
    }

    public function activePriceOverrides()
    {
        return $this->priceOverrides()->active();
    }

    public function currentPriceOverride($storeId = null)
    {
        $query = $this->priceOverrides()->active();

        if ($storeId) {
            $query->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                  ->orWhereNull('store_id');
            });
        } else {
            $query->whereNull('store_id');
        }

        return $query->orderBy('created_at', 'desc')->first();
    }

    public function getCurrentPrice($storeId = null)
    {
        $override = $this->currentPriceOverride($storeId);
        return $override ? $override->price : null; // Assuming product has a base_price field
    }

    public function createPriceOverride(array $data)
    {
        return ProductPriceOverride::createOverride(array_merge($data, ['product_id' => $this->id]));
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function activeBatches()
    {
        return $this->batches()->active();
    }

    public function availableBatches()
    {
        return $this->batches()->available();
    }

    public function getCurrentBatchPrice($storeId = null)
    {
        $query = $this->availableBatches();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $batch = $query->orderBy('sell_price', 'asc')->first();

        return $batch ? $batch->sell_price : null;
    }

    public function getBatchByBarcode($barcode)
    {
        return $this->batches()
                   ->whereHas('barcode', function ($query) use ($barcode) {
                       $query->where('barcode', $barcode);
                   })
                   ->first();
    }

    public function getTotalInventory($storeId = null)
    {
        $query = $this->batches()->active();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->sum('quantity');
    }

    public function getLowestBatchPrice($storeId = null)
    {
        $query = $this->availableBatches();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->min('sell_price');
    }

    public function getHighestBatchPrice($storeId = null)
    {
        $query = $this->availableBatches();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->max('sell_price');
    }

    public function getAverageBatchPrice($storeId = null)
    {
        $query = $this->availableBatches();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->avg('sell_price');
    }
}