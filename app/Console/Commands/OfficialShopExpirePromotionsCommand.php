<?php

namespace App\Console\Commands;

use App\Services\OfficialShopSelectionService;
use Illuminate\Console\Command;

/**
 * OfficialShopExpirePromotionsCommand
 *
 * ปิดโปรโมทสินค้าใหม่ที่หมดอายุ
 * - ตรวจสอบโปรโมท 7 วันที่หมดอายุ
 * - ปลดล็อคสินค้าที่ถูกล็อคระหว่างโปรโมท
 * - ถอดสินค้าออกจาก Official Shop (หมวดสินค้าใหม่)
 *
 * แนะนำให้รัน: ทุกชั่วโมง หรือทุกวันเวลา 00:30
 * Schedule: $schedule->command('officialshop:expire-promotions')->hourly();
 */
class OfficialShopExpirePromotionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officialshop:expire-promotions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ปิดโปรโมทสินค้าใหม่ที่หมดอายุ 7 วัน';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         🆕 Official Shop - Expire Promotions                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info('🔍 กำลังตรวจสอบโปรโมทที่หมดอายุ...');
        $this->newLine();

        try {
            $service = new OfficialShopSelectionService;
            $result = $service->processExpiredNewProductPromotions();

            $expiredCount = count($result['expired']);

            if ($expiredCount === 0) {
                $this->info('✅ ไม่มีโปรโมทที่หมดอายุ');
            } else {
                $this->info("📊 พบโปรโมทหมดอายุ: {$expiredCount} รายการ");
                $this->newLine();

                $this->table(
                    ['Promotion ID', 'Product ID', 'Views', 'Sales'],
                    collect($result['expired'])->map(fn ($item) => [
                        $item['promotion_id'],
                        $item['product_id'],
                        $item['views'],
                        $item['sales'],
                    ])->toArray()
                );
            }

            $this->newLine();
            $this->info('✅ ประมวลผลเสร็จสิ้น!');
            $this->info("🕐 เวลา: {$result['processed_at']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
