<?php

namespace App\Services\Fortune;

use App\Console\Commands\FortuneBubbleRecover;
use App\Jobs\DeliverTakeoverDeferredJob;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneTakeoverService;
use App\Services\LineFortuneService;
use App\Support\SafeLog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🤫 TakeoverResumeService — ของที่ลูกค้าจ่ายเงินแล้วแต่ถูกพักไว้ระหว่างแอดมินเทคโอเวอร์
 *
 * กฎคู่กัน (เจ้าของ 2026-10-06 + rule_paid_bills_always_resume):
 *   - ระหว่างเทคโอเวอร์ บอทห้ามส่งอะไรเลย (TakeoverSendGuard)
 *   - แต่ของที่จ่ายแล้วต้องไม่หาย → จดไว้ใน `conversation_state.takeover_deferred.<ชิ้น>` ของบิลนั้น
 *     แล้วส่ง **ตามลำดับ** ตอนเทคโอเวอร์จบ (คืนงาน / หมดเวลา / ทักมาหลังหมดเวลา)
 *   - ของจุกจิก (ทวง/ping/ชวนซื้อ/nudge) ไม่พัก — ทิ้งเลย
 *   - ไม่ตอบคำถามที่แอดมินคุยไปแล้ว — คำถามที่ค้างตอนจบเทคโอเวอร์ถูกล้าง (ไม่ตัดโควตา) [L6]
 *     แอดมินเลือกได้ว่า "จัดการเองแล้ว ไม่ต้องส่ง" (deliver_deferred=false)
 *
 * ## ส่งแบบไม่หาย (bug-hunt C2)
 * ลบรายการพัก "ทีละชิ้น หลังส่งถึงแล้ว" เท่านั้น (หรือชิ้นนั้นล้าสมัยจริง) — ส่งไม่สำเร็จ = เก็บไว้ + นับครั้ง
 * แล้ว job ลองใหม่แบบเว้นระยะ · แอดมินเทคโอเวอร์ซ้ำระหว่างส่ง = หยุดทันที ของที่เหลือรอจบรอบหน้า
 * ครบ MAX_ATTEMPTS = ยอมแพ้ (ติดธง gave_up + แจ้งแอดมิน) ของยังโชว์ในแอปแอดมิน
 *
 * รูปของแต่ละชิ้น: { at, status?, fp?, payload?, options?, attempts?, last_failed_at?, gave_up?, await_reply? }
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

    /** คำตอบ Celtic รายข้อที่ AI ตอบแล้วแต่ยังไม่ถึงลูกค้า */
    public const ITEM_CELTIC_ANSWERS = 'celtic_answers';

    /** บทสรุป Celtic ที่ยังไม่ถึงลูกค้า */
    public const ITEM_CELTIC_SUMMARY = 'celtic_summary';

    /** คำทำนายแบบบับเบิ้ลที่ส่งค้างครึ่งทาง */
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

    /** ผลการส่งของหนึ่งชิ้น */
    public const OUT_DELIVERED = 'delivered';

    /** ล้าสมัย/ส่งไปแล้วทางอื่น — ลบทิ้งได้ */
    public const OUT_STALE = 'stale';

    /** ส่งไม่สำเร็จ — เก็บไว้ลองใหม่ */
    public const OUT_FAILED = 'failed';

    /** ยังไม่ถึงเวลา (มีทางอื่นกำลังส่ง / รอ reply ฟรีก่อน) — เก็บไว้ ไม่นับครั้ง */
    public const OUT_LATER = 'later';

    /** LINE ไม่มี replyToken — จอดไว้แนบไปกับคำตอบถัดไป (ห้าม push) */
    public const OUT_AWAIT_REPLY = 'await_reply';

    /** ส่งไม่สำเร็จกี่ครั้งแล้วยอมแพ้ (แจ้งแอดมิน) */
    public const MAX_ATTEMPTS = 5;

    /** "กล่อง" ที่ต้องเช็คลายนิ้วมือสถานะก่อนส่งซ้ำ (เดินต่อไปแล้ว = ล้าสมัย) */
    private const BOX_ITEMS = [
        self::ITEM_PAYFIRST_BIRTHDATE,
        self::ITEM_CELTIC_START,
        self::ITEM_SLIP_RESULT,
        self::ITEM_CELTIC_RESUME,
    ];

    /** สถานะ Celtic ที่ลูกค้ายังต้องทำต่อ (เปิดไพ่ / ถามคำถาม) */
    private const CELTIC_INTERACTIVE_STATUSES = [
        FortuneReading::STATUS_CELTIC_PICKING,
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        FortuneReading::STATUS_CELTIC_QA_PROMPT,
    ];

    /** สถานะ Celtic ที่นาฬิกาถาม-ตอบกำลังเดิน */
    private const CELTIC_QA_STATUSES = [
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        FortuneReading::STATUS_CELTIC_QA_PROMPT,
    ];

    /** ล็อกการส่งต่อลูกค้า (วินาที) — ยาวกว่า timeout ของ job (80) */
    private const DELIVER_LOCK_SECONDS = 90;

    /** ไม่เริ่มชิ้นใหม่หลังใช้เวลาไปเท่านี้ (วินาที) — ที่เหลือต่อใน job ถัดไป (job timeout 80 < retry_after 90) */
    public const TIME_BUDGET_SECONDS = 50;

    /** LINE: ลูกค้าเพิ่งทักมาหลังหมดเวลา → ให้ทาง reply (ฟรี) ส่งคำทำนายก่อน job (วินาที) */
    private const PREFER_REPLY_SECONDS = 90;

    /** ในหน่วยความจำ: ลูกค้าที่มีกล่อง "แม่หมอกลับมาแล้ว" จอดรอแนบไปกับ reply ถัดไป (LINE) */
    private static array $linePrefixArmed = [];

    // ============================================================
    // พักของ
    // ============================================================

    /**
     * จดว่า "ของชิ้นนี้ของบิลนี้" ถูกพักไว้เพราะแอดมินเทคโอเวอร์อยู่
     *
     * เขียน JSON_SET ที่ `$.takeover_deferred.<ชิ้น>` ตัวเดียว (ไม่อ่าน-แก้-เขียนทั้งอ็อบเจกต์) — สองที่พักพร้อมกันไม่ทับกัน [L2]
     * ไม่มีข้อมูลใหม่ ($meta ว่าง) = พักแบบ "ถ้ายังไม่มี" (cron เรียกทุกนาทีระหว่างเทคโอเวอร์)
     * เขียนเสร็จแล้วลูกค้า "ไม่ได้ถูกเทคโอเวอร์แล้ว" (จบไประหว่างนั้น) → สั่งส่งเลย ไม่ปล่อยค้าง [L1]
     *
     * @param  array<string, mixed>  $meta  ข้อมูลประกอบ (payload/options/status/fp) — ต้อง json_encode ได้
     */
    public static function defer(FortuneReading $reading, string $item, array $meta = []): void
    {
        try {
            $value = array_merge(['at' => now()->toIso8601String()], $meta);
            $written = ConversationStateAtomic::setChild($reading, 'takeover_deferred', $item, $value, $meta === []);

            if ($written) {
                Log::info('🤫 takeover_deferred: พักของที่จ่ายแล้วไว้ส่งตอนจบเทคโอเวอร์', [
                    'reading_id' => $reading->id,
                    'item' => $item,
                ]);
            }

            self::deliverIfNoLongerTakenOver($reading, $item);
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
     * ถ้าลายนิ้วมือสถานะของบิลตอนนั้นยังเหมือนตอนพัก (ลูกค้าเดินต่อไปแล้ว = กล่องนี้ล้าสมัย ไม่ส่ง) [L8]
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
            'fp' => self::stateFingerprint($reading),
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
            $alreadyDeferred = ConversationStateAtomic::fresh($reading, 'takeover_deferred.'.$item) !== null;
        } catch (\Throwable $e) {
            // อ่านไม่ได้ = ถือว่ายังไม่เคยพัก (แจ้งแอดมินซ้ำได้ ดีกว่าเงียบ)
        }

        if ($result !== null) {
            self::deferResponse($reading, $item, $result, $extra);
        } else {
            self::defer($reading, $item); // พักแบบ "ถ้ายังไม่มี" — cron เรียกซ้ำทุกนาทีระหว่างเทคโอเวอร์
        }

        if (! $alreadyDeferred) {
            self::notifyAdminPaidDuringTakeover($reading, $what);
        }
    }

    /**
     * แจ้งแอดมิน (ช่องทางแจ้งเตือนแอดมิน ไม่ใช่โควตาลูกค้า) ว่าลูกค้าจ่ายแล้วระหว่างเทคโอเวอร์
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
     * @return array<int, array<string, mixed>>
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
                $meta = is_array($items[$item]) ? $items[$item] : [];
                $out[] = [
                    'reading_id' => (int) $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'item' => $item,
                    'label' => self::LABELS[$item] ?? $item,
                    'at' => $meta['at'] ?? null,
                    // ฟิลด์เพิ่ม (additive) — แอปเก่าไม่อ่านก็ไม่เป็นไร
                    'attempts' => (int) ($meta['attempts'] ?? 0),
                    'gave_up' => (bool) ($meta['gave_up'] ?? false),
                    'awaiting_customer_reply' => (bool) ($meta['await_reply'] ?? false),
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
     * ลูกค้ายังถูกเทคโอเวอร์ด้วยบิลอื่นอยู่ → จำเวลาเริ่มไว้ แล้วรอเทคโอเวอร์ตัวสุดท้ายจบ
     *
     * @param  array{ended?: bool, episode_start?: ?CarbonInterface, episode_end?: ?CarbonInterface, clear_pending?: bool, inbound?: bool}  $opts
     *                                                                                                                                             ended: เทคโอเวอร์เพิ่งจบจริงในการเรียกนี้ (false = แค่ตามส่งของค้าง)
     *                                                                                                                                             clear_pending: ล้างคำถามที่ค้างตอบ (L6 — ค่าเริ่มต้น true)
     *                                                                                                                                             inbound: ลูกค้าเพิ่งทักมา (LINE ให้ทาง reply ฟรีส่งคำทำนายก่อน)
     */
    public static function afterTakeoverEnded(FortuneReading $reading, bool $deliver = true, ?int $adminId = null, array $opts = []): void
    {
        try {
            ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::resolve($reading);
            if ($userId === '') {
                return;
            }

            $ended = (bool) ($opts['ended'] ?? false);
            $episodeStart = $opts['episode_start'] ?? null;

            if (TakeoverSendGuard::isTakenOver($platform, $userId, true)) {
                if ($ended && $episodeStart) {
                    self::stashEpisodeStart($userId, $episodeStart);
                }

                Log::info('🤫 takeover จบบนบิลนี้ แต่ลูกค้ายังถูกเทคโอเวอร์ด้วยบิลอื่น — ยังไม่ส่งของที่พัก', [
                    'reading_id' => $reading->id,
                ]);

                return;
            }

            if ($ended) {
                $episodeStart = self::pullEpisodeStart($userId, $episodeStart);
                $episodeEnd = $opts['episode_end'] ?? now();

                // ⏸️ [L11] เวลาถาม-ตอบที่ลูกค้าจ่ายมา "หยุดเดิน" ระหว่างแอดมินคุย — เลื่อนนาฬิกาออกไปเท่าช่วงที่ทับกัน
                if ($episodeStart) {
                    self::shiftSessionClocks($userId, Carbon::instance($episodeStart), Carbon::instance($episodeEnd));
                }

                // 🧹 [L6] คำถามที่ค้างอยู่ตอนจบเทคโอเวอร์ = แอดมินคุยเรื่องนั้นไปแล้ว → ห้าม AI ตอบซ้ำ (ไม่ตัดโควตา)
                if (($opts['clear_pending'] ?? true) === true) {
                    self::clearPendingQuestions($platform, $userId);
                }
            }

            if (self::readingsWithDeferred($userId)->isEmpty()) {
                return;
            }

            if (! $deliver) {
                self::discardDeferred($platform, $userId, $adminId);

                return;
            }

            // LINE: ลูกค้าเพิ่งทักมา (มี replyToken สด) → ให้ทาง reply (ฟรี) ส่งคำทำนายก่อน job ห้ามส่งซ้อน [L9]
            if (! empty($opts['inbound']) && $platform === FortuneRecipient::PLATFORM_LINE) {
                try {
                    Cache::put(self::preferReplyKey($userId), 1, self::PREFER_REPLY_SECONDS);
                } catch (\Throwable $e) {
                    // ไม่มี cache = job ส่งเอง (มีล็อกกันซ้อนอยู่แล้ว)
                }
            }

            self::dispatchDelivery($platform, $userId);
        } catch (\Throwable $e) {
            Log::error('🤫 afterTakeoverEnded ล้ม — ของที่พักยังอยู่ (ตัวกวาดจะตามส่ง)', [
                'reading_id' => $reading->id,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * ลูกค้าทักมาหลังเทคโอเวอร์หมดเวลาแต่ cron ยังไม่กวาด → ปิดให้ตอนนี้ (ส่งของที่พักตามขั้นตอน)
     *
     * @param  iterable<FortuneReading>|null  $expired  บิลที่หมดเวลาแล้ว (ส่งมาจากคิวรีรวมของ TakeoverIngress) — null = หาเอง
     */
    public static function finishExpiredFor(string $platform, string $userId, ?iterable $expired = null, bool $inbound = false): void
    {
        try {
            $expired ??= self::customerQuery($userId)->takeoverExpired()->get();

            foreach ($expired as $reading) {
                app(FortuneTakeoverService::class)->expireOne($reading, $inbound);
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
     * สั่ง job ส่งของที่พัก (กันสั่งซ้ำรัว ๆ ภายใน 60 วิ — มีล็อกต่อลูกค้าอีกชั้นใน job)
     *
     * คิว tpix-default = คิวที่ worker หลักของ prod (fortune-worker-supervisor) ฟังแน่นอน
     */
    public static function dispatchDelivery(string $platform, string $userId, int $delaySeconds = 0, int $hop = 0): void
    {
        try {
            if ($hop === 0 && ! Cache::add(self::dispatchKey($userId), 1, 60)) {
                return; // เพิ่งสั่งไป — job ตัวนั้นจะเก็บให้ครบ
            }
        } catch (\Throwable $e) {
            // ไม่มี cache = สั่งเลย (ล็อกใน job กันซ้อนอยู่แล้ว)
        }

        $job = (new DeliverTakeoverDeferredJob($platform, $userId, $hop))->onQueue('tpix-default');
        if ($delaySeconds > 0) {
            $job->delay(now()->addSeconds($delaySeconds));
        }

        dispatch($job);
    }

    /**
     * job ส่งเสร็จรอบหนึ่งแล้ว — เปิดให้สั่งรอบใหม่ได้ทันที (เทคโอเวอร์รอบถัดไปจบภายใน 60 วิ ต้องไม่ถูกกันทิ้ง)
     */
    public static function releaseDispatchKey(string $userId): void
    {
        try {
            Cache::forget(self::dispatchKey($userId));
        } catch (\Throwable $e) {
            // หมดอายุเองใน 60 วิ
        }
    }

    /**
     * ส่งของที่พักไว้ทั้งหมดของลูกค้าคนนี้ — ตามลำดับ ทีละชิ้น ลบทีละชิ้นหลังส่งถึง (เรียกจาก DeliverTakeoverDeferredJob)
     *
     * @param  float|null  $deadline  microtime(true) ที่ห้ามเริ่มชิ้นใหม่หลังจากนี้ (null = ไม่จำกัด)
     * @return array{locked: bool, blocked: bool, incomplete: bool, delivered: array<int,string>, stale: array<int,string>, failed: array<int,string>, later: array<int,string>, awaiting: array<int,string>, retry_in: ?int}
     */
    public static function deliverDeferred(string $platform, string $userId, ?float $deadline = null): array
    {
        $report = [
            'locked' => false, 'blocked' => false, 'incomplete' => false,
            'delivered' => [], 'stale' => [], 'failed' => [], 'later' => [], 'awaiting' => [],
            'retry_in' => null,
        ];

        $lock = null;
        try {
            $lock = Cache::lock(self::deliverLockKey($userId), self::DELIVER_LOCK_SECONDS);
            if (! $lock->get()) {
                $report['locked'] = true; // อีกตัวกำลังส่งให้ลูกค้าคนนี้อยู่

                return $report;
            }
        } catch (\Throwable $e) {
            $lock = null; // ไม่มี cache lock = ส่งต่อ (ของแต่ละชิ้นมีด่านกันซ้ำของตัวเอง)
        }

        try {
            foreach (self::readingsWithDeferred($userId) as $reading) {
                $items = (array) $reading->getConversationState('takeover_deferred', []);
                ['platform' => $rPlatform, 'user_id' => $rUserId] = FortuneRecipient::resolve($reading);

                // ส่งกล่อง "เริ่มเปิดไพ่" ไปแล้วรอบนี้ = ไม่ต้องตามด้วย "เปิดไพ่ต่อ" ซ้ำ
                $startDelivered = false;

                foreach (self::ORDER as $item) {
                    if (! array_key_exists($item, $items)) {
                        continue;
                    }
                    $meta = is_array($items[$item]) ? $items[$item] : [];

                    if (! empty($meta['gave_up']) || ! empty($meta['await_reply'])) {
                        continue; // ยอมแพ้แล้ว (รอแอดมิน) / จอดรอแนบไปกับ reply ถัดไป (LINE)
                    }

                    // แอดมินเทคโอเวอร์ซ้ำระหว่างส่ง → หยุดทันที ของที่เหลือรอจบรอบหน้า
                    if (TakeoverSendGuard::isTakenOver($platform, $userId, true)) {
                        $report['blocked'] = true;

                        return $report;
                    }

                    if ($deadline !== null && microtime(true) >= $deadline) {
                        $report['incomplete'] = true;

                        return $report;
                    }

                    if ($item === self::ITEM_CELTIC_RESUME && $startDelivered) {
                        $outcome = self::OUT_STALE;
                    } else {
                        try {
                            $reading->refresh();
                            $outcome = self::deliverItem($reading, $item, $meta, $rPlatform, $rUserId);
                        } catch (\Throwable $e) {
                            Log::error('🤫 deliverDeferred: ส่งของที่พักล้ม (จะลองใหม่)', [
                                'reading_id' => $reading->id,
                                'item' => $item,
                                'error' => SafeLog::exceptionMessage($e),
                            ]);
                            $outcome = self::OUT_FAILED;
                        }
                    }

                    // ส่งไม่ออกเพราะแอดมินเทคโอเวอร์ซ้ำพอดี = ไม่ใช่ความล้มเหลว (เก็บไว้รอจบรอบหน้า)
                    if ($outcome === self::OUT_FAILED && TakeoverSendGuard::isTakenOver($platform, $userId, true)) {
                        $report['blocked'] = true;

                        return $report;
                    }

                    self::applyOutcome($reading, $item, $meta, $outcome, $report);

                    if ($item === self::ITEM_CELTIC_START && $outcome === self::OUT_DELIVERED) {
                        $startDelivered = true;
                    }
                }

                ConversationStateAtomic::removeIfEmpty($reading, 'takeover_deferred');

                Log::info('✨ deliverDeferred: จัดการของที่พักระหว่างเทคโอเวอร์', [
                    'reading_id' => $reading->id,
                    'delivered' => $report['delivered'],
                    'failed' => $report['failed'],
                    'later' => $report['later'],
                ]);
            }
        } finally {
            try {
                $lock?->release();
            } catch (\Throwable $e) {
                // หมดอายุเองใน 90 วิ
            }
        }

        return $report;
    }

    /**
     * แอดมินเลือก "จัดการเองแล้ว ไม่ต้องส่ง" — ล้างรายการพัก + ตั้งธงว่าส่งแล้ว กัน cron ตามส่งซ้ำ
     */
    public static function discardDeferred(string $platform, string $userId, ?int $adminId = null): void
    {
        foreach (self::readingsWithDeferred($userId) as $reading) {
            $items = (array) $reading->getConversationState('takeover_deferred', []);
            if ($items === []) {
                continue;
            }

            if (isset($items[self::ITEM_DEEP_READING])) {
                ConversationStateAtomic::set($reading, 'reading_sent_directly', true);
                ConversationStateAtomic::set($reading, 'reading_notification_sent', true);
                ConversationStateAtomic::set($reading, 'delivered_by_admin_takeover', true);
            }

            if (isset($items[self::ITEM_CELTIC_ANSWERS])) {
                FortuneCelticQuestion::query()
                    ->where('fortune_reading_id', $reading->id)
                    ->whereNotNull('answered_at')
                    ->whereNull('delivered_at')
                    ->update(['delivered_at' => now()]);
            }

            if (isset($items[self::ITEM_CELTIC_SUMMARY])) {
                ConversationStateAtomic::set($reading, 'celtic_summary_delivered', true);
                ConversationStateAtomic::set($reading, 'celtic_summary_delivered_at', now()->toIso8601String());
                ConversationStateAtomic::set($reading, 'celtic_summary_handled_by_admin', true);
            }

            if (isset($items[self::ITEM_BUBBLES])) {
                ConversationStateAtomic::remove($reading, 'bubble_pending', 'bubble_pending_at');
            }

            ConversationStateAtomic::remove($reading, 'takeover_deferred');

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

        self::disarmLinePrefixIfNothingLeft($userId);
    }

    /**
     * 🧹 [L1] ตัวกวาด: ของที่พักค้างของลูกค้าที่ "ไม่ได้ถูกเทคโอเวอร์แล้ว" → สั่งส่ง (เรียกจาก fortune:expire-conversations)
     *
     * กันเคส: พักของหลังจากเทคโอเวอร์จบไปแล้ว (แข่งกัน) / job ส่งตาย / ลองครบรอบของ job แล้วยังไม่ออก
     *
     * @return int จำนวนลูกค้าที่สั่งส่ง
     */
    public static function sweepStuckDeferred(int $limit = 200): int
    {
        try {
            $rows = FortuneReading::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->where('conversation_state', 'like', '%takeover_deferred%')
                ->whereRaw("JSON_VALID(conversation_state) AND JSON_LENGTH(JSON_EXTRACT(conversation_state, '$.takeover_deferred')) > 0")
                ->orderBy('id')
                ->limit($limit)
                ->get(['id', 'platform', 'platform_user_id', 'facebook_user_id', 'conversation_state']);
        } catch (\Throwable $e) {
            Log::error('🧹 sweepStuckDeferred: คิวรีล้ม', ['error' => SafeLog::exceptionMessage($e)]);

            return 0;
        }

        // เฉพาะลูกค้าที่มี "ชิ้นที่ยังส่งได้" (ไม่นับที่ยอมแพ้แล้ว / จอดรอ reply ถัดไป)
        $customers = [];
        foreach ($rows as $row) {
            $items = (array) $row->getConversationState('takeover_deferred', []);
            $actionable = array_filter($items, fn ($m) => is_array($m) && empty($m['gave_up']) && empty($m['await_reply']));
            if ($actionable === []) {
                continue;
            }
            ['platform' => $p, 'user_id' => $u] = FortuneRecipient::resolve($row);
            if ($u !== '') {
                $customers[$u] = $p;
            }
        }

        if ($customers === []) {
            return 0;
        }

        $takenOver = TakeoverSendGuard::takenOverUserIds(array_keys($customers));
        $count = 0;
        foreach ($customers as $userId => $platform) {
            if (isset($takenOver[$userId])) {
                continue; // ยังคุยกับแอดมินอยู่ — รอจบ
            }
            self::dispatchDelivery($platform, (string) $userId);
            $count++;
        }

        if ($count > 0) {
            Log::warning('🧹 sweepStuckDeferred: เจอของที่พักค้างหลังจบเทคโอเวอร์ — สั่งส่ง', ['customers' => $count]);
        }

        return $count;
    }

    // ============================================================
    // LINE: กล่อง "แม่หมอกลับมาแล้ว" แนบไปกับ reply ถัดไป (ห้าม push) [L7]
    // ============================================================

    /** จำว่าลูกค้าคนนี้มีกล่องจอดรอแนบไปกับ reply ถัดไป */
    public static function armLineReplyPrefix(string $userId): void
    {
        self::$linePrefixArmed[$userId] = true;
        try {
            Cache::put(self::linePrefixKey($userId), 1, now()->addDays(3));
        } catch (\Throwable $e) {
            // ไม่มี cache — TakeoverIngress ติดธงให้ใหม่ตอนลูกค้าทัก (อ่านจาก DB)
        }
    }

    /** มีกล่องจอดรอไหม (เช็คเร็ว — ไม่แตะ DB) */
    public static function lineReplyPrefixArmed(string $userId): bool
    {
        if (isset(self::$linePrefixArmed[$userId])) {
            return true;
        }

        try {
            return Cache::has(self::linePrefixKey($userId));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * หยิบกล่องที่จอดรอ (ลบออกจากรายการพักแบบมีเงื่อนไข = หยิบได้คนเดียว) — null = ไม่มี/ล้าสมัย
     *
     * @return array{reading_id:int, meta:array<string,mixed>, message:array{type:string,text:string}}|null
     */
    public static function takeLineReplyPrefix(string $userId): ?array
    {
        try {
            foreach (self::readingsWithDeferred($userId) as $reading) {
                $meta = $reading->getConversationState('takeover_deferred', [])[self::ITEM_CELTIC_RESUME] ?? null;
                if (! is_array($meta) || empty($meta['await_reply'])) {
                    continue;
                }

                if (! ConversationStateAtomic::removeChildIf($reading, 'takeover_deferred', self::ITEM_CELTIC_RESUME, 'at', (string) ($meta['at'] ?? ''))) {
                    continue; // อีกที่หยิบไปแล้ว
                }
                ConversationStateAtomic::removeIfEmpty($reading, 'takeover_deferred');

                $text = self::celticResumeText($reading, true);
                if ($text === null) {
                    continue; // ลูกค้าเดินต่อไปแล้ว — ไม่ต้องบอก
                }

                ConversationStateAtomic::set($reading, 'takeover_resume_sent_at', now()->toIso8601String());

                return [
                    'reading_id' => (int) $reading->id,
                    'meta' => $meta,
                    'message' => ['type' => 'text', 'text' => $text],
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('🤫 takeLineReplyPrefix ล้ม (non-blocking)', ['error' => SafeLog::exceptionMessage($e)]);
        } finally {
            self::disarmLinePrefixIfNothingLeft($userId);
        }

        return null;
    }

    /** reply ที่แนบกล่องไปส่งไม่ออก → จอดกล่องกลับคืน */
    public static function restoreLineReplyPrefix(string $userId, array $taken): void
    {
        try {
            $meta = (array) ($taken['meta'] ?? []);
            $meta['await_reply'] = true;
            ConversationStateAtomic::setChild((int) $taken['reading_id'], 'takeover_deferred', self::ITEM_CELTIC_RESUME, $meta, true);
            self::armLineReplyPrefix($userId);
        } catch (\Throwable $e) {
            // ของประดับ — หายได้
        }
    }

    // ============================================================
    // ภายใน — ส่งของทีละชิ้น
    // ============================================================

    /**
     * ส่งของหนึ่งชิ้น — คืนผล (OUT_*)
     */
    private static function deliverItem(FortuneReading $reading, string $item, array $meta, string $platform, string $userId): string
    {
        // กล่อง: ลูกค้าเดินต่อไปแล้ว (ลายนิ้วมือสถานะเปลี่ยน) / กล่องเดียวกันเพิ่งส่งไปแล้ว = ล้าสมัย [L8]
        if (in_array($item, self::BOX_ITEMS, true)) {
            $fp = self::stateFingerprint($reading);
            if (isset($meta['fp']) && $meta['fp'] !== $fp) {
                Log::info('🤫 deliverItem: สถานะบิลเปลี่ยนไปแล้ว — กล่องที่พักล้าสมัย ไม่ส่ง', [
                    'reading_id' => $reading->id,
                    'item' => $item,
                    'was' => $meta['fp'],
                    'now' => $fp,
                ]);

                return self::OUT_STALE;
            }
            if (! isset($meta['fp']) && isset($meta['status']) && $meta['status'] !== $reading->conversation_status) {
                return self::OUT_STALE;
            }
            $sentFp = $reading->getConversationState('takeover_delivered', [])[$item] ?? null;
            if ($sentFp !== null && $sentFp === $fp) {
                return self::OUT_STALE; // กล่องนี้ในสถานะนี้ส่งไปแล้ว (cron พักซ้ำหลังส่ง)
            }
        }

        switch ($item) {
            case self::ITEM_DEEP_READING:
                return self::deliverDeepReading($reading, $platform, $userId);

            case self::ITEM_PAYFIRST_BIRTHDATE:
            case self::ITEM_CELTIC_START:
            case self::ITEM_SLIP_RESULT:
                return self::resendResponse($reading, $meta, $platform, $userId);

            case self::ITEM_CELTIC_RESUME:
                return self::sendCelticResumePrompt($reading, $platform, $userId);

            case self::ITEM_CELTIC_ANSWERS:
                return self::deliverCelticAnswers($reading);

            case self::ITEM_CELTIC_SUMMARY:
                return self::deliverCelticSummary($reading);

            case self::ITEM_BUBBLES:
                if (! is_array($reading->getConversationState('bubble_pending'))) {
                    return self::OUT_STALE; // ส่งครบไปแล้ว
                }

                return FortuneBubbleRecover::recoverReading($reading, FortuneTellingSetting::getSettings())
                    ? self::OUT_DELIVERED
                    : self::OUT_FAILED;

            case self::ITEM_VOICE_SUMMARY:
                dispatch(new \App\Jobs\ProcessVoiceSummaryJob((int) $reading->id, $platform, $userId));

                return self::OUT_DELIVERED;
        }

        return self::OUT_STALE;
    }

    /**
     * บันทึกผลของชิ้นนั้นลงรายการพัก + รายงาน
     */
    private static function applyOutcome(FortuneReading $reading, string $item, array $meta, string $outcome, array &$report): void
    {
        $at = (string) ($meta['at'] ?? '');

        switch ($outcome) {
            case self::OUT_DELIVERED:
                if (in_array($item, self::BOX_ITEMS, true)) {
                    ConversationStateAtomic::setChild($reading, 'takeover_delivered', $item, self::stateFingerprint($reading));
                }
                if ($item === self::ITEM_CELTIC_RESUME) {
                    ConversationStateAtomic::set($reading, 'takeover_resume_sent_at', now()->toIso8601String());
                }
                ConversationStateAtomic::removeChildIf($reading, 'takeover_deferred', $item, 'at', $at);
                $report['delivered'][] = $item;
                break;

            case self::OUT_STALE:
                ConversationStateAtomic::removeChildIf($reading, 'takeover_deferred', $item, 'at', $at);
                $report['stale'][] = $item;
                break;

            case self::OUT_AWAIT_REPLY:
                ConversationStateAtomic::set($reading, 'takeover_deferred.'.$item.'.await_reply', true);
                ['user_id' => $uid] = FortuneRecipient::resolve($reading);
                self::armLineReplyPrefix($uid);
                $report['awaiting'][] = $item;
                break;

            case self::OUT_LATER:
                $report['later'][] = $item;
                $report['retry_in'] = min($report['retry_in'] ?? PHP_INT_MAX, 60);
                break;

            case self::OUT_FAILED:
            default:
                $attempts = (int) ($meta['attempts'] ?? 0) + 1;
                ConversationStateAtomic::set($reading, 'takeover_deferred.'.$item.'.attempts', $attempts);
                ConversationStateAtomic::set($reading, 'takeover_deferred.'.$item.'.last_failed_at', now()->toIso8601String());

                if ($attempts >= self::MAX_ATTEMPTS) {
                    ConversationStateAtomic::set($reading, 'takeover_deferred.'.$item.'.gave_up', true);
                    self::alertGaveUp($reading, $item, $attempts);
                } else {
                    $report['failed'][] = $item;
                }
                break;
        }
    }

    /**
     * คำทำนาย Deep 39 — คำสั่งเดียวกับเส้นหลักของ prod (มี deep_response แล้ว = ข้าม AI ส่งอย่างเดียว)
     * ล็อก "fortune:deep_deliver:{id}" ตัวเดียวกับทาง reply + check-pending → ส่งได้ทางเดียวเท่านั้น [L9]
     */
    private static function deliverDeepReading(FortuneReading $reading, string $platform, string $userId): string
    {
        if (! $reading->is_paid || (bool) $reading->getConversationState('reading_sent_directly', false)) {
            return self::OUT_STALE;
        }

        // LINE: ลูกค้าเพิ่งทักมาหลังหมดเวลา → ทาง reply (ฟรี) ในเทิร์นนั้นส่งเอง — job รอดูผลทีหลัง
        if ($platform === FortuneRecipient::PLATFORM_LINE) {
            try {
                if (Cache::has(self::preferReplyKey($userId))) {
                    return self::OUT_LATER;
                }
            } catch (\Throwable $e) {
                // ไม่มี cache = ส่งเลย
            }
        }

        // อีกทางกำลังส่งอยู่ (ถือล็อก) → รอดูผลรอบหน้า ห้ามยิงซ้อน
        try {
            if (Cache::has("fortune:deep_deliver:{$reading->id}")) {
                return self::OUT_LATER;
            }
        } catch (\Throwable $e) {
            // ไม่มี cache = ส่งเลย (คำสั่งข้างล่างจับล็อกเองอีกชั้น)
        }

        Artisan::call('fortune:process-deep', [
            'readingId' => (int) $reading->id,
            'platform' => $platform,
            'userId' => $userId,
        ]);

        $reading->refresh();

        return (bool) $reading->getConversationState('reading_sent_directly', false)
            ? self::OUT_DELIVERED
            : self::OUT_FAILED;
    }

    /**
     * คำตอบ Celtic รายข้อ — เส้นเดียวกับ cron แต่เจาะบิลนี้ "ไม่สนหน้าต่าง 2 ชม." [L4]
     */
    private static function deliverCelticAnswers(FortuneReading $reading): string
    {
        $pending = fn () => FortuneCelticQuestion::query()
            ->where('fortune_reading_id', $reading->id)
            ->undelivered();

        if (! $pending()->exists()) {
            return self::OUT_STALE;
        }

        Artisan::call('fortune:celtic-redeliver', ['--reading' => (int) $reading->id]);

        // ที่ยังค้างและยังมีสิทธิ์ลองใหม่ (cron ยอมแพ้ที่ 3 ครั้ง — ที่เหลือส่งคืนผ่าน reply ตอนลูกค้าทัก)
        return $pending()->where('delivery_attempts', '<', 3)->exists()
            ? self::OUT_FAILED
            : self::OUT_DELIVERED;
    }

    /**
     * บทสรุป Celtic — เส้นเดียวกับ cron (โหมด --reading ไม่สนหน้าต่างเวลา + มีล็อกต่อบิลกันส่งซ้อน)
     */
    private static function deliverCelticSummary(FortuneReading $reading): string
    {
        if ((bool) $reading->getConversationState('celtic_summary_delivered', false)
            || (bool) $reading->getConversationState('celtic_finale_replayed', false)
            || trim((string) $reading->getConversationState('celtic_finale_text', '')) === '') {
            return self::OUT_STALE;
        }

        Artisan::call('fortune:celtic-summary-redeliver', ['--reading' => (int) $reading->id]);
        $reading->refresh();

        return (bool) $reading->getConversationState('celtic_summary_delivered', false)
            ? self::OUT_DELIVERED
            : self::OUT_FAILED;
    }

    /**
     * ส่งกล่องที่พักไว้ซ้ำ (ลายนิ้วมือสถานะเช็คแล้วใน deliverItem)
     */
    private static function resendResponse(FortuneReading $reading, array $meta, string $platform, string $userId): string
    {
        $payload = (array) ($meta['payload'] ?? []);
        if (empty($payload['action']) && empty($payload['message'])) {
            return self::OUT_STALE;
        }

        $payload['reading'] = $reading;

        return (new FortuneChannelManager(FortuneTellingSetting::getSettings()))
            ->sendResponse($platform, $userId, $payload, (array) ($meta['options'] ?? []))
            ? self::OUT_DELIVERED
            : self::OUT_FAILED;
    }

    /**
     * กล่อง "แม่หมอกลับมาดูแลต่อแล้ว" ของ Celtic ที่ค้างกลางทาง — บอกขั้นถัดไปตามสถานะจริงของบิล
     *
     * LINE: replyToken ที่ฝากไว้เท่านั้น — ไม่มี = จอดไว้แนบไปกับ reply ถัดไป **ห้าม push** [L7]
     */
    private static function sendCelticResumePrompt(FortuneReading $reading, string $platform, string $userId): string
    {
        $message = self::celticResumeText($reading, false);
        if ($message === null) {
            return self::OUT_STALE;
        }

        // กล่องเดียวกันเพิ่งส่งไปไม่นาน (cron พักซ้ำหลังส่ง) = ไม่ต้องส่งอีก
        $lastSent = $reading->getConversationState('takeover_resume_sent_at');
        if ($lastSent) {
            try {
                if (Carbon::parse($lastSent)->gt(now()->subMinutes(10))) {
                    return self::OUT_STALE;
                }
            } catch (\Throwable $e) {
                // parse ไม่ได้ = ส่งตามปกติ
            }
        }

        if ($platform === FortuneRecipient::PLATFORM_LINE) {
            $token = ReplyTokenVault::take($platform, $userId);
            if ($token && (new LineFortuneService(FortuneTellingSetting::getSettings()))->replyMessage($token, [['type' => 'text', 'text' => $message]])) {
                return self::OUT_DELIVERED;
            }

            return self::OUT_AWAIT_REPLY;
        }

        $sender = FortuneMessengerFactory::sender($platform, $userId, FortuneTellingSetting::getSettings());
        if ($sender === null) {
            return self::OUT_STALE;
        }

        return $sender->sendMessage($userId, $message, [
            'from_admin' => true,
            'message_tag' => 'POST_PURCHASE_UPDATE',
        ]) ? self::OUT_DELIVERED : self::OUT_FAILED;
    }

    /**
     * ข้อความกล่อง "แม่หมอกลับมาแล้ว" — null = ไม่ต้องส่ง (ยังไม่จ่าย / เดินพ้นช่วงเปิดไพ่-ถามแล้ว)
     *
     * @param  bool  $asPrefix  true = ท่อนสั้นนำหน้าคำตอบ (ลูกค้ากำลังพิมพ์มาอยู่แล้ว ไม่ต้องบอกให้พิมพ์)
     */
    private static function celticResumeText(FortuneReading $reading, bool $asPrefix): ?string
    {
        if (! $reading->is_paid || ! in_array($reading->conversation_status, self::CELTIC_INTERACTIVE_STATUSES, true)) {
            return null;
        }

        $name = $reading->facebook_user_name ?: 'เจ้าชะตา';

        if ($asPrefix) {
            return "✨ แม่หมอกลับมาดูแลต่อแล้วค่ะ คุณ{$name}";
        }

        if ($reading->conversation_status === FortuneReading::STATUS_CELTIC_PICKING) {
            $picked = count((array) $reading->getCelticCards());
            $next = $reading->getNextCelticPosition();
            $posName = $next ? (FortuneReading::CELTIC_POSITIONS[$next]['name'] ?? '') : '';

            return "✨ แม่หมอกลับมาดูแลต่อแล้วค่ะ คุณ{$name}\n\n"
                ."🃏 เปิดไปแล้ว {$picked}/10 ใบ"
                .($next ? " — ใบถัดไปคือใบที่ {$next}".($posName !== '' ? " [{$posName}]" : '') : '')
                ."\n🧘 ตั้งจิตนึกถึงสิ่งที่อยากรู้ แล้วพิมพ์ *'พร้อม'* เพื่อเปิดไพ่ต่อได้เลยค่ะ";
        }

        return "✨ แม่หมอกลับมาดูแลต่อแล้วค่ะ คุณ{$name}\n\n"
            .'💬 พิมพ์คำถามที่อยากรู้ต่อได้เลยนะคะ แม่หมอจะดูไพ่ชุดเดิมของคุณให้ค่ะ';
    }

    /**
     * ลายนิ้วมือสถานะของบิล (ตอนพัก vs ตอนส่ง) — ต่างกัน = ลูกค้าเดินต่อไปแล้ว กล่องที่พักล้าสมัย [L8]
     *
     * สถานะ + จ่ายแล้วไหม + จำนวนไพ่ที่เปิด + จำนวนคำถามที่ใช้ + มีคำทำนาย Deep แล้วไหม
     */
    public static function stateFingerprint(FortuneReading $reading): string
    {
        $cards = 0;
        try {
            $cards = count((array) $reading->getCelticCards());
        } catch (\Throwable $e) {
            // ไม่มีไพ่ = 0
        }

        return implode('|', [
            (string) $reading->conversation_status,
            $reading->is_paid ? 'paid' : 'unpaid',
            'cards:'.$cards,
            'q:'.(int) ($reading->celtic_questions_used ?? 0),
            'deep:'.(empty($reading->deep_response) ? 0 : 1),
        ]);
    }

    // ============================================================
    // ภายใน — จบเทคโอเวอร์: นาฬิกา / คำถามค้าง
    // ============================================================

    /**
     * ⏸️ [L11] เลื่อนนาฬิกาของเซสชันที่ลูกค้าจ่ายมา ออกไปเท่าช่วงที่ทับกับเทคโอเวอร์
     *
     * - Celtic ถาม-ตอบ (celtic_first_answered_at + celtic_cross_qa_window_minutes) — ตัวจับเวลาของ auto-finalize
     * - Celtic คุยต่อหลังบทสรุป (เพดานรวมนับจาก celtic_first_answered_at / idle นับจาก aftercare_last_msg_at)
     * - Pro Session (pro_session_started_at + pro_session_window_minutes) — Deep 39 และ Celtic หลังบทสรุป
     * - ช่วงสแตนบายก่อนถามข้อแรก (pro_session_ready_at + pro_session_standby_minutes)
     */
    public static function shiftSessionClocks(string $userId, CarbonInterface $episodeStart, CarbonInterface $episodeEnd): void
    {
        if ($episodeEnd->lessThanOrEqualTo($episodeStart)) {
            return;
        }

        try {
            $settings = FortuneTellingSetting::getSettings();
            $qaWindow = (int) $settings->getCelticQaWindowMinutes();
            $aftercareOn = $settings->isCelticAftercareEnabled();
            $aftercareTotal = (int) $settings->getCelticAftercareTotalMinutes();
            $aftercareIdle = (int) $settings->getCelticAftercareIdleMinutes();
            $standby = (int) ($settings->pro_session_standby_minutes ?? 30);
            $standby = $standby > 0 ? $standby : 30;

            $readings = self::customerQuery($userId)
                ->where('is_paid', true)
                ->where('created_at', '>=', now()->subDays(3))
                ->get();

            foreach ($readings as $r) {
                $shifted = [];

                // Celtic: celtic_first_answered_at (คอลัมน์) — ใช้ทั้งหน้าต่างถาม-ตอบ และเพดานรวมช่วงคุยต่อ
                $fa = $r->celtic_first_answered_at;
                $aftercareActive = $aftercareOn
                    && ! empty($r->getConversationState('celtic_aftercare_started_at'))
                    && ! (bool) $r->getConversationState('celtic_aftercare_farewelled', false);
                $finalized = ! empty($r->getConversationState('celtic_grand_finale_at'));

                if ($fa && $r->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
                    $window = null;
                    if (! $finalized && in_array($r->conversation_status, self::CELTIC_QA_STATUSES, true)) {
                        $window = $qaWindow;
                    } elseif ($aftercareActive) {
                        $window = $aftercareTotal;
                    }

                    if ($window !== null) {
                        $s = self::overlapSeconds(Carbon::instance($fa), $window, $episodeStart, $episodeEnd);
                        if ($s > 0) {
                            FortuneReading::query()->whereKey($r->id)->update([
                                'celtic_first_answered_at' => Carbon::instance($fa)->addSeconds($s),
                            ]);
                            $shifted['celtic_first_answered_at'] = $s;
                        }
                    }
                }

                if ($aftercareActive) {
                    $shifted += self::shiftStateClock($r, 'celtic_aftercare_last_msg_at', $aftercareIdle, $episodeStart, $episodeEnd,
                        $r->getConversationState('celtic_aftercare_started_at'));
                    $shifted += self::shiftStateClock($r, 'celtic_aftercare_started_at', $aftercareTotal + 15, $episodeStart, $episodeEnd);
                }

                // Pro Session (Deep 39 / Celtic หลังบทสรุป) — เริ่มนับแล้ว = หน้าต่างถาม · ยังไม่เริ่ม = สแตนบาย
                $psStarted = $r->getConversationState('pro_session_started_at');
                if ((bool) $r->getConversationState('pro_session_active', false) && ! empty($psStarted)) {
                    $psWindow = (int) $r->getConversationState('pro_session_window_minutes', FortuneConversationService::PRO_SESSION_DEEP_MINUTES);
                    $shifted += self::shiftStateClock($r, 'pro_session_started_at', max(1, $psWindow), $episodeStart, $episodeEnd);
                } elseif (empty($psStarted) && ! $fa && ! empty($r->getConversationState('pro_session_ready_at'))
                    && ((bool) $r->getConversationState('pro_session_active', false) || in_array($r->conversation_status, self::CELTIC_QA_STATUSES, true))) {
                    $shifted += self::shiftStateClock($r, 'pro_session_ready_at', $standby, $episodeStart, $episodeEnd);
                }

                if ($shifted !== []) {
                    Log::info('⏸️ takeover: เลื่อนนาฬิกาเซสชันที่จ่ายแล้วออกไปเท่าช่วงที่แอดมินคุย', [
                        'reading_id' => $r->id,
                        'shifted_seconds' => $shifted,
                        'episode' => [$episodeStart->toIso8601String(), $episodeEnd->toIso8601String()],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('⏸️ shiftSessionClocks ล้ม (non-blocking)', [
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * 🧹 [L6] ล้างคำถามที่ค้างตอบของลูกค้าคนนี้ (state + buffer) — ตาข่ายกู้จะไม่หยิบไปให้ AI ตอบ · ไม่แตะโควตา
     */
    public static function clearPendingQuestions(string $platform, string $userId): void
    {
        try {
            $rows = self::customerQuery($userId)
                ->where('is_paid', true)
                ->where('created_at', '>=', now()->subDays(3))
                ->where('conversation_state', 'like', '%pending_q%')
                ->get(['id', 'conversation_state']);

            foreach ($rows as $row) {
                $celtic = (array) $row->getConversationState('celtic_pending_q', []);
                $pro = (array) $row->getConversationState('pro_session_pending_q', []);
                if ($celtic === [] && $pro === []
                    && empty($row->getConversationState('celtic_pending_q_at'))
                    && empty($row->getConversationState('pro_session_pending_q_at'))) {
                    continue;
                }

                ConversationStateAtomic::remove($row, 'celtic_pending_q', 'celtic_pending_q_at', 'pro_session_pending_q', 'pro_session_pending_q_at');

                Log::info('🧹 takeover จบ: ล้างคำถามที่ค้างตอบ (แอดมินคุยไปแล้ว — ไม่ให้ AI ตอบซ้ำ · ไม่ตัดโควตา)', [
                    'reading_id' => $row->id,
                    'celtic_pending' => count($celtic),
                    'pro_session_pending' => count($pro),
                ]);
            }

            $buffer = app(MessageBuffer::class);
            foreach (['celtic_q', 'deep_qa', 'chat'] as $scope) {
                $buffer->clear($scope, $userId);
            }
        } catch (\Throwable $e) {
            Log::warning('🧹 clearPendingQuestions ล้ม (non-blocking)', [
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    /**
     * เลื่อนนาฬิกาใน conversation_state หนึ่งตัว — คืน [คีย์ => วินาที] ถ้าเลื่อน
     *
     * @return array<string, int>
     */
    private static function shiftStateClock(FortuneReading $r, string $key, int $windowMinutes, CarbonInterface $epStart, CarbonInterface $epEnd, ?string $fallback = null): array
    {
        $raw = $r->getConversationState($key) ?: $fallback;
        if (empty($raw)) {
            return [];
        }

        try {
            $start = Carbon::parse($raw);
        } catch (\Throwable $e) {
            return [];
        }

        $s = self::overlapSeconds($start, $windowMinutes, $epStart, $epEnd);
        if ($s <= 0) {
            return [];
        }

        ConversationStateAtomic::set($r, $key, $start->copy()->addSeconds($s)->toIso8601String());

        return [$key => $s];
    }

    /** วินาทีที่หน้าต่าง [start, start+window] ทับกับช่วงเทคโอเวอร์ [epStart, epEnd] */
    private static function overlapSeconds(CarbonInterface $start, int $windowMinutes, CarbonInterface $epStart, CarbonInterface $epEnd): int
    {
        $end = $start->copy()->addMinutes($windowMinutes);
        $from = max($start->getTimestamp(), $epStart->getTimestamp());
        $to = min($end->getTimestamp(), $epEnd->getTimestamp());

        return max(0, $to - $from);
    }

    private static function stashEpisodeStart(string $userId, CarbonInterface $start): void
    {
        try {
            $key = 'takeover_episode_start:'.$userId;
            $prev = Cache::get($key);
            $min = $prev ? min((int) $prev, $start->getTimestamp()) : $start->getTimestamp();
            Cache::put($key, $min, now()->addHours(48));
        } catch (\Throwable $e) {
            // ไม่มี cache = ใช้เวลาเริ่มของบิลสุดท้ายแทน (เลื่อนนาฬิกาน้อยไปนิด ไม่พัง)
        }
    }

    private static function pullEpisodeStart(string $userId, ?CarbonInterface $start): ?CarbonInterface
    {
        try {
            $stashed = Cache::pull('takeover_episode_start:'.$userId);
            if ($stashed) {
                $stashedAt = Carbon::createFromTimestamp((int) $stashed);

                return $start ? ($stashedAt->lessThan($start) ? $stashedAt : $start) : $stashedAt;
            }
        } catch (\Throwable $e) {
            // ใช้ค่าที่ส่งมา
        }

        return $start;
    }

    // ============================================================
    // ภายใน — อื่น ๆ
    // ============================================================

    /**
     * [L1] พักของเสร็จแล้วลูกค้าไม่ได้ถูกเทคโอเวอร์แล้ว (เทคโอเวอร์จบไประหว่างที่ผู้เรียกเช็ค→พัก) → สั่งส่งเลย
     */
    private static function deliverIfNoLongerTakenOver(FortuneReading $reading, string $item): void
    {
        try {
            ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::resolve($reading);
            if ($userId === '' || TakeoverSendGuard::isTakenOver($platform, $userId, true)) {
                return;
            }

            Log::warning('🤫 takeover_deferred: พักของหลังเทคโอเวอร์จบไปแล้ว (แข่งกัน) — สั่งส่งทันที', [
                'reading_id' => $reading->id,
                'item' => $item,
            ]);
            self::dispatchDelivery($platform, $userId);
        } catch (\Throwable $e) {
            // ตัวกวาดใน fortune:expire-conversations ตามเก็บให้
        }
    }

    private static function alertGaveUp(FortuneReading $reading, string $item, int $attempts): void
    {
        Log::critical('🤫 deliverDeferred: ส่งของที่จ่ายแล้วไม่ออกครบจำนวนครั้ง — ยอมแพ้ รอแอดมิน', [
            'reading_id' => $reading->id,
            'item' => $item,
            'attempts' => $attempts,
        ]);

        try {
            app(\App\Services\LineAlertService::class)->alertUnusualActivity('🚨 ส่งของที่ลูกค้าจ่ายแล้วไม่ออก (หลังจบเทคโอเวอร์)', [
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'customer' => $reading->facebook_user_name,
                'what' => self::LABELS[$item] ?? $item,
                'attempts' => $attempts,
                'admin_action' => 'ส่งให้ลูกค้าเอง แล้วคืนงานแบบ "จัดการเองแล้ว" เพื่อล้างรายการ',
                'admin_panel' => url('/admin/takeover/'.$reading->id),
            ]);
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    private static function disarmLinePrefixIfNothingLeft(string $userId): void
    {
        try {
            foreach (self::readingsWithDeferred($userId) as $reading) {
                $meta = $reading->getConversationState('takeover_deferred', [])[self::ITEM_CELTIC_RESUME] ?? null;
                if (is_array($meta) && ! empty($meta['await_reply'])) {
                    return; // ยังมีกล่องจอดรออยู่
                }
            }

            unset(self::$linePrefixArmed[$userId]);
            Cache::forget(self::linePrefixKey($userId));
        } catch (\Throwable $e) {
            // ธงค้าง = แค่เช็ค DB เพิ่มหนึ่งครั้งตอน reply ถัดไป
        }
    }

    /**
     * บิลของลูกค้าคนนี้ที่มีของพักอยู่ (ไม่ว่าง)
     *
     * @return \Illuminate\Support\Collection<int, FortuneReading>
     */
    private static function readingsWithDeferred(?string $userId): \Illuminate\Support\Collection
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return collect();
        }

        return self::customerQuery($userId)
            ->whereNotNull('conversation_state')
            ->whereRaw("JSON_VALID(conversation_state) AND JSON_LENGTH(JSON_EXTRACT(conversation_state, '$.takeover_deferred')) > 0")
            ->orderBy('id')
            ->get();
    }

    private static function customerQuery(string $userId): \Illuminate\Database\Eloquent\Builder
    {
        return FortuneReading::query()->where(function ($q) use ($userId) {
            $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
        });
    }

    private static function deliverLockKey(string $userId): string
    {
        return 'takeover_deliver_lock:'.$userId;
    }

    private static function dispatchKey(string $userId): string
    {
        return 'takeover_deliver_dispatch:'.$userId;
    }

    private static function preferReplyKey(string $userId): string
    {
        return 'takeover_prefer_reply:'.$userId;
    }

    private static function linePrefixKey(string $userId): string
    {
        return 'takeover_line_prefix:'.$userId;
    }
}
