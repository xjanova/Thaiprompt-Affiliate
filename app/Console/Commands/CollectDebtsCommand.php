<?php

namespace App\Console\Commands;

use App\Services\DebtCollectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CollectDebtsCommand
 *
 * เก็บหนี้อัตโนมัติจาก earnings ที่ available
 * หักสูงสุด 50% จากรายได้ของ user ที่มีหนี้ค้างชำระ
 */
class CollectDebtsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debt:collect-batch
                            {--limit=50 : จำนวน users สูงสุดที่จะประมวลผล}
                            {--dry-run : แสดงรายการที่จะหักโดยไม่ทำจริง}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'เก็บหนี้อัตโนมัติจาก earnings ที่ available';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 เริ่มเก็บหนี้อัตโนมัติ...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('📋 [DRY RUN] จะแสดงรายการที่จะหักโดยไม่ทำจริง');
            $this->newLine();

            // แสดง users ที่มีหนี้
            $usersWithDebt = \App\Models\WalletDebt::active()
                ->selectRaw('user_id, COUNT(*) as debt_count, SUM(remaining_amount) as total_debt')
                ->groupBy('user_id')
                ->get();

            if ($usersWithDebt->isEmpty()) {
                $this->info('ℹ️ ไม่มี user ที่มีหนี้ค้างชำระ');

                return Command::SUCCESS;
            }

            $this->info("📋 พบ {$usersWithDebt->count()} users ที่มีหนี้");

            $tableData = $usersWithDebt->map(function ($item) {
                $availableEarnings = \App\Models\EarningsLedger::where('user_id', $item->user_id)
                    ->where('status', \App\Models\EarningsLedger::STATUS_AVAILABLE)
                    ->sum('net_amount');

                return [
                    $item->user_id,
                    $item->debt_count,
                    number_format($item->total_debt, 2),
                    number_format($availableEarnings, 2),
                    number_format(min($item->total_debt, $availableEarnings * 0.5), 2),
                ];
            })->toArray();

            $this->table(
                ['User ID', 'จำนวนหนี้', 'ยอดหนี้รวม', 'Earnings พร้อมใช้', 'จะหักได้ (สูงสุด)'],
                $tableData
            );

            return Command::SUCCESS;
        }

        // ดำเนินการจริง
        try {
            $debtService = app(DebtCollectionService::class);
            $results = $debtService->batchCollectDebts();

            $this->newLine();
            $this->info('📊 ผลการประมวลผล:');
            $this->table(
                ['รายการ', 'จำนวน'],
                [
                    ['✅ Users ที่ประมวลผล', $results['processed']],
                    ['💰 ยอดเก็บหนี้ได้', '฿'.number_format($results['collected'], 2)],
                    ['❌ ผิดพลาด', count($results['errors'])],
                ]
            );

            if (! empty($results['errors'])) {
                $this->newLine();
                $this->error('❗ รายการที่ผิดพลาด:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - User #{$error['user_id']}: {$error['error']}");
                }
            }

            Log::info('Batch debt collection completed', $results);

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());
            Log::error('Batch debt collection command failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ เก็บหนี้อัตโนมัติเสร็จสิ้น!');

        return Command::SUCCESS;
    }
}
