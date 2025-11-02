<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreatIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'threat_type',
        'source',
        'confidence_score',
        'details',
        'last_seen',
        'expires_at',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'expires_at' => 'datetime',
        'details' => 'array',
    ];

    /**
     * Check if an IP is in threat database
     */
    public static function isThreatIp(string $ip, int $minConfidence = 50): bool
    {
        return static::where('ip_address', $ip)
            ->where('confidence_score', '>=', $minConfidence)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get threat info for an IP
     */
    public static function getThreatInfo(string $ip): ?self
    {
        return static::where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('confidence_score', 'desc')
            ->first();
    }

    /**
     * Scope: Active threats only
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: By threat type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('threat_type', $type);
    }

    /**
     * Scope: By source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: High confidence threats
     */
    public function scopeHighConfidence($query, int $threshold = 70)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Get threat type badge color
     */
    public function getThreatTypeBadgeColor(): string
    {
        return match($this->threat_type) {
            'proxy' => 'orange',
            'vpn' => 'yellow',
            'tor' => 'purple',
            'spam' => 'blue',
            'abuse' => 'red',
            'malware' => 'red',
            'scanner' => 'pink',
            default => 'gray',
        };
    }

    /**
     * Get threat type label
     */
    public function getThreatTypeLabel(): string
    {
        return match($this->threat_type) {
            'proxy' => '🔀 Proxy',
            'vpn' => '🔒 VPN',
            'tor' => '🧅 Tor',
            'spam' => '📧 Spam',
            'abuse' => '⚠️ Abuse',
            'malware' => '🦠 Malware',
            'scanner' => '🔍 Scanner',
            default => '❓ Other',
        };
    }
}
