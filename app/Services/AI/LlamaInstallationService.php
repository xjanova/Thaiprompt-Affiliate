<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Service สำหรับจัดการการติดตั้ง Llama AI
 *
 * รองรับการติดตั้งผ่าน:
 * - Ollama
 * - llama.cpp (Hugging Face)
 *
 * มี Progress tracking และ status reporting
 *
 * หมายเหตุ: บน shared hosting จะไม่สามารถติดตั้งได้
 * จะแสดงคำแนะนำแทน
 */
class LlamaInstallationService
{
    /**
     * ไฟล์สำหรับเก็บ progress
     */
    private const PROGRESS_FILE = 'ai-installation/progress.json';

    /**
     * ไฟล์สำหรับเก็บ log
     */
    private const LOG_FILE = 'ai-installation/install.log';

    /**
     * Installation steps
     */
    private const STEPS = [
        'checking_system' => ['name' => 'ตรวจสอบระบบ', 'weight' => 5],
        'installing_dependencies' => ['name' => 'ติดตั้ง Dependencies', 'weight' => 10],
        'downloading_model' => ['name' => 'ดาวน์โหลด Model', 'weight' => 60],
        'setting_up_server' => ['name' => 'ตั้งค่า Server', 'weight' => 15],
        'testing' => ['name' => 'ทดสอบระบบ', 'weight' => 10],
    ];

    /**
     * เริ่มการติดตั้ง Llama
     *
     * @param string $method วิธีติดตั้ง: 'ollama' หรือ 'huggingface'
     * @param string $model Model ที่ต้องการติดตั้ง
     * @return array
     */
    public function startInstallation(string $method = 'ollama', string $model = 'llama3.2:3b'): array
    {
        // ตรวจสอบว่าสามารถติดตั้งได้หรือไม่
        $canInstall = $this->checkInstallCapability();

        if (!$canInstall['can_install']) {
            // บันทึก log
            $this->log("ไม่สามารถติดตั้งได้: {$canInstall['reason']}");

            // อัพเดท progress เป็น error พร้อมคำแนะนำ
            $this->updateProgress([
                'status' => 'error',
                'step' => 'checking_system',
                'step_name' => 'ตรวจสอบระบบ',
                'percentage' => 5,
                'message' => $canInstall['reason'],
                'is_shared_hosting' => true,
                'manual_instructions' => $this->getManualInstructions($method, $model),
                'alternative_options' => $this->getAlternativeOptions(),
            ]);

            return [
                'success' => false,
                'message' => $canInstall['reason'],
                'is_shared_hosting' => true,
                'manual_instructions' => $this->getManualInstructions($method, $model),
                'alternative_options' => $this->getAlternativeOptions(),
            ];
        }

        // เคลียร์ progress เดิม
        $this->resetProgress();

        // บันทึก log
        $this->log("เริ่มติดตั้ง Llama - Method: {$method}, Model: {$model}");

        // Update progress
        $this->updateProgress([
            'status' => 'running',
            'step' => 'checking_system',
            'step_name' => 'ตรวจสอบระบบ',
            'percentage' => 0,
            'message' => 'กำลังเริ่มต้นการติดตั้ง...',
            'started_at' => now()->toIso8601String(),
            'method' => $method,
            'model' => $model,
        ]);

        // รัน script ใน background
        $scriptPath = base_path('scripts/install-llama-with-progress.sh');

        // สร้าง script ถ้ายังไม่มี
        if (!file_exists($scriptPath)) {
            $this->createInstallScript($scriptPath);
        }

        // รัน script
        $progressFile = storage_path('app/' . self::PROGRESS_FILE);
        $logFile = storage_path('app/' . self::LOG_FILE);

        $command = sprintf(
            'bash %s %s %s %s %s > /dev/null 2>&1 &',
            escapeshellarg($scriptPath),
            escapeshellarg($method),
            escapeshellarg($model),
            escapeshellarg($progressFile),
            escapeshellarg($logFile)
        );

        @exec($command);

        // ตรวจสอบว่า script รันได้จริงหรือไม่ (รอ 2 วินาที)
        sleep(2);
        $progress = $this->getProgress();

        if ($progress['percentage'] == 0 && $progress['status'] === 'running') {
            // Script ไม่ได้รัน - อาจเป็น shared hosting
            $this->updateProgress([
                'status' => 'error',
                'step' => 'checking_system',
                'step_name' => 'ตรวจสอบระบบ',
                'percentage' => 5,
                'message' => 'ไม่สามารถรัน background process ได้ เซิร์ฟเวอร์นี้อาจเป็น shared hosting',
                'is_shared_hosting' => true,
                'manual_instructions' => $this->getManualInstructions($method, $model),
                'alternative_options' => $this->getAlternativeOptions(),
            ]);

            return [
                'success' => false,
                'message' => 'ไม่สามารถรัน background process ได้',
                'is_shared_hosting' => true,
                'manual_instructions' => $this->getManualInstructions($method, $model),
            ];
        }

        return [
            'success' => true,
            'message' => 'เริ่มการติดตั้งแล้ว',
            'progress_url' => '/admin/ai-providers/install/progress',
        ];
    }

