<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * LINE Signup Rate Limiting Middleware
 *
 * Prevents signup abuse by limiting signup attempts
 * per LINE user ID and per IP address.
 */
class LineSignupThrottle
{
    /**
     * Maximum signup attempts per LINE user per hour
     */
    private const MAX_ATTEMPTS_PER_USER = 5;

    /**
     * Maximum signup attempts per IP per hour
     */
    private const MAX_ATTEMPTS_PER_IP = 10;

    /**
     * Time window in seconds (1 hour)
     */
    private const TIME_WINDOW = 3600;

    /**
     * Lockout duration after exceeding limit (2 hours)
     */
    private const LOCKOUT_DURATION = 7200;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get LINE user ID from request or session
        $lineUserId = $this->getLineUserId($request);
        $ip = $request->ip();

        // Check if locked out
        if ($lineUserId && $this->isLockedOut($lineUserId, 'user')) {
            $remainingTime = $this->getRemainingLockoutTime($lineUserId, 'user');

            Log::warning('LINE signup lockout - user', [
                'line_user_id' => $lineUserId,
                'ip' => $ip,
                'remaining_seconds' => $remainingTime,
            ]);

            return redirect()->back()->withErrors([
                'line_signup' => "คุณพยายามสมัครบ่อยเกินไป กรุณารอ {$this->formatTime($remainingTime)}"
            ]);
        }

        if ($this->isLockedOut($ip, 'ip')) {
            $remainingTime = $this->getRemainingLockoutTime($ip, 'ip');

            Log::warning('LINE signup lockout - IP', [
                'ip' => $ip,
                'line_user_id' => $lineUserId ?? 'unknown',
                'remaining_seconds' => $remainingTime,
            ]);

            return response()->json([
                'error' => 'Too many signup attempts',
                'retry_after' => $remainingTime,
            ], 429);
        }

        // Check rate limits (before lockout)
        if ($lineUserId && $this->isRateLimited($lineUserId, 'user')) {
            $this->lockout($lineUserId, 'user');

            Log::alert('LINE signup rate limit exceeded - user locked out', [
                'line_user_id' => $lineUserId,
                'ip' => $ip,
            ]);

            return redirect()->back()->withErrors([
                'line_signup' => 'คุณพยายามสมัครบ่อยเกินไป กรุณารอสักครู่'
            ]);
        }

        if ($this->isRateLimited($ip, 'ip')) {
            $this->lockout($ip, 'ip');

            Log::alert('LINE signup rate limit exceeded - IP locked out', [
                'ip' => $ip,
                'line_user_id' => $lineUserId ?? 'unknown',
            ]);

            return response()->json([
                'error' => 'Too many signup attempts from this network',
            ], 429);
        }

        // Increment attempt counter
        if ($lineUserId) {
            $this->incrementAttempts($lineUserId, 'user');
        }
        $this->incrementAttempts($ip, 'ip');

        return $next($request);
    }

    /**
     * Get LINE user ID from request
     */
    private function getLineUserId(Request $request): ?string
    {
        // Try from route parameter
        if ($request->route('token')) {
            $prospect = \App\Models\MlmProspect::where('referral_token', $request->route('token'))->first();
            return $prospect?->line_user_id;
        }

        // Try from session (LINE login flow)
        $lineProfile = session('line_temp_profile');
        return $lineProfile['line_user_id'] ?? null;
    }

    /**
     * Check if rate limited
     */
    private function isRateLimited(string $identifier, string $type): bool
    {
        $key = "line_signup_{$type}:{$identifier}";
        $attempts = Cache::get($key, 0);

        $maxAttempts = $type === 'user'
            ? self::MAX_ATTEMPTS_PER_USER
            : self::MAX_ATTEMPTS_PER_IP;

        return $attempts >= $maxAttempts;
    }

    /**
     * Check if locked out
     */
    private function isLockedOut(string $identifier, string $type): bool
    {
        $key = "line_signup_lockout_{$type}:{$identifier}";
        return Cache::has($key);
    }

    /**
     * Get remaining lockout time in seconds
     */
    private function getRemainingLockoutTime(string $identifier, string $type): int
    {
        $key = "line_signup_lockout_{$type}:{$identifier}";
        $lockoutUntil = Cache::get($key);

        if (!$lockoutUntil) {
            return 0;
        }

        $remaining = $lockoutUntil - now()->timestamp;
        return max(0, $remaining);
    }

    /**
     * Lockout the identifier
     */
    private function lockout(string $identifier, string $type): void
    {
        $key = "line_signup_lockout_{$type}:{$identifier}";
        $lockoutUntil = now()->addSeconds(self::LOCKOUT_DURATION)->timestamp;

        Cache::put($key, $lockoutUntil, now()->addSeconds(self::LOCKOUT_DURATION));
    }

    /**
     * Increment attempt counter
     */
    private function incrementAttempts(string $identifier, string $type): void
    {
        $key = "line_signup_{$type}:{$identifier}";
        $current = Cache::get($key, 0);

        Cache::put($key, $current + 1, now()->addSeconds(self::TIME_WINDOW));
    }

    /**
     * Format time duration
     */
    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours} ชั่วโมง {$minutes} นาที";
        }

        return "{$minutes} นาที";
    }

    /**
     * Clear attempt counters (call after successful signup)
     */
    public static function clearAttempts(string $lineUserId, string $ip): void
    {
        Cache::forget("line_signup_user:{$lineUserId}");
        Cache::forget("line_signup_ip:{$ip}");
        Cache::forget("line_signup_lockout_user:{$lineUserId}");
        Cache::forget("line_signup_lockout_ip:{$ip}");

        Log::info('LINE signup attempts cleared', [
            'line_user_id' => $lineUserId,
            'ip' => $ip,
        ]);
    }
}
