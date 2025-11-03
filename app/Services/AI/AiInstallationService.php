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
        $provider = AiProvider::where('name', 'deepseek-local')->first();

        if ($provider) {
            $provider->is_active = true;
            $provider->is_available = true;
            $provider->config = array_merge($provider->config ?? [], [
                'installed_models' => [$modelId],
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
        // สำหรับ DeepSeek models
        // Format: deepseek-coder:6.7b-instruct-q4_k_m

        $modelName = str_replace('deepseek-coder-', 'deepseek-coder:', $modelId);
        $modelName = str_replace('deepseek-llm-', 'deepseek-llm:', $modelName);
        $modelName = str_replace('-instruct', '-instruct', $modelName);
        $modelName = str_replace('-chat', '-chat', $modelName);

        // เพิ่ม quantization tag
        $quantLower = strtolower(str_replace('_', '_', $quantization));
        $modelName .= '-' . $quantLower;

        return $modelName;
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
     * ลบโมเดลที่ติดตั้งแล้ว
     */
    public function uninstallModel(string $modelId): bool
    {
        try {
            $result = Process::run('ollama rm ' . $modelId);

            return $result->successful();
        } catch (\Exception $e) {
            Log::error('Failed to uninstall model', [
                'model_id' => $modelId,
                'error' => $e->getMessage(),
            ]);

            return false;
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