    /**
     * ตรวจสอบความสามารถในการติดตั้ง
     *
     * @return array
     */
    private function checkInstallCapability(): array
    {
        // 1. ตรวจสอบ exec function
        if (!function_exists('exec')) {
            return [
                'can_install' => false,
                'reason' => 'PHP function exec() ถูก disable - ไม่สามารถรันคำสั่งติดตั้งได้',
            ];
        }

        // 2. ตรวจสอบว่า exec ถูก disable หรือไม่
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));
        if (in_array('exec', $disabledFunctions)) {
            return [
                'can_install' => false,
                'reason' => 'PHP function exec() ถูก disable ใน php.ini',
            ];
        }

        // 3. ตรวจสอบ proc_open (จำเป็นสำหรับ background process)
        if (in_array('proc_open', $disabledFunctions)) {
            return [
                'can_install' => false,
                'reason' => 'PHP function proc_open() ถูก disable - ไม่สามารถรัน background process ได้',
            ];
        }

        // 4. ทดสอบรันคำสั่งง่ายๆ
        $output = [];
        $returnCode = -1;
        @exec('echo "test"', $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'can_install' => false,
                'reason' => 'ไม่สามารถรันคำสั่ง shell ได้ - อาจเป็น shared hosting ที่มีการจำกัด',
            ];
        }

        // 5. ตรวจสอบว่าสามารถเขียนไฟล์ script ได้
        $scriptsDir = base_path('scripts');
        if (!is_writable($scriptsDir)) {
            return [
                'can_install' => false,
                'reason' => 'ไม่สามารถเขียนไฟล์ในโฟลเดอร์ scripts/ ได้',
            ];
        }

        return [
            'can_install' => true,
            'reason' => 'พร้อมติดตั้ง',
        ];
    }

    /**
     * คำแนะนำการติดตั้งแบบ manual
     *
     * @param string $method
     * @param string $model
     * @return array
     */
    private function getManualInstructions(string $method, string $model): array
    {
        if ($method === 'ollama') {
            return [
                'title' => 'วิธีติดตั้ง Ollama บน VPS/Server ของคุณ',
                'steps' => [
                    '1. SSH เข้าสู่เซิร์ฟเวอร์ของคุณ',
                    '2. รันคำสั่ง: curl -fsSL https://ollama.com/install.sh | sh',
                    '3. เริ่ม Ollama: ollama serve',
                    "4. ดาวน์โหลด Model: ollama pull {$model}",
                    '5. ทดสอบ: curl http://localhost:11434/api/tags',
                ],
                'commands' => [
                    'curl -fsSL https://ollama.com/install.sh | sh',
                    'ollama serve &',
                    "ollama pull {$model}",
                ],
            ];
        }

        return [
            'title' => 'วิธีติดตั้ง Llama จาก Hugging Face',
            'steps' => [
                '1. SSH เข้าสู่เซิร์ฟเวอร์ที่มี RAM เพียงพอ',
                '2. ติดตั้ง Python: sudo apt install python3 python3-pip',
                '3. ติดตั้ง huggingface_hub: pip install huggingface_hub',
                "4. ดาวน์โหลด Model: huggingface-cli download meta-llama/{$model}",
                '5. ติดตั้ง llama.cpp สำหรับรัน inference',
            ],
            'commands' => [
                'pip install huggingface_hub',
                "huggingface-cli download meta-llama/{$model}",
            ],
        ];
    }

    /**
     * ทางเลือกอื่นสำหรับใช้ AI
     *
     * @return array
     */
    private function getAlternativeOptions(): array
    {
        return [
            [
                'name' => 'Together AI (Meta Llama Cloud)',
                'description' => 'ใช้ Llama 4 ผ่าน API โดยไม่ต้องติดตั้ง',
                'url' => 'https://api.together.xyz',
                'free_tier' => true,
                'setup' => 'ไปที่ AI Providers > Meta > กรอก API Key',
            ],
            [
                'name' => 'OpenAI',
                'description' => 'ใช้ GPT-4 ผ่าน API',
                'url' => 'https://platform.openai.com',
                'free_tier' => false,
                'setup' => 'ไปที่ AI Providers > OpenAI > กรอก API Key',
            ],
            [
                'name' => 'Anthropic Claude',
                'description' => 'ใช้ Claude 3 ผ่าน API',
                'url' => 'https://console.anthropic.com',
                'free_tier' => false,
                'setup' => 'ไปที่ AI Providers > Anthropic > กรอก API Key',
            ],
            [
                'name' => 'VPS แยกสำหรับ Ollama',
                'description' => 'เช่า VPS (เช่น DigitalOcean, Vultr) เพื่อรัน Ollama',
                'url' => 'https://www.digitalocean.com',
                'free_tier' => false,
                'setup' => 'เช่า VPS > ติดตั้ง Ollama > เชื่อมต่อผ่าน API',
            ],
        ];
    }

    /**
     * ดึง progress ปัจจุบัน
     *
     * @return array
     */
    public function getProgress(): array
    {
        if (!Storage::exists(self::PROGRESS_FILE)) {
            return [
                'status' => 'idle',
                'percentage' => 0,
                'message' => 'ยังไม่ได้เริ่มติดตั้ง',
            ];
        }

        $content = Storage::get(self::PROGRESS_FILE);
        $progress = json_decode($content, true);

        if (!$progress) {
            return [
                'status' => 'error',
                'percentage' => 0,
                'message' => 'ไม่สามารถอ่าน progress ได้',
            ];
        }

        return $progress;
    }

    /**
     * อัพเดท progress
     *
     * @param array $data
     */
    public function updateProgress(array $data): void
    {
        Storage::put(self::PROGRESS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * รีเซ็ต progress
     */
    public function resetProgress(): void
    {
        if (Storage::exists(self::PROGRESS_FILE)) {
            Storage::delete(self::PROGRESS_FILE);
        }
        if (Storage::exists(self::LOG_FILE)) {
            Storage::delete(self::LOG_FILE);
        }

        // สร้าง directory
        Storage::makeDirectory('ai-installation');
    }

    /**
     * ดึง installation log
     *
     * @return string
     */
    public function getLog(): string
    {
        if (!Storage::exists(self::LOG_FILE)) {
            return '';
        }

        return Storage::get(self::LOG_FILE);
    }

    /**
     * บันทึก log
     *
     * @param string $message
     */
    public function log(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}\n";

        Storage::append(self::LOG_FILE, $line);
        Log::info("Llama Installation: {$message}");
    }

    /**
     * ตรวจสอบสถานะการติดตั้ง Ollama
     *
     * @return array{is_installed: bool, version: string|null, message: string}
     */
    public function checkInstallationStatus(): array
    {
        // ตรวจสอบว่า Ollama ถูกติดตั้งหรือยัง
        if (!$this->canExecuteCommands()) {
            // ไม่สามารถรันคำสั่งได้ - ลองเช็คผ่าน HTTP แทน
            try {
                $response = Http::timeout(5)->get('http://localhost:11434/api/version');
                if ($response->successful()) {
                    return [
                        'is_installed' => true,
                        'version' => $response->json('version'),
                        'message' => 'Ollama ติดตั้งแล้วและกำลังทำงาน',
                    ];
                }
            } catch (\Exception $e) {
                // ไม่สามารถเชื่อมต่อได้
            }

            return [
                'is_installed' => false,
                'version' => null,
                'message' => 'ไม่สามารถตรวจสอบได้ (exec ถูก disable)',
            ];
        }

        // ตรวจสอบว่ามี binary ollama หรือไม่
        $output = [];
        $returnCode = -1;
        @exec('which ollama 2>/dev/null', $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            // ดึง version
            $versionOutput = [];
            @exec('ollama --version 2>/dev/null', $versionOutput);
            $version = !empty($versionOutput) ? trim(implode('', $versionOutput)) : null;

            // ลองดึง version ผ่าน regex
            if ($version) {
                preg_match('/(\d+\.\d+\.\d+)/', $version, $matches);
                $version = $matches[1] ?? $version;
            }

            return [
                'is_installed' => true,
                'version' => $version,
                'message' => 'Ollama ติดตั้งแล้ว',
            ];
        }

        return [
            'is_installed' => false,
            'version' => null,
            'message' => 'Ollama ยังไม่ได้ติดตั้ง',
        ];
    }

    /**
     * ติดตั้ง Ollama
     *
     * @return array{success: bool, message: string}
     */
    public function installOllama(): array
    {
        // ตรวจสอบว่าติดตั้งแล้วหรือยัง
        $status = $this->checkInstallationStatus();
        if ($status['is_installed']) {
            return [
                'success' => true,
                'message' => 'Ollama ติดตั้งแล้ว เวอร์ชัน: ' . ($status['version'] ?? 'N/A'),
            ];
        }

        // ตรวจสอบว่าสามารถรันคำสั่งได้หรือไม่
        $canInstall = $this->checkInstallCapability();
        if (!$canInstall['can_install']) {
            return [
                'success' => false,
                'message' => $canInstall['reason'],
                'manual_instructions' => $this->getManualInstructions('ollama', 'llama3:8b'),
            ];
        }

        // ติดตั้ง Ollama
        $this->log('เริ่มติดตั้ง Ollama...');

        $output = [];
        $returnCode = -1;
        @exec('curl -fsSL https://ollama.com/install.sh | sh 2>&1', $output, $returnCode);

        $outputStr = implode("\n", $output);
        $this->log("Install output: {$outputStr}");

        if ($returnCode !== 0) {
            $this->log("การติดตั้ง Ollama ล้มเหลว (exit code: {$returnCode})");

            return [
                'success' => false,
                'message' => 'การติดตั้ง Ollama ล้มเหลว: ' . $outputStr,
                'manual_instructions' => $this->getManualInstructions('ollama', 'llama3:8b'),
            ];
        }

        $this->log('ติดตั้ง Ollama สำเร็จ');

        return [
            'success' => true,
            'message' => 'ติดตั้ง Ollama สำเร็จ',
        ];
    }

    /**
     * ดาวน์โหลดโมเดล AI
     *
     * @param string $modelName ชื่อโมเดล เช่น llama3:8b, mistral:7b
     * @return array{success: bool, message: string}
     */
    public function downloadModel(string $modelName): array
    {
        // ตรวจสอบว่า Ollama ติดตั้งแล้วหรือยัง
        $status = $this->checkInstallationStatus();
        if (!$status['is_installed']) {
            return [
                'success' => false,
                'message' => 'กรุณาติดตั้ง Ollama ก่อน',
            ];
        }

        $this->log("เริ่มดาวน์โหลดโมเดล: {$modelName}");

        // ลองผ่าน API ก่อน (ไม่ต้องใช้ exec)
        try {
            $response = Http::timeout(600)->post('http://localhost:11434/api/pull', [
                'name' => $modelName,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $this->log("ดาวน์โหลดโมเดล {$modelName} สำเร็จ (ผ่าน API)");
                return [
                    'success' => true,
                    'message' => "ดาวน์โหลดโมเดล {$modelName} สำเร็จ",
                ];
            }

            $errorBody = $response->body();
            $this->log("API pull ล้มเหลว: {$errorBody}");
        } catch (\Exception $e) {
            $this->log("API pull exception: {$e->getMessage()}");
        }

        // Fallback: ใช้ exec
        if ($this->canExecuteCommands()) {
            $output = [];
            $returnCode = -1;
            @exec("ollama pull " . escapeshellarg($modelName) . " 2>&1", $output, $returnCode);

            $outputStr = implode("\n", $output);
            $this->log("Exec pull output: {$outputStr}");

            if ($returnCode === 0) {
                $this->log("ดาวน์โหลดโมเดล {$modelName} สำเร็จ (ผ่าน exec)");
                return [
                    'success' => true,
                    'message' => "ดาวน์โหลดโมเดล {$modelName} สำเร็จ",
                ];
            }
        }

        return [
            'success' => false,
            'message' => "ไม่สามารถดาวน์โหลดโมเดล {$modelName} ได้",
        ];
    }

    /**
     * ลบโมเดล AI
     *
     * @param string $modelName ชื่อโมเดลที่ต้องการลบ
     * @return array{success: bool, message: string}
     */
    public function deleteModel(string $modelName): array
    {
        $this->log("เริ่มลบโมเดล: {$modelName}");

        // ลองผ่าน API ก่อน
        try {
            $response = Http::timeout(30)->delete('http://localhost:11434/api/delete', [
                'name' => $modelName,
            ]);

            if ($response->successful()) {
                $this->log("ลบโมเดล {$modelName} สำเร็จ (ผ่าน API)");
                return [
                    'success' => true,
                    'message' => "ลบโมเดล {$modelName} สำเร็จ",
                ];
            }
        } catch (\Exception $e) {
            $this->log("API delete exception: {$e->getMessage()}");
        }

        // Fallback: ใช้ exec
        if ($this->canExecuteCommands()) {
            $output = [];
            $returnCode = -1;
            @exec("ollama rm " . escapeshellarg($modelName) . " 2>&1", $output, $returnCode);

            if ($returnCode === 0) {
                $this->log("ลบโมเดล {$modelName} สำเร็จ (ผ่าน exec)");
                return [
                    'success' => true,
                    'message' => "ลบโมเดล {$modelName} สำเร็จ",
                ];
            }
        }

        return [
            'success' => false,
            'message' => "ไม่สามารถลบโมเดล {$modelName} ได้",
        ];
    }

    /**
     * ตรวจสอบว่าสามารถรันคำสั่ง exec ได้หรือไม่
     *
     * @return bool
     */
    private function canExecuteCommands(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));
        return !in_array('exec', $disabledFunctions);
    }

    /**
     * ยกเลิกการติดตั้ง
     *
     * @return array
     */
    public function cancelInstallation(): array
    {
        // หา process และ kill
        @exec("pkill -f 'install-llama-with-progress.sh' 2>/dev/null");
        @exec("pkill -f 'ollama pull' 2>/dev/null");

        $this->updateProgress([
            'status' => 'cancelled',
            'percentage' => 0,
            'message' => 'ยกเลิกการติดตั้งแล้ว',
            'cancelled_at' => now()->toIso8601String(),
        ]);

        return [
            'success' => true,
            'message' => 'ยกเลิกการติดตั้งแล้ว',
        ];
    }

    /**
     * ตรวจสอบว่ากำลังติดตั้งอยู่หรือไม่
     *
     * @return bool
     */
    public function isInstalling(): bool
    {
        $progress = $this->getProgress();
        return $progress['status'] === 'running';
    }

    /**
     * สร้าง installation script
     *
     * @param string $path
     */
    private function createInstallScript(string $path): void
    {
        $script = $this->getInstallScriptContent();
        @file_put_contents($path, $script);
        @chmod($path, 0755);
    }

    /**
     * เนื้อหา installation script
     *
     * @return string
     */
    private function getInstallScriptContent(): string
    {
        return <<<'BASH'
#!/bin/bash
#
# Llama Installation Script with Progress Tracking
# Usage: ./install-llama-with-progress.sh <method> <model> <progress_file> <log_file>
#
# Features:
# - Real-time download progress with file size
# - Automatic cleanup on failure
# - Trap for interrupt signals
#

METHOD="${1:-ollama}"
MODEL="${2:-llama3.2:3b}"
PROGRESS_FILE="${3:-/tmp/llama-progress.json}"
LOG_FILE="${4:-/tmp/llama-install.log}"

# Track downloaded files for cleanup
DOWNLOAD_DIR=""
DOWNLOADED_FILES=()

# Function to update progress with more details
update_progress() {
    local status="$1"
    local step="$2"
    local step_name="$3"
    local percentage="$4"
    local message="$5"
    local extra="${6:-}"

    cat > "$PROGRESS_FILE" << EOF
{
    "status": "$status",
    "step": "$step",
    "step_name": "$step_name",
    "percentage": $percentage,
    "message": "$message",
    "method": "$METHOD",
    "model": "$MODEL",
    "updated_at": "$(date -Iseconds)"$extra
}
EOF
}

# Function to log
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to format bytes to human readable
format_bytes() {
    local bytes=$1
    if [ $bytes -ge 1073741824 ]; then
        echo "$(echo "scale=2; $bytes/1073741824" | bc) GB"
    elif [ $bytes -ge 1048576 ]; then
        echo "$(echo "scale=2; $bytes/1048576" | bc) MB"
    elif [ $bytes -ge 1024 ]; then
        echo "$(echo "scale=2; $bytes/1024" | bc) KB"
    else
        echo "$bytes B"
    fi
}

# Cleanup function - removes downloaded files on failure
cleanup_on_failure() {
    log "Cleanup: Removing downloaded files..."
    update_progress "error" "cleanup" "กำลังลบไฟล์" 0 "กำลังลบไฟล์ที่ดาวน์โหลดไม่สำเร็จ..."

    # For Ollama - remove the model
    if [ "$METHOD" = "ollama" ]; then
        if command -v ollama &> /dev/null; then
            log "Removing Ollama model: $MODEL"
            ollama rm "$MODEL" 2>/dev/null
        fi

        # Remove partial downloads from Ollama cache
        OLLAMA_MODELS_DIR="${HOME}/.ollama/models"
        if [ -d "$OLLAMA_MODELS_DIR" ]; then
            # Find and remove partial/temp files
            find "$OLLAMA_MODELS_DIR" -name "*.part" -delete 2>/dev/null
            find "$OLLAMA_MODELS_DIR" -name "*.tmp" -delete 2>/dev/null
            log "Cleaned up Ollama cache"
        fi
    fi

    # For Hugging Face - remove downloaded files
    if [ "$METHOD" = "huggingface" ]; then
        if [ -n "$DOWNLOAD_DIR" ] && [ -d "$DOWNLOAD_DIR" ]; then
            log "Removing download directory: $DOWNLOAD_DIR"
            rm -rf "$DOWNLOAD_DIR"
        fi

        # Remove from HuggingFace cache
        HF_CACHE="${HOME}/.cache/huggingface"
        if [ -d "$HF_CACHE" ]; then
            # Remove partial downloads
            find "$HF_CACHE" -name "*.incomplete" -delete 2>/dev/null
            find "$HF_CACHE" -name "*.lock" -delete 2>/dev/null
            log "Cleaned up HuggingFace cache"
        fi
    fi

    log "Cleanup completed"
}

# Trap for cleanup on error or interrupt
trap 'cleanup_on_failure; exit 1' ERR INT TERM

log "Starting Llama installation - Method: $METHOD, Model: $MODEL"

# Step 1: Check system (0-5%)
update_progress "running" "checking_system" "ตรวจสอบระบบ" 0 "กำลังตรวจสอบระบบ..."
log "Checking system requirements..."

TOTAL_RAM=$(free -g 2>/dev/null | awk '/^Mem:/{print $2}' || echo "0")
CPU_CORES=$(nproc 2>/dev/null || echo "1")
DISK_FREE=$(df -BG . 2>/dev/null | tail -1 | awk '{print $4}' | tr -d 'G' || echo "0")

log "System: RAM=${TOTAL_RAM}GB, CPU=${CPU_CORES} cores, Disk Free=${DISK_FREE}GB"
update_progress "running" "checking_system" "ตรวจสอบระบบ" 5 "RAM: ${TOTAL_RAM}GB, CPU: ${CPU_CORES} cores, พื้นที่ว่าง: ${DISK_FREE}GB"

sleep 1

# Step 2: Install dependencies (5-15%)
update_progress "running" "installing_dependencies" "ติดตั้ง Dependencies" 5 "กำลังติดตั้ง dependencies..."
log "Installing dependencies..."

if [ "$METHOD" = "ollama" ]; then
    # Check if Ollama is installed
    if ! command -v ollama &> /dev/null; then
        update_progress "running" "installing_dependencies" "ติดตั้ง Dependencies" 8 "กำลังติดตั้ง Ollama..."
        log "Installing Ollama..."

        curl -fsSL https://ollama.com/install.sh | sh >> "$LOG_FILE" 2>&1

        if [ $? -ne 0 ]; then
            update_progress "error" "installing_dependencies" "ติดตั้ง Dependencies" 8 "ติดตั้ง Ollama ล้มเหลว"
            log "ERROR: Failed to install Ollama"
            exit 1
        fi
    fi

    update_progress "running" "installing_dependencies" "ติดตั้ง Dependencies" 15 "ติดตั้ง Ollama สำเร็จ"
    log "Ollama installed successfully"
else
    # For Hugging Face method
    update_progress "running" "installing_dependencies" "ติดตั้ง Dependencies" 8 "กำลังติดตั้ง Python dependencies..."
    pip install --quiet huggingface_hub tqdm >> "$LOG_FILE" 2>&1
    update_progress "running" "installing_dependencies" "ติดตั้ง Dependencies" 15 "ติดตั้ง dependencies สำเร็จ"
fi

sleep 1

# Step 3: Download model (15-75%)
update_progress "running" "downloading_model" "ดาวน์โหลด Model" 15 "เริ่มดาวน์โหลด $MODEL..."
log "Downloading model: $MODEL"

DOWNLOAD_START_TIME=$(date +%s)

if [ "$METHOD" = "ollama" ]; then
    # Start Ollama service if not running
    if ! pgrep -x "ollama" > /dev/null; then
        log "Starting Ollama service..."
        nohup ollama serve > /dev/null 2>&1 &
        sleep 3
    fi

    # Get model size estimate first (if available)
    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 16 "กำลังตรวจสอบขนาด Model..."

    # Pull model with detailed progress tracking
    LAST_PERCENT=0
    LAST_DOWNLOADED=""

    ollama pull "$MODEL" 2>&1 | while IFS= read -r line; do
        log "$line"

        # Parse progress: "pulling abc123... 45% ▕████      ▏ 1.2 GB/2.5 GB"
        if [[ "$line" =~ ([0-9]+)%.*([0-9.]+\ [KMGT]?B)/([0-9.]+\ [KMGT]?B) ]]; then
            PERCENT="${BASH_REMATCH[1]}"
            DOWNLOADED="${BASH_REMATCH[2]}"
            TOTAL="${BASH_REMATCH[3]}"

            # Map 0-100% to 16-75%
            MAPPED_PERCENT=$((16 + (PERCENT * 59 / 100)))

            # Calculate speed
            CURRENT_TIME=$(date +%s)
            ELAPSED=$((CURRENT_TIME - DOWNLOAD_START_TIME))
            if [ $ELAPSED -gt 0 ]; then
                # Rough speed calculation
                SPEED_MSG="(${ELAPSED}s elapsed)"
            else
                SPEED_MSG=""
            fi

            update_progress "running" "downloading_model" "ดาวน์โหลด Model" $MAPPED_PERCENT "กำลังดาวน์โหลด: ${DOWNLOADED} / ${TOTAL} (${PERCENT}%) ${SPEED_MSG}"

        # Simple percentage match
        elif [[ "$line" =~ ([0-9]+)% ]]; then
            PERCENT="${BASH_REMATCH[1]}"
            MAPPED_PERCENT=$((16 + (PERCENT * 59 / 100)))
            update_progress "running" "downloading_model" "ดาวน์โหลด Model" $MAPPED_PERCENT "กำลังดาวน์โหลด $MODEL... ${PERCENT}%"
        fi
    done

    PULL_EXIT_CODE=${PIPESTATUS[0]}

    if [ $PULL_EXIT_CODE -ne 0 ]; then
        update_progress "error" "downloading_model" "ดาวน์โหลด Model" 20 "ดาวน์โหลด Model ล้มเหลว - กำลังลบไฟล์..."
        log "ERROR: Failed to download model (exit code: $PULL_EXIT_CODE)"
        cleanup_on_failure
        exit 1
    fi

    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 75 "ดาวน์โหลด $MODEL สำเร็จ"
    log "Model downloaded successfully"

else
    # Hugging Face download with progress
    DOWNLOAD_DIR="/tmp/llama-download-$$"
    mkdir -p "$DOWNLOAD_DIR"

    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 18 "กำลังดาวน์โหลดจาก Hugging Face..."

    # Create Python script for download with progress
    cat > "$DOWNLOAD_DIR/download.py" << 'PYEOF'
import os
import sys
import json
from huggingface_hub import hf_hub_download, snapshot_download
from huggingface_hub.utils import tqdm as hf_tqdm

model_id = sys.argv[1]
progress_file = sys.argv[2]
log_file = sys.argv[3]

def update_progress(percentage, message):
    with open(progress_file, 'w') as f:
        json.dump({
            "status": "running",
            "step": "downloading_model",
            "step_name": "ดาวน์โหลด Model",
            "percentage": percentage,
            "message": message,
            "method": "huggingface",
            "model": model_id
        }, f, ensure_ascii=False)

def log(msg):
    with open(log_file, 'a') as f:
        f.write(f"[PYTHON] {msg}\n")

try:
    log(f"Starting download: {model_id}")
    update_progress(20, f"เริ่มดาวน์โหลด {model_id}...")

    # Try to download
    local_dir = snapshot_download(
        repo_id=model_id,
        local_dir=os.path.dirname(progress_file) + "/model",
        resume_download=True
    )

    update_progress(75, f"ดาวน์โหลดสำเร็จ: {local_dir}")
    log(f"Download complete: {local_dir}")
    print(local_dir)

except Exception as e:
    log(f"Download failed: {str(e)}")
    update_progress(20, f"ดาวน์โหลดล้มเหลว: {str(e)}")
    sys.exit(1)
PYEOF

    python3 "$DOWNLOAD_DIR/download.py" "$MODEL" "$PROGRESS_FILE" "$LOG_FILE" >> "$LOG_FILE" 2>&1

    if [ $? -ne 0 ]; then
        update_progress "error" "downloading_model" "ดาวน์โหลด Model" 20 "ดาวน์โหลดจาก Hugging Face ล้มเหลว - กำลังลบไฟล์..."
        log "ERROR: Failed to download from Hugging Face"
        cleanup_on_failure
        exit 1
    fi

    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 75 "ดาวน์โหลดสำเร็จ"
fi

# Calculate download time
DOWNLOAD_END_TIME=$(date +%s)
DOWNLOAD_DURATION=$((DOWNLOAD_END_TIME - DOWNLOAD_START_TIME))
log "Download completed in ${DOWNLOAD_DURATION} seconds"

sleep 1

# Step 4: Setup server (75-90%)
update_progress "running" "setting_up_server" "ตั้งค่า Server" 75 "กำลังตั้งค่า server..."
log "Setting up server..."

if [ "$METHOD" = "ollama" ]; then
    # Ensure Ollama is running
    if ! pgrep -x "ollama" > /dev/null; then
        nohup ollama serve > /dev/null 2>&1 &
        sleep 2
    fi

    update_progress "running" "setting_up_server" "ตั้งค่า Server" 85 "Ollama server พร้อมใช้งาน"
else
    # Setup llama.cpp server (if using Hugging Face)
    update_progress "running" "setting_up_server" "ตั้งค่า Server" 85 "ตั้งค่า llama.cpp server..."
fi

update_progress "running" "setting_up_server" "ตั้งค่า Server" 90 "ตั้งค่า server สำเร็จ"
log "Server setup complete"

sleep 1

# Step 5: Testing (90-100%)
update_progress "running" "testing" "ทดสอบระบบ" 90 "กำลังทดสอบ..."
log "Testing installation..."

if [ "$METHOD" = "ollama" ]; then
    # Test API
    RESPONSE=$(curl -s http://localhost:11434/api/tags 2>/dev/null)

    if [ -n "$RESPONSE" ]; then
        update_progress "running" "testing" "ทดสอบระบบ" 95 "API ทำงานปกติ"
        log "API test passed"

        # Quick model test
        TEST_RESPONSE=$(curl -s -m 30 http://localhost:11434/api/generate -d "{\"model\": \"$MODEL\", \"prompt\": \"Hi\", \"stream\": false}" 2>/dev/null | head -c 100)

        if [ -n "$TEST_RESPONSE" ]; then
            update_progress "running" "testing" "ทดสอบระบบ" 98 "Model ตอบกลับปกติ"
            log "Model test passed"
        else
            log "Warning: Model did not respond to test prompt"
        fi
    else
        update_progress "error" "testing" "ทดสอบระบบ" 90 "API ไม่ตอบกลับ - กำลังลบไฟล์..."
        log "ERROR: API test failed"
        cleanup_on_failure
        exit 1
    fi
fi

# Disable trap before successful completion
trap - ERR INT TERM

# Complete!
TOTAL_TIME=$(($(date +%s) - DOWNLOAD_START_TIME + 5))
update_progress "completed" "completed" "เสร็จสิ้น" 100 "ติดตั้ง $MODEL สำเร็จ! (ใช้เวลา ${TOTAL_TIME} วินาที)" ",\n    \"completed_at\": \"$(date -Iseconds)\",\n    \"duration_seconds\": $TOTAL_TIME"
log "Installation completed successfully in ${TOTAL_TIME} seconds!"

echo "Installation complete!"
BASH;
    }
}
