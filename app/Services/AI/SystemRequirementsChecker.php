<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Process;

class SystemRequirementsChecker
{
    /**
     * ตรวจสอบทรัพยากรระบบทั้งหมด
     */
    public function checkAll(): array
    {
        return [
            'cpu' => $this->checkCpu(),
            'ram' => $this->checkRam(),
            'gpu' => $this->checkGpu(),
            'disk' => $this->checkDisk(),
            'os' => $this->checkOs(),
            'python' => $this->checkPython(),
            'dependencies' => $this->checkDependencies(),
            'overall_status' => $this->getOverallStatus(),
        ];
    }

    /**
     * ตรวจสอบ CPU
     */
    public function checkCpu(): array
    {
        $cpuInfo = [
            'cores' => 0,
            'model' => 'Unknown',
            'architecture' => 'Unknown',
            'status' => 'unknown',
        ];

        try {
            // นับจำนวน CPU cores
            $result = Process::run('nproc');
            if ($result->successful()) {
                $cpuInfo['cores'] = (int) trim($result->output());
            }

            // ข้อมูล CPU model
            $result = Process::run('cat /proc/cpuinfo | grep "model name" | head -1 | cut -d ":" -f2');
            if ($result->successful()) {
                $cpuInfo['model'] = trim($result->output());
            }

            // Architecture
            $result = Process::run('uname -m');
            if ($result->successful()) {
                $cpuInfo['architecture'] = trim($result->output());
            }

            // กำหนด status ตามจำนวน cores
            if ($cpuInfo['cores'] >= 8) {
                $cpuInfo['status'] = 'excellent';
                $cpuInfo['message'] = 'CPU มีประสิทธิภาพสูง เหมาะสำหรับโมเดลขนาดใหญ่';
            } elseif ($cpuInfo['cores'] >= 4) {
                $cpuInfo['status'] = 'good';
                $cpuInfo['message'] = 'CPU เพียงพอสำหรับโมเดลขนาดกลาง';
            } else {
                $cpuInfo['status'] = 'warning';
                $cpuInfo['message'] = 'CPU อาจไม่เพียงพอสำหรับโมเดลขนาดใหญ่';
            }
        } catch (\Exception $e) {
            $cpuInfo['status'] = 'error';
            $cpuInfo['message'] = 'ไม่สามารถตรวจสอบ CPU ได้: ' . $e->getMessage();
        }

        return $cpuInfo;
    }

    /**
     * ตรวจสอบ RAM
     */
    public function checkRam(): array
    {
        $ramInfo = [
            'total_gb' => 0,
            'available_gb' => 0,
            'used_gb' => 0,
            'used_percent' => 0,
            'status' => 'unknown',
        ];

        try {
            // ใช้ free -b เพื่อดูข้อมูล RAM ในหน่วย bytes
            $result = Process::run('free -b | grep Mem:');
            if ($result->successful()) {
                $line = trim($result->output());
                $parts = preg_split('/\s+/', $line);

                if (count($parts) >= 7) {
                    $total = (int) $parts[1];
                    $used = (int) $parts[2];
                    $available = (int) $parts[6];

                    $ramInfo['total_gb'] = round($total / (1024 ** 3), 2);
                    $ramInfo['used_gb'] = round($used / (1024 ** 3), 2);
                    $ramInfo['available_gb'] = round($available / (1024 ** 3), 2);
                    $ramInfo['used_percent'] = round(($used / $total) * 100, 1);
                }
            }

            // กำหนด status ตามขนาด RAM ที่มี
            if ($ramInfo['available_gb'] >= 32) {
                $ramInfo['status'] = 'excellent';
                $ramInfo['message'] = 'RAM เพียงพอสำหรับโมเดลขนาดใหญ่ (13B+)';
                $ramInfo['recommended_models'] = ['13B', '30B', '70B'];
            } elseif ($ramInfo['available_gb'] >= 16) {
                $ramInfo['status'] = 'good';
                $ramInfo['message'] = 'RAM เพียงพอสำหรับโมเดลขนาดกลาง (7B-13B)';
                $ramInfo['recommended_models'] = ['7B', '13B'];
            } elseif ($ramInfo['available_gb'] >= 8) {
                $ramInfo['status'] = 'fair';
                $ramInfo['message'] = 'RAM เพียงพอสำหรับโมเดลขนาดเล็ก (3B-7B)';
                $ramInfo['recommended_models'] = ['3B', '7B'];
            } else {
                $ramInfo['status'] = 'insufficient';
                $ramInfo['message'] = 'RAM ไม่เพียงพอสำหรับการติดตั้ง AI โมเดล (ต้องการอย่างน้อย 8GB)';
                $ramInfo['recommended_models'] = [];
            }
        } catch (\Exception $e) {
            $ramInfo['status'] = 'error';
            $ramInfo['message'] = 'ไม่สามารถตรวจสอบ RAM ได้: ' . $e->getMessage();
        }

        return $ramInfo;
    }

