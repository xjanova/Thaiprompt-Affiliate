<?php

namespace App\Http\Middleware;

use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('ratelimit.enabled')) {
            return $next($request);
        }

        $ip = $request->ip();
        $email = $request->input('email');

        // Create cache keys
        $ipKey = "login_attempts:ip:{$ip}";
        $emailKey = $email ? "login_attempts:email:{$email}" : null;

        $maxAttempts = config('ratelimit.login.max_attempts', 5);
        $decayMinutes = config('ratelimit.login.decay_minutes', 15);
        $lockoutMinutes = config('ratelimit.login.lockout_minutes', 30);

        // Check if IP is locked out
        if (Cache::has("login_lockout:ip:{$ip}")) {
            $remainingTime = Cache::get("login_lockout:ip:{$ip}");

            Log::warning('Login attempt blocked: IP locked out', [
                'ip' => $ip,
                'email' => $email,
                'remaining_minutes' => round($remainingTime / 60),
            ]);

            return $this->buildLockoutResponse($remainingTime);
        }

        // Check if email is locked out
        if ($emailKey && Cache::has("login_lockout:email:{$email}")) {
            $remainingTime = Cache::get("login_lockout:email:{$email}");

            Log::warning('Login attempt blocked: Email locked out', [
                'ip' => $ip,
                'email' => $email,
                'remaining_minutes' => round($remainingTime / 60),
            ]);

            return $this->buildLockoutResponse($remainingTime);
        }

        // Get current attempt counts
        $ipAttempts = Cache::get($ipKey, 0);
        $emailAttempts = $emailKey ? Cache::get($emailKey, 0) : 0;

        // Check if max attempts exceeded
        if ($ipAttempts >= $maxAttempts || $emailAttempts >= $maxAttempts) {
            // Lock out the IP and email
            $lockoutSeconds = $lockoutMinutes * 60;

            Cache::put("login_lockout:ip:{$ip}", $lockoutSeconds, $lockoutSeconds);

            if ($emailKey) {
                Cache::put("login_lockout:email:{$email}", $lockoutSeconds, $lockoutSeconds);
            }

            Log::warning('Login attempts exceeded: Account locked', [
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts,
                'lockout_minutes' => $lockoutMinutes,
            ]);

            // Log to security logs
            SecurityLog::logRateLimitExceeded(
                ipAddress: $ip,
                endpoint: '/login',
                email: $email,
                userAgent: $request->userAgent()
            );

            return $this->buildLockoutResponse($lockoutSeconds);
        }

        // Process the request
        $response = $next($request);

        // If login failed (401 or validation error), increment attempts
        if ($response->status() === 401 || $response->status() === 422) {
            $attempts = Cache::get($ipKey, 0) + 1;
            Cache::put($ipKey, $attempts, $decayMinutes * 60);

            if ($emailKey) {
                $emailAttempts = Cache::get($emailKey, 0) + 1;
                Cache::put($emailKey, $emailAttempts, $decayMinutes * 60);
            }

            $remainingAttempts = $maxAttempts - $attempts;

            if ($remainingAttempts > 0) {
                Log::info('Failed login attempt', [
                    'ip' => $ip,
                    'email' => $email,
                    'attempts' => $attempts,
                    'remaining_attempts' => $remainingAttempts,
                ]);

                // Log to security logs
                SecurityLog::logFailedLogin(
                    ipAddress: $ip,
                    email: $email,
                    reason: "Failed login attempt ({$attempts}/{$maxAttempts})",
                    userAgent: $request->userAgent()
                );
            }
        } else if ($response->status() === 200 || $response->status() === 302) {
            // Login successful, clear attempts
            Cache::forget($ipKey);
            if ($emailKey) {
                Cache::forget($emailKey);
            }

            // Log successful login
            if ($request->user()) {
                SecurityLog::logSuccessfulLogin(
                    ipAddress: $ip,
                    userId: $request->user()->id,
                    userAgent: $request->userAgent()
                );
            }
        }

        return $response;
    }

    /**
     * Build a lockout response.
     */
    protected function buildLockoutResponse(int $remainingSeconds): Response
    {
        $minutes = ceil($remainingSeconds / 60);

        return response()->json([
            'message' => __('Too many login attempts. Please try again in :minutes minutes.', ['minutes' => $minutes]),
            'error' => 'too_many_attempts',
            'retry_after' => $remainingSeconds,
        ], 429)->header('Retry-After', $remainingSeconds);
    }
}
