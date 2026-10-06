<?php

namespace App\Services\AdminApp;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneRecipient;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Support\SafeLog;
use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 ปุ่ม "ทำนายซ้ำ" ของแอปแอดมิน — POST /api/admin/fortune/readings/{id}/retry
 *
 * เซิร์ฟเวอร์เลือกวิธีกู้เอง (แอปไม่ต้องรู้ว่าบิลค้างแบบไหน) — ทุกวิธีลอกจากปุ่มบนหน้าเว็บที่ใช้อยู่จริง:
 *   - recover_pay_first  Deep ที่ยังไม่มีวันเกิด → fortune:recover-paid-no-birthdate --id --force
 *                        (Admin\FortuneReadingsController::recoverPayFirstReading) — ขอวันเกิดใหม่
 *   - resend             Deep ที่สร้างคำทำนายแล้วแต่ยังไม่ถึงลูกค้า (reading_sent_directly = false)
 *                        (Admin\FortuneReadingsController::resendDeepReading) + ล็อกส่ง fortune:deep_deliver + ตั้งธงส่งแล้ว
 *   - regenerate         Deep ที่มีวันเกิด+คำถามครบ → ล้างคำทำนาย/ธงส่ง กลับเป็น paid แล้วสั่งงาน AI **หลังส่งคำตอบ**
 *                        (Admin\FortuneReadingsController::retryDeepReading) + รีเซ็ต auto_retry_count / failure_notified
 *                        ⚠️ ต้อง register_shutdown_function — dispatchSmart() เรียก fastcgi_finish_request + Artisan::call
 *                           ถ้าเรียกกลางคำขอ/ใน terminating() งานพังเงียบ (ดูคอมเมนต์ในเมธอดเว็บ)
 *                        🚫 Deep ที่ยังรอลูกค้ากรอก (collecting_questions / collecting_tarot) หรือไม่มีคำถาม = 409 AWAITING_CUSTOMER
 *                           (บังคับสร้าง = ได้คำทำนายข้ามการเปิดไพ่ หรือคำทำนายว่างเปล่าที่วนกลับมาค้างอีก)
 *   - celtic_recover     Celtic → เส้นบิลเดี่ยวของ Admin\FortuneCelticCrossController::emergencyRecoverAction
 *                        (ยังไม่จ่ายผ่าน state machine / ค้าง paid ยังไม่เปิดไพ่ → force-promote + พรอมต์ไพ่ใบแรก
 *                         · อื่น ๆ → ส่งข้อความกู้ ณ จุดเดิม)
 *   - celtic_regenerate  Celtic ค้าง celtic_generating (หรือ paid ที่เปิดไพ่ครบแล้ว) → ทำแบบ recoverStuckGenerating ของ
 *                        fortune:celtic-redeliver: เด้งเป็น awaiting + ธง celtic_regen_pending='1' ให้ cron ปั่นคำตอบใหม่
 *                        **ไม่ส่งข้อความหาลูกค้า** (เดิมส่ง "แม่หมอกำลังพิจารณา…" ลอย ๆ = push กล่องกำลังคิดบน LINE โดยไม่มีใครปั่นให้จริง)
 *
 * ด่าน (ตามลำดับ): จ่ายแล้ว → ไม่ใช่บิลจันทรา → **ไม่อยู่ในการเทคโอเวอร์ของแอดมิน** → งาน AI ไม่ได้วิ่งอยู่
 *   → ค้างจริง (StuckReadingFinder) หรือถูกส่งต่อเกิน 24 ชม. (admin_review_needed)
 *   → **ลูกค้าคนเดียวกันไม่มีบิลใหม่กว่าที่จ่ายแล้ว/ยังคุยอยู่** (409 NEWER_ACTIVE_BILL — ไม่งั้นเปิดบิลเก่าซ้อนบิลใหม่
 *     = สอง flow ชนกัน แบบเคส rule_reconcile_competing_bills_on_pay) → ล็อก admin_retry:{id} 120 วิ (กดซ้ำ = 429)
 *
 * บิลที่ถูกส่งต่อเกิน 24 ชม. มีธง admin_review_alerted ซึ่งทำให้บอท "ไม่เห็น" บิลนี้เป็นบทสนทนาที่ยังเปิด
 * (FortuneReading::scopeActiveConversation) — ถ้าไม่ล้าง ลูกค้าตอบวันเกิด/เลือกไพ่ต่อแล้วข้อความไม่ไหลเข้าบิลที่จ่ายแล้ว
 * (เสี่ยงขายบิลใหม่ = เก็บเงินซ้ำ) ⇒ ทุกการกู้ล้างธงนี้ · ถ้ายังค้างอีก fortune:expire-stuck-paid จะปักธง+แจ้งแอดมินใหม่เอง
 */
