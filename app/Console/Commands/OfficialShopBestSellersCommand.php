<?php

namespace App\Console\Commands;

use App\Services\OfficialShopSelectionService;
use Illuminate\Console\Command;

/**
 * OfficialShopBestSellersCommand
 *
 * คำนวณสินค้าขายดีรายเดือน
 * - คำนวณยอดขายของแต่ละสินค้าในเดือนที่ระบุ
 * - อัพเดทหมวด "สินค้าขายดี" ใน Official Shop
 *
 * แนะนำให้รัน: ทุกต้นเดือน เวลา 00:00
 * Schedule: $schedule->command('officialshop:calculate-bestsellers')->monthlyOn(1, '00:00');
 */
class OfficialShopBestSellersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officialshop:calculate-bestsellers
                            {--month= : เดือนที่ต้องการคำนวณ (1-12)}
                            {--year= : ปีที่ต้องการคำนวณ}
                            {--limit= : จำนวนสินค้าที่ต้องการแสดง}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'คำนวณสินค้าขายดีรายเดือนสำหรับ Official Shop';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         🔥 Official Shop - Best Sellers                       ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // รับค่าจาก options หรือใช้เดือน/ปีปัจจุบัน
        $month = $this->option('month') ? (int) $this->option('month') : now()->month;
        $year = $this->option('year') ? (int) $this->option('year') : now()->year;

        $thaiMonths = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];

        $monthName = $thaiMonths[$month] ?? $month;

        $this->info("📅 คำนวณสินค้าขายดีเดือน: {$monthName} {$year}");
        $this->newLine();

        try {
            $service = new OfficialShopSelectionService();
            $result = $service->calculateMonthlyBestSellers($month, $year);

            if (!isset($result['products'])) {
                $this->error('❌ ' . ($result['message'] ?? 'เกิดข้อผิดพลาด'));
                return Command::FAILURE;
            }

            $productsCount = count($result['products']);

            if ($productsCount === 0) {
                $this->warn('⚠️ ไม่พบข้อมูลยอดขายในเดือนนี้');
            } else {
                $this->info("📊 พบสินค้าขายดี: {$productsCount} รายการ");
                $this->newLine();

                $this->table(
                    ['อันดับ', 'Product ID', 'Store ID', 'ยอดขาย', 'มูลค่า (บาท)'],
                    collect($result['products'])->map(fn($item) => [
                        $item['rank'],
                        $item['product_id'],
                        $item['store_id'],
                        number_format($item['sales_count']),
                        number_format($item['sales_amount'], 2),
                    ])->toArray()
                );
            }

            $this->newLine();
            $this->info('✅ คำนวณสินค้าขายดีเสร็จสิ้น!');
            $this->info("🕐 เวลา: {$result['calculated_at']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
