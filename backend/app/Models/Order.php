<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'store_id',
        'order_type',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'notes',
        'shipping_address',
        'billing_address',
        'order_date',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'created_by',
        'processed_by',
        'shipped_by',
        'tracking_number',
        'carrier_name',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
            $order->order_date = $order->order_date ?? now();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function activeShipment()
    {
        return $this->shipments()->whereNotIn('status', ['delivered', 'cancelled', 'returned'])->first();
    }

    public function deliveredShipments()
    {
        return $this->shipments()->delivered();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'processed_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'shipped_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('order_date', now()->month)
                    ->whereYear('order_date', now()->year);
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    // Workflow methods
    public function confirm()
    {
        if (!$this->isPending()) {
            throw new \Exception('Order cannot be confirmed in its current status.');
        }

        $this->status = 'confirmed';
        $this->confirmed_at = now();
        $this->save();

        return $this;
    }

    public function startProcessing(Employee $employee)
    {
        if (!$this->isConfirmed()) {
            throw new \Exception('Order must be confirmed before processing.');
        }

        $this->status = 'processing';
        $this->processed_by = $employee->id;
        $this->save();

        return $this;
    }

    public function markReadyForPickup()
    {
        if (!$this->isProcessing()) {
            throw new \Exception('Order must be processing before marking ready for pickup.');
        }

        $this->status = 'ready_for_pickup';
        $this->save();

        return $this;
    }

    public function ship($trackingNumber = null, $carrierName = null, Employee $shippedBy = null)
    {
        if (!in_array($this->status, ['processing', 'ready_for_pickup'])) {
            throw new \Exception('Order cannot be shipped in its current status.');
        }

        $this->status = 'shipped';
        $this->shipped_at = now();
        $this->tracking_number = $trackingNumber;
        $this->carrier_name = $carrierName;

        if ($shippedBy) {
            $this->shipped_by = $shippedBy->id;
        }

        $this->save();

        return $this;
    }

    public function deliver()
    {
        if (!$this->isShipped()) {
            throw new \Exception('Order must be shipped before delivery.');
        }

        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();

        // Update customer purchase history
        $this->customer->recordPurchase($this->total_amount, $this->id);

        return $this;
    }

    public function cancel()
    {
        if (in_array($this->status, ['delivered', 'cancelled'])) {
            throw new \Exception('Order cannot be cancelled in its current status.');
        }

        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->save();

        return $this;
    }

    public function markAsPaid($paymentMethod = null)
    {
        $this->payment_status = 'paid';
        if ($paymentMethod) {
            $this->payment_method = $paymentMethod;
        }
        $this->save();

        return $this;
    }

    // Calculation methods
    public function calculateTotals()
    {
        $subtotal = $this->items->sum('total_amount');
        $taxAmount = $this->items->sum('tax_amount');
        $discountAmount = $this->items->sum('discount_amount');

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->discount_amount = $discountAmount;

        // Calculate total with proper decimal precision
        $this->total_amount = round($calculation, 2);

        $this->save();

        return $this;
    }

    public function addItem(Product $product, $quantity, $unitPrice = null, $options = [])
    {
        $unitPrice = $unitPrice ?? $product->getCurrentPrice($this->store_id);
        $totalAmount = $quantity * $unitPrice;

        $item = $this->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'product_options' => $options,
        ]);

        $this->calculateTotals();

        return $item;
    }

    public function removeItem(OrderItem $item)
    {
        $item->delete();
        $this->calculateTotals();

        return $this;
    }

    // Helper methods
    public function getShippingAddressFormattedAttribute()
    {
        if (!$this->shipping_address) {
            return $this->customer->full_address;
        }

        $address = $this->shipping_address;
        return implode(', ', array_filter([
            $address['address'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ]));
    }

    public function getBillingAddressFormattedAttribute()
    {
        if (!$this->billing_address) {
            return $this->customer->full_address;
        }

        $address = $this->billing_address;
        return implode(', ', array_filter([
            $address['address'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ]));
    }

    public function getOrderTypeLabelAttribute()
    {
        return match($this->order_type) {
            'counter' => 'Counter Sale',
            'social_commerce' => 'Social Commerce',
            'ecommerce' => 'E-commerce',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'gray',
            'confirmed' => 'blue',
            'processing' => 'yellow',
            'ready_for_pickup' => 'orange',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            'refunded' => 'red',
            default => 'gray',
        };
    }

    public function getPaymentStatusColorAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'gray',
            'paid' => 'green',
            'failed' => 'red',
            'refunded' => 'orange',
            default => 'gray',
        };
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['delivered', 'cancelled', 'refunded']);
    }

    public function canBeShipped(): bool
    {
        return in_array($this->status, ['processing', 'ready_for_pickup']);
    }

    // Static methods
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public static function getOrderStats($storeId = null)
    {
        $query = static::query();

        if ($storeId) {
            $query->byStore($storeId);
        }

        return [
            'total_orders' => $query->count(),
            'pending_orders' => (clone $query)->pending()->count(),
            'processing_orders' => (clone $query)->processing()->count(),
            'shipped_orders' => (clone $query)->shipped()->count(),
            'delivered_orders' => (clone $query)->delivered()->count(),
            'cancelled_orders' => (clone $query)->cancelled()->count(),
            'total_revenue' => $query->paid()->sum('total_amount'),
            'today_orders' => (clone $query)->today()->count(),
            'today_revenue' => (clone $query)->today()->paid()->sum('total_amount'),
        ];
    }

    public static function createCounterOrder(Customer $customer, Store $store, Employee $createdBy, array $items = [])
    {
        $order = static::create([
            'customer_id' => $customer->id,
            'store_id' => $store->id,
            'order_type' => 'counter',
            'created_by' => $createdBy->id,
        ]);

        foreach ($items as $itemData) {
            $product = Product::find($itemData['product_id']);
            $order->addItem($product, $itemData['quantity'], $itemData['unit_price'] ?? null, $itemData['options'] ?? []);
        }

        return $order;
    }

    public static function createSocialCommerceOrder(Customer $customer, Store $store, Employee $createdBy, array $items = [])
    {
        $order = static::create([
            'customer_id' => $customer->id,
            'store_id' => $store->id,
            'order_type' => 'social_commerce',
            'created_by' => $createdBy->id,
        ]);

        foreach ($items as $itemData) {
            $product = Product::find($itemData['product_id']);
            $order->addItem($product, $itemData['quantity'], $itemData['unit_price'] ?? null, $itemData['options'] ?? []);
        }

        return $order;
    }

    public function createShipment(array $shipmentData = [])
    {
        if ($this->isCancelled()) {
            throw new \Exception('Cannot create shipment for cancelled order');
        }

        if ($this->activeShipment()) {
            throw new \Exception('Order already has an active shipment');
        }

        return Shipment::createFromOrder($this, $shipmentData);
    }

    public function canCreateShipment(): bool
    {
        return !$this->isCancelled() && !$this->activeShipment();
    }

    public function getShipmentStatus()
    {
        $shipment = $this->activeShipment();
        return $shipment ? $shipment->status : null;
    }

    public function getTrackingNumber()
    {
        $shipment = $this->activeShipment();
        return $shipment ? ($shipment->pathao_tracking_number ?? $shipment->shipment_number) : null;
    }
}