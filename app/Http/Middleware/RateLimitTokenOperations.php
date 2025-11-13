<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitTokenOperations
{
    /**
     * Handle an incoming request.
     *
     * Rate limits:
     * - Token creation: 5 per hour
     * - Token transfer: 20 per hour
     * - Token buy/sell: 50 per hour
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $operation = 'default'): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Define rate limits per operation
        $limits = [
            'create' => ['max' => 5, 'window' => 3600], // 5 per hour
            'transfer' => ['max' => 20, 'window' => 3600], // 20 per hour
            'trade' => ['max' => 50, 'window' => 3600], // 50 per hour (buy/sell)
            'deploy' => ['max' => 3, 'window' => 3600], // 3 per hour
            'default' => ['max' => 30, 'window' => 3600], // 30 per hour
        ];

        $limit = $limits[$operation] ?? $limits['default'];
        $key = "rate_limit:token:{$operation}:{$user->id}";

        // Get current count
        $count = Cache::get($key, 0);

        if ($count >= $limit['max']) {
            $remainingTime = Cache::get("{$key}:expires") - time();

            Log::warning("Rate limit exceeded for user {$user->id} on operation {$operation}");

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "คุณทำรายการมากเกินไป กรุณารอ " . ceil($remainingTime / 60) . " นาที",
                'retry_after' => $remainingTime,
                'limit' => $limit['max'],
                'window' => $limit['window'],
            ], 429);
        }

        // Increment count
        Cache::put($key, $count + 1, $limit['window']);

        if ($count === 0) {
            Cache::put("{$key}:expires", time() + $limit['window'], $limit['window']);
        }

        // Add rate limit info to response headers
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limit['max']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit['max'] - $count - 1));
        $response->headers->set('X-RateLimit-Reset', time() + $limit['window']);

        return $response;
    }
}
