<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only use created_at

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'ip_address',
        'user_agent',
        'country_code',
        'city',
        'url',
        'method',
        'metadata',
        'description',
        'cf_ray',
        'cf_connecting_ip',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user associated with this log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a security event
     *
     * @param string $eventType
     * @param array $data
     * @return SecurityLog
     */
    public static function logEvent(string $eventType, array $data = []): SecurityLog
    {
        $request = request();

        return static::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'event_type' => $eventType,
            'severity' => $data['severity'] ?? 'low',
            'ip_address' => $data['ip_address'] ?? $request->ip(),
            'user_agent' => $data['user_agent'] ?? $request->userAgent(),
            'country_code' => $data['country_code'] ?? $request->header('CF-IPCountry'),
            'city' => $data['city'] ?? null,
            'url' => $data['url'] ?? $request->fullUrl(),
            'method' => $data['method'] ?? $request->method(),
            'metadata' => $data['metadata'] ?? [],
            'description' => $data['description'] ?? null,
            'cf_ray' => $data['cf_ray'] ?? $request->header('CF-Ray'),
            'cf_connecting_ip' => $data['cf_connecting_ip'] ?? $request->header('CF-Connecting-IP'),
        ]);
    }

    /**
     * Get logs by event type
     *
     * @param string $eventType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function byEventType(string $eventType)
    {
        return static::where('event_type', $eventType);
    }

    /**
     * Get logs by severity
     *
     * @param string $severity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function bySeverity(string $severity)
    {
        return static::where('severity', $severity);
    }

    /**
     * Get recent logs
     *
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function recent(int $hours = 24)
    {
        return static::where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Get suspicious activities
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function suspicious()
    {
        return static::whereIn('event_type', [
            'spam_detected',
            'rate_limit_exceeded',
            'suspicious_activity',
            'ip_blocked',
            'unauthorized_access',
        ]);
    }
}
