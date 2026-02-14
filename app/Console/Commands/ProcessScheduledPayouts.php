<?php

namespace App\Console\Commands;

use App\Services\PayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessScheduledPayouts Command
 *
 * ประมวลผล Payout Requests ที่ถูก schedule ไว้และถึงเวลาแล้ว
 *
 * สำหรับระบบ Payout ที่ตั้งค่าเป็น auto_schedule:
 * - scheduled_at ถึงเวลา → approve อัตโนมัติ
 * - ประมวลผลจ่ายเงินทันที
 */
class ProcessScheduledPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:process-scheduled
                            {--dry-run : แสดงรายการที่จะถูกประมวลผลโดยไม่ทำจริง}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ประมวลผล Scheduled Payout Requests ที่ถึงกำหนดแล้ว';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 เริ่มประมวลผล Scheduled Payouts...');
        $this->newLine();

        $payoutService = new PayoutService;
        $dryRun = $this->option('dry-run');

        try {
            if ($dryRun) {
                // ดึงรายการที่จะประมวลผล
                $payouts = \App\Models\PayoutRequest::where('status', \App\Models\PayoutRequest::STATUS_SCHEDULED)
                    ->where('scheduled_at', '<=', now())
                    ->get();

                if ($payouts->isEmpty()) {
                    $this->info('ℹ️ ไม่มี Scheduled Payouts ที่ถึงกำหนด');

                    return Command::SUCCESS;
                }

                $this->warn('📋 [DRY RUN] รายการที่จะถูกประมวลผล:');
                $this->table(
                    ['ID', 'User ID', 'Type', 'Net Amount', 'Scheduled At'],
                    $payouts->map(fn ($p) => [
                        $p->id,
                        $p->user_id,
                        $p->earning_type,
                        number_format($p->net_amount, 2),
                        $p->scheduled_at->format('Y-m-d H:i'),
                    ])->toArray()
                );

                return Command::SUCCESS;
            }

            $results = $payoutService->processScheduledPayouts();

            // แสดงผลลัพธ์
            $this->info('📊 ผลการประมวลผล:');
            $this->table(
                ['รายการ', 'จำนวน'],
                [
                    ['✅ ประมวลผลสำเร็จ', $results['processed'] ?? 0],
                    ['❌ ผิดพลาด', $results['failed'] ?? 0],
                ]
            );

            if (! empty($results['errors'])) {
                $this->newLine();
                $this->error('❗ รายการที่ผิดพลาด:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - Payout #{$error['payout_id']}: {$error['error']}");
                }
            }

            $this->newLine();
            $this->info('✅ ประมวลผลเสร็จสิ้น!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());
            Log::error('ProcessScheduledPayouts failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
