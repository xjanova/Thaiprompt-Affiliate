<?php

namespace App\Services\Fortune;

use App\Jobs\DeliverTakeoverDeferredJob;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneTakeoverService;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🤫 TakeoverResumeService — ของที่ลูกค้าจ่ายเงินแล้วแต่ถูกพักไว้ระหว่างแอดมินเทคโอเวอร์
 *
 * กฎคู่กัน (เจ้าของ 2026-10-06 + rule_paid_bills_always_resume):
 *   - ระหว่างเทคโอเวอร์ บอทห้ามส่งอะไรเลย (TakeoverSendGuard)
 *   - แต่ของที่จ่ายแล้วต้องไม่หาย → จดไว้ใน `conversation_state.takeover_deferred` ของบิลนั้น
 *     แล้วส่ง **ครั้งเดียว ตามลำดับ** ตอนเทคโอเวอร์จบ (คืนงาน / หมดเวลา / ทักมาหลังหมดเวลา)
 *   - ของจุกจิก (ทวง/ping/ชวนซื้อ/nudge) ไม่พัก — ทิ้งเลย
 *   - ไม่ตอบคำถามที่แอดมินคุยไปแล้วซ้ำ — ข้อความที่ลูกค้าพิมพ์ระหว่างเทคโอเวอร์ไม่ถูก "เล่นซ้ำ"
 *     (แค่ park เป็นบริบท) · แอดมินเลือกได้ว่า "จัดการเองแล้ว ไม่ต้องส่ง" (deliver_deferred=false)
 *
 * รูปของ takeover_deferred: { <item>: { at, status?, payload?, options? } }
 */
final class TakeoverResumeService
{
    /** คำทำนาย Deep 39 ที่ AI ทำเสร็จแล้วแต่ยังไม่ได้ส่ง */
    public const ITEM_DEEP_READING = 'deep_reading';

    /** กล่องขอวันเกิด/ตั้งจิตหลังจ่าย 39 (pay-first) */
    public const ITEM_PAYFIRST_BIRTHDATE = 'payfirst_birthdate';

    /** กล่อง "ตัดบิลเรียบร้อย + เริ่มเปิดไพ่" ของ Celtic 99 */
    public const ITEM_CELTIC_START = 'celtic_start';

    /** ผลตรวจสลิป (SlipOK fallback) */
    public const ITEM_SLIP_RESULT = 'slip_result';

    /** กล่อง "แม่หมอกลับมาดูแลต่อ — เปิดไพ่/ถามต่อได้เลย" ของ Celtic ที่ค้างกลางทาง */
    public const ITEM_CELTIC_RESUME = 'celtic_resume';

    /** คำตอบ Celtic รายข้อที่ AI ตอบแล้วแต่ยังไม่ถึงลูกค้า (cron celtic-redeliver ส่งต่อหลังจบเทคโอเวอร์) */
    public const ITEM_CELTIC_ANSWERS = 'celtic_answers';

    /** บทสรุป Celtic ที่ยังไม่ถึงลูกค้า */
    public const ITEM_CELTIC_SUMMARY = 'celtic_summary';

    /** คำทำนายแบบบับเบิ้ลที่ส่งค้างครึ่งทาง (cron bubble-recover ส่งต่อหลังจบเทคโอเวอร์) */
    public const ITEM_BUBBLES = 'bubbles';

    /** สรุปเสียงคำทำนาย */
    public const ITEM_VOICE_SUMMARY = 'voice_summary';

    /** ลำดับการส่งตอนจบเทคโอเวอร์ — ของที่จ่ายเงินซื้อมาก่อน */
    public const ORDER = [
        self::ITEM_DEEP_READING,
        self::ITEM_PAYFIRST_BIRTHDATE,
        self::ITEM_CELTIC_START,
        self::ITEM_SLIP_RESULT,
        self::ITEM_CELTIC_RESUME,
        self::ITEM_CELTIC_ANSWERS,
        self::ITEM_CELTIC_SUMMARY,
        self::ITEM_BUBBLES,
        self::ITEM_VOICE_SUMMARY,
    ];

