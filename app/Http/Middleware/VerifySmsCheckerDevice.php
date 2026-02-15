<?php

namespace App\Http\Middleware;

use App\Models\SmsCheckerDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสอบ SMS Checker Device
 *
 * Middleware สำหรับ authenticate อุปกรณ์ SMS Checker ผ่าน API Key
 * ตรวจสอบ: API key → สถานะอุปกรณ์ → Device ID → Rate Limiting
 *
 * Rate Limiting:
 * - Critical endpoints (notify, register, FCM) → ไม่จำกัด (ต้องผ่านเสมอ)
 * - Standard endpoints (orders, sync, status) → 120 requests/นาที/อุปกรณ์
 */
class VerifySmsCheckerDevice
{
    /**
     * Endpoints ที่สำคัญ — ต้องผ่านเสมอ ไม่ต้อง rate limit
     */
    private const CRITICAL_PATHS = [
        'register-device',
        'register-fcm-token',
        'notify',
        'notify-action',
        'approve',
        'reject',
        'bulk-approve',
        'debug-report',
        'debug-topup',
    ];

    /**
     * ตรวจสอบ API key, สถานะอุปกรณ์ และ rate limiting
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
            ], 401);
        }

        // ค้นหาอุปกรณ์จาก API key
        $device = SmsCheckerDevice::findByApiKey($apiKey);

        if (! $device) {
            Log::warning('SmsChecker: Invalid API key', [
                'ip' => $request->ip(),
                'key_prefix' => substr($apiKey, 0, 10) . '...',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        // ตรวจสอบว่าอุปกรณ์ active หรือไม่
        if (! $device->isActive()) {
            Log::warning('SmsChecker: Inactive device attempted access', [
                'device_id' => $device->device_id,
                'status' => $device->status,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device is ' . $device->status,
            ], 403);
        }

        // ตรวจสอบ Device ID (ถ้ามีการส่งมา)
        $deviceId = $request->header('X-Device-Id');
        if ($deviceId && $device->device_id !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID mismatch',
            ], 403);
        }

        // Rate Limiting — เฉพาะ non-critical endpoints
        if (! $this->isCriticalPath($request)) {
            $rateLimit = 120; // requests ต่อนาที
            $cacheKey = 'smschecker_rate:' . $device->device_id;
            $requestCount = Cache::get($cacheKey, 0);

            if ($requestCount >= $rateLimit) {
                Log::warning('SmsChecker: Rate limit exceeded', [
                    'device_id' => $device->device_id,
                    'count' => $requestCount,
                    'limit' => $rateLimit,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Rate limit exceeded',
                ], 429);
            }

            Cache::put($cacheKey, $requestCount + 1, now()->addMinute());
        }

        // แนบข้อมูลอุปกรณ์ไปกับ request
        $request->attributes->set('sms_checker_device', $device);

        return $next($request);
    }

    /**
     * ตรวจสอบว่า request นี้เป็น critical path หรือไม่
     *
     * Critical paths เช่น notify, register, approve — ต้องผ่านเสมอ
     * ห้าม rate limit เพราะจะทำให้ SMS notification หรือ order approval หลุด
     */
    private function isCriticalPath(Request $request): bool
    {
        $path = $request->path();

        foreach (self::CRITICAL_PATHS as $criticalPath) {
            if (str_contains($path, $criticalPath)) {
                return true;
            }
        }

        return false;
    }
}
