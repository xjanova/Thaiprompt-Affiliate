<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\Fortune\MessageBuffer;
use App\Services\FortuneChannelManager;
use App\Services\FortuneLocaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 📦 (2026-05-20 Phase 4a) Process buffered Celtic Q2+ messages
 *
 * ♻️ REVIVED (2026-06-22 FIX D): กลับมา dispatch อีกครั้งเป็น "settle window" (trailing debounce)
 *   owner spec: ระหว่าง Q&A ถ้าลูกค้า "รัวคำ" → บอทนิ่งรอจนเงียบครบ window แล้วตอบรวดเดียว
 *   (ไม่ตอบทีละข้อความ). handleCelticAwaitingQuestion append เข้า buffer 'celtic_q' + dispatch job นี้
 *   (delay window+1). isReadyToFlush default (fromFirstMessage=false) = นับจากข้อความล่าสุด →
 *   reset ทุกครั้งที่ลูกค้าพิมพ์ → flush เมื่อเงียบครบ window. ปิดด้วย setting celtic_qa_settle_seconds=0.
 *
 *   (เดิม 2026-05-29 DEPRECATED ช่วง single-bot immediate — ตอนนี้กลับมาใช้แบบ trailing-debounce)
 *
 * Flow (legacy):
 *   1. handleCelticAwaitingQuestion append message ลง buffer + dispatch job (delayed N sec)
 *   2. ถ้าลูกค้าพิมพ์อีก → append + dispatch job อีกตัว
 *   3. Job ตัวแรก fire → เห็น last_at ยังใหม่ → skip (return)
 *   4. Job ตัวสุดท้าย fire → เห็น last_at >= N sec ago → flush + AI 1 ครั้ง
 *
 * Idempotent: ถ้าหลาย jobs fire พร้อมกัน — ตัวแรกที่ flush ได้ buffer ที่เหลือเป็น empty → ตัวถัดไป skip
 */
class ProcessBufferedCelticMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Max retry — buffer feature ไม่ critical, fail = ข้าม */
    public int $tries = 1;

    /** @var int Job timeout — flush + AI call + reply */
    public int $timeout = 180;

    /**
     * 🔢 (2026-09-12 FTU-260912-J8005) โหมด "รอคิว" — ข้อที่ลูกค้ากดระหว่างแม่หมอกำลังตอบอีกข้อ
     *   ต้องรอให้ข้อก่อนหน้า **ตอบเสร็จและส่งครบทุกกล่อง** ก่อน ไม่งั้นคำตอบแทรกกลางบับเบิ้ล
     *
     * ⚠️ ห้ามย้ายไปเป็น promoted constructor property — job ที่ค้างในคิวตอน deploy ถูก unserialize
     *   โดยไม่เรียก constructor ⇒ property แบบ typed ที่ไม่มีค่าเริ่มต้นจะ "uninitialized" แล้ว Error
     */
    public bool $waitForIdle = false;

    /** รอบที่รอคิวไปแล้ว — เพดานกันวนไม่จบถ้าสถานะค้าง */
    public int $idleWaits = 0;

    /**
     * รอได้สูงสุดกี่รอบ (× IDLE_RECHECK_SECONDS ≈ 3 นาที)
     *
     * ครบแล้ว **ไม่ตอบเอง** — ปล่อยคำถามค้างไว้ใน buffer + conversation_state ให้
     * fortune:celtic-answer-recover หยิบไปตอบเมื่อข้อก่อนหน้าเสร็จจริง
     * (ถ้าฝืนตอบตอนข้อเดิมยัง generating = ชนเลขลำดับคำถาม → ลูกค้าได้ "พิมพ์คำถามเดิมส่งมาอีกครั้ง")
     */
    private const MAX_IDLE_WAITS = 22;

    private const IDLE_RECHECK_SECONDS = 8;

    public function __construct(
        public int $readingId,
        public string $platform,
        public string $userId,
        public int $windowSeconds,
    ) {
        $this->onQueue('tpix-default'); // queue ที่มี worker อยู่
    }

    /**
     * เปิดโหมดรอคิว (ดู $waitForIdle) — ใช้ต่อท้าย dispatch(): `::dispatch(...)->waitForIdle()`
     */
    public function waitForIdle(bool $wait = true): static
    {
        $this->waitForIdle = $wait;

        return $this;
    }

    /**
     * 🔢 (2026-09-12) ข้อก่อนหน้ายังไม่เสร็จ — AI ยังตอบอยู่ หรือบับเบิ้ลยังทยอยส่งไม่ครบ
     *
     * public static — fortune:celtic-answer-recover ใช้ตัวเดียวกันตัดสินว่า "ยังไม่ใช่คำถามค้าง"
     *
     *   • celtic_generating = รอเสมอ ไม่ดูอายุ — ค้างจริง FortuneCelticRedeliver::recoverStuckGenerating
     *     เด้งกลับเป็น awaiting ให้เองที่ 90 วิ (ถ้าฝืนตอบระหว่าง generating = ชนเลขลำดับคำถาม)
     *   • แถวที่เพิ่งตอบ (< 90 วิ) แต่ยังไม่ mark delivered = กล่องแรกกำลังส่ง
     *     (ยกเว้นแถว error ขึ้นต้น ⚠️ — ไม่มีวันถูก mark ห้ามทำให้ข้อถัดไปรอฟรี 90 วิ)
     *   • bubble_pending ค้าง > 3 นาที = FortuneBubbleRecover รับช่วงกู้ไปแล้ว ไม่ต้องรอ
     */
    public static function previousAnswerInFlight(FortuneReading $reading): bool
    {
        if ($reading->conversation_status === FortuneReading::STATUS_CELTIC_GENERATING) {
            return true;
        }

        // ช่องสั้น ๆ หลังสถานะกลับเป็น awaiting แต่กล่องแรกยังส่งไม่เสร็จ (ยังไม่ mark / ยังไม่จดบับเบิ้ล)
        try {
            $justAnswered = $reading->celticQuestions()
                ->whereNotNull('answered_at')
                ->whereNull('delivered_at')
                ->where('answered_at', '>=', now()->subSeconds(90))
                ->where('response', 'not like', '⚠️%')
                ->exists();
            if ($justAnswered) {
                return true;
            }
        } catch (\Throwable $e) {
            // อ่านไม่ได้ → ไปดูบับเบิ้ลต่อ
        }

        $bubbleAt = $reading->getConversationState('bubble_pending_at');
        if (! empty($reading->getConversationState('bubble_pending')) && $bubbleAt) {
            try {
                return \Carbon\Carbon::parse($bubbleAt)->gt(now()->subMinutes(3));
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }

    public function handle(): void
    {
        $buffer = app(MessageBuffer::class);
        $scope = 'celtic_q';

        $buf = $buffer->peek($scope, $this->userId);

        // เช็คว่าพร้อม flush หรือยัง (เงียบครบ window นับจากข้อความล่าสุด)
        //   ⏳ (2026-09-02) หน้าต่างอาจถูกขยายเป็น 50 วิสำหรับคนเล่ายาว → ต้องมีเพดานรวมกำกับ
        //     ไม่งั้นคนที่พิมพ์ทุก 40 วินาทีติดกันจะไม่ได้คำตอบเลย
        $maxSec = (int) (FortuneTellingSetting::getSettings()->qa_settle_max_seconds ?? 180);
        if (! empty($buf) && ! $buffer->isSettled($scope, $this->userId, $this->windowSeconds, $maxSec)) {
            Log::debug('ProcessBufferedCelticMessageJob: buffer ยังใหม่ → skip', [
                'reading_id' => $this->readingId,
                'user_id' => $this->userId,
                'count' => count($buf),
                'window' => $this->windowSeconds,
            ]);

            return;
        }

        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            Log::warning('ProcessBufferedCelticMessageJob: reading not found', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // Flush + AI
        // 🛟 (2026-08-21) buffer บน Cache หายไป → กู้จากสำเนาบน conversation_state (MySQL)
        //   เกิดจริงเมื่อ deploy รัน `cache:clear` (= flushdb ทั้ง redis DB 1) ระหว่างที่ลูกค้าถาม
        //   เดิมเคสนี้ = `return;` เงียบ → คำถามลูกค้าที่จ่าย 99฿ ระเหยโดยไม่มี error
        $convService = new \App\Services\FortuneConversationService(FortuneTellingSetting::getSettings());

        // 🛟 (2026-09-08 FTU-260905-N3337) ด่านสถานะ/หมดเวลา ต้องตัดสิน **ก่อน** หยิบคำถามออกจากตาข่ายกู้
        //
        //   ลำดับเดิมกลับหัว: flush + take (ล้างสำเนาบน conversation_state ทิ้ง) แล้วค่อยเช็ค
        //   canAskMoreCeltic() → หมดเวลา = `return;` เงียบ ⇒ คำถามหายถาวร ไม่เหลือให้
        //   fortune:celtic-answer-recover กู้ และไม่เข้าบทสรุปด้วย
        //   (ลูกค้าจ่าย 99 กดปุ่มที่ระบบเสนอเอง แล้วได้ความเงียบ — ช้าไป 1 วินาที)
        //
        //   บทเรียนเดียวกับฝั่ง Deep 39 (FTU-260822-P2391): เช็ค session ก่อน แล้วค่อยหยิบของ
        if (! in_array($reading->conversation_status, [
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            FortuneReading::STATUS_CELTIC_GENERATING,
        ], true)) {
            // ⚠️ ห้าม take — ปล่อยสำเนาไว้ให้ตาข่ายกู้/บทสรุปเก็บ (เดิมกินทิ้งไปแล้วถึงค่อย return)
            $peek = $convService->peekPendingProSessionQuestionPublic($reading, 'celtic');

            Log::warning('ProcessBufferedCelticMessageJob: สถานะไม่ตรง → ไม่ตอบ (คงคำถามไว้ให้ตาข่ายกู้)', [
                'reading_id' => $this->readingId,
                'state' => $reading->conversation_status,
                'q_preview' => mb_substr((string) $peek['text'], 0, 120),
            ]);

            return;
        }

        // 🔢 (2026-09-12 FTU-260912-J8005) โหมดรอคิว — ข้อก่อนหน้ายังตอบ/ส่งไม่เสร็จ → ยังไม่หยิบของ
        //   นัดตัวเองมาดูใหม่ทุก IDLE_RECHECK_SECONDS จนกว่าข้อก่อนหน้าจะส่งครบทุกกล่อง
        //   ต้องเช็ค "มีของรอ" ก่อน — job อื่นอาจ flush ไปแล้ว ห้ามนัดวนเปล่า ๆ
        //   ⚠️ ลูปนี้ **ห้ามเขียนอะไรลง reading** — setConversationState() เขียน JSON ทั้งก้อนจากสำเนาที่โหลด
        //     ตอนต้น job = ทับ bubble_pending / celtic_pending_q ที่ job อื่นเพิ่งแก้ (บับเบิ้ลส่งซ้ำ/ตอบซ้ำ)
        //     และดัน updated_at = ด่านเด้งสถานะค้าง 90 วิ มองว่ายังสด
        //     ส่วน fortune:celtic-answer-recover ไม่แย่งตอบเพราะเช็ค previousAnswerInFlight() ตัวเดียวกัน
        $pendingQ = $reading->getConversationState('celtic_pending_q', []);
        $hasWork = ! empty($buf) || (is_array($pendingQ) && $pendingQ !== []);
        if ($this->waitForIdle && $hasWork && self::previousAnswerInFlight($reading)) {
            if ($this->idleWaits >= self::MAX_IDLE_WAITS) {
                // ครบเพดาน — ไม่ฝืนตอบทับข้อที่ยังไม่เสร็จ ปล่อยของค้างไว้ให้ตาข่ายกู้ (ดู MAX_IDLE_WAITS)
                Log::warning('ProcessBufferedCelticMessageJob: รอคิวครบเพดาน → ปล่อยให้ celtic-answer-recover ตอบ', [
                    'reading_id' => $this->readingId,
                    'state' => $reading->conversation_status,
                ]);

                return;
            }

            $next = new self($this->readingId, $this->platform, $this->userId, $this->windowSeconds);
            $next->waitForIdle = true;
            $next->idleWaits = $this->idleWaits + 1;
            dispatch($next)->delay(now()->addSeconds(self::IDLE_RECHECK_SECONDS));

            Log::debug('ProcessBufferedCelticMessageJob: รอคิว — ข้อก่อนหน้ายังส่งไม่ครบ', [
                'reading_id' => $this->readingId,
                'wait_no' => $next->idleWaits,
                'state' => $reading->conversation_status,
            ]);

            return;
        }

        // อ่านไว้ก่อนหยิบของ — ใช้ตัดสินท้ายบล็อกว่าจะตอบปกติ หรือปิดรอบด้วยบทสรุป
        $windowClosed = ! $reading->canAskMoreCeltic();

        $combined = '';
        $messageCount = 0;
        if (! empty($buf)) {
            $flushed = $buffer->flush($scope, $this->userId);
            $combined = (string) $flushed['combined'];
            $messageCount = (int) ($flushed['count'] ?? 0);

            // ⚠️ ล้างสำเนาสำรอง "ทันที" ที่ flush สำเร็จ — ห้ามเลื่อนไปทีหลัง
            //   job ตัวอื่นที่ fire พร้อมกันจะเจอ cache ว่างแล้วตกไปหยิบสำเนา = ตอบซ้ำ
            if (trim($combined) !== '') {
                $convService->takePendingProSessionQuestionPublic($reading, 'celtic');
            }
        }

        if (trim($combined) === '') {
            $combined = $convService->takePendingProSessionQuestionPublic($reading, 'celtic');
            $messageCount = trim($combined) === '' ? 0 : substr_count($combined, chr(10)) + 1;

            if (trim($combined) !== '') {
                Log::warning('ProcessBufferedCelticMessageJob: 🛟 กู้คำถามจาก conversation_state (cache buffer หาย)', [
                    'reading_id' => $this->readingId,
                    'combined_preview' => mb_substr($combined, 0, 120),
                ]);
            }
        }

        if (trim($combined) === '') {
            Log::debug('ProcessBufferedCelticMessageJob: combined ว่าง — skip');

            return;
        }

        // ⏳ (2026-09-08) หน้าต่างคุยปิดไปแล้วระหว่างที่คำถามนอนอยู่ใน settle-buffer
        //   เดิมจุดนี้คือ `return;` เปล่า ๆ — ตอนนี้ทำแบบเดียวกับเส้นตรง: ฝากเข้าบทสรุป + ปิดรอบ
        if ($windowClosed) {
            $this->closeSessionWithLateQuestion($reading, $convService, $combined);

            return;
        }

        // 🌙 (2026-05-23 v3) ลบ silent sandbagging + physical delay ทั้งหมด
        //    user spec ใหม่: "เปลี่ยนไม่ให้มีการดีเลย์ในการตอบ + 5 คำถาม / 15 นาที + บอกกติการให้ชัด"
        //    Hard cap จัดการที่ canAskMoreCeltic() (check ด้านบนแล้ว) → endSession ผ่าน trait
        //    ไม่มี sleep / typing delay / cap template — ส่งทันที ทุกข้อความ

        Log::info('ProcessBufferedCelticMessageJob: flush + AI (no delay)', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'message_count' => $messageCount,
            'combined_preview' => mb_substr($combined, 0, 120),
        ]);

        // เรียก AI ทำนาย (เหมือน handleCelticAwaitingQuestion เดิม)
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING]);

        try {
            $service = app(CelticCrossService::class);
            $result = $service->askQuestion($reading, $combined);

            $reading->refresh();

            if (! $result['success']) {
                // ⏳ (2026-09-08) หน้าต่างเพิ่งปิดระหว่างที่ AI กำลังคิด — askQuestion() ก็ตัดด้วยด่านเวลาเหมือนกัน
                //   ถ้าตอบ "พิมพ์คำถามเดิมส่งมาอีกครั้ง" ตรงนี้ = หลอกลูกค้าให้พิมพ์ซ้ำเข้าประตูที่ปิดไปแล้ว
                if (! $reading->canAskMoreCeltic()) {
                    $this->closeSessionWithLateQuestion($reading, $convService, $combined);

                    return;
                }

                // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอขัดข้องเด็ดขาด" — ไม่ echo technical msg
                $this->sendErrorReply('🌙 แม่หมอขอตั้งสมาธิที่ไพ่อีกครู่นะคะ — พิมพ์คำถามเดิมส่งมาอีกครั้งได้เลยค่ะ ✨');
                $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

                return;
            }

            // 🔧 (2026-06-23 FIX D fix) ใช้ decoration เดียวกับ inline path —
            //   footer กติกา (เหลือเวลา X นาที) + กล่องคำถามแนะนำ (ปุ่มเลข) + carry-forward + off-topic/max-cap
            //   เดิม job ส่ง bare response → คำตอบที่ผ่าน buffer ไม่มี footer/ปุ่ม + คำถาม both-pick หาย
            //   finalizeCelticAnswer จัดการ state เอง (AWAITING_QUESTION / COMPLETED ถ้า session จบ)
            $payload = (new \App\Services\FortuneConversationService(\App\Models\FortuneTellingSetting::getSettings()))
                ->finalizeCelticAnswerPublic($reading->fresh(), $result);

            $channelManager = app(FortuneChannelManager::class);
            // 🎟️ (2026-08-26) ยืม replyToken ที่เทิร์น silent_skip ฝากไว้ → ตอบฟรี ไม่กินโควต้า push
            //   หมดอายุ/ไม่มี → $extra ว่าง → ตกไป push ตามเดิม (ไม่มี regression)
            $channelManager->sendResponse($this->platform, $this->userId, $payload, $this->borrowedReplyExtra());
        } catch (\Throwable $e) {
            Log::error('ProcessBufferedCelticMessageJob: exception', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);

            // กลับ state เผื่อ retry
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            $this->sendErrorReply('เกิดข้อผิดพลาด ลองพิมพ์ใหม่อีกครั้งค่ะ');
        }
    }

    /**
     * ⏳ (2026-09-08 FTU-260905-N3337) คำถามมาถึงหลังหน้าต่างคุยปิด — **ห้ามเงียบ**
     *
     * เดิมจุดนี้คือ `return;` เปล่า ๆ หลังจากคำถามถูกหยิบออกจากตาข่ายกู้ไปแล้ว
     * ⇒ ลูกค้าจ่าย 99 กดปุ่มคำถามแนะนำที่ระบบเสนอเอง แล้วได้ความเงียบ ไม่มีข้อความบอกว่าทำไม
     *
     * ทำแบบเดียวกับเส้นตรง (handleCelticAwaitingQuestion เมื่อ canAskMoreCeltic() = false):
     *   1) ฝากคำถามเป็นแถว pending → บทสรุปท้าย (Grand Finale) ตอบให้ในนั้น
     *   2) ปิดรอบ + ส่งบทสรุปทันที ไม่ต้องรอ cron fortune:celtic-auto-finalize (สูงสุด 5 นาที)
     *
     * ⚠️ ธง `celtic_grand_finale_at` = กันส่งบทสรุปซ้ำ (ธงตัวเดียวกับที่ cron ใช้)
     */
    protected function closeSessionWithLateQuestion(
        FortuneReading $reading,
        \App\Services\FortuneConversationService $convService,
        string $question
    ): void {
        $stashed = false;

        try {
            $stashed = $convService->stashUnansweredCelticQuestionPublic($reading, $question);
        } catch (\Throwable $e) {
            Log::error('ProcessBufferedCelticMessageJob: ฝากคำถามเข้าบทสรุปไม่สำเร็จ', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('ProcessBufferedCelticMessageJob: ⏳ คำถามมาถึงหลังหมดเวลา → ฝากเข้าบทสรุปแล้วปิดรอบ', [
            'reading_id' => $this->readingId,
            'stashed' => $stashed,
            'q_preview' => mb_substr($question, 0, 120),
        ]);

        try {
            $reading->refresh();

            // 🛡️ บทสรุปส่งไปแล้ว (cron ชิงปิดก่อน / เส้นอื่นปิดไปแล้ว) → ห้ามส่งซ้ำ
            //
            //   เช็ค 2 ชั้น เพราะธงกับสถานะถูกตั้งคนละจังหวะ:
            //     • endCelticSession() ตั้ง status = COMPLETED **ทันทีที่เข้า** (ก่อนยิง AI)
            //     • celtic_grand_finale_at ถูกตั้ง **ตอนบทสรุปเสร็จ** (CelticCrossService)
            //   ⇒ ถ้าดูแค่ธง จะมองไม่เห็นตัวที่กำลัง generate อยู่ = ลูกค้าได้บทสรุป 2 ใบ
            if (! empty($reading->getConversationState('celtic_grand_finale_at'))
                || $reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                Log::info('ProcessBufferedCelticMessageJob: มีเส้นอื่นปิดรอบไปแล้ว → ไม่ส่งบทสรุปซ้ำ', [
                    'reading_id' => $this->readingId,
                    'state' => $reading->conversation_status,
                ]);

                return;
            }

            // 🌐 queue worker ไม่มี request context → คืน locale ก่อนสร้างบทสรุป (แบบเดียวกับ cron auto-finalize)
            try {
                FortuneLocaleService::setCurrent(
                    FortuneLocaleService::getStored($this->platform, $this->userId)
                        ?? FortuneLocaleService::LOCALE_TH
                );
            } catch (\Throwable $e) {
                FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
            }

            $payload = $convService->endCelticSession($reading, 'time_expired');

            // 🎟️ LINE: ใช้ replyToken ที่เทิร์น silent_skip ฝากไว้ก่อน = ฟรี ไม่กินโควต้า push
            //    FB: POST_PURCHASE_UPDATE ส่งได้แม้พ้นหน้าต่าง 24 ชม. (แบบเดียวกับ cron auto-finalize)
            app(FortuneChannelManager::class)->sendResponse(
                $this->platform,
                $this->userId,
                $payload,
                array_merge(
                    ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE'],
                    $this->borrowedReplyExtra()
                )
            );
        } catch (\Throwable $e) {
            Log::error('ProcessBufferedCelticMessageJob: ปิดรอบ + ส่งบทสรุปไม่สำเร็จ', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🎟️ (2026-08-26) หยิบ replyToken ที่เทิร์น webhook ฝากไว้ (ถ้ายังสด)
     *
     * LINE คิดเงิน push แต่ reply ฟรี — job นี้เดิมไม่มี token เลยต้อง push ทุกคำตอบ
     * ซึ่งเป็นตัวกินโควต้าหลักของ Celtic 99฿ (~19-20 push/เซสชัน)
     *
     * @return array<string,string> ว่าง = ไม่มี token → caller ตกไป push ตามเดิม
     */
    protected function borrowedReplyExtra(): array
    {
        if ($this->platform !== 'line') {
            return [];
        }

        $token = \App\Services\Fortune\ReplyTokenVault::take($this->platform, $this->userId);

        return $token ? ['reply_token' => $token] : [];
    }

    /**
     * ส่งข้อความ error ผ่าน channel manager
     */
    protected function sendErrorReply(string $message): void
    {
        try {
            $channelManager = app(FortuneChannelManager::class);
            $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => 'celtic_ai_failed',
                'message' => '⚠️ '.$message,
            ], $this->borrowedReplyExtra());
        } catch (\Throwable $e) {
            Log::debug('ProcessBufferedCelticMessageJob: sendErrorReply fail', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // 🌙 (2026-05-23 v3) ลบ sendCapReachedTemplate — hard cap จัดการที่ trait endCelticSession() แล้ว
    //    user spec ใหม่: ตอบทันที / บอกกติกาให้ชัด / ครบ 5 คำถาม → ส่ง Grand Finale (ไม่ใช่ template เนียน)
}
