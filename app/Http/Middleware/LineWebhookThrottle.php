<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * LINE Webhook Rate Limiting Middleware
 *
 * Protects webhook endpoint from abuse and DOS attacks
 * by limiting requests per LINE user and per IP address.
 */
class LineWebhookThrottle
{
    /**
     * Maximum requests per LINE user per minute
     */
    private const MAX_REQUESTS_PER_USER = 20;

    /**
     * Maximum requests per IP per minute
     */
    private const MAX_REQUESTS_PER_IP = 60;

    /**
     * Time window in seconds
     */
    private const TIME_WINDOW = 60;

    /**
     * Maximum burst requests (allows temporary spike)
     */
    private const MAX_BURST = 5;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get LINE user ID from webhook payload
        $body = json_decode($request->getContent(), true);
        $lineUserId = $this->extractLineUserId($body);

        // Get client IP
        $ip = $request->ip();

        // Check rate limits
        if ($lineUserId && $this->isUserRateLimited($lineUserId)) {
            Log::warning('LINE webhook rate limit exceeded for user', [
                'line_user_id' => $lineUserId,
                'ip' => $ip,
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'Please slow down',
            ], 429);
        }

        if ($this->isIpRateLimited($ip)) {
            Log::warning('LINE webhook rate limit exceeded for IP', [
                'ip' => $ip,
                'line_user_id' => $lineUserId ?? 'unknown',
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'IP rate limit exceeded',
            ], 429);
        }

        // Check for suspicious patterns (burst detection)
        if ($lineUserId && $this->isSuspiciousBurst($lineUserId)) {
            Log::alert('Suspicious burst activity detected from LINE user', [
                'line_user_id' => $lineUserId,
                'ip' => $ip,
            ]);

            // Still allow but log for monitoring
        }

        // Increment counters
        if ($lineUserId) {
            $this->incrementUserCounter($lineUserId);
        }
        $this->incrementIpCounter($ip);

        return $next($request);
    }

    /**
     * Extract LINE user ID from webhook events
     */
    private function extractLineUserId(array $body): ?string
    {
        $events = $body['events'] ?? [];

        if (empty($events)) {
            return null;
        }

        // Get user ID from first event
        $firstEvent = $events[0];
        return $firstEvent['source']['userId'] ?? null;
    }

    /**
     * Check if user has exceeded rate limit
     */
    private function isUserRateLimited(string $lineUserId): bool
    {
        $key = "line_webhook_user:{$lineUserId}";
        $count = Cache::get($key, 0);

        return $count >= self::MAX_REQUESTS_PER_USER;
    }

    /**
     * Check if IP has exceeded rate limit
     */
    private function isIpRateLimited(string $ip): bool
    {
        $key = "line_webhook_ip:{$ip}";
        $count = Cache::get($key, 0);

        return $count >= self::MAX_REQUESTS_PER_IP;
    }

    /**
     * Check for suspicious burst activity
     */
    private function isSuspiciousBurst(string $lineUserId): bool
    {
        $key = "line_webhook_burst:{$lineUserId}";
        $burstCount = Cache::get($key, 0);

        return $burstCount >= self::MAX_BURST;
    }

    /**
     * Increment user request counter
     */
    private function incrementUserCounter(string $lineUserId): void
    {
        $key = "line_webhook_user:{$lineUserId}";
        $ttl = self::TIME_WINDOW;

        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addSeconds($ttl));

        // Track burst (10-second window)
        $burstKey = "line_webhook_burst:{$lineUserId}";
        $burstCurrent = Cache::get($burstKey, 0);
        Cache::put($burstKey, $burstCurrent + 1, now()->addSeconds(10));
    }

    /**
     * Increment IP request counter
     */
    private function incrementIpCounter(string $ip): void
    {
        $key = "line_webhook_ip:{$ip}";
        $ttl = self::TIME_WINDOW;

        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addSeconds($ttl));
    }
}
