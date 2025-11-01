<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'type',
        'reason',
        'blocked_at',
        'expires_at',
        'blocked_by',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who blocked this IP.
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Check if the block has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // Permanent block
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if an IP is blocked.
     */
    public static function isBlocked(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('type', 'blacklist')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if an IP is whitelisted.
     */
    public static function isWhitelisted(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('type', 'whitelist')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Block an IP address.
     */
    public static function blockIp(
        string $ip,
        string $reason = null,
        int $blockedBy = null,
        $expiresAt = null,
        string $notes = null
    ): self {
        return static::create([
            'ip_address' => $ip,
            'type' => 'blacklist',
            'reason' => $reason,
            'blocked_by' => $blockedBy,
            'expires_at' => $expiresAt,
            'notes' => $notes,
            'is_active' => true,
        ]);
    }

    /**
     * Whitelist an IP address.
     */
    public static function whitelistIp(
        string $ip,
        string $reason = null,
        int $addedBy = null,
        string $notes = null
    ): self {
        return static::create([
            'ip_address' => $ip,
            'type' => 'whitelist',
            'reason' => $reason,
            'blocked_by' => $addedBy,
            'notes' => $notes,
            'is_active' => true,
        ]);
    }

    /**
     * Unblock an IP address.
     */
    public static function unblockIp(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('type', 'blacklist')
            ->update(['is_active' => false]);
    }

    /**
     * Scope: Active blocks only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: Blacklist only.
     */
    public function scopeBlacklist($query)
    {
        return $query->where('type', 'blacklist');
    }

    /**
     * Scope: Whitelist only.
     */
    public function scopeWhitelist($query)
    {
        return $query->where('type', 'whitelist');
    }
}
