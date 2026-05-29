<?php

namespace App\Console\Commands;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🐛 (2026-05-28) Celtic Q&A Re-Deliver — หลักประกันว่าลูกค้าจ่าย 99฿ ได้รับคำทำนายเสมอ
 *
 * ปัญหา: AI สร้างคำตอบสำเร็จ + markCelticAnswered + บันทึก response ลง DB
 *   แต่ FB/LINE push ล้มเหลว (transient / 24hr window / #551) → ลูกค้าไม่ได้รับ เห็นแค่ "ติดขัด"
 *   เคสจริง: บิล FTU-260528-E8815 (reading 4009) — คำตอบ 908 ตัวอักษรอยู่ใน DB แต่ลูกค้าไม่เห็น
 *   (pending architectural fix #1 จาก hotfix 2026-05-21 — markAnswered ก่อน confirm delivery)
 *
 * วิธีแก้: ChannelManager set `delivered_at` เมื่อ push สำเร็จ. cron นี้หา question ที่
 *   answered แต่ delivered_at ยังว่าง → re-push ผ่าน POST_PURCHASE_UPDATE (FB) / push (LINE)
 *   → ลูกค้าได้รับภายใน 1-2 นาทีแม้ push แรกพลาด
 *
 * เงื่อนไข candidate:
 *   - answered_at not null + response ไม่ว่าง + delivered_at null
 *   - answered_at ในช่วง [now-maxHours, now-minSeconds]  (min-age ให้ sync push ลองก่อน)
 *   - delivery_attempts < max-attempts  (cap กัน loop)
 *   - reading เป็น Celtic paid
 *
 * Schedule: ทุกนาที (routes/console.php) — withoutOverlapping
 *
 * Usage:
 *   php artisan fortune:celtic-redeliver
 *   php artisan fortune:celtic-redeliver --dry
 *   php artisan fortune:celtic-redeliver --min-seconds=60 --max-hours=2 --max-attempts=3
 */
class FortuneCelticRedeliver extends Command
{
    protected $signature = 'fortune:celtic-redeliver
                            {--dry : Dry run — แสดงรายการ ไม่ส่งจริง}
                            {--min-seconds=60 : อายุขั้นต่ำหลัง answered (ให้ sync push ลองก่อน)}
                            {--max-hours=2 : อายุสูงสุด — ไม่ส่งคำตอบเก่าเกิน}
                            {--max-attempts=3 : จำนวนครั้งสูงสุดที่พยายาม re-deliver}
                            {--limit=30 : จำนวน question สูงสุดต่อรอบ}';

    protected $description = 'ส่งคำทำนาย Celtic ซ้ำให้ลูกค้าที่ AI ตอบแล้วแต่ push แรกไม่ถึง (delivered_at null)';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry');
        $minSeconds = max(15, (int) $this->option('min-seconds'));
        $maxHours = max(1, (int) $this->option('max-hours'));
        $maxAttempts = max(1, (int) $this->option('max-attempts'));
        $limit = max(1, (int) $this->option('limit'));

        $candidates = FortuneCelticQuestion::query()
            ->undelivered()
            ->where('answered_at', '<=', now()->subSeconds($minSeconds))
            ->where('answered_at', '>=', now()->subHours($maxHours))
            ->where('delivery_attempts', '<', $maxAttempts)
            ->with('reading')
            ->orderBy('answered_at', 'asc')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มีคำทำนาย Celtic ที่ค้างส่ง');

            return 0;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $q) {
            $reading = $q->reading;

            // ต้องเป็น Celtic paid เท่านั้น (กันเคสแปลก)
            if (! $reading || ! $reading->is_paid
                || $reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
                $skipped++;

                continue;
            }

            // 🐛 (2026-05-29) Safety net — session จบแล้ว (completed) ไม่ต้อง re-deliver รายข้อ
            //   Grand Finale ตอนจบรวมคำตอบทุกข้อให้แล้ว — ถ้า cron ส่งคำตอบรายข้อซ้ำ
            //   ลูกค้าจะเห็นคำตอบเก่าโผล่ "หลังสรุป" (เคสจริง reading 4191 สมร: ครอบครัวซ้ำ)
            //   mark delivered กัน cron วนจับทุกนาทีจน maxAttempts (terminal state — ไม่ต้องส่งแล้ว)
            //   หมายเหตุ: endCelticSession ก็ mark ให้แล้ว — นี่คือ safety net เผื่อ legacy/race
            if ($reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                $q->markDelivered();
                $skipped++;

                continue;
            }

            // resolve platform + userId (pattern เดียวกับทั้งระบบ — platform field ก่อน แล้ว ID pattern)
            $platform = $reading->platform;
            if (! $platform || ! in_array($platform, ['facebook', 'line'], true)) {
                $candidateId = $reading->facebook_user_id ?: $reading->platform_user_id ?: '';
                $platform = preg_match('/^U[a-f0-9]{32}$/i', $candidateId) ? 'line' : 'facebook';
            }
            $userId = $platform === 'line'
                ? (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '')
                : (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');

            if ($userId === '') {
                $this->warn("  Q#{$q->id} (reading {$reading->id}) skip — no user_id");
                $skipped++;

                continue;
            }

            $message = trim((string) $q->response)
                ."\n\n──────────────────────\n"
                .'💬 พิมพ์คำถามต่อได้เลย — หรือพิมพ์ *"เลิกทำนายและสรุปผล"* เมื่อพร้อมค่ะ ✨';

            $this->info("  Q#{$q->id} (reading {$reading->id}, {$platform}, attempt ".($q->delivery_attempts + 1).") answered {$q->answered_at}");

            if ($isDry) {
                $this->line('    [DRY] '.mb_substr((string) $q->response, 0, 80).'...');

                continue;
            }

            // นับ attempt ก่อนส่ง — แม้ exception ก็ถือว่าใช้ไป 1 ครั้ง (กัน loop)
            $q->forceFill(['delivery_attempts' => $q->delivery_attempts + 1])->save();

            try {
                $ok = $this->pushAnswer($platform, $userId, $message);

                if ($ok) {
                    $q->markDelivered();
                    $sent++;
                    Log::info('FortuneCelticRedeliver: re-delivered สำเร็จ', [
                        'question_id' => $q->id,
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'attempt' => $q->delivery_attempts,
                    ]);
                } else {
                    $failed++;
                    $this->error('    ❌ re-deliver ไม่สำเร็จ');
                    Log::warning('FortuneCelticRedeliver: re-deliver fail', [
                        'question_id' => $q->id,
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'attempt' => $q->delivery_attempts,
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("    ❌ exception: {$e->getMessage()}");
                Log::warning('FortuneCelticRedeliver: exception', [
                    'question_id' => $q->id,
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: re-delivered={$sent} skipped={$skipped} failed={$failed}");

        return $failed > 0 ? 1 : 0;
    }

    /**
     * push คำทำนายซ้ำผ่าน platform-specific service
     *
     * FB: POST_PURCHASE_UPDATE tag → ส่งได้แม้นอก 24hr window (ลูกค้าจ่ายแล้ว)
     * LINE: pushMessage (ไม่ติด reply token window)
     *
     * @return bool ส่งสำเร็จไหม
     */
    protected function pushAnswer(string $platform, string $userId, string $message): bool
    {
        if ($platform === 'facebook') {
            return app(FacebookWebhookService::class)->sendMessage(
                $userId,
                $message,
                [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]
            );
        }

        if ($platform === 'line') {
            $lineService = app(LineFortuneService::class);
            if (method_exists($lineService, 'pushMessage')) {
                return (bool) $lineService->pushMessage($userId, $message);
            }
            if (method_exists($lineService, 'sendMessage')) {
                return (bool) $lineService->sendMessage($userId, $message);
            }
        }

        return false;
    }
}
