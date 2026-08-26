<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\Fortune\MessageBuffer;
use App\Services\FortuneChannelManager;
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

    public function __construct(
        public int $readingId,
        public string $platform,
        public string $userId,
        public int $windowSeconds,
    ) {
        $this->onQueue('tpix-default'); // queue ที่มี worker อยู่
    }

    public function handle(): void
    {
        $buffer = app(MessageBuffer::class);
        $scope = 'celtic_q';

        $buf = $buffer->peek($scope, $this->userId);

        // เช็คว่าพร้อม flush หรือยัง (last message อยู่นาน >= window)
        if (! empty($buf) && ! $buffer->isReadyToFlush($scope, $this->userId, $this->windowSeconds)) {
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

        // ตรวจ canAskMore + state เผื่อ session หมดเวลาแล้ว
        if (! $reading->canAskMoreCeltic()) {
            Log::info('ProcessBufferedCelticMessageJob: session expired → skip', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        if (! in_array($reading->conversation_status, [
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            FortuneReading::STATUS_CELTIC_GENERATING,
        ], true)) {
            Log::info('ProcessBufferedCelticMessageJob: state mismatch → skip', [
                'reading_id' => $this->readingId,
                'state' => $reading->conversation_status,
            ]);

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