    /**
     * ตรวจสอบ GPU
     */
    public function checkGpu(): array
    {
        $gpuInfo = [
            'available' => false,
            'type' => 'none',
            'count' => 0,
            'gpus' => [],
            'cuda_version' => null,
            'status' => 'none',
        ];

        try {
            // ตรวจสอบ NVIDIA GPU ด้วย nvidia-smi
            $result = Process::run('which nvidia-smi');
            if ($result->successful()) {
                $nvidiaSmiResult = Process::run('nvidia-smi --query-gpu=name,memory.total,memory.free,driver_version --format=csv,noheader,nounits');

                if ($nvidiaSmiResult->successful()) {
                    $gpuInfo['available'] = true;
                    $gpuInfo['type'] = 'nvidia';

                    $lines = explode("\n", trim($nvidiaSmiResult->output()));
                    $gpuInfo['count'] = count($lines);

                    foreach ($lines as $line) {
                        $parts = array_map('trim', explode(',', $line));
                        if (count($parts) >= 3) {
                            $gpuInfo['gpus'][] = [
                                'name' => $parts[0],
                                'memory_total_mb' => (int) $parts[1],
                                'memory_free_mb' => (int) $parts[2],
                                'memory_total_gb' => round($parts[1] / 1024, 2),
                                'memory_free_gb' => round($parts[2] / 1024, 2),
                                'driver_version' => $parts[3] ?? 'Unknown',
                            ];
                        }
                    }

                    // ตรวจสอบ CUDA version
                    $cudaResult = Process::run('nvcc --version | grep "release" | awk \'{print $5}\' | cut -d "," -f1');
                    if ($cudaResult->successful()) {
                        $gpuInfo['cuda_version'] = trim($cudaResult->output());
                    }

                    // กำหนด status ตาม VRAM
                    $totalVram = array_sum(array_column($gpuInfo['gpus'], 'memory_total_gb'));

                    if ($totalVram >= 24) {
                        $gpuInfo['status'] = 'excellent';
                        $gpuInfo['message'] = 'GPU มี VRAM เพียงพอสำหรับโมเดลขนาดใหญ่มาก (30B-70B)';
                        $gpuInfo['recommended_models'] = ['30B', '70B'];
                    } elseif ($totalVram >= 16) {
                        $gpuInfo['status'] = 'very_good';
                        $gpuInfo['message'] = 'GPU มี VRAM เพียงพอสำหรับโมเดลขนาดใหญ่ (13B-30B)';
                        $gpuInfo['recommended_models'] = ['13B', '30B'];
                    } elseif ($totalVram >= 8) {
                        $gpuInfo['status'] = 'good';
                        $gpuInfo['message'] = 'GPU มี VRAM เพียงพอสำหรับโมเดลขนาดกลาง (7B-13B)';
                        $gpuInfo['recommended_models'] = ['7B', '13B'];
                    } elseif ($totalVram >= 4) {
                        $gpuInfo['status'] = 'fair';
                        $gpuInfo['message'] = 'GPU มี VRAM เพียงพอสำหรับโมเดลขนาดเล็ก (3B-7B)';
                        $gpuInfo['recommended_models'] = ['3B', '7B'];
                    } else {
                        $gpuInfo['status'] = 'insufficient';
                        $gpuInfo['message'] = 'GPU มี VRAM ไม่เพียงพอ ควรใช้ CPU inference';
                        $gpuInfo['recommended_models'] = [];
                    }
                }
            }

            // ถ้าไม่มี NVIDIA ลองตรวจสอบ AMD GPU
            if (!$gpuInfo['available']) {
                $result = Process::run('lspci | grep -i "VGA.*AMD"');
                if ($result->successful() && !empty(trim($result->output()))) {
                    $gpuInfo['available'] = true;
                    $gpuInfo['type'] = 'amd';
                    $gpuInfo['status'] = 'warning';
                    $gpuInfo['message'] = 'ตรวจพบ AMD GPU แต่ยังไม่รองรับ ROCm ในระบบนี้ ควรใช้ CPU inference';
                }
            }

            // ถ้าไม่มี GPU เลย
            if (!$gpuInfo['available']) {
                $gpuInfo['status'] = 'none';
                $gpuInfo['message'] = 'ไม่พบ GPU จะใช้ CPU inference (ช้ากว่า GPU)';
                $gpuInfo['recommended_models'] = ['3B', '7B (quantized)'];
            }
        } catch (\Exception $e) {
            $gpuInfo['status'] = 'error';
            $gpuInfo['message'] = 'ไม่สามารถตรวจสอบ GPU ได้: ' . $e->getMessage();
        }

        return $gpuInfo;
    }

