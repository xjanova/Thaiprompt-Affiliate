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
                            {--limit=50 : จำนวนสูงสุดต่อรอบ}
                            {--reading= : เจาะจง reading id (ข้ามหน้าต่างเวลา + grace — ใช้ตอนจบเทคโอเวอร์/ตามส่งด้วยมือ)}';

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

        // 🔧 (2026-10-06) โหมดเจาะบิล — ใช้ตอนจบเทคโอเวอร์ / ตามส่งด้วยมือ (ไม่สนหน้าต่าง 2 ชม. และ grace)
        if ($only = $this->option('reading')) {
            $reading = FortuneReading::find((int) $only);
            $ok = $reading && ! $dry && self::recoverReading($reading, $settings);
            $this->info($ok ? "💬 bubble-recover: ส่งที่ค้างของบิล {$only} แล้ว" : "💬 bubble-recover: บิล {$only} ไม่มีอะไรส่ง/ส่งไม่ออก");

            return self::SUCCESS;
        }

        // ผู้สมัคร: reading ที่เพิ่งขยับ (rememberPending เขียน conversation_state → updated_at เด้ง)
        //   หน้าต่าง 2 ชม. กว้างพอสำหรับทุกเคสจริง และแคบพอให้ query ไม่กวาดทั้งตาราง
        //   🤫 (2026-10-06, bug-hunt P2) เฉพาะบิลที่มีกล่องค้างจริง — เดิมดึงทุกบิลที่แค่ขยับ แล้วเช็คเทคโอเวอร์ทีละบิล
        $candidates = FortuneReading::query()
            ->withoutJuntra() // 🌙 บิลเว็บจันทราไม่มีข้อความให้กู้ — ห้ามแย่งช่อง limit ของลูกค้าจริง
            ->where('updated_at', '>=', now()->subHours(2))
            ->whereRaw("JSON_VALID(conversation_state) AND JSON_TYPE(JSON_EXTRACT(conversation_state, '$.bubble_pending')) = 'OBJECT'")
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        // เช็คเทคโอเวอร์ทั้งชุดในคิวรีเดียว (เดิม N+1 — ทีละบิล)
        $takenOver = \App\Services\Fortune\TakeoverSendGuard::takenOverUserIds(
            $candidates->map(fn (FortuneReading $r) => \App\Services\Fortune\FortuneRecipient::userIdOf($r))->all()
        );

        foreach ($candidates as $reading) {
            // 🤫 (2026-10-06) แอดมินเทคโอเวอร์ลูกค้าคนนี้อยู่ → ไม่ส่ง พักไว้ ส่งตอนจบเทคโอเวอร์ (ไม่สนหน้าต่าง 2 ชม.)
            if (isset($takenOver[\App\Services\Fortune\FortuneRecipient::userIdOf($reading)])) {
                \App\Services\Fortune\TakeoverResumeService::defer($reading, \App\Services\Fortune\TakeoverResumeService::ITEM_BUBBLES);

                continue;
            }

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

            $this->warn("  reading {$reading->id} (".($pending['platform'] ?? 'facebook').") ค้าง {$stuckSec}s · เหลือ "
                .count((array) ($pending['bubbles'] ?? [])).' กล่อง');

            if ($dry) {
                $this->line('    [DRY] '.mb_substr(trim(implode("\n\n", array_map('strval', (array) ($pending['bubbles'] ?? [])))), 0, 80).'...');
                $recovered++;

                continue;
            }

            if (self::recoverReading($reading, $settings, $stuckSec)) {
                $recovered++;
            }
        }

        // จบรอบ — ไม่ทิ้ง context ของผู้รับคนสุดท้ายไว้ให้โค้ดถัดไปในโปรเซสเดียวกัน
        \App\Services\Fortune\FortunePageContext::forget();

        $this->info("💬 bubble-recover: กู้ {$recovered} · ข้าม (ยังไม่ถึง grace {$graceSec}s) {$skipped}");

        return self::SUCCESS;
    }

    /**
     * 🛟 เทกล่องที่ค้างของบิลนี้รวมเป็นกล่องเดียว แล้วล้างธง — true = ส่งออก (หรือไม่มีอะไรค้างแล้ว)
     *
     * ใช้ทั้ง cron (หลังพ้น grace) และ TakeoverResumeService ตอนจบเทคโอเวอร์ (bug-hunt L4 — ไม่สนหน้าต่าง 2 ชม.)
     * ส่งท่อนหลักไม่ออก = **ไม่ล้างธง** รอบหน้าลองใหม่ (เดิมล้างทั้งที่ไม่รู้ผล — แอดมินเทคโอเวอร์พอดี = ของหาย)
     */
    public static function recoverReading(FortuneReading $reading, FortuneTellingSetting $settings, ?int $stuckSec = null): bool
    {
        $pending = $reading->getConversationState('bubble_pending');
        if (! is_array($pending)) {
            return true; // ส่งครบไปแล้ว
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
            SendFortuneBubbleJob::clearPending($reading->id);

            return true;
        }

        $rest = trim(implode("\n\n", $bubbles));

        // 🏬 (2026-09-13) ผู้รับแต่ละคนต้องหาเพจ (token) ของตัวเอง — ห้ามค้าง context ของลูกค้าคนก่อน
        //    ไม่งั้นลูกค้าคนที่ 2+ ของเพจสาขาถูกส่งด้วย token เพจของคนแรก → Graph 400 → ของที่จ่ายแล้วหาย
        \App\Services\Fortune\FortunePageContext::forget();

        try {
            if ($platform === 'line') {
                $line = new LineFortuneService($settings);

                $ok = $rest === '' || $line->sendMessage($userId, $rest);

                if ($ok && $tail !== null && trim((string) $tail) !== '') {
                    $line->sendMessage($userId, (string) $tail, ['quick_replies' => $tailQr]);
                }
            } else {
                // ✈️ (2026-09-13) FB / Telegram — ผู้ส่งตามช่องทางของบิล
                $fb = \App\Services\Fortune\FortuneMessengerFactory::sender($platform, $userId, $settings) ?? new FacebookWebhookService($settings);

                $ok = $rest === '' || $fb->sendMessage($userId, $rest, [
                    'allow_duplicate' => true,
                    'no_default_qr' => true,
                ]);

                if ($ok && $tail !== null && trim((string) $tail) !== '') {
                    $fb->sendQuickReplies($userId, (string) $tail, $tailQr);
                }
            }

            if (! $ok) {
                Log::error('💬 Bubble: กู้ไม่สำเร็จ — ส่งไม่ออก (เก็บธงไว้ลองใหม่)', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                ]);

                return false;
            }

            SendFortuneBubbleJob::clearPending($reading->id);

            Log::critical('💬 Bubble: กู้คำทำนายที่ส่งไปครึ่งเดียว (worker/คิวไม่ทำงาน หรือค้างจากเทคโอเวอร์)', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'user_id' => $userId,
                'stuck_sec' => $stuckSec,
                'bubbles_left' => count($bubbles),
            ]);

            return true;
        } catch (\Throwable $e) {
            // ส่งไม่สำเร็จ → **ไม่ล้างธง** รอบหน้าลองใหม่
            Log::error('💬 Bubble: กู้ไม่สำเร็จ (จะลองใหม่รอบหน้า)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            \App\Services\Fortune\FortunePageContext::forget();
        }
    }
}
