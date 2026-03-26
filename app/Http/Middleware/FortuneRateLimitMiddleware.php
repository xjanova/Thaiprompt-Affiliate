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
     * เพิ่มเป็น 120 เพื่อให้ตอบได้รวดเร็ว — เหลือแค่กัน spam/fraud
     */
    protected int $maxAttemptsPerIp = 120;

    /**
     * จำนวน requests สูงสุดต่อ window (ต่อ Facebook User)
     * เพิ่มเป็น 200 เพื่อไม่จำกัดการใช้งานปกติ
     */
    protected int $maxAttemptsPerUser = 200;

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
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ดึง IP address
        $ip = $request->ip();
        $ipKey = 'fortune_rate_limit:ip:'.$ip;

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
            $userKey = 'fortune_rate_limit:fb_user:'.$facebookUserId;

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
     */
    protected function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * เพิ่มจำนวน attempts
     */
    protected function hit(string $key): int
    {
        $expiry = now()->addMinutes($this->decayMinutes);
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, $expiry);

        // เก็บเวลาหมดอายุสำหรับคำนวณ retry_after
        if (! Cache::has($key.':timer')) {
            Cache::put($key.':timer', $expiry, $expiry);
        }

        return $attempts;
    }

    /**
     * ดึงจำนวน attempts ปัจจุบัน
     */
    protected function attempts(string $key): int
    {
        return Cache::get($key, 0);
    }

    /**
     * ดึงจำนวนวินาทีที่ต้องรอก่อนลองใหม่
     */
    protected function availableIn(string $key): int
    {
        $expiresAt = Cache::get($key.':timer');

        if (! $expiresAt) {
            return $this->decayMinutes * 60;
        }

        return max(0, now()->diffInSeconds($expiresAt, false));
    }

    /**
     * ดึง timestamp ที่ rate limit จะ reset
     */
    protected function availableAt(string $key): int
    {
        $expiresAt = Cache::get($key.':timer');

        return $expiresAt
            ? $expiresAt->timestamp
            : now()->addMinutes($this->decayMinutes)->timestamp;
    }

    /**
     * ดึง Facebook User ID จาก webhook payload
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
     */
    public static function clearRateLimit(string $identifier): void
    {
        Cache::forget('fortune_rate_limit:ip:'.$identifier);
        Cache::forget('fortune_rate_limit:ip:'.$identifier.':timer');
        Cache::forget('fortune_rate_limit:fb_user:'.$identifier);
        Cache::forget('fortune_rate_limit:fb_user:'.$identifier.':timer');
    }
}