    /** ป้ายภาษาไทยสำหรับแอปแอดมิน */
    public const LABELS = [
        self::ITEM_DEEP_READING => 'คำทำนายเชิงลึก (39) รอส่ง',
        self::ITEM_PAYFIRST_BIRTHDATE => 'กล่องขอวันเกิดหลังจ่าย 39',
        self::ITEM_CELTIC_START => 'กล่องเริ่มเปิดไพ่ Celtic หลังจ่าย 99',
        self::ITEM_SLIP_RESULT => 'ผลตรวจสลิป',
        self::ITEM_CELTIC_RESUME => 'ชวนเปิดไพ่/ถามต่อ (Celtic ค้างกลางทาง)',
        self::ITEM_CELTIC_ANSWERS => 'คำตอบ Celtic ที่ยังไม่ถึงลูกค้า',
        self::ITEM_CELTIC_SUMMARY => 'บทสรุป Celtic รอส่ง',
        self::ITEM_BUBBLES => 'คำทำนายแบบบับเบิ้ลที่ค้างครึ่งทาง',
        self::ITEM_VOICE_SUMMARY => 'สรุปเสียงคำทำนาย',
    ];

    /** สถานะ Celtic ที่ลูกค้ายังต้องทำต่อ (เปิดไพ่ / ถามคำถาม) */
    private const CELTIC_INTERACTIVE_STATUSES = [
        FortuneReading::STATUS_CELTIC_PICKING,
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        FortuneReading::STATUS_CELTIC_QA_PROMPT,
    ];

    // ============================================================
    // พักของ
    // ============================================================

