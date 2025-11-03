<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocalAiManager
{
    private string $serviceName = 'ollama';
    private string $endpoint = 'http://localhost:11434';

    /**
     * เริ่มต้น Ollama service
     */
    public function start(): array
    {
        try {
            // ตรวจสอบว่ารันอยู่แล้วหรือไม่
            if ($this->isRunning()) {
                return [
                    'success' => true,
                    'message' => 'Ollama กำลังทำงานอยู่แล้ว',
                    'already_running' => true,
                ];
            }

            // พยายามเริ่ม service ด้วยวิธีต่างๆ
            $result = $this->tryStartService();

            if ($result['success']) {
                // รอให้ service พร้อม (สูงสุด 10 วินาที)
                $ready = $this->waitUntilReady(10);

                if ($ready) {
                    Cache::put('ollama_status', 'running', 300);

                    return [
                        'success' => true,
                        'message' => 'เริ่ม Ollama สำเร็จ',
                        'endpoint' => $this->endpoint,
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'เริ่ม Ollama แล้วแต่ไม่สามารถเชื่อมต่อได้',
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to start Ollama', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * หยุด Ollama service
     */
    public function stop(): array
    {
        try {
            if (!$this->isRunning()) {
                return [
                    'success' => true,
                    'message' => 'Ollama ไม่ได้ทำงานอยู่',
                    'already_stopped' => true,
                ];
            }

            // หยุด service
            $result = $this->tryStopService();

            if ($result['success']) {
                Cache::forget('ollama_status');

                return [
                    'success' => true,
                    'message' => 'หยุด Ollama สำเร็จ',
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to stop Ollama', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Restart Ollama service
     */
    public function restart(): array
    {
        $stopResult = $this->stop();

        if (!$stopResult['success'] && !($stopResult['already_stopped'] ?? false)) {
            return $stopResult;
        }

        sleep(2); // รอให้หยุดสมบูรณ์

        return $this->start();
    }

    /**
     * ตรวจสอบสถานะ
     */
    public function getStatus(): array
    {
        $isRunning = $this->isRunning();
        $processInfo = $this->getProcessInfo();
        $resourceUsage = $isRunning ? $this->getResourceUsage() : null;

        return [
            'running' => $isRunning,
            'endpoint' => $this->endpoint,
            'process' => $processInfo,
            'resource_usage' => $resourceUsage,
            'uptime' => $isRunning ? $this->getUptime() : null,
        ];
    }

    /**
     * ตรวจสอบว่า Ollama ทำงานอยู่หรือไม่
     */
    public function isRunning(): bool
    {
        // ตรวจสอบจาก cache ก่อน
        if (Cache::has('ollama_status')) {
            return Cache::get('ollama_status') === 'running';
        }

        // ตรวจสอบด้วยการ ping API
        try {
            $result = Process::timeout(5)->run("curl -s -o /dev/null -w '%{http_code}' {$this->endpoint}/api/tags");

            if ($result->successful()) {
                $httpCode = trim($result->output());
                $running = $httpCode === '200';

                if ($running) {
                    Cache::put('ollama_status', 'running', 60);
                }

                return $running;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return false;
    }

    /**
     * ดึงข้อมูล process
     */
    public function getProcessInfo(): ?array
    {
        try {
            $result = Process::run('pgrep -a ollama');

            if ($result->successful() && !empty(trim($result->output()))) {
                $lines = explode("\n", trim($result->output()));
                $processes = [];

                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;

                    $parts = explode(' ', trim($line), 2);
                    $processes[] = [
                        'pid' => $parts[0],
                        'command' => $parts[1] ?? 'ollama',
                    ];
                }

                return [
                    'found' => true,
                    'count' => count($processes),
                    'processes' => $processes,
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return [
            'found' => false,
            'count' => 0,
            'processes' => [],
        ];
    }

    /**
     * ดึงข้อมูลการใช้ทรัพยากร
     */
    public function getResourceUsage(): array
    {
        $processInfo = $this->getProcessInfo();

        if (!$processInfo['found']) {
            return [
                'cpu_percent' => 0,
                'memory_mb' => 0,
                'memory_percent' => 0,
            ];
        }

        $pids = array_column($processInfo['processes'], 'pid');
        $totalCpu = 0;
        $totalMem = 0;

        foreach ($pids as $pid) {
            $stats = $this->getProcessStats($pid);
            $totalCpu += $stats['cpu_percent'];
            $totalMem += $stats['memory_mb'];
        }

        // ดึงข้อมูล GPU ถ้ามี
        $gpuUsage = $this->getGpuUsage();

        return [
            'cpu_percent' => round($totalCpu, 2),
            'memory_mb' => round($totalMem, 2),
            'memory_gb' => round($totalMem / 1024, 2),
            'memory_percent' => $this->calculateMemoryPercent($totalMem),
            'gpu' => $gpuUsage,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * ดึงสถิติของ process
     */
    private function getProcessStats(string $pid): array
    {
        try {
            // ใช้ ps command เพื่อดึงข้อมูล CPU และ Memory
            $result = Process::run("ps -p {$pid} -o %cpu,%mem,rss --no-headers");

            if ($result->successful()) {
                $output = trim($result->output());
                $parts = preg_split('/\s+/', $output);

                if (count($parts) >= 3) {
                    return [
                        'cpu_percent' => (float) $parts[0],
                        'memory_percent' => (float) $parts[1],
                        'memory_kb' => (int) $parts[2],
                        'memory_mb' => round((int) $parts[2] / 1024, 2),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return [
            'cpu_percent' => 0,
            'memory_percent' => 0,
            'memory_kb' => 0,
            'memory_mb' => 0,
        ];
    }

    /**
     * คำนวณ memory percent จากระบบทั้งหมด
     */
    private function calculateMemoryPercent(float $memoryMb): float
    {
        try {
            $result = Process::run('free -m | grep Mem:');

            if ($result->successful()) {
                $line = trim($result->output());
                $parts = preg_split('/\s+/', $line);

                if (count($parts) >= 2) {
                    $totalMemMb = (int) $parts[1];
                    return round(($memoryMb / $totalMemMb) * 100, 2);
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 0;
    }

    /**
     * ดึงข้อมูล GPU usage
     */
    public function getGpuUsage(): ?array
    {
        try {
            // ตรวจสอบว่ามี nvidia-smi หรือไม่
            $result = Process::run('which nvidia-smi');

            if (!$result->successful()) {
                return null;
            }

            // ดึงข้อมูล GPU
            $result = Process::run('nvidia-smi --query-gpu=index,name,utilization.gpu,utilization.memory,memory.used,memory.total,temperature.gpu --format=csv,noheader,nounits');

            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));
                $gpus = [];

                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;

                    $parts = array_map('trim', explode(',', $line));

                    if (count($parts) >= 7) {
                        $gpus[] = [
                            'index' => (int) $parts[0],
                            'name' => $parts[1],
                            'utilization_percent' => (int) $parts[2],
                            'memory_utilization_percent' => (int) $parts[3],
                            'memory_used_mb' => (int) $parts[4],
                            'memory_total_mb' => (int) $parts[5],
                            'memory_used_gb' => round((int) $parts[4] / 1024, 2),
                            'memory_total_gb' => round((int) $parts[5] / 1024, 2),
                            'temperature_c' => (int) $parts[6],
                        ];
                    }
                }

                return [
                    'available' => true,
                    'count' => count($gpus),
                    'gpus' => $gpus,
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * ดึง uptime
     */
    private function getUptime(): ?string
    {
        $processInfo = $this->getProcessInfo();

        if (!$processInfo['found']) {
            return null;
        }

        try {
            $pid = $processInfo['processes'][0]['pid'];
            $result = Process::run("ps -p {$pid} -o etime --no-headers");

            if ($result->successful()) {
                return trim($result->output());
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * พยายามเริ่ม service
     */
    private function tryStartService(): array
    {
        // วิธีที่ 1: systemctl (สำหรับ systemd)
        $result = Process::run('systemctl start ollama 2>&1');
        if ($result->successful()) {
            return ['success' => true, 'method' => 'systemctl'];
        }

        // วิธีที่ 2: service command
        $result = Process::run('service ollama start 2>&1');
        if ($result->successful()) {
            return ['success' => true, 'method' => 'service'];
        }

        // วิธีที่ 3: รัน ollama serve โดยตรงใน background
        $result = Process::run('nohup ollama serve > /tmp/ollama.log 2>&1 &');
        if ($result->successful()) {
            return ['success' => true, 'method' => 'direct'];
        }

        return [
            'success' => false,
            'message' => 'ไม่สามารถเริ่ม Ollama ได้ กรุณาเริ่มด้วยตนเอง',
        ];
    }

    /**
     * พยายามหยุด service
     */
    private function tryStopService(): array
    {
        // วิธีที่ 1: systemctl
        $result = Process::run('systemctl stop ollama 2>&1');
        if ($result->successful()) {
            return ['success' => true, 'method' => 'systemctl'];
        }

        // วิธีที่ 2: service command
        $result = Process::run('service ollama stop 2>&1');
        if ($result->successful()) {
            return ['success' => true, 'method' => 'service'];
        }

        // วิธีที่ 3: kill process โดยตรง
        $processInfo = $this->getProcessInfo();
        if ($processInfo['found']) {
            foreach ($processInfo['processes'] as $process) {
                Process::run("kill {$process['pid']}");
            }
            return ['success' => true, 'method' => 'kill'];
        }

        return [
            'success' => false,
            'message' => 'ไม่สามารถหยุด Ollama ได้',
        ];
    }

    /**
     * รอจนกว่า service จะพร้อม
     */
    private function waitUntilReady(int $maxSeconds = 10): bool
    {
        $startTime = time();

        while ((time() - $startTime) < $maxSeconds) {
            if ($this->isRunning()) {
                return true;
            }
            sleep(1);
        }

        return false;
    }

    /**
     * ดึงรายการโมเดลที่กำลังโหลดอยู่ใน memory
     */
    public function getLoadedModels(): array
    {
        if (!$this->isRunning()) {
            return [];
        }

        try {
            $result = Process::run("curl -s {$this->endpoint}/api/ps");

            if ($result->successful()) {
                $data = json_decode($result->output(), true);

                if (isset($data['models'])) {
                    return array_map(function ($model) {
                        return [
                            'name' => $model['name'] ?? 'unknown',
                            'size' => $model['size'] ?? 0,
                            'size_gb' => round(($model['size'] ?? 0) / (1024 ** 3), 2),
                            'digest' => $model['digest'] ?? null,
                            'expires_at' => $model['expires_at'] ?? null,
                        ];
                    }, $data['models']);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get loaded models', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * ทำให้โมเดลพร้อมใช้งาน (load เข้า memory)
     */
    public function loadModel(string $modelName): array
    {
        if (!$this->isRunning()) {
            return [
                'success' => false,
                'message' => 'Ollama ไม่ได้ทำงาน',
            ];
        }

        try {
            // ส่ง request เปล่าไปที่โมเดลเพื่อให้ load
            $result = Process::timeout(30)->run("curl -s -X POST {$this->endpoint}/api/generate -d '{\"model\":\"{$modelName}\",\"prompt\":\"\"}'");

            if ($result->successful()) {
                return [
                    'success' => true,
                    'message' => 'โหลดโมเดลเข้า memory แล้ว',
                ];
            }

            return [
                'success' => false,
                'message' => 'ไม่สามารถโหลดโมเดลได้',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงสถิติรวม
     */
    public function getSummaryStats(): array
    {
        $status = $this->getStatus();
        $loadedModels = $this->getLoadedModels();

        return [
            'status' => $status['running'] ? 'running' : 'stopped',
            'uptime' => $status['uptime'],
            'loaded_models_count' => count($loadedModels),
            'loaded_models' => $loadedModels,
            'resource_usage' => $status['resource_usage'],
            'endpoint' => $this->endpoint,
        ];
    }
}
