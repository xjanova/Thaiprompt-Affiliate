<?php

namespace App\Jobs;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\ChatReplyRecovery;
use App\Services\Fortune\TakeoverSendGuard;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Support\SafeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 (2026-10-30) ลองตอบแชทอีกรอบหลัง AI ตอบไม่ทัน — Facebook/Telegram เท่านั้น
 *
 * LINE ไม่มีเส้นนี้ เพราะคำตอบตามหลังไม่มี replyToken = ต้อง push (ดู ChatReplyRecovery)
 * คำถามถูกแย่งไปตอบแล้ว (ลูกค้าทักใหม่ ระบบรวมคำถามให้) claim() จะคืน null แล้วจบเงียบ ๆ
 */
class RetryFailedChatReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    /**
     * @param  string  $pendingId  id ของคำถามค้าง — ไม่ตรงแปลว่าก้อนนี้ถูกจัดการไปแล้ว
     * @param  int  $attempt  รอบนี้เป็นรอบกู้ที่เท่าไร (เริ่มจาก 1)
     */
    public function __construct(
        public string $platform,
        public string $userId,
        public string $pendingId,
        public int $attempt,
    ) {
        $this->onQueue('tpix-default');
    }

    public function handle(): void
    {
        $recovery = app(ChatReplyRecovery::class);
        $pending = $recovery->claim($this->platform, $this->userId, $this->pendingId);

        if ($pending === null) {
            return;
        }

        // แอดมินเข้ามาดูแลแล้ว คำถามยังอยู่ในรายการรอแอดมิน ไม่หาย
        if (TakeoverSendGuard::userIsTakenOver($this->platform, $this->userId)
            || $recovery->adminAlreadyAnswered($pending)) {
            Log::info('RetryFailedChatReplyJob: แอดมินดูแลแล้ว — ไม่ตอบซ้ำ', ['user_id' => $this->userId]);

            return;
        }

        try {
            $service = new FortuneConversationService(FortuneTellingSetting::getSettings());
            $service->setPlatform($this->platform);

            // ระหว่างรอ ลูกค้าเริ่มดูดวงที่จ่ายแล้ว → ห้ามแชทค้างแทรกกลางการทำนาย
            if ($service->hasPaidActiveReading($this->userId)) {
                return;
            }

            $result = $service->tryAIChatResponse(
                $this->userId,
                $pending['text'],
                null,
                [
                    'buffered_count' => (int) ($pending['count'] ?? 1),
                    'chat_budget_ms' => ChatReplyRecovery::RETRY_BUDGET_MS,
                    'chat_retry' => true,
                ],
                true,
            );

            if (($result['action'] ?? null) === FortuneConversationService::ACTION_AI_CHAT_FAILED) {
                $recovery->recover($service, $this->platform, $this->userId, $pending, $this->attempt);

                return;
            }

            if ($result === null || empty($result['message'])) {
                $recovery->putBack($this->platform, $this->userId, $pending, $this->attempt);

                return;
            }

            $sent = app(FortuneChannelManager::class)->sendResponse($this->platform, $this->userId, $result);

            if ($sent) {
                $recovery->markAnswered($pending, (string) $result['message']);
            } else {
                $recovery->putBack($this->platform, $this->userId, $pending, $this->attempt);
            }
        } catch (\Throwable $e) {
            Log::error('RetryFailedChatReplyJob: exception', [
                'user_id' => $this->userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            $recovery->putBack($this->platform, $this->userId, $pending, $this->attempt);
        }
    }
}
