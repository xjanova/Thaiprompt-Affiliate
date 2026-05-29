<?php

namespace App\Jobs;

use App\Models\FortuneReading;
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
 * ⚠️ DEPRECATED (2026-05-29): เลิก dispatch job นี้แล้ว — Celtic รวมเป็น immediate path เดียว
 *   (Single-bot spec: "ใช้ตัวเดียวคุยเลย เหมือนพูดคุยกับหมอ"). handleCelticAwaitingQuestion
 *   เอา debounce buffer ออกแล้ว → ทุกคำถามตอบสด (askQuestion sync). เก็บ class ไว้เผื่อ job
 *   ที่ค้างใน queue ตอน deploy — handle() ยัง drain ได้ปลอดภัย (buffer ว่าง/state mismatch → skip).
 *   ลบ class ได้เมื่อมั่นใจว่า queue ไม่มี job ค้าง.
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

        // 🌙 (2026-05-23 v3) ลบ silent sandbagging + physical delay ทั้งหมด
        //    user spec ใหม่: "เปลี่ยนไม่ให้มีการดีเลย์ในการตอบ + 5 คำถาม / 15 นาที + บอกกติการให้ชัด"
        //    Hard cap จัดการที่ canAskMoreCeltic() (check ด้านบนแล้ว) → endSession ผ่าน trait
        //    ไม่มี sleep / typing delay / cap template — ส่งทันที ทุกข้อความ

        Log::info('ProcessBufferedCelticMessageJob: flush + AI (no delay)', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'message_count' => $flushed['count'],
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

    // 🌙 (2026-05-23 v3) ลบ sendCapReachedTemplate — hard cap จัดการที่ trait endCelticSession() แล้ว
    //    user spec ใหม่: ตอบทันที / บอกกติกาให้ชัด / ครบ 5 คำถาม → ส่ง Grand Finale (ไม่ใช่ template เนียน)
}
