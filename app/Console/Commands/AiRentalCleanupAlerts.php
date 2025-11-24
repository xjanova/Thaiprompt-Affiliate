<?php

namespace App\Console\Commands;

use App\Services\AiRental\MonitoringService;
use App\Models\AiRentalAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * AI Rental Cleanup Alerts Command
 *
 * Auto-resolve alerts ที่หมดอายุและลบ alerts เก่าๆ
 * ใช้สำหรับ cron job ทุก 1 ชั่วโมง
 */
class AiRentalCleanupAlerts extends Command
{
    /**
     * Command signature
     *
     * @var string
     */
    protected $signature = 'ai-rental:cleanup-alerts
                            {--delete-days=30 : Delete resolved alerts older than N days}
                            {--resolve-only : Only auto-resolve, don\'t delete}
                            {--delete-only : Only delete old alerts, don\'t auto-resolve}
                            {--dry-run : Preview what will be cleaned without actually cleaning}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Auto-resolve alerts ที่หมดอายุและลบ alerts เก่าๆ';

    /**
     * Monitoring Service
     *
     * @var MonitoringService
     */
    protected MonitoringService $monitoringService;

    /**
     * Statistics
     *
     * @var array
     */
    protected array $stats = [
        'expired_alerts' => 0,
        'auto_resolved' => 0,
        'old_alerts' => 0,
        'deleted' => 0,
        'failed' => 0,
    ];

    /**
     * Constructor
     *
     * @param MonitoringService $monitoringService
     */
    public function __construct(MonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the command
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🧹 เริ่มทำความสะอาด Alerts...');
        $this->newLine();

        $startTime = now();

        try {
            // Auto-resolve expired alerts
            if (!$this->option('delete-only')) {
                $this->autoResolveExpiredAlerts();
            }

            // Delete old resolved alerts
            if (!$this->option('resolve-only')) {
                $this->deleteOldAlerts();
            }

            // แสดงสรุปผล
            $this->displaySummary($startTime);

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาดในการทำความสะอาด alerts: ' . $e->getMessage());
            Log::error('AI Rental alert cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Auto-resolve alerts ที่หมดอายุ
     *
     * @return void
     */
    protected function autoResolveExpiredAlerts(): void
    {
        $this->info('🔄 กำลัง auto-resolve alerts ที่หมดอายุ...');

        // ดึง expired alerts
        $expiredAlerts = AiRentalAlert::where('expires_at', '<', now())
            ->whereNull('resolved_at')
            ->get();

        $this->stats['expired_alerts'] = $expiredAlerts->count();

        if ($this->stats['expired_alerts'] === 0) {
            $this->info('  ✅ ไม่มี alerts ที่หมดอายุต้อง resolve');
            $this->newLine();
            return;
        }

        $this->info("  📊 พบ {$this->stats['expired_alerts']} alerts ที่หมดอายุ");

        if ($this->output->isVerbose()) {
            $this->displayExpiredAlerts($expiredAlerts);
        }

        if ($this->option('dry-run')) {
            $this->warn('  🔍 Dry-run mode: จะไม่มีการ resolve จริง');
            $this->newLine();
            return;
        }

        // Auto-resolve
        try {
            $resolved = $this->monitoringService->autoResolveExpiredAlerts();
            $this->stats['auto_resolved'] = $resolved;

            $this->info("  ✅ Auto-resolve สำเร็จ {$resolved} alerts");
        } catch (\Exception $e) {
            $this->error("  ❌ Auto-resolve ล้มเหลว: {$e->getMessage()}");
            $this->stats['failed']++;
        }

        $this->newLine();
    }

    /**
     * ลบ alerts เก่าที่ resolved แล้ว
     *
     * @return void
     */
    protected function deleteOldAlerts(): void
    {
        $days = (int) $this->option('delete-days');
        $cutoffDate = now()->subDays($days);

        $this->info("🗑️  กำลังลบ alerts ที่ resolved แล้วเกิน {$days} วัน...");

        // ดึง old alerts
        $oldAlerts = AiRentalAlert::whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoffDate)
            ->where('is_sensitive', false)
            ->get();

        $this->stats['old_alerts'] = $oldAlerts->count();

        if ($this->stats['old_alerts'] === 0) {
            $this->info('  ✅ ไม่มี alerts เก่าต้องลบ');
            $this->newLine();
            return;
        }

        $this->info("  📊 พบ {$this->stats['old_alerts']} alerts เก่า");

        if ($this->output->isVerbose()) {
            $this->displayOldAlerts($oldAlerts, $cutoffDate);
        }

        if ($this->option('dry-run')) {
            $this->warn('  🔍 Dry-run mode: จะไม่มีการลบจริง');
            $this->newLine();
            return;
        }

        // ถามยืนยัน
        if (!$this->option('no-interaction')) {
            if (!$this->confirm("ต้องการลบ alerts เหล่านี้หรือไม่?", true)) {
                $this->warn('  ❌ ยกเลิกการลบ');
                $this->newLine();
                return;
            }
        }

        // ลบ alerts
        try {
            $deleted = AiRentalAlert::whereNotNull('resolved_at')
                ->where('resolved_at', '<', $cutoffDate)
                ->where('is_sensitive', false)
                ->delete();

            $this->stats['deleted'] = $deleted;
            $this->info("  ✅ ลบสำเร็จ {$deleted} alerts");
        } catch (\Exception $e) {
            $this->error("  ❌ ลบล้มเหลว: {$e->getMessage()}");
            $this->stats['failed']++;
        }

        $this->newLine();
    }

    /**
     * แสดงรายการ expired alerts
     *
     * @param \Illuminate\Support\Collection $alerts
     * @return void
     */
    protected function displayExpiredAlerts($alerts): void
    {
        $tableData = [];

        foreach ($alerts->take(10) as $alert) {
            $tableData[] = [
                $alert->id,
                $alert->alert_type,
                $alert->severity,
                $alert->title,
                $alert->expires_at->diffForHumans(),
            ];
        }

        if ($alerts->count() > 10) {
            $tableData[] = ['...', '...', '...', "และอีก " . ($alerts->count() - 10) . " alerts", '...'];
        }

        $this->table(
            ['ID', 'Type', 'Severity', 'Title', 'Expired'],
            $tableData
        );
    }

    /**
     * แสดงรายการ old alerts
     *
     * @param \Illuminate\Support\Collection $alerts
     * @param \Carbon\Carbon $cutoffDate
     * @return void
     */
    protected function displayOldAlerts($alerts, $cutoffDate): void
    {
        $tableData = [];

        foreach ($alerts->take(10) as $alert) {
            $tableData[] = [
                $alert->id,
                $alert->alert_type,
                $alert->title,
                $alert->resolved_at->format('Y-m-d'),
                $alert->resolved_at->diffForHumans(),
            ];
        }

        if ($alerts->count() > 10) {
            $tableData[] = ['...', '...', "และอีก " . ($alerts->count() - 10) . " alerts", '...', '...'];
        }

        $this->table(
            ['ID', 'Type', 'Title', 'Resolved Date', 'Age'],
            $tableData
        );
    }

    /**
     * แสดงสรุปผล
     *
     * @param \Carbon\Carbon $startTime
     * @return void
     */
    protected function displaySummary(\Carbon\Carbon $startTime): void
    {
        $duration = $startTime->diffInSeconds(now());

        $this->info('📈 สรุปผลการทำความสะอาด:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['Alerts ที่หมดอายุ', $this->stats['expired_alerts']],
                ['✅ Auto-resolved', $this->stats['auto_resolved']],
                ['Alerts เก่า (resolved)', $this->stats['old_alerts']],
                ['🗑️  ลบแล้ว', $this->stats['deleted']],
                ['❌ ล้มเหลว', $this->stats['failed']],
                ['⏱️  ระยะเวลา', "{$duration} วินาที"],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 Dry-run mode: ไม่มีการเปลี่ยนแปลงใดๆ');
        } elseif ($this->stats['failed'] > 0) {
            $this->newLine();
            $this->warn("⚠️  มี {$this->stats['failed']} operations ที่ล้มเหลว");
        } elseif ($this->stats['auto_resolved'] > 0 || $this->stats['deleted'] > 0) {
            $this->newLine();
            $this->info('✅ ทำความสะอาดสำเร็จ!');
        }

        $this->newLine();
        $this->info('✨ เสร็จสิ้นการทำความสะอาด!');
    }
}
