<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\FortuneTakeoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * fortune:expire-conversations
 *
 * ปิด conversation ที่ถูกทิ้งไว้กลางทาง (ยูสเซ่อร์ไม่กลับมาตอบ)
 *
 * Flow เดิม — `FortuneReading::expireOldConversations()` ทำงานเฉพาะเมื่อ
 * ยูสเซ่อร์ส่งข้อความเข้ามาอีกครั้ง ทำให้ orphan conversations อยู่ถาวรใน DB
 *
 * Command นี้รันทุก 5 นาที เพื่อ:
 * - ปิด conversation ที่ค้างเกิน timeout (30 นาทีทั่วไป / 10 นาที PAID)
 * - ล้าง takeover ที่หมดเวลา
 */
class FortuneExpireConversations extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:expire-conversations
        {--dry-run : แสดงผลโดยไม่แก้ไขจริง}';

    /**
     * @var string
     */
    protected $description = 'ปิด conversation ดูดวงที่หมดอายุและล้าง takeover ที่หมดเวลา';

    /**
     * ประมวลผล command
     */
    public function handle(FortuneTakeoverService $takeoverService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // ========================================
        // 🎯 Phase K — ส่ง closing-pitch DM ก่อนยกเลิก (บิลอายุ 25-30 นาที)
        //    ปิดการขายอีกรอบโดย reframe ราคา + เน้นไพ่จาก "ดวงชนะ" ของลูกค้าเอง
        // ========================================
        $remindersSent = 0;

        if ($dryRun) {
            // ⏰ (2026-06-12) ใช้ billTimeoutMinutes (setting, default 180) แทน 30 นาทีเดิม
            $remindersSent = FortuneReading::whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
                ->where('is_paid', false)
                ->whereNotNull('unique_payment_amount_id')
                ->whereBetween('updated_at', [
                    now()->subMinutes(FortuneReading::billTimeoutMinutes()),
                    now()->subMinutes(max(1, FortuneReading::billTimeoutMinutes() - 5)),
                ])
                ->count();
        } else {
            $remindersSent = FortuneReading::sendExpiryReminders();
        }

        // ========================================
        // 🎯 Phase J — ยกเลิกบิล pending_payment ที่ค้างเกิน timeout (setting, default 3 ชม.)
        //    (cancel UPA + ส่ง FCM แจ้งแอพ SMS Checker) ก่อนปิด conversation
        // ⏰ (2026-06-12) เดิม 30 นาที — เจ้าของสั่งขยายเป็น 3 ชม. ผ่าน bill_payment_timeout_minutes
        // ========================================
        $cancelledBills = 0;

        if ($dryRun) {
            $cancelledBills = FortuneReading::whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
                ->where('is_paid', false)
                ->whereNotNull('unique_payment_amount_id')
                ->where('updated_at', '<', now()->subMinutes(FortuneReading::billTimeoutMinutes()))
                ->count();
        } else {
            $cancelledBills = FortuneReading::cancelExpiredPendingBills();
        }

        // ========================================
        // 1+2. ปิด conversation + PAID timeout (ส่วนที่ไม่ใช่บิล)
        // ========================================
        $expiredCount = 0;

        if ($dryRun) {
            // Dry-run: นับอย่างเดียว ไม่อัพเดต
            // หมายเหตุ: pending_payment ที่มีบิลถูกนับไปแล้วใน $cancelledBills — ไม่นับซ้ำ
            $expiredCount = FortuneReading::whereIn('conversation_status', [
                FortuneReading::STATUS_AWAITING_CONFIRMATION,
                FortuneReading::STATUS_BASIC_DONE,
                FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                FortuneReading::STATUS_COLLECTING_QUESTIONS,
                FortuneReading::STATUS_COLLECTING_TAROT,
            ])
                ->where('updated_at', '<', now()->subMinutes(FortuneReading::PAYMENT_TIMEOUT_MINUTES))
                ->count()
                + FortuneReading::where('conversation_status', FortuneReading::STATUS_PAID)
                    ->where('updated_at', '<', now()->subMinutes(FortuneReading::PAID_PROCESSING_TIMEOUT_MINUTES))
                    ->count();
        } else {
            $expiredCount = FortuneReading::expireAllOldConversations();
        }

        // ========================================
        // 3. ล้าง takeover ที่หมดเวลา
        // ========================================
        $takeoverCount = $dryRun
            ? FortuneReading::takeoverExpired()->count()
            : $takeoverService->cleanupExpired();

        // ========================================
        // สรุปผล
        // ========================================
        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $total = $remindersSent + $cancelledBills + $expiredCount + $takeoverCount;

        if ($total > 0) {
            Log::info($prefix.'fortune:expire-conversations', [
                'reminders_sent' => $remindersSent,
                'bills_cancelled' => $cancelledBills,
                'conversations_expired' => $expiredCount,
                'takeover_cleared' => $takeoverCount,
            ]);

            if ($remindersSent > 0) {
                $this->info($prefix."ส่ง closing-pitch DM: {$remindersSent} (บิลอายุ 25 นาที)");
            }
            if ($cancelledBills > 0) {
                $this->info($prefix."ยกเลิกบิลค้าง: {$cancelledBills} (แจ้ง SMS Checker แล้ว)");
            }
            $this->info($prefix."ปิด conversation: {$expiredCount}");
            $this->info($prefix."ล้าง takeover: {$takeoverCount}");
        }

        return self::SUCCESS;
    }
}
