<?php

namespace App\Services;

use App\Models\User;
use App\Models\AiRentalDeployment;
use App\Models\AiRentalCloudConfig;
use App\Models\AiRentalModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI Rental Deployment Service
 *
 * จัดการ Business Logic สำหรับ Deployments
 */
class AiRentalDeploymentService
{
    /**
     * สร้าง Deployment ใหม่
     *
     * @param User $user ผู้ใช้
     * @param AiRentalCloudConfig $config Cloud configuration
     * @param AiRentalModel $model AI Model ที่จะ deploy
     * @param string $instanceType ประเภท instance (e.g., "A100-40GB")
     * @param string|null $deploymentName ชื่อ deployment (optional)
     * @param array $environmentVars ตัวแปร environment (optional)
     * @param int|null $autoStopMinutes เวลาหยุดอัตโนมัติ (นาที)
     * @return AiRentalDeployment
     *
     * @throws \Exception
     */
    public function createDeployment(
        User $user,
        AiRentalCloudConfig $config,
        AiRentalModel $model,
        string $instanceType,
        ?string $deploymentName = null,
        array $environmentVars = [],
        ?int $autoStopMinutes = null
    ): AiRentalDeployment {
        return DB::transaction(function () use (
            $user,
            $config,
            $model,
            $instanceType,
            $deploymentName,
            $environmentVars,
            $autoStopMinutes
        ) {
            // สร้างชื่อ deployment (ถ้าไม่ระบุ)
            if (!$deploymentName) {
                $deploymentName = $this->generateDeploymentName($model->name);
            }

            // สร้าง metadata
            $metadata = [
                'environment_vars' => $environmentVars,
                'auto_stop_minutes' => $autoStopMinutes,
                'created_from' => 'web_ui',
                'user_ip' => request()->ip(),
            ];

            // สร้าง deployment
            $deployment = AiRentalDeployment::create([
                'user_id' => $user->id,
                'cloud_config_id' => $config->id,
                'model_id' => $model->id,
                'name' => $deploymentName,
                'instance_type' => $instanceType,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            // Log การสร้าง
            Log::info("Deployment created", [
                'deployment_id' => $deployment->id,
                'user_id' => $user->id,
                'model' => $model->name,
                'instance_type' => $instanceType,
            ]);

            // เริ่ม deployment process (async)
            $this->initiateDeployment($deployment);

            return $deployment;
        });
    }

    /**
     * เริ่มกระบวนการ Deploy
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    protected function initiateDeployment(AiRentalDeployment $deployment): void
    {
        try {
            // อัพเดท status เป็น starting
            $deployment->update(['status' => 'starting']);

            // TODO: เรียก API ของ Cloud Provider เพื่อสร้าง instance
            // ตอนนี้ใช้ Mock data
            $this->mockDeploymentProcess($deployment);

            Log::info("Deployment initiated", [
                'deployment_id' => $deployment->id,
            ]);

        } catch (\Exception $e) {
            // ถ้าเกิด error ให้อัพเดท status
            $deployment->update([
                'status' => 'failed',
                'logs' => $this->appendLog($deployment->logs, 'error', $e->getMessage()),
            ]);

            Log::error("Deployment failed", [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mock Deployment Process (สำหรับทดสอบ)
     *
     * ในโปรดักชันจริงจะเรียก API ของ Cloud Provider
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    protected function mockDeploymentProcess(AiRentalDeployment $deployment): void
    {
        // สร้าง instance_id และ endpoint_url (mock)
        $instanceId = 'inst-' . Str::random(12);
        $endpointUrl = "https://api-{$instanceId}.inference.example.com/v1";

        // อัพเดท deployment
        $deployment->update([
            'status' => 'running',
            'instance_id' => $instanceId,
            'endpoint_url' => $endpointUrl,
            'deployed_at' => now(),
            'logs' => $this->createInitialLogs(),
        ]);

        // อัพเดท last_used_at ของ config
        $deployment->cloudConfig->update(['last_used_at' => now()]);
    }

    /**
     * เริ่ม Deployment (หลังจากหยุดไปแล้ว)
     *
     * @param AiRentalDeployment $deployment
     * @return void
     *
     * @throws \Exception
     */
    public function startDeployment(AiRentalDeployment $deployment): void
    {
        try {
            // อัพเดท status
            $deployment->update([
                'status' => 'starting',
                'logs' => $this->appendLog($deployment->logs, 'info', 'Restarting deployment...'),
            ]);

            // TODO: เรียก API เพื่อ start instance
            // Mock: อัพเดทเป็น running
            sleep(1); // Simulate API call
            $deployment->update([
                'status' => 'running',
                'deployed_at' => now(),
                'stopped_at' => null,
                'logs' => $this->appendLog($deployment->logs, 'success', 'Deployment started successfully'),
            ]);

            Log::info("Deployment started", [
                'deployment_id' => $deployment->id,
            ]);

        } catch (\Exception $e) {
            $deployment->update([
                'status' => 'failed',
                'logs' => $this->appendLog($deployment->logs, 'error', $e->getMessage()),
            ]);

            Log::error("Failed to start deployment", [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * หยุด Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return void
     *
     * @throws \Exception
     */
    public function stopDeployment(AiRentalDeployment $deployment): void
    {
        try {
            // อัพเดท status
            $deployment->update([
                'status' => 'stopping',
                'logs' => $this->appendLog($deployment->logs, 'info', 'Stopping deployment...'),
            ]);

            // TODO: เรียก API เพื่อ stop instance
            // Mock: อัพเดทเป็น stopped
            sleep(1); // Simulate API call

            // Finalize deployment (คำนวณต้นทุน)
            $this->finalizeDeployment($deployment);

            Log::info("Deployment stopped", [
                'deployment_id' => $deployment->id,
                'total_hours' => $deployment->total_hours,
                'total_cost' => $deployment->total_cost,
            ]);

        } catch (\Exception $e) {
            $deployment->update([
                'status' => 'error',
                'logs' => $this->appendLog($deployment->logs, 'error', $e->getMessage()),
            ]);

            Log::error("Failed to stop deployment", [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * รีสตาร์ท Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return void
     *
     * @throws \Exception
     */
    public function restartDeployment(AiRentalDeployment $deployment): void
    {
        try {
            $deployment->update([
                'logs' => $this->appendLog($deployment->logs, 'info', 'Restarting deployment...'),
            ]);

            // หยุดก่อน
            $this->stopDeployment($deployment);

            // รอ 2 วินาที
            sleep(2);

            // เริ่มใหม่
            $this->startDeployment($deployment);

            Log::info("Deployment restarted", [
                'deployment_id' => $deployment->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to restart deployment", [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Finalize Deployment (คำนวณต้นทุนเมื่อหยุด)
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function finalizeDeployment(AiRentalDeployment $deployment): void
    {
        // คำนวณเวลาทำงาน (ชั่วโมง)
        $deployedAt = $deployment->deployed_at;
        $stoppedAt = now();

        if ($deployedAt) {
            $totalMinutes = $deployedAt->diffInMinutes($stoppedAt);
            $totalHours = round($totalMinutes / 60, 2);
        } else {
            $totalHours = 0;
        }

        // คำนวณต้นทุน (ตามราคาของ model และ instance type)
        $costPerHour = $this->calculateCostPerHour($deployment);
        $totalCost = round($totalHours * $costPerHour, 2);

        // อัพเดท deployment
        $deployment->update([
            'status' => 'stopped',
            'stopped_at' => $stoppedAt,
            'total_hours' => $totalHours,
            'total_cost' => $totalCost,
            'logs' => $this->appendLog(
                $deployment->logs,
                'info',
                "Deployment stopped. Total hours: {$totalHours}h, Total cost: \${$totalCost}"
            ),
        ]);
    }

    /**
     * คำนวณต้นทุนต่อชั่วโมง
     *
     * @param AiRentalDeployment $deployment
     * @return float
     */
    protected function calculateCostPerHour(AiRentalDeployment $deployment): float
    {
        // ดึงราคาจาก cloud provider
        $provider = $deployment->cloudConfig->cloudProvider;

        // ใช้ราคาเฉลี่ยจาก provider
        $basePrice = ($provider->min_price_per_hour + $provider->max_price_per_hour) / 2;

        // ปรับราคาตาม instance type
        $multiplier = $this->getInstanceTypeMultiplier($deployment->instance_type);

        return round($basePrice * $multiplier, 2);
    }

    /**
     * ดึง multiplier สำหรับ instance type
     *
     * @param string $instanceType
     * @return float
     */
    protected function getInstanceTypeMultiplier(string $instanceType): float
    {
        // Multipliers ตาม GPU type
        $multipliers = [
            'A100-80GB' => 2.5,
            'A100-40GB' => 2.0,
            'A100-20GB' => 1.5,
            'A6000' => 1.3,
            'RTX 4090' => 1.0,
            'RTX 3090' => 0.8,
            'T4' => 0.5,
        ];

        // ค้นหา multiplier ที่ตรงกัน
        foreach ($multipliers as $type => $multiplier) {
            if (str_contains($instanceType, $type)) {
                return $multiplier;
            }
        }

        // Default multiplier
        return 1.0;
    }

    /**
     * ดึง Logs
     *
     * @param AiRentalDeployment $deployment
     * @return array
     */
    public function fetchLogs(AiRentalDeployment $deployment): array
    {
        // TODO: ดึง logs จาก Cloud Provider API
        // ตอนนี้ return logs ที่บันทึกไว้ใน database

        return $deployment->logs ?? [];
    }

    /**
     * สร้างชื่อ Deployment
     *
     * @param string $modelName
     * @return string
     */
    protected function generateDeploymentName(string $modelName): string
    {
        $slug = Str::slug($modelName);
        $random = Str::random(6);
        return "{$slug}-{$random}";
    }

    /**
     * สร้าง Initial Logs
     *
     * @return array
     */
    protected function createInitialLogs(): array
    {
        return [
            [
                'timestamp' => now()->toIso8601String(),
                'level' => 'info',
                'message' => 'Deployment initialized',
            ],
            [
                'timestamp' => now()->addSeconds(5)->toIso8601String(),
                'level' => 'info',
                'message' => 'Pulling Docker image...',
            ],
            [
                'timestamp' => now()->addSeconds(30)->toIso8601String(),
                'level' => 'info',
                'message' => 'Loading model weights...',
            ],
            [
                'timestamp' => now()->addSeconds(60)->toIso8601String(),
                'level' => 'success',
                'message' => 'Model loaded successfully',
            ],
            [
                'timestamp' => now()->addSeconds(70)->toIso8601String(),
                'level' => 'info',
                'message' => 'Starting API server...',
            ],
            [
                'timestamp' => now()->addSeconds(80)->toIso8601String(),
                'level' => 'success',
                'message' => 'Deployment is ready!',
            ],
        ];
    }

    /**
     * เพิ่ม Log Entry
     *
     * @param array|null $existingLogs
     * @param string $level (info, success, warning, error)
     * @param string $message
     * @return array
     */
    protected function appendLog(?array $existingLogs, string $level, string $message): array
    {
        $logs = $existingLogs ?? [];

        $logs[] = [
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
        ];

        // จำกัดจำนวน logs (เก็บแค่ 500 entries ล่าสุด)
        if (count($logs) > 500) {
            $logs = array_slice($logs, -500);
        }

        return $logs;
    }

    /**
     * ตรวจสอบ Health ของ Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return array
     */
    public function checkHealth(AiRentalDeployment $deployment): array
    {
        // TODO: เรียก health check endpoint
        // Mock response
        return [
            'status' => 'healthy',
            'response_time_ms' => rand(50, 200),
            'memory_usage_percent' => rand(30, 80),
            'gpu_usage_percent' => rand(20, 90),
            'uptime_seconds' => $deployment->getUptimeMinutes() * 60,
        ];
    }

    /**
     * ดึงสถิติการใช้งาน
     *
     * @param User $user
     * @return array
     */
    public function getUserStats(User $user): array
    {
        return [
            'total_deployments' => AiRentalDeployment::where('user_id', $user->id)->count(),
            'running_deployments' => AiRentalDeployment::where('user_id', $user->id)
                ->where('status', 'running')
                ->count(),
            'total_hours' => AiRentalDeployment::where('user_id', $user->id)
                ->sum('total_hours'),
            'total_cost' => AiRentalDeployment::where('user_id', $user->id)
                ->sum('total_cost'),
            'average_uptime' => AiRentalDeployment::where('user_id', $user->id)
                ->whereNotNull('total_hours')
                ->avg('total_hours'),
        ];
    }
}
