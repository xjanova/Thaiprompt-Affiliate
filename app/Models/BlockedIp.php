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
        'ip_type',
        'ip_range_start',
        'ip_range_end',
        'ip_cidr',
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
        $blockedRules = static::where('type', 'blacklist')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($blockedRules as $rule) {
            if (static::ipMatchesRule($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is whitelisted.
     */
    public static function isWhitelisted(string $ip): bool
    {
        $whitelistRules = static::where('type', 'whitelist')
            ->where('is_active', true)
            ->get();

        foreach ($whitelistRules as $rule) {
            if (static::ipMatchesRule($ip, $rule)) {
                return true;
            }
        }

        return false;
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

    /**
     * Check if an IP matches a blocking/whitelisting rule.
     */
    protected static function ipMatchesRule(string $ip, self $rule): bool
    {
        switch ($rule->ip_type) {
            case 'single':
                return $ip === $rule->ip_address;

            case 'range':
                return static::ipInRange($ip, $rule->ip_range_start, $rule->ip_range_end);

            case 'cidr':
                return static::ipInCidr($ip, $rule->ip_cidr);

            default:
                return false;
        }
    }

    /**
     * Check if an IP is within a range.
     */
    protected static function ipInRange(string $ip, string $startIp, string $endIp): bool
    {
        // Convert IP to long for comparison
        $ipLong = ip2long($ip);
        $startLong = ip2long($startIp);
        $endLong = ip2long($endIp);

        // If any conversion fails, it might be IPv6
        if ($ipLong === false || $startLong === false || $endLong === false) {
            return static::ipv6InRange($ip, $startIp, $endIp);
        }

        return $ipLong >= $startLong && $ipLong <= $endLong;
    }

    /**
     * Check if an IPv6 is within a range.
     */
    protected static function ipv6InRange(string $ip, string $startIp, string $endIp): bool
    {
        $ip = inet_pton($ip);
        $startIp = inet_pton($startIp);
        $endIp = inet_pton($endIp);

        if ($ip === false || $startIp === false || $endIp === false) {
            return false;
        }

        return ($ip >= $startIp && $ip <= $endIp);
    }

    /**
     * Check if an IP is within a CIDR range.
     */
    protected static function ipInCidr(string $ip, string $cidr): bool
    {
        // Check if it's IPv6 CIDR
        if (strpos($cidr, ':') !== false) {
            return static::ipv6InCidr($ip, $cidr);
        }

        // IPv4 CIDR
        list($subnet, $mask) = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int)$mask);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }

    /**
     * Check if an IPv6 is within a CIDR range.
     */
    protected static function ipv6InCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);

        $ip = inet_pton($ip);
        $subnet = inet_pton($subnet);

        if ($ip === false || $subnet === false) {
            return false;
        }

        $mask = (int)$mask;

        // Create mask
        $maskBin = str_repeat('1', $mask) . str_repeat('0', 128 - $mask);
        $maskPacked = '';

        for ($i = 0; $i < 128; $i += 8) {
            $maskPacked .= chr(bindec(substr($maskBin, $i, 8)));
        }

        return ($ip & $maskPacked) === ($subnet & $maskPacked);
    }

    /**
     * Get display label for IP type.
     */
    public function getIpTypeLabel(): string
    {
        return match($this->ip_type) {
            'single' => 'Single IP',
            'range' => 'IP Range',
            'cidr' => 'CIDR',
            default => 'Unknown',
        };
    }

    /**
     * Get display value for IP.
     */
    public function getIpDisplay(): string
    {
        return match($this->ip_type) {
            'single' => $this->ip_address,
            'range' => "{$this->ip_range_start} - {$this->ip_range_end}",
            'cidr' => $this->ip_cidr,
            default => $this->ip_address,
        };
    }
}