class StuckReadingRetrier
{
    /** คีย์ล็อกกันกดซ้ำ */
    public const LOCK_PREFIX = 'admin_retry:';

    /** อายุล็อกกันกดซ้ำ (วินาที) */
    public const LOCK_SECONDS = 120;

    /** @var Closure(Closure): void ตัวเลื่อนงานไปหลังส่งคำตอบ (เทสต์ฉีดตัวเก็บแทนได้) */
    private Closure $defer;

    public function __construct(?Closure $defer = null)
    {
        $this->defer = $defer ?? static function (Closure $job): void {
            register_shutdown_function($job);
        };
    }

    /**
     * สั่งกู้บิล — คืน [HTTP status, JSON body]
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function retry(FortuneReading $reading, ?int $adminId): array
    {
        $reading->refresh();
        $now = now();

        if (! $reading->is_paid) {
            return $this->refuse($reading, 422, 'NOT_PAID', 'บิลนี้ยังไม่ได้ชำระเงิน — สั่งทำนายซ้ำไม่ได้');
        }
        if ($reading->isJuntraBill()) {
            return $this->refuse($reading, 422, 'JUNTRA_BILL', 'บิลจากเว็บจันทรา — จันทราเป็นผู้ส่งคำทำนายเอง สั่งซ้ำจากที่นี่ไม่ได้');
        }
        if ($reading->isAdminTakenOver()) {
            return $this->refuse($reading, 409, 'ADMIN_TAKEOVER_ACTIVE', 'แอดมินคุมห้องนี้อยู่ — คืนให้บอทก่อนสั่งทำนายซ้ำ');
        }
        if (StuckReadingFinder::generationInFlight($reading)) {
            return $this->refuse($reading, 409, 'GENERATION_IN_FLIGHT', 'ระบบกำลังสร้าง/ส่งคำทำนายบิลนี้อยู่ — รอสักครู่แล้วค่อยเช็คใหม่');
        }

        $stuckReason = StuckReadingFinder::stuckReason($reading, $now);
        if ($stuckReason === null && ! StuckReadingFinder::hasEscalationFlag($reading)) {
            return $this->refuse($reading, 409, 'NOT_STUCK', 'บิลนี้ไม่ได้ค้าง — ระบบยังทำงานตามปกติ ไม่ต้องสั่งซ้ำ');
        }

        $userId = (string) ($reading->platform_user_id ?: ($reading->facebook_user_id ?: ''));
        if ($userId === '') {
            return $this->refuse($reading, 422, 'NO_RECIPIENT', 'ไม่พบไอดีลูกค้าของบิลนี้ — ส่งข้อความหาลูกค้าไม่ได้');
        }
        $platform = (string) ($reading->platform ?: FortuneRecipient::platformFromUserId($userId));

        // ลูกค้ามีบิลใหม่กว่าที่จ่ายแล้ว/ยังคุยอยู่ → ห้ามปลุกบิลเก่า (ธง admin_review_* คงไว้ตามเดิม)
        $newer = $this->newerActiveBill($reading);
        if ($newer !== null) {
            $ref = $newer->bill_reference ?: '#'.$newer->id;

            return $this->refuse(
                $reading,
                409,
                'NEWER_ACTIVE_BILL',
                "ลูกค้ามีบิลใหม่กว่า ({$ref}) ที่จ่ายแล้วหรือกำลังคุยอยู่ — ห้ามปลุกบิลนี้ซ้อน ให้คืนเงินหรือปิดบิลนี้แทนการทำนายซ้ำ"
            );
        }

        $action = $this->chooseAction($reading);
        if (is_array($action)) {
            // [status, code, message] = ปฏิเสธ
            return $this->refuse($reading, $action[0], $action[1], $action[2]);
        }

        // กดซ้ำ/กดพร้อมกันหลายเครื่อง — คำขอแรกเท่านั้นที่ได้ทำ
        if (! Cache::add(self::LOCK_PREFIX.$reading->id, $adminId ?? 0, self::LOCK_SECONDS)) {
            return $this->refuse($reading, 429, 'RETRY_COOLDOWN', 'เพิ่งสั่งทำนายซ้ำบิลนี้ไปแล้ว — รอ 2 นาทีก่อนสั่งอีกครั้ง');
        }

        Log::info('AdminApp retry: เริ่มกู้บิลค้าง', [
            'reading_id' => $reading->id,
            'action' => $action,
            'stuck_reason' => $stuckReason,
            'escalated' => StuckReadingFinder::hasEscalationFlag($reading),
            'admin_id' => $adminId,
        ]);

        try {
            [$ok, $delivered, $message] = match ($action) {
                'recover_pay_first' => $this->recoverPayFirst($reading, $adminId),
                'resend' => $this->resend($reading, $platform, $userId, $adminId),
                'regenerate' => $this->regenerate($reading, $platform, $userId, $adminId),
                'celtic_recover' => $this->celticRecover($reading, $platform, $userId, $adminId),
                'celtic_regenerate' => $this->celticRegenerate($reading, $adminId),
            };
        } catch (\Throwable $e) {
            // ล้มกลางทาง → ปลดล็อกให้กดใหม่ได้ทันที
            Cache::forget(self::LOCK_PREFIX.$reading->id);
            Log::error('AdminApp retry: กู้บิลไม่สำเร็จ', [
                'reading_id' => $reading->id,
                'action' => $action,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return [500, [
                'success' => false,
                'error_code' => 'RETRY_FAILED',
                'action' => $action,
                'message' => 'สั่งทำนายซ้ำไม่สำเร็จ — ลองใหม่อีกครั้ง หรือกู้จากหน้าเว็บ',
                'data' => $this->data($reading, $action, false, $stuckReason),
            ]];
        }

        return [$ok ? 200 : 502, [
            'success' => $ok,
            'action' => $action,
            'message' => $message,
            'data' => $this->data($reading->fresh() ?? $reading, $action, $delivered, $stuckReason),
        ]];
    }

    /**
     * เลือกวิธีกู้ — คืนชื่อวิธี หรือ [status, error_code, message] เมื่อไม่ควรทำอะไร
     *
     * @return string|array{0: int, 1: string, 2: string}
     */
    public function chooseAction(FortuneReading $reading): string|array
    {
        if ($reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
            $status = (string) $reading->conversation_status;
            if ($status === FortuneReading::STATUS_COMPLETED) {
                return [409, 'ALREADY_COMPLETED', 'บิล Celtic นี้ทำนายจบแล้ว — ไม่มีอะไรให้กู้'];
            }

            // AI ตอบค้าง (หรือค้าง paid ทั้งที่เปิดไพ่ครบแล้ว) → ให้ cron ปั่นคำตอบใหม่ ไม่ส่งข้อความลอย ๆ
            if ($status === FortuneReading::STATUS_CELTIC_GENERATING
                || ($status === FortuneReading::STATUS_PAID && $reading->getCelticPickedCount() >= 10)) {
                return 'celtic_regenerate';
            }

            return 'celtic_recover';
        }

        if ($reading->reading_type !== FortuneReading::READING_TYPE_DEEP) {
            return [422, 'UNSUPPORTED_PACKAGE', 'บิลแพคเกจนี้ไม่มีขั้นตอนทำนายซ้ำอัตโนมัติ — จัดการจากหน้าเว็บ'];
        }

        // มีคำทำนายแล้ว → ส่งซ้ำ/จบ (เช็คก่อนวันเกิด — เส้นขอวันเกิดใหม่ --force ล้าง deep_response ทิ้ง)
        if (trim((string) $reading->deep_response) !== '') {
            if (! $reading->getConversationState('reading_sent_directly', false)) {
                return 'resend';
            }

            return [409, 'ALREADY_DELIVERED', 'คำทำนายบิลนี้ส่งถึงลูกค้าแล้ว — ไม่ส่งซ้ำ (เปิดดูในแชทก่อน)'];
        }

        // Pay-First: ยังไม่มีวันเกิด = สร้างคำทำนายไม่ได้ → ขอวันเกิดใหม่ (ตรงกับด่านในปุ่มเว็บ retryDeepReading)
        if (empty($reading->birth_date)) {
            return 'recover_pay_first';
        }

        // ลูกค้ายังกรอกไม่จบ (คำถาม / เปิดไพ่) หรือไม่มีคำถามเลย → ห้ามบังคับสร้าง
        //   (ด่านเดียวกับ fortune:check-pending: ไม่มีคำถาม = ข้าม)
        $awaitingCustomer = in_array($reading->conversation_status, [
            FortuneReading::STATUS_COLLECTING_QUESTIONS,
            FortuneReading::STATUS_COLLECTING_TAROT,
        ], true);
        $hasQuestions = ! empty($reading->getCollectedQuestions()) || ! empty($reading->questions);
        if ($awaitingCustomer || ! $hasQuestions) {
            return [409, 'AWAITING_CUSTOMER', 'ลูกค้ายังให้ข้อมูลไม่ครบ (คำถาม/เปิดไพ่) — สร้างคำทำนายตอนนี้ไม่ได้ แนะนำให้เทคโอเวอร์แชทคุยกับลูกค้าแทน'];
        }

        return 'regenerate';
    }

