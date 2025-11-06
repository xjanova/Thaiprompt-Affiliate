<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency Middleware
 *
 * Prevents duplicate payment processing by tracking idempotency keys.
 * Clients should send an 'Idempotency-Key' header with their requests.
 */
class IdempotencyMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST requests (payment creation)
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        // Get idempotency key from header
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            // If no key provided, generate one based on request data
            // This is a fallback, but clients should provide their own key
            $idempotencyKey = $this->generateIdempotencyKey($request);
        }

        $cacheKey = "idempotency:{$idempotencyKey}";

        // Check if this request was already processed
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);

            // Return cached response with 200 status (idempotent response)
            return response()->json($cachedResponse['data'], $cachedResponse['status'])
                ->header('X-Idempotent-Replay', 'true');
        }

        // Lock to prevent concurrent requests with same key
        $lock = Cache::lock("lock:{$cacheKey}", 10); // 10 second lock

        if (!$lock->get()) {
            return response()->json([
                'message' => 'Duplicate request in progress. Please wait.',
            ], 409); // Conflict
        }

        try {
            // Process the request
            $response = $next($request);

            // Cache successful responses for 24 hours
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Cache::put($cacheKey, [
                    'data' => $response->getData(true),
                    'status' => $response->getStatusCode(),
                ], now()->addDay());
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Generate idempotency key from request data
     */
    private function generateIdempotencyKey(Request $request): string
    {
        $user = $request->user();
        $userId = $user ? $user->id : $request->ip();

        // Create hash from user ID, route, and relevant payment data
        $data = [
            'user_id' => $userId,
            'route' => $request->path(),
            'amount' => $request->input('amount'),
            'payment_method' => $request->input('payment_method'),
            'timestamp' => now()->format('Y-m-d H:i'), // Group by minute
        ];

        return 'auto-' . hash('sha256', json_encode($data));
    }
}
