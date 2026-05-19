<?php

namespace App\Jobs;

use App\Models\FortuneAdminQA;
use App\Models\LineBotConversation;
use App\Services\GeminiEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 📚 (2026-05-19) Capture Admin Q&A Job
 *
 * เมื่อแอดมินตอบลูกค้าใน FB Page Inbox/Business Suite → FB ส่ง echo event
 * → handleEchoMessage dispatch job นี้ → เก็บคู่ Q (last customer message) + A (admin reply)
 *
 * Flow:
 *   1. โหลด conversation history ของ customer (LineBotConversation, 24hr timeout)
 *   2. หา last message ที่ role='user' = Q
 *   3. Embed Q → vector 768
 *   4. INSERT fortune_admin_qa (Q + embedding + A)
 *
 * Skip conditions:
 *   - ไม่มี user message ใน history (admin ทักลูกค้าก่อน) → skip
 *   - Embedding ล้มเหลว → save แต่ q_embedding=null (จะถูก skip ตอน retrieve)
 *   - Admin reply ว่าง → skip
 *
 * Cost: 1 embedding call per admin reply (text-embedding-004 ฟรี)
 */
class CaptureAdminQAJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public string $platform,           // 'facebook' / 'line'
        public string $customerUserId,     // FB PSID / LINE userId
        public string $adminReplyText,     // A — admin reply text
        public ?int $adminUserId = null,   // null ถ้าไม่รู้ admin (FB Page Inbox)
        public ?array $contextMeta = null, // เพิ่มเติม (page_id, reading_id, ฯลฯ)
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);

        Log::debug('CaptureAdminQAJob: เริ่มประมวลผล', [
            'platform' => $this->platform,
            'customer_id' => $this->customerUserId,
            'admin_reply_length' => mb_strlen($this->adminReplyText),
        ]);

        // 1) Validate inputs
        $adminReply = trim($this->adminReplyText);
        if ($adminReply === '') {
            Log::debug('CaptureAdminQAJob: admin reply ว่าง — skip');

            return;
        }

        // 2) หา last customer message (Q) จาก conversation history
        $questionText = $this->findLastCustomerMessage();
        if ($questionText === null) {
            Log::debug('CaptureAdminQAJob: ไม่มี last customer message — skip', [
                'customer_id' => $this->customerUserId,
            ]);

            return;
        }

        // 3) Embed Q (Gemini text-embedding-004)
        $embeddingService = new GeminiEmbeddingService;
        $embedding = $embeddingService->embed($questionText);

        if ($embedding === null) {
            Log::info('CaptureAdminQAJob: embed ล้มเหลว — บันทึกแบบไม่มี vector', [
                'q_preview' => mb_substr($questionText, 0, 80),
            ]);
        }

        // 4) INSERT — รวม context meta (previous turns เพิ่มเติม)
        try {
            FortuneAdminQA::create([
                'q_text' => $questionText,
                'q_embedding' => $embedding,
                'a_text' => $adminReply,
                'admin_user_id' => $this->adminUserId,
                'source_platform' => $this->platform,
                'source_user_id' => $this->customerUserId,
                'context_json' => $this->contextMeta,
                'is_active' => true,
            ]);

            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);
            Log::info('CaptureAdminQAJob: บันทึก Q&A สำเร็จ', [
                'platform' => $this->platform,
                'has_embedding' => $embedding !== null,
                'q_preview' => mb_substr($questionText, 0, 80),
                'a_preview' => mb_substr($adminReply, 0, 80),
                'elapsed_ms' => $elapsedMs,
            ]);
        } catch (\Throwable $e) {
            Log::error('CaptureAdminQAJob: INSERT ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // retry
        }
    }

    /**
     * หา last role='user' message จาก conversation history (10 messages ล่าสุด)
     *
     * @return string|null text หรือ null ถ้าไม่มี
     */
    protected function findLastCustomerMessage(): ?string
    {
        try {
            $conversation = LineBotConversation::findOrCreateForPlatform(
                $this->customerUserId,
                $this->platform,
                1440, // 24 ชม. timeout
            );

            $history = $conversation->getHistoryForAI(10);
            if (empty($history) || ! is_array($history)) {
                return null;
            }

            // วน history จากท้ายมาก่อน หา role='user'
            for ($i = count($history) - 1; $i >= 0; $i--) {
                $msg = $history[$i];
                if (($msg['role'] ?? null) === 'user' && ! empty($msg['content'])) {
                    $text = trim((string) $msg['content']);
                    if ($text !== '') {
                        return $text;
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('CaptureAdminQAJob: ดึง history ไม่ได้', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
