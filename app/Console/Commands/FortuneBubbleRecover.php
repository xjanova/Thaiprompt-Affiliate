<?php

namespace App\Console\Commands;

use App\Jobs\SendFortuneBubbleJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 (2026-08-28) กู้ "คำทำนายส่งไปได้ครึ่งเดียว" ของสายบับเบิ้ล
 *
 * ## ทำไมต้องมีตัวนี้แยกจาก redeliver เดิม
 *
 * สายบับเบิ้ลส่งกล่องแรกแบบ sync แล้วยกกล่อง 2..N ขึ้นคิว
 * ⇒ กล่องแรกถึงลูกค้า → `markDelivered()` ทำงาน → **cron redeliver เดิมมองว่าส่งครบแล้ว**
 * ถ้า worker ตาย/คิวหาย ตรงนั้น ลูกค้าที่จ่ายเงินจะได้คำทำนาย **ท่อนแรกท่อนเดียวถาวร**
 * และไม่มี error ที่ไหนเลย — `failed()` ของ job ก็ไม่ทำงานเพราะ job ไม่เคยถูกหยิบไปรัน
 *
 * ## แหล่งความจริง = MySQL ไม่ใช่ Cache
 *
 * `conversation_state.bubble_pending` (+ `bubble_pending_at`) เขียนไว้ **ก่อน** ขึ้นคิว
 * ห้ามย้ายไป Cache เด็ดขาด — deploy รัน `cache:clear` = `flushdb` ทั้ง redis DB 1
 * ตาข่ายที่อ่าน Cache จะ "กู้ของที่ถูกล้างทิ้งไม่ได้ตามนิยาม" (บทเรียน FTU-260821-K9664)
 *
 * ## ท่ากู้: เทที่เหลือรวมเป็นกล่องเดียว ไม่ผ่าใหม่
 *
 * ถึงจุดนี้ลูกค้ารอมานานแล้ว — ความครบสำคัญกว่าความสวย
 *
 * ใช้:
 *   php artisan fortune:bubble-recover
 *   php artisan fortune:bubble-recover --dry
 *   php artisan fortune:bubble-recover --limit=80
 */
class FortuneBubbleRecover extends Command
{
    protected $signature = 'fortune:bubble-recover
                            {--dry : Dry run — รายงานที่จะกู้ แต่ไม่ยิงจริง}
                            {--limit=50 : จำนวนสูงสุดต่อรอบ}';

    protected $description = 'กู้คำทำนายสายบับเบิ้ลที่ส่งไปได้ครึ่งเดียว (worker ตาย/คิวหาย)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = max(1, (int) $this->option('limit'));

        $settings = FortuneTellingSetting::getSettings();

        // grace: เวลาที่ลูกโซ่ควรใช้จนจบ = (กล่องมากสุด × ระยะห่างมากสุด) + เผื่อคิวหน่วง
        //   ต่ำกว่านี้ = ไปแย่งส่งทับ job ที่ยังทำงานปกติอยู่ → ลูกค้าเห็นข้อความซ้ำ
        $maxBubbles = max(1, (int) ($settings->fortune_chat_bubble_max ?? 4));
        $gapMax = max(1, (int) ($settings->fortune_chat_bubble_gap_max ?? 10));
        $graceSec = max(120, ($maxBubbles * $gapMax) + 90);

        $recovered = 0;
        $skipped = 0;

        // ผู้สมัคร: reading ที่เพิ่งขยับ (rememberPending เขียน conversation_state → updated_at เด้ง)
        //   หน้าต่าง 2 ชม. กว้างพอสำหรับทุกเคสจริง และแคบพอให้ query ไม่กวาดทั้งตาราง
        $candidates = FortuneReading::query()
            ->where('updated_at', '>=', now()->subHours(2))
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($candidates as $reading) {
            $pending = $reading->getConversationState('bubble_pending');
            $pendingAt = $reading->getConversationState('bubble_pending_at');

            if (! is_array($pending) || empty($pendingAt)) {
                continue; // ไม่มีกล่องค้าง = ปกติ
            }

            try {
                $stuckSec = (int) Carbon::parse($pendingAt)->diffInSeconds(now(), true);
            } catch (\Throwable $e) {
                continue; // timestamp พัง — ปล่อยไว้ ไม่เดา
            }

            if ($stuckSec < $graceSec) {
                $skipped++; // ลูกโซ่ยังวิ่งปกติอยู่ — ห้ามแย่งส่ง

                continue;
            }

            $platform = (string) ($pending['platform'] ?? 'facebook');
            $userId = (string) ($pending['user_id'] ?? '');
            $bubbles = array_values(array_filter(
                (array) ($pending['bubbles'] ?? []),
                static fn ($b) => is_string($b) && trim($b) !== ''
            ));
            $tail = $pending['tail'] ?? null;
            $tailQr = (array) ($pending['tail_qr'] ?? []);

            if ($userId === '' || ($bubbles === [] && ($tail === null || trim((string) $tail) === ''))) {
                // ธงเสียหาย/ว่าง — ล้างทิ้ง ไม่ต้องกู้
                if (! $dry) {
                    SendFortuneBubbleJob::clearPending($reading->id);
                }

                continue;
            }

            $rest = trim(implode("\n\n", $bubbles));

            $this->warn("  reading {$reading->id} ({$platform}) ค้าง {$stuckSec}s · เหลือ ".count($bubbles).' กล่อง');

            if ($dry) {
                $this->line('    [DRY] '.mb_substr($rest, 0, 80).'...');
                $recovered++;

                continue;
            }

            try {
                if ($platform === 'line') {
                    $line = new LineFortuneService($settings);

                    if ($rest !== '') {
                        $line->sendMessage($userId, $rest);
                    }

                    if ($tail !== null && trim((string) $tail) !== '') {
                        $line->sendMessage($userId, (string) $tail, ['quick_replies' => $tailQr]);
                    }
                } else {
                    $fb = new FacebookWebhookService($settings);

                    if ($rest !== '') {
                        $fb->sendMessage($userId, $rest, [
                            'allow_duplicate' => true,
                            'no_default_qr' => true,
                        ]);
                    }

                    if ($tail !== null && trim((string) $tail) !== '') {
                        $fb->sendQuickReplies($userId, (string) $tail, $tailQr);
                    }
                }

                SendFortuneBubbleJob::clearPending($reading->id);
                $recovered++;

                Log::critical('💬 Bubble: กู้คำทำนายที่ส่งไปครึ่งเดียว (worker/คิวไม่ทำงาน)', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'user_id' => $userId,
                    'stuck_sec' => $stuckSec,
                    'bubbles_left' => count($bubbles),
                ]);
            } catch (\Throwable $e) {
                // ส่งไม่สำเร็จ → **ไม่ล้างธง** รอบหน้าลองใหม่
                Log::error('💬 Bubble: กู้ไม่สำเร็จ (จะลองใหม่รอบหน้า)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("💬 bubble-recover: กู้ {$recovered} · ข้าม (ยังไม่ถึง grace {$graceSec}s) {$skipped}");

        return self::SUCCESS;
    }
}
