<?php

namespace App\Console\Commands;

use App\Jobs\SendBillReminderJob;
use App\Models\FortuneReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 💸 (2026-05-14) Bill Reminder Scanner — หาบิลค้างจ่ายแล้ว dispatch job ทวง
 *
 * Schedule: every minute (ดู routes/console.php)
 *
 * ⏰ (2026-06-12) ปรับเป็น 3 จังหวะตลอดอายุบิล (เดิมทวงครั้งเดียวที่ 2-10 นาที):
 *   เจ้าของสั่ง: "คอยเตือนเป็นระยะถึงบิลที่ยังไม่ได้ชำระ — อย่าเอาแต่ส่งกล่องข้อความเดิมๆ"
 *   บิลอายุ 3 ชม. (bill_payment_timeout_minutes) → เตือน 3 ครั้ง โทนต่างกัน:
 *     - stage 1: นาทีที่ {min}-15      (เช็คอินแรก — ติดขัดอะไรไหม)
 *     - stage 2: ~45-60% ของอายุบิล   (เช็คอินกลาง — ทักนุ่มๆ)
 *     - stage 3: 35 นาทีสุดท้าย        (ครั้งสุดท้าย — บอกตรงๆ ว่าบิลใกล้ถูกยกเลิก)
 *   ข้อความ AI สร้างใหม่ทุกครั้ง (persona + RAG) → ไม่ใช่กล่องเดิมซ้ำ
 *   dedup ผ่าน conversation_state.bill_reminder_stage (job เช็คซ้ำอีกชั้น)
 *
 * เงื่อนไข:
 *   - status = pending_payment / celtic_pending_payment
 *   - is_paid = false
 *   - UPA reserved + expires_at > now()
 */
class FortuneSendBillReminder extends Command
{
    protected $signature = 'fortune:bill-reminder
        {--min=2 : นาทีต่ำสุดที่บิลค้าง (pending_payment) ก่อนเริ่มทวง stage 1 (default 2)}
        {--max=15 : นาทีสูงสุดของ stage 1 (default 15)}
        {--method-min=2 : นาทีต่ำสุดสำหรับ awaiting_payment_method (default 2)}
        {--method-max=10 : นาทีสูงสุดสำหรับ awaiting_payment_method (default 10)}
        {--dry-run : ไม่ dispatch จริง — แค่ scan + รายงาน}';

    protected $description = 'สแกนบิลที่ค้างจ่าย + dispatch SendBillReminderJob ทวงลูกค้าด้วย AI 3 จังหวะ (รองรับ awaiting_payment_method)';

