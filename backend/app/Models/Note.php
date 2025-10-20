<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'created_by',
        'title',
        'content',
        'type',
        'is_private',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public function scopeVisibleTo($query, Employee $viewer)
    {
        // HR and managers can see private notes
        if ($viewer->hasPermission('view-private-notes') || $viewer->is_manager) {
            return $query;
        }

        // Others can only see public notes about themselves
        return $query->where(function ($q) use ($viewer) {
            $q->where('is_private', false)
              ->orWhere('employee_id', $viewer->id);
        });
    }

    public function isVisibleTo(Employee $viewer): bool
    {
        if (!$this->is_private) {
            return true;
        }

        return $viewer->hasPermission('view-private-notes') ||
               $viewer->is_manager ||
               $this->employee_id === $viewer->id;
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'hr' => 'blue',
            'performance' => 'green',
            'disciplinary' => 'red',
            'medical' => 'orange',
            'general' => 'gray',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'hr' => 'users',
            'performance' => 'chart-line',
            'disciplinary' => 'exclamation-triangle',
            'medical' => 'heartbeat',
            'general' => 'sticky-note',
            default => 'sticky-note',
        };
    }
}
