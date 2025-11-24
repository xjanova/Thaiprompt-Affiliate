<?php

namespace App\Console\Commands;

use App\Services\AiRental\AuditService;
use App\Models\AiRentalAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * AI Rental Cleanup Audit Logs Command
 *
 * ลบ audit logs เก่าๆ ที่ไม่ sensitive และไม่ต้อง review
 * ใช้สำหรับ cron job ทุกวันเวลา 02:00
 */
class AiRentalCleanupLogs extends Command
{
    /**
     * Command signature
     *
     * @var string
     */
    protected $signature = 'ai-rental:cleanup-logs
                            {--days=90 : Delete logs older than N days (default: 90)}
                            {--keep-sensitive : Keep sensitive logs}
                            {--keep-review : Keep logs that require review}
                            {--batch-size=1000 : Delete in batches of N records}
                            {--dry-run : Preview what will be deleted without actually deleting}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'ลบ audit logs เก่าๆ ที่ไม่จำเป็น';

    /**
     * Audit Service
     *
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * Statistics
     *
     * @var array
     */
    protected array $stats = [
        'total_old_logs' => 0,
        'deleted' => 0,
        'kept_sensitive' => 0,
        'kept_review' => 0,
        'batches' => 0,
        'failed' => 0,
    ];

    /**
     * Constructor
     *
     * @param AuditService $auditService
     */
    public function __construct(AuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    /**
     * Execute the command
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🧹 เริ่มทำความสะอาด Audit Logs...');
        $this->newLine();

        $startTime = now();
        $days = (int) $this->option('days');

        try {
            // แสดงข้อมูลเบื้องต้น
            $this->displayInitialInfo($days);

            // นับจำนวน logs เก่า
            $this->countOldLogs($days);

            if ($this->stats['total_old_logs'] === 0) {
                $this->info('✅ ไม่มี audit logs เก่าต้องลบ');
                return 0;
            }

            // แสดง sample logs
            if ($this->output->isVerbose()) {
                $this->displaySampleLogs($days);
            }

            // ถามยืนยัน
            if (!$this->option('dry-run') && !$this->option('no-interaction')) {
                if (!$this->confirm("ต้องการลบ {$this->stats['total_old_logs']} audit logs หรือไม่?", true)) {
                    $this->warn('❌ ยกเลิกการลบ');
                    return 0;
                }
                $this->newLine();
            }

            // ลบ logs
            if (!$this->option('dry-run')) {
                $this->deleteLogs($days);
            }

            // แสดงสรุปผล
            $this->displaySummary($startTime, $days);

            return $this->stats['failed'] > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาดในการลบ audit logs: ' . $e->getMessage());
            Log::error('AI Rental audit log cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * แสดงข้อมูลเบื้องต้น
     *
     * @param int $days
     * @return void
     */
    protected function displayInitialInfo(int $days): void
    {
        $cutoffDate = now()->subDays($days);

        $this->info("📅 จะลบ logs ที่เก่ากว่า {$days} วัน (ก่อน {$cutoffDate->format('Y-m-d H:i:s')})");
        $this->info("🔒 เงื่อนไข:");
        $this->line("   • ไม่ลบ logs ที่ sensitive: " . ($this->option('keep-sensitive') ? '✅' : '❌'));
        $this->line("   • ไม่ลบ logs ที่ต้อง review: " . ($this->option('keep-review') ? '✅' : '❌'));
        $this->newLine();
    }

    /**
     * นับจำนวน logs เก่า
     *
     * @param int $days
     * @return void
     */
    protected function countOldLogs(int $days): void
    {
        $cutoffDate = now()->subDays($days);

        $query = AiRentalAuditLog::where('action_at', '<', $cutoffDate);

        // นับทั้งหมด
        $totalCount = $query->count();

        // นับที่จะลบ (ตาม conditions)
        $deleteQuery = clone $query;

        if ($this->option('keep-sensitive')) {
            $deleteQuery->where('is_sensitive', false);
            $this->stats['kept_sensitive'] = $query->where('is_sensitive', true)->count();
        }

        if ($this->option('keep-review')) {
            $deleteQuery->where('requires_review', false);
            $this->stats['kept_review'] = $query->where('requires_review', true)->count();
        }

        $this->stats['total_old_logs'] = $deleteQuery->count();

        $this->info("📊 สถิติ Audit Logs:");
        $this->line("   • Logs ทั้งหมดที่เก่ากว่า {$days} วัน: " . number_format($totalCount));
        $this->line("   • จะลบ: " . number_format($this->stats['total_old_logs']));

        if ($this->stats['kept_sensitive'] > 0) {
            $this->line("   • เก็บไว้ (sensitive): " . number_format($this->stats['kept_sensitive']));
        }

        if ($this->stats['kept_review'] > 0) {
            $this->line("   • เก็บไว้ (require review): " . number_format($this->stats['kept_review']));
        }

        $this->newLine();
    }

    /**
     * แสดง sample logs ที่จะลบ
     *
     * @param int $days
     * @return void
     */
    protected function displaySampleLogs(int $days): void
    {
        $cutoffDate = now()->subDays($days);

        $query = AiRentalAuditLog::where('action_at', '<', $cutoffDate);

        if ($this->option('keep-sensitive')) {
            $query->where('is_sensitive', false);
        }

        if ($this->option('keep-review')) {
            $query->where('requires_review', false);
        }

        $sampleLogs = $query->orderBy('action_at')
            ->limit(10)
            ->get();

        if ($sampleLogs->isEmpty()) {
            return;
        }

        $this->info('📋 ตัวอย่าง Audit Logs ที่จะลบ (10 รายการแรก):');

        $tableData = [];
        foreach ($sampleLogs as $log) {
            $tableData[] = [
                $log->id,
                $log->action_category ?? 'N/A',
                $log->action,
                $log->user_id ?? 'System',
                $log->action_at->format('Y-m-d'),
                $log->action_at->diffForHumans(),
            ];
        }

        $this->table(
            ['ID', 'Category', 'Action', 'User ID', 'Date', 'Age'],
            $tableData
        );

        $this->newLine();
    }

    /**
     * ลบ logs แบบ batch
     *
     * @param int $days
     * @return void
     */
    protected function deleteLogs(int $days): void
    {
        $cutoffDate = now()->subDays($days);
        $batchSize = (int) $this->option('batch-size');

        $this->info("🗑️  กำลังลบ logs แบบ batch (ขนาด {$batchSize})...");

        $deleted = 0;
        $totalToDelete = $this->stats['total_old_logs'];

        // สร้าง progress bar
        $bar = $this->output->createProgressBar($totalToDelete);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        try {
            while (true) {
                $query = AiRentalAuditLog::where('action_at', '<', $cutoffDate);

                if ($this->option('keep-sensitive')) {
                    $query->where('is_sensitive', false);
                }

                if ($this->option('keep-review')) {
                    $query->where('requires_review', false);
                }

                $batchDeleted = $query->limit($batchSize)->delete();

                if ($batchDeleted === 0) {
                    break;
                }

                $deleted += $batchDeleted;
                $this->stats['batches']++;

                $bar->setMessage("Batch #{$this->stats['batches']} - ลบแล้ว {$deleted} logs");
                $bar->advance($batchDeleted);

                // Sleep เล็กน้อยเพื่อไม่ให้ DB overload
                usleep(100000); // 0.1 second
            }

            $bar->finish();
            $this->newLine(2);

            $this->stats['deleted'] = $deleted;
            $this->info("✅ ลบสำเร็จ {$deleted} audit logs ใน {$this->stats['batches']} batches");
        } catch (\Exception $e) {
            $this->error("❌ ลบล้มเหลว: {$e->getMessage()}");
            $this->stats['failed']++;
        }

        $this->newLine();
    }

    /**
     * แสดงสรุปผล
     *
     * @param \Carbon\Carbon $startTime
     * @param int $days
     * @return void
     */
    protected function displaySummary(\Carbon\Carbon $startTime, int $days): void
    {
        $duration = $startTime->diffInSeconds(now());

        $this->info('📈 สรุปผลการทำความสะอาด:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['Logs เก่ากว่า ' . $days . ' วัน', number_format($this->stats['total_old_logs'])],
                ['🗑️  ลบแล้ว', number_format($this->stats['deleted'])],
                ['🔒 เก็บไว้ (sensitive)', number_format($this->stats['kept_sensitive'])],
                ['📋 เก็บไว้ (review)', number_format($this->stats['kept_review'])],
                ['📦 Batches', $this->stats['batches']],
                ['❌ ล้มเหลว', $this->stats['failed']],
                ['⏱️  ระยะเวลา', "{$duration} วินาที"],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 Dry-run mode: ไม่มีการลบจริง');
        } elseif ($this->stats['failed'] > 0) {
            $this->newLine();
            $this->warn("⚠️  การลบล้มเหลว");
        } elseif ($this->stats['deleted'] > 0) {
            $this->newLine();
            $freed = $this->stats['deleted'] * 2; // ประมาณ 2KB per log
            $this->info("✅ ทำความสะอาดสำเร็จ! ประหยัดพื้นที่ประมาณ " . number_format($freed) . " KB");
        }

        $this->newLine();
        $this->info('✨ เสร็จสิ้นการทำความสะอาด!');
    }
}
