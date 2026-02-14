<?php

namespace App\Console\Commands;

use App\Services\OfficialShopSelectionService;
use Illuminate\Console\Command;

/**
 * OfficialShopProcessWarningsCommand
 *
 * ประมวลผล Warnings สำหรับ Official Shop
 * - ตรวจสอบว่าร้านค้าปรับปรุงคะแนนแล้วหรือยัง
 * - ถอดสินค้าที่หมดเวลาปรับปรุงออกจาก Official Shop
 *
 * แนะนำให้รัน: ทุกวัน เวลา 01:00
 * Schedule: $schedule->command('officialshop:process-warnings')->dailyAt('01:00');
 */
class OfficialShopProcessWarningsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officialshop:process-warnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ประมวลผล Warnings และถอดสินค้าที่หมดเวลาปรับปรุง';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         ⚠️  Official Shop - Process Warnings                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info('🔍 กำลังประมวลผล Warnings...');
        $this->newLine();

        try {
            $service = new OfficialShopSelectionService;
            $result = $service->processWarnings();

            // แสดงผลลัพธ์
            $this->info('📊 ผลลัพธ์:');
            $this->table(
                ['รายการ', 'จำนวน'],
                [
                    ['ปรับปรุงสำเร็จ', count($result['improved'])],
                    ['หมดเวลาปรับปรุง', count($result['expired'])],
                    ['ถูกถอดออก', count($result['removed'])],
                ]
            );

            // แสดงรายการที่ปรับปรุงสำเร็จ
            if (! empty($result['improved'])) {
                $this->newLine();
                $this->info('✅ ปรับปรุงสำเร็จ:');
                foreach ($result['improved'] as $item) {
                    $this->line("   - [Product #{$item['product_id']}] คะแนนใหม่: {$item['new_score']}");
                }
            }

            // แสดงรายการที่หมดเวลา
            if (! empty($result['expired'])) {
                $this->newLine();
                $this->warn('⏰ หมดเวลาปรับปรุง:');
                foreach ($result['expired'] as $item) {
                    $this->line("   - [Product #{$item['product_id']}]");
                }
            }

            // แสดงรายการที่ถูกถอด
            if (! empty($result['removed'])) {
                $this->newLine();
                $this->error('❌ ถูกถอดออกจาก Official Shop:');
                foreach ($result['removed'] as $productId) {
                    $this->line("   - Product #{$productId}");
                }
            }

            $this->newLine();
            $this->info('✅ ประมวลผล Warnings เสร็จสิ้น!');
            $this->info("🕐 เวลา: {$result['processed_at']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
