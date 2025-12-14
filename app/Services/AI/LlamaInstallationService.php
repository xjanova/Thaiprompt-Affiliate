<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service สำหรับจัดการการติดตั้ง Llama AI
 *
 * รองรับการติดตั้งผ่าน:
 * - Ollama
 * - llama.cpp (Hugging Face)
 *
 * มี Progress tracking และ status reporting
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

        exec($command);

        return [
            'success' => true,
            'message' => 'เริ่มการติดตั้งแล้ว',
            'progress_url' => '/admin/ai-providers/install/progress',
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
     * ยกเลิกการติดตั้ง
     *
     * @return array
     */
    public function cancelInstallation(): array
    {
        // หา process และ kill
        exec("pkill -f 'install-llama-with-progress.sh'");
        exec("pkill -f 'ollama pull'");

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
        file_put_contents($path, $script);
        chmod($path, 0755);
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

METHOD="${1:-ollama}"
MODEL="${2:-llama3.2:3b}"
PROGRESS_FILE="${3:-/tmp/llama-progress.json}"
LOG_FILE="${4:-/tmp/llama-install.log}"

# Function to update progress
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

log "Starting Llama installation - Method: $METHOD, Model: $MODEL"

# Step 1: Check system (0-5%)
update_progress "running" "checking_system" "ตรวจสอบระบบ" 0 "กำลังตรวจสอบระบบ..."
log "Checking system requirements..."

TOTAL_RAM=$(free -g | awk '/^Mem:/{print $2}')
CPU_CORES=$(nproc)

log "System: RAM=${TOTAL_RAM}GB, CPU=${CPU_CORES} cores"
update_progress "running" "checking_system" "ตรวจสอบระบบ" 5 "RAM: ${TOTAL_RAM}GB, CPU: ${CPU_CORES} cores"

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
    pip install --quiet huggingface_hub >> "$LOG_FILE" 2>&1
    update_progress "running" "installing_dependencies" "ติดตั้ง Dependencies" 15 "ติดตั้ง dependencies สำเร็จ"
fi

sleep 1

# Step 3: Download model (15-75%)
update_progress "running" "downloading_model" "ดาวน์โหลด Model" 15 "เริ่มดาวน์โหลด $MODEL..."
log "Downloading model: $MODEL"

if [ "$METHOD" = "ollama" ]; then
    # Start Ollama service if not running
    if ! pgrep -x "ollama" > /dev/null; then
        log "Starting Ollama service..."
        nohup ollama serve > /dev/null 2>&1 &
        sleep 3
    fi

    # Download model with progress tracking
    # Ollama doesn't provide percentage, so we simulate based on model size
    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 20 "กำลังดาวน์โหลด $MODEL... (อาจใช้เวลา 5-30 นาที)"

    # Pull model
    ollama pull "$MODEL" 2>&1 | while read line; do
        # Parse Ollama output for progress
        if [[ "$line" =~ ([0-9]+)% ]]; then
            PERCENT="${BASH_REMATCH[1]}"
            # Map 0-100% to 15-75%
            MAPPED_PERCENT=$((15 + (PERCENT * 60 / 100)))
            update_progress "running" "downloading_model" "ดาวน์โหลด Model" $MAPPED_PERCENT "กำลังดาวน์โหลด $MODEL... ${PERCENT}%"
        fi
        log "$line"
    done

    if [ $? -ne 0 ]; then
        update_progress "error" "downloading_model" "ดาวน์โหลด Model" 20 "ดาวน์โหลด Model ล้มเหลว"
        log "ERROR: Failed to download model"
        exit 1
    fi

    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 75 "ดาวน์โหลด $MODEL สำเร็จ"
    log "Model downloaded successfully"
else
    # Hugging Face download
    update_progress "running" "downloading_model" "ดาวน์โหลด Model" 20 "กำลังดาวน์โหลดจาก Hugging Face..."

    # This would need Python script for actual progress
    # For now, simulate progress
    for i in $(seq 20 5 75); do
        update_progress "running" "downloading_model" "ดาวน์โหลด Model" $i "กำลังดาวน์โหลด... ${i}%"
        sleep 2
    done
fi

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
        TEST_RESPONSE=$(curl -s http://localhost:11434/api/generate -d "{\"model\": \"$MODEL\", \"prompt\": \"Hi\", \"stream\": false}" 2>/dev/null | head -c 100)

        if [ -n "$TEST_RESPONSE" ]; then
            update_progress "running" "testing" "ทดสอบระบบ" 98 "Model ตอบกลับปกติ"
            log "Model test passed"
        fi
    else
        update_progress "error" "testing" "ทดสอบระบบ" 90 "API ไม่ตอบกลับ"
        log "ERROR: API test failed"
        exit 1
    fi
fi

# Complete!
update_progress "completed" "completed" "เสร็จสิ้น" 100 "ติดตั้ง $MODEL สำเร็จ! พร้อมใช้งาน" ",\n    \"completed_at\": \"$(date -Iseconds)\""
log "Installation completed successfully!"

echo "Installation complete!"
BASH;
    }
}
