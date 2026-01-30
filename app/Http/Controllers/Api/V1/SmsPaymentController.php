<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Services\SmsPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * SMS Payment Controller
 *
 * จัดการ API สำหรับระบบชำระเงินผ่าน SMS Checker
 * รับ notification จาก Android App และจัดการ unique amount สำหรับ checkout
 */
class SmsPaymentController extends Controller
{
    public function __construct(
        private SmsPaymentService $smsPaymentService
    ) {}

    /**
     * รับ SMS payment notification จาก Android App
     *
     * POST /api/v1/sms-payment/notify
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function notify(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ตรวจสอบ rate limit ต่ออุปกรณ์
        $rateLimitKey = 'smschecker:rate:' . $device->device_id;
        $rateLimit = config('smschecker.rate_limit_per_minute', 30);
        $currentCount = (int) Cache::get($rateLimitKey, 0);

        if ($currentCount >= $rateLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Max ' . $rateLimit . ' requests per minute.',
            ], 429);
        }

        // เพิ่ม counter (หมดอายุ 60 วินาที)
        Cache::put($rateLimitKey, $currentCount + 1, 60);

        // ตรวจสอบ security headers ที่จำเป็น
        $signature = $request->header('X-Signature');
        $nonce = $request->header('X-Nonce');
        $timestamp = $request->header('X-Timestamp');

        if (!$signature || !$nonce || !$timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required security headers',
            ], 400);
        }

        // ตรวจสอบความสดของ timestamp (อ่านค่าจาก config)
        $toleranceSeconds = config('smschecker.timestamp_tolerance', 300);
        $requestTime = intval($timestamp);
        $currentTime = intval(round(microtime(true) * 1000));
        if (abs($currentTime - $requestTime) > ($toleranceSeconds * 1000)) {
            return response()->json([
                'success' => false,
                'message' => 'Request timestamp expired',
            ], 400);
        }

        // รับ encrypted data
        $encryptedData = $request->input('data');
        if (!$encryptedData) {
            return response()->json([
                'success' => false,
                'message' => 'No payload data',
            ], 400);
        }

        // ตรวจสอบ HMAC signature
        $signatureData = $encryptedData . $nonce . $timestamp;
        if (!$this->smsPaymentService->verifySignature($signatureData, $signature, $device->secret_key)) {
            Log::warning('SMS Payment: ลายเซ็นไม่ถูกต้อง', [
                'device_id' => $device->device_id,
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        // ถอดรหัส payload
        $payload = $this->smsPaymentService->decryptPayload($encryptedData, $device->secret_key);
        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decrypt payload',
            ], 400);
        }

        // ตรวจสอบ bank ที่รองรับจาก config
        $supportedBanks = array_keys(config('smschecker.supported_banks', []));
        $bankRule = !empty($supportedBanks)
            ? 'required|string|in:' . implode(',', $supportedBanks)
            : 'required|string|max:20';

        // ตรวจสอบ payload fields
        $validator = Validator::make($payload, [
            'bank' => $bankRule,
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'account_number' => 'nullable|string|max:50',
            'sender_or_receiver' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'sms_timestamp' => 'required|numeric',
            'device_id' => 'required|string',
            'nonce' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload data',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ประมวลผล notification
        $result = $this->smsPaymentService->processNotification(
            $payload,
            $device,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ตรวจสอบสถานะอุปกรณ์และจำนวน pending
     *
     * GET /api/v1/sms-payment/status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $pendingCount = SmsPaymentNotification::where('device_id', $device->device_id)
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'success' => true,
            'status' => $device->status,
            'pending_count' => $pendingCount,
            'message' => null,
        ]);
    }

    /**
     * ลงทะเบียน/อัพเดทข้อมูลอุปกรณ์
     *
     * POST /api/v1/sms-payment/register-device
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:50',
            'device_name' => 'required|string|max:100',
            'platform' => 'required|string|max:20',
            'app_version' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device->update([
            'device_name' => $request->input('device_name'),
            'platform' => $request->input('platform'),
            'app_version' => $request->input('app_version'),
            'last_active_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
        ]);
    }

    /**
     * สร้าง unique payment amount สำหรับ checkout
     *
     * เรียกจากระบบ web checkout ไม่ใช่จาก Android App
     *
     * POST /api/v1/sms-payment/generate-amount
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateAmount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_amount' => 'required|numeric|min:1',
            'transaction_id' => 'nullable|integer',
            'transaction_type' => 'nullable|string|max:50',
            'expiry_minutes' => 'nullable|integer|min:5|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $expiryMinutes = $request->input('expiry_minutes')
            ?? config('smschecker.unique_amount_expiry', 30);

        $uniqueAmount = $this->smsPaymentService->generateUniqueAmount(
            $request->input('base_amount'),
            $request->input('transaction_id'),
            $request->input('transaction_type', 'order'),
            $expiryMinutes
        );

        if (!$uniqueAmount) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้าง unique amount ได้ มี transactions pending เต็มสำหรับราคานี้',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้าง unique amount สำเร็จ',
            'data' => [
                'base_amount' => number_format((float) $uniqueAmount->base_amount, 2, '.', ''),
                'unique_amount' => number_format((float) $uniqueAmount->unique_amount, 2, '.', ''),
                'decimal_suffix' => $uniqueAmount->decimal_suffix,
                'expires_at' => $uniqueAmount->expires_at->toIso8601String(),
                'display_amount' => '฿' . number_format((float) $uniqueAmount->unique_amount, 2),
            ],
        ]);
    }

    /**
     * ดู notification history สำหรับ admin dashboard
     *
     * GET /api/v1/sms-payment/notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function notifications(Request $request): JsonResponse
    {
        $query = SmsPaymentNotification::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('bank')) {
            $query->where('bank', $request->input('bank'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }
}
