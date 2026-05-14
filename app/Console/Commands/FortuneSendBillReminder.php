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
        {--min=20 : นาทีต่ำสุดที่บิลค้าง ก่อนเริ่มทวง (default 20)}
        {--max=60 : นาทีสูงสุดที่บิลค้าง — เก่ากว่านี้ปล่อย expire เอง (default 60)}
        {--dry-run : ไม่ dispatch จริง — แค่ scan + รายงาน}';

    protected $description = 'สแกนบิลที่ค้างจ่าย + dispatch SendBillReminderJob ทวงลูกค้าด้วย AI';

    public function handle(): int
    {
        $minMinutes = (int) $this->option('min');
        $maxMinutes = (int) $this->option('max');
        $dryRun = (bool) $this->option('dry-run');

        $pendingStatuses = [
            FortuneReading::STATUS_PENDING_PAYMENT,
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
        ];

        $readings = FortuneReading::query()
            ->whereIn('conversation_status', $pendingStatuses)
            ->where('is_paid', false)
            ->where('created_at', '<=', now()->subMinutes($minMinutes))
            ->where('created_at', '>=', now()->subMinutes($maxMinutes))
            ->whereHas('uniquePaymentAmount', function ($q) {
                $q->where('status', 'reserved')
                    ->where('expires_at', '>', now());
            })
            ->get();

        $dispatched = 0;
        $skipped = 0;

        foreach ($readings as $reading) {
            // 🩹 Dedup — ส่งครั้งเดียวต่อบิล
            if ($reading->getConversationState('bill_reminder_sent_at')) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  [dry] #%d  %s  %s  ฿%s  อายุ %d นาที',
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
                Log::warning('FortuneSendBillReminder: dispatch fail', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("📊 Scan complete — total: {$readings->count()} | dispatched: {$dispatched} | skipped (already reminded): {$skipped}");

        Log::info('FortuneSendBillReminder: scan complete', [
            'total' => $readings->count(),
            'dispatched' => $dispatched,
            'skipped' => $skipped,
            'min_minutes' => $minMinutes,
            'max_minutes' => $maxMinutes,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }
}
