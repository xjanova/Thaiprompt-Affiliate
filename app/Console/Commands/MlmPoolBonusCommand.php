<?php

namespace App\Console\Commands;

use App\Services\MlmPoolBonusService;
use Illuminate\Console\Command;

/**
 * Artisan command สำหรับจัดการ Pool Bonus
 *
 * ใช้งาน:
 * - php artisan mlm:pool-bonus calculate    - คำนวณ Pool Bonus
 * - php artisan mlm:pool-bonus distribute   - กระจาย Pool Bonus
 * - php artisan mlm:pool-bonus auto         - รันอัตโนมัติ (คำนวณ + กระจาย)
 * - php artisan mlm:pool-bonus status       - ดูสถานะ period ปัจจุบัน
 */
class MlmPoolBonusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlm:pool-bonus
                            {action=status : Action to perform (calculate|distribute|auto|status)}
                            {--period-type= : Period type (daily|weekly|monthly)}
                            {--force : Force recalculation even if already calculated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'จัดการ Pool Bonus (All Sale Bonus) - คำนวณและกระจายโบนัสจากยอดขายรวม';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $service = new MlmPoolBonusService;

        return match ($action) {
            'calculate' => $this->runCalculate($service),
            'distribute' => $this->runDistribute($service),
            'auto' => $this->runAuto($service),
            'status' => $this->showStatus($service),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * คำนวณ Pool Bonus
     */
    protected function runCalculate(MlmPoolBonusService $service): int
    {
        $this->info('🔄 กำลังคำนวณ Pool Bonus...');

        $periodType = $this->option('period-type');
        $period = $service->createPeriod($periodType);

        $this->table(
            ['รายการ', 'ค่า'],
            [
                ['Period ID', $period->id],
                ['ประเภท', $period->period_type],
                ['เริ่มต้น', $period->period_start->format('d/m/Y')],
                ['สิ้นสุด', $period->period_end->format('d/m/Y')],
            ]
        );

        $result = $service->calculateAndDistribute($period);

        if ($result['success']) {
            $this->info('✅ คำนวณสำเร็จ!');
            $this->table(
                ['รายการ', 'ค่า'],
                [
                    ['ยอดใน Pool', number_format($result['pool_amount'] ?? 0, 2).' บาท'],
                    ['สมาชิกที่ qualify', $result['qualified_count'] ?? 0],
                    ['Total Shares', $result['total_shares'] ?? 0],
                    ['ต่อ Share', number_format($result['amount_per_share'] ?? 0, 2).' บาท'],
                ]
            );

            return 0;
        }

        $this->error('❌ เกิดข้อผิดพลาด: '.($result['message'] ?? 'Unknown error'));

        return 1;
    }

    /**
     * กระจาย Pool Bonus
     */
    protected function runDistribute(MlmPoolBonusService $service): int
    {
        $this->info('🔄 กำลังกระจาย Pool Bonus...');

        $periodType = $this->option('period-type');
        $period = $service->createPeriod($periodType);

        if ($period->status !== 'pending') {
            $this->warn("⚠️ Period นี้มี status: {$period->status} (ต้องเป็น pending)");

            return 1;
        }

        $result = $service->approveAndPay($period);

        if ($result['success']) {
            $this->info('✅ กระจายสำเร็จ!');
            $this->table(
                ['รายการ', 'ค่า'],
                [
                    ['จ่ายให้', $result['paid_count'].' คน'],
                    ['ยอดรวม', number_format($result['total_paid'] ?? 0, 2).' บาท'],
                ]
            );

            return 0;
        }

        $this->error('❌ เกิดข้อผิดพลาด: '.($result['message'] ?? 'Unknown error'));

        return 1;
    }

    /**
     * รันอัตโนมัติ
     */
    protected function runAuto(MlmPoolBonusService $service): int
    {
        $this->info('🔄 กำลังรัน Pool Bonus อัตโนมัติ...');

        $result = $service->runAutoPoolBonus();

        if ($result['success']) {
            $this->info('✅ รันอัตโนมัติสำเร็จ!');
            $this->info($result['message'] ?? '');

            return 0;
        }

        $this->warn('⚠️ '.($result['message'] ?? 'Unknown issue'));

        return 1;
    }

    /**
     * แสดงสถานะ
     */
    protected function showStatus(MlmPoolBonusService $service): int
    {
        $this->info('📊 สถานะ Pool Bonus');
        $this->newLine();

        $summary = $service->getSummary(5);

        // แสดง totals
        $this->table(
            ['สรุปทั้งหมด', 'ค่า'],
            [
                ['ยอด Pool รวม', number_format($summary['totals']['total_pool'], 2).' บาท'],
                ['จำนวนสมาชิกที่ได้รับ', number_format($summary['totals']['total_members'])],
                ['จำนวน Periods', $summary['totals']['total_periods']],
            ]
        );

        // แสดง periods ล่าสุด
        $this->newLine();
        $this->info('📅 Periods ล่าสุด:');

        if ($summary['periods']->isEmpty()) {
            $this->warn('ยังไม่มี Pool Bonus Period');
        } else {
            $rows = $summary['periods']->map(function ($period) {
                return [
                    $period->id,
                    $period->period_type,
                    $period->period_start->format('d/m/Y'),
                    $period->period_end->format('d/m/Y'),
                    number_format($period->pool_amount, 2),
                    $period->qualified_members_count,
                    $period->status,
                ];
            })->toArray();

            $this->table(
                ['ID', 'ประเภท', 'เริ่ม', 'สิ้นสุด', 'ยอด Pool', 'สมาชิก', 'สถานะ'],
                $rows
            );
        }

        return 0;
    }
}
