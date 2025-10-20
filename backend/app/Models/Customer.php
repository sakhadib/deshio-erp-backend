<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'customer_type',
        'name',
        'phone',
        'email',
        'password',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'preferences',
        'social_profiles',
        'customer_code',
        'total_purchases',
        'total_orders',
        'last_purchase_at',
        'first_purchase_at',
        'status',
        'notes',
        'created_by',
        'assigned_employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'datetime',
        'email_verified_at' => 'datetime',
        'total_purchases' => 'decimal:2',
        'total_orders' => 'integer',
        'last_purchase_at' => 'datetime',
        'first_purchase_at' => 'datetime',
        'preferences' => 'array',
        'social_profiles' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateCustomerCode();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function activeOrders()
    {
        return $this->orders()->where('status', '!=', 'cancelled');
    }

    public function completedOrders()
    {
        return $this->orders()->where('status', 'completed');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    public function scopeCounterCustomers($query)
    {
        return $query->where('customer_type', 'counter');
    }

    public function scopeSocialCommerceCustomers($query)
    {
        return $query->where('customer_type', 'social_commerce');
    }

    public function scopeEcommerceCustomers($query)
    {
        return $query->where('customer_type', 'ecommerce');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    public function scopeAssignedToEmployee($query, $employeeId)
    {
        return $query->where('assigned_employee_id', $employeeId);
    }

    public function scopeHighValue($query, $threshold = 1000)
    {
        return $query->where('total_purchases', '>=', $threshold);
    }

    public function scopeRecentPurchasers($query, $days = 30)
    {
        return $query->where('last_purchase_at', '>=', now()->subDays($days));
    }

    public function scopeTopCustomers($query, $limit = 10)
    {
        return $query->orderBy('total_purchases', 'desc')->limit($limit);
    }

    // Type checks
    public function isCounterCustomer(): bool
    {
        return $this->customer_type === 'counter';
    }

    public function isSocialCommerceCustomer(): bool
    {
        return $this->customer_type === 'social_commerce';
    }

    public function isEcommerceCustomer(): bool
    {
        return $this->customer_type === 'ecommerce';
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    // Business logic methods
    public function recordPurchase($amount, $orderId = null)
    {
        $this->increment('total_orders');
        $this->increment('total_purchases', $amount);

        if (!$this->first_purchase_at) {
            $this->first_purchase_at = now();
        }

        $this->last_purchase_at = now();
        $this->save();

        return $this;
    }

    public function getFullAddressArray()
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'street' => $this->address,
            'area' => $this->city,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country ?? 'Bangladesh',
        ];
    }

    public function hasCompleteAddress(): bool
    {
        return !empty($this->address) && !empty($this->city) && !empty($this->country);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->diffInYears(now()) : null;
    }

    public function getFormattedPhoneAttribute()
    {
        // Basic phone formatting - can be enhanced based on country
        $phone = $this->phone;
        if (strlen($phone) === 11 && str_starts_with($phone, '01')) {
            // Bangladesh mobile format
            return '+880 ' . substr($phone, 0, 5) . '-' . substr($phone, 5);
        }
        return $phone;
    }

    public function getCommunicationPreferencesAttribute()
    {
        return $this->preferences['communication'] ?? [];
    }

    public function getShoppingPreferencesAttribute()
    {
        return $this->preferences['shopping'] ?? [];
    }

    public function setCommunicationPreference($channel, $enabled = true)
    {
        $preferences = $this->preferences ?? [];
        $preferences['communication'][$channel] = $enabled;
        $this->preferences = $preferences;
        $this->save();
    }

    public function setShoppingPreference($category, $value)
    {
        $preferences = $this->preferences ?? [];
        $preferences['shopping'][$category] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    public function addSocialProfile($platform, $identifier)
    {
        $profiles = $this->social_profiles ?? [];
        $profiles[$platform] = $identifier;
        $this->social_profiles = $profiles;
        $this->save();
    }

    public function getSocialProfile($platform)
    {
        return $this->social_profiles[$platform] ?? null;
    }

    public function getWhatsAppNumberAttribute()
    {
        return $this->getSocialProfile('whatsapp') ?? $this->phone;
    }

    public function assignToEmployee(Employee $employee)
    {
        $this->assigned_employee_id = $employee->id;
        $this->save();
        return $this;
    }

    public function unassignEmployee()
    {
        $this->assigned_employee_id = null;
        $this->save();
        return $this;
    }

    public function activate()
    {
        $this->status = 'active';
        $this->save();
        return $this;
    }

    public function deactivate()
    {
        $this->status = 'inactive';
        $this->save();
        return $this;
    }

    public function block()
    {
        $this->status = 'blocked';
        $this->save();
        return $this;
    }

    public function getLifetimeValueAttribute()
    {
        return $this->total_purchases;
    }

    public function getAverageOrderValueAttribute()
    {
        return $this->total_orders > 0 ? $this->total_purchases / $this->total_orders : 0;
    }

    public function getDaysSinceLastPurchaseAttribute()
    {
        return $this->last_purchase_at ? $this->last_purchase_at->diffInDays(now()) : null;
    }

    public function isLoyalCustomer($threshold = 500): bool
    {
        return $this->total_purchases >= $threshold;
    }

    public function isRecentCustomer($days = 90): bool
    {
        return $this->first_purchase_at && $this->first_purchase_at->diffInDays(now()) <= $days;
    }

    public function isAtRiskCustomer($days = 90): bool
    {
        return $this->last_purchase_at && $this->last_purchase_at->diffInDays(now()) > $days;
    }

    // Static factory methods for different customer types
    public static function createCounterCustomer(array $data)
    {
        return static::create(array_merge($data, [
            'customer_type' => 'counter',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]));
    }

    public static function createSocialCommerceCustomer(array $data)
    {
        return static::create(array_merge($data, [
            'customer_type' => 'social_commerce',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]));
    }

    public static function createEcommerceCustomer(array $data)
    {
        $customer = static::create(array_merge($data, [
            'customer_type' => 'ecommerce',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]));

        // Set password if provided
        if (isset($data['password'])) {
            $customer->setPassword($data['password']);
        }

        return $customer;
    }

    public static function findByPhone($phone)
    {
        return static::where('phone', $phone)->first();
    }

    public static function findByEmail($email)
    {
        return static::where('email', $email)->first();
    }

    public static function findByCode($code)
    {
        return static::where('customer_code', $code)->first();
    }

    public static function generateCustomerCode(): string
    {
        do {
            $code = 'CUST-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (static::where('customer_code', $code)->exists());

        return $code;
    }

    public static function getCustomerStats()
    {
        return [
            'total_customers' => static::count(),
            'active_customers' => static::active()->count(),
            'counter_customers' => static::counterCustomers()->count(),
            'social_commerce_customers' => static::socialCommerceCustomers()->count(),
            'ecommerce_customers' => static::ecommerceCustomers()->count(),
            'high_value_customers' => static::highValue()->count(),
            'recent_customers' => static::recentPurchasers()->count(),
            'total_revenue' => static::sum('total_purchases'),
            'average_customer_value' => static::avg('total_purchases'),
        ];
    }

    public static function getTopCustomersByRevenue($limit = 10)
    {
        return static::with('assignedEmployee')
                    ->orderBy('total_purchases', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getCustomersAtRisk($days = 90)
    {
        return static::where('last_purchase_at', '<', now()->subDays($days))
                    ->orWhereNull('last_purchase_at')
                    ->orderBy('last_purchase_at')
                    ->get();
    }

    public function getCustomerTypeLabelAttribute()
    {
        return match($this->customer_type) {
            'counter' => 'Counter Sale',
            'social_commerce' => 'Social Commerce',
            'ecommerce' => 'E-commerce',
            default => 'Unknown',
        };
    }

    public function setPassword($password)
    {
        $this->password = bcrypt($password);
        $this->save();
        return $this;
    }

    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    public function markEmailAsVerified()
    {
        $this->email_verified_at = now();
        $this->save();
        return $this;
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function sendEmailVerificationNotification()
    {
        // Implementation for sending email verification
        // This would typically use Laravel's built-in notification system
    }

    public function canLogin(): bool
    {
        return $this->isEcommerceCustomer() && $this->isActive() && $this->hasVerifiedEmail();
    }
}