    /**
     * บิลใหม่กว่าของลูกค้าคนเดียวกันที่จ่ายแล้ว หรือยังเป็นบทสนทนาที่เปิดอยู่ — null = ไม่มี
     *
     * ตัวตนลูกค้าชุดเดียวกับ FortuneConversationService::cancelCompetingPrePaymentReadings()
     * (facebook_user_id + platform_user_id — LINE เก็บ id เดียวกันทั้งสองคอลัมน์)
     */
    public function newerActiveBill(FortuneReading $reading): ?FortuneReading
    {
        $ids = array_values(array_unique(array_filter([
            (string) $reading->facebook_user_id,
            (string) $reading->platform_user_id,
        ], fn (string $id) => $id !== '')));
        if ($ids === []) {
            return null;
        }

        $newerPaid = FortuneReading::query()
            ->where('id', '>', $reading->id)
            ->where(function ($q) use ($ids) {
                $q->whereIn('facebook_user_id', $ids)->orWhereIn('platform_user_id', $ids);
            })
            ->where('is_paid', true)
            ->orderByDesc('id')
            ->first(['id', 'bill_reference']);
        if ($newerPaid !== null) {
            return $newerPaid;
        }

        // บทสนทนาที่ยังเปิด — นิยามเดียวกับที่บอทใช้หาบิลของลูกค้า (scopeActiveConversation)
        foreach ($ids as $id) {
            $active = FortuneReading::query()
                ->activeConversation($id)
                ->where('id', '>', $reading->id)
                ->orderByDesc('id')
                ->first(['id', 'bill_reference']);
            if ($active !== null) {
                return $active;
            }
        }

        return null;
    }

