<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\FacebookWebhookService;
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
 * Flow:
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

    /** @var int  Max retry — buffer feature ไม่ critical, fail = ข้าม */
    public int $tries = 1;

    /** @var int  Job timeout — flush + AI call + reply */
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
        if (empty($buf)) {
            // ไม่มี buffer — อาจถูก flush โดย job อื่นไปแล้ว
            return;
        }

        // เช็คว่าพร้อม flush หรือยัง (last message อยู่นาน >= window)
        if (! $buffer->isReadyToFlush($scope, $this->userId, $this->windowSeconds)) {
            Log::debug('ProcessBufferedCelticMessageJob: buffer ยังใหม่ → skip', [
                'reading_id' => $this->readingId,
                'user_id' => $this->userId,
                'count' => count($buf),
                'window' => $this->windowSeconds,
            ]);

            return;
        }

        // Flush + AI
        $flushed = $buffer->flush($scope, $this->userId);
        $combined = $flushed['combined'];

        if (trim($combined) === '') {
            Log::debug('ProcessBufferedCelticMessageJob: combined ว่าง — skip');

            return;
        }

        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            Log::warning('ProcessBufferedCelticMessageJob: reading not found', [
                'reading_id' => $this->readingId,
            ]);

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

        // 🌙 (2026-05-23) Silent sandbagging — TYPE:A counter ใกล้ max → physical delay + cap
        //    user spec: ลูกค้าจำกติก 30 นาที — ห้ามประกาศ max questions
        //    คำนวณ remaining → delay/skip ตาม mode (รั้งเวลาให้ใกล้ window 30min หมด)
        //    remaining=0 → ไม่เรียก AI, ส่ง template เนียน (ลูกค้าเดาว่าใกล้หมดเวลา)
        $settings = FortuneTellingSetting::getSettings();
        $maxQuestions = (int) ($settings->celtic_cross_max_questions ?? 0);
        $questionsUsed = (int) ($reading->celtic_questions_used ?? 0);
        $remaining = $maxQuestions > 0 ? max(0, $maxQuestions - $questionsUsed) : 999;

        // Hard cap — ครบ TYPE:A quota แล้ว → ส่ง template + skip AI call
        if ($maxQuestions > 0 && $remaining === 0) {
            Log::info('ProcessBufferedCelticMessageJob: TYPE:A cap reached → silent template', [
                'reading_id' => $this->readingId,
                'used' => $questionsUsed,
                'max' => $maxQuestions,
            ]);

            $this->sendCapReachedTemplate($reading);
            // คืน state ให้พร้อมรับข้อความถัดไป — bot ตอบ template ซ้ำจน window หมด (cron auto-finalize)
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            return;
        }

        // Physical typing delay — รั้งเวลาให้ใกล้ window 30min หมด (cron จะส่ง grand_finale)
        if ($remaining <= 2 && $remaining > 0 && $this->platform === 'facebook') {
            $delaySeconds = $remaining === 1 ? 10 : 5;
            try {
                app(FacebookWebhookService::class)->sendTypingOn($this->userId);
                sleep($delaySeconds);
            } catch (\Throwable $delayErr) {
                Log::debug('ProcessBufferedCelticMessageJob: typing delay fail (non-blocking)', [
                    'error' => $delayErr->getMessage(),
                ]);
            }
        }

        Log::info('ProcessBufferedCelticMessageJob: flush + AI', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'message_count' => $flushed['count'],
            'remaining' => $remaining,
            'combined_preview' => mb_substr($combined, 0, 120),
        ]);

        // เรียก AI ทำนาย (เหมือน handleCelticAwaitingQuestion เดิม)
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING]);

        try {
            $service = app(CelticCrossService::class);
            $result = $service->askQuestion($reading, $combined);

            $reading->refresh();

            if (! $result['success']) {
                $this->sendErrorReply($result['message'] ?? 'AI ระบบขัดข้อง');
                $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

                return;
            }

            // ส่ง response ผ่าน FortuneChannelManager
            $channelManager = app(FortuneChannelManager::class);
            $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => 'celtic_question_answered',
                'message' => $result['response'] ?? '',
                'reading' => $reading,
                'sequence' => $result['sequence'] ?? null,
                'is_prediction' => $result['is_prediction'] ?? true,
            ]);

            // กลับ state AWAITING_QUESTION ให้พร้อมถามต่อ
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
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
     * ส่งข้อความ error ผ่าน channel manager
     */
    protected function sendErrorReply(string $message): void
    {
        try {
            $channelManager = app(FortuneChannelManager::class);
            $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => 'celtic_ai_failed',
                'message' => '⚠️ '.$message,
            ]);
        } catch (\Throwable $e) {
            Log::debug('ProcessBufferedCelticMessageJob: sendErrorReply fail', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🌙 (2026-05-23) ส่ง template เนียนเมื่อ TYPE:A counter ครบ max
     *    user spec: ห้ามประกาศ max questions — ลูกค้าจำกติก 30 นาที
     *    Template ทำให้ลูกค้าเดาว่า "ใกล้หมดเวลา" (จริงๆ ก็ใช่ — window จะหมดอีกไม่นาน)
     *    bot ตอบซ้ำได้เรื่อยๆ จนกว่า cron FortuneCelticAutoFinalize จะส่ง grand_finale
     */
    protected function sendCapReachedTemplate(FortuneReading $reading): void
    {
        // physical delay เนียน 12s ก่อนตอบ — ดึงเวลาให้ใกล้ window หมด
        if ($this->platform === 'facebook') {
            try {
                app(FacebookWebhookService::class)->sendTypingOn($this->userId);
                sleep(12);
            } catch (\Throwable $delayErr) {
                // ignore
            }
        }

        $messages = [
            "🌙 *แม่หมอกำลังเก็บพลังให้เจ้าชะตาค่ะ* ✨\n\n"
                ."ลองนิ่งกับคำตอบที่ผ่านมา ค่อย ๆ อ่านซ้ำหลายรอบ — บางครั้งความหมายแท้จริงจะค่อย ๆ ผุดขึ้นมาเอง 🕯️\n\n"
                .'_หากมีเรื่องสำคัญที่ยังค้าง — รอแม่หมอเรียบเรียงพลังให้สักครู่นะคะ_',

            "🕯️ *พลังในไพ่กำลังเงียบลงค่ะ*\n\n"
                ."แม่หมอขอใช้ช่วงนี้เก็บพลังให้เจ้าชะตา — ระหว่างนี้ลองใคร่ครวญสิ่งที่แม่หมอบอกไปก่อนนะคะ\n\n"
                .'_แม่หมอจะส่งบทสรุปสำคัญให้ในไม่ช้านี้ ✨_',

            "🌙 *แม่หมอนิ่งฟังเจ้าชะตาอยู่ค่ะ*\n\n"
                ."บางคำถาม — คำตอบที่ดีที่สุดอยู่ในใจของเจ้าชะตาเองอยู่แล้ว 🙏\n\n"
                .'_ลองอ่านคำทำนายก่อนหน้านี้ซ้ำอีกครั้ง — บางทีจะเข้าใจในมุมใหม่_',
        ];

        // เลือก template สลับกัน (variety) — ใช้ counter mod
        $idx = ((int) ($reading->celtic_questions_used ?? 0)) % count($messages);
        $message = $messages[$idx];

        try {
            $channelManager = app(FortuneChannelManager::class);
            $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => 'celtic_question_answered',
                'message' => $message,
                'reading' => $reading,
            ], [
                'from_admin' => true,
                'message_tag' => 'POST_PURCHASE_UPDATE',
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProcessBufferedCelticMessageJob: sendCapReachedTemplate fail', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
