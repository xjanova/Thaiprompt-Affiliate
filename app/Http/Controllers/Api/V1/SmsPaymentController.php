<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Models\VendorStore;
use App\Services\Payment\PaymentService;
use App\Services\FcmNotificationService;
use App\Services\SmsPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        private SmsPaymentService $smsPaymentService,
        private FcmNotificationService $fcmService,
    ) {}

    /**
     * ตรวจสอบว่า device มีสิทธิ์เข้าถึง transaction หรือไม่
     *
     * - Admin device → เข้าถึงได้เฉพาะบิลของ Premium Shop (platformStoreId)
     *   ป้องกัน auto-approve ผิดร้าน: ถ้า seller โอนเงินยอดเดียวกัน admin จะไม่เห็นบิลนั้น
     * - Seller device → เข้าถึงได้เฉพาะบิลของ store ตัวเอง (device.store_id)
     *
     * @param SmsCheckerDevice $device
     * @param PaymentTransaction $transaction
     * @return bool
     */
    private function deviceCanAccessTransaction(SmsCheckerDevice $device, PaymentTransaction $transaction): bool
    {
        // Admin device → เห็นเฉพาะบิลของ Premium Shop
        if ($device->isAdminDevice()) {
            $platformStoreId = VendorStore::getPlatformStoreId();
            $txnStoreId = (int) ($transaction->store_id ?? $platformStoreId);
            return $txnStoreId === $platformStoreId;
        }

        // Seller device → เช็คว่า store_id ของ device ตรงกับ transaction
        return (int) $device->store_id === (int) ($transaction->store_id ?? VendorStore::getPlatformStoreId());
    }

    /**
     * แปลง device.store_id → ค่าจริงที่ใช้งาน
     *
     * - Admin device (store_id = null) → ใช้ platformStoreId (Premium Shop)
     * - Seller device → ใช้ store_id ของ device
     *
     * @param SmsCheckerDevice $device
     * @return int
     */
    private function resolveDeviceStoreId(SmsCheckerDevice $device): int
    {
        return (int) ($device->store_id ?? VendorStore::getPlatformStoreId());
    }

    /**
     * เพิ่ม store_id filter ให้ query ตาม device
     *
     * - Admin device → filter เฉพาะ platformStoreId (เห็นแค่บิล Premium Shop + หมอดู)
     *   ป้องกัน auto-approve ผิดร้าน: ยอดเงินของ seller ร้านอื่นจะไม่ปนมา
     * - Seller device → filter ตาม device.store_id (เห็นเฉพาะบิลร้านตัวเอง)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param SmsCheckerDevice $device
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyStoreFilter($query, SmsCheckerDevice $device)
    {
        if ($device->isAdminDevice()) {
            // Admin device → เห็นเฉพาะบิลของ Premium Shop (platformStoreId)
            $query->where('store_id', VendorStore::getPlatformStoreId());
        } else {
            // Seller device → เห็นเฉพาะบิลของ store ตัวเอง
            $query->where('store_id', $device->store_id);
        }

        return $query;
    }

    /**
     * ตรวจสอบว่า device มีสิทธิ์เข้าถึงบิลดูดวงหรือไม่
     *
     * FortuneReading ไม่มี store_id → ผูกกับ admin/platform เท่านั้น
     * เฉพาะ admin device เท่านั้นที่เห็นและจัดการบิลดูดวงได้
     *
     * @param SmsCheckerDevice $device
     * @return bool
     */
    private function deviceCanAccessFortuneReading(SmsCheckerDevice $device): bool
    {
        return $device->isAdminDevice();
    }

    /**
     * แปลง PaymentTransaction → RemoteOrderApproval format สำหรับ Android app
     *
     * Android app คาดหวัง format: id, approval_status, confidence,
     * order_details_json, notification, synced_version, etc.
     */
    private function transformToOrderApproval(PaymentTransaction $txn): array
    {
        // แปลง status ของ PaymentTransaction → approval_status ที่ Android เข้าใจ
        $approvalStatus = match ($txn->status) {
            'pending' => 'pending_review',
            'processing' => 'pending_review',
            'completed' => 'auto_approved',
            'failed' => 'rejected',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
            'refunded' => 'rejected',
            default => 'pending_review',
        };

        // ดึง order details (ต้องมี amount เสมอ ไม่ว่าจะมี order หรือไม่)
        // ใช้ transaction_id เป็น order_number หลัก เพื่อให้ Android ส่งกลับ approve ได้ถูกต้อง
        $orderDetails = [
            'order_number' => $txn->transaction_id,
            'product_name' => null,
            'product_details' => null,
            'quantity' => null,
            'website_name' => config('app.name'),
            'customer_name' => $txn->user?->name ?? null,
            // CRITICAL: ส่งยอดเงินที่ลูกค้าต้องจ่ายจริง (รวมทศนิยมที่ generate แล้ว)
            // ต้องเป็น Double ไม่ใช่ String เพื่อให้ Android แมทได้แบบ exact match
            'amount' => (float) $txn->amount,
        ];

        // ถ้ามี order เชื่อมโยง ให้เติมข้อมูลเพิ่ม
        if ($txn->order_id && $txn->order) {
            $order = $txn->order;
            $orderDetails['product_name'] = $order->items?->first()?->product?->name ?? null;
            $orderDetails['quantity'] = $order->items?->count() ?? null;
            $orderDetails['customer_name'] = $order->user?->name ?? $txn->user?->name ?? null;
        }

        // ดึง matched notification ถ้ามี
        $notification = null;
        $matchedNotification = SmsPaymentNotification::where('matched_transaction_id', $txn->id)->first();
        if ($matchedNotification) {
            $notification = [
                'id' => $matchedNotification->id,
                'bank' => $matchedNotification->bank,
                'type' => $matchedNotification->type,
                // ส่งเป็น String แต่ format ให้ได้ทศนิยม 2 ตำแหน่งเสมอ (เช่น "10.79")
                'amount' => sprintf('%.2f', (float) $matchedNotification->amount),
                'sms_timestamp' => $matchedNotification->sms_timestamp,
                'sender_or_receiver' => $matchedNotification->sender_or_receiver,
            ];
        } else {
            // ไม่มี notification match → สร้าง dummy notification จาก transaction data
            $notification = [
                'id' => $txn->id,
                'bank' => $txn->payment_method === 'promptpay' ? 'PROMPTPAY' : strtoupper($txn->payment_method ?? 'UNKNOWN'),
                'type' => 'credit',
                // ส่งเป็น String แต่ format ให้ได้ทศนิยม 2 ตำแหน่งเสมอ (เช่น "10.79")
                'amount' => sprintf('%.2f', (float) $txn->amount),
                'sms_timestamp' => $txn->created_at?->format('Y-m-d H:i:s'),
                'sender_or_receiver' => $txn->user?->name ?? '',
            ];
        }

        return [
            'id' => $txn->id,
            'notification_id' => $matchedNotification?->id,
            'matched_transaction_id' => $txn->id,
            'device_id' => $matchedNotification?->device_id,
            'approval_status' => $approvalStatus,
            'confidence' => $matchedNotification ? 'high' : 'medium',
            'approved_by' => null,
            'approved_at' => $txn->paid_at?->toIso8601String(),
            'rejected_at' => $txn->status === 'failed' ? $txn->updated_at?->toIso8601String() : null,
            'rejection_reason' => null,
            'order_details_json' => $orderDetails,
            // ชื่อเซิร์ฟเวอร์ เพื่อให้แอพแสดงว่าบิลมาจากเซิร์ฟไหน
            'server_name' => config('app.name'),
            'synced_version' => $txn->updated_at ? intval($txn->updated_at->timestamp * 1000) : 0,
            // เวลาที่สร้างบิลจริงจากเซิร์ฟ (ISO 8601) - แอพควรใช้เวลานี้แสดง ไม่ใช่ createdAt ของ local DB
            'created_at' => $txn->created_at?->toIso8601String(),
            'updated_at' => $txn->updated_at?->toIso8601String(),
            'notification' => $notification,
        ];
    }

    /**
     * แปลง FortuneReading → RemoteOrderApproval format สำหรับ Android app
     *
     * ใช้ bill_reference (FTU-YYMMDD-XXXXX) เป็น order_number
     * เพื่อให้ Android ส่งกลับ approve ตาม prefix ได้ถูกต้อง
     */
    private function transformFortuneReadingToOrderApproval(FortuneReading $reading): array
    {
        // แปลง conversation_status → approval_status ที่ Android เข้าใจ
        $approvalStatus = match ($reading->conversation_status) {
            FortuneReading::STATUS_PENDING_PAYMENT => 'pending_review',
            FortuneReading::STATUS_PAID, FortuneReading::STATUS_COMPLETED => 'auto_approved',
            default => 'pending_review',
        };

        // ดึงยอดเงินจริงที่ต้องชำระ (unique amount)
        $uniquePayment = $reading->unique_payment_amount_id
            ? UniquePaymentAmount::find($reading->unique_payment_amount_id)
            : null;
        $displayAmount = $uniquePayment
            ? (float) $uniquePayment->unique_amount
            : (float) $reading->amount_paid;

        $orderDetails = [
            'order_number' => $reading->bill_reference,
            'product_name' => 'ดูดวง' . ($reading->reading_type === 'deep' ? ' (เชิงลึก)' : ''),
            'product_details' => $reading->facebook_user_name ?? null,
            'quantity' => 1,
            'website_name' => config('app.name'),
            'customer_name' => $reading->facebook_user_name ?? 'ลูกค้าดูดวง',
            'amount' => $displayAmount,
        ];

        // ดึง matched notification ถ้ามี
        $notification = null;
        if ($reading->sms_notification_id) {
            $matchedNotification = SmsPaymentNotification::find($reading->sms_notification_id);
            if ($matchedNotification) {
                $notification = [
                    'id' => $matchedNotification->id,
                    'bank' => $matchedNotification->bank,
                    'type' => $matchedNotification->type,
                    'amount' => sprintf('%.2f', (float) $matchedNotification->amount),
                    'sms_timestamp' => $matchedNotification->sms_timestamp,
                    'sender_or_receiver' => $matchedNotification->sender_or_receiver,
                ];
            }
        }

        if (! $notification) {
            $notification = [
                'id' => $reading->id,
                'bank' => 'PROMPTPAY',
                'type' => 'credit',
                'amount' => sprintf('%.2f', $displayAmount),
                'sms_timestamp' => $reading->created_at?->format('Y-m-d H:i:s'),
                'sender_or_receiver' => $reading->facebook_user_name ?? '',
            ];
        }

        return [
            'id' => $reading->id,
            'notification_id' => $reading->sms_notification_id,
            'matched_transaction_id' => $reading->id,
            'device_id' => null,
            'approval_status' => $approvalStatus,
            'confidence' => $reading->is_paid ? 'high' : 'medium',
            'approved_by' => null,
            'approved_at' => $reading->paid_at?->toIso8601String(),
            'rejected_at' => null,
            'rejection_reason' => null,
            'order_details_json' => $orderDetails,
            'server_name' => config('app.name'),
            'synced_version' => $reading->updated_at ? intval($reading->updated_at->timestamp * 1000) : 0,
            'created_at' => $reading->created_at?->toIso8601String(),
            'updated_at' => $reading->updated_at?->toIso8601String(),
            'notification' => $notification,
        ];
    }

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
            'fcm_token' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [
            'device_name' => $request->input('device_name'),
            'platform' => $request->input('platform'),
            'app_version' => $request->input('app_version'),
            'last_active_at' => now(),
            'ip_address' => $request->ip(),
        ];

        // บันทึก FCM token สำหรับ push notifications
        if ($request->filled('fcm_token')) {
            $updateData['fcm_token'] = $request->input('fcm_token');
            $updateData['fcm_token_updated_at'] = now();
        }

        $device->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
        ]);
    }

    /**
     * Register/update FCM token สำหรับ push notifications
     *
     * Android App เรียกเมื่อ FCM token เปลี่ยน (startup หรือ onNewToken)
     * endpoint นี้รับเฉพาะ fcm_token เท่านั้น ไม่ต้องส่งข้อมูลอุปกรณ์ทั้งหมดเหมือน register-device
     *
     * POST /api/v1/sms-payment/register-fcm-token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerFcmToken(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->fcmService->registerToken($device, $request->input('fcm_token'));

        return response()->json([
            'success' => true,
            'message' => 'FCM token registered successfully',
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

    // =================================================================
    // Endpoints สำหรับ Android App (Orders management + Device settings)
    // =================================================================

    /**
     * ดึงรายการ orders (pending payment transactions)
     *
     * Android App ใช้แสดงรายการ orders ที่รอชำระเงิน
     *
     * GET /api/v1/sms-payment/orders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function orders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = PaymentTransaction::query();

        // === Multi-Store Filtering (Strict Mode) ===
        // ทุก device เห็นเฉพาะ orders ของ store ตัวเองเท่านั้น
        // (null = admin/official shop, int = seller store)
        $this->applyStoreFilter($query, $device);

        // กรองสถานะ (default: pending + processing = รอชำระเงิน)
        $status = $request->input('status', 'waiting');
        if ($status === 'waiting') {
            // 'waiting' = pending หรือ processing (รอชำระเงิน)
            $query->whereIn('status', ['pending', 'processing']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        // กรองตามวันที่
        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo);
        }

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('promptpay_ref_no', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        // แปลง PaymentTransaction → RemoteOrderApproval format สำหรับ Android app
        $orders = collect($paginated->items())->map(function ($txn) {
            return $this->transformToOrderApproval($txn);
        });

        // === รวมบิลดูดวง (FortuneReading) ที่รอชำระเงินหรือชำระแล้ว ===
        // FortuneReading ไม่มี store_id → เฉพาะ admin device เท่านั้นที่เห็น
        // แสดงเฉพาะ page 1 เพื่อไม่ให้ซ้ำในหน้าถัดไป
        // หมายเหตุ: บิลเก่าอาจไม่มี unique_payment_amount_id (สร้างก่อนระบบ SMS Checker)
        // → ใช้ conversation_status เป็นหลักในการกรอง ไม่ต้องบังคับว่าต้องมี unique_payment_amount_id
        $fortuneReadings = collect();
        if ($paginated->currentPage() === 1 && $this->deviceCanAccessFortuneReading($device)) {
            $fortuneQuery = FortuneReading::query()
                ->whereNotNull('bill_reference');

            if ($status === 'waiting') {
                $fortuneQuery->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT);
            } elseif ($status === 'all') {
                $fortuneQuery->whereIn('conversation_status', [
                    FortuneReading::STATUS_PENDING_PAYMENT,
                    FortuneReading::STATUS_PAID,
                    FortuneReading::STATUS_COMPLETED,
                ]);
            }

            if ($dateFrom) {
                $fortuneQuery->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $fortuneQuery->where('created_at', '<=', $dateTo);
            }

            $fortuneReadings = $fortuneQuery->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $fortuneOrders = $fortuneReadings->map(function ($reading) {
                return $this->transformFortuneReadingToOrderApproval($reading);
            });

            // รวมเข้ากับ orders แล้ว sort ตาม created_at
            $orders = $orders->concat($fortuneOrders)
                ->sortByDesc('created_at')
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $orders,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total() + $fortuneReadings->count(),
            ],
        ]);
    }

    /**
     * อนุมัติ order (ยืนยันการชำระเงิน)
     *
     * รองรับทั้ง numeric ID (legacy) และ bill_reference string (ใหม่)
     * Decode prefix: PRE-/SEL-/TXN-/TAROT- → PaymentTransaction, FTU-/FR- → FortuneReading
     *
     * POST /api/v1/sms-payment/orders/{identifier}/approve
     *
     * @param Request $request
     * @param mixed $identifier numeric ID หรือ bill_reference string
     * @return JsonResponse
     */
    public function approveOrder(Request $request, $identifier): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $resolved = $this->resolveOrderByIdentifier($identifier);
        if (! $resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found: ' . $identifier,
            ], 404);
        }

        $model = $resolved['model'];
        $type = $resolved['type'];

        // === FortuneReading: approve ด้วย confirmPayment() ===
        if ($type === 'fortune') {
            /** @var FortuneReading $model */

            // FortuneReading ไม่มี store_id → เฉพาะ admin device เท่านั้น
            if (! $this->deviceCanAccessFortuneReading($device)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admin devices can manage fortune reading bills',
                ], 403);
            }

            if ($model->is_paid) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fortune reading already paid',
                    'data' => ['bill_reference' => $model->bill_reference, 'status' => 'paid'],
                ]);
            }

            // ค้นหา SMS notification ที่จับคู่กับบิลนี้
            $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();
            $model->confirmPayment($notification);

            // อัพเดท notification สถานะเป็น confirmed
            if ($notification) {
                $notification->update(['status' => 'confirmed']);
            }

            Log::info('SMS Payment: อนุมัติบิลดูดวงจากอุปกรณ์', [
                'fortune_reading_id' => $model->id,
                'bill_reference' => $model->bill_reference,
                'device_id' => $device->device_id,
                'sms_notification_id' => $notification?->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fortune reading approved successfully',
                'data' => ['bill_reference' => $model->bill_reference, 'status' => 'paid'],
            ]);
        }

        // === PaymentTransaction: approve ด้วย completePayment() ===
        /** @var PaymentTransaction $model */

        // Multi-Store (Strict): ตรวจสอบสิทธิ์ของ device
        if (! $this->deviceCanAccessTransaction($device, $model)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this transaction',
            ], 403);
        }

        // Idempotent: ถ้า approved แล้ว → return success
        if ($model->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Order already approved',
                'data' => ['transaction_id' => $model->transaction_id, 'status' => 'completed'],
            ]);
        }

        if (! in_array($model->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending (current: ' . $model->status . ')',
            ], 422);
        }

        // Mark UniquePaymentAmount as used (เหมือน xmanstudio)
        $uniqueAmount = UniquePaymentAmount::where('transaction_id', $model->id)
            ->whereIn('status', ['reserved', 'expired'])
            ->first();
        if ($uniqueAmount) {
            $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
        }

        // Update SMS notification status to confirmed (ถ้ามี)
        $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();
        if ($notification) {
            $notification->update(['status' => 'confirmed']);
        }

        app(PaymentService::class)->completePayment($model);

        // ส่ง FCM push ให้แอพอัพเดทสถานะทันที (เหมือน xmanstudio)
        try {
            $this->fcmService->notifyTransactionApproved($model);
        } catch (\Exception $e) {
            Log::warning('FCM push for transaction_approved failed', ['error' => $e->getMessage()]);
        }

        Log::info('SMS Payment: อนุมัติ order จากอุปกรณ์', [
            'transaction_id' => $model->transaction_id,
            'device_id' => $device->device_id,
            'store_id' => $device->store_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order approved successfully',
            'data' => ['transaction_id' => $model->transaction_id, 'status' => 'completed'],
        ]);
    }

    /**
     * ค้นหา order จาก identifier (numeric ID หรือ bill_reference string)
     *
     * Prefix routing:
     * - PRE-, SEL-, TXN-, TAROT- → PaymentTransaction (transaction_id)
     * - FTU-, FR- → FortuneReading (bill_reference)
     * - Numeric → PaymentTransaction::find() / legacy virtual ID (10M+)
     *
     * @param mixed $identifier
     * @return array{model: Model, type: string}|null
     */
    private function resolveOrderByIdentifier($identifier): ?array
    {
        // Numeric ID → legacy compat
        if (is_numeric($identifier)) {
            $txn = PaymentTransaction::find($identifier);
            if ($txn) {
                return ['model' => $txn, 'type' => 'transaction'];
            }

            // Legacy virtual ID compat (10M+ offset)
            $id = (int) $identifier;
            if ($id > 10_000_000) {
                $fortune = FortuneReading::find($id - 10_000_000);
                if ($fortune) {
                    return ['model' => $fortune, 'type' => 'fortune'];
                }
            }

            return null;
        }

        $identifier = (string) $identifier;

        // PaymentTransaction prefixes
        if (str_starts_with($identifier, 'PRE-')
            || str_starts_with($identifier, 'SEL-')
            || str_starts_with($identifier, 'TXN-')
            || str_starts_with($identifier, 'TAROT-')
        ) {
            $txn = PaymentTransaction::where('transaction_id', $identifier)->first();

            return $txn ? ['model' => $txn, 'type' => 'transaction'] : null;
        }

        // FortuneReading prefixes
        if (str_starts_with($identifier, 'FTU-') || str_starts_with($identifier, 'FR-')) {
            $fortune = FortuneReading::where('bill_reference', $identifier)->first();

            return $fortune ? ['model' => $fortune, 'type' => 'fortune'] : null;
        }

        // Fallback: ลองค้นทั้งสองตาราง
        $txn = PaymentTransaction::where('transaction_id', $identifier)->first();
        if ($txn) {
            return ['model' => $txn, 'type' => 'transaction'];
        }

        $fortune = FortuneReading::where('bill_reference', $identifier)->first();

        return $fortune ? ['model' => $fortune, 'type' => 'fortune'] : null;
    }

    /**
     * ปฏิเสธ order
     *
     * รองรับทั้ง numeric ID (legacy) และ bill_reference string (ใหม่)
     *
     * POST /api/v1/sms-payment/orders/{identifier}/reject
     *
     * @param Request $request
     * @param mixed $identifier numeric ID หรือ bill_reference string
     * @return JsonResponse
     */
    public function rejectOrder(Request $request, $identifier): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $resolved = $this->resolveOrderByIdentifier($identifier);
        if (! $resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found: ' . $identifier,
            ], 404);
        }

        $model = $resolved['model'];
        $type = $resolved['type'];
        $reason = $request->input('reason', 'Rejected via SMS Checker');

        // === FortuneReading: reject ===
        if ($type === 'fortune') {
            /** @var FortuneReading $model */

            // FortuneReading ไม่มี store_id → เฉพาะ admin device เท่านั้น
            if (! $this->deviceCanAccessFortuneReading($device)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admin devices can manage fortune reading bills',
                ], 403);
            }

            if ($model->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fortune reading already paid, cannot reject',
                ], 422);
            }

            $model->update([
                'conversation_status' => 'cancelled',
                'notes' => $reason,
            ]);

            // ยกเลิก UniquePaymentAmount เพื่อปลดปล่อย suffix ให้ใช้ซ้ำได้
            if ($model->unique_payment_amount_id) {
                $uniqueAmount = UniquePaymentAmount::find($model->unique_payment_amount_id);
                if ($uniqueAmount && in_array($uniqueAmount->status, ['reserved', 'expired'])) {
                    $uniqueAmount->cancel();
                }
            }

            Log::info('SMS Payment: ปฏิเสธบิลดูดวงจากอุปกรณ์', [
                'fortune_reading_id' => $model->id,
                'bill_reference' => $model->bill_reference,
                'device_id' => $device->device_id,
                'reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fortune reading rejected',
                'data' => ['bill_reference' => $model->bill_reference, 'status' => 'cancelled'],
            ]);
        }

        // === PaymentTransaction: reject ===
        /** @var PaymentTransaction $model */

        // Multi-Store (Strict): ตรวจสอบสิทธิ์
        if (! $this->deviceCanAccessTransaction($device, $model)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this transaction',
            ], 403);
        }

        if (! in_array($model->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending (current: ' . $model->status . ')',
            ], 422);
        }

        $model->markAsFailed($reason);

        // ยกเลิก UniquePaymentAmount เพื่อปลดปล่อย suffix
        $uniqueAmount = UniquePaymentAmount::where('transaction_id', $model->id)
            ->whereIn('status', ['reserved', 'expired'])
            ->first();
        if ($uniqueAmount) {
            $uniqueAmount->cancel();
        }

        // ยกเลิก SMS notification ที่เชื่อมโยง (ถ้ามี)
        $matchedNotification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();
        if ($matchedNotification) {
            $matchedNotification->update(['status' => 'rejected']);
        }

        // ยกเลิก Order ที่เชื่อมโยง (ถ้ายัง pending)
        if ($model->order_id && $model->order && $model->order->status === 'pending') {
            $model->order->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'cancellation_reason' => 'ปฏิเสธโดย SMS Checker: ' . $reason,
            ]);
        }

        // ส่ง FCM push ให้แอพอัพเดทสถานะทันที
        try {
            $this->fcmService->notifyTransactionRejected($model);
        } catch (\Exception $e) {
            Log::warning('FCM push for transaction_rejected failed', ['error' => $e->getMessage()]);
        }

        Log::info('SMS Payment: ปฏิเสธ order จากอุปกรณ์', [
            'transaction_id' => $model->transaction_id,
            'device_id' => $device->device_id,
            'reason' => $reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order rejected',
            'data' => ['transaction_id' => $model->transaction_id, 'status' => 'failed'],
        ]);
    }

    /**
     * อนุมัติหลาย orders พร้อมกัน
     *
     * รองรับทั้ง numeric ID (legacy) และ bill_reference string (ใหม่)
     *
     * POST /api/v1/sms-payment/orders/bulk-approve
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkApproveOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => ['required'], // รองรับทั้ง integer และ string (bill_reference)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $identifiers = $request->input('ids');
        $approved = 0;
        $failed = 0;

        $paymentService = app(PaymentService::class);
        foreach ($identifiers as $identifier) {
            $resolved = $this->resolveOrderByIdentifier($identifier);
            if (! $resolved) {
                $failed++;
                continue;
            }

            $model = $resolved['model'];
            $type = $resolved['type'];

            if ($type === 'fortune') {
                /** @var FortuneReading $model */
                // FortuneReading → เฉพาะ admin device
                if (! $this->deviceCanAccessFortuneReading($device)) {
                    $failed++;
                    continue;
                }
                if (! $model->is_paid) {
                    $model->confirmPayment();
                    $approved++;
                } else {
                    $failed++; // already paid
                }
            } else {
                /** @var PaymentTransaction $model */
                // Multi-Store: ตรวจสอบสิทธิ์ device
                if (! $this->deviceCanAccessTransaction($device, $model)) {
                    $failed++;
                    continue;
                }
                if (in_array($model->status, ['pending', 'processing'])) {
                    // Mark UniquePaymentAmount as used
                    $uniqueAmount = UniquePaymentAmount::where('transaction_id', $model->id)
                        ->whereIn('status', ['reserved', 'expired'])
                        ->first();
                    if ($uniqueAmount) {
                        $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                    }

                    // Update SMS notification status
                    $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();
                    if ($notification) {
                        $notification->update(['status' => 'confirmed']);
                    }

                    $paymentService->completePayment($model);
                    $approved++;
                } else {
                    $failed++;
                }
            }
        }

        Log::info('SMS Payment: bulk approve จากอุปกรณ์', [
            'device_id' => $device->device_id,
            'approved' => $approved,
            'failed' => $failed,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Approved {$approved} orders" . ($failed > 0 ? ", {$failed} skipped" : ''),
            'data' => ['approved' => $approved, 'failed' => $failed],
        ]);
    }

    /**
     * Incremental sync - ดึง orders ที่เปลี่ยนแปลงตั้งแต่ version ที่กำหนด
     *
     * GET /api/v1/sms-payment/orders/sync
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // รองรับทั้ง since_version (Android app) และ since (legacy)
        $sinceVersion = $request->input('since_version') ?? $request->input('since') ?? 0;
        $query = PaymentTransaction::query();

        // === Multi-Store Filtering (Strict Mode) ===
        $this->applyStoreFilter($query, $device);

        if ($sinceVersion > 0) {
            // ดึง orders ที่อัพเดทหลังจาก timestamp ที่กำหนด (milliseconds)
            $query->where('updated_at', '>', date('Y-m-d H:i:s', $sinceVersion / 1000));
        }

        $transactions = $query->orderBy('updated_at', 'desc')
            ->limit($request->input('limit', 100))
            ->get();

        // แปลง PaymentTransaction → RemoteOrderApproval format สำหรับ Android app
        $orders = $transactions->map(function ($txn) {
            return $this->transformToOrderApproval($txn);
        });

        // === รวมบิลดูดวง (FortuneReading) ที่เปลี่ยนแปลง ===
        // FortuneReading ไม่มี store_id → เฉพาะ admin device เท่านั้น
        // ใช้ bill_reference แทน unique_payment_amount_id เพื่อรองรับบิลเก่า
        $allOrders = $orders;
        if ($this->deviceCanAccessFortuneReading($device)) {
            $fortuneQuery = FortuneReading::query()
                ->whereNotNull('bill_reference')
                ->whereIn('conversation_status', [
                    FortuneReading::STATUS_PENDING_PAYMENT,
                    FortuneReading::STATUS_PAID,
                    FortuneReading::STATUS_COMPLETED,
                ]);

            if ($sinceVersion > 0) {
                $fortuneQuery->where('updated_at', '>', date('Y-m-d H:i:s', $sinceVersion / 1000));
            }

            $fortuneReadings = $fortuneQuery->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get();

            $fortuneOrders = $fortuneReadings->map(function ($reading) {
                return $this->transformFortuneReadingToOrderApproval($reading);
            });

            $allOrders = $orders->concat($fortuneOrders)
                ->sortByDesc('updated_at')
                ->values();
        }

        $latestVersion = intval(round(microtime(true) * 1000));

        // อัพเดท last_active_at ของอุปกรณ์
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $allOrders,
                'latest_version' => $latestVersion,
            ],
        ]);
    }

    /**
     * ค้นหา order ที่ตรงกับยอดเงินที่ได้รับจาก SMS
     *
     * Android app เรียก endpoint นี้เมื่อได้รับ SMS ยอดเงินเข้า
     * แทนที่จะดึง orders ทั้งหมดมาแสดง จะดึงเฉพาะที่ยอดตรงกัน
     *
     * GET /api/v1/sms-payment/orders/match?amount=500.37
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function matchOrderByAmount(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $amount = $request->input('amount');
        if (!$amount || !is_numeric($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Amount is required and must be numeric',
            ], 400);
        }

        $amount = (float) $amount;
        $graceMinutes = (int) config('smschecker.orphan.match_window_minutes', 60);
        $deviceStoreId = $this->resolveDeviceStoreId($device);

        // =====================================================================
        // ค้นหา PaymentTransaction (เหมือน xmanstudio)
        // =====================================================================

        // Query 1: UniquePaymentAmount active/used → PaymentTransaction pending
        $transaction = PaymentTransaction::with(['order'])
            ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                $q->where('unique_amount', $amount)
                    ->whereIn('status', ['reserved', 'used']);
            })
            ->whereIn('status', ['pending', 'processing'])
            ->where('store_id', $deviceStoreId)
            ->orderBy('created_at', 'desc')
            ->first();

        // Fallback 1: /notify → attemptMatch() ทำไปแล้ว (status='completed')
        // → หา transaction ที่ match แล้วเพื่อ return ให้ Android รู้
        $alreadyMatched = false;
        if (! $transaction) {
            $transaction = PaymentTransaction::with(['order'])
                ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                    $q->where('unique_amount', $amount)
                        ->where('status', 'used');
                })
                ->where('store_id', $deviceStoreId)
                ->where('created_at', '>=', now()->subHours(1))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($transaction) {
                $alreadyMatched = true;
            }
        }

        // Fallback 2: UniquePaymentAmount หมดอายุแล้ว (ภายใน grace period)
        // กรณี SMS มาช้า / cleanup ทำงานแล้ว แต่ transaction ยัง pending/failed
        if (! $transaction) {
            $transaction = PaymentTransaction::with(['order'])
                ->whereHas('uniquePaymentAmount', function ($q) use ($amount, $graceMinutes) {
                    $q->where('unique_amount', $amount)
                        ->whereIn('status', ['expired', 'reserved'])
                        ->where('expires_at', '>', now()->subMinutes($graceMinutes));
                })
                ->whereIn('status', ['pending', 'processing', 'failed'])
                ->where('store_id', $deviceStoreId)
                ->orderBy('created_at', 'desc')
                ->first();

            // Recovery: ถ้า transaction ถูก markAsFailed โดย cleanup → กู้คืนเป็น pending
            if ($transaction && $transaction->status === 'failed') {
                $transaction->update(['status' => 'pending']);
                Log::info('SMS Payment: Recovered expired transaction for match', [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                ]);
            }
        }

        // Fallback 3: ค้นหาด้วย amount ตรงๆ (กรณี UniquePaymentAmount record สูญหาย)
        if (! $transaction) {
            $query = PaymentTransaction::query()
                ->whereIn('status', ['pending', 'processing'])
                ->whereIn('payment_method', ['promptpay', 'bank_transfer'])
                ->where('amount', $amount)
                ->where('store_id', $deviceStoreId);

            $transaction = $query->orderBy('created_at', 'desc')->first();
        }

        // === Auto-approve PaymentTransaction เมื่อจับคู่ได้ ===
        if ($transaction && ! $alreadyMatched) {
            $autoConfirm = config('smschecker.auto_confirm_matched', true);
            if ($autoConfirm && in_array($transaction->status, ['pending', 'processing'])) {
                try {
                    // Mark UniquePaymentAmount as used
                    $uniqueAmount = UniquePaymentAmount::where('transaction_id', $transaction->id)
                        ->whereIn('status', ['reserved', 'expired'])
                        ->first();
                    if ($uniqueAmount) {
                        $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                    }

                    app(PaymentService::class)->completePayment($transaction);
                    $transaction = $transaction->fresh();

                    // ส่ง FCM push ให้แอพอัพเดทสถานะทันที
                    try {
                        $this->fcmService->notifyTransactionApproved($transaction, $device);
                    } catch (\Exception $fcmEx) {
                        Log::warning('FCM push for auto-approved match failed', ['error' => $fcmEx->getMessage()]);
                    }

                    Log::info('SMS Payment: Auto-approved on match', [
                        'device_id' => $device->device_id,
                        'amount' => $amount,
                        'transaction_id' => $transaction->id,
                        'store_id' => $deviceStoreId,
                    ]);
                } catch (\Exception $e) {
                    Log::error('SMS Payment: Auto-approve failed on match', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // =====================================================================
        // ค้นหา FortuneReading (เฉพาะ admin device)
        // =====================================================================
        if (! $transaction && $this->deviceCanAccessFortuneReading($device)) {
            $fortuneReading = $this->matchFortuneReadingByAmount($amount, $graceMinutes);

            if ($fortuneReading) {
                // Recovery: ถ้า cleanup ปิดไปแล้ว → กู้คืนเป็น pending_payment
                if (! $fortuneReading->is_paid && $fortuneReading->conversation_status !== FortuneReading::STATUS_PENDING_PAYMENT) {
                    $fortuneReading->update(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
                    Log::info('SMS Payment: Recovered fortune reading for match', [
                        'fortune_reading_id' => $fortuneReading->id,
                    ]);
                }

                // Auto-approve fortune reading (เหมือน xmanstudio auto-approve topup)
                $autoConfirm = config('smschecker.auto_confirm_matched', true);
                if ($autoConfirm && ! $fortuneReading->is_paid) {
                    try {
                        $fortuneReading->confirmPayment();
                        $fortuneReading = $fortuneReading->fresh();

                        Log::info('SMS Payment: Auto-approved fortune reading on match', [
                            'device_id' => $device->device_id,
                            'amount' => $amount,
                            'fortune_reading_id' => $fortuneReading->id,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('SMS Payment: Auto-approve fortune reading failed', [
                            'fortune_reading_id' => $fortuneReading->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $orderData = $this->transformFortuneReadingToOrderApproval($fortuneReading);
                $device->update(['last_active_at' => now()]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'matched' => true,
                        'order' => $orderData,
                        'message' => 'Found matching fortune reading',
                    ],
                ]);
            }
        }

        // =====================================================================
        // ไม่พบ order ใดๆ
        // =====================================================================
        if (! $transaction) {
            $device->update(['last_active_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => [
                    'matched' => false,
                    'order' => null,
                    'message' => 'No pending order found with amount ' . number_format($amount, 2),
                ],
            ]);
        }

        // แปลงเป็น format ที่ Android เข้าใจ
        $orderData = $this->transformToOrderApproval($transaction);
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'matched' => true,
                'order' => $orderData,
                'message' => $alreadyMatched ? 'Order already matched' : 'Found matching order',
            ],
        ]);
    }

    /**
     * ค้นหา FortuneReading จากยอดเงิน (3 ระดับ)
     *
     * @param float $amount
     * @param int $graceMinutes
     * @return FortuneReading|null
     */
    private function matchFortuneReadingByAmount(float $amount, int $graceMinutes): ?FortuneReading
    {
        // 1) Active unique amount → ยังรอชำระ (pending_payment)
        $fortuneReading = FortuneReading::findByUniqueAmount($amount);

        // 2) Grace period — unique amount หมดอายุแล้ว แต่ FortuneReading ยังรอชำระ/ถูก cleanup ปิดไปแล้ว
        if (! $fortuneReading) {
            $uniquePayment = UniquePaymentAmount::where('unique_amount', $amount)
                ->where('transaction_type', 'fortune_reading')
                ->whereIn('status', ['reserved', 'expired'])
                ->where('expires_at', '>', now()->subMinutes($graceMinutes))
                ->orderBy('expires_at', 'desc')
                ->first();

            if ($uniquePayment) {
                $fortuneReading = FortuneReading::where('unique_payment_amount_id', $uniquePayment->id)
                    ->where('is_paid', false)
                    ->whereIn('conversation_status', [
                        FortuneReading::STATUS_PENDING_PAYMENT,
                        FortuneReading::STATUS_COMPLETED,
                    ])
                    ->first();
            }
        }

        // 3) Already handled by /notify → status=paid/completed
        if (! $fortuneReading) {
            $uniquePayment = UniquePaymentAmount::where('unique_amount', $amount)
                ->where('transaction_type', 'fortune_reading')
                ->where('created_at', '>=', now()->subHours(1))
                ->first();

            if ($uniquePayment) {
                $fortuneReading = FortuneReading::where('unique_payment_amount_id', $uniquePayment->id)
                    ->whereIn('conversation_status', [
                        FortuneReading::STATUS_PAID,
                        FortuneReading::STATUS_COMPLETED,
                    ])
                    ->first();
            }
        }

        return $fortuneReading;
    }

    /**
     * ดึงตั้งค่าอุปกรณ์
     *
     * GET /api/v1/sms-payment/device-settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDeviceSettings(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'approval_mode' => $device->getApprovalMode(),
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'status' => $device->status,
                'supported_banks' => array_keys(config('smschecker.supported_banks', [])),
                'auto_confirm' => config('smschecker.auto_confirm_matched', true),
                'rate_limit_per_minute' => config('smschecker.rate_limit_per_minute', 30),
            ],
        ]);
    }

    /**
     * อัพเดทตั้งค่าอุปกรณ์
     *
     * PUT /api/v1/sms-payment/device-settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDeviceSettings(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'device_name' => 'sometimes|string|max:255',
            'approval_mode' => 'sometimes|in:auto,manual,smart',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [];
        if ($request->has('device_name')) {
            $updateData['device_name'] = $request->input('device_name');
        }
        if ($request->has('approval_mode')) {
            $updateData['approval_mode'] = $request->input('approval_mode');
        }

        if (!empty($updateData)) {
            $device->update($updateData);

            // ส่ง FCM push ให้แอพอัพเดทตั้งค่าทันที (เหมือน xmanstudio)
            if (isset($updateData['approval_mode'])) {
                try {
                    $this->fcmService->notifySettingsChanged($device, 'approval_mode', $updateData['approval_mode']);
                } catch (\Exception $e) {
                    Log::warning('FCM push for settings_changed failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated',
        ]);
    }

    /**
     * สถิติ dashboard สำหรับ Android App
     *
     * GET /api/v1/sms-payment/dashboard-stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $days = (int) $request->input('days', 7);
        $since = now()->subDays($days)->startOfDay();

        // === Multi-Store Filtering (Strict Mode) ===
        // ทุก device เห็นเฉพาะ stats ของ store ตัวเองเท่านั้น
        $baseQuery = function () use ($device) {
            $query = PaymentTransaction::query();
            $this->applyStoreFilter($query, $device);
            return $query;
        };

        // นับ orders ตามสถานะ (ใช้ PaymentTransaction เป็น base)
        $totalOrders = $baseQuery()->where('created_at', '>=', $since)->count();
        $completedOrders = $baseQuery()->where('created_at', '>=', $since)
            ->where('status', 'completed')->count();
        $pendingOrders = $baseQuery()->where('status', 'pending')->count();
        $failedOrders = $baseQuery()->where('created_at', '>=', $since)
            ->where('status', 'failed')->count();

        // แบ่ง auto approved (matched by SMS) vs manually approved
        $autoApproved = SmsPaymentNotification::where('device_id', $device->device_id)
            ->where('status', 'matched')
            ->where('created_at', '>=', $since)
            ->count();
        $manuallyApproved = max(0, $completedOrders - $autoApproved);

        // ยอดรวม
        $totalAmount = (float) $baseQuery()->where('created_at', '>=', $since)
            ->where('status', 'completed')
            ->sum('amount');

        // Daily breakdown
        $dailyBreakdown = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayStart = now()->subDays($i)->startOfDay();
            $dayEnd = now()->subDays($i)->endOfDay();

            $dayCount = $baseQuery()->whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $dayApproved = $baseQuery()->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'completed')->count();
            $dayRejected = $baseQuery()->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'failed')->count();
            $dayAmount = (float) $baseQuery()->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'completed')->sum('amount');

            $dailyBreakdown[] = [
                'date' => $date,
                'count' => $dayCount,
                'approved' => $dayApproved,
                'rejected' => $dayRejected,
                'amount' => $dayAmount,
            ];
        }

        // Response format ตรงกับ Android app RemoteDashboardStats model
        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'auto_approved' => $autoApproved,
                'manually_approved' => $manuallyApproved,
                'pending_review' => $pendingOrders,
                'rejected' => $failedOrders,
                'total_amount' => $totalAmount,
                'daily_breakdown' => $dailyBreakdown,
            ],
        ]);
    }

    // =================================================================
    // Endpoints เพิ่มเติมเพื่อให้ Android App เชื่อมต่อได้สมบูรณ์
    // =================================================================

    /**
     * รับ encrypted action (approve/reject) จาก Android App
     *
     * ใช้ security flow เหมือน notify() (signature, nonce, timestamp, decrypt)
     * Payload: { action, order_identifier, amount, bank, sms_reference, device_id, reason, nonce }
     *
     * POST /api/v1/sms-payment/notify-action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function notifyAction(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ตรวจสอบ security headers ที่จำเป็น
        $signature = $request->header('X-Signature');
        $nonce = $request->header('X-Nonce');
        $timestamp = $request->header('X-Timestamp');

        if (! $signature || ! $nonce || ! $timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required security headers',
            ], 400);
        }

        // ตรวจสอบความสดของ timestamp (ภายใน 5 นาที)
        $requestTime = intval($timestamp);
        $currentTime = intval(round(microtime(true) * 1000));
        $tolerance = config('smschecker.timestamp_tolerance', 300) * 1000;

        if (abs($currentTime - $requestTime) > $tolerance) {
            return response()->json([
                'success' => false,
                'message' => 'Request timestamp expired',
            ], 400);
        }

        // รับ encrypted data
        $encryptedData = $request->input('data');
        if (! $encryptedData) {
            return response()->json([
                'success' => false,
                'message' => 'No payload data',
            ], 400);
        }

        // ตรวจสอบ HMAC signature
        $signatureData = $encryptedData . $nonce . $timestamp;
        if (! $this->smsPaymentService->verifySignature($signatureData, $signature, $device->secret_key)) {
            Log::warning('SMS Payment notifyAction: ลายเซ็นไม่ถูกต้อง', [
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
        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decrypt payload',
            ], 400);
        }

        // ตรวจสอบ payload fields
        $validator = Validator::make($payload, [
            'action' => 'required|in:approve,reject',
            'order_identifier' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'bank' => 'nullable|string|max:20',
            'sms_reference' => 'nullable|string|max:100',
            'device_id' => 'required|string',
            'reason' => 'nullable|string|max:500',
            'nonce' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload data',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ตรวจสอบ duplicate nonce (ป้องกัน replay attack)
        $existingNonce = DB::table('sms_payment_nonces')
            ->where('nonce', $payload['nonce'])
            ->exists();

        if ($existingNonce) {
            Log::warning('SMS Payment notifyAction: Duplicate nonce', [
                'nonce' => $payload['nonce'],
                'device_id' => $device->device_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Duplicate request (nonce already used)',
            ], 409);
        }

        // บันทึก nonce
        DB::table('sms_payment_nonces')->insert([
            'nonce' => $payload['nonce'],
            'device_id' => $device->device_id,
            'used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ค้นหา order จาก identifier (รองรับทั้ง numeric ID, transaction_id, bill_reference)
        $identifier = $payload['order_identifier'];
        $resolved = $this->resolveOrderByIdentifier($identifier);

        if (! $resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found: ' . $identifier,
            ], 404);
        }

        $model = $resolved['model'];
        $type = $resolved['type'];
        $action = $payload['action'];

        // === FortuneReading ===
        if ($type === 'fortune') {
            /** @var FortuneReading $model */
            if (! $this->deviceCanAccessFortuneReading($device)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admin devices can manage fortune reading bills',
                ], 403);
            }

            return $action === 'approve'
                ? $this->executeFortuneApproveAction($payload, $model, $device, $request->ip())
                : $this->executeFortuneRejectAction($payload, $model, $device, $request->ip());
        }

        // === PaymentTransaction ===
        /** @var PaymentTransaction $model */
        if (! $this->deviceCanAccessTransaction($device, $model)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this transaction',
            ], 403);
        }

        return $action === 'approve'
            ? $this->executeTransactionApproveAction($payload, $model, $device, $request->ip())
            : $this->executeTransactionRejectAction($payload, $model, $device, $request->ip());
    }

    /**
     * Execute encrypted approve action on a PaymentTransaction
     */
    private function executeTransactionApproveAction(array $payload, PaymentTransaction $txn, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        // Idempotent: ถ้า completed แล้ว → return success
        if ($txn->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Order already approved',
                'data' => ['order' => $this->transformToOrderApproval($txn)],
            ]);
        }

        if (! in_array($txn->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction cannot be approved in current status: ' . $txn->status,
            ], 422);
        }

        // Mark UniquePaymentAmount as used (เหมือน approveOrder)
        $uniqueAmount = UniquePaymentAmount::where('transaction_id', $txn->id)
            ->whereIn('status', ['reserved', 'expired'])
            ->first();
        if ($uniqueAmount) {
            $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
        }

        // Update SMS notification status to confirmed (ถ้ามี)
        $notification = SmsPaymentNotification::where('matched_transaction_id', $txn->id)->first();
        if ($notification) {
            $notification->update(['status' => 'confirmed']);
        }

        app(PaymentService::class)->completePayment($txn);

        // ส่ง FCM push ให้แอพอัพเดทสถานะทันที
        try {
            $this->fcmService->notifyTransactionApproved($txn, $device);
        } catch (\Exception $e) {
            Log::warning('FCM push for encrypted approve failed', ['error' => $e->getMessage()]);
        }

        // อัพเดท device activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $txn = $txn->fresh();

        Log::info('SMS Payment: Transaction approved via encrypted action', [
            'transaction_id' => $txn->transaction_id,
            'amount' => $payload['amount'],
            'bank' => $payload['bank'] ?? 'N/A',
            'device_id' => $device->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order approved successfully',
            'data' => ['order' => $this->transformToOrderApproval($txn)],
        ]);
    }

    /**
     * Execute encrypted reject action on a PaymentTransaction
     */
    private function executeTransactionRejectAction(array $payload, PaymentTransaction $txn, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        $reason = $payload['reason'] ?? 'Rejected via SMS Checker';

        if (! in_array($txn->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction cannot be rejected in current status: ' . $txn->status,
            ], 422);
        }

        $txn->markAsFailed($reason);

        // ยกเลิก UniquePaymentAmount
        $uniqueAmount = UniquePaymentAmount::where('transaction_id', $txn->id)
            ->whereIn('status', ['reserved', 'expired'])
            ->first();
        if ($uniqueAmount) {
            $uniqueAmount->cancel();
        }

        // ยกเลิก SMS notification ที่เชื่อมโยง
        $matchedNotification = SmsPaymentNotification::where('matched_transaction_id', $txn->id)->first();
        if ($matchedNotification) {
            $matchedNotification->update(['status' => 'rejected']);
        }

        // ยกเลิก Order ที่เชื่อมโยง
        if ($txn->order_id && $txn->order && $txn->order->status === 'pending') {
            $txn->order->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'cancellation_reason' => 'ปฏิเสธโดย SMS Checker: ' . $reason,
            ]);
        }

        // ส่ง FCM push ให้แอพอัพเดทสถานะทันที
        try {
            $this->fcmService->notifyTransactionRejected($txn, $device);
        } catch (\Exception $e) {
            Log::warning('FCM push for encrypted reject failed', ['error' => $e->getMessage()]);
        }

        // อัพเดท device activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $txn = $txn->fresh();

        Log::info('SMS Payment: Transaction rejected via encrypted action', [
            'transaction_id' => $txn->transaction_id,
            'reason' => $reason,
            'device_id' => $device->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order rejected',
            'data' => ['order' => $this->transformToOrderApproval($txn)],
        ]);
    }

    /**
     * Execute encrypted approve action on a FortuneReading
     */
    private function executeFortuneApproveAction(array $payload, FortuneReading $reading, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        // Idempotent: ถ้า paid แล้ว → return success
        if ($reading->is_paid) {
            return response()->json([
                'success' => true,
                'message' => 'Fortune reading already paid',
                'data' => ['order' => $this->transformFortuneReadingToOrderApproval($reading)],
            ]);
        }

        // ค้นหา SMS notification ที่ตรงกับบิลนี้ (จับคู่ด้วย amount + สถานะ matched)
        $notification = SmsPaymentNotification::where('matched_transaction_id', $reading->id)
            ->first();

        // ถ้าไม่พบจาก matched_transaction_id → ลองหาจากยอดเงินที่ตรงกัน
        if (!$notification && !empty($payload['amount'])) {
            $notification = SmsPaymentNotification::where('amount', $payload['amount'])
                ->where('type', 'credit')
                ->whereIn('status', ['matched', 'pending'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $reading->confirmPayment($notification);

        // อัพเดท notification สถานะเป็น confirmed
        if ($notification) {
            $notification->update([
                'status' => 'confirmed',
                'matched_transaction_id' => $reading->id,
            ]);
        }

        // อัพเดท device activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $reading = $reading->fresh();

        Log::info('SMS Payment: Fortune reading approved via encrypted action', [
            'fortune_reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'amount' => $payload['amount'],
            'device_id' => $device->device_id,
            'sms_notification_id' => $notification?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fortune reading approved successfully',
            'data' => ['order' => $this->transformFortuneReadingToOrderApproval($reading)],
        ]);
    }

    /**
     * Execute encrypted reject action on a FortuneReading
     */
    private function executeFortuneRejectAction(array $payload, FortuneReading $reading, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        $reason = $payload['reason'] ?? 'Rejected via SMS Checker';

        if ($reading->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Fortune reading already paid, cannot reject',
            ], 422);
        }

        $reading->update([
            'conversation_status' => 'cancelled',
            'notes' => $reason,
        ]);

        // ยกเลิก UniquePaymentAmount
        if ($reading->unique_payment_amount_id) {
            $uniqueAmount = UniquePaymentAmount::find($reading->unique_payment_amount_id);
            if ($uniqueAmount && in_array($uniqueAmount->status, ['reserved', 'expired'])) {
                $uniqueAmount->cancel();
            }
        }

        // อัพเดท device activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $reading = $reading->fresh();

        Log::info('SMS Payment: Fortune reading rejected via encrypted action', [
            'fortune_reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'reason' => $reason,
            'device_id' => $device->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fortune reading rejected',
            'data' => ['order' => $this->transformFortuneReadingToOrderApproval($reading)],
        ]);
    }

    /**
     * Legacy sync — redirect ไป syncOrders()
     *
     * Android app เวอร์ชันเก่าเรียก GET /sync แทน /orders/sync
     *
     * GET /api/v1/sms-payment/sync
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        return $this->syncOrders($request);
    }

    /**
     * ดึง sync version ปัจจุบัน
     *
     * Android app ใช้เช็คว่าข้อมูลฝั่ง server มีการเปลี่ยนแปลงหรือไม่
     * ก่อนจะเรียก syncOrders() เพื่อดึงข้อมูลจริง
     *
     * GET /api/v1/sms-payment/sync-version
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSyncVersion(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $this->getSyncVersionNumber(),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * คำนวณ sync version number จาก last update ของ PaymentTransaction + FortuneReading
     *
     * @return int Unix timestamp ของ record ที่ update ล่าสุด
     */
    private function getSyncVersionNumber(): int
    {
        return Cache::remember('sms_payment_sync_version', 60, function () {
            $lastTransaction = PaymentTransaction::orderBy('updated_at', 'desc')->first();
            $lastFortune = FortuneReading::whereNotNull('unique_payment_amount_id')
                ->orderBy('updated_at', 'desc')
                ->first();

            $timestamps = array_filter([
                $lastTransaction?->updated_at?->timestamp,
                $lastFortune?->updated_at?->timestamp,
            ]);

            return ! empty($timestamps) ? max($timestamps) : time();
        });
    }
}
