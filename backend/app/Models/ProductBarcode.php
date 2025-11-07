<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'barcode',
        'type',
        'is_primary',
        'is_active',
        'generated_at',
        'is_defective',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_defective' => 'boolean',
        'generated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($barcode) {
            if (empty($barcode->barcode)) {
                $barcode->barcode = static::generateUniqueBarcode();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'barcode_id');
    }

    public function defectiveRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DefectiveProduct::class, 'product_barcode_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefective($query)
    {
        return $query->where('is_defective', true);
    }

    public function scopeNonDefective($query)
    {
        return $query->where('is_defective', false);
    }

    public function makePrimary()
    {
        // Remove primary status from other barcodes of this product
        static::where('product_id', $this->product_id)
              ->where('id', '!=', $this->id)
              ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);

        return $this;
    }

    public static function generateUniqueBarcode($length = 12): string
    {
        do {
            $barcode = static::generateBarcode($length);
        } while (static::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public static function generateBarcode($length = 12): string
    {
        // Generate a random numeric barcode
        $barcode = '';
        for ($i = 0; $i < $length; $i++) {
            $barcode .= mt_rand(0, 9);
        }

        return $barcode;
    }

    public static function generateEAN13(): string
    {
        // Generate a valid EAN-13 barcode
        $prefix = '123'; // Example prefix
        $random = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $partial = $prefix . $random;

        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $partial[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $partial . $checkDigit;
    }

    public static function createForProduct(Product $product, $type = 'CODE128', $makePrimary = false)
    {
        $barcode = static::create([
            'product_id' => $product->id,
            'type' => $type,
            'is_primary' => $makePrimary,
        ]);

        if ($makePrimary) {
            $barcode->makePrimary();
        }

        return $barcode;
    }

    public static function getPrimaryBarcodeForProduct($productId)
    {
        return static::byProduct($productId)->primary()->active()->first();
    }

    public static function getBarcodesForProduct($productId, $onlyActive = true)
    {
        $query = static::byProduct($productId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    public function getFormattedBarcodeAttribute()
    {
        // Format barcode based on type
        switch ($this->type) {
            case 'EAN13':
                return substr($this->barcode, 0, 1) . '-' .
                       substr($this->barcode, 1, 6) . '-' .
                       substr($this->barcode, 7, 6);
            case 'CODE128':
            default:
                return $this->barcode;
        }
    }

    public function getCurrentLocation()
    {
        return ProductMovement::getCurrentLocation($this->id);
    }

    public function getLocationHistory()
    {
        return ProductMovement::getProductLocationHistory($this->id);
    }

    public function getCurrentStore()
    {
        $currentMovement = ProductMovement::byBarcode($this->id)
                                         ->orderBy('movement_date', 'desc')
                                         ->first();

        return $currentMovement ? $currentMovement->toStore : null;
    }

    public function getCurrentBatch(): ?ProductBatch
    {
        $currentMovement = ProductMovement::byBarcode($this->id)
                                         ->with('batch')
                                         ->orderBy('movement_date', 'desc')
                                         ->first();

        return $currentMovement ? $currentMovement->batch : null;
    }

    public function isCurrentlyAtStore($storeId)
    {
        $currentStore = $this->getCurrentStore();
        return $currentStore && $currentStore->id === $storeId;
    }

    public function getMovementCount()
    {
        return ProductMovement::byBarcode($this->id)->count();
    }

    public function getLastMovementDate()
    {
        $lastMovement = ProductMovement::byBarcode($this->id)
                                      ->orderBy('movement_date', 'desc')
                                      ->first();

        return $lastMovement ? $lastMovement->movement_date : null;
    }

    public static function scanBarcode($barcode)
    {
        $barcodeRecord = static::where('barcode', $barcode)->first();

        if (!$barcodeRecord) {
            return [
                'found' => false,
                'message' => 'Barcode not found in system',
            ];
        }

        $currentLocation = $barcodeRecord->getCurrentStore();
        $currentBatch = $barcodeRecord->getCurrentBatch();
        $lastMovement = ProductMovement::byBarcode($barcodeRecord->id)
                                      ->orderBy('movement_date', 'desc')
                                      ->first();

        return [
            'found' => true,
            'barcode' => $barcodeRecord,
            'product' => $barcodeRecord->product,
            'current_location' => $currentLocation,
            'current_batch' => $currentBatch,
            'last_movement' => $lastMovement,
            'location_history' => $barcodeRecord->getLocationHistory(),
            'is_available' => $currentBatch ? $currentBatch->isAvailable() : false,
            'quantity_available' => $currentBatch ? $currentBatch->quantity : 0,
        ];
    }

    public function getCurrentShipment()
    {
        // Check if this barcode is currently in a shipment
        $currentShipment = Shipment::whereJsonContains('package_barcodes', $this->barcode)
                                  ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                                  ->first();

        return $currentShipment;
    }

    public function getShipmentHistory()
    {
        return Shipment::whereJsonContains('package_barcodes', $this->barcode)
                      ->with(['order', 'customer'])
                      ->orderBy('created_at', 'desc')
                      ->get();
    }

    public function isInShipment(): bool
    {
        return $this->getCurrentShipment() !== null;
    }

    public function getShipmentStatus()
    {
        $shipment = $this->getCurrentShipment();
        return $shipment ? $shipment->status : null;
    }

    public function getShipmentTrackingNumber()
    {
        $shipment = $this->getCurrentShipment();
        return $shipment ? ($shipment->pathao_tracking_number ?? $shipment->shipment_number) : null;
    }

    // Defective product methods
    public function markAsDefective(array $defectData): DefectiveProduct
    {
        // Mark barcode as defective
        $this->update(['is_defective' => true, 'is_active' => false]);

        // Create defective product record
        $defectiveProduct = DefectiveProduct::create([
            'product_id' => $this->product_id,
            'product_barcode_id' => $this->id,
            'product_batch_id' => $defectData['product_batch_id'] ?? null,
            'store_id' => $defectData['store_id'],
            'defect_type' => $defectData['defect_type'],
            'defect_description' => $defectData['defect_description'],
            'defect_images' => $defectData['defect_images'] ?? null,
            'severity' => $defectData['severity'] ?? 'moderate',
            'original_price' => $defectData['original_price'],
            'identified_by' => $defectData['identified_by'] ?? null,
            'internal_notes' => $defectData['internal_notes'] ?? null,
        ]);

        // Remove from regular inventory if batch is provided
        if (isset($defectData['product_batch_id'])) {
            $batch = ProductBatch::find($defectData['product_batch_id']);
            if ($batch && $batch->quantity > 0) {
                $batch->decrement('quantity', 1);

                // Log the removal
                ProductMovement::create([
                    'product_id' => $this->product_id,
                    'product_batch_id' => $batch->id,
                    'store_id' => $defectData['store_id'],
                    'movement_type' => 'defective',
                    'quantity' => -1,
                    'unit_cost' => $defectData['original_price'],
                    'total_cost' => $defectData['original_price'],
                    'reference_type' => 'defective_product',
                    'reference_id' => $defectiveProduct->id,
                    'notes' => "Marked as defective: {$defectData['defect_type']}",
                    'created_by' => $defectData['identified_by'] ?? null,
                ]);
            }
        }

        return $defectiveProduct;
    }

    public function isDefective(): bool
    {
        return $this->is_defective ?? false;
    }

    public function getDefectiveRecord(): ?DefectiveProduct
    {
        return $this->defectiveRecord;
    }

    public function canBeMarkedAsDefective(): bool
    {
        return !$this->is_defective && $this->is_active;
    }
}
