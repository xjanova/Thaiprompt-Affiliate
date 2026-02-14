<?php

namespace App\Console\Commands;

use App\Models\EarningsLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ReleasePendingEarnings Command
 *
 * ปล่อย Earnings ที่ pending และถึงเวลา available แล้ว
 *
 * EarningsLedger มี available_at ที่กำหนดว่าเมื่อไหร่จะปล่อยให้ถอนได้
 * Command นี้จะเปลี่ยนสถานะจาก pending → available เมื่อถึงเวลา
 */
class ReleasePendingEarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earnings:release-pending
                            {--limit=100 : จำนวน earnings สูงสุดที่จะประมวลผล}
                            {--dry-run : แสดงรายการที่จะถูกปล่อยโดยไม่ทำจริง}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ปล่อย Earnings ที่ pending และถึงเวลา available แล้ว';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 เริ่มปล่อย Pending Earnings...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        // ดึง earnings ที่ pending และถึงเวลาแล้ว
        $query = EarningsLedger::where('status', EarningsLedger::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->limit($limit);

        $earnings = $query->get();

        if ($earnings->isEmpty()) {
            $this->info('ℹ️ ไม่มี Earnings ที่ต้องปล่อย');

            return Command::SUCCESS;
        }

        $this->info("📋 พบ {$earnings->count()} รายการที่ต้องปล่อย");
        $this->newLine();

        if ($dryRun) {
            $this->warn('📋 [DRY RUN] รายการที่จะถูกปล่อย:');
            $this->table(
                ['ID', 'User ID', 'Type', 'Net Amount', 'Available At'],
                $earnings->map(fn ($e) => [
                    $e->id,
                    $e->user_id,
                    $e->earning_type,
                    number_format($e->net_amount, 2),
                    $e->available_at?->format('Y-m-d H:i') ?? 'ทันที',
                ])->toArray()
            );

            return Command::SUCCESS;
        }

        $released = 0;
        $failed = 0;
        $errors = [];

        $bar = $this->output->createProgressBar($earnings->count());
        $bar->start();

        foreach ($earnings as $earning) {
            try {
                $earning->update([
                    'status' => EarningsLedger::STATUS_AVAILABLE,
                    'available_at' => now(),
                ]);
                $released++;

            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'id' => $earning->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to release earning', [
                    'earning_id' => $earning->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // แสดงผลลัพธ์
        $this->info('📊 ผลการประมวลผล:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['✅ ปล่อยสำเร็จ', $released],
                ['❌ ผิดพลาด', $failed],
            ]
        );

        if (! empty($errors)) {
            $this->newLine();
            $this->error('❗ รายการที่ผิดพลาด:');
            foreach ($errors as $error) {
                $this->line("  - Earning #{$error['id']}: {$error['error']}");
            }
        }

        Log::info('Pending earnings released', [
            'released' => $released,
            'failed' => $failed,
        ]);

        $this->newLine();
        $this->info('✅ ประมวลผลเสร็จสิ้น!');

        return Command::SUCCESS;
    }
}
