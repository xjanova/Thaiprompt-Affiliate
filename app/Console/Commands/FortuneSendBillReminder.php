<?php

namespace App\Console\Commands;

use App\Jobs\SendBillReminderJob;
use App\Models\FortuneReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 💸 (2026-05-14) Bill Reminder Scanner — หาบิลค้างจ่ายแล้ว dispatch job ทวง
 *
 * Schedule: every 5 min (ดู routes/console.php)
 *
 * เงื่อนไข:
 *   - status = pending_payment / celtic_pending_payment
 *   - is_paid = false
 *   - created_at อายุ {min_minutes}-{max_minutes} นาที (default 20-60)
 *   - UPA reserved + expires_at > now()
 *   - ยังไม่เคยทวง (bill_reminder_sent_at = null)
 */
class FortuneSendBillReminder extends Command
{
    protected $signature = 'fortune:bill-reminder
        {--min=20 : นาทีต่ำสุดที่บิลค้าง (pending_payment) ก่อนเริ่มทวง (default 20)}
        {--max=60 : นาทีสูงสุดที่บิลค้าง — เก่ากว่านี้ปล่อย expire เอง (default 60)}
        {--method-min=5 : นาทีต่ำสุดสำหรับ awaiting_payment_method (default 5)}
        {--method-max=30 : นาทีสูงสุดสำหรับ awaiting_payment_method (default 30)}
        {--dry-run : ไม่ dispatch จริง — แค่ scan + รายงาน}';

    protected $description = 'สแกนบิลที่ค้างจ่าย + dispatch SendBillReminderJob ทวงลูกค้าด้วย AI (รองรับ awaiting_payment_method)';

    public function handle(): int
    {
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

        $pendingReadings = FortuneReading::query()
            ->whereIn('conversation_status', $pendingStatuses)
            ->where('is_paid', false)
            ->where('created_at', '<=', now()->subMinutes($minMinutes))
            ->where('created_at', '>=', now()->subMinutes($maxMinutes))
            ->whereHas('uniquePaymentAmount', function ($q) {
                $q->where('status', 'reserved')
                    ->where('expires_at', '>', now());
            })
            ->get();

        foreach ($pendingReadings as $reading) {
            if ($reading->getConversationState('bill_reminder_sent_at')) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  [dry-pending] #%d  %s  %s  ฿%s  อายุ %d นาที',
                    $reading->id,
                    str_pad((string) $reading->conversation_status, 25, ' '),
                    $reading->bill_reference ?? '-',
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
                Log::warning('FortuneSendBillReminder: dispatch fail (pending)', [
                    'reading_id' => $reading->id,
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
