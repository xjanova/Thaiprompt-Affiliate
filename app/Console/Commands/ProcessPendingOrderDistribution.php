<?php

namespace App\Console\Commands;

use App\Services\OrderDistributionService;
use Illuminate\Console\Command;

/**
 * ProcessPendingOrderDistribution Command
 *
 * ประมวลผล Order Distribution ที่ค้างอยู่
 * สำหรับ Order ที่ชำระเงินแล้วแต่ยังไม่ได้ distribute
 *
 * ใช้สำหรับ:
 * - กู้คืน orders ที่ผิดพลาดระหว่างการประมวลผล
 * - ประมวลผล orders เก่าที่ยังไม่ได้ distribute
 */
class ProcessPendingOrderDistribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-distribution
                            {--limit=50 : จำนวน orders สูงสุดที่จะประมวลผล}
                            {--dry-run : แสดงรายการที่จะประมวลผลโดยไม่ทำจริง}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ประมวลผล Order Distribution ที่ค้างอยู่ (paid orders ที่ยังไม่ได้ distribute)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 เริ่มประมวลผล Pending Order Distributions...');
        $this->newLine();

        $distributionService = new OrderDistributionService;
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        try {
            $results = $distributionService->processPendingOrders($limit);

            if ($dryRun) {
                $this->warn('📋 [DRY RUN] รายการที่จะถูกประมวลผล:');
                $this->table(
                    ['Order ID', 'Order Number', 'Total Amount', 'Status'],
                    collect($results['orders'] ?? [])->map(fn ($o) => [
                        $o['id'],
                        $o['order_number'],
                        number_format($o['total_amount'], 2),
                        'pending',
                    ])->toArray()
                );

                return Command::SUCCESS;
            }

            // แสดงผลลัพธ์
            $this->info('📊 ผลการประมวลผล:');
            $this->table(
                ['รายการ', 'จำนวน'],
                [
                    ['✅ ประมวลผลสำเร็จ', $results['processed'] ?? 0],
                    ['❌ ผิดพลาด', $results['failed'] ?? 0],
                    ['⏭️ ข้าม (distribute แล้ว)', $results['skipped'] ?? 0],
                ]
            );

            if (! empty($results['errors'])) {
                $this->newLine();
                $this->error('❗ รายการที่ผิดพลาด:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - Order #{$error['order_id']}: {$error['error']}");
                }
            }

            $this->newLine();
            $this->info('✅ ประมวลผลเสร็จสิ้น!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
