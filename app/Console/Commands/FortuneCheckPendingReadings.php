<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ตรวจสอบบิลดูดวงที่ชำระเงินแล้วแต่ยังไม่ได้รับคำทำนาย
 *
 * ทำงานเป็น safety net สำหรับกรณี:
 * 1. ProcessDeepFortuneReadingJob ล้มเหลว/ถูกฆ่าโดย process timeout
 * 2. Queue worker ไม่ทำงาน / queue stuck
 * 3. proc_open() background process ไม่ start
 * 4. คนดูดวงพร้อมกันเยอะ ทำให้ job ค้างใน queue
 *
 * Command นี้:
 * - เช็คบิลที่ is_paid=true แต่ยังไม่มี deep_response
 * - เฉพาะที่ชำระเงินมาแล้ว 2-30 นาที (ให้เวลา job ทำงานปกติก่อน)
 * - Dispatch ProcessDeepFortuneReadingJob ใหม่ให้อัตโนมัติ
 * - ป้องกัน duplicate ด้วย conversation_status check
 *
 * Schedule: ทุก 1 นาที (everyMinute)
 */
class FortuneCheckPendingReadings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fortune:check-pending
                            {--dry-run : แสดงผลอย่างเดียว ไม่ dispatch job}
                            {--force : บังคับ retry ทั้งหมด ไม่ว่าจะผ่านมานานแค่ไหน}';

    /**
     * The console command description.
     */
    protected $description = 'ตรวจสอบบิลดูดวงที่ชำระเงินแล้วแต่ยังไม่ได้คำทำนาย → retry อัตโนมัติ';

    /**
     * เวลาขั้นต่ำหลังชำระเงินก่อนจะ retry (นาที)
     * ให้เวลา job ทำงานปกติก่อน ไม่ dispatch ซ้ำซ้อน
     */
    protected const MIN_WAIT_MINUTES = 2;

    /**
     * เวลาสูงสุดที่จะ retry (นาที)
     * หลังจากนี้ถือว่าเก่าเกินไป ต้องให้แอดมินจัดการ
     */
    protected const MAX_WAIT_MINUTES = 30;

    /**
     * จำนวน retry สูงสุดต่อ reading ใน command นี้
     * ป้องกัน dispatch ซ้ำไม่จำกัด
     */
    protected const MAX_AUTO_RETRIES = 3;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('🔮 ตรวจสอบบิลดูดวงที่รอคำทำนาย...');

        // ค้นหาบิลที่ชำระเงินแล้วแต่ยังไม่มี deep_response
        $query = FortuneReading::where('is_paid', true)
            ->where('reading_type', 'deep')
            ->whereNull('deep_response')
            ->where(function ($q) {
                // สถานะ paid (รอ processing) หรือ completed (job failed + safety net เปลี่ยนแล้ว)
                $q->where('conversation_status', FortuneReading::STATUS_PAID)
                    ->orWhere(function ($sub) {
                        // completed แต่ยังไม่มี deep_response = job ล้มเหลว
                        $sub->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                            ->whereNull('deep_response');
                    });
            })
            ->whereNotNull('paid_at');

        // ไม่ force → กรองเฉพาะ 2-30 นาทีหลังชำระ
        if (! $isForce) {
            $query->where('paid_at', '<=', now()->subMinutes(self::MIN_WAIT_MINUTES))
                ->where('paid_at', '>=', now()->subMinutes(self::MAX_WAIT_MINUTES));
        }

        $pendingReadings = $query->orderBy('paid_at', 'asc')->get();

        if ($pendingReadings->isEmpty()) {
            $this->info('✅ ไม่มีบิลที่รอคำทำนาย');

            return self::SUCCESS;
        }

        $this->info("📋 พบ {$pendingReadings->count()} บิลที่รอคำทำนาย");

        $dispatched = 0;
        $skipped = 0;

        foreach ($pendingReadings as $reading) {
            $waitMinutes = (int) $reading->paid_at->diffInMinutes(now());
            $billRef = $reading->bill_reference ?? "#{$reading->id}";
            $platform = $reading->platform ?? 'facebook';
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

            // ตรวจสอบ retry count จาก conversation_state
            $retryCount = $reading->getConversationState('auto_retry_count', 0);
            if ($retryCount >= self::MAX_AUTO_RETRIES && ! $isForce) {
                $this->warn("  ⏭  {$billRef} — retry ครบ {$retryCount} ครั้งแล้ว (ข้าม) ต้องให้แอดมิน retry");
                $skipped++;

                continue;
            }

            // ตรวจสอบว่ามี user ID สำหรับส่งข้อความหรือไม่
            if (empty($userId)) {
                $this->warn("  ⏭  {$billRef} — ไม่มี User ID (ข้าม)");
                $skipped++;

                continue;
            }

            // ตรวจสอบว่ามีคำถามสำหรับทำนายหรือไม่
            $hasQuestions = ! empty($reading->getCollectedQuestions())
                || ! empty($reading->questions);
            if (! $hasQuestions) {
                $this->warn("  ⏭  {$billRef} — ไม่มีคำถาม (ข้าม)");
                $skipped++;

                continue;
            }

            $this->info("  🔄 {$billRef} — รอ {$waitMinutes} นาที, retry #{$retryCount} → dispatch job");

            if (! $isDryRun) {
                try {
                    // อัพเดท retry count
                    $reading->setConversationState('auto_retry_count', $retryCount + 1);
                    $reading->setConversationState('last_auto_retry_at', now()->toIso8601String());

                    // เปลี่ยนสถานะกลับเป็น paid เพื่อให้ job ทำงานได้
                    if ($reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                        $reading->update(['conversation_status' => FortuneReading::STATUS_PAID]);
                    }

                    // Dispatch job
                    ProcessDeepFortuneReadingJob::dispatchSmart(
                        $reading->id, null, $platform, $userId
                    );

                    Log::info('fortune:check-pending: dispatch retry job', [
                        'reading_id' => $reading->id,
                        'bill_reference' => $billRef,
                        'retry_count' => $retryCount + 1,
                        'wait_minutes' => $waitMinutes,
                        'platform' => $platform,
                    ]);

                    $dispatched++;
                } catch (\Exception $e) {
                    $this->error("  ❌ {$billRef} — dispatch ล้มเหลว: {$e->getMessage()}");
                    Log::error('fortune:check-pending: dispatch failed', [
                        'reading_id' => $reading->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->info("  [DRY RUN] จะ dispatch job สำหรับ {$billRef}");
                $dispatched++;
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: dispatch {$dispatched} บิล, ข้าม {$skipped} บิล");

        if ($dispatched > 0) {
            Log::info('fortune:check-pending: สรุปผล', [
                'dispatched' => $dispatched,
                'skipped' => $skipped,
                'total_found' => $pendingReadings->count(),
            ]);
        }

        return self::SUCCESS;
    }
}
