<?php

namespace App\Console\Commands;

use App\Services\OfficialShopSelectionService;
use Illuminate\Console\Command;

/**
 * OfficialShopAiSelectionCommand
 *
 * รัน AI Selection สำหรับ Official Shop อัตโนมัติ
 * - คัดเลือกสินค้าใหม่เข้า Official Shop
 * - ตรวจสอบและอัพเดทคะแนนสินค้าที่อยู่แล้ว
 * - สร้าง Warning สำหรับสินค้าที่คะแนนต่ำ
 *
 * แนะนำให้รัน: ทุกวัน เวลา 00:00
 * Schedule: $schedule->command('officialshop:run-ai-selection')->daily();
 */
class OfficialShopAiSelectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officialshop:run-ai-selection
                            {--max= : จำนวนสินค้าสูงสุดที่จะเลือก}
                            {--dry-run : แสดงผลลัพธ์โดยไม่บันทึก}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'รัน AI Selection เพื่อคัดเลือกสินค้าเข้า Official Shop';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         🤖 Official Shop - AI Selection                       ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $maxProducts = $this->option('max') ? (int) $this->option('max') : null;
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  โหมด Dry Run - จะไม่บันทึกผลลัพธ์');
            $this->newLine();
        }

        $this->info('🔍 กำลังประมวลผล AI Selection...');
        $this->newLine();

        try {
            $service = new OfficialShopSelectionService();
            $result = $service->runAiSelection($maxProducts);

            if (!$result['success']) {
                $this->error('❌ ' . ($result['message'] ?? 'เกิดข้อผิดพลาด'));
                return Command::FAILURE;
            }

            // แสดงผลลัพธ์
            $this->info('📊 ผลลัพธ์การ Selection:');
            $this->table(
                ['รายการ', 'จำนวน'],
                [
                    ['สินค้าใน Official Shop ทั้งหมด', $result['stats']['total_featured']],
                    ['สินค้าที่ถูกเลือกใหม่', $result['stats']['new_selections']],
                    ['สินค้าที่ได้รับ Warning', $result['stats']['new_warnings']],
                    ['สินค้าที่ถูกถอดออก', $result['stats']['removed_count']],
                ]
            );

            // แสดงรายการที่ถูกเลือกใหม่
            if (!empty($result['selected'])) {
                $this->newLine();
                $this->info('✅ สินค้าที่ถูกเลือกใหม่:');
                foreach ($result['selected'] as $item) {
                    $this->line("   - [{$item['product_id']}] {$item['product_name']} (คะแนน: {$item['score']})");
                }
            }

            // แสดง Warnings
            if (!empty($result['warnings'])) {
                $this->newLine();
                $this->warn('⚠️  สินค้าที่ได้รับ Warning:');
                foreach ($result['warnings'] as $item) {
                    $this->line("   - [{$item['product_id']}] คะแนน: {$item['current_score']} (ต้องการ: {$item['required_score']})");
                }
            }

            // แสดงสินค้าที่ถูกถอด
            if (!empty($result['removed'])) {
                $this->newLine();
                $this->error('❌ สินค้าที่ถูกถอดออก:');
                foreach ($result['removed'] as $item) {
                    $this->line("   - [{$item['product_id']}] เหตุผล: {$item['reason']}");
                }
            }

            $this->newLine();
            $this->info('✅ AI Selection เสร็จสิ้น!');
            $this->info("🕐 เวลา: {$result['run_at']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
