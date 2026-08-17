<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBufferedProSessionMessageJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\MessageBuffer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 (2026-08-17) กู้คำถาม Pro Session (Deep 39 / Celtic หลังจบ 3Q) ที่ "ถามแล้วไม่มีคำตอบ"
 *
 * คู่แฝดของ fortune:celtic-answer-recover — เพิ่มมาพร้อมกับ settle window ของ Pro Session
 * เพราะกลไก buffer แบบเดียวกันเคยทำลูกค้าเงียบมาแล้วจริงฝั่ง Celtic:
 *   เคส Siripon Schröter (2026-07-08) — ลูกค้าถาม → เข้า buffer + dispatch job (tries=1)
 *   → deploy รีสตาร์ท queue worker ระหว่างนั้น → job หาย → buffer ไม่ถูก flush → เงียบ ~9 นาที
 *   ลูกค้าจ่ายเงินแล้วแต่ไม่ได้คำตอบ และไม่มี error ให้เห็นที่ไหนเลย
 *
 * จับเคสเดียว (Pro Session ไม่มี state 'generating' แยก — handleProSession ทำงาน sync ในตัว job):
 *   มี buffer 'deep_qa' ค้างเกิน grace + session ยังเปิดอยู่ → re-dispatch job (ไม่ delay)
 *
 * Idempotent: job peek buffer ว่าง = return ทันที · flush เป็น atomic (กัน double-answer)
 *   ถ้า session ปิดไปแล้วระหว่างรอ job ก็เช็ค isInProSessionPublic() เองอีกชั้น
 *
 * ⚠️ grace ต้อง "นานกว่าที่ job ปกติจะ flush" — ไม่งั้นจะไปแย่ง job เดิมที่ยัง debounce อยู่ตามปกติ
 *
 * Schedule: ทุกนาที (routes/console.php) — buffer TTL 5 นาที จึงต้องจับให้ทันภายใน TTL
 *
 * Usage:
 *   php artisan fortune:pro-session-answer-recover
 *   php artisan fortune:pro-session-answer-recover --dry
 *   php artisan fortune:pro-session-answer-recover --limit=80
 */
class FortuneProSessionAnswerRecover extends Command
{
    protected $signature = 'fortune:pro-session-answer-recover
                            {--dry : Dry run — รายงานที่จะกู้ แต่ไม่ยิงจริง}
                            {--limit=50 : จำนวนสูงสุดต่อรอบ}';

    protected $description = 'กู้คำถาม Pro Session (Deep 39) ที่ลูกค้าถามแล้ว buffer ค้างไม่ถูกตอบ';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = max(1, (int) $this->option('limit'));

        $settings = FortuneTellingSetting::getSettings();
        $settleSec = (int) ($settings->pro_session_settle_seconds ?? 10);

        // grace: job ปกติ delay settleSec+1 แล้ว flush → เสร็จราว 15s
        //   ตั้ง 60s ขึ้นไป = มั่นใจว่า job ตายจริง ไม่ใช่กำลัง debounce อยู่
        $graceSec = max($settleSec + 45, 60);

        $buffer = app(MessageBuffer::class);
        $now = microtime(true);

        $recovered = 0;
        $skipped = 0;

        // ผู้สมัคร: บิลที่จ่ายแล้วใน 24 ชม. และเพิ่งขยับ (settle block เขียน conversation_state → updated_at เด้ง)
        //   buffer TTL 5 นาที → มองย้อน 10 นาทีพอ
        $candidates = FortuneReading::query()
            ->where('is_paid', true)
            ->where('paid_at', '>=', now()->subMinutes(1440))
            ->where('updated_at', '>=', now()->subMinutes(10))
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($candidates as $reading) {
            $userId = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
            if ($userId === '') {
                continue;
            }

            $buf = $buffer->peek('deep_qa', $userId);
            if (empty($buf)) {
                continue; // ไม่มี buffer ค้าง = ปกติ
            }

            $lastAt = end($buf)['at'] ?? 0;
            $stuckSec = (int) ($now - $lastAt);
            if ($stuckSec < $graceSec) {
                $skipped++; // ยัง debounce ปกติอยู่ — ห้ามแย่ง job เดิม

                continue;
            }

            // session ต้องยังเปิดอยู่ ไม่งั้นตอบไปก็ผิดจังหวะ
            // ⚠️ อ่าน "ธงดิบ" เท่านั้น — **ห้ามเรียก isInProSession()** ตรงนี้
            //   เพราะมันไม่ใช่ read-only: หมดเวลาเมื่อไหร่มันจะ clearProSessionFlags() ให้เลย
            //   cron นี้รันทุกนาทีกับผู้สมัครสูงสุด 50 ราย → จะไล่ปิด session ของลูกค้าที่ยังไม่ได้ทักเลย
            //   แล้วไปแย่งงาน cron แจ้งหมดเวลา (fortune:deep-auto-finalize) จนลูกค้าไม่ได้รับข้อความ "หมดเวลา"
            //   การเช็คเวลาแบบมีผลข้างเคียงปล่อยให้เป็นหน้าที่ของ job ตอน execute (บริบทเดียวกับข้อความปกติ)
            if (! $reading->getConversationState('pro_session_active', false)) {
                continue;
            }

            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');
            $preview = mb_substr(implode(' | ', array_map(fn ($m) => $m['text'] ?? '', $buf)), 0, 80);

            $this->warn("  #{$reading->id} buffer deep_qa ค้าง (".count($buf)." msg, {$stuckSec}s) → re-dispatch: {$preview}");

            if (! $dry) {
                ProcessBufferedProSessionMessageJob::dispatch($reading->id, $platform, $userId, $settleSec);

                Log::warning('ProSession Answer Recover: re-dispatch stuck buffer', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'buffer_count' => count($buf),
                    'stuck_sec' => $stuckSec,
                ]);
            }
            $recovered++;
        }

        $this->info("📊 pro-session buffer-recover {$recovered} | skipped(ยังไม่ stuck) {$skipped}");

        return 0;
    }
}
