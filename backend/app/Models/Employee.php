<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'store_id',
        'is_in_service',
        'role_id',
        'phone',
        'address',
        'employee_code',
        'hire_date',
        'department',
        'salary',
        'manager_id',
        'is_active',
        'avatar',
        'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'is_in_service' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EmployeeSession::class);
    }

    public function activeSessions()
    {
        return $this->sessions()->active();
    }

    public function emailVerificationTokens(): HasMany
    {
        return $this->hasMany(EmailVerificationToken::class);
    }

    public function activeEmailVerificationTokens()
    {
        return $this->emailVerificationTokens()->active();
    }

    public function passwordResetTokens(): HasMany
    {
        return $this->hasMany(PasswordResetToken::class);
    }

    public function activePasswordResetTokens()
    {
        return $this->passwordResetTokens()->active();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInService($query)
    {
        return $query->where('is_in_service', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function hasPermission($permission): bool
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getIsManagerAttribute()
    {
        return $this->subordinates()->exists();
    }

    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}
