<?php

namespace App\Services\AI;

use App\Models\AiInstallationLog;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class AiInstallationService
{
    private SystemRequirementsChecker $requirementsChecker;
    private ModelRecommendationService $recommendationService;

    public function __construct()
    {
        $this->requirementsChecker = new SystemRequirementsChecker();
        $this->recommendationService = new ModelRecommendationService();
    }

    /**
     * เริ่มต้นการติดตั้ง
     */
    public function startInstallation(
        int $userId,
        string $installationType,
        string $modelId,
        string $quantization,
        array $config = []
    ): AiInstallationLog {
        // สร้าง installation log
        $log = AiInstallationLog::create([
            'user_id' => $userId,
            'installation_type' => $installationType,
            'status' => AiInstallationLog::STATUS_PENDING,
            'progress_percentage' => 0,
            'current_step' => 'เตรียมการติดตั้ง',
            'total_steps' => $this->getTotalSteps($installationType),
            'config' => array_merge($config, [
                'model_id' => $modelId,
                'quantization' => $quantization,
            ]),
            'started_at' => now(),
        ]);

        $log->appendLog('เริ่มต้นการติดตั้ง ' . $installationType);
        $log->appendLog('โมเดล: ' . $modelId);
        $log->appendLog('Quantization: ' . $quantization);

        return $log;
    }

    /**
     * ติดตั้ง DeepSeek
     */
    public function installDeepSeek(AiInstallationLog $log): bool
    {
        try {
            $config = $log->config;
            $modelId = $config['model_id'];
            $quantization = $config['quantization'];

            // Step 1: ตรวจสอบความต้องการของระบบ
            $log->updateProgress(
                AiInstallationLog::STATUS_PENDING,
                5,
                'ตรวจสอบความต้องการของระบบ'
            );

            $systemInfo = $this->requirementsChecker->checkAll();
            $log->appendLog('RAM: ' . $systemInfo['ram']['available_gb'] . ' GB available');
            $log->appendLog('CPU: ' . $systemInfo['cpu']['cores'] . ' cores');

            if ($systemInfo['gpu']['available']) {
                $log->appendLog('GPU: ' . $systemInfo['gpu']['gpus'][0]['name']);
                $log->appendLog('VRAM: ' . $systemInfo['gpu']['gpus'][0]['memory_total_gb'] . ' GB');
            } else {
                $log->appendLog('GPU: ไม่พบ - จะใช้ CPU inference');
            }

            if (!$systemInfo['overall_status']['ready']) {
                $errors = implode(', ', $systemInfo['overall_status']['errors']);
                throw new \Exception('ระบบไม่พร้อม: ' . $errors);
            }

            // Step 2: ตรวจสอบและติดตั้ง Ollama
            $log->updateProgress(
                AiInstallationLog::STATUS_DOWNLOADING,
                15,
                'ตรวจสอบและติดตั้ง Ollama'
            );

            $ollamaInstalled = $this->ensureOllamaInstalled($log);

            if (!$ollamaInstalled) {
                throw new \Exception('ไม่สามารถติดตั้ง Ollama ได้');
            }

            // Step 3: ดาวน์โหลดโมเดล
            $log->updateProgress(
                AiInstallationLog::STATUS_DOWNLOADING,
                30,
                'กำลังดาวน์โหลดโมเดล ' . $modelId
            );

            $modelDownloaded = $this->downloadModel($log, $modelId, $quantization);

            if (!$modelDownloaded) {
                throw new \Exception('ไม่สามารถดาวน์โหลดโมเดลได้');
            }

            // Step 4: ตั้งค่า configuration
            $log->updateProgress(
                AiInstallationLog::STATUS_CONFIGURING,
                80,
                'กำลังตั้งค่าระบบ'
            );

            $this->configureModel($log, $modelId, $quantization, $systemInfo);

            // Step 5: ทดสอบการเชื่อมต่อ
            $log->updateProgress(
                AiInstallationLog::STATUS_TESTING,
                90,
                'ทดสอบการเชื่อมต่อ'
            );

            $testResult = $this->testModelConnection($log, $modelId);

            if (!$testResult) {
                throw new \Exception('ไม่สามารถเชื่อมต่อกับโมเดลได้');
            }

            // Step 6: เสร็จสิ้น
            $log->markCompleted();
            $log->appendLog('ติดตั้งเสร็จสิ้น!');
            $log->appendLog('โมเดลพร้อมใช้งานที่: http://localhost:11434');

            // อัปเดต AI Provider ให้ active
            $this->activateLocalProvider($modelId, $config);

            return true;
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
            $log->appendLog('ERROR: ' . $e->getMessage());
            Log::error('DeepSeek installation failed', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * ตรวจสอบและติดตั้ง Ollama ถ้ายังไม่มี
     */
    private function ensureOllamaInstalled(AiInstallationLog $log): bool
    {
        // ตรวจสอบว่ามี ollama อยู่แล้วหรือไม่
        $result = Process::run('which ollama');

        if ($result->successful() && !empty(trim($result->output()))) {
            $log->appendLog('Ollama ติดตั้งอยู่แล้วที่: ' . trim($result->output()));

            // ตรวจสอบ version
            $versionResult = Process::run('ollama --version');
            if ($versionResult->successful()) {
                $log->appendLog('Ollama version: ' . trim($versionResult->output()));
            }

            return true;
        }

        $log->appendLog('ไม่พบ Ollama - เริ่มติดตั้ง...');

        // ติดตั้ง Ollama
        $installScript = 'curl -fsSL https://ollama.com/install.sh | sh';
        $log->appendLog('รัน: ' . $installScript);

        $result = Process::timeout(600)->run($installScript);

        if ($result->successful()) {
            $log->appendLog('ติดตั้ง Ollama สำเร็จ');
            $log->appendLog($result->output());
            return true;
        } else {
            $log->appendLog('ERROR: ติดตั้ง Ollama ไม่สำเร็จ');
            $log->appendLog($result->errorOutput());
            return false;
        }
    }

    /**
     * ดาวน์โหลดโมเดล
     */
    private function downloadModel(
        AiInstallationLog $log,
        string $modelId,
        string $quantization
    ): bool {
        // แปลง model ID และ quantization เป็น Ollama model name
        $ollamaModelName = $this->convertToOllamaModelName($modelId, $quantization);

        $log->appendLog('กำลังดาวน์โหลด: ' . $ollamaModelName);
        $log->appendLog('การดาวน์โหลดอาจใช้เวลานาน กรุณารอ...');

        // รัน ollama pull ใน background และติดตาม progress
        $command = 'ollama pull ' . $ollamaModelName;
        $log->appendLog('รัน: ' . $command);

        // TODO: ในการใช้งานจริง ควรรันใน background job และติดตาม progress
        // ตอนนี้จะรันแบบ synchronous เพื่อความง่าย
        $result = Process::timeout(3600)->run($command); // 1 hour timeout

        if ($result->successful()) {
            $log->appendLog('ดาวน์โหลดโมเดลสำเร็จ');
            $log->appendLog($result->output());

            // อัปเดต progress เป็น 70%
            $log->updateProgress(
                AiInstallationLog::STATUS_INSTALLING,
                70,
                'ติดตั้งโมเดลเสร็จสิ้น'
            );

            return true;
        } else {
            $log->appendLog('ERROR: ดาวน์โหลดโมเดลไม่สำเร็จ');
            $log->appendLog($result->errorOutput());
            return false;
        }
    }

    /**
     * ตั้งค่าโมเดล
     */
    private function configureModel(
        AiInstallationLog $log,
        string $modelId,
        string $quantization,
        array $systemInfo
    ): void {
        $log->appendLog('กำลังตั้งค่าโมเดล...');

        // สร้าง Modelfile สำหรับ custom configuration
        $settings = $this->recommendationService->getOptimalSettings($modelId, $systemInfo);

        $log->appendLog('การตั้งค่าที่แนะนำ:');
        $log->appendLog('- Use GPU: ' . ($settings['use_gpu'] ? 'Yes' : 'No'));
        $log->appendLog('- Threads: ' . $settings['threads']);
        $log->appendLog('- Context Size: ' . $settings['context_size']);
        $log->appendLog('- Batch Size: ' . $settings['batch_size']);

        // บันทึก config
        $log->config = array_merge($log->config, [
            'optimal_settings' => $settings,
            'ollama_model_name' => $this->convertToOllamaModelName($modelId, $quantization),
        ]);
        $log->save();

        $log->appendLog('ตั้งค่าเสร็จสิ้น');
    }

    /**
     * ทดสอบการเชื่อมต่อกับโมเดล
     */
    private function testModelConnection(AiInstallationLog $log, string $modelId): bool
    {
        $log->appendLog('กำลังทดสอบการเชื่อมต่อ...');

        $ollamaModelName = $log->config['ollama_model_name'];

        // ตรวจสอบว่าโมเดลมีอยู่ใน ollama list
        $result = Process::run('ollama list');

        if ($result->successful()) {
            $log->appendLog('รายการโมเดลที่ติดตั้ง:');
            $log->appendLog($result->output());

            if (str_contains($result->output(), $ollamaModelName)) {
                $log->appendLog('✓ พบโมเดล ' . $ollamaModelName);

                // ทดสอบรันโมเดล
                $testPrompt = 'สวัสดี ตอบกลับด้วยคำว่า "พร้อมใช้งาน"';
                $command = 'ollama run ' . $ollamaModelName . ' "' . $testPrompt . '"';

                $log->appendLog('ทดสอบรันโมเดล...');
                $result = Process::timeout(120)->run($command);

                if ($result->successful()) {
                    $log->appendLog('✓ โมเดลทำงานปกติ');
                    $log->appendLog('Response: ' . substr($result->output(), 0, 200));
                    return true;
                } else {
                    $log->appendLog('✗ โมเดลไม่สามารถตอบกลับได้');
                    return false;
                }
            } else {
                $log->appendLog('✗ ไม่พบโมเดล ' . $ollamaModelName . ' ในรายการ');
                return false;
            }
        } else {
            $log->appendLog('ERROR: ไม่สามารถเรียก ollama list ได้');
            return false;
        }
    }

    /**
     * เปิดใช้งาน Local Provider
     */
    private function activateLocalProvider(string $modelId, array $config): void
    {
        // หา Ollama provider
        $provider = AiProvider::where('provider_type', 'ollama')
            ->orWhere('name', 'LIKE', '%ollama%')
            ->orWhere('name', 'LIKE', '%local%')
            ->first();

        if ($provider) {
            $provider->is_active = true;
            $provider->is_available = true;

            // เพิ่มโมเดลเข้าไปในรายการ installed_models
            $installedModels = $provider->config['installed_models'] ?? [];
            if (!in_array($modelId, $installedModels)) {
                $installedModels[] = $modelId;
            }

            $provider->config = array_merge($provider->config ?? [], [
                'installed_models' => $installedModels,
                'ollama_endpoint' => 'http://localhost:11434',
                'last_installed_at' => now()->toDateTimeString(),
            ]);
            $provider->save();
        }
    }

    /**
     * แปลง model ID เป็น Ollama model name
     */
    private function convertToOllamaModelName(string $modelId, string $quantization): string
    {
        // โมเดลใหม่ใช้ชื่อตรงกับ Ollama เลย ไม่ต้องแปลง
        // Format: qwen2.5:0.5b, gemma2:2b, llama3.2:3b, phi3:3.8b

        // ถ้ามี : แสดงว่าชื่อถูกต้องแล้ว ใช้เลย
        if (str_contains($modelId, ':')) {
            return $modelId;
        }

        // Fallback: ถ้าไม่มี : ให้เพิ่ม quantization
        return $modelId;
    }

    /**
     * รับจำนวน steps ทั้งหมด
     */
    private function getTotalSteps(string $installationType): int
    {
        return match ($installationType) {
            AiInstallationLog::TYPE_DEEPSEEK => 6,
            AiInstallationLog::TYPE_OLLAMA => 5,
            default => 3,
        };
    }

    /**
     * ยกเลิกการติดตั้ง
     */
    public function cancelInstallation(AiInstallationLog $log): void
    {
        $log->cancel();
        $log->appendLog('ผู้ใช้ยกเลิกการติดตั้ง');

        // TODO: Kill any running processes
    }

    /**
     * ลบโมเดลที่ติดตั้งแล้ว (ปลอดภัย - ตรวจสอบว่าไม่มีการใช้งานอยู่)
     */
    public function uninstallModel(string $modelId): array
    {
        try {
            Log::info('Starting model uninstallation', ['model_id' => $modelId]);

            // ตรวจสอบว่ามีโมเดลนี้ติดตั้งอยู่จริงหรือไม่
            $installedModels = $this->getInstalledModels();
            $modelExists = false;

            foreach ($installedModels as $model) {
                if ($model['name'] === $modelId) {
                    $modelExists = true;
                    break;
                }
            }

            if (!$modelExists) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบโมเดลนี้ในระบบ',
                    'code' => 'MODEL_NOT_FOUND',
                ];
            }

            // ตรวจสอบว่า Ollama กำลังรันอยู่หรือไม่
            $ollamaStatus = $this->checkOllamaStatus();

            if (!$ollamaStatus['running']) {
                return [
                    'success' => false,
                    'message' => 'Ollama ไม่ได้รันอยู่ กรุณาเริ่ม Ollama Service ก่อน',
                    'code' => 'OLLAMA_NOT_RUNNING',
                ];
            }

            // เช็คว่ามี bot ที่กำลังใช้โมเดลนี้อยู่หรือไม่
            $activeBots = \App\Models\AiBotProfile::where('is_active', true)
                ->whereHas('model', function($query) use ($modelId) {
                    // ตรวจสอบว่า model_identifier มีชื่อโมเดลที่คล้ายกัน
                    $query->where('model_identifier', 'LIKE', '%' . explode(':', $modelId)[0] . '%');
                })
                ->count();

            if ($activeBots > 0) {
                return [
                    'success' => false,
                    'message' => "พบ {$activeBots} AI Bot ที่กำลังใช้โมเดลนี้อยู่\n\nกรุณาปิดการใช้งาน หรือเปลี่ยนโมเดลของ Bot เหล่านั้นก่อน",
                    'code' => 'MODEL_IN_USE',
                    'active_bots_count' => $activeBots,
                ];
            }

            // ลบโมเดล
            Log::info('Executing ollama rm command', ['model_id' => $modelId]);
            $result = Process::timeout(60)->run('ollama rm ' . $modelId);

            if ($result->successful()) {
                Log::info('Model uninstalled successfully', ['model_id' => $modelId]);

                // ลบจาก provider config
                $this->removeModelFromProviderConfig($modelId);

                return [
                    'success' => true,
                    'message' => 'ถอนการติดตั้งโมเดลเรียบร้อยแล้ว',
                    'output' => $result->output(),
                ];
            } else {
                Log::warning('Model uninstallation failed', [
                    'model_id' => $modelId,
                    'error' => $result->errorOutput(),
                ]);

                return [
                    'success' => false,
                    'message' => 'ไม่สามารถถอนการติดตั้งได้: ' . $result->errorOutput(),
                    'code' => 'UNINSTALL_FAILED',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to uninstall model', [
                'model_id' => $modelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * ลบโมเดลออกจาก Provider config
     */
    private function removeModelFromProviderConfig(string $modelId): void
    {
        try {
            $provider = \App\Models\AiProvider::where('name', 'deepseek-local')
                ->orWhere('provider_type', 'ollama')
                ->first();

            if ($provider && isset($provider->config['installed_models'])) {
                $installedModels = $provider->config['installed_models'];

                // ลบโมเดลออกจาก array
                $installedModels = array_filter($installedModels, function($model) use ($modelId) {
                    return !str_contains($model, $modelId) && !str_contains($modelId, $model);
                });

                $config = $provider->config;
                $config['installed_models'] = array_values($installedModels);
                $provider->config = $config;
                $provider->save();

                Log::info('Removed model from provider config', [
                    'model_id' => $modelId,
                    'provider_id' => $provider->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update provider config after uninstall', [
                'model_id' => $modelId,
                'error' => $e->getMessage(),
            ]);
            // ไม่ throw error เพราะโมเดลลบออกแล้ว
        }
    }

    /**
     * รายการโมเดลที่ติดตั้งแล้ว
     */
    public function getInstalledModels(): array
    {
        try {
            $result = Process::run('ollama list');

            if ($result->successful()) {
                $output = trim($result->output());
                $lines = explode("\n", $output);

                // ข้าม header line
                array_shift($lines);

                $models = [];

                foreach ($lines as $line) {
                    if (empty(trim($line))) {
                        continue;
                    }

                    $parts = preg_split('/\s+/', trim($line));

                    if (count($parts) >= 3) {
                        $models[] = [
                            'name' => $parts[0],
                            'id' => $parts[1],
                            'size' => $parts[2],
                            'modified' => implode(' ', array_slice($parts, 3)),
                        ];
                    }
                }

                return $models;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get installed models', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * ตรวจสอบสถานะ Ollama service
     */
    public function checkOllamaStatus(): array
    {
        $status = [
            'installed' => false,
            'running' => false,
            'version' => null,
            'endpoint' => 'http://localhost:11434',
        ];

        try {
            // ตรวจสอบว่าติดตั้งหรือไม่
            $result = Process::run('which ollama');
            if ($result->successful() && !empty(trim($result->output()))) {
                $status['installed'] = true;

                // ตรวจสอบ version
                $versionResult = Process::run('ollama --version');
                if ($versionResult->successful()) {
                    $status['version'] = trim($versionResult->output());
                }

                // ตรวจสอบว่ารันอยู่หรือไม่
                $pingResult = Process::run('curl -s http://localhost:11434/api/tags');
                if ($pingResult->successful()) {
                    $status['running'] = true;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check Ollama status', [
                'error' => $e->getMessage(),
            ]);
        }

        return $status;
    }
}