    // ────────────────────────────────────────────────────────────
    // วิธีกู้แต่ละแบบ — คืน [ok, delivered, ข้อความไทย]
    // ────────────────────────────────────────────────────────────

    /**
     * Deep ยังไม่มีวันเกิด — คำสั่งเดียวกับปุ่ม "🛟 ส่งขอวันเกิดใหม่" บนเว็บ
     *
     * @return array{0: bool, 1: bool|null, 2: string}
     */
    private function recoverPayFirst(FortuneReading $reading, ?int $adminId): array
    {
        $this->markRetried($reading, $adminId);

        Artisan::call('fortune:recover-paid-no-birthdate', [
            '--id' => $reading->id,
            '--force' => true,
        ]);
        $output = Artisan::output();

        if (str_contains($output, 'recover + push')) {
            return [true, true, 'ส่งข้อความขอวันเกิดให้ลูกค้าแล้ว — รอลูกค้าตอบ ระบบจะทำนายต่อเอง'];
        }
        if (str_contains($output, 'ใช้วันเกิดเดิม')) {
            return [true, null, 'พบวันเกิดเดิมของลูกค้า — เริ่มทำนายต่อให้แล้ว'];
        }
        if (str_contains($output, 'push ล้มเหลว')) {
            return [true, false, 'รีเซ็ตบิลให้รอวันเกิดแล้ว แต่ส่งข้อความไม่ออก (FB อาจเกิน 24 ชม.) — ลูกค้าทักกลับมาจะเข้าขั้นขอวันเกิดเอง'];
        }

        return [true, null, 'สั่งกู้แล้ว — ตรวจผลในหน้ารายละเอียดบิล'];
    }

