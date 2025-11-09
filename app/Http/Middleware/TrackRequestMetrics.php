<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackRequestMetrics
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        $this->trackMetrics($request, $response, $duration);

        return $response;
    }

    /**
     * Track request metrics
     */
    protected function trackMetrics(Request $request, Response $response, float $duration): void
    {
        try {
            $today = now()->format('Y-m-d');

            // Increment request count
            Cache::increment("metrics:{$today}:requests", 1);

            // Track response time
            $responseTimes = Cache::get("metrics:{$today}:response_times", []);
            $responseTimes[] = $duration;
            Cache::put("metrics:{$today}:response_times", $responseTimes, now()->addDays(2));

            // Track status codes
            $statusCode = $response->getStatusCode();
            Cache::increment("metrics:{$today}:status:{$statusCode}", 1);

            // Track errors (4xx and 5xx)
            if ($statusCode >= 400) {
                Cache::increment("metrics:{$today}:errors", 1);

                if ($statusCode >= 500) {
                    Cache::increment("metrics:{$today}:server_errors", 1);
                } else {
                    Cache::increment("metrics:{$today}:client_errors", 1);
                }
            }

            // Track method
            $method = $request->method();
            Cache::increment("metrics:{$today}:method:{$method}", 1);

        } catch (\Exception $e) {
            // Silently fail - don't break the application
            logger()->error('Failed to track request metrics: ' . $e->getMessage());
        }
    }

    /**
     * Get average response time for a date
     */
    public static function getAverageResponseTime(string $date): float
    {
        $responseTimes = Cache::get("metrics:{$date}:response_times", []);

        if (empty($responseTimes)) {
            return 0;
        }

        return round(array_sum($responseTimes) / count($responseTimes), 2);
    }

    /**
     * Get request count for a date
     */
    public static function getRequestCount(string $date): int
    {
        return Cache::get("metrics:{$date}:requests", 0);
    }

    /**
     * Get error count for a date
     */
    public static function getErrorCount(string $date): int
    {
        return Cache::get("metrics:{$date}:errors", 0);
    }

    /**
     * Get error rate for a date
     */
    public static function getErrorRate(string $date): float
    {
        $requests = self::getRequestCount($date);
        $errors = self::getErrorCount($date);

        if ($requests === 0) {
            return 0;
        }

        return round(($errors / $requests) * 100, 2);
    }
}
