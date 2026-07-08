<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBufferedCelticMessageJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\MessageBuffer;
use App\Services\FortuneChannelManager;
use App\Services\FortuneLocaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 (2026-07-08) กู้คำถาม Celtic ที่ "ลูกค้าถามแล้วแต่ไม่มีคำตอบ" — safety net
 *
 * ต้นตอ (Siripon Schröter 2026-07-08): ลูกค้าถามคำถาม → เข้า settle-buffer (celtic_q, MessageBuffer)
 *   + dispatch ProcessBufferedCelticMessageJob (delay). ระหว่างนั้น deploy รีสตาร์ท queue worker →
 *   job (tries=1, ไม่ retry) หาย → buffer ไม่ถูก flush → ลูกค้าเงียบ ~9 นาทีจน window หมด.
 *   celtic-redeliver ไม่ช่วย (ครอบเฉพาะ "ตอบแล้วส่งไม่ถึง" ไม่ครอบ "รับแล้วไม่ได้ตอบ").
 *
 * Command นี้จับ 2 เคส — ไม่แตะ hot path (handleCelticAwaitingQuestion / job เดิม):
 *   A) status=celtic_awaiting_question + มี buffer celtic_q ค้างเกิน grace (job ปกติควร flush ไปแล้ว)
 *      → re-dispatch ProcessBufferedCelticMessageJob (idempotent: flush ใช้ Cache::lock กัน double-answer;
 *        ถ้า buffer ถูก flush ไปแล้ว/ตอบแล้ว → peek ว่าง → job skip)
 *   B) status=celtic_generating ค้างเกิน STUCK_GENERATING_MINUTES (เกิน job timeout 180s = job ตายแน่)
 *      → revert เป็น awaiting + nudge ให้พิมพ์คำถามใหม่ (คำถามเดิมถูก flush ทิ้งแล้ว กู้ text ไม่ได้)
 *      idempotent: revert แล้ว next run เห็น awaiting (ไม่ match generating) → ไม่ยิงซ้ำ
 *
 * Schedule: every minute (routes/console.php) — buffer TTL 5 นาที จึงต้องจับให้ทันภายใน TTL
 *
 * Usage:
 *   php artisan fortune:celtic-answer-recover           # รันจริง
 *   php artisan fortune:celtic-answer-recover --dry     # dry run
 *   php artisan fortune:celtic-answer-recover --limit=80
 */
class FortuneCelticAnswerRecover extends Command
{
    protected $signature = 'fortune:celtic-answer-recover
                            {--dry : Dry run — รายงานที่จะกู้ แต่ไม่ยิงจริง}
                            {--limit=50 : จำนวนสูงสุดต่อรอบ}';

    protected $description = 'กู้คำถาม Celtic ที่ลูกค้าถามแล้วแต่ไม่มีคำตอบ (buffer ค้าง / generating ค้าง)';

    /** @var int generating ค้างเกินกี่นาที = job ตายแน่ (job timeout 180s → 5 นาทีปลอดภัย) */
    private const STUCK_GENERATING_MINUTES = 5;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = (int) $this->option('limit');

        $settings = FortuneTellingSetting::getSettings();
        $settleSec = (int) ($settings->celtic_qa_settle_seconds ?? 10);
        // grace: นานเกินกว่าที่ job ปกติจะ flush (delay settleSec+1 → flush ~15s) → 60s = stuck แน่
        $graceSec = max($settleSec + 45, 60);

        $buffer = app(MessageBuffer::class);
        $now = microtime(true);

        $recoveredBuffer = 0;
        $recoveredGenerating = 0;
        $skipped = 0;

        // ── เคส A: awaiting + buffer ค้าง ─────────────────────────────────────
        $awaiting = FortuneReading::query()
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->where('conversation_status', FortuneReading::STATUS_CELTIC_AWAITING_QUESTION)
            ->where('updated_at', '>=', now()->subMinutes(10)) // buffer TTL 5 นาที — เกินนี้ไม่มี buffer
            ->orderBy('updated_at', 'asc')
            ->limit(max(1, $limit))
            ->get();

        foreach ($awaiting as $reading) {
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
            if (empty($userId)) {
                continue;
            }

            $buf = $buffer->peek('celtic_q', $userId);
            if (empty($buf)) {
                continue; // ไม่มี buffer ค้าง (ปกติ / flush แล้ว / TTL หมด)
            }

            $lastAt = end($buf)['at'] ?? 0;
            $stuckSec = (int) ($now - $lastAt);
            if ($stuckSec < $graceSec) {
                $skipped++; // ยังไม่ stuck — อาจกำลัง debounce ปกติ (ห้ามแย่ง job เดิม)

                continue;
            }

            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');
            $preview = mb_substr(implode(' | ', array_map(fn ($m) => $m['text'] ?? '', $buf)), 0, 80);

            $this->warn("  A #{$reading->id} buffer ค้าง (".count($buf)." msg, {$stuckSec}s) → re-dispatch: {$preview}");

            if (! $dry) {
                // re-dispatch job เดิม (ไม่ delay) — flush-lock กัน double-answer, job เช็ค state/canAskMore เอง
                ProcessBufferedCelticMessageJob::dispatch($reading->id, $platform, $userId, $settleSec);
                Log::warning('Celtic Answer Recover A: re-dispatch stuck buffer', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'buffer_count' => count($buf),
                    'stuck_sec' => $stuckSec,
                ]);
            }
            $recoveredBuffer++;
        }

        // ── เคส B: generating ค้าง (job ตาย หลัง flush) ───────────────────────
        $generating = FortuneReading::query()
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->where('conversation_status', FortuneReading::STATUS_CELTIC_GENERATING)
            ->where('updated_at', '<=', now()->subMinutes(self::STUCK_GENERATING_MINUTES))
            ->orderBy('updated_at', 'asc')
            ->limit(max(1, $limit))
            ->get();

        $channelManager = $dry ? null : new FortuneChannelManager($settings);

        foreach ($generating as $reading) {
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
            if (empty($userId)) {
                continue;
            }
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');

            $stuckMin = (int) $reading->updated_at?->diffInMinutes(now());
            $this->warn("  B #{$reading->id} generating ค้าง {$stuckMin} นาที → revert awaiting + nudge");

            if (! $dry) {
                // revert state ก่อน (กันคำตอบซ้ำถ้า job ผีฟื้น) แล้วค่อย nudge
                $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

                try {
                    $storedLocale = FortuneLocaleService::getStored($platform, $userId) ?? FortuneLocaleService::LOCALE_TH;
                    FortuneLocaleService::setCurrent($storedLocale);
                } catch (\Throwable $e) {
                    FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
                }

                $channelManager->sendResponse($platform, $userId, [
                    'action' => 'celtic_answer_recovered',
                    'message' => '🌙 แม่หมอขอตั้งสมาธิที่ไพ่อีกครู่นะคะ — เจ้าชะตาพิมพ์คำถามเดิมส่งมาอีกครั้งได้เลยค่ะ ✨',
                ], [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                Log::warning('Celtic Answer Recover B: revert stuck generating + nudge', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'stuck_min' => $stuckMin,
                ]);
            }
            $recoveredGenerating++;
        }

        $this->info("📊 buffer-recover {$recoveredBuffer} | generating-recover {$recoveredGenerating} | skipped(ยังไม่ stuck) {$skipped}");

        return 0;
    }
}
