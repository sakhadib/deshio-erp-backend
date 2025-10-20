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

    protected $fillable = [
        'category_id',
        'vendor_id',
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
}