<?php

namespace App\Http\Middleware;

use App\Models\SmsCheckerDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySmsCheckerDevice
{
    /**
     * Verify the SMS Checker device API key and device status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
            ], 401);
        }

        $device = SmsCheckerDevice::findByApiKey($apiKey);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        if (!$device->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Device is ' . $device->status,
            ], 403);
        }

        // Verify device ID if provided
        $deviceId = $request->header('X-Device-Id');
        if ($deviceId && $device->device_id !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID mismatch',
            ], 403);
        }

        // Attach device to request (use attributes for object, not merge)
        $request->attributes->set('sms_checker_device', $device);

        return $next($request);
    }
}