    /**
     * ตรวจสอบพื้นที่ disk
     */
    public function checkDisk(): array
    {
        $diskInfo = [
            'total_gb' => 0,
            'available_gb' => 0,
            'used_gb' => 0,
            'used_percent' => 0,
            'status' => 'unknown',
        ];

        try {
            $result = Process::run('df -B1 / | tail -1');
            if ($result->successful()) {
                $line = trim($result->output());
                $parts = preg_split('/\s+/', $line);

                if (count($parts) >= 5) {
                    $total = (int) $parts[1];
                    $used = (int) $parts[2];
                    $available = (int) $parts[3];

                    $diskInfo['total_gb'] = round($total / (1024 ** 3), 2);
                    $diskInfo['used_gb'] = round($used / (1024 ** 3), 2);
                    $diskInfo['available_gb'] = round($available / (1024 ** 3), 2);
                    $diskInfo['used_percent'] = (int) str_replace('%', '', $parts[4]);
                }
            }

            // กำหนด status ตามพื้นที่ว่าง
            if ($diskInfo['available_gb'] >= 100) {
                $diskInfo['status'] = 'excellent';
                $diskInfo['message'] = 'พื้นที่เพียงพอสำหรับโมเดลหลายตัว';
            } elseif ($diskInfo['available_gb'] >= 50) {
                $diskInfo['status'] = 'good';
                $diskInfo['message'] = 'พื้นที่เพียงพอสำหรับโมเดล 2-3 ตัว';
            } elseif ($diskInfo['available_gb'] >= 20) {
                $diskInfo['status'] = 'fair';
                $diskInfo['message'] = 'พื้นที่เพียงพอสำหรับโมเดล 1 ตัว';
            } else {
                $diskInfo['status'] = 'insufficient';
                $diskInfo['message'] = 'พื้นที่ไม่เพียงพอ (ต้องการอย่างน้อย 20GB)';
            }
        } catch (\Exception $e) {
            $diskInfo['status'] = 'error';
            $diskInfo['message'] = 'ไม่สามารถตรวจสอบ disk ได้: ' . $e->getMessage();
        }

        return $diskInfo;
    }

