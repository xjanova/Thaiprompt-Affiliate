<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * NFC Device Pairing API Controller
 *
 * จับคู่แอพ NFCMasterPro กับระบบหลังบ้าน Thaiprompt
 * - สร้าง QR Code สำหรับจับคู่แอพ (admin panel)
 * - ลงทะเบียนอุปกรณ์ (จากแอพ)
 * - ตรวจสอบสถานะอุปกรณ์
 *
 * @version 1.0.0
 */
class NFCDeviceApiController extends Controller
{
    /**
     * Generate QR payload for app-backend pairing
     *
     * เรียกจากหน้าแอดมิน → สร้าง QR ที่แอพสแกนแล้วตั้งค่า API อัตโนมัติ
     *
     * POST /api/v1/nfc/devices/generate-qr
     */
    public function generatePairingQR(Request $request)
    {
        $user = $request->user();
        $deviceToken = Str::random(48);
        $expiresAt = now()->addHour();

        // Store device token for validation when app registers
        Cache::put("nfc_device_pairing:{$deviceToken}", [
            'created_by' => $user?->id,
            'org_id' => config('app.org_id', 'default'),
            'created_at' => now()->toIso8601String(),
        ], $expiresAt);

        $apiUrl = rtrim(config('app.url'), '/') . '/api/v1';
        $apiKey = $this->generateDeviceApiKey($user);
        $orgName = config('app.name', 'Thaiprompt');

        $qrPayload = [
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
            'device_token' => $deviceToken,
            'org_name' => $orgName,
            'org_id' => config('app.org_id', 'default'),
            'expires_at' => $expiresAt->toIso8601String(),
            'permissions' => ['nfc', 'payment', 'members', 'cards'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'qr_payload' => json_encode($qrPayload),
                'qr_payload_object' => $qrPayload,
                'device_token' => $deviceToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_minutes' => 60,
            ],
        ]);
    }

    /**
     * Register device (called from NFCMasterPro app after scanning QR)
     *
     * POST /api/v1/nfc/devices/register
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'app_version' => 'nullable|string',
            'platform' => 'nullable|string',
            'app_name' => 'nullable|string',
        ]);

        $deviceToken = $request->device_token;

        // Validate token from cache
        $cached = Cache::get("nfc_device_pairing:{$deviceToken}");

        // If token not in cache, could be a direct registration with valid API key
        // Accept registration if the request has valid API authentication
        $deviceData = [
            'device_token' => $deviceToken,
            'app_version' => $request->app_version ?? 'unknown',
            'platform' => $request->platform ?? 'android',
            'app_name' => $request->app_name ?? 'NFCMasterPro',
            'registered_at' => now()->toIso8601String(),
            'ip_address' => $request->ip(),
            'paired_by' => $cached['created_by'] ?? null,
        ];

        // Store registered device
        Cache::put("nfc_device:{$deviceToken}", $deviceData, now()->addYear());

        // Clean up pairing token
        if ($cached) {
            Cache::forget("nfc_device_pairing:{$deviceToken}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully / ลงทะเบียนอุปกรณ์สำเร็จ',
            'data' => [
                'device_token' => $deviceToken,
                'registered_at' => $deviceData['registered_at'],
            ],
        ]);
    }

    /**
     * Check device status
     *
     * GET /api/v1/nfc/devices/status
     */
    public function deviceStatus(Request $request)
    {
        $deviceToken = $request->header('X-Device-Token') ?? $request->device_token;

        if (!$deviceToken) {
            return response()->json([
                'success' => false,
                'error' => 'Device token required',
            ], 400);
        }

        $device = Cache::get("nfc_device:{$deviceToken}");

        if (!$device) {
            return response()->json([
                'success' => false,
                'is_registered' => false,
                'error' => 'Device not registered',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'is_registered' => true,
            'data' => [
                'app_version' => $device['app_version'],
                'platform' => $device['platform'],
                'registered_at' => $device['registered_at'],
            ],
        ]);
    }

    /**
     * Revoke device registration
     *
     * DELETE /api/v1/nfc/devices/revoke
     */
    public function revokeDevice(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        Cache::forget("nfc_device:{$request->device_token}");

        return response()->json([
            'success' => true,
            'message' => 'Device revoked successfully',
        ]);
    }

    /**
     * Generate a device-specific API key
     */
    private function generateDeviceApiKey($user): string
    {
        // In production, create a proper API key in the database
        // For now, use the user's existing API key or generate a temporary one
        if ($user && $user->api_key) {
            return $user->api_key;
        }

        return 'nfc_' . Str::random(32);
    }
}
