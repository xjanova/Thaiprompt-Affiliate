<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_type',
        'status',
        'message',
        'metrics',
        'response_time_ms',
        'checked_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'response_time_ms' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    /**
     * Check if healthy
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if warning
     */
    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    /**
     * Check if critical
     */
    public function isCritical(): bool
    {
        return $this->status === 'critical';
    }

    /**
     * Check if down
     */
    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'healthy' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            'down' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get status icon
     */
    public function getStatusIcon(): string
    {
        return match($this->status) {
            'healthy' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'critical' => 'exclamation-circle',
            'down' => 'times-circle',
            default => 'question-circle',
        };
    }

    /**
     * Scope by check type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('check_type', $type);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for latest checks
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('checked_at');
    }

    /**
     * Get latest check by type
     */
    public static function getLatestByType(string $type): ?self
    {
        return self::where('check_type', $type)
            ->latest('checked_at')
            ->first();
    }
}