    public function handle(): int
    {
        // 💸 (2026-06-26) toggle กระตุ้นจ่ายบิล ปิด → ไม่ทวงบิล (เตือนบิลค้าง)
        if (! (bool) (\App\Models\FortuneTellingSetting::getSettings()->enable_bill_payment_nudge ?? true)) {
            $this->info('ℹ️ กระตุ้นจ่ายบิลถูกปิด (enable_bill_payment_nudge=off) — ข้าม bill-reminder');

            return 0;
        }

        $minMinutes = (int) $this->option('min');
        $maxMinutes = (int) $this->option('max');
        $methodMinMinutes = (int) $this->option('method-min');
        $methodMaxMinutes = (int) $this->option('method-max');
        $dryRun = (bool) $this->option('dry-run');

        $dispatched = 0;
        $skipped = 0;

        // 🔹 Branch 1: pending_payment / celtic_pending_payment — เลือก QR แล้ว ยังไม่โอน
        //   require UPA reserved + ยังไม่หมดอายุ
        $pendingStatuses = [
            FortuneReading::STATUS_PENDING_PAYMENT,
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
        ];

        // ⏰ หน้าต่าง 3 จังหวะ คำนวณจากอายุบิลจริง (default 180 นาที)
        //   ตัวอย่าง 180 นาที: stage1 = 2-15 / stage2 = 81-108 / stage3 = 145-172
        $timeout = FortuneReading::billTimeoutMinutes();
        $stageWindows = [
            1 => [$minMinutes, min($maxMinutes, $timeout - 5)],
            2 => [(int) floor($timeout * 0.45), (int) floor($timeout * 0.6)],
            3 => [max(1, $timeout - 35), max(2, $timeout - 8)],
        ];

        $pendingReadings = FortuneReading::query()
            ->whereIn('conversation_status', $pendingStatuses)
            ->where('is_paid', false)
            ->where('created_at', '<=', now()->subMinutes($minMinutes))
            ->where('created_at', '>=', now()->subMinutes($timeout))
            ->whereHas('uniquePaymentAmount', function ($q) {
                $q->where('status', 'reserved')
                    ->where('expires_at', '>', now());
            })
            ->get();

        foreach ($pendingReadings as $reading) {
            $ageMinutes = (int) $reading->created_at->diffInMinutes(now());

            // หา stage ที่อายุบิลตกอยู่ในหน้าต่าง
            $desiredStage = 0;
            foreach ($stageWindows as $stage => [$from, $to]) {
                if ($ageMinutes >= $from && $ageMinutes <= $to) {
                    $desiredStage = $stage;
                    break;
                }
            }

            if ($desiredStage === 0) {
                continue; // อยู่นอกหน้าต่างทุก stage — เงียบไว้
            }

            // stage ที่ส่งไปแล้ว (รองรับ legacy: bill_reminder_sent_at เดิม = stage 1)
            $sentStage = (int) $reading->getConversationState('bill_reminder_stage', 0);
            if ($sentStage === 0 && $reading->getConversationState('bill_reminder_sent_at')) {
                $sentStage = 1;
            }

            if ($sentStage >= $desiredStage) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  [dry-pending] #%d  %s  %s  ฿%s  อายุ %d นาที  stage %d',
                    $reading->id,
                    str_pad((string) $reading->conversation_status, 25, ' '),
                    $reading->bill_reference ?? '-',
                    $reading->amount_paid,
                    $ageMinutes,
                    $desiredStage
                ));
                $dispatched++;

                continue;
            }

            try {
                SendBillReminderJob::dispatch($reading->id, $desiredStage);
                $dispatched++;
            } catch (\Throwable $e) {
                Log::warning('FortuneSendBillReminder: dispatch fail (pending)', [
                    'reading_id' => $reading->id,
                    'stage' => $desiredStage,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 🔹 Branch 2 (2026-05-24): awaiting_payment_method — ลูกค้าเห็นปุ่ม QR/บัตร แต่ไม่กด
        //   ไม่ require UPA (ยังไม่ได้เลือกวิธีจ่าย) + short window 5-30 นาที (decision fatigue)
        $methodReadings = FortuneReading::query()
            ->where('conversation_status', FortuneReading::STATUS_AWAITING_PAYMENT_METHOD)
            ->where('is_paid', false)
            ->where('created_at', '<=', now()->subMinutes($methodMinMinutes))
            ->where('created_at', '>=', now()->subMinutes($methodMaxMinutes))
            ->get();

        foreach ($methodReadings as $reading) {
            if ($reading->getConversationState('bill_reminder_sent_at')) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  [dry-method] #%d  awaiting_payment_method  ฿%s  อายุ %d นาที',
                    $reading->id,
                    $reading->amount_paid,
                    (int) $reading->created_at->diffInMinutes(now())
                ));
                $dispatched++;

                continue;
            }

            try {
                SendBillReminderJob::dispatch($reading->id);
                $dispatched++;
            } catch (\Throwable $e) {
                Log::warning('FortuneSendBillReminder: dispatch fail (method)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $total = $pendingReadings->count() + $methodReadings->count();
        $this->info("📊 Scan complete — total: {$total} | dispatched: {$dispatched} | skipped (already reminded): {$skipped}");

        Log::info('FortuneSendBillReminder: scan complete', [
            'total' => $total,
            'pending_count' => $pendingReadings->count(),
            'method_count' => $methodReadings->count(),
            'dispatched' => $dispatched,
            'skipped' => $skipped,
            'min_minutes' => $minMinutes,
            'max_minutes' => $maxMinutes,
            'method_min_minutes' => $methodMinMinutes,
            'method_max_minutes' => $methodMaxMinutes,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }
}