    /**
     * Deep สร้างแล้วแต่ยังไม่ถึงลูกค้า — ส่งคำทำนายเดิมซ้ำ (ลอก resendDeepReading)
     *
     * เพิ่มจากเว็บ: ถือล็อกส่ง fortune:deep_deliver:{id} (key เดียวกับ fortune:process-deep / check-pending P2 —
     * กันส่งซ้อน) และตั้งธงส่งแล้วจากผลส่งจริง (ไม่งั้น check-pending P2 ส่งซ้ำอีกรอบ)
     *
     * @return array{0: bool, 1: bool, 2: string}
     */
    private function resend(FortuneReading $reading, string $platform, string $userId, ?int $adminId): array
    {
        $deliverLock = 'fortune:deep_deliver:'.$reading->id;
        if (! Cache::add($deliverLock, 1, 600)) {
            return [false, false, 'ระบบกำลังส่งคำทำนายบิลนี้อยู่ — รอสักครู่แล้วค่อยเช็คใหม่'];
        }

        // ส่งไม่ออก/โยน error กลางทาง → ปล่อยล็อกส่งใน finally (เดิมปล่อยเฉพาะ ! $sent — โยน error = ล็อกค้าง 10 นาที
        //   check-pending ข้ามบิลนี้ + ปุ่มนี้ตอบ GENERATION_IN_FLIGHT จนล็อกหมดอายุ) · ส่งสำเร็จ = ถือล็อกไว้ตามเดิม
        $sent = false;
        try {
            $this->markRetried($reading, $adminId);

            $cm = $this->channelManager();

            // ส่งผังดวงก่อน (ถ้ามี) — ล้มไม่เป็นไร
            if ($reading->reading_image_url) {
                try {
                    $cm->getPlatform($platform)?->sendImage($userId, $reading->reading_image_url);
                } catch (\Throwable $imgErr) {
                    Log::warning('AdminApp retry resend: ส่งรูปผังดวงไม่สำเร็จ', ['reading_id' => $reading->id, 'error' => SafeLog::exceptionMessage($imgErr)]);
                }
            }

            $sent = (bool) $cm->sendResponse($platform, $userId, [
                'action' => 'resend',
                'message' => $reading->deep_response,
            ], ['from_admin' => true]);
        } finally {
            if (! $sent) {
                Cache::forget($deliverLock);
            }
        }

        if (! $sent) {
            return [false, false, 'ส่งคำทำนายไม่ออก (แพลตฟอร์มปฏิเสธ — FB อาจเกิน 24 ชม. หรือโควตา LINE หมด) — ลูกค้าทักกลับมาจะได้รับเอง'];
        }

        // อ่านแถวใหม่ก่อนรวมธง — ระหว่างส่ง (หลายวินาที) webhook อาจเขียน conversation_state ไปแล้ว
        $reading->refresh();
        $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];
        $reading->update([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'conversation_state' => array_merge($state, [
                'reading_sent_directly' => true,
                'reading_notification_sent' => true,
                'delivered_by_push' => true,
                'reading_ready_sent' => true,
                'reading_ready_sent_at' => now()->toIso8601String(),
            ]),
        ]);

