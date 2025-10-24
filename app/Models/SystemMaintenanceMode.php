<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemMaintenanceMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'system_update_id',
        'is_active',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'allowed_ips',
        'allowed_users',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'allowed_ips' => 'array',
        'allowed_users' => 'array',
    ];

    /**
     * Get the system update
     */
    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if user is allowed
     */
    public function isUserAllowed(int $userId): bool
    {
        if (!$this->allowed_users) {
            return false;
        }

        return in_array($userId, $this->allowed_users);
    }

    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (!$this->allowed_ips) {
            return false;
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Activate maintenance mode
     */
    public function activate(): bool
    {
        $this->is_active = true;
        $this->actual_start = now();
        return $this->save();
    }

    /**
     * Deactivate maintenance mode
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        $this->actual_end = now();
        return $this->save();
    }

    /**
     * Check if scheduled
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_start && $this->scheduled_start > now();
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_end && $this->scheduled_end < now() && $this->is_active;
    }

    /**
     * Scope for active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for scheduled
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_start')
            ->where('scheduled_start', '>', now());
    }

    /**
     * Get the active maintenance mode
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
