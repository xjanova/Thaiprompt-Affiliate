<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\IpIntelligenceService;
use App\Services\UserAgentParser;
use App\Services\AutoBanService;

class SecurityLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only created_at

    protected $fillable = [
        'event_type',
        'ip_address',
        'user_id',
        'email',
        'user_agent',
        'severity',
        'description',
        'metadata',
        // IP Intelligence
        'country_code',
        'country_name',
        'city',
        'latitude',
        'longitude',
        // Device Intelligence
        'os',
        'os_version',
        'browser',
        'browser_version',
        'device_type',
        // Security flags
        'is_vpn',
        'is_proxy',
        'is_tor',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_vpn' => 'boolean',
        'is_proxy' => 'boolean',
        'is_tor' => 'boolean',
    ];

    /**
     * Boot method to auto-populate intelligence data
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            // Get IP intelligence
            if ($log->ip_address) {
                $ipInfo = IpIntelligenceService::getIpInfo($log->ip_address);

                $log->country_code = $ipInfo['country_code'];
                $log->country_name = $ipInfo['country_name'];
                $log->city = $ipInfo['city'];
                $log->latitude = $ipInfo['latitude'];
                $log->longitude = $ipInfo['longitude'];
                $log->is_vpn = $ipInfo['is_vpn'];
                $log->is_proxy = $ipInfo['is_proxy'];
                $log->is_tor = $ipInfo['is_tor'];
            }

            // Parse user agent
            if ($log->user_agent) {
                $uaInfo = UserAgentParser::parse($log->user_agent);

                $log->os = $uaInfo['os'];
                $log->os_version = $uaInfo['os_version'];
                $log->browser = $uaInfo['browser'];
                $log->browser_version = $uaInfo['browser_version'];
                $log->device_type = $uaInfo['device_type'];
            }
        });

        static::created(function ($log) {
            // Check for auto-ban after creating log
            if (in_array($log->event_type, ['login_failed', 'turnstile_failed', 'rate_limit_exceeded'])) {
                AutoBanService::checkAndBan($log->ip_address, $log->event_type);
            }
        });
    }

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a security event.
     */
    public static function logEvent(
        string $eventType,
        string $ipAddress,
        string $severity = 'low',
        ?int $userId = null,
        ?string $email = null,
        ?string $description = null,
        ?array $metadata = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'event_type' => $eventType,
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'email' => $email,
            'user_agent' => $userAgent,
            'severity' => $severity,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log failed login attempt.
     */
    public static function logFailedLogin(
        string $ipAddress,
        ?string $email = null,
        ?string $reason = null,
        ?string $userAgent = null
    ): self {
        return static::logEvent(
            eventType: 'login_failed',
            ipAddress: $ipAddress,
            severity: 'medium',
            email: $email,
            description: $reason ?? 'Invalid credentials',
            userAgent: $userAgent
        );
    }

    /**
     * Log successful login.
     */
    public static function logSuccessfulLogin(
        string $ipAddress,
        int $userId,
        ?string $userAgent = null
    ): self {
        return static::logEvent(
            eventType: 'login_success',
            ipAddress: $ipAddress,
            severity: 'low',
            userId: $userId,
            description: 'User logged in successfully',
            userAgent: $userAgent
        );
    }

    /**
     * Log IP block.
     */
    public static function logIpBlock(
        string $ipAddress,
        string $reason,
        ?int $blockedBy = null
    ): self {
        return static::logEvent(
            eventType: 'ip_blocked',
            ipAddress: $ipAddress,
            severity: 'high',
            userId: $blockedBy,
            description: $reason
        );
    }

    /**
     * Log Turnstile verification failure.
     */
    public static function logTurnstileFailure(
        string $ipAddress,
        string $point,
        ?string $userAgent = null
    ): self {
        return static::logEvent(
            eventType: 'turnstile_failed',
            ipAddress: $ipAddress,
            severity: 'medium',
            description: "Turnstile verification failed at {$point}",
            metadata: ['point' => $point],
            userAgent: $userAgent
        );
    }

    /**
     * Log rate limit exceeded.
     */
    public static function logRateLimitExceeded(
        string $ipAddress,
        string $endpoint,
        ?string $email = null,
        ?string $userAgent = null
    ): self {
        return static::logEvent(
            eventType: 'rate_limit_exceeded',
            ipAddress: $ipAddress,
            severity: 'high',
            email: $email,
            description: "Rate limit exceeded for {$endpoint}",
            metadata: ['endpoint' => $endpoint],
            userAgent: $userAgent
        );
    }

    /**
     * Scope: By event type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope: By severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Recent logs (last N days).
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Critical events only.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope: High severity events.
     */
    public function scopeHigh($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    /**
     * Scope: By country.
     */
    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope: By device type.
     */
    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope: By OS.
     */
    public function scopeByOs($query, string $os)
    {
        return $query->where('os', $os);
    }

    /**
     * Scope: By browser.
     */
    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser', $browser);
    }

    /**
     * Scope: VPN/Proxy users.
     */
    public function scopeVpnOrProxy($query)
    {
        return $query->where(function($q) {
            $q->where('is_vpn', true)
              ->orWhere('is_proxy', true)
              ->orWhere('is_tor', true);
        });
    }

    /**
     * Get analytics data
     */
    public static function getAnalytics(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'total_events' => static::where('created_at', '>=', $since)->count(),
            'by_severity' => static::where('created_at', '>=', $since)
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_event_type' => static::where('created_at', '>=', $since)
                ->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'event_type')
                ->toArray(),
            'by_country' => static::where('created_at', '>=', $since)
                ->whereNotNull('country_code')
                ->selectRaw('country_code, country_name, COUNT(*) as count')
                ->groupBy('country_code', 'country_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
            'by_os' => static::where('created_at', '>=', $since)
                ->whereNotNull('os')
                ->selectRaw('os, COUNT(*) as count')
                ->groupBy('os')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'os')
                ->toArray(),
            'by_browser' => static::where('created_at', '>=', $since)
                ->whereNotNull('browser')
                ->selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'browser')
                ->toArray(),
            'by_device' => static::where('created_at', '>=', $since)
                ->whereNotNull('device_type')
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->pluck('count', 'device_type')
                ->toArray(),
            'vpn_proxy_count' => static::where('created_at', '>=', $since)
                ->vpnOrProxy()
                ->count(),
        ];
    }

    /**
     * Get timeline data for charts
     */
    public static function getTimeline(string $eventType = null, int $days = 30): array
    {
        $query = static::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date');

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        return $query->pluck('count', 'date')->toArray();
    }
}