        return [true, true, 'ส่งคำทำนายให้ลูกค้าแล้ว'];
    }

    /**
     * Deep ที่ไม่มีคำทำนาย — ล้างแล้วสั่งสร้างใหม่หลังส่งคำตอบ (ลอก retryDeepReading)
     *
     * @return array{0: bool, 1: bool|null, 2: string}
     */
    private function regenerate(FortuneReading $reading, string $platform, string $userId, ?int $adminId): array
    {
        $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];

        $reading->update([
            'deep_response' => null,
            'ai_response' => null,
            'conversation_status' => FortuneReading::STATUS_PAID,
            'conversation_state' => array_merge($state, [
                // ธงส่ง — ถ้าไม่ล้าง Job จะข้ามการส่งเพราะคิดว่าส่งไปแล้ว (ดูคอมเมนต์ใน retryDeepReading)
                'reading_sent_directly' => false,
                'reading_notification_sent' => false,
                'reading_notification_attempted' => false,
                'reading_notification_retry_count' => 0,
                'reading_ready_sent' => false,
                'reading_ready_for_reply' => false,
                'delivered_by_push' => false,
                'delivered_by_reply_message' => false,
                'phase2_notify_retry_count' => 0,
                // ตัวนับของ fortune:check-pending — ครบ 5 แล้วมันจะไม่ช่วย retry อีก + แจ้งลูกค้าครั้งเดียว
                'auto_retry_count' => 0,
                'failure_notified' => false,
            ], $this->retriedFlags($adminId)),
        ]);

        $readingId = (int) $reading->id;
        ($this->defer)(function () use ($readingId, $platform, $userId) {
            try {
                ProcessDeepFortuneReadingJob::dispatchSmart($readingId, null, $platform, $userId);
            } catch (\Throwable $e) {
                Log::error('AdminApp retry: dispatch ล้มใน shutdown', [
                    'reading_id' => $readingId,
                    'error' => SafeLog::exceptionMessage($e),
                ]);
            }
        });

        return [true, null, 'เริ่มสร้างคำทำนายใหม่แล้ว — ระบบจะส่งให้ลูกค้าเองเมื่อเสร็จ (ประมาณ 1–2 นาที)'];
    }

    /**
     * Celtic — เส้นบิลเดี่ยวของปุ่ม Emergency Recovery บนเว็บ
     *
     * @return array{0: bool, 1: bool, 2: string}
     */
    private function celticRecover(FortuneReading $reading, string $platform, string $userId, ?int $adminId): array
    {
        $this->markRetried($reading, $adminId);

        $settings = FortuneTellingSetting::getSettings();
        $svc = new FortuneConversationService($settings);
        $cm = $this->channelManager($settings);

        $forcePromoted = false;
        $picked = $reading->getCelticPickedCount();
        if (in_array($reading->conversation_status, [
            FortuneReading::STATUS_NEW,
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            // ค้าง paid = mark-paid ตั้ง PAID แล้ว handleCelticPaymentMatched ล้ม — state machine ไม่เคยเดิน
            FortuneReading::STATUS_PAID,
        ], true) && $picked === 0) {
            // จ่ายแล้วแต่ state machine ไม่เดินต่อ (slip matcher เปลี่ยนสถานะไม่ครบ) → เข้าเส้นยืนยันการจ่ายใหม่
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT]);
            $response = $svc->onCelticPaymentConfirmed($reading->fresh());
            $forcePromoted = true;
        } else {
            if ($reading->conversation_status === FortuneReading::STATUS_PAID && $picked > 0) {
                // ค้าง paid ทั้งที่เปิดไพ่ไปบ้างแล้ว (ยังไม่ครบ 10) → กลับขั้นเปิดไพ่ ให้พรอมต์ resume ตรงกับสถานะจริง
                $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);
            }
            $response = $svc->buildCelticResumeResponse($reading->fresh(), false);
        }

        $response['message'] = "🔔 *ขออภัยที่ทำให้รอนะคะ*\n"
            ."ระบบกู้สถานะให้แล้ว — ดำเนินการต่อได้เลยค่ะ ⬇️\n\n"
            ."═══════════════════════\n\n"
            .($response['message'] ?? '');

        $sent = (bool) $cm->sendResponse($platform, $userId, $response, [
            'from_admin' => true,
            'message_tag' => 'POST_PURCHASE_UPDATE',
        ]);

        Log::info('AdminApp retry: Celtic Emergency Recovery (บิลเดี่ยว)', [
            'reading_id' => $reading->id,
            'platform' => $platform,
            'force_promoted' => $forcePromoted,
            'sent' => $sent,
            'admin_id' => $adminId,
        ]);

        if (! $sent) {
            return [false, false, 'กู้สถานะแล้ว แต่ส่งข้อความหาลูกค้าไม่ออก — ลูกค้าทักกลับมาจะทำต่อจากจุดเดิม'];
        }

        return [true, true, $forcePromoted
            ? 'กู้บิล Celtic แล้ว — ส่งพรอมต์เปิดไพ่ใบแรกให้ลูกค้าแล้ว'
            : 'กู้บิล Celtic แล้ว — ส่งข้อความให้ลูกค้าทำต่อจากจุดเดิมแล้ว'];
    }

    /**
     * Celtic ที่ AI ตอบค้าง — ทำแบบ FortuneCelticRedeliver::recoverStuckGenerating():
     * เด้งเป็น celtic_awaiting_question + ธง celtic_regen_pending='1' (ค่า '1'/'0' แบบเดียวกับ cron)
     * ให้ fortune:celtic-redeliver (ทุกนาที) ปั่นคำตอบของคำถามที่ค้างใหม่ แล้วส่งผ่านเส้น redeliver เดิม
     * ที่รู้เรื่องโควตา LINE / หน้าต่าง 24 ชม. อยู่แล้ว
     *
     * ไม่ส่งข้อความหาลูกค้าเลย (ไม่ push บน LINE) — delivered = false
     *
     * @return array{0: bool, 1: bool, 2: string}
     */
    private function celticRegenerate(FortuneReading $reading, ?int $adminId): array
    {
        $reading->refresh();
        $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];

        $reading->update([
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'conversation_state' => array_merge($state, ['celtic_regen_pending' => '1'], $this->retriedFlags($adminId)),
        ]);

        // cron จะปั่นได้จริงไหม (เงื่อนไขเดียวกับ regenerateOrphanAnswer: มีคำถามค้าง · ไพ่ครบ · ยังอยู่ในเวลาคุย)
        //   ไม่ได้ = อย่างน้อยปลดสถานะค้างให้ลูกค้าพิมพ์ถามต่อได้ — บอกแอดมินตามจริง
        $willRegenerate = false;
        try {
            $fresh = $reading->fresh() ?? $reading;
            $willRegenerate = \App\Models\FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                ->whereNull('answered_at')
                ->exists()
                && count($fresh->getCelticCards()) >= 10
                && $fresh->canAskMoreCeltic();
        } catch (\Throwable $e) {
            // ประเมินไม่ได้ — ถือว่าเข้าคิวแล้ว (cron ตัดสินเองอีกชั้น)
            $willRegenerate = true;
        }

        Log::info('AdminApp retry: Celtic ค้าง generating → ส่งเข้าคิวปั่นคำตอบใหม่ (celtic_regen_pending)', [
            'reading_id' => $reading->id,
            'admin_id' => $adminId,
            'will_regenerate' => $willRegenerate,
        ]);

        return [true, false, $willRegenerate
            ? 'ส่งบิลเข้าคิวปั่นคำตอบใหม่แล้ว — ระบบจะสร้างคำตอบที่ค้างและส่งให้ลูกค้าเองภายในไม่กี่นาที (ยังไม่ได้ส่งข้อความหาลูกค้า)'
            : 'ปลดสถานะค้างแล้ว ลูกค้าพิมพ์ถามต่อได้ — แต่ไม่มีคำถามค้างให้ปั่นใหม่ (หรือหมดเวลาคุยแล้ว) ยังไม่ได้ส่งข้อความหาลูกค้า'];
    }

    // ────────────────────────────────────────────────────────────
    // ตัวช่วย
    // ────────────────────────────────────────────────────────────

    /**
     * ตั้งธง "แอดมินสั่งกู้แล้ว" + ล้าง admin_review_alerted (ดูคอมเมนต์หัวคลาส) — อัปเดตครั้งเดียว
     */
    private function markRetried(FortuneReading $reading, ?int $adminId): void
    {
        $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];
        $reading->update(['conversation_state' => array_merge($state, $this->retriedFlags($adminId))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function retriedFlags(?int $adminId): array
    {
        return [
            'admin_review_alerted' => false,
            'admin_retry_at' => now()->toIso8601String(),
            'admin_retry_by' => $adminId,
        ];
    }

    protected function channelManager(?FortuneTellingSetting $settings = null): FortuneChannelManager
    {
        return new FortuneChannelManager($settings ?? FortuneTellingSetting::getSettings());
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function refuse(FortuneReading $reading, int $status, string $code, string $message): array
    {
        return [$status, [
            'success' => false,
            'error_code' => $code,
            'action' => null,
            'message' => $message,
            'data' => $this->data($reading, null, null, null),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function data(FortuneReading $reading, ?string $action, ?bool $delivered, ?string $stuckReason): array
    {
        return [
            'reading_id' => (int) $reading->id,
            'action' => $action,
            'delivered' => $delivered,
            'stuck_reason' => $stuckReason,
            'conversation_status' => $reading->conversation_status,
        ];
    }
}
