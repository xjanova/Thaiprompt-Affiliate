<?php

namespace App\Jobs;

use App\Models\FortuneAdminQA;
use App\Models\FortuneReading;
use App\Models\LineBotConversation;
use App\Services\FortuneAdminQAClassifier;
use App\Services\GeminiEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 📚 (2026-05-19) Capture Admin Q&A Job
 *     (2026-05-20) v2 — เพิ่ม category + context (reading state, prev turns, page_id)
 *
 * เมื่อแอดมินตอบลูกค้าใน FB Page Inbox/Business Suite → FB ส่ง echo event
 * → handleEchoMessage dispatch job นี้ → เก็บคู่ Q (last customer message) + A (admin reply)
 *
 * Flow v2:
 *   1. โหลด conversation history ของ customer (LineBotConversation, 24hr timeout)
 *   2. หา last message ที่ role='user' = Q + เก็บ prev 2-3 turns เข้า context_json
 *   3. ถ้า contextMeta มี reading_id → load FortuneReading → derive category
 *   4. Embed Q → vector 768
 *   5. INSERT fortune_admin_qa (Q + embedding + A + category + reading_id + page_id + ...)
 *
 * contextMeta ที่รับ (จาก FacebookWebhookController):
 *   - page_id:        FB Page ID ที่ admin ตอบ
 *   - reading_id:     FortuneReading id (ถ้ามี active reading)
 *   - reading_type:   basic/deep/celtic_cross/free_card
 *   - app_id, echo:   echo metadata เดิม
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

    /**
     * จำนวน prev turns ที่เก็บใน context_json (ใช้ดูบริบทย้อนหลัง)
     */
    private const PREV_TURNS_COUNT = 4;

    public function __construct(
        public string $platform,           // 'facebook' / 'line'
        public string $customerUserId,     // FB PSID / LINE userId
        public string $adminReplyText,     // A — admin reply text
        public ?int $adminUserId = null,   // null ถ้าไม่รู้ admin (FB Page Inbox)
        public ?array $contextMeta = null, // เพิ่มเติม (page_id, reading_id, reading_type, app_id, echo)
        public ?string $explicitQuestion = null, // (2026-06-06) Q ตรงๆ — ใช้เมื่อรู้คำถามแน่ชัด (saved-questions/ฝากคำถาม) ไม่ต้องเดาจาก history
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);

        Log::debug('CaptureAdminQAJob: เริ่มประมวลผล', [
            'platform' => $this->platform,
            'customer_id' => $this->customerUserId,
            'admin_reply_length' => mb_strlen($this->adminReplyText),
            'has_context' => $this->contextMeta !== null,
        ]);

        // 1) Validate inputs
        $adminReply = trim($this->adminReplyText);
        if ($adminReply === '') {
            Log::debug('CaptureAdminQAJob: admin reply ว่าง — skip');

            return;
        }

        // 2) หา Q (คำถามลูกค้า) + prev turns
        //    - ถ้า caller ส่ง explicitQuestion มา (เช่น หน้า saved-questions / โหมดฝากคำถาม)
        //      → ใช้คำถามนั้นตรงๆ ไม่ต้องเดาจาก history
        //      เพราะ conversation 24 ชม. อาจหมดอายุ → extract ได้ Q ผิด/null
        //    - ไม่งั้น (FB echo จาก Page Inbox) → ดึง last customer message จาก history เหมือนเดิม
        $explicit = $this->explicitQuestion !== null ? trim($this->explicitQuestion) : '';
        if ($explicit !== '') {
            $questionText = $explicit;
            $prevTurns = [];
        } else {
            [$questionText, $prevTurns] = $this->extractQuestionAndContext();
            if ($questionText === null) {
                Log::debug('CaptureAdminQAJob: ไม่มี last customer message — skip', [
                    'customer_id' => $this->customerUserId,
                ]);

                return;
            }
        }

        // 3) Derive category — load reading ถ้ามี reading_id
        $readingId = $this->contextMeta['reading_id'] ?? null;
        $readingType = $this->contextMeta['reading_type'] ?? null;
        $pageId = $this->contextMeta['page_id'] ?? null;

        $reading = null;
        if ($readingId) {
            try {
                $reading = FortuneReading::find($readingId);
                // sync reading_type จาก model จริง (กัน caller ส่ง stale)
                if ($reading) {
                    $readingType = $reading->reading_type;
                }
            } catch (\Throwable $e) {
                Log::debug('CaptureAdminQAJob: load reading ไม่สำเร็จ — ใช้ context_meta เดิม', [
                    'reading_id' => $readingId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $category = FortuneAdminQAClassifier::classify($reading, $questionText);

        // 4) Embed Q (Gemini text-embedding-004)
        $embeddingService = new GeminiEmbeddingService;
        $embedding = $embeddingService->embed($questionText);

        if ($embedding === null) {
            Log::info('CaptureAdminQAJob: embed ล้มเหลว — บันทึกแบบไม่มี vector', [
                'q_preview' => mb_substr($questionText, 0, 80),
            ]);
        }

        // 5) Build context_json — เก็บ prev turns + status + metadata
        $contextJson = [
            'prev_turns' => $prevTurns,
            'conversation_status' => $reading?->conversation_status,
            'reading_status_at_capture' => $reading?->conversation_status,
            'captured_at' => now()->toIso8601String(),
        ];
        // merge metadata เดิม (app_id, echo) แต่ไม่ทับ field ใหม่
        // 🧹 (2026-07-26) เพิ่ม is_human_typed — ถ้าไม่ใส่ใน whitelist ตรงนี้
        //    ค่าที่ webhook ส่งมาจะถูกทิ้งเงียบ ๆ (บั๊กที่เจอตอนรีวิว)
        if (is_array($this->contextMeta)) {
            foreach (['app_id', 'echo', 'is_human_typed'] as $passthrough) {
                if (array_key_exists($passthrough, $this->contextMeta)) {
                    $contextJson[$passthrough] = $this->contextMeta[$passthrough];
                }
            }
        }

        // 6) INSERT
        try {
            FortuneAdminQA::create([
                'q_text' => $questionText,
                'q_embedding' => $embedding,
                'a_text' => $adminReply,
                'category' => $category,
                'admin_user_id' => $this->adminUserId,
                'source_platform' => $this->platform,
                'source_user_id' => $this->customerUserId,
                'page_id' => $pageId,
                'reading_id' => $readingId,
                'reading_type' => $readingType,
                'context_json' => $contextJson,
                'is_active' => true,
            ]);

            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);
            Log::info('CaptureAdminQAJob: บันทึก Q&A สำเร็จ', [
                'platform' => $this->platform,
                'category' => $category,
                'reading_id' => $readingId,
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
     * ดึง last customer message + prev turns จาก conversation history
     *
     * @return array{0:string|null, 1:array<int,array{role:string,content:string}>}
     *         [$lastCustomerMessage, $prevTurns]
     */
    protected function extractQuestionAndContext(): array
    {
        try {
            $conversation = LineBotConversation::findOrCreateForPlatform(
                $this->customerUserId,
                $this->platform,
                1440, // 24 ชม. timeout
            );

            $history = $conversation->getHistoryForAI(10);
            if (empty($history) || ! is_array($history)) {
                return [null, []];
            }

            // วน history จากท้ายมาก่อน หา role='user' ล่าสุด
            $lastUserIdx = null;
            for ($i = count($history) - 1; $i >= 0; $i--) {
                $msg = $history[$i];
                if (($msg['role'] ?? null) === 'user' && ! empty($msg['content'])) {
                    $text = trim((string) $msg['content']);
                    if ($text !== '') {
                        $lastUserIdx = $i;
                        break;
                    }
                }
            }

            if ($lastUserIdx === null) {
                return [null, []];
            }

            $questionText = trim((string) $history[$lastUserIdx]['content']);

            // เก็บ prev N turns ก่อนหน้า last user message
            $start = max(0, $lastUserIdx - self::PREV_TURNS_COUNT);
            $prevTurns = [];
            for ($i = $start; $i < $lastUserIdx; $i++) {
                $msg = $history[$i];
                $role = $msg['role'] ?? null;
                $content = trim((string) ($msg['content'] ?? ''));
                if ($role && $content !== '') {
                    $prevTurns[] = [
                        'role' => $role,
                        'content' => mb_substr($content, 0, 500), // cap ขนาด กัน context_json ใหญ่
                    ];
                }
            }

            return [$questionText, $prevTurns];
        } catch (\Throwable $e) {
            Log::warning('CaptureAdminQAJob: ดึง history ไม่ได้', [
                'error' => $e->getMessage(),
            ]);

            return [null, []];
        }
    }
}
