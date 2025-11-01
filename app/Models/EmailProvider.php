<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmailProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'type',
        'configuration',
        'is_active',
        'is_default',
        'priority',
        'daily_limit',
        'hourly_limit',
        'sent_today',
        'sent_this_hour',
        'last_sent_at',
        'last_reset_at',
        'health_check',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'daily_limit' => 'integer',
        'hourly_limit' => 'integer',
        'sent_today' => 'integer',
        'sent_this_hour' => 'integer',
        'last_sent_at' => 'datetime',
        'last_reset_at' => 'datetime',
        'health_check' => 'array',
    ];

    /**
     * Encrypt configuration before saving.
     */
    public function setConfigurationAttribute($value): void
    {
        $this->attributes['configuration'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Decrypt configuration when retrieving.
     */
    public function getConfigurationAttribute($value): array
    {
        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Scope a query to only include active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to get default provider.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if provider has reached daily limit.
     */
    public function hasReachedDailyLimit(): bool
    {
        if (!$this->daily_limit) {
            return false;
        }

        $this->resetCountersIfNeeded();

        return $this->sent_today >= $this->daily_limit;
    }

    /**
     * Check if provider has reached hourly limit.
     */
    public function hasReachedHourlyLimit(): bool
    {
        if (!$this->hourly_limit) {
            return false;
        }

        $this->resetCountersIfNeeded();

        return $this->sent_this_hour >= $this->hourly_limit;
    }

    /**
     * Check if provider can send email.
     */
    public function canSend(): bool
    {
        return $this->is_active
            && !$this->hasReachedDailyLimit()
            && !$this->hasReachedHourlyLimit();
    }

    /**
     * Increment send counters.
     */
    public function incrementSentCount(): void
    {
        $this->resetCountersIfNeeded();

        $this->increment('sent_today');
        $this->increment('sent_this_hour');
        $this->update(['last_sent_at' => now()]);
    }

    /**
     * Reset counters if needed (daily/hourly).
     */
    protected function resetCountersIfNeeded(): void
    {
        $now = now();
        $lastReset = $this->last_reset_at ?? $now->copy()->subDay();

        // Reset daily counter
        if ($lastReset->isYesterday() || $lastReset->lt($now->copy()->startOfDay())) {
            $this->sent_today = 0;
        }

        // Reset hourly counter
        if ($lastReset->lt($now->copy()->subHour())) {
            $this->sent_this_hour = 0;
        }

        $this->last_reset_at = $now;
        $this->save();
    }

    /**
     * Set as default provider.
     */
    public function setAsDefault(): void
    {
        // Remove default from all other providers
        static::where('is_default', true)->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Get the default provider.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get available provider (respecting limits and priority).
     */
    public static function getAvailable(): ?self
    {
        return static::active()
            ->byPriority()
            ->get()
            ->first(function ($provider) {
                return $provider->canSend();
            });
    }

    /**
     * Update health check status.
     */
    public function updateHealthCheck(bool $healthy, ?string $message = null): void
    {
        $this->update([
            'health_check' => [
                'healthy' => $healthy,
                'message' => $message,
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