    /**
     * ตรวจสอบ OS
     */
    public function checkOs(): array
    {
        $osInfo = [
            'name' => 'Unknown',
            'version' => 'Unknown',
            'kernel' => 'Unknown',
            'status' => 'unknown',
        ];

        try {
            // OS name
            $result = Process::run('cat /etc/os-release | grep "PRETTY_NAME" | cut -d "=" -f2 | tr -d \'"\'');
            if ($result->successful()) {
                $osInfo['name'] = trim($result->output());
            }

            // Kernel version
            $result = Process::run('uname -r');
            if ($result->successful()) {
                $osInfo['kernel'] = trim($result->output());
            }

            $osInfo['status'] = 'supported';
            $osInfo['message'] = 'OS รองรับการติดตั้ง';
        } catch (\Exception $e) {
            $osInfo['status'] = 'error';
            $osInfo['message'] = 'ไม่สามารถตรวจสอบ OS ได้: ' . $e->getMessage();
        }

        return $osInfo;
    }

    /**
     * ตรวจสอบ Python
     */
    public function checkPython(): array
    {
        $pythonInfo = [
            'installed' => false,
            'version' => null,
            'pip_installed' => false,
            'pip_version' => null,
            'status' => 'not_installed',
        ];

        try {
            // ตรวจสอบ Python 3
            $result = Process::run('python3 --version');
            if ($result->successful()) {
                $pythonInfo['installed'] = true;
                $pythonInfo['version'] = trim(str_replace('Python ', '', $result->output()));
            }

            // ตรวจสอบ pip
            $result = Process::run('pip3 --version');
            if ($result->successful()) {
                $pythonInfo['pip_installed'] = true;
                $output = trim($result->output());
                if (preg_match('/pip ([\d.]+)/', $output, $matches)) {
                    $pythonInfo['pip_version'] = $matches[1];
                }
            }

            // กำหนด status
            if ($pythonInfo['installed'] && $pythonInfo['pip_installed']) {
                // ตรวจสอบ version
                $version = explode('.', $pythonInfo['version']);
                $major = (int) $version[0];
                $minor = (int) ($version[1] ?? 0);

                if ($major >= 3 && $minor >= 8) {
                    $pythonInfo['status'] = 'ready';
                    $pythonInfo['message'] = 'Python พร้อมใช้งาน';
                } else {
                    $pythonInfo['status'] = 'outdated';
                    $pythonInfo['message'] = 'Python เวอร์ชันเก่า ควรอัปเดตเป็น 3.8 ขึ้นไป';
                }
            } elseif ($pythonInfo['installed']) {
                $pythonInfo['status'] = 'missing_pip';
                $pythonInfo['message'] = 'มี Python แต่ไม่มี pip ต้องติดตั้ง pip';
            } else {
                $pythonInfo['status'] = 'not_installed';
                $pythonInfo['message'] = 'ต้องติดตั้ง Python 3.8+ และ pip';
            }
        } catch (\Exception $e) {
            $pythonInfo['status'] = 'error';
            $pythonInfo['message'] = 'ไม่สามารถตรวจสอบ Python ได้: ' . $e->getMessage();
        }

        return $pythonInfo;
    }

    /**
     * ตรวจสอบ dependencies ต่างๆ
     */
    public function checkDependencies(): array
    {
        $deps = [
            'git' => $this->checkCommand('git --version'),
            'curl' => $this->checkCommand('curl --version'),
            'docker' => $this->checkCommand('docker --version'),
        ];

        return $deps;
    }

    /**
     * ตรวจสอบ command
     */
    private function checkCommand(string $command): array
    {
        try {
            $result = Process::run($command);
            if ($result->successful()) {
                return [
                    'installed' => true,
                    'version' => trim(explode("\n", $result->output())[0]),
                    'status' => 'available',
                ];
            }
        } catch (\Exception $e) {
            // Command not found
        }

        return [
            'installed' => false,
            'version' => null,
            'status' => 'not_installed',
        ];
    }

