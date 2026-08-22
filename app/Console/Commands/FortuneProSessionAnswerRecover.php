<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBufferedProSessionMessageJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\MessageBuffer;
use App\Services\FortuneConversationService;
use Carbon\Carbon;
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
 * 🛟 (2026-08-21) เปลี่ยนแหล่งความจริงจาก "buffer บน Cache" → "ธงบน conversation_state (MySQL)"
 *   เคส FTU-260821-K9664 พิสูจน์ว่าเวอร์ชันเดิมกู้ไม่ได้เลย: deploy รัน `php artisan cache:clear`
 *   ซึ่งเรียก `RedisStore::flush()` → `flushdb()` **ล้างทั้ง redis DB 1 ไม่ใช่ลบตาม prefix**
 *   → buffer หายทั้งก้อน → cron นี้ `peek()` แล้วเจอว่าง → `continue` → มองไม่เห็นอะไรเลย
 *   ลูกค้าจ่าย 39฿ ถาม 3 ข้อ เงียบ 8 นาที แล้วโดน "หมดเวลาทำนายแล้วค่ะ" ทับหน้า
 *
 * จับเคสเดียว (Pro Session ไม่มี state 'generating' แยก — handleProSession ทำงาน sync ในตัว job):
 *   มี pro_session_pending_q ค้างเกิน grace + session ยังเปิดอยู่ → re-dispatch job (ไม่ delay)
 *
 * Idempotent: takePendingProSessionQuestionPublic() หยิบใต้ Cache::lock (กัน double-answer)
 *   ถ้า session ปิดไปแล้วระหว่างรอ job ก็เช็ค isInProSessionPublic() เองอีกชั้น
 *
 * ⚠️ grace ต้อง "นานกว่าที่ job ปกติจะ flush" — ไม่งั้นจะไปแย่ง job เดิมที่ยัง debounce อยู่ตามปกติ
 *
 * Schedule: ทุกนาที (routes/console.php) — ต้องจับให้ทันภายใน PRO_SESSION_PENDING_GRACE_MINUTES (15 นาที)
 *   เพราะพ้นเพดานนั้นคำถามค้างจะเลิกยืดเวลา session แล้วโดนกวาดทิ้ง
 *
 * ⚠️ (2026-08-22) หน้าต่างมองย้อนข้างล่าง (15 นาที) ต้อง **เท่ากับ** PRO_SESSION_PENDING_GRACE_MINUTES เสมอ
 *   ไม่เท่ากันเมื่อไหร่ = มีช่วงที่ cron ยังไล่ตอบอยู่แต่เกราะหยุดนาฬิกาหลุดไปแล้ว → บิลถูกปิดทับ
 *   (เคสจริง FTU-260822-P2391 — 10 vs 15 ทำให้ลูกค้าจ่าย 39฿ ได้คำตอบ 0 ข้อ)
 *
 * ⚠️ ตาข่ายนี้กู้ได้เฉพาะงานที่ "หาย" ไม่ใช่งานที่ "รอ" — re-dispatch คือการไปต่อท้ายคิวเดิม
 *   ถ้าคิวตัน ยิ่ง dispatch ยิ่งต่อแถว ไม่ได้ช่วยอะไร (ต้องไปแก้ที่การแยกเลนคิวแทน)
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

        $recovered = 0;
        $skipped = 0;

        // ผู้สมัคร: บิลที่จ่ายแล้วใน 24 ชม. และเพิ่งขยับ (settle block เขียน conversation_state → updated_at เด้ง)
        //   🛟 (2026-08-21) เดิม 10 นาทีผูกกับ buffer TTL ซึ่งตอนนี้ไม่ใช่แหล่งความจริงแล้ว
        //   🔒 (2026-08-22) ผูกกับ PRO_SESSION_PENDING_GRACE_MINUTES ตรงๆ — ห้าม hardcode แยกกันอีก
        //      เกราะสองชั้น (cron ไล่ตอบ / has...() หยุดนาฬิกา) ต้องหมดอายุพร้อมกันเป๊ะ
        $lookbackMin = FortuneConversationService::PRO_SESSION_PENDING_GRACE_MINUTES;

        $candidates = FortuneReading::query()
            ->where('is_paid', true)
            ->where('paid_at', '>=', now()->subMinutes(1440))
            ->where('updated_at', '>=', now()->subMinutes($lookbackMin))
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($candidates as $reading) {
            $userId = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
            if ($userId === '') {
                continue;
            }

            // 🛟 (2026-08-21) แหล่งความจริง = ธงบน conversation_state (MySQL) ไม่ใช่ buffer บน Cache
            //
            //   เดิมด่านนี้คือ `peek('deep_qa', $userId)` แล้ว `if (empty($buf)) continue;`
            //   → **ตาข่ายกู้ที่ peek คีย์เดิม กู้ buffer ที่ "ถูกล้างทิ้ง" ไม่ได้ตามนิยาม**
            //   เคส FTU-260821-K9664: deploy รัน `cache:clear` (= flushdb ทั้ง redis DB 1) →
            //   buffer หาย → cron นี้รันทุกนาทีแต่มองไม่เห็นอะไรเลย → ลูกค้าจ่ายเงินแล้วเงียบ 8 นาที
            //   จนโดน cron แจ้ง "หมดเวลาทำนายแล้วค่ะ" ทับหน้า
            //
            //   conversation_state อยู่บน MySQL = deploy ล้างไม่ได้ → เห็นคำถามค้างเสมอ
            $pendingAt = $reading->getConversationState('pro_session_pending_q_at');
            $pending = $reading->getConversationState('pro_session_pending_q', []);

            if (empty($pendingAt) || ! is_array($pending) || $pending === []) {
                continue; // ไม่มีคำถามค้าง = ปกติ
            }

            try {
                $stuckSec = (int) Carbon::parse($pendingAt)->diffInSeconds(now(), true);
            } catch (\Throwable $e) {
                continue; // timestamp พัง — ปล่อยให้ clearProSessionFlags กวาดตอน session ปิด
            }

            if ($stuckSec < $graceSec) {
                $skipped++; // ยัง debounce ปกติอยู่ — ห้ามแย่ง job เดิม

                continue;
            }

            // buffer บน cache ยังอยู่ไหม (ใช้แค่รายงาน — ไม่ใช่เงื่อนไขตัดสินแล้ว)
            $buf = $buffer->peek('deep_qa', $userId);

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
            $preview = mb_substr(implode(' | ', array_map(fn ($t) => (string) $t, $pending)), 0, 80);
            $bufState = empty($buf) ? 'cache หาย' : 'cache ยังอยู่ '.count($buf).' msg';

            $this->warn("  #{$reading->id} คำถามค้าง ".count($pending)." ข้อ ({$stuckSec}s, {$bufState}) → re-dispatch: {$preview}");

            if (! $dry) {
                // windowSeconds=0 → job flush ทันที ไม่ต้องรอ debounce อีกรอบ (ค้างมา {$stuckSec}s แล้ว)
                ProcessBufferedProSessionMessageJob::dispatch($reading->id, $platform, $userId, 0);

                Log::warning('ProSession Answer Recover: re-dispatch คำถามค้าง', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'pending_count' => count($pending),
                    'cache_buffer_count' => count($buf),
                    'stuck_sec' => $stuckSec,
                ]);
            }
            $recovered++;
        }

        $this->info("📊 pro-session buffer-recover {$recovered} | skipped(ยังไม่ stuck) {$skipped}");

        return 0;
    }
}
