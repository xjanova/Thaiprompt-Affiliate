<?php

namespace App\Console\Commands;

use App\Services\OrderDistributionService;
use Illuminate\Console\Command;

/**
 * ProcessPendingOrderDistribution — แบ่งเงินออเดอร์ที่ชำระแล้วแต่ยังไม่ถูกแบ่ง (cron ทุก 5 นาที)
 *
 * - ค่าเริ่มต้นหยิบเฉพาะออเดอร์ที่ชำระตั้งแต่ setting money.distribution_backfill_from
 *   (ออเดอร์เก่าก่อนเปิดระบบแบ่งเงินจริง ต้องให้แอดมินตรวจแล้วสั่ง --include-old เอง)
 * - --dry-run แสดงรายการเท่านั้น ไม่แบ่งเงินจริง (เดิม dry-run แบ่งเงินจริงไปแล้วค่อยแสดงผล)
 */
class ProcessPendingOrderDistribution extends Command
{
    protected $signature = 'orders:process-distribution
                            {--limit=50 : จำนวน orders สูงสุดที่จะประมวลผล}
                            {--include-old : รวมออเดอร์ที่ชำระก่อน money.distribution_backfill_from}
                            {--dry-run : แสดงรายการที่จะประมวลผลโดยไม่ทำจริง}';

    protected $description = 'ประมวลผล Order Distribution ที่ค้างอยู่ (paid orders ที่ยังไม่ได้ distribute)';

    public function handle(OrderDistributionService $distributionService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $respectCutoff = ! $this->option('include-old');

        if ($this->option('dry-run')) {
            $orders = $distributionService->getPendingOrders($limit, $respectCutoff);

            $this->warn('📋 [DRY RUN] ออเดอร์ที่รอแบ่งเงิน: '.$orders->count().' รายการ (ยังไม่แบ่งจริง)');
            if ($orders->isNotEmpty()) {
                $this->table(
                    ['Order ID', 'Order Number', 'ยอดรวม', 'สถานะ', 'ชำระเมื่อ'],
                    $orders->map(fn ($o) => [
                        $o->id,
                        $o->order_number,
                        number_format((float) $o->total_amount, 2),
                        $o->status,
                        optional($o->paid_at)->format('Y-m-d H:i') ?? '-',
                    ])->toArray()
                );
            }

            return Command::SUCCESS;
        }

        try {
            $results = $distributionService->processPendingOrders($limit, $respectCutoff);
        } catch (\Throwable $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->table(['รายการ', 'จำนวน'], [
            ['✅ แบ่งเงินสำเร็จ', $results['processed']],
            ['⏭️ ข้าม', $results['skipped']],
            ['❌ ผิดพลาด', $results['failed']],
        ]);

        foreach ($results['errors'] as $error) {
            $this->line("  - Order #{$error['order_id']}: {$error['error']}");
        }

        return Command::SUCCESS;
    }
}
