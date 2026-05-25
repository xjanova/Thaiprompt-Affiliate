<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 (2026-05-25 Patch E) Soft reminder for paid Celtic stuck at celtic_picking
 *
 * เป้าหมาย: หาบิล Celtic 99฿ ที่ลูกค้าจ่ายแล้ว แต่ไม่ได้เปิดไพ่/ไม่ตอบ
 *           ส่ง DM soft reminder **ครั้งเดียวต่อ reading**
 *
 * เคสจริง R3711 (2026-05-25 06:59 → 4hr stuck):
 *   - paid 99฿ แล้ว status=celtic_picking
 *   - 0 user messages หลัง paid
 *   - 4 ชั่วโมงผ่านไป cron expire-stuck-paid ยังไม่ถึง (24hr threshold)
 *   - ลูกค้าน่าจะลังเล/ไม่เห็นปุ่ม/ปุ่ม timeout
 *
 * ทำไมไม่ใช้ fortune:celtic-recover --auto:
 *   - --auto ถูก disable (re-push annoy ลูกค้า + เปลือง FB quota)
 *   - command นี้ต่างจาก recover เพราะ:
 *     (a) ส่ง *ครั้งเดียว* ต่อ reading (mark flag กัน duplicate)
 *     (b) Soft tone — "หมอจันทรารอเปิดไพ่ให้นะคะ" ไม่ใช่ "ระบบตรวจพบ"
 *     (c) เฉพาะลูกค้าเงียบสนิท (0 user msg หลัง paid) — ไม่กวนคนกำลังคุย
 *
 * เงื่อนไข candidate:
 *   - reading_type = 'celtic_cross'
 *   - is_paid = true
 *   - conversation_status = 'celtic_picking'
 *   - paid_at อยู่ในช่วง [now-6hr, now-30min]  ← ให้เวลา flow ปกติทำงาน 30 min ก่อน
 *   - celtic_stuck_reminder_sent_at ใน conversation_state ยังว่าง
 *   - line_bot_messages ฝั่ง user หลัง paid_at = 0
 *
 * Schedule: ทุก 15 นาที (รัน 4 ครั้งใน 1 ชั่วโมง — ครอบ window 30 min - 6 hr)
 *
 * Usage:
 *   php artisan fortune:remind-stuck-celtic
 *   php artisan fortune:remind-stuck-celtic --dry
 *   php artisan fortune:remind-stuck-celtic --max-hours=6 --min-minutes=30
 */
class FortuneRemindStuckCeltic extends Command
{
    protected $signature = 'fortune:remind-stuck-celtic
                            {--dry : Dry run — แสดงรายการ ไม่ส่ง DM จริง}
                            {--min-minutes=30 : เวลา min หลัง paid_at (default 30 นาที)}
                            {--max-hours=6 : เวลา max หลัง paid_at (default 6 ชั่วโมง)}
                            {--limit=20 : จำนวน reading สูงสุดต่อรอบ}';

    protected $description = 'ส่ง soft reminder ครั้งเดียวให้ลูกค้า Celtic 99฿ ที่จ่ายแล้วเงียบหายไม่เปิดไพ่';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry');
        $minMinutes = max(5, (int) $this->option('min-minutes'));
        $maxHours = max(1, (int) $this->option('max-hours'));
        $limit = max(1, (int) $this->option('limit'));

        $cutoffMin = now()->subMinutes($minMinutes);
        $cutoffMax = now()->subHours($maxHours);

        $candidates = FortuneReading::query()
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->where('conversation_status', FortuneReading::STATUS_CELTIC_PICKING)
            ->whereNotNull('paid_at')
            ->where('paid_at', '<=', $cutoffMin)
            ->where('paid_at', '>=', $cutoffMax)
            ->orderBy('paid_at', 'asc')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มี Celtic stuck reading ที่ต้อง remind');

            return 0;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $reading) {
            $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];

            // กัน duplicate: ถ้าเคย remind แล้ว skip
            if (! empty($state['celtic_stuck_reminder_sent_at'])) {
                $skipped++;
                continue;
            }

            $platform = $reading->platform
                ?? (! empty($reading->facebook_user_id) ? 'facebook' : 'line');
            $userId = (string) ($reading->facebook_user_id ?? $reading->line_user_id ?? '');

            if ($userId === '') {
                $this->warn("  #{$reading->id} skip — no user_id");
                $skipped++;
                continue;
            }

            // เช็คว่า user ยังไม่ตอบหลัง paid_at (silence detector)
            $userMsgCount = DB::table('line_bot_conversations as c')
                ->join('line_bot_messages as m', 'm.conversation_id', '=', 'c.id')
                ->where('c.platform', $platform)
                ->where('c.line_user_id', $userId)
                ->where('m.role', 'user')
                ->where('m.created_at', '>', $reading->paid_at)
                ->count();

            if ($userMsgCount > 0) {
                // ลูกค้ามีคุยอยู่ — ไม่ต้องเตือน
                $skipped++;
                continue;
            }

            $name = $reading->facebook_user_name ?? 'เจ้าชะตา';
            $billRef = $reading->bill_reference ?? '-';
            $message = "🌙 หมอจันทรารอเปิดไพ่ให้คุณ{$name}อยู่นะคะ ✨\n\n"
                ."บิล Celtic 99฿ ของเจ้าชะตา (เลขที่ {$billRef}) ยังไม่ได้เริ่มเปิดไพ่เลย\n"
                ."ถ้าพร้อมแล้ว พิมพ์ \"เริ่มเปิดไพ่\" ได้เลยค่ะ 🃏\n\n"
                ."(บิลนี้ใช้ได้เสมอ — กลับมาทำต่อเมื่อสะดวกได้เลยนะคะ 🙏)";

            $this->info("  #{$reading->id} ({$platform}/{$name}) paid {$reading->paid_at}");

            if ($isDry) {
                $this->line('    [DRY] '.mb_substr($message, 0, 100).'...');
                continue;
            }

            try {
                $delivered = $this->sendReminder($platform, $userId, $message);

                if ($delivered) {
                    // Mark sent
                    $state['celtic_stuck_reminder_sent_at'] = now()->toIso8601String();
                    $reading->conversation_state = $state;
                    $reading->save();

                    $sent++;
                    Log::info('FortuneRemindStuckCeltic: reminder sent', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'paid_at' => $reading->paid_at?->toIso8601String(),
                    ]);
                } else {
                    $failed++;
                    $this->error("    ❌ ส่งไม่สำเร็จ");
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("    ❌ exception: {$e->getMessage()}");
                Log::warning('FortuneRemindStuckCeltic: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: sent={$sent} skipped={$skipped} failed={$failed}");

        return $failed > 0 ? 1 : 0;
    }

    /**
     * ส่ง reminder ผ่าน platform-specific service
     *
     * FB: POST_PURCHASE_UPDATE message tag (no 24hr window restriction since paid)
     * LINE: ปกติ pushMessage
     */
    protected function sendReminder(string $platform, string $userId, string $message): bool
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
            // LINE service uses LineMessagingService internally
            $lineService = app(LineFortuneService::class);
            if (method_exists($lineService, 'pushMessage')) {
                return $lineService->pushMessage($userId, $message);
            }
            if (method_exists($lineService, 'sendMessage')) {
                return $lineService->sendMessage($userId, $message);
            }
        }

        return false;
    }
}
