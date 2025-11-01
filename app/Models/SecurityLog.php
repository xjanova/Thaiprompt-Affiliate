<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

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
}
