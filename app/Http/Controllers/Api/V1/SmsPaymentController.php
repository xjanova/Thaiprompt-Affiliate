<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Services\Payment\PaymentService;
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
            'cancelled' => 'rejected',
            default => 'pending_review',
        };

        // ดึง order details (ต้องมี amount เสมอ ไม่ว่าจะมี order หรือไม่)
        $orderDetails = [
            'order_number' => null,
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
            $orderDetails['order_number'] = $order->order_number ?? null;
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
            'synced_version' => $txn->updated_at ? intval($txn->updated_at->timestamp * 1000) : 0,
            'created_at' => $txn->created_at?->toIso8601String(),
            'updated_at' => $txn->updated_at?->toIso8601String(),
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

        // === Multi-Store Filtering ===
        // Admin devices (store_id = null) → เห็นทุก order
        // Seller devices → เห็นเฉพาะ order ที่มี store_id ตรงกับร้านตัวเอง
        if (!$device->isAdminDevice()) {
            $query->where('store_id', $device->store_id);
        }

        // กรองสถานะ (default: pending)
        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
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

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $orders,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * อนุมัติ order (ยืนยันการชำระเงิน)
     *
     * Android App เรียกเมื่อผู้ใช้กดอนุมัติ
     *
     * POST /api/v1/sms-payment/orders/{id}/approve
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approveOrder(Request $request, int $id): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $transaction = PaymentTransaction::find($id);
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // === Multi-Store: ตรวจสอบสิทธิ์ของ device ในการจัดการ transaction นี้ ===
        if (!$device->isAdminDevice() && $transaction->store_id !== $device->store_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this transaction',
            ], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending (current: ' . $transaction->status . ')',
            ], 422);
        }

        // ใช้ PaymentService เพื่อให้ Order ถูกอัพเดทและ downstream logic ทำงาน
        // (MLM Commission, Cashback, Revenue Distribution, ลดสต็อก)
        app(PaymentService::class)->completePayment($transaction);

        Log::info('SMS Payment: อนุมัติ order จากอุปกรณ์', [
            'transaction_id' => $transaction->id,
            'device_id' => $device->device_id,
            'store_id' => $device->store_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order approved successfully',
            'data' => ['transaction_id' => $transaction->id, 'status' => 'completed'],
        ]);
    }

    /**
     * ปฏิเสธ order
     *
     * POST /api/v1/sms-payment/orders/{id}/reject
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function rejectOrder(Request $request, int $id): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $transaction = PaymentTransaction::find($id);
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // === Multi-Store: ตรวจสอบสิทธิ์ ===
        if (!$device->isAdminDevice() && $transaction->store_id !== $device->store_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this transaction',
            ], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending (current: ' . $transaction->status . ')',
            ], 422);
        }

        $reason = $request->input('reason', 'Rejected via SMS Checker');
        $transaction->markAsFailed();

        Log::info('SMS Payment: ปฏิเสธ order จากอุปกรณ์', [
            'transaction_id' => $transaction->id,
            'device_id' => $device->device_id,
            'reason' => $reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order rejected',
            'data' => ['transaction_id' => $transaction->id, 'status' => 'failed'],
        ]);
    }

    /**
     * อนุมัติหลาย orders พร้อมกัน
     *
     * POST /api/v1/sms-payment/orders/bulk-approve
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkApproveOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (!$device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->input('ids');
        $approved = 0;
        $failed = 0;

        $paymentService = app(PaymentService::class);
        foreach ($ids as $id) {
            $transaction = PaymentTransaction::find($id);
            if ($transaction && $transaction->status === 'pending') {
                // ใช้ PaymentService เพื่อให้ Order ถูกอัพเดทและ downstream logic ทำงาน
                $paymentService->completePayment($transaction);
                $approved++;
            } else {
                $failed++;
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

        // === Multi-Store Filtering ===
        if (!$device->isAdminDevice()) {
            $query->where('store_id', $device->store_id);
        }

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

        $latestVersion = intval(round(microtime(true) * 1000));

        // อัพเดท last_active_at ของอุปกรณ์
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
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

        // ค้นหา PaymentTransaction ที่:
        // 1. status = pending หรือ processing
        // 2. amount ตรงกับที่ได้รับ (exact match สำหรับ unique decimal)
        // 3. payment_method = promptpay หรือ bank_transfer
        $query = PaymentTransaction::query()
            ->whereIn('status', ['pending', 'processing'])
            ->whereIn('payment_method', ['promptpay', 'bank_transfer'])
            ->where('amount', $amount);

        // === Multi-Store Filtering ===
        if (!$device->isAdminDevice()) {
            $query->where('store_id', $device->store_id);
        }

        // ดึง transaction ที่ตรงกัน (ควรมีแค่ 1 เพราะ unique amount)
        $transaction = $query->orderBy('created_at', 'desc')->first();

        if (!$transaction) {
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

        // อัพเดท last_active_at ของอุปกรณ์
        $device->update(['last_active_at' => now()]);

        Log::info('SMS Payment: Order matched by amount', [
            'device_id' => $device->device_id,
            'amount' => $amount,
            'transaction_id' => $transaction->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'matched' => true,
                'order' => $orderData,
                'message' => 'Found matching order',
            ],
        ]);
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

        if (!empty($updateData)) {
            $device->update($updateData);
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

        // === Multi-Store Filtering ===
        // สร้าง base query ที่ filter ตาม store ของ device
        $baseQuery = function () use ($device) {
            $q = PaymentTransaction::query();
            if (!$device->isAdminDevice()) {
                $q->where('store_id', $device->store_id);
            }
            return $q;
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
}
