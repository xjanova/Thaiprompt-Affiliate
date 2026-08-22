<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Models\VendorStore;
use App\Services\FcmNotificationService;
use App\Services\FortuneChannelManager;
use App\Services\Payment\PaymentService;
use App\Services\SmsPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyStoreFilter($query, SmsCheckerDevice $device)
    {
        if ($device->isAdminDevice()) {
            // Admin device → เห็นบิลของ Platform + บิลที่ยังไม่มี store (legacy)
            $platformStoreId = VendorStore::where('store_slug', VendorStore::PLATFORM_STORE_SLUG)->value('id');
            $query->where(function ($q) use ($platformStoreId) {
                $q->whereNull('store_id');
                if ($platformStoreId) {
                    $q->orWhere('store_id', $platformStoreId);
                }
            });
        } else {
            // Seller device → เห็นเฉพาะบิลของ store ตัวเอง
            $query->where('store_id', $device->store_id);
        }

        return $query;
    }

    /**
     * ตรวจสอบว่า device มีสิทธิ์เข้าถึงบิลดูดวงหรือไม่
     *
     * บิลดูดวงให้ทุก device เห็นได้ เพราะต้องรองรับ auto-approve เมื่อโอนตรงยอด
     */
    private function deviceCanAccessFortuneReading(SmsCheckerDevice $device): bool
    {
        return true;
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
    /**
     * Offset สำหรับ FortuneReading ID เพื่อไม่ให้ชนกับ PaymentTransaction ID
     * Android app ใช้ UNIQUE(remoteApprovalId, serverId) ในฐานข้อมูลท้องถิ่น
     * ถ้า FortuneReading.id = PaymentTransaction.id → ตัวหลังจะทับตัวแรก
     * เลยบวก offset 10,000,000 เพื่อแยก namespace
     */
    private const FORTUNE_READING_ID_OFFSET = 10000000;

    /**
     * 🧾 (2026-07-27) แคชผลค้นสลิปต่อ 1 request — reading_id => SlipVerificationLog|null
     *
     * transformFortuneReadingToOrderApproval ถูกเรียกทีละบิลใน orders()/syncOrders()
     * ถ้าค้น log ทีละบิล = N+1 query → preloadSlipLogsFor() ยิงครั้งเดียวแล้วเก็บที่นี่
     *
     * @var array<int, \App\Models\SlipVerificationLog|null>
     */
    private array $slipLogCache = [];

    /**
     * 🧾 (2026-07-27) โหลด "สลิปที่ตรวจผ่าน" ของหลายบิลในคิวรีเดียว (กัน N+1)
     *
     * @param  \Illuminate\Support\Collection<int, FortuneReading>  $readings
     */
    private function preloadSlipLogsFor($readings): void
    {
        $ids = collect($readings)->pluck('id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        try {
            // เอาแถวล่าสุดของแต่ละบิลที่ "ผ่าน + ยังมีไฟล์รูป" (รูป archive 30 วัน)
            $logs = \App\Models\SlipVerificationLog::query()
                ->whereIn('fortune_reading_id', $ids)
                ->whereNotNull('slip_image_path')
                ->where('decision', 'approve')
                ->orderBy('id', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::debug('preloadSlipLogsFor ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($ids as $id) {
            $this->slipLogCache[(int) $id] = null;
        }
        foreach ($logs as $log) {
            $rid = (int) $log->fortune_reading_id;
            // เรียง id DESC → แถวแรกที่เจอคือล่าสุด
            if (($this->slipLogCache[$rid] ?? null) === null) {
                $this->slipLogCache[$rid] = $log;
            }
        }
    }

    /**
     * 🧾 (2026-07-27) สลิปที่ทำให้บิลนี้ผ่าน (ล่าสุด) — คืน null ถ้าไม่มี/รูปถูก purge แล้ว
     */
    private function slipLogFor(FortuneReading $reading): ?\App\Models\SlipVerificationLog
    {
        $rid = (int) $reading->id;
        if (array_key_exists($rid, $this->slipLogCache)) {
            return $this->slipLogCache[$rid];
        }

        try {
            $log = \App\Models\SlipVerificationLog::query()
                ->where('fortune_reading_id', $rid)
                ->whereNotNull('slip_image_path')
                ->where('decision', 'approve')
                ->orderBy('id', 'desc')
                ->first();
        } catch (\Throwable $e) {
            Log::debug('slipLogFor ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
            $log = null;
        }

        return $this->slipLogCache[$rid] = $log;
    }

    /**
     * 🏬 (2026-08-15) สาขาที่บิลใบนี้เกิด — ส่งให้แอพ SMS Checker แสดงว่าเป็นของเพจไหน
     *
     * memo ไว้ในตัวแปร static เพราะ orders() วนบิลทีละใบ
     * ถ้า query ทุกใบจะกลายเป็น N+1 บนหน้าที่แอพ poll ทุก 30 วินาที
     *
     * @return array{id: int, code: string, name: string, is_default: bool}|null
     */
    private function branchInfoFor(FortuneReading $reading): ?array
    {
        $pageId = $reading->fortune_page_id ?? null;

        if (empty($pageId)) {
            return null; // บิลเก่าก่อนมีระบบสาขา / งานคอนโซล
        }

        static $memo = [];

        if (array_key_exists($pageId, $memo)) {
            return $memo[$pageId];
        }

        try {
            $page = \App\Models\FortunePage::find($pageId);

            $memo[$pageId] = $page === null ? null : [
                'id' => (int) $page->id,
                'code' => (string) $page->code,
                'name' => (string) ($page->brand_name ?: $page->name),
                'is_default' => (bool) $page->is_default,
            ];
        } catch (\Throwable $e) {
            // อ่านสาขาไม่ได้ต้องไม่ทำให้รายการบิลทั้งหน้าพัง — แอพขาดแค่ป้ายสาขา
            $memo[$pageId] = null;
        }

        return $memo[$pageId];
    }

    private function transformFortuneReadingToOrderApproval(FortuneReading $reading): array
    {
        // แปลง conversation_status → approval_status ที่ Android เข้าใจ
        // ถ้า completed แต่ไม่ได้จ่ายเงิน = ลูกค้ายกเลิก → ส่ง 'cancelled'
        // 🔮 Celtic Cross statuses (post-payment): PICKING / AWAITING_QUESTION / GENERATING / QA_PROMPT
        //    → ทุก status หลังจ่ายเงิน is_paid=true ให้ส่ง 'auto_approved' (บิลถูกตัดแล้ว)
        $approvalStatus = match (true) {
            $reading->conversation_status === FortuneReading::STATUS_PENDING_PAYMENT => 'pending_review',
            $reading->conversation_status === FortuneReading::STATUS_CELTIC_PENDING_PAYMENT => 'pending_review',
            // 🛑 (2026-05-06) STATUS_AWAITING_DELIVERY_CONFIRM ลบไปแล้ว — Pay-Later removed
            $reading->conversation_status === FortuneReading::STATUS_PAID => 'auto_approved',
            $reading->is_paid && in_array($reading->conversation_status, [
                FortuneReading::STATUS_CELTIC_PICKING,
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_GENERATING,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ], true) => 'auto_approved',
            $reading->conversation_status === FortuneReading::STATUS_COMPLETED && $reading->is_paid => 'auto_approved',
            $reading->conversation_status === FortuneReading::STATUS_COMPLETED && ! $reading->is_paid => 'cancelled',
            default => 'pending_review',
        };

        // 🏷️ ดึง cancellation_reason จาก conversation_state (เฉพาะกรณี cancelled)
        //    ค่าที่เป็นไปได้:
        //      - 'auto_expired'        — Phase J cron ยกเลิกที่ 30 นาที (หลัก)
        //      - 'user_cancelled'      — ลูกค้ากดยกเลิกเอง
        //      - 'auto_expired_grace'  — SmsPaymentService cleanup ยกเลิกหลัง grace 90 นาที
        //      - 'unknown'             — บิลเก่าไม่ได้ระบุ (ก่อนระบบนี้เข้า)
        $cancellationReason = null;
        if ($approvalStatus === 'cancelled') {
            $cancellationReason = 'unknown';
            try {
                $state = $reading->conversation_state ?? [];
                if (is_array($state) && ! empty($state['cancellation_reason'])) {
                    $cancellationReason = (string) $state['cancellation_reason'];
                }
            } catch (\Throwable $e) {
                // ปล่อย default
            }
        }

        // ดึงยอดเงินจริงที่ต้องชำระ (unique amount)
        // 🩹 (2026-05-05) Fallback chain — กัน amount=0 บน SMS Checker app
        //   เคสจริง: Pay-Later createPaymentBill fail → setPendingPayment ไม่ถูกเรียก
        //             → reading.amount_paid=0 → SMS Checker app แสดง ฿0 → admin filter ผิด
        //   Fix: 1) UPA.unique_amount (จริงสุด — ทศนิยม unique)
        //        2) reading.amount_paid (ถ้าเคย setPendingPayment)
        //        3) settings price ตาม reading_type (ปลอดภัยสุด — แสดงราคาฐาน)
        $uniquePayment = $reading->unique_payment_amount_id
            ? UniquePaymentAmount::find($reading->unique_payment_amount_id)
            : null;
        if ($uniquePayment) {
            $displayAmount = (float) $uniquePayment->unique_amount;
        } elseif ((float) $reading->amount_paid > 0) {
            $displayAmount = (float) $reading->amount_paid;
        } else {
            // Fallback ตาม reading_type → ราคาฐานจาก settings
            try {
                $fortuneSettings = \App\Models\FortuneTellingSetting::getSettings();
                $displayAmount = match ($reading->reading_type) {
                    FortuneReading::READING_TYPE_CELTIC_CROSS => (float) ($fortuneSettings->celtic_cross_price ?? 99),
                    FortuneReading::READING_TYPE_DEEP => (float) ($fortuneSettings->deep_reading_price ?? 39),
                    default => 0.0,
                };
            } catch (\Throwable $e) {
                $displayAmount = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS ? 99.0 : 39.0;
            }
        }

        // 👤 Customer name resolution (priority chain — รองรับทุก platform)
        //   1. facebook_user_name        — Facebook ที่ extract มาจาก profile
        //   2. user_profile['name']      — cross-platform (LINE / Web)
        //   3. user.name                 — ถ้ามี registered user account
        //   4. platform-specific id      — fallback แสดง LINE-xxxx / FB-xxxx
        //   5. 'ลูกค้าดูดวง'             — สุดท้าย
        $customerName = $reading->facebook_user_name;
        if (empty($customerName) || $customerName === 'คุณ') {
            $profile = $reading->user_profile ?? [];
            if (is_array($profile) && ! empty($profile['name']) && $profile['name'] !== 'คุณ') {
                $customerName = $profile['name'];
            }
        }
        if (empty($customerName) || $customerName === 'คุณ') {
            try {
                $customerName = $reading->user?->name;
            } catch (\Throwable $e) {
                $customerName = null;
            }
        }
        if (empty($customerName) || $customerName === 'คุณ') {
            $platformId = $reading->platform_user_id ?? $reading->facebook_user_id;
            if (! empty($platformId)) {
                $platformLabel = strtoupper($reading->platform ?? 'FB');
                $customerName = $platformLabel.'-'.substr($platformId, -6);
            }
        }
        if (empty($customerName)) {
            $customerName = 'ลูกค้าดูดวง';
        }

        // 🏷️ ชื่อสินค้าตามประเภทคำทำนาย — ให้ SMS Checker app แสดงประเภทถูกต้อง
        //    'celtic_cross' → "ดูดวงไพ่เซลติก" (99฿ — ไพ่ยิปซีเต็มสำรับ 10 ใบ)
        //    'deep'         → "ดูดวง (เชิงลึก)" (39฿ — วันเกิด + ไพ่ 1 ใบ)
        //    'basic'/null   → "ดูดวง" (ฟรี dummy หรือ legacy)
        $productName = match ($reading->reading_type) {
            FortuneReading::READING_TYPE_CELTIC_CROSS => 'ดูดวงไพ่เซลติก',
            FortuneReading::READING_TYPE_DEEP => 'ดูดวง (เชิงลึก)',
            default => 'ดูดวง',
        };

        // 📱 (2026-06-11) ช่องทางที่ลูกค้าทักมา (facebook / line) — ให้ SMS Checker app
        //    แสดง badge โลโก้ FB/LINE บนการ์ดบิล (logic เดียวกับ approveOrder delivery routing)
        $platform = $reading->platform
            ?: ((preg_match('/^U[0-9a-f]{32}$/i', $reading->facebook_user_id ?? '')) ? 'line' : 'facebook');

        // 🏬 (2026-08-15) บิลนี้มาจากสาขาไหน — เจ้าของสั่ง "แอพต้องระบุด้วยว่าบิลเป็นของเพจไหน"
        //    ส่ง 2 ทาง:
        //      1. ก้อน branch (มีโครงสร้าง) — ไว้ให้แอพรุ่นใหม่วาด badge สาขา
        //      2. ต่อท้าย product_details — เห็นผลทันทีบนแอพรุ่นปัจจุบันโดยไม่ต้องอัปเดตแอพ
        //    แสดงเฉพาะสาขาที่ "ไม่ใช่สาขาหลัก" — บิลส่วนใหญ่มาจากเพจหลัก
        //    ถ้าแปะทุกใบจะกลายเป็นข้อความซ้ำเต็มจอจนไม่มีใครอ่าน
        $branch = $this->branchInfoFor($reading);
        $productDetails = $customerName;

        if ($branch !== null && ! $branch['is_default']) {
            $productDetails = $customerName.' · 🏬 '.$branch['name'];
        }

        // 🛑 (2026-05-06) Pay-Later removed — ไม่มี suffix "ดูก่อนจ่าย" + ไม่ส่ง flag
        $orderDetails = [
            'order_number' => $reading->bill_reference,
            'product_name' => $productName,
            'product_details' => $productDetails,
            'quantity' => 1,
            'website_name' => config('app.name'),
            'customer_name' => $customerName,
            'amount' => $displayAmount,
            'platform' => $platform,
            'branch' => $branch,
            // 🚫 (2026-07-27) บิลดูดวงยกเลิกการอนุมัติได้จากแอพ (voidApproval engine)
            //    แอพจะโชว์ปุ่ม "ยกเลิกการอนุมัติ" เฉพาะบิลที่ค่านี้ = true และอนุมัติแล้ว
            'can_void' => true,
        ];

        // 🧾 (2026-07-27) สลิปที่ทำให้บิลนี้ผ่าน — ส่ง metadata + path รูปให้แอพเปิดดูตรวจซ้ำได้
        //   PDPA: ส่งแค่ "เส้นทาง" ไม่ส่งไฟล์/base64 — แอพต้องยิงพร้อม X-Api-Key + X-Device-Id
        //   รูป archive 30 วัน (fortune:purge-slip-archive) → บิลเก่ากว่านั้น slip = null
        $slipLog = $this->slipLogFor($reading);
        if ($slipLog) {
            $orderDetails['slip'] = [
                'log_id' => (int) $slipLog->id,
                // relative path — แอพต่อกับ baseUrl ของ server ตัวเอง (กัน APP_URL ตั้งผิด)
                'image_path' => 'api/v1/sms-payment/orders/'.rawurlencode((string) $reading->bill_reference).'/slip-image',
                'trans_ref' => $slipLog->trans_ref,
                'sender_name' => $slipLog->sender_name,
                'receiver_account' => $slipLog->receiver_account,
                'amount' => $slipLog->amount !== null ? (float) $slipLog->amount : null,
                'checked_at' => $slipLog->created_at?->toIso8601String(),
            ];
        }

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

        // ใช้ offset ID เพื่อไม่ให้ชนกับ PaymentTransaction ID ใน Android local DB
        $offsetId = $reading->id + self::FORTUNE_READING_ID_OFFSET;

        // 🏷️ (2026-05-25) Human-readable label สำหรับ smschecker app
        //    User spec: "บิลที่ถูกยกเลิกโดยระบบ ให้ขึ้นในแอพและในระบบต่างๆ ว่า ยกเลิกโดยระบบ"
        //    ส่ง cancellation_reason (enum key) + cancellation_reason_label (Thai text) พร้อมกัน
        //    → app สามารถใช้ label ตรงๆ ได้ ไม่ต้อง map เอง (backward compat กับ app เก่า)
        $cancellationReasonLabel = $cancellationReason
            ? FortuneReading::getCancellationReasonLabel($cancellationReason)
            : null;

        return [
            'id' => $offsetId,
            'notification_id' => $reading->sms_notification_id,
            'matched_transaction_id' => $offsetId,
            'device_id' => null,
            'approval_status' => $approvalStatus,
            'cancellation_reason' => $cancellationReason,
            'cancellation_reason_label' => $cancellationReasonLabel,
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
     */
    public function notify(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ตรวจสอบ rate limit ต่ออุปกรณ์
        $rateLimitKey = 'smschecker:rate:'.$device->device_id;
        $rateLimit = config('smschecker.rate_limit_per_minute', 30);
        $currentCount = (int) Cache::get($rateLimitKey, 0);

        if ($currentCount >= $rateLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Max '.$rateLimit.' requests per minute.',
            ], 429);
        }

        // เพิ่ม counter (หมดอายุ 60 วินาที)
        Cache::put($rateLimitKey, $currentCount + 1, 60);

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
        if (! $encryptedData) {
            return response()->json([
                'success' => false,
                'message' => 'No payload data',
            ], 400);
        }

        // ตรวจสอบ HMAC signature
        $signatureData = $encryptedData.$nonce.$timestamp;
        if (! $this->smsPaymentService->verifySignature($signatureData, $signature, $device->secret_key)) {
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
        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decrypt payload',
            ], 400);
        }

        // ตรวจสอบ bank ที่รองรับจาก config
        $supportedBanks = array_keys(config('smschecker.supported_banks', []));
        $bankRule = ! empty($supportedBanks)
            ? 'required|string|in:'.implode(',', $supportedBanks)
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

        // ✅ แปลง matched model เป็น RemoteOrderApproval format เพื่อให้แอพแสดงบิลได้ทันที
        $matchedOrder = null;
        if (! empty($result['matched_model'])) {
            $model = $result['matched_model'];
            $type = $result['matched_model_type'] ?? null;

            if ($type === 'fortune_reading' && $model instanceof FortuneReading) {
                $matchedOrder = $this->transformFortuneReadingToOrderApproval($model);
            } elseif ($type === 'payment_transaction' && $model instanceof PaymentTransaction) {
                $matchedOrder = $this->transformToOrderApproval($model);
            }

            // ลบ matched_model ออกจาก response (ไม่ต้องส่ง Eloquent model ทั้งก้อน)
            unset($result['matched_model'], $result['matched_model_type']);
        }

        // เพิ่ม matched_order ลงใน data เพื่อให้แอพแสดงบิลได้ทันทีหลัง notify
        // ✅ ส่งทั้ง 'matched_order' (ชื่อเดิม) และ 'order' (ที่ Android app อ่าน)
        // Android app TransactionRepository.kt อ่านจาก data.order
        if ($matchedOrder) {
            $result['data']['matched_order'] = $matchedOrder;
            $result['data']['order'] = $matchedOrder;
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ตรวจสอบสถานะอุปกรณ์และจำนวน pending
     *
     * GET /api/v1/sms-payment/status
     */
    public function status(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
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
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
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

        // ✅ อัพเดท device_id จากแอพ — ให้แอพเป็น source of truth สำหรับ device_id
        // กรณี admin สร้าง device ไว้ด้วย SMSCHK-XXXXXXXX แต่แอพ generate ID ใหม่
        // register-device จะ sync ให้ตรงกัน ป้องกัน "Device ID mismatch" error
        $incomingDeviceId = $request->input('device_id');
        if ($incomingDeviceId && $device->device_id !== $incomingDeviceId) {
            Log::info('registerDevice: อัพเดท device_id', [
                'old_device_id' => $device->device_id,
                'new_device_id' => $incomingDeviceId,
                'ip' => $request->ip(),
            ]);
            $updateData['device_id'] = $incomingDeviceId;
        }

        // บันทึก FCM token สำหรับ push notifications
        if ($request->filled('fcm_token')) {
            $fcmToken = $request->input('fcm_token');
            $updateData['fcm_token'] = $fcmToken;
            $updateData['fcm_token_updated_at'] = now();

            Log::info('FCM Register (via registerDevice): ได้รับ FCM token', [
                'device_id' => $incomingDeviceId ?? $device->device_id,
                'token_length' => strlen($fcmToken),
                'token_prefix' => substr($fcmToken, 0, 20).'...',
                'ip' => $request->ip(),
            ]);
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
     */
    public function registerFcmToken(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            Log::warning('FCM Register: Unauthorized — ไม่พบอุปกรณ์ใน request', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            Log::warning('FCM Register: Validation failed', [
                'device_id' => $device->device_id,
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fcmToken = $request->input('fcm_token');

        Log::info('FCM Register: ได้รับ FCM token จากแอพ', [
            'device_id' => $device->device_id,
            'token_length' => strlen($fcmToken),
            'token_prefix' => substr($fcmToken, 0, 20).'...',
            'ip' => $request->ip(),
        ]);

        $this->fcmService->registerToken($device, $fcmToken);

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

        if (! $uniqueAmount) {
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
                'display_amount' => '฿'.number_format((float) $uniqueAmount->unique_amount, 2),
            ],
        ]);
    }

    /**
     * ดู notification history สำหรับ admin dashboard
     *
     * GET /api/v1/sms-payment/notifications
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
     */
    public function orders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = PaymentTransaction::query();

        // === Multi-Store Filtering (Strict Mode) ===
        // ทุก device เห็นเฉพาะ orders ของ store ตัวเองเท่านั้น
        // (null = admin/official shop, int = seller store)
        $this->applyStoreFilter($query, $device);

        // กรองสถานะ (default: pending + processing + completed ล่าสุด)
        $status = $request->input('status', 'waiting');
        if ($status === 'waiting') {
            // 'waiting' = pending/processing (รอชำระ) + completed ที่เพิ่ง auto-approve (ภายใน 1 ชม.)
            // เพื่อให้แอพแสดงบิลที่เพิ่งจับคู่สำเร็จจาก SMS ด้วย
            $query->where(function ($q) {
                $q->whereIn('status', ['pending', 'processing'])
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'completed')
                            ->where('paid_at', '>=', now()->subHour());
                    });
            });
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

        // ✅ Safety net: ตรวจสอบบิลดูดวงที่ค้างที่ 'paid' → retry อัตโนมัติ
        // ทำงานทุกครั้งที่แอพ poll orders (ทุก 30-60 วินาที)
        // ใช้เวลาน้อย (1 query) ไม่กระทบ performance
        $this->retryStuckFortuneReadings();

        // === รวมบิลดูดวง (FortuneReading) ที่รอชำระเงินหรือชำระแล้ว ===
        // FortuneReading ไม่มี store_id → เฉพาะ admin device เท่านั้นที่เห็น
        // หมายเหตุ: บิลเก่าอาจไม่มี unique_payment_amount_id (สร้างก่อนระบบ SMS Checker)
        // → ใช้ conversation_status เป็นหลักในการกรอง ไม่ต้องบังคับว่าต้องมี unique_payment_amount_id
        $fortuneReadings = collect();
        if ($this->deviceCanAccessFortuneReading($device)) {
            $fortuneQuery = FortuneReading::query();

            if ($status === 'waiting') {
                // 'waiting' รวม:
                //  - PENDING_PAYMENT / CELTIC_PENDING_PAYMENT (รอชำระ) — บิลใหม่
                //  - PAID (เพิ่ง auto-approve จาก SMS — ยังประมวลผล AI)
                //  - 🔮 CELTIC_PICKING/AWAITING_QUESTION/GENERATING/QA_PROMPT (Celtic หลังจ่าย — ยัง active)
                //  - COMPLETED ภายใน 24 ชม. — ทั้งบิล cancelled (is_paid=false)
                //    และบิลทำนายเสร็จ (is_paid=true) เพื่อให้แอพเห็นการเปลี่ยนแปลงสถานะ
                //    และ "ลบ" บิลที่ cancel แล้วออกจาก UI
                //    ⚠️ เคยมีบั๊ก: ไม่รวม COMPLETED → บิล cancelled ค้างใน UI ตลอดไป
                //    ⚠️ เคยมีบั๊ก: ไม่รวม Celtic statuses → บิล Celtic 99฿ หายจาก UI หลัง FCM push
                $fortuneQuery->where(function ($q) {
                    $q->whereIn('conversation_status', [
                        FortuneReading::STATUS_PENDING_PAYMENT,
                        FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
                        FortuneReading::STATUS_PAID,
                        FortuneReading::STATUS_CELTIC_PICKING,
                        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                        FortuneReading::STATUS_CELTIC_GENERATING,
                        FortuneReading::STATUS_CELTIC_QA_PROMPT,
                    ])
                        ->orWhere(function ($q2) {
                            $q2->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                ->where('updated_at', '>=', now()->subHours(24));
                        });
                });
            } elseif ($status === 'all') {
                $fortuneQuery->whereIn('conversation_status', [
                    FortuneReading::STATUS_PENDING_PAYMENT,
                    FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
                    FortuneReading::STATUS_PAID,
                    FortuneReading::STATUS_CELTIC_PICKING,
                    FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                    FortuneReading::STATUS_CELTIC_GENERATING,
                    FortuneReading::STATUS_CELTIC_QA_PROMPT,
                    FortuneReading::STATUS_COMPLETED,
                ]);
            }

            // ไม่ส่งบิลที่ยอด 0 (ยังไม่มี unique amount / ยังอยู่ระหว่าง conversation)
            $fortuneQuery->where(function ($q) {
                $q->where('amount_paid', '>', 0)
                    ->orWhereHas('uniquePaymentAmount', function ($uq) {
                        $uq->where('unique_amount', '>', 0);
                    });
            });

            if ($dateFrom) {
                $fortuneQuery->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $fortuneQuery->where('created_at', '<=', $dateTo);
            }

            $fortuneReadings = $fortuneQuery->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            // 🧾 โหลดสลิปของทุกบิลในคิวรีเดียวก่อน transform (กัน N+1)
            $this->preloadSlipLogsFor($fortuneReadings);

            $fortuneOrders = $fortuneReadings->map(function ($reading) {
                return $this->transformFortuneReadingToOrderApproval($reading);
            });

            // รวมเข้ากับ orders แล้ว sort ตาม created_at
            $orders = $orders->concat($fortuneOrders)
                ->sortByDesc('created_at')
                ->values();

            // 🔍 Debug: log fortune readings ที่ถูกส่งกลับ
            if ($fortuneReadings->isNotEmpty()) {
                Log::info('SMS Payment orders(): ส่ง fortune readings กลับแอพ', [
                    'device_id' => $device->device_id,
                    'fortune_count' => $fortuneReadings->count(),
                    'status_filter' => $status,
                    'fortune_ids' => $fortuneReadings->pluck('id')->toArray(),
                    'fortune_statuses' => $fortuneReadings->pluck('conversation_status')->toArray(),
                    'fortune_bills' => $fortuneReadings->pluck('bill_reference')->toArray(),
                ]);

                // 🔍 Debug: log ตัวอย่าง transformed data ตัวแรก
                $sampleTransformed = $fortuneOrders->first();
                if ($sampleTransformed) {
                    Log::info('SMS Payment orders(): sample fortune order', [
                        'sample_id' => $sampleTransformed['id'] ?? 'null',
                        'sample_status' => $sampleTransformed['approval_status'] ?? 'null',
                        'sample_order_number' => $sampleTransformed['order_details_json']['order_number'] ?? 'null',
                        'sample_amount' => $sampleTransformed['order_details_json']['amount'] ?? 'null',
                        'sample_notification_id' => $sampleTransformed['notification_id'] ?? 'null',
                        'sample_has_notification' => isset($sampleTransformed['notification']),
                        'sample_synced_version' => $sampleTransformed['synced_version'] ?? 'null',
                    ]);
                }
            } else {
                Log::info('SMS Payment orders(): ไม่มี fortune readings ให้ส่ง', [
                    'device_id' => $device->device_id,
                    'status_filter' => $status,
                    'device_can_access' => true,
                ]);
            }
        }

        // 🔍 Debug: log total response
        Log::info('SMS Payment orders(): response', [
            'device_id' => $device->device_id,
            'total_orders' => $orders->count(),
            'payment_txn_count' => $paginated->total(),
            'fortune_count' => $fortuneReadings->count(),
            'status_filter' => $status,
        ]);

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
     * 🧾 (2026-07-27) สตรีมรูปสลิปที่ทำให้บิลนี้ผ่าน — ให้แอดมินตรวจซ้ำในแอพ SMS Checker
     *
     * GET /api/v1/sms-payment/orders/{identifier}/slip-image
     *
     * PDPA: รูปมีชื่อผู้โอน/เลขบัญชี → เสิร์ฟผ่าน device auth (X-Api-Key + X-Device-Id) เท่านั้น
     *       เฉพาะ admin device (deviceCanAccessFortuneReading) และไม่ตั้ง public cache
     * รูป archive 30 วัน (fortune:purge-slip-archive) → เกินนั้นได้ 404
     *
     * @param  mixed  $identifier  bill_reference (FTU-...) หรือ numeric ID (มี/ไม่มี offset)
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function slipImage(Request $request, $identifier)
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ⚠️ ใช้ isAdminDevice() ไม่ใช่ deviceCanAccessFortuneReading() (ตัวนั้น return true เสมอ
        //    เพราะบิลดูดวงต้องให้ทุกเครื่อง auto-approve ได้) — รูปสลิปมีชื่อผู้โอน/เลขบัญชี = PDPA
        //    เครื่องร้านค้า (seller device) ต้องไม่เห็น
        if (! $device->isAdminDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin devices can view fortune slip images',
            ], 403);
        }

        $resolved = $this->resolveOrderByIdentifier($identifier);
        if (! $resolved || $resolved['type'] !== 'fortune') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบบิลดูดวงนี้: '.$identifier,
            ], 404);
        }

        /** @var FortuneReading $reading */
        $reading = $resolved['model'];
        $slipLog = $this->slipLogFor($reading);
        $path = (string) ($slipLog?->slip_image_path ?? '');

        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรูปสลิปของบิลนี้ (อาจถูกลบตามรอบ 30 วัน)',
            ], 404);
        }

        $device->update([
            'last_active_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'private, no-store',
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
     * @param  mixed  $identifier  numeric ID หรือ bill_reference string
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
                'message' => 'Order not found: '.$identifier,
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

            // 🩹 (2026-05-05) Admin approve = re-trigger reading flow ถ้ายังไม่ได้ส่ง
            //   user spec: "เมื่ออนุมัติบิลแล้ว ก็ปล่อยกระตุ้นโฟลว์ให้ปล่อยคำทำนาย"
            //   เคสจริง: บิล is_paid=true แต่ AI fail / push fail → ลูกค้ายังไม่ได้คำทำนาย
            //              เดิม: admin กด approve → "already paid" — ไม่ทำอะไร
            //              ใหม่: ตรวจว่าส่งคำทำนายไปแล้วหรือยัง
            //   - Deep: has deep_response + reading_sent_directly=true → delivered ✓
            //   - Celtic: celtic_questions_used >= 1 → started Q&A ✓
            //   ถ้ายังไม่ deliver → dispatch flow แม้ paid อยู่แล้ว
            $alreadyDelivered = false;
            if ($model->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
                $alreadyDelivered = (int) ($model->celtic_questions_used ?? 0) >= 1;
            } else {
                $alreadyDelivered = ! empty($model->deep_response)
                    && (bool) ($model->conversation_state['reading_sent_directly'] ?? false);
            }

            if ($model->is_paid && $alreadyDelivered) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fortune reading already paid and delivered',
                    'data' => ['bill_reference' => $model->bill_reference, 'status' => 'paid'],
                ]);
            }

            // ค้นหา SMS notification ที่จับคู่กับบิลนี้
            $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();

            // ✅ ยืนยันการชำระเงินทันที (ก่อน dispatch job)
            // เพื่อให้แอพแสดงสถานะ "ชำระแล้ว" ทันที ไม่ต้องรอ job รัน
            if (! $model->is_paid) {
                $model->confirmPayment($notification);
                $model = $model->fresh();
            }

            // 🩹 (2026-05-05) Unified delivery routing — user spec: "ส่งคำทำนายทุกกรณี"
            //   Logic ใหม่ใช้ $alreadyDelivered (คำนวณก่อนหน้า) เป็น single source of truth:
            //     ✓ alreadyDelivered=true  → ส่ง "thank-you" อย่างเดียว (กัน re-spam คำทำนายซ้ำ)
            //     ✓ alreadyDelivered=false → dispatch flow → AI gen / re-push เสมอ
            //   ครอบคลุม edge cases:
            //     • Pay-Later ลูกค้าได้รับคำทำนายแล้ว → thank-you only ✓
            //     • Pay-Later AI gen เสร็จแต่ delivery fail (sent_directly=false) → dispatch re-push ✓
            //     • Pay-First AI fail / push fail → dispatch re-gen + push ✓
            //     • Pay-First สำเร็จเรียบร้อย → thank-you only ✓
            if ($alreadyDelivered) {
                try {
                    $platform = $model->platform
                        ?: ((preg_match('/^U[0-9a-f]{32}$/i', $model->facebook_user_id ?? '')) ? 'line' : 'facebook');
                    $userId = $model->platform_user_id ?? $model->facebook_user_id;

                    if ($userId) {
                        $name = $model->facebook_user_name ?? 'คุณ';
                        $thankMsg = \App\Services\FortuneLocaleService::lo(
                            "✅ *ระบบรับการชำระเงินแล้วค่ะ คุณ{$name}* 🙏\n\n"
                                ."📋 บิล: {$model->bill_reference}\n"
                                .'💰 ยอด: ฿'.number_format($model->amount_paid, 2)."\n\n"
                                ."ขอบคุณที่ไว้วางใจแม่หมอจันทรานะคะ ✨\n"
                                .'หวังว่าคำทำนายจะเป็นประโยชน์กับเจ้าชะตา 🙏',
                            "✅ *ລະບົບຮັບການຊຳລະເງິນແລ້ວເດີ ເຈົ້າ{$name}* 🙏\n\n"
                                ."📋 ບິນ: {$model->bill_reference}\n"
                                .'💰 ຍອດ: ฿'.number_format($model->amount_paid, 2)."\n\n"
                                .'ຂອບໃຈທີ່ໄວ້ວາງໃຈແມ່ໝໍຈັນທະຣາເດີ ✨'
                        );

                        $settings = FortuneTellingSetting::getSettings();
                        $channelManager = new FortuneChannelManager($settings);
                        $platformService = $channelManager->getPlatform($platform);
                        if ($platformService) {
                            $platformService->sendMessage($userId, $thankMsg, ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                        }

                        // ปิด conversation_status เป็น COMPLETED — บิลปิดถาวร
                        $model->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
                    }

                    Log::info('💎 SMS Payment: admin approved + reading delivered → thank-you only (no re-dispatch)', [
                        'reading_id' => $model->id,
                        'bill_reference' => $model->bill_reference,
                        'reading_type' => $model->reading_type,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('💎 SMS Payment: thank-you push ล้มเหลว (best-effort)', [
                        'reading_id' => $model->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // 🔮 ยังไม่ deliver — dispatch flow (Deep gen+push / Celtic เปิดไพ่)
                //   user spec: "เมื่ออนุมัติบิลแล้ว ก็ปล่อยกระตุ้นโฟลว์ให้ปล่อยคำทำนาย"
                //   ครอบคลุม:
                //     • Pay-First not yet delivered (AI fail / push fail / not started)
                //     • Pay-Later AI gen เสร็จแต่ delivery fail
                //     • Celtic paid but not started Q&A (push first card prompt)
                Log::info('💎 SMS Payment: admin approve + reading NOT delivered → dispatch flow', [
                    'reading_id' => $model->id,
                    'bill_reference' => $model->bill_reference,
                    'reading_type' => $model->reading_type,
                    'has_deep_response' => ! empty($model->deep_response),
                    'celtic_q_used' => $model->celtic_questions_used ?? 0,
                ]);
                $this->dispatchFortuneApprovalFlow($model, $notification);
            }

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
                'message' => 'Fortune reading approved and deep reading sent',
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

        // 🩹 (2026-05-05) Admin force approve — user spec: "การกดอนุมัติบิลต้อง force เสมอ"
        //   เคสจริง: บิลที่ status = expired / cancelled / failed → admin กด approve ได้ไม่ผ่าน (422)
        //            แต่ admin ตัดสินใจแล้วว่าได้รับเงินจริง — ต้อง override status ทุกกรณี
        //   เดิม: ! in_array($model->status, ['pending', 'processing']) → return 422
        //   ใหม่: log warning ถ้าจะ approve บิลที่ไม่ใช่ pending — แต่ดำเนินการต่อ (admin authority)
        if (! in_array($model->status, ['pending', 'processing'])) {
            Log::warning('SMS Payment: admin force-approve transaction with non-pending status', [
                'transaction_id' => $model->transaction_id,
                'current_status' => $model->status,
                'device_id' => $device->device_id,
            ]);
            // ไม่ block — admin มี authority อนุมัติทุก status (รวม expired/cancelled/failed)
        }

        // ใช้ DB transaction ครอบทั้งหมดเพื่อให้ rollback ได้ถ้า completePayment ล้มเหลว
        try {
            \DB::transaction(function () use ($model) {
                // 🩹 (2026-05-05) Mark UPA as used — รวมทุก status (force approve)
                //   เดิม: filter เฉพาะ reserved/expired → admin force approve UPA cancelled ไม่อัปเดต
                //   ใหม่: รวม cancelled, used (idempotent) — admin ตัดสินใจอัปเดตให้ตรงกับการรับเงินจริง
                $uniqueAmount = UniquePaymentAmount::where('transaction_id', $model->id)->first();
                if ($uniqueAmount && $uniqueAmount->status !== 'used') {
                    $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                }

                // Update SMS notification status to confirmed (ถ้ามี)
                $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();
                if ($notification) {
                    $notification->update(['status' => 'confirmed']);
                }

                // 🩹 (2026-05-05) ถ้า status เป็น cancelled/failed/expired — reset เป็น pending ก่อน
                //   เพราะ markAsCompleted() อาจตรวจ guard internally ใน completePayment
                if (! in_array($model->status, ['pending', 'processing', 'completed'])) {
                    $model->update(['status' => 'pending']);
                    $model->refresh();
                }

                app(PaymentService::class)->completePayment($model);
            });
        } catch (\Exception $e) {
            Log::error('SMS Payment: approve order ล้มเหลว', [
                'transaction_id' => $model->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete payment: '.$e->getMessage(),
            ], 500);
        }

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
     * ✅ Safety net: ตรวจสอบบิลดูดวงที่ค้างที่ 'paid' แต่ยังไม่มีคำทำนาย → retry อัตโนมัติ
     *
     * เรียกทุกครั้งที่แอพ poll orders — ทำงานเร็ว (1 query, ไม่ lock)
     * เป็นตัวช่วยเสริมจาก fortune:check-pending (scheduler) ที่อาจไม่ได้ตั้ง
     */
    private function retryStuckFortuneReadings(): void
    {
        try {
            // ค้นหาบิลที่ชำระแล้ว แต่ยังไม่มี deep_response (ค้าง 2-30 นาที)
            // รวม STATUS_COMPLETED ด้วย เพราะ job ที่ล้มเหลวจะเปลี่ยนสถานะเป็น completed
            // แต่ deep_response ยังเป็น null → ต้อง retry
            $stuckReadings = FortuneReading::where('is_paid', true)
                ->where('reading_type', 'deep')
                ->whereNull('deep_response')
                ->where(function ($q) {
                    $q->where('conversation_status', FortuneReading::STATUS_PAID)
                        ->orWhere(function ($sub) {
                            // completed + deep_response null = job ล้มเหลว
                            $sub->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                ->whereNull('deep_response');
                        });
                })
                ->whereNotNull('paid_at')
                ->where('paid_at', '<=', now()->subMinutes(2))
                ->where('paid_at', '>=', now()->subHours(24)) // ขยายจาก 30 นาทีเป็น 24 ชั่วโมง
                ->limit(3) // จำกัดไม่ให้ retry มากเกินไปพร้อมกัน
                ->get();

            foreach ($stuckReadings as $reading) {
                // ตรวจสอบ retry count (ป้องกัน dispatch ซ้ำไม่จำกัด)
                $retryCount = $reading->getConversationState('auto_retry_count', 0);
                if ($retryCount >= 5) { // เพิ่มจาก 3 เป็น 5 ครั้ง
                    continue;
                }

                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
                if (empty($userId)) {
                    continue;
                }

                // ใช้ Cache lock ป้องกัน dispatch ซ้ำจากหลาย request พร้อมกัน
                $lockKey = "fortune_retry_lock:{$reading->id}";
                if (Cache::has($lockKey)) {
                    continue;
                }
                Cache::put($lockKey, true, 120); // lock 2 นาที

                // อัพเดท retry count
                $reading->setConversationState('auto_retry_count', $retryCount + 1);
                $reading->setConversationState('last_auto_retry_at', now()->toIso8601String());

                // เปลี่ยนสถานะกลับเป็น paid เพื่อให้ job ทำงานได้
                if ($reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                    $reading->update(['conversation_status' => FortuneReading::STATUS_PAID]);
                }

                $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

                ProcessDeepFortuneReadingJob::dispatchSmart(
                    $reading->id, null, $platform, $userId
                );

                Log::info('SMS Payment orders(): auto-retry stuck fortune reading', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'retry_count' => $retryCount + 1,
                    'paid_minutes_ago' => (int) $reading->paid_at->diffInMinutes(now()),
                    'was_status' => $reading->conversation_status,
                ]);
            }
        } catch (\Exception $e) {
            // ไม่ให้ error จาก safety net กระทบ response ปกติ
            Log::warning('SMS Payment orders(): retryStuckFortuneReadings failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🔮 Dispatch flow ที่ถูกต้องตาม reading_type — ใช้แทน ProcessDeepFortuneReadingJob ตรงๆ
     *
     * - Deep 39฿  → dispatch ProcessDeepFortuneReadingJob (สร้างคำทำนายใน background)
     * - Celtic 99฿ → call SmsPaymentService::handleCelticPaymentMatched()
     *               (transition status → CELTIC_PICKING + push "เริ่มเปิดไพ่ใบที่ 1")
     *               ❌ ห้าม dispatch ProcessDeepFortuneReadingJob — Celtic ไม่มีวันเกิด/คำทำนาย deep
     *
     * @param  FortuneReading  $reading  บิลที่จ่ายแล้ว (is_paid=true)
     * @param  SmsPaymentNotification|null  $notification  SMS ที่ตรงบิล (null ถ้า admin force approve)
     */
    private function dispatchFortuneApprovalFlow(
        FortuneReading $reading,
        ?SmsPaymentNotification $notification
    ): bool {
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        if (empty($userId)) {
            Log::warning('SMS Payment: dispatchFortuneApprovalFlow — ไม่มี userId', [
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'reading_type' => $reading->reading_type,
            ]);

            return false;
        }

        $platform = $reading->platform
            ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

        // 🔮 Celtic Cross — push เริ่มเปิดไพ่ + transition CELTIC_PICKING (ไม่ dispatch deep job)
        if ($reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
            try {
                // ⚠️ confirmPayment ก่อน handleCelticPaymentMatched เสมอ
                //    handleCelticPaymentMatched assume is_paid=true
                //    (auto SMS match flow ที่ matchAndProcessFortuneReading: บรรทัด 667 ของ SmsPaymentService
                //     จะ confirmPayment ก่อนเรียก handleCelticPaymentMatched อยู่แล้ว)
                if (! $reading->is_paid) {
                    $reading->confirmPayment($notification);
                    $reading = $reading->fresh();
                }

                return app(SmsPaymentService::class)->handleCelticPaymentMatched(
                    $reading,
                    $notification,
                    $platform,
                    (string) $userId,
                    (float) $reading->amount_paid
                );
            } catch (\Throwable $e) {
                Log::critical('SMS Payment: dispatchFortuneApprovalFlow Celtic ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        // 🔹 Deep 39฿ — dispatch background job เพื่อสร้างคำทำนาย
        ProcessDeepFortuneReadingJob::dispatchSmart(
            $reading->id, $notification?->id, $platform, $userId
        );

        return true;
    }

    /**
     * ค้นหา order จาก identifier (numeric ID หรือ bill_reference string)
     *
     * Prefix routing:
     * - PRE-, SEL-, TXN-, TAROT- → PaymentTransaction (transaction_id)
     * - FTU-, FR- → FortuneReading (bill_reference)
     * - Numeric → PaymentTransaction::find() / legacy virtual ID (10M+)
     *
     * @param  mixed  $identifier
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
     * @param  mixed  $identifier  numeric ID หรือ bill_reference string
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
                'message' => 'Order not found: '.$identifier,
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
                'message' => 'Transaction is not pending (current: '.$model->status.')',
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
                'cancellation_reason' => 'ปฏิเสธโดย SMS Checker: '.$reason,
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

                // 🩹 (2026-05-05) Unified delivery routing — ตรงกับ approveOrder
                //   alreadyDelivered=true  → confirm + thank-you (กัน re-spam)
                //   alreadyDelivered=false → confirm + dispatch flow (ส่งคำทำนายทุกกรณี)
                $alreadyDelivered = $model->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
                    ? (int) ($model->celtic_questions_used ?? 0) >= 1
                    : (! empty($model->deep_response)
                        && (bool) ($model->conversation_state['reading_sent_directly'] ?? false));

                if ($model->is_paid && $alreadyDelivered) {
                    $failed++; // already paid + delivered — count as skip (idempotent)
                } else {
                    $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();

                    if (! $model->is_paid) {
                        $model->confirmPayment($notification);
                        $model = $model->fresh();
                    }

                    if ($alreadyDelivered) {
                        // Pay-Later เคสที่ลูกค้าได้รับคำทำนายแล้ว — ไม่ต้อง re-spam
                        // (admin force confirm payment เฉยๆ — ไม่ dispatch ซ้ำ)
                        Log::info('💎 SMS Payment: bulk approved + delivered → confirm only', [
                            'reading_id' => $model->id,
                            'bill_reference' => $model->bill_reference,
                        ]);
                    } else {
                        // ยังไม่ deliver — dispatch flow ส่งคำทำนาย
                        $dispatched = $this->dispatchFortuneApprovalFlow($model, $notification);
                        if (! $dispatched && ! $model->is_paid) {
                            $model->confirmPayment($notification);
                        }
                    }
                    $approved++;
                }
            } else {
                /** @var PaymentTransaction $model */
                // Multi-Store: ตรวจสอบสิทธิ์ device
                if (! $this->deviceCanAccessTransaction($device, $model)) {
                    $failed++;

                    continue;
                }
                // 🩹 (2026-05-05) Bulk admin force approve — ตรงกับ approveOrder
                //   user spec: "การกดอนุมัติบิลต้อง force เสมอ"
                //   เดิม: skip ถ้า status != pending/processing → admin force ไม่ได้
                //   ใหม่: log warning + ดำเนินการต่อ (ครอบคลุม cancelled/expired/failed)
                if ($model->status === 'completed') {
                    $failed++; // already approved — count as skip
                } else {
                    if (! in_array($model->status, ['pending', 'processing'])) {
                        Log::warning('SMS Payment: bulk admin force-approve transaction with non-pending status', [
                            'transaction_id' => $model->transaction_id,
                            'current_status' => $model->status,
                            'device_id' => $device->device_id,
                        ]);
                    }

                    try {
                        \DB::transaction(function () use ($model, $paymentService) {
                            // Mark UniquePaymentAmount as used (force — รวมทุก status)
                            $uniqueAmount = UniquePaymentAmount::where('transaction_id', $model->id)->first();
                            if ($uniqueAmount && $uniqueAmount->status !== 'used') {
                                $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                            }

                            // Update SMS notification status
                            $notification = SmsPaymentNotification::where('matched_transaction_id', $model->id)->first();
                            if ($notification) {
                                $notification->update(['status' => 'confirmed']);
                            }

                            // ถ้า status ไม่ใช่ pending/processing/completed → reset เป็น pending ก่อน
                            if (! in_array($model->status, ['pending', 'processing', 'completed'])) {
                                $model->update(['status' => 'pending']);
                                $model->refresh();
                            }

                            $paymentService->completePayment($model);
                        });
                        $approved++;
                    } catch (\Exception $e) {
                        Log::error('SMS Payment: bulk approve ล้มเหลว', [
                            'transaction_id' => $model->id,
                            'error' => $e->getMessage(),
                        ]);
                        $failed++;
                    }
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
            'message' => "Approved {$approved} orders".($failed > 0 ? ", {$failed} skipped" : ''),
            'data' => ['approved' => $approved, 'failed' => $failed],
        ]);
    }

    /**
     * Incremental sync - ดึง orders ที่เปลี่ยนแปลงตั้งแต่ version ที่กำหนด
     *
     * GET /api/v1/sms-payment/orders/sync
     */
    public function syncOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
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
                    FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
                    // 🌍 (2026-08-23) เลนบัตรต่างประเทศ — บิลยังรอชำระอยู่ แอดมินต้องเห็นในแอพ
                    FortuneReading::STATUS_PENDING_STRIPE_PAYMENT,
                    FortuneReading::STATUS_PAID,
                    // 🔮 Celtic statuses (post-payment) — sync ให้ SMS app เห็นการเปลี่ยน status
                    FortuneReading::STATUS_CELTIC_PICKING,
                    FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                    FortuneReading::STATUS_CELTIC_GENERATING,
                    FortuneReading::STATUS_CELTIC_QA_PROMPT,
                    FortuneReading::STATUS_COMPLETED,
                ])
                // ไม่ส่งบิลที่ยอด 0
                ->where(function ($q) {
                    $q->where('amount_paid', '>', 0)
                        ->orWhereHas('uniquePaymentAmount', function ($uq) {
                            $uq->where('unique_amount', '>', 0);
                        });
                });

            if ($sinceVersion > 0) {
                $fortuneQuery->where('updated_at', '>', date('Y-m-d H:i:s', $sinceVersion / 1000));
            }

            $fortuneReadings = $fortuneQuery->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get();

            // 🧾 โหลดสลิปของทุกบิลในคิวรีเดียวก่อน transform (กัน N+1)
            $this->preloadSlipLogsFor($fortuneReadings);

            $fortuneOrders = $fortuneReadings->map(function ($reading) {
                return $this->transformFortuneReadingToOrderApproval($reading);
            });

            $allOrders = $orders->concat($fortuneOrders)
                ->sortByDesc('updated_at')
                ->values();

            // 🔍 Debug: log fortune readings ที่ถูกส่งผ่าน sync
            if ($fortuneReadings->isNotEmpty()) {
                Log::info('SMS Payment syncOrders(): ส่ง fortune readings', [
                    'device_id' => $device->device_id,
                    'fortune_count' => $fortuneReadings->count(),
                    'since_version' => $sinceVersion,
                    'fortune_ids' => $fortuneReadings->pluck('id')->toArray(),
                    'fortune_bills' => $fortuneReadings->pluck('bill_reference')->toArray(),
                ]);
            } else {
                Log::info('SMS Payment syncOrders(): ไม่มี fortune readings', [
                    'device_id' => $device->device_id,
                    'since_version' => $sinceVersion,
                    'since_date' => $sinceVersion > 0 ? date('Y-m-d H:i:s', $sinceVersion / 1000) : 'all',
                ]);
            }
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
     */
    public function matchOrderByAmount(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $amount = $request->input('amount');
        if (! $amount || ! is_numeric($amount)) {
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
                    \DB::transaction(function () use ($transaction) {
                        // Mark UniquePaymentAmount as used
                        $uniqueAmount = UniquePaymentAmount::where('transaction_id', $transaction->id)
                            ->whereIn('status', ['reserved', 'expired'])
                            ->first();
                        if ($uniqueAmount) {
                            $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                        }

                        app(PaymentService::class)->completePayment($transaction);
                    });
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
                // Recovery: ถ้า cleanup ปิดไปแล้ว → กู้คืนเป็น pending_payment ตาม reading_type
                $expectedStatus = $fortuneReading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
                    ? FortuneReading::STATUS_CELTIC_PENDING_PAYMENT
                    : FortuneReading::STATUS_PENDING_PAYMENT;

                if (! $fortuneReading->is_paid && $fortuneReading->conversation_status !== $expectedStatus) {
                    $fortuneReading->update(['conversation_status' => $expectedStatus]);
                    Log::info('SMS Payment: Recovered fortune reading for match', [
                        'fortune_reading_id' => $fortuneReading->id,
                        'expected_status' => $expectedStatus,
                    ]);
                }

                // Auto-approve fortune reading (เหมือน xmanstudio auto-approve topup)
                $autoConfirm = config('smschecker.auto_confirm_matched', true);
                if ($autoConfirm && ! $fortuneReading->is_paid) {
                    try {
                        // 🔮 หา notification ที่เพิ่งส่ง (จาก /match endpoint)
                        $notification = SmsPaymentNotification::where('amount', $amount)
                            ->where('type', 'credit')
                            ->whereNull('matched_transaction_id')
                            ->orderBy('sms_timestamp', 'desc')
                            ->first();

                        $fortuneReading->confirmPayment($notification);
                        $fortuneReading = $fortuneReading->fresh();

                        // 🔮 Route ตาม reading_type — Celtic / Deep flow ต่างกัน
                        //    helper จะเลือก ProcessDeepFortuneReadingJob (deep) หรือ
                        //    handleCelticPaymentMatched (celtic) ตาม reading.reading_type
                        //    ⚠️ เคยมีบั๊ก: dispatchSmart ทุก reading_type → Celtic ได้ flow Deep ผิด
                        $dispatched = $this->dispatchFortuneApprovalFlow($fortuneReading, $notification);

                        Log::info('SMS Payment: Auto-approved fortune reading on match', [
                            'device_id' => $device->device_id,
                            'amount' => $amount,
                            'fortune_reading_id' => $fortuneReading->id,
                            'reading_type' => $fortuneReading->reading_type,
                            'flow_dispatched' => $dispatched,
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
                    'message' => 'No pending order found with amount '.number_format($amount, 2),
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
                        // 🔮 (2026-08-23) Celtic 99 ตกหล่นมาตลอด — grace path หาบิล 99 ไม่เจอ
                        FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
                        // 🌍 (2026-08-23) เลนบัตรต่างประเทศ — ยอด QR ยังจองอยู่ ลูกค้ากลับมาสแกนได้
                        FortuneReading::STATUS_PENDING_STRIPE_PAYMENT,
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
     */
    public function getDeviceSettings(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
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
     */
    public function updateDeviceSettings(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
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

        if (! empty($updateData)) {
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
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
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
        $signatureData = $encryptedData.$nonce.$timestamp;
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
            // 🚫 (2026-07-27) void = ยกเลิกการอนุมัติที่ทำไปแล้ว (บิลดูดวงเท่านั้น)
            'action' => 'required|in:approve,reject,void',
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
                'message' => 'Order not found: '.$identifier,
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

            // 🚫 void = ถอยเงิน/ดึงคอมมิชชั่นคืน — จำกัดเครื่องแอดมินเท่านั้น
            //    (deviceCanAccessFortuneReading return true เสมอ จึงต้องเช็ค isAdminDevice() เพิ่ม)
            if ($action === 'void' && ! $device->isAdminDevice()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admin devices can void fortune approvals',
                ], 403);
            }

            return match ($action) {
                'approve' => $this->executeFortuneApproveAction($payload, $model, $device, $request->ip()),
                'void' => $this->executeFortuneVoidAction($payload, $model, $device, $request->ip()),
                default => $this->executeFortuneRejectAction($payload, $model, $device, $request->ip()),
            };
        }

        // === PaymentTransaction ===
        /** @var PaymentTransaction $model */
        if (! $this->deviceCanAccessTransaction($device, $model)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this transaction',
            ], 403);
        }

        // 🚫 void รองรับเฉพาะบิลดูดวง — บิลร้านค้ามี order/commission/stock ผูกอยู่ ต้องแก้ที่หลังบ้าน
        if ($action === 'void') {
            return response()->json([
                'success' => false,
                'message' => 'ยกเลิกการอนุมัติได้เฉพาะบิลดูดวง — บิลร้านค้าต้องยกเลิกที่หน้าเว็บแอดมิน',
            ], 422);
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
                'message' => 'Transaction cannot be approved in current status: '.$txn->status,
            ], 422);
        }

        // 🔒 SECURITY (2026-04-28): บังคับต้องมี SMS valid (เหมือน fortune)
        // กฎ: amount ตรง, มาหลัง transaction, ยังไม่ถูก match
        $billAmount = (float) ($payload['amount'] ?? $txn->amount);
        $txn_created = $txn->created_at;

        $notification = SmsPaymentNotification::where('matched_transaction_id', $txn->id)->first();

        if (! $notification && $billAmount > 0) {
            $notification = SmsPaymentNotification::where('amount', $billAmount)
                ->where('type', 'credit')
                ->whereIn('status', ['matched', 'pending'])
                ->whereNull('matched_transaction_id')
                ->where(function ($q) use ($txn_created) {
                    $q->where('sms_timestamp', '>=', $txn_created)
                        ->orWhere('created_at', '>=', $txn_created);
                })
                ->orderBy('sms_timestamp', 'asc')
                ->first();
        }

        $force = (bool) ($payload['force'] ?? false);
        if (! $notification && ! $force) {
            Log::warning('🚫 SMS Payment: ปฏิเสธ approve transaction — ไม่มี SMS valid', [
                'transaction_id' => $txn->id,
                'transaction_no' => $txn->transaction_id,
                'amount' => $billAmount,
                'device_id' => $device->device_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ SMS แจ้งเงินเข้าที่ตรงกับรายการนี้ — ไม่อนุมัติ',
                'error_code' => 'NO_VALID_SMS_FOR_TRANSACTION',
            ], 422);
        }

        if (! $notification && $force) {
            Log::critical('⚠️ SMS Payment: Force approve transaction โดยไม่มี SMS', [
                'transaction_id' => $txn->id,
                'amount' => $billAmount,
                'device_id' => $device->device_id,
            ]);
        }

        // ใช้ DB transaction ครอบทั้งหมดเพื่อให้ rollback ได้ถ้า completePayment ล้มเหลว
        try {
            \DB::transaction(function () use ($txn, $notification) {
                // Mark UniquePaymentAmount as used (เหมือน approveOrder)
                $uniqueAmount = UniquePaymentAmount::where('transaction_id', $txn->id)
                    ->whereIn('status', ['reserved', 'expired'])
                    ->first();
                if ($uniqueAmount) {
                    $uniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                }

                // Update SMS notification status to confirmed
                if ($notification) {
                    $notification->update([
                        'status' => 'confirmed',
                        'matched_transaction_id' => $txn->id,
                    ]);
                }

                app(PaymentService::class)->completePayment($txn);
            });
        } catch (\Exception $e) {
            Log::error('SMS Payment: encrypted approve ล้มเหลว', [
                'transaction_id' => $txn->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete payment: '.$e->getMessage(),
            ], 500);
        }

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
                'message' => 'Transaction cannot be rejected in current status: '.$txn->status,
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
                'cancellation_reason' => 'ปฏิเสธโดย SMS Checker: '.$reason,
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
     *
     * 🔒 SECURITY (2026-04-28): Strict SMS-required validation
     *   เคสบั๊กเก่า: แอป SMS Checker ส่ง approve มาโดยไม่มี SMS จริง
     *   → server dispatch job → mark paid → ลูกค้าได้คำทำนายฟรี
     *   Fix: บังคับต้องมี SMS ที่:
     *     a) ยอดตรง
     *     b) sms_timestamp >= reading.created_at (SMS มาหลังบิล)
     *     c) ยังไม่ถูก match กับบิลอื่น
     *   Admin override: ถ้าจริงๆ ต้องการ approve โดยไม่มี SMS → ต้องส่ง force=true ใน payload
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

        // 🛡️ (2026-08-12, owner "ห้ามมีบิลซ้อน") บิลนี้ถูกปิดไปแล้วเพราะบิลพี่น้องถูกจ่ายไปก่อน
        //   → อนุมัติซ้ำ = เงินก้อนเดียวถูกนับ 2 บิล + ส่งดวงซ้ำ. force ก็ข้ามไม่ได้
        //   เคสจริง: แอพยังโชว์บิล 39 ที่ถูกยกเลิกไป 2 นาทีก่อน (sync ส่ง completed มาด้วย)
        //   แอดมินกด force-approve → ปลุกทั้ง flow กลับมา (R11016/R11017 พรพรรณ 2026-08-12)
        //   ⚠️ บิลหมดอายุ/ยกเลิกที่ "ยังไม่มีใครจ่ายแทน" ไม่โดนด่านนี้ — โอนช้าแล้วตัดย้อนหลังยังทำได้ตามเดิม
        if (($supersededBy = $reading->supersededByPaidReading()) !== null) {
            Log::warning('🚫 SMS Payment: ปฏิเสธ approve — บิลนี้ถูกแทนด้วยบิลที่จ่ายแล้ว', [
                'fortune_reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'superseded_by_reading_id' => $supersededBy->id,
                'superseded_by_bill_reference' => $supersededBy->bill_reference,
                'device_id' => $device->device_id,
                'forced' => (bool) ($payload['force'] ?? false),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'บิลนี้ถูกยกเลิกไปแล้ว เพราะลูกค้าจ่ายบิล '.($supersededBy->bill_reference ?? '-')
                    .' แทน — อนุมัติซ้ำจะเป็นการเก็บเงินก้อนเดิมสองครั้ง',
                'error_code' => 'BILL_SUPERSEDED_BY_PAID',
                'data' => [
                    'superseded_by_bill_reference' => $supersededBy->bill_reference,
                    'superseded_by_amount' => (float) $supersededBy->amount_paid,
                    'hint' => 'ลูกค้าได้รับบริการจากบิลที่จ่ายแล้วเรียบร้อย — ไม่ต้องทำอะไรเพิ่ม',
                ],
            ], 409);
        }

        // 🔒 ค้นหา SMS notification ที่ valid สำหรับบิลนี้
        // กฎ: amount ตรง, มาหลังบิล, ยังไม่ถูก match
        $billAmount = (float) ($payload['amount'] ?? $reading->amount_paid);
        $reading_created = $reading->created_at;

        // 1. หา notification ที่ match กับ reading นี้แล้ว (idempotent retry)
        $notification = SmsPaymentNotification::where('matched_transaction_id', $reading->id)
            ->first();

        // 2. ถ้าไม่พบ → หา notification ที่ใช้ได้: ยอดตรง + มาหลังบิล + ยังว่าง
        if (! $notification && $billAmount > 0) {
            $notification = SmsPaymentNotification::where('amount', $billAmount)
                ->where('type', 'credit')
                ->whereIn('status', ['matched', 'pending'])
                ->whereNull('matched_transaction_id')  // 🔒 ยังไม่ถูก match
                ->where(function ($q) use ($reading_created) {
                    // 🔒 SMS ต้องมาหลัง bill ถูกสร้าง
                    $q->where('sms_timestamp', '>=', $reading_created)
                        ->orWhere('created_at', '>=', $reading_created);
                })
                ->orderBy('sms_timestamp', 'asc')
                ->first();
        }

        // 🔒 ถ้าไม่มี SMS valid → ปฏิเสธ (เว้นแต่จะส่ง force=true)
        $force = (bool) ($payload['force'] ?? false);
        if (! $notification && ! $force) {
            Log::warning('🚫 SMS Payment: ปฏิเสธ approve — ไม่มี SMS valid สำหรับบิลนี้', [
                'fortune_reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'amount' => $billAmount,
                'device_id' => $device->device_id,
                'reading_created' => $reading_created->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ SMS แจ้งเงินเข้าที่ตรงกับบิลนี้ หรือ SMS มาก่อนบิลถูกสร้าง — ไม่อนุมัติ',
                'error_code' => 'NO_VALID_SMS_FOR_BILL',
                'data' => [
                    'bill_amount' => $billAmount,
                    'bill_created' => $reading_created->toIso8601String(),
                    'hint' => 'รอ SMS แจ้งเงินเข้า หรือใช้ web admin panel เพื่อ override',
                ],
            ], 422);
        }

        // ⚠️ Force approve โดยไม่มี SMS — log critical สำหรับ audit
        if (! $notification && $force) {
            Log::critical('⚠️ SMS Payment: Force approve โดยไม่มี SMS', [
                'fortune_reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'amount' => $billAmount,
                'device_id' => $device->device_id,
                'admin_action' => 'force_approve_no_sms',
            ]);
        }

        // 🔮 Route ตาม reading_type — Celtic 99฿ ต่างจาก Deep 39฿ flow
        //    helper จะ dispatch ProcessDeepFortuneReadingJob (deep) หรือ
        //    call handleCelticPaymentMatched (celtic) — confirm payment เองในแต่ละ flow
        //    ⚠️ เคยมีบั๊ก: ProcessDeepFortuneReadingJob ทำงานกับ Celtic = สร้างคำทำนายผิด schema
        if (! $reading->is_paid) {
            $reading->confirmPayment($notification);
            $reading = $reading->fresh();
        }
        $this->dispatchFortuneApprovalFlow($reading, $notification);

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
            'amount' => $payload['amount'] ?? null,
            'device_id' => $device->device_id,
            'sms_notification_id' => $notification?->id,
            'force_used' => $force && ! $notification,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fortune reading approved and deep reading sent',
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
     * 🚫 (2026-07-27) ยกเลิกการอนุมัติบิลดูดวงจากแอพ (Void Approval)
     *
     * Use case: SlipOK อนุมัติผิด (สลิปปลอม/สลิปเก่า) หรือแอดมินกด Force ผิดบิล
     *   → บิลขึ้น "จ่ายแล้ว ✓" ทั้งที่ไม่ได้เงิน → ต้องถอยกลับเป็น "ยังไม่ได้ชำระ"
     *
     * ใช้ engine เดียวกับหน้าเว็บแอดมิน: FortuneReading::voidApproval()
     *   คืน UPA → cancelled, ปลด SMS notification, ดึง commission คืน,
     *   is_paid=false + status=COMPLETED + cancellation_reason='approval_voided'
     *   → แอพจะเห็นบิลเป็น "ยกเลิกการอนุมัติโดยแอดมิน" และกด Force อนุมัติใหม่ได้
     *
     * Guard: บิลที่ลูกค้าใช้บริการไปแล้ว (เปิดไพ่/ได้คำทำนาย) ต้องส่ง force=true
     *        (แอพจะถามยืนยันรอบสองก่อน) — ตรงกับ FortuneCelticCrossController::voidApproval
     */
    private function executeFortuneVoidAction(array $payload, FortuneReading $reading, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        $reason = trim((string) ($payload['reason'] ?? '')) ?: 'ยกเลิกการอนุมัติจากแอพ SMS Checker';
        $force = (bool) ($payload['force'] ?? false);

        // Idempotent: ยังไม่จ่าย = ไม่มีอะไรให้ถอย (เช่นกดซ้ำ / response timeout แล้ว retry)
        if (! $reading->is_paid) {
            return response()->json([
                'success' => true,
                'message' => 'บิลนี้อยู่ในสถานะยังไม่ได้ชำระอยู่แล้ว',
                'data' => ['order' => $this->transformFortuneReadingToOrderApproval($reading)],
            ]);
        }

        // ⚠️ ลูกค้าใช้บริการไปแล้ว → ต้องยืนยันซ้ำ (แอพส่ง force=true รอบสอง)
        $consumed = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
            ? ($reading->getCelticPickedCount() > 0 || (int) ($reading->celtic_questions_used ?? 0) > 0)
            : ! empty($reading->deep_response);

        if ($consumed && ! $force) {
            return response()->json([
                'success' => false,
                'message' => 'บิลนี้ลูกค้าใช้บริการไปแล้ว (เปิดไพ่/ได้คำทำนาย) — ยืนยันอีกครั้งถ้าแน่ใจว่าอนุมัติผิด',
                'error_code' => 'BILL_CONSUMED',
                'data' => ['consumed' => true],
            ], 422);
        }

        $result = $reading->voidApproval($reason, null);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'ยกเลิกการอนุมัติไม่สำเร็จ',
            ], 422);
        }

        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $reading = $reading->fresh();

        Log::critical('⛔ SMS Payment: Fortune approval VOIDED via app', [
            'fortune_reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'device_id' => $device->device_id,
            'reason' => $reason,
            'consumed' => $consumed,
            'force_used' => $force,
            'reverted' => $result['reverted'] ?? [],
            'warnings' => $result['warnings'] ?? [],
            'admin_action' => 'void_approval_from_app',
        ]);

        $message = 'ยกเลิกการอนุมัติแล้ว — คืนเป็น "ยังไม่ได้ชำระ"';
        if (! empty($result['warnings'])) {
            $message .= ' ⚠️ '.implode('; ', $result['warnings']);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['order' => $this->transformFortuneReadingToOrderApproval($reading)],
        ]);
    }

    /**
     * Legacy sync — redirect ไป syncOrders()
     *
     * Android app เวอร์ชันเก่าเรียก GET /sync แทน /orders/sync
     *
     * GET /api/v1/sms-payment/sync
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

    // ================================================================
    // 🔍 (2026-05-21) Orphan SMS → Bill Candidates (admin-side fuzzy match)
    //
    // Flow: admin เห็น orphan SMS ใน SmsChecker app → กด "หาบิลตรงกัน"
    //       → app POST /orphans/find-bill-candidates
    //       → backend return list ของ bills ที่ name+time+amount match
    //       → admin เลือก → app POST /orphans/confirm-match
    //       → backend approve + dispatch flow
    //
    // Rule (user spec 2026-05-21):
    //   - amount: ลูกค้าโอน >= bill.base_amount (ไม่ยอมขาด)
    //   - name fuzzy + time window
    //   - admin ยืนยันทุกครั้ง (ไม่ auto)
    // ================================================================

    /**
     * 🔎 POST /api/v1/sms-payment/orphans/find-bill-candidates
     *
     * รับ SMS info → return list ของบิลที่อาจตรงกัน (sorted by name_score desc)
     *
     * Body:
     *   - amount: float (จำนวนเงินใน SMS)
     *   - sender_name: string|null (ชื่อผู้โอน)
     *   - sms_timestamp: string (ISO 8601)
     *   - window_hours: int|null (default 24)
     */
    public function findBillCandidatesForOrphan(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->deviceCanAccessFortuneReading($device)) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin devices can search fortune bills',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'sender_name' => 'nullable|string|max:255',
            'sms_timestamp' => 'required|date',
            'window_hours' => 'nullable|integer|min:1|max:168', // max 1 week
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $amount = (float) $request->input('amount');
        $senderName = $request->input('sender_name');
        $smsTimestamp = \Carbon\Carbon::parse($request->input('sms_timestamp'));
        $windowHours = (int) $request->input('window_hours', 24);

        $matcher = app(\App\Services\Fortune\FortunePaymentFuzzyMatcher::class);
        $candidates = $matcher->findBillCandidatesForOrphan(
            $amount,
            $senderName,
            $smsTimestamp,
            $windowHours
        );

        $payload = array_map(function ($c) {
            /** @var FortuneReading $r */
            $r = $c['reading'];

            return [
                'bill_reference' => $r->bill_reference,
                'reading_id' => $r->id,
                'reading_type' => $r->reading_type,
                'customer_name' => $r->facebook_user_name,
                'expected_amount' => (float) ($r->uniquePaymentAmount?->unique_amount ?? $r->amount_paid),
                'base_price' => (float) ($r->uniquePaymentAmount?->base_amount ?? 0),
                'name_score' => $c['name_score'],
                'time_delta_minutes' => $c['time_delta_minutes'],
                'amount_delta' => $c['amount_delta'],
                'bill_created_at' => $r->created_at?->toIso8601String(),
                'platform' => $r->platform,
            ];
        }, $candidates);

        Log::info('SMS Payment: orphan bill-candidates lookup', [
            'device_id' => $device->device_id,
            'amount' => $amount,
            'sender' => $senderName,
            'candidates_count' => count($payload),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'candidates' => $payload,
                'count' => count($payload),
            ],
        ]);
    }

    /**
     * ✅ POST /api/v1/sms-payment/orphans/confirm-match
     *
     * Admin ยืนยัน: ผูก SMS เข้ากับบิล + approve + dispatch flow
     *
     * Body:
     *   - bill_reference: string (บิลที่เลือก)
     *   - sms_notification_id: int|null (SMS ที่จะผูก — null = no SMS link)
     */
    public function confirmOrphanMatch(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->deviceCanAccessFortuneReading($device)) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin devices can confirm bill matches',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'bill_reference' => 'required|string|max:50',
            'sms_notification_id' => 'nullable|integer|min:1',
            // 🤖 (2026-05-21) Smart mode flag — distinguish auto vs admin-confirmed
            'auto_smart' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $billRef = (string) $request->input('bill_reference');
        $smsId = $request->input('sms_notification_id');
        $autoSmart = (bool) $request->input('auto_smart', false);

        $reading = FortuneReading::where('bill_reference', $billRef)->first();
        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบบิล {$billRef}",
            ], 404);
        }

        $sms = $smsId
            ? SmsPaymentNotification::find((int) $smsId)
            : null;

        $matcher = app(\App\Services\Fortune\FortunePaymentFuzzyMatcher::class);
        $contextLabel = $autoSmart
            ? "device={$device->device_id} via Android SMART AUTO (no admin click)"
            : "device={$device->device_id} via Android orphan manual match";
        $ok = $matcher->confirmMatchByAdmin(
            $reading,
            $sms,
            $contextLabel
        );

        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => 'Confirm match ล้มเหลว — เช็ค log',
            ], 500);
        }

        // Dispatch flow ทำนาย/Celtic (เหมือน approveOrder)
        $reading = $reading->fresh();
        $dispatched = $this->dispatchFortuneApprovalFlow($reading, $sms);

        Log::warning('💎 SMS Payment: orphan confirm-match by admin', [
            'device_id' => $device->device_id,
            'bill_reference' => $billRef,
            'reading_id' => $reading->id,
            'sms_id' => $sms?->id,
            'dispatched' => $dispatched,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ผูก SMS เข้าบิลสำเร็จ + เริ่มส่งคำทำนาย',
            'data' => [
                'bill_reference' => $billRef,
                'reading_id' => $reading->id,
                'flow_dispatched' => $dispatched,
            ],
        ]);
    }
}
