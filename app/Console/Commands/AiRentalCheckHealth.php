<?php

namespace App\Console\Commands;

use App\Services\AiRental\HealthCheckService;
use App\Models\AiRentalDeployment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * AI Rental Health Check Command
 *
 * รัน health checks อัตโนมัติสำหรับ deployments ทั้งหมด
 * ใช้สำหรับ cron job ทุก 5 นาที
 */
class AiRentalCheckHealth extends Command
{
    /**
     * Command signature
     *
     * @var string
     */
    protected $signature = 'ai-rental:check-health
                            {--deployment= : Check specific deployment ID only}
                            {--frequency=5min : Check frequency (1min, 5min, 15min, 30min, 1hour)}
                            {--force : Force check even if recently checked}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'รัน health checks อัตโนมัติสำหรับ AI Rental deployments';

    /**
     * Health Check Service
     *
     * @var HealthCheckService
     */
    protected HealthCheckService $healthCheckService;

    /**
     * Statistics
     *
     * @var array
     */
    protected array $stats = [
        'total' => 0,
        'checked' => 0,
        'healthy' => 0,
        'degraded' => 0,
        'unhealthy' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    /**
     * Constructor
     *
     * @param HealthCheckService $healthCheckService
     */
    public function __construct(HealthCheckService $healthCheckService)
    {
        parent::__construct();
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Execute the command
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🏥 เริ่มตรวจสอบสุขภาพ AI Rental Deployments...');
        $this->newLine();

        $startTime = now();

        try {
            // ถ้าระบุ deployment เฉพาะ
            if ($deploymentId = $this->option('deployment')) {
                $this->checkSingleDeployment($deploymentId);
            } else {
                $this->checkAllDeployments();
            }

            // แสดงสรุปผล
            $this->displaySummary($startTime);

            // Return exit code
            return $this->stats['unhealthy'] > 0 || $this->stats['failed'] > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาดในการตรวจสอบ: ' . $e->getMessage());
            Log::error('AI Rental health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * ตรวจสอบ deployment เดียว
     *
     * @param int $deploymentId
     * @return void
     */
    protected function checkSingleDeployment(int $deploymentId): void
    {
        $deployment = AiRentalDeployment::find($deploymentId);

        if (!$deployment) {
            $this->error("❌ ไม่พบ deployment ID: {$deploymentId}");
            return;
        }

        $this->stats['total'] = 1;
        $this->info("🔍 กำลังตรวจสอบ: {$deployment->deployment_name}");

        $this->checkDeployment($deployment);
    }

    /**
     * ตรวจสอบ deployments ทั้งหมดที่ running
     *
     * @return void
     */
    protected function checkAllDeployments(): void
    {
        $deployments = AiRentalDeployment::running()->get();

        $this->stats['total'] = $deployments->count();

        if ($this->stats['total'] === 0) {
            $this->warn('⚠️  ไม่มี deployments ที่กำลัง running');
            return;
        }

        $this->info("📊 พบ {$this->stats['total']} deployments ที่กำลัง running");
        $this->newLine();

        // สร้าง progress bar
        $bar = $this->output->createProgressBar($this->stats['total']);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        foreach ($deployments as $deployment) {
            $bar->setMessage("Checking: {$deployment->deployment_name}");

            $this->checkDeployment($deployment);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * ตรวจสอบ deployment
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    protected function checkDeployment(AiRentalDeployment $deployment): void
    {
        try {
            // ถ้าไม่ force และเคยตรวจสอบไปไม่นาน ให้ skip
            if (!$this->option('force')) {
                $lastCheck = $deployment->healthChecks()
                    ->where('created_at', '>', now()->subMinutes(4))
                    ->first();

                if ($lastCheck) {
                    $this->stats['skipped']++;
                    return;
                }
            }

            // ทำ health check
            $healthCheck = $this->healthCheckService->checkDeploymentHealth($deployment, [
                'check_frequency' => $this->option('frequency'),
            ]);

            $this->stats['checked']++;

            // นับสถานะ
            switch ($healthCheck->status) {
                case 'healthy':
                    $this->stats['healthy']++;
                    break;
                case 'degraded':
                    $this->stats['degraded']++;
                    break;
                case 'unhealthy':
                    $this->stats['unhealthy']++;
                    break;
            }

            // แสดงผลถ้า verbose
            if ($this->output->isVerbose()) {
                $icon = match ($healthCheck->status) {
                    'healthy' => '✅',
                    'degraded' => '⚠️ ',
                    'unhealthy' => '❌',
                    default => '❓',
                };

                $this->line("  {$icon} {$deployment->deployment_name}: {$healthCheck->status} ({$healthCheck->response_time_ms}ms)");
            }
        } catch (\Exception $e) {
            $this->stats['failed']++;

            if ($this->output->isVerbose()) {
                $this->error("  ❌ {$deployment->deployment_name}: {$e->getMessage()}");
            }

            Log::error("Health check failed for deployment {$deployment->id}", [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        $this->newLine();
        $this->info('📈 สรุปผลการตรวจสอบ:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['Deployments ทั้งหมด', $this->stats['total']],
                ['ตรวจสอบแล้ว', $this->stats['checked']],
                ['✅ Healthy', $this->stats['healthy']],
                ['⚠️  Degraded', $this->stats['degraded']],
                ['❌ Unhealthy', $this->stats['unhealthy']],
                ['💥 Failed', $this->stats['failed']],
                ['⏭️  Skipped', $this->stats['skipped']],
                ['⏱️  ระยะเวลา', "{$duration} วินาที"],
            ]
        );

        // แสดง warning ถ้ามีปัญหา
        if ($this->stats['unhealthy'] > 0) {
            $this->warn("⚠️  มี {$this->stats['unhealthy']} deployments ที่ unhealthy!");
        }

        if ($this->stats['failed'] > 0) {
            $this->error("❌ มี {$this->stats['failed']} deployments ที่ตรวจสอบไม่ได้!");
        }

        if ($this->stats['unhealthy'] === 0 && $this->stats['failed'] === 0 && $this->stats['checked'] > 0) {
            $this->info('✅ ทุก deployments ทำงานปกติ!');
        }

        $this->newLine();
        $this->info('✨ เสร็จสิ้นการตรวจสอบ!');
    }
}
