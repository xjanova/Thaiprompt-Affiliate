<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class FoodPassportRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * Rate limiting for Food Passport API to prevent abuse
     * and protect TPIX blockchain operations.
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        $limits = $this->getLimits($tier);

        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $limits['maxAttempts'])) {
            return $this->buildRateLimitResponse(
                $key,
                $limits['maxAttempts'],
                $limits['decayMinutes']
            );
        }

        RateLimiter::hit($key, $limits['decayMinutes'] * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $key,
            $limits['maxAttempts'],
            $limits['decayMinutes']
        );
    }

    /**
     * Get rate limits based on tier.
     */
    protected function getLimits(string $tier): array
    {
        return match($tier) {
            // Public endpoints (QR scan, view)
            'public' => [
                'maxAttempts' => 100,
                'decayMinutes' => 1,
            ],

            // IoT sensors (bulk uploads)
            'iot' => [
                'maxAttempts' => 1000,
                'decayMinutes' => 1,
            ],

            // Blockchain operations (expensive)
            'blockchain' => [
                'maxAttempts' => 10,
                'decayMinutes' => 1,
            ],

            // Carbon credit trading
            'trading' => [
                'maxAttempts' => 20,
                'decayMinutes' => 1,
            ],

            // API write operations
            'write' => [
                'maxAttempts' => 30,
                'decayMinutes' => 1,
            ],

            // Default (read operations)
            default => [
                'maxAttempts' => 60,
                'decayMinutes' => 1,
            ],
        };
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use user ID if authenticated
        if ($user = $request->user()) {
            return "food-passport-api:{$user->id}";
        }

        // Use IP address for unauthenticated requests
        return "food-passport-api-ip:{$request->ip()}";
    }

    /**
     * Build rate limit exceeded response.
     */
    protected function buildRateLimitResponse(
        string $key,
        int $maxAttempts,
        int $decayMinutes
    ): Response {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error' => [
                'type' => 'rate_limit_exceeded',
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes,
                'retry_after_seconds' => $retryAfter,
                'retry_after_human' => $this->formatRetryAfter($retryAfter),
            ],
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(
        Response $response,
        string $key,
        int $maxAttempts,
        int $decayMinutes
    ): Response {
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        if ($remaining === 0) {
            $response->headers->set('Retry-After', $retryAfter);
        }

        return $response;
    }

    /**
     * Format retry after time to human-readable string.
     */
    protected function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = ceil($seconds / 60);
        return "{$minutes} minute" . ($minutes > 1 ? 's' : '');
    }
}
