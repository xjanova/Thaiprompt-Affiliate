<?php

namespace App\Console\Commands;

use App\Services\LineAutoRetryService;
use Illuminate\Console\Command;

/**
 * Process LINE Failed Messages - Artisan Command
 *
 * คำสั่งสำหรับประมวลผลข้อความ LINE ที่ล้มเหลวและรอการ retry
 * ใช้ใน cron job เพื่อ retry ข้อความที่ล้มเหลวอัตโนมัติ
 *
 * Usage:
 * - php artisan line:process-retries           (ค่าเริ่มต้น: 100 ข้อความ)
 * - php artisan line:process-retries --limit=50
 * - php artisan line:process-retries --cleanup  (ล้างข้อความเก่าด้วย)
 *
 * Cron Setup:
 * - * * * * * php artisan line:process-retries >> /dev/null 2>&1
 * - หรือทุก 5 นาที: * /5 * * * * php artisan line:process-retries
 *
 * @package App\Console\Commands
 */
class ProcessLineRetries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:process-retries
                            {--limit=100 : จำนวนข้อความสูงสุดที่จะประมวลผล}
                            {--cleanup : ล้างข้อความเก่าที่ resolve แล้ว}
                            {--cleanup-days=30 : จำนวนวันที่จะเก็บข้อความเก่า (ใช้กับ --cleanup)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ประมวลผลข้อความ LINE ที่ล้มเหลวและรอการ retry';

    /**
     * LineAutoRetryService instance
     *
     * @var LineAutoRetryService
     */
    protected LineAutoRetryService $retryService;

    /**
     * Constructor
     *
     * @param LineAutoRetryService $retryService
     */
    public function __construct(LineAutoRetryService $retryService)
    {
        parent::__construct();
        $this->retryService = $retryService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🔄 เริ่มประมวลผลข้อความ LINE ที่รอการ retry...');
        $this->newLine();

        // ดึงค่า limit
        $limit = (int) $this->option('limit');

        if ($limit <= 0) {
            $this->error('❌ Limit ต้องมากกว่า 0');
            return Command::FAILURE;
        }

        try {
            // ประมวลผลข้อความที่รอการ retry
            $this->line("📊 กำลังประมวลผล (สูงสุด {$limit} ข้อความ)...");

            $successCount = $this->retryService->retryPendingMessages($limit);

            $this->newLine();
            $this->info("✅ ประมวลผลเสร็จสิ้น:");
            $this->line("   - Retry สำเร็จ: {$successCount} ข้อความ");

            // แสดงสถิติ
            $this->displayStatistics();

            // ทำการ cleanup ถ้าระบุ option
            if ($this->option('cleanup')) {
                $this->newLine();
                $this->performCleanup();
            }

            // แสดงเวลาที่ใช้
            $duration = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("⏱️  ใช้เวลา: {$duration} วินาที");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ เกิดข้อผิดพลาด: ' . $e->getMessage());
            $this->line('   Stack trace: ' . $e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * แสดงสถิติการ retry
     *
     * @return void
     */
    protected function displayStatistics(): void
    {
        try {
            $this->newLine();
            $this->line('📈 สถิติย้อนหลัง 7 วัน:');

            $stats = $this->retryService->getRetryStatistics(7);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Failed Messages', number_format($stats['total'])],
                    ['Succeeded', number_format($stats['succeeded']) . ' (' . $stats['success_rate'] . '%)'],
                    ['Abandoned', number_format($stats['abandoned']) . ' (' . $stats['abandonment_rate'] . '%)'],
                    ['Still Pending', number_format($stats['pending'])],
                    ['Avg Retry Count', $stats['avg_retry_count']],
                ]
            );

            // แสดง error types ถ้ามี
            if (!empty($stats['by_error_type'])) {
                $this->newLine();
                $this->line('🔍 Error Types:');

                $errorRows = [];
                foreach ($stats['by_error_type'] as $type => $count) {
                    $errorRows[] = [$type, number_format($count)];
                }

                $this->table(['Error Type', 'Count'], $errorRows);
            }

        } catch (\Exception $e) {
            $this->warn('⚠️  ไม่สามารถดึงสถิติได้: ' . $e->getMessage());
        }
    }

    /**
     * ทำการ cleanup ข้อความเก่า
     *
     * @return void
     */
    protected function performCleanup(): void
    {
        $days = (int) $this->option('cleanup-days');

        if ($days <= 0) {
            $this->warn('⚠️  Cleanup days ต้องมากกว่า 0, ข้าม cleanup...');
            return;
        }

        try {
            $this->line("🧹 กำลังลบข้อความเก่าที่ resolve แล้ว (เก่ากว่า {$days} วัน)...");

            $deletedCount = $this->retryService->cleanupOldMessages($days);

            $this->info("✅ ลบข้อความเก่าแล้ว: {$deletedCount} รายการ");

        } catch (\Exception $e) {
            $this->error('❌ Cleanup ล้มเหลว: ' . $e->getMessage());
        }
    }
}