    /**
     * สรุปสถานะโดยรวม
     */
    public function getOverallStatus(): array
    {
        $cpu = $this->checkCpu();
        $ram = $this->checkRam();
        $gpu = $this->checkGpu();
        $disk = $this->checkDisk();
        $python = $this->checkPython();

        $ready = true;
        $warnings = [];
        $errors = [];

        // ตรวจสอบแต่ละส่วน
        if ($ram['status'] === 'insufficient') {
            $ready = false;
            $errors[] = 'RAM ไม่เพียงพอ (ต้องการอย่างน้อย 8GB available)';
        } elseif ($ram['status'] === 'fair') {
            $warnings[] = 'RAM จำกัด - แนะนำใช้โมเดลขนาดเล็กเท่านั้น';
        }

        if ($disk['status'] === 'insufficient') {
            $ready = false;
            $errors[] = 'พื้นที่ disk ไม่เพียงพอ (ต้องการอย่างน้อย 20GB)';
        }

        if ($python['status'] === 'not_installed') {
            $ready = false;
            $errors[] = 'ต้องติดตั้ง Python 3.8+ และ pip';
        } elseif ($python['status'] === 'outdated') {
            $warnings[] = 'Python เวอร์ชันเก่า ควรอัปเดต';
        }

        if ($gpu['status'] === 'none') {
            $warnings[] = 'ไม่มี GPU - การประมวลผลจะช้ากว่าปกติ';
        }

        return [
            'ready' => $ready,
            'status' => $ready ? 'ready' : 'not_ready',
            'warnings' => $warnings,
            'errors' => $errors,
            'message' => $ready
                ? 'ระบบพร้อมสำหรับการติดตั้ง AI'
                : 'ระบบยังไม่พร้อม กรุณาแก้ไขปัญหาที่พบ',
        ];
    }

    /**
     * แนะนำโมเดลที่เหมาะสมตามทรัพยากร
     */
    public function getRecommendedModels(): array
    {
        $ram = $this->checkRam();
        $gpu = $this->checkGpu();

        $recommendations = [];

        // ถ้ามี GPU ใช้ VRAM เป็นหลัก
        if ($gpu['available'] && !empty($gpu['gpus'])) {
            $totalVram = array_sum(array_column($gpu['gpus'], 'memory_total_gb'));

            if ($totalVram >= 24) {
                $recommendations[] = [
                    'model' => 'deepseek-coder-33b-instruct',
                    'size' => '33B',
                    'quantization' => 'Q5_K_M',
                    'vram_required' => '22GB',
                    'performance' => 'excellent',
                    'reason' => 'GPU มี VRAM เพียงพอสำหรับโมเดลขนาดใหญ่',
                ];
            }

            if ($totalVram >= 16) {
                $recommendations[] = [
                    'model' => 'deepseek-coder-6.7b-instruct',
                    'size' => '13B',
                    'quantization' => 'Q5_K_M',
                    'vram_required' => '14GB',
                    'performance' => 'very_good',
                    'reason' => 'สมดุลระหว่างประสิทธิภาพและความเร็ว',
                ];
            }

            if ($totalVram >= 8) {
                $recommendations[] = [
                    'model' => 'deepseek-coder-6.7b-instruct',
                    'size' => '7B',
                    'quantization' => 'Q4_K_M',
                    'vram_required' => '6GB',
                    'performance' => 'good',
                    'reason' => 'เหมาะสำหรับ GPU ขนาดกลาง',
                ];
            }
        }

        // CPU-only recommendations
        if (!$gpu['available'] || empty($recommendations)) {
            if ($ram['available_gb'] >= 16) {
                $recommendations[] = [
                    'model' => 'deepseek-coder-6.7b-instruct',
                    'size' => '7B',
                    'quantization' => 'Q4_K_M',
                    'vram_required' => '0GB (CPU)',
                    'ram_required' => '8GB',
                    'performance' => 'fair',
                    'reason' => 'เหมาะสำหรับ CPU inference',
                ];
            }

            if ($ram['available_gb'] >= 8) {
                $recommendations[] = [
                    'model' => 'deepseek-coder-1.3b-instruct',
                    'size' => '3B',
                    'quantization' => 'Q4_K_M',
                    'vram_required' => '0GB (CPU)',
                    'ram_required' => '4GB',
                    'performance' => 'basic',
                    'reason' => 'โมเดลขนาดเล็กสำหรับระบบที่มี RAM จำกัด',
                ];
            }
        }

        return $recommendations;
    }
}
