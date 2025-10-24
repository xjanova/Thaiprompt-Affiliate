<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'action_url',
        'target_audience',
        'system_update_id',
        'is_active',
        'is_dismissible',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'action_url' => 'array',
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
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
        if (!$this->is_active) {
            return false;
        }

        if ($this->published_at && $this->published_at > now()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Check if published
     */
    public function isPublished(): bool
    {
        return $this->published_at && $this->published_at <= now();
    }

    /**
     * Publish the notification
     */
    public function publish(): bool
    {
        $this->is_active = true;
        $this->published_at = now();
        return $this->save();
    }

    /**
     * Deactivate the notification
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Get severity badge color
     */
    public function getSeverityBadgeColor(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIcon(): string
    {
        return match($this->type) {
            'update' => 'sync-alt',
            'maintenance' => 'tools',
            'alert' => 'exclamation-triangle',
            'info' => 'info-circle',
            default => 'bell',
        };
    }

    /**
     * Scope for active notifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope by target audience
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('target_audience', 'all')
                ->orWhere('target_audience', $audience);
        });
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
