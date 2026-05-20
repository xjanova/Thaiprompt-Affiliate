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

            // เรียก tryAIChatResponse ด้วย bypassBuffer=true → ไม่เข้า buffer อีก
            $result = $service->tryAIChatResponse(
                $this->userId,
                $combined,
                null,   // userProfile — chat path resolve เอง
                null,   // dmContext
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
