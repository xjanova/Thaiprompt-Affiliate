<?php

namespace App\Jobs;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\MessageBuffer;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 📦 (2026-05-20 Phase 4b) Process buffered chat messages — chat path
 *
 * Flow:
 *   1. tryAIChatResponse append message ลง buffer + dispatch job (delayed N sec)
 *   2. ถ้าลูกค้าพิมพ์อีก → append + dispatch อีก job
 *   3. Job ตัวสุดท้าย fire → buffer.isReadyToFlush(N) → flush + tryAIChatResponse (bypass buffer)
 *
 * Idempotent: ตัวก่อนหน้า peek เห็น last_at ยังใหม่ → skip
 */
class ProcessBufferedChatMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $platform,
        public string $userId,
        public int $windowSeconds,
    ) {
        $this->onQueue('tpix-default');
    }

    public function handle(): void
    {
        $buffer = app(MessageBuffer::class);
        $scope = 'chat';

        if (! $buffer->isReadyToFlush($scope, $this->userId, $this->windowSeconds)) {
            Log::debug('ProcessBufferedChatMessageJob: buffer ยังใหม่ → skip', [
                'user_id' => $this->userId,
                'window' => $this->windowSeconds,
            ]);

            return;
        }

        $flushed = $buffer->flush($scope, $this->userId);
        $combined = $flushed['combined'];

        if (trim($combined) === '') {
            return;
        }

        Log::info('ProcessBufferedChatMessageJob: flush + tryAIChatResponse', [
            'platform' => $this->platform,
            'user_id' => $this->userId,
            'message_count' => $flushed['count'],
            'combined_preview' => mb_substr($combined, 0, 120),
        ]);

        try {
            $settings = FortuneTellingSetting::getSettings();
            $service = new FortuneConversationService($settings);

            // 🛡️ (2026-06-17) ระหว่างรอ buffer (สูงสุด ~1 นาที) ถ้าลูกค้าเริ่ม "ดูดวงที่จ่ายแล้ว" ไปแล้ว
            //   → ห้ามส่ง chat reply ที่ค้างมาแทรกกลางการทำนาย (reading flow เป็นเจ้าของบทสนทนาแล้ว)
            if ($service->hasPaidActiveReading($this->userId)) {
                Log::info('ProcessBufferedChatMessageJob: paid active reading — skip buffered chat reply', [
                    'user_id' => $this->userId,
                ]);

                return;
            }

            // เรียก tryAIChatResponse ด้วย bypassBuffer=true → ไม่เข้า buffer อีก
            //   📚 (2026-06-17) ส่ง buffered_count → AI รู้ว่านี่คือ "หลายข้อความรวมเป็นก้อนเดียว"
            //   (ร่ายยาว) → วิเคราะห์รวมแล้วตอบทีเดียว ไม่ตอบทีละบรรทัด
            $result = $service->tryAIChatResponse(
                $this->userId,
                $combined,
                null,   // userProfile — chat path resolve เอง
                ['buffered_count' => (int) ($flushed['count'] ?? 1)],   // dmContext — จำนวนข้อความที่รวม
                true,   // bypassBuffer
            );

            if ($result === null || empty($result['message'])) {
                Log::debug('ProcessBufferedChatMessageJob: tryAIChatResponse returned null/empty', [
                    'user_id' => $this->userId,
                ]);

                return;
            }

            // ส่ง response ผ่าน channel manager
            app(FortuneChannelManager::class)->sendResponse(
                $this->platform,
                $this->userId,
                $result
            );
        } catch (\Throwable $e) {
            Log::error('ProcessBufferedChatMessageJob: exception', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
