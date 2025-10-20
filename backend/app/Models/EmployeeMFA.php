<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMFA extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'secret',
        'backup_codes',
        'is_enabled',
        'verified_at',
        'last_used_at',
        'settings',
    ];

    protected $casts = [
        'backup_codes' => 'array',
        'is_enabled' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
        'settings' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled && $this->isVerified();
    }

    public function enable()
    {
        $this->update(['is_enabled' => true]);
        return $this;
    }

    public function disable()
    {
        $this->update(['is_enabled' => false]);
        return $this;
    }

    public function verify()
    {
        $this->update(['verified_at' => now()]);
        return $this;
    }

    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
        return $this;
    }

    public function hasBackupCodes(): bool
    {
        return !empty($this->backup_codes) && is_array($this->backup_codes);
    }

    public function useBackupCode($code): bool
    {
        if (!$this->hasBackupCodes()) {
            return false;
        }

        $codes = $this->backup_codes;
        $index = array_search($code, $codes);

        if ($index !== false) {
            unset($codes[$index]);
            $this->update(['backup_codes' => array_values($codes)]);
            $this->updateLastUsed();
            return true;
        }

        return false;
    }

    public function generateBackupCodes($count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }

        $this->update(['backup_codes' => $codes]);
        return $codes;
    }

    public function getIsVerifiedAttribute()
    {
        return $this->isVerified();
    }

    public function getIsEnabledAttribute()
    {
        return $this->isEnabled();
    }
}
