<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class PaymentRateLimiter
{
    /**
     * Handle an incoming request for payment operations
     */
    public function handle(Request $request, Closure $next, string $limitType = 'payment'): Response
    {
        $user = $request->user();
        $ip = $request->ip();

        // Create a unique key based on user ID or IP
        $key = $user ? "payment:{$limitType}:user:{$user->id}" : "payment:{$limitType}:ip:{$ip}";

        // Define limits based on type
        $limits = [
            'payment' => [
                'attempts' => 10,  // 10 payment attempts
                'decay' => 60,     // per 60 seconds
            ],
            'topup' => [
                'attempts' => 5,
                'decay' => 300,    // 5 top-ups per 5 minutes
            ],
            'withdrawal' => [
                'attempts' => 3,
                'decay' => 3600,   // 3 withdrawals per hour
            ],
            'webhook' => [
                'attempts' => 100,
                'decay' => 60,     // 100 webhook calls per minute
            ],
        ];

        $config = $limits[$limitType] ?? $limits['payment'];

        // Check rate limit
        $executed = RateLimiter::attempt(
            $key,
            $config['attempts'],
            function () {},
            $config['decay']
        );

        if (!$executed) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many payment requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Add rate limit headers to response
        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-RateLimit-Limit', $config['attempts']);
            $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $config['attempts']));
        }

        return $response;
    }
}