    /**
     * จดว่า "ของชิ้นนี้ของบิลนี้" ถูกพักไว้เพราะแอดมินเทคโอเวอร์อยู่
     *
     * เขียนแบบ JSON_SET คีย์เดียว (ไม่ทับ state ทั้งก้อน) + อัปเดตตัวแปรในหน่วยความจำของผู้เรียกด้วย
     * — กันผู้เรียกที่ถือ $reading เดิม setConversationState ทีหลังแล้วเขียนทับรายการพักหาย
     *
     * @param  array<string, mixed>  $meta  ข้อมูลประกอบ (payload/options/status) — ต้อง json_encode ได้
     */
    public static function defer(FortuneReading $reading, string $item, array $meta = []): void
    {
        try {
            $current = FortuneReading::query()->whereKey($reading->id)->value('conversation_state');
            $current = is_array($current) ? $current : [];
            $deferred = is_array($current['takeover_deferred'] ?? null) ? $current['takeover_deferred'] : [];

            // พักไว้แล้วและไม่มีข้อมูลใหม่ → ไม่ต้องเขียนซ้ำ (cron เรียกทุกนาทีระหว่างเทคโอเวอร์)
            if (isset($deferred[$item]) && $meta === []) {
                self::syncInMemory($reading, $deferred);

                return;
            }

            $deferred[$item] = array_merge(['at' => now()->toIso8601String()], $meta);

            DB::update(
                'UPDATE fortune_readings SET conversation_state = JSON_SET('.FortuneReading::STATE_OBJECT_SQL.", '$.takeover_deferred', JSON_EXTRACT(?, '$')) WHERE id = ?",
                [json_encode($deferred, JSON_UNESCAPED_UNICODE), $reading->id]
            );

            self::syncInMemory($reading, $deferred);

            Log::info('🤫 takeover_deferred: พักของที่จ่ายแล้วไว้ส่งตอนจบเทคโอเวอร์', [
                'reading_id' => $reading->id,
                'item' => $item,
            ]);
        } catch (\Throwable $e) {
            Log::error('🤫 takeover_deferred: จดของที่พักไม่สำเร็จ', [
                'reading_id' => $reading->id,
                'item' => $item,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * พัก "กล่องข้อความที่บอทจะส่ง" (ผลลัพธ์รูปเดียวกับ FortuneChannelManager::sendResponse) — ส่งซ้ำตอนจบเทคโอเวอร์
     * ถ้าสถานะบิลตอนนั้นยังเหมือนตอนพัก (เดินต่อไปแล้ว = กล่องนี้ล้าสมัย ไม่ส่ง)
     *
     * @param  array<string, mixed>  $result  ['action' => ..., 'message' => ..., ...]
     * @param  array<string, mixed>  $extra  options ของ sendResponse (message_tag ฯลฯ)
     */
    public static function deferResponse(FortuneReading $reading, string $item, array $result, array $extra = []): void
    {
        // ตัดของที่ serialize ไม่ได้ (โมเดล/อ็อบเจกต์) — ตอนส่งจริงใส่ reading สดกลับเข้าไป
        $payload = [];
        foreach ($result as $key => $value) {
            if ($key === 'reading' || is_object($value)) {
                continue;
            }
            $payload[$key] = $value;
        }

        $options = array_filter($extra, fn ($v) => is_scalar($v) || $v === null);

        self::defer($reading, $item, [
            'status' => $reading->conversation_status,
            'payload' => $payload,
            'options' => $options,
        ]);
    }

    /**
     * พักของที่จ่ายเงินแล้ว + แจ้งแอดมินครั้งแรกที่ของชิ้นนี้ถูกพัก (กันแจ้งซ้ำทุกนาทีจาก cron)
     *
     * @param  string  $what  คำอธิบายภาษาไทยในข้อความแจ้งแอดมิน
     * @param  array<string, mixed>|null  $result  กล่องที่จะส่ง (null = พักแบบไม่มีกล่อง เช่น คำทำนาย Deep)
     * @param  array<string, mixed>  $extra  options ของ sendResponse
     */
    public static function deferPaid(FortuneReading $reading, string $item, string $what, ?array $result = null, array $extra = []): void
    {
        $alreadyDeferred = false;
        try {
            $current = FortuneReading::query()->whereKey($reading->id)->value('conversation_state');
            $alreadyDeferred = is_array($current) && isset($current['takeover_deferred'][$item]);
        } catch (\Throwable $e) {
            // อ่านไม่ได้ = ถือว่ายังไม่เคยพัก (แจ้งแอดมินซ้ำได้ ดีกว่าเงียบ)
        }

        if ($result !== null) {
            self::deferResponse($reading, $item, $result, $extra);
        } elseif (! $alreadyDeferred) {
            self::defer($reading, $item);
        }

        if (! $alreadyDeferred) {
            self::notifyAdminPaidDuringTakeover($reading, $what);
        }
    }

    /**
     * พักของ + แจ้งแอดมิน (ช่องทางแจ้งเตือนแอดมิน ไม่ใช่โควตาลูกค้า) ว่าลูกค้าจ่ายแล้วระหว่างเทคโอเวอร์
     */
    public static function notifyAdminPaidDuringTakeover(FortuneReading $reading, string $what): void
    {
        ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::resolve($reading);

        try {
            app(FortuneChatLogService::class)->record(
                $platform,
                $userId,
                'system',
                '💰 ลูกค้าจ่ายแล้วระหว่างเทคโอเวอร์ — '.$what.' · บอทพักไว้ส่งตอนคืนงาน',
                ['by' => 'bot_suppressed']
            );
        } catch (\Throwable $e) {
            // best-effort
        }

        try {
            app(\App\Services\LineAlertService::class)->alertUnusualActivity('💰 ลูกค้าจ่ายแล้วระหว่างเทคโอเวอร์', [
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'platform' => $platform,
                'customer' => $reading->facebook_user_name,
                'what' => $what,
                'admin_action' => 'บอทพักไว้ — กดคืนงานเพื่อให้บอทส่ง หรือคืนงานแบบ "จัดการเองแล้ว"',
                'admin_panel' => url('/admin/takeover/'.$reading->id),
            ]);
        } catch (\Throwable $e) {
            Log::warning('🤫 แจ้งแอดมิน "จ่ายแล้วระหว่างเทคโอเวอร์" ไม่สำเร็จ (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * รายการของที่พักไว้ของลูกค้าคนนี้ (สำหรับแอปแอดมิน — ฟิลด์ deferred)
     *
     * @return array<int, array{reading_id:int,bill_reference:?string,item:string,label:string,at:?string}>
     */
    public static function deferredFor(string $platform, ?string $userId): array
    {
        $out = [];

        foreach (self::readingsWithDeferred($userId) as $reading) {
            $items = (array) $reading->getConversationState('takeover_deferred', []);
            foreach (self::ORDER as $item) {
                if (! isset($items[$item])) {
                    continue;
                }
                $out[] = [
                    'reading_id' => (int) $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'item' => $item,
                    'label' => self::LABELS[$item] ?? $item,
                    'at' => is_array($items[$item]) ? ($items[$item]['at'] ?? null) : null,
                ];
            }
        }

        return $out;
    }

    // ============================================================
    // จบเทคโอเวอร์
    // ============================================================

    /**
     * เรียกทุกครั้งที่เทคโอเวอร์ของบิลหนึ่งจบ (resume / หมดเวลา) — ส่งหรือทิ้งของที่พักของ "ลูกค้าคนนั้น"
     *
     * ลูกค้ายังถูกเทคโอเวอร์ด้วยบิลอื่นอยู่ → ไม่ทำอะไร (รอเทคโอเวอร์ตัวสุดท้ายจบ)
     */
    public static function afterTakeoverEnded(FortuneReading $reading, bool $deliver = true, ?int $adminId = null): void
    {
        try {
            ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::resolve($reading);
            if ($userId === '') {
                return;
            }

            if (TakeoverSendGuard::isTakenOver($platform, $userId)) {
                Log::info('🤫 takeover จบบนบิลนี้ แต่ลูกค้ายังถูกเทคโอเวอร์ด้วยบิลอื่น — ยังไม่ส่งของที่พัก', [
                    'reading_id' => $reading->id,
                ]);

                return;
            }

            if (self::readingsWithDeferred($userId)->isEmpty()) {
                return;
            }

            if (! $deliver) {
                self::discardDeferred($platform, $userId, $adminId);

                return;
            }

            // ส่งในคิว — ไม่ถ่วง request ของแอดมิน/cron/webhook (คำทำนายพร้อมรูปใช้เวลาหลายวินาที)
            // คิว tpix-default = คิวที่ worker หลักของ prod (fortune-worker-supervisor) ฟังแน่นอน
            // (fortune-deep มี worker เฉพาะตอน deploy.sh สตาร์ทให้ — ไม่การันตี)
            dispatch((new DeliverTakeoverDeferredJob($platform, $userId))->onQueue('tpix-default'));
        } catch (\Throwable $e) {
            Log::error('🤫 afterTakeoverEnded ล้ม — ของที่พักยังอยู่ (cron จะตามส่งได้)', [
                'reading_id' => $reading->id,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * ลูกค้าทักมาหลังเทคโอเวอร์หมดเวลาแต่ cron ยังไม่กวาด → ปิดให้ตอนนี้ (ส่งของที่พักตามขั้นตอน)
     */
    public static function finishExpiredFor(string $platform, string $userId): void
    {
        try {
            $expired = FortuneReading::query()
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->takeoverExpired()
                ->get();

            foreach ($expired as $reading) {
                app(FortuneTakeoverService::class)->expireOne($reading);
            }
        } catch (\Throwable $e) {
            Log::warning('🤫 finishExpiredFor ล้ม (non-blocking — cron จะกวาดให้)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * ส่งของที่พักไว้ทั้งหมดของลูกค้าคนนี้ — ครั้งเดียว ตามลำดับ (เรียกจาก DeliverTakeoverDeferredJob)
     *
     * @return array<int, string> รายการที่ส่ง/สั่งส่งไปแล้ว (ไว้ใน log/เทสต์)
     */
    public static function deliverDeferred(string $platform, string $userId): array
    {
        $done = [];

        // ระหว่างรอคิว แอดมินอาจเทคโอเวอร์ใหม่ → ปล่อยของที่พักไว้ตามเดิม (รอบหน้าค่อยส่ง)
        if (TakeoverSendGuard::isTakenOver($platform, $userId)) {
            Log::info('🤫 deliverDeferred: ลูกค้าถูกเทคโอเวอร์อีกรอบระหว่างรอคิว — เก็บของที่พักไว้ตามเดิม', [
                'platform' => $platform,
                'user_id' => $userId,
            ]);

            return $done;
        }

        foreach (self::readingsWithDeferred($userId) as $reading) {
            $items = self::claim($reading);
            if ($items === []) {
                continue; // อีก process หยิบไปแล้ว = ไม่ส่งซ้ำ
            }

            $reading->refresh();
            ['platform' => $rPlatform, 'user_id' => $rUserId] = FortuneRecipient::resolve($reading);

            foreach (self::ORDER as $item) {
                if (! array_key_exists($item, $items)) {
                    continue;
                }

                // กล่องเริ่มเปิดไพ่ถูกส่งแล้วในรอบนี้ = ไม่ต้องตามด้วยกล่อง "เปิดไพ่ต่อ" ซ้ำ
                if ($item === self::ITEM_CELTIC_RESUME && in_array(self::ITEM_CELTIC_START, $done, true)) {
                    continue;
                }

                try {
                    if (self::deliverItem($reading, $item, (array) $items[$item], $rPlatform, $rUserId)) {
                        $done[] = $item;
                    }
                } catch (\Throwable $e) {
                    Log::error('🤫 deliverDeferred: ส่งของที่พักไม่สำเร็จ (cron/ทักกลับยังตามส่งได้)', [
                        'reading_id' => $reading->id,
                        'item' => $item,
                        'error' => SafeLog::exceptionMessage($e),
                    ]);
                }
            }

            Log::info('✨ deliverDeferred: ส่งของที่พักระหว่างเทคโอเวอร์แล้ว', [
                'reading_id' => $reading->id,
                'items' => array_keys($items),
                'delivered' => $done,
            ]);
        }

        return $done;
    }

    /**
     * แอดมินเลือก "จัดการเองแล้ว ไม่ต้องส่ง" — ล้างรายการพัก + ตั้งธงว่าส่งแล้ว กัน cron ตามส่งซ้ำ
     */
    public static function discardDeferred(string $platform, string $userId, ?int $adminId = null): void
    {
        foreach (self::readingsWithDeferred($userId) as $reading) {
            $items = self::claim($reading);
            if ($items === []) {
                continue;
            }

            $reading->refresh();

            if (isset($items[self::ITEM_DEEP_READING])) {
                $reading->setConversationState('reading_sent_directly', true);
                $reading->setConversationState('reading_notification_sent', true);
                $reading->setConversationState('delivered_by_admin_takeover', true);
            }

            if (isset($items[self::ITEM_CELTIC_ANSWERS])) {
                FortuneCelticQuestion::query()
                    ->where('fortune_reading_id', $reading->id)
                    ->whereNotNull('answered_at')
                    ->whereNull('delivered_at')
                    ->update(['delivered_at' => now()]);
            }

            if (isset($items[self::ITEM_CELTIC_SUMMARY])) {
                $reading->setConversationState('celtic_summary_delivered', true);
                $reading->setConversationState('celtic_summary_delivered_at', now()->toIso8601String());
                $reading->setConversationState('celtic_summary_handled_by_admin', true);
            }

            if (isset($items[self::ITEM_BUBBLES])) {
                \App\Jobs\SendFortuneBubbleJob::clearPending((int) $reading->id);
            }

            try {
                FortuneTakeoverLog::create([
                    'fortune_reading_id' => $reading->id,
                    'user_id' => $adminId,
                    'action' => FortuneTakeoverLog::ACTION_MESSAGE,
                    'reason' => 'deferred_discarded',
                    'message' => 'แอดมินคืนงานแบบ "จัดการเองแล้ว" — ไม่ส่ง: '.implode(', ', array_keys($items)),
                    'platform' => $reading->platform,
                ]);
            } catch (\Throwable $e) {
                // audit เป็น best-effort
            }

            Log::info('🤫 discardDeferred: แอดมินจัดการเองแล้ว — ล้างของที่พัก', [
                'reading_id' => $reading->id,
                'items' => array_keys($items),
                'admin_id' => $adminId,
            ]);
        }
    }

    // ============================================================
    // ภายใน
    // ============================================================

    /**
     * บิลของลูกค้าคนนี้ที่มีของพักอยู่
     *
     * @return \Illuminate\Support\Collection<int, FortuneReading>
     */
    private static function readingsWithDeferred(?string $userId): \Illuminate\Support\Collection
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return collect();
        }

        return FortuneReading::query()
            ->where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
            ->whereNotNull('conversation_state')
            ->whereRaw("JSON_VALID(conversation_state) AND JSON_EXTRACT(conversation_state, '$.takeover_deferred') IS NOT NULL")
            ->orderBy('id')
            ->get();
    }

    /**
     * หยิบรายการพักออกจากบิลแบบ "ครั้งเดียว" (ล็อกแถว + ลบคีย์ในธุรกรรมเดียว) — ตัวที่ได้ [] = มีคนหยิบไปแล้ว
     *
     * @return array<string, mixed>
     */
    private static function claim(FortuneReading $reading): array
    {
        $items = DB::transaction(function () use ($reading) {
            $row = FortuneReading::query()->whereKey($reading->id)->lockForUpdate()->first();
            $items = $row ? $row->getConversationState('takeover_deferred', []) : [];
            if (! is_array($items) || $items === []) {
                return [];
            }

            DB::update(
                'UPDATE fortune_readings SET conversation_state = JSON_REMOVE('.FortuneReading::STATE_OBJECT_SQL.", '$.takeover_deferred') WHERE id = ?",
                [$reading->id]
            );

            return $items;
        });

        self::syncInMemory($reading, null);

        return $items;
    }

    /**
     * ส่งของหนึ่งชิ้น — true = ส่ง/สั่งส่งแล้ว
     */
    private static function deliverItem(FortuneReading $reading, string $item, array $meta, string $platform, string $userId): bool
    {
        switch ($item) {
            case self::ITEM_DEEP_READING:
                if (! $reading->is_paid || (bool) $reading->getConversationState('reading_sent_directly', false)) {
                    return false;
                }

                // คำสั่งเดียวกับเส้นหลักของ prod — มี deep_response แล้ว = ข้าม AI ส่งอย่างเดียว · มีล็อกกันส่งซ้ำในตัว
                Artisan::call('fortune:process-deep', [
                    'readingId' => (int) $reading->id,
                    'platform' => $platform,
                    'userId' => $userId,
                ]);

                return true;

            case self::ITEM_PAYFIRST_BIRTHDATE:
            case self::ITEM_CELTIC_START:
            case self::ITEM_SLIP_RESULT:
                return self::resendResponse($reading, $meta, $platform, $userId);

            case self::ITEM_CELTIC_RESUME:
                return self::sendCelticResumePrompt($reading, $platform, $userId);

            case self::ITEM_CELTIC_SUMMARY:
                Artisan::call('fortune:celtic-summary-redeliver', ['--reading' => (int) $reading->id]);

                return true;

            case self::ITEM_VOICE_SUMMARY:
                dispatch(new \App\Jobs\ProcessVoiceSummaryJob((int) $reading->id, $platform, $userId));

                return true;

            case self::ITEM_CELTIC_ANSWERS:
            case self::ITEM_BUBBLES:
                // cron ตามส่งของตัวเองอยู่แล้ว (celtic-redeliver / bubble-recover ทุกนาที) — ข้ามเทคโอเวอร์ระหว่างนั้น
                // ตอนนี้เทคโอเวอร์จบแล้ว รอบถัดไปของ cron จะส่ง (มีตัวนับ/ธงกันซ้ำของมันเอง)
                return true;
        }

        return false;
    }

    /**
     * ส่งกล่องที่พักไว้ซ้ำ — เฉพาะเมื่อสถานะบิลยังเหมือนตอนพัก (เดินต่อไปแล้ว = กล่องนี้ล้าสมัย)
     */
    private static function resendResponse(FortuneReading $reading, array $meta, string $platform, string $userId): bool
    {
        $payload = (array) ($meta['payload'] ?? []);
        if (empty($payload['action']) && empty($payload['message'])) {
            return false;
        }

        $wasStatus = $meta['status'] ?? null;
        if ($wasStatus !== null && $wasStatus !== $reading->conversation_status) {
            Log::info('🤫 resendResponse: สถานะบิลเปลี่ยนไปแล้ว — กล่องที่พักล้าสมัย ไม่ส่ง', [
                'reading_id' => $reading->id,
                'was' => $wasStatus,
                'now' => $reading->conversation_status,
            ]);

            return false;
        }

        $payload['reading'] = $reading;

        return (bool) (new FortuneChannelManager(FortuneTellingSetting::getSettings()))
            ->sendResponse($platform, $userId, $payload, (array) ($meta['options'] ?? []));
    }

    /**
     * กล่อง "แม่หมอกลับมาดูแลต่อแล้ว" ของ Celtic ที่ค้างกลางทาง — บอกขั้นถัดไปตามสถานะจริงของบิล
     */
    private static function sendCelticResumePrompt(FortuneReading $reading, string $platform, string $userId): bool
    {
        if (! $reading->is_paid || ! in_array($reading->conversation_status, self::CELTIC_INTERACTIVE_STATUSES, true)) {
            return false;
        }

        $name = $reading->facebook_user_name ?: 'เจ้าชะตา';

        if ($reading->conversation_status === FortuneReading::STATUS_CELTIC_PICKING) {
            $picked = count((array) $reading->getCelticCards());
            $next = $reading->getNextCelticPosition();
            $posName = $next ? (FortuneReading::CELTIC_POSITIONS[$next]['name'] ?? '') : '';

            $message = "✨ แม่หมอกลับมาดูแลต่อแล้วค่ะ คุณ{$name}\n\n"
                ."🃏 เปิดไปแล้ว {$picked}/10 ใบ"
                .($next ? " — ใบถัดไปคือใบที่ {$next}".($posName !== '' ? " [{$posName}]" : '') : '')
                ."\n🧘 ตั้งจิตนึกถึงสิ่งที่อยากรู้ แล้วพิมพ์ *'พร้อม'* เพื่อเปิดไพ่ต่อได้เลยค่ะ";
        } else {
            $message = "✨ แม่หมอกลับมาดูแลต่อแล้วค่ะ คุณ{$name}\n\n"
                .'💬 พิมพ์คำถามที่อยากรู้ต่อได้เลยนะคะ แม่หมอจะดูไพ่ชุดเดิมของคุณให้ค่ะ';
        }

        $sender = FortuneMessengerFactory::sender($platform, $userId, FortuneTellingSetting::getSettings());
        if ($sender === null) {
            return false;
        }

        // LINE: ของลูกค้าที่จ่ายแล้ว — ใช้ replyToken ที่ฝากไว้ก่อน (ฟรี) ไม่มีค่อย push
        if ($platform === FortuneRecipient::PLATFORM_LINE && $sender instanceof \App\Services\LineFortuneService) {
            $token = ReplyTokenVault::take($platform, $userId);
            if ($token && $sender->replyMessage($token, [['type' => 'text', 'text' => $message]])) {
                return true;
            }
        }

        return (bool) $sender->sendMessage($userId, $message, [
            'from_admin' => true,
            'message_tag' => 'POST_PURCHASE_UPDATE',
        ]);
    }

    /**
     * อัปเดต takeover_deferred ในหน่วยความจำของผู้เรียก (ไม่เขียน DB ซ้ำ) — null = ลบคีย์
     */
    private static function syncInMemory(FortuneReading $reading, ?array $deferred): void
    {
        try {
            $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];
            if ($deferred === null) {
                unset($state['takeover_deferred']);
            } else {
                $state['takeover_deferred'] = $deferred;
            }
            $reading->conversation_state = $state;
            $reading->syncOriginalAttribute('conversation_state');
        } catch (\Throwable $e) {
            // ไม่ critical — DB ถูกแล้ว
        }
    }
}
