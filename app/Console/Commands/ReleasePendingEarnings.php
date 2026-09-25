<?php

namespace App\Console\Commands;

use App\Models\EarningsLedger;
use App\Services\SellerPayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ReleasePendingEarnings — จ่ายรายได้ที่ถึงเวลาเข้ากระเป๋าผู้รับ
 *
 * รายได้ผู้ขาย (seller_sale จากออเดอร์):
 *   ปล่อยเฉพาะออเดอร์ที่ "ส่งถึงแล้ว" (delivered/completed) + ครบ holding_days
 *   หรือส่งพัสดุแล้วเกิน money.shipped_auto_release_days วันโดยลูกค้าไม่กดยืนยัน
 *   หรือสินค้าดิจิทัลล้วนที่จ่ายแล้วครบ holding_days
 *   แล้วโอนเข้า wallet ผู้ขายทันที (SellerPayoutService — ครั้งเดียวต่อ ledger)
 *   (เดิมปล่อยตามเวลาอย่างเดียวและไม่เคยโอนเงินจริง — audit G18 / SELLER-01)
 *
 * รายได้ประเภทอื่น: เปลี่ยน pending → available เมื่อถึง available_at (พฤติกรรมเดิม)
 */
class ReleasePendingEarnings extends Command
{
    protected $signature = 'earnings:release-pending
                            {--limit=100 : จำนวน earnings สูงสุดที่จะประมวลผล}
                            {--dry-run : แสดงรายการที่จะถูกปล่อยโดยไม่ทำจริง}';

    protected $description = 'จ่ายรายได้ผู้ขายที่ส่งของถึงและครบระยะพักเงินเข้ากระเป๋า + ปล่อย earnings ประเภทอื่นที่ถึงเวลา';

    public function handle(SellerPayoutService $payouts): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info('🔄 เริ่มจ่ายรายได้ที่ถึงเวลา...');

        // ===== 1) รายได้ผู้ขายจากออเดอร์ =====
        if ($dryRun) {
            $eligible = $payouts->eligibleQuery()->limit($limit)->get();
            $this->warn('📋 [DRY RUN] รายได้ผู้ขายที่จะถูกโอนเข้ากระเป๋า: '.$eligible->count().' รายการ');
            if ($eligible->isNotEmpty()) {
                $this->table(
                    ['ID', 'ผู้ขาย', 'ออเดอร์', 'ยอดสุทธิ', 'สถานะ'],
                    $eligible->map(fn ($e) => [
                        $e->id,
                        $e->user_id,
                        $e->source_id,
                        number_format((float) $e->net_amount, 2),
                        $e->status,
                    ])->toArray()
                );
            }
        } else {
            $sellerResult = $payouts->releaseEligible($limit);

            $this->table(['รายได้ผู้ขาย', 'จำนวน'], [
                ['ตรวจ', $sellerResult['checked']],
                ['✅ โอนเข้ากระเป๋า', $sellerResult['credited']],
                ['⏭️ ข้าม', $sellerResult['skipped']],
                ['❌ ผิดพลาด', $sellerResult['failed']],
                ['ยอดที่โอน (บาท)', number_format($sellerResult['total_credited'], 2)],
            ]);

            foreach ($sellerResult['errors'] as $error) {
                $this->line("  - Ledger #{$error['id']}: {$error['reason']}");
            }

            if ($sellerResult['checked'] > 0) {
                Log::info('Seller earnings released', $sellerResult);
            }
        }

        // ===== 2) รายได้ประเภทอื่น (ปล่อยตามเวลา) =====
        $others = EarningsLedger::where('status', EarningsLedger::STATUS_PENDING)
            ->where('earning_type', '!=', EarningsLedger::TYPE_SELLER_SALE)
            ->where(function ($q) {
                $q->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($others->isEmpty()) {
            $this->info('✅ ประมวลผลเสร็จสิ้น');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('📋 [DRY RUN] รายได้ประเภทอื่นที่จะเปลี่ยนเป็นพร้อมจ่าย: '.$others->count().' รายการ');

            return Command::SUCCESS;
        }

        $released = 0;
        foreach ($others as $earning) {
            try {
                // อัปเดตแบบมีเงื่อนไขสถานะ → รันซ้อนกัน 2 process ก็ไม่ทับกัน
                $released += EarningsLedger::whereKey($earning->id)
                    ->where('status', EarningsLedger::STATUS_PENDING)
                    ->update(['status' => EarningsLedger::STATUS_AVAILABLE, 'available_at' => now()]);
            } catch (\Throwable $e) {
                Log::error('Failed to release earning', ['earning_id' => $earning->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("✅ ปล่อยรายได้ประเภทอื่น {$released} รายการ");

        return Command::SUCCESS;
    }
}
