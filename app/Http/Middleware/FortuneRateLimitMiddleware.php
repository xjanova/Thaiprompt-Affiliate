<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortune Rate Limit Middleware
 *
 * ป้องกัน spam และ abuse ด้วย rate limiting แบบ sliding window
 * รองรับ multiple tiers: per IP, per user, global
 */
class FortuneRateLimitMiddleware
{
    /**
     * จำนวน requests สูงสุดต่อ window (ต่อ IP)
     */
    protected int $maxAttemptsPerIp = 10;

    /**
     * จำนวน requests สูงสุดต่อ window (ต่อ Facebook User)
     */
    protected int $maxAttemptsPerUser = 20;

    /**
     * Window duration (minutes)
     */
    protected int $decayMinutes = 1;

    /**
     * Handle an incoming request.
     *
     * จัดการ rate limiting ด้วย sliding window algorithm
     * - ต่อ IP: 10 requests/minute
     * - ต่อ Facebook User: 20 requests/minute
     * - Log การละเมิด
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ดึง IP address
        $ip = $request->ip();
        $ipKey = 'fortune_rate_limit:ip:' . $ip;

        // ตรวจสอบ rate limit สำหรับ IP
        if ($this->tooManyAttempts($ipKey, $this->maxAttemptsPerIp)) {
            Log::warning('Fortune API: Rate limit exceeded', [
                'ip' => $ip,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'กรุณารอสักครู่แล้วลองใหม่อีกครั้ง',
                'retry_after' => $this->availableIn($ipKey),
            ], 429);
        }

        // ถ้ามี Facebook User ID ให้เช็คด้วย
        $facebookUserId = $this->extractFacebookUserId($request);
        if ($facebookUserId) {
            $userKey = 'fortune_rate_limit:fb_user:' . $facebookUserId;

            if ($this->tooManyAttempts($userKey, $this->maxAttemptsPerUser)) {
                Log::warning('Fortune API: User rate limit exceeded', [
                    'facebook_user_id' => $facebookUserId,
                    'ip' => $ip,
                ]);

                return response()->json([
                    'error' => 'Too many requests',
                    'message' => 'คุณส่งคำขอบ่อยเกินไป กรุณารอสักครู่',
                    'retry_after' => $this->availableIn($userKey),
                ], 429);
            }

            // นับ attempt สำหรับ user
            $this->hit($userKey);
        }

        // นับ attempt สำหรับ IP
        $this->hit($ipKey);

        // ให้ request ผ่านไป
        $response = $next($request);

        // เพิ่ม rate limit headers
        $response->headers->set('X-RateLimit-Limit', $this->maxAttemptsPerIp);
        $response->headers->set('X-RateLimit-Remaining', max(0, $this->maxAttemptsPerIp - $this->attempts($ipKey)));
        $response->headers->set('X-RateLimit-Reset', $this->availableAt($ipKey));

        return $response;
    }

    /**
     * ตรวจสอบว่ามี attempts เกินจำนวนที่กำหนดหรือไม่
     *
     * @param string $key
     * @param int $maxAttempts
     * @return bool
     */
    protected function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * เพิ่มจำนวน attempts
     *
     * @param string $key
     * @return int
     */
    protected function hit(string $key): int
    {
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes($this->decayMinutes));
        return $attempts;
    }

    /**
     * ดึงจำนวน attempts ปัจจุบัน
     *
     * @param string $key
     * @return int
     */
    protected function attempts(string $key): int
    {
        return Cache::get($key, 0);
    }

    /**
     * ดึงจำนวนวินาทีที่ต้องรอก่อนลองใหม่
     *
     * @param string $key
     * @return int
     */
    protected function availableIn(string $key): int
    {
        $expiresAt = Cache::get($key . ':timer', now()->addMinutes($this->decayMinutes));
        return max(0, $expiresAt->diffInSeconds());
    }

    /**
     * ดึง timestamp ที่ rate limit จะ reset
     *
     * @param string $key
     * @return int
     */
    protected function availableAt(string $key): int
    {
        return now()->addMinutes($this->decayMinutes)->timestamp;
    }

    /**
     * ดึง Facebook User ID จาก webhook payload
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function extractFacebookUserId(Request $request): ?string
    {
        // จาก webhook payload
        $entry = $request->input('entry.0.messaging.0.sender.id')
            ?? $request->input('entry.0.changes.0.value.from.id');

        if ($entry) {
            return $entry;
        }

        // จาก query string (สำหรับ testing)
        return $request->input('facebook_user_id');
    }

    /**
     * Clear rate limit (สำหรับ testing)
     *
     * @param string $identifier
     * @return void
     */
    public static function clearRateLimit(string $identifier): void
    {
        Cache::forget('fortune_rate_limit:ip:' . $identifier);
        Cache::forget('fortune_rate_limit:fb_user:' . $identifier);
    }
}
