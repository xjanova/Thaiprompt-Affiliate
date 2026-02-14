<?php

namespace App\Http\Middleware;

use App\Models\SmsCheckerDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสอบ SMS Checker Device
 *
 * Middleware สำหรับ authenticate อุปกรณ์ SMS Checker ผ่าน API Key
 * ตรวจสอบ: API key → สถานะอุปกรณ์ → Device ID (ถ้ามี)
 */
class VerifySmsCheckerDevice
{
    /**
     * ตรวจสอบ API key และสถานะอุปกรณ์
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
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        // ตรวจสอบว่าอุปกรณ์ active หรือไม่
        if (! $device->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Device is '.$device->status,
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

        // แนบข้อมูลอุปกรณ์ไปกับ request
        $request->attributes->set('sms_checker_device', $device);

        return $next($request);
    }
}
