<?php

namespace App\Services\AI;

class ModelRecommendationService
{
    /**
     * รายการโมเดล DeepSeek ทั้งหมดพร้อมข้อมูล requirements
     */
    private array $availableModels = [
        'deepseek-coder-33b-instruct' => [
            'name' => 'DeepSeek Coder 33B Instruct',
            'size' => '33B',
            'description' => 'โมเดล Coding ขนาดใหญ่ที่ทรงพลัง เขียนโค้ดได้หลายภาษา มีความสามารถในการวิเคราะห์และแก้ไขโค้ดที่ซับซ้อน เหมาะสำหรับโปรเจกต์ขนาดใหญ่',
            'capabilities' => ['coding', 'reasoning', 'multilingual'],
            'context_window' => 16384,
            'features' => [
                'เขียนโค้ด Python, JavaScript, Java, C++, Go และอื่นๆ',
                'วิเคราะห์และแก้ไข Bug ในโค้ด',
                'สร้าง Unit Tests อัตโนมัติ',
                'Refactor Code และปรับปรุงประสิทธิภาพ',
                'อธิบายโค้ดและสร้างเอกสาร'
            ],
            'quantizations' => [
                'FP16' => [
                    'vram_required' => 66,
                    'ram_required' => 70,
                    'performance' => 100,
                    'quality' => 100,
                ],
                'Q8_0' => [
                    'vram_required' => 35,
                    'ram_required' => 40,
                    'performance' => 95,
                    'quality' => 98,
                ],
                'Q5_K_M' => [
                    'vram_required' => 22,
                    'ram_required' => 26,
                    'performance' => 90,
                    'quality' => 95,
                ],
                'Q4_K_M' => [
                    'vram_required' => 18,
                    'ram_required' => 22,
                    'performance' => 85,
                    'quality' => 90,
                ],
            ],
        ],
        'deepseek-coder-6.7b-instruct' => [
            'name' => 'DeepSeek Coder 6.7B Instruct',
            'size' => '7B',
            'description' => 'โมเดล Coding ขนาดกลางที่มีความสมดุลดี ใช้ทรัพยากรไม่มาก แต่ให้ประสิทธิภาพที่ดีเยี่ยม เหมาะสำหรับงาน Coding ทั่วไป',
            'capabilities' => ['coding', 'reasoning', 'multilingual'],
            'context_window' => 16384,
            'features' => [
                'เขียนและแก้ไขโค้ดได้หลายภาษา',
                'แนะนำวิธีแก้ไข Bug',
                'สร้าง Code Snippets ที่มีประสิทธิภาพ',
                'ตรวจสอบ Code Quality',
                'รองรับภาษาไทยและภาษาอื่นๆ'
            ],
            'quantizations' => [
                'FP16' => [
                    'vram_required' => 14,
                    'ram_required' => 16,
                    'performance' => 100,
                    'quality' => 100,
                ],
                'Q8_0' => [
                    'vram_required' => 8,
                    'ram_required' => 10,
                    'performance' => 95,
                    'quality' => 98,
                ],
                'Q5_K_M' => [
                    'vram_required' => 6,
                    'ram_required' => 8,
                    'performance' => 90,
                    'quality' => 95,
                ],
                'Q4_K_M' => [
                    'vram_required' => 5,
                    'ram_required' => 6,
                    'performance' => 85,
                    'quality' => 90,
                ],
                'Q4_0' => [
                    'vram_required' => 4,
                    'ram_required' => 5,
                    'performance' => 80,
                    'quality' => 85,
                ],
            ],
        ],
        'deepseek-coder-1.3b-instruct' => [
            'name' => 'DeepSeek Coder 1.3B Instruct',
            'size' => '1.3B',
            'description' => 'โมเดล Coding ขนาดเล็ก เร็วและประหยัดทรัพยากร เหมาะสำหรับคอมพิวเตอร์ที่มี RAM น้อย หรือต้องการความเร็วสูง',
            'capabilities' => ['coding', 'basic_reasoning'],
            'context_window' => 16384,
            'features' => [
                'เขียนโค้ดพื้นฐานได้หลายภาษา',
                'แก้ไข Bug ง่ายๆ',
                'สร้าง Function และ Class ทั่วไป',
                'ความเร็วสูง ตอบสนองเร็ว',
                'ใช้ RAM เพียง 2-4 GB'
            ],
            'quantizations' => [
                'FP16' => [
                    'vram_required' => 3,
                    'ram_required' => 4,
                    'performance' => 100,
                    'quality' => 100,
                ],
                'Q8_0' => [
                    'vram_required' => 2,
                    'ram_required' => 3,
                    'performance' => 95,
                    'quality' => 98,
                ],
                'Q4_K_M' => [
                    'vram_required' => 1.5,
                    'ram_required' => 2,
                    'performance' => 85,
                    'quality' => 90,
                ],
            ],
        ],
        'deepseek-llm-7b-chat' => [
            'name' => 'DeepSeek LLM 7B Chat',
            'size' => '7B',
            'description' => 'โมเดล Chat ทั่วไป สำหรับการสนทนา ตอบคำถาม และช่วยเหลือในงานต่างๆ รองรับภาษาไทยและหลายภาษา',
            'capabilities' => ['chat', 'reasoning', 'multilingual'],
            'context_window' => 4096,
            'features' => [
                'สนทนาและตอบคำถามได้อย่างธรรมชาติ',
                'ช่วยวางแผนและให้คำแนะนำ',
                'แปลภาษาและสรุปเนื้อหา',
                'ค้นหาข้อมูลและวิเคราะห์',
                'เขียนเนื้อหา Email, บทความ'
            ],
            'quantizations' => [
                'FP16' => [
                    'vram_required' => 14,
                    'ram_required' => 16,
                    'performance' => 100,
                    'quality' => 100,
                ],
                'Q5_K_M' => [
                    'vram_required' => 6,
                    'ram_required' => 8,
                    'performance' => 90,
                    'quality' => 95,
                ],
                'Q4_K_M' => [
                    'vram_required' => 5,
                    'ram_required' => 6,
                    'performance' => 85,
                    'quality' => 90,
                ],
            ],
        ],
        'deepseek-llm-67b-chat' => [
            'name' => 'DeepSeek LLM 67B Chat',
            'size' => '67B',
            'description' => 'โมเดล Chat ขนาดใหญ่ที่ทรงพลังที่สุด สำหรับงานซับซ้อนและต้องการคุณภาพสูงสุด มีความสามารถในการวิเคราะห์ลึกและแก้ปัญหายาก',
            'capabilities' => ['chat', 'reasoning', 'multilingual', 'complex_tasks'],
            'context_window' => 4096,
            'features' => [
                'สนทนาและวิเคราะห์ในระดับสูง',
                'แก้ปัญหาที่ซับซ้อนและต้องการการคิดลึก',
                'วางแผนและสร้างกลยุทธ์',
                'วิจัยและวิเคราะห์ข้อมูลขนาดใหญ่',
                'เขียนเนื้อหาคุณภาพสูงระดับมืออาชีพ'
            ],
            'quantizations' => [
                'Q5_K_M' => [
                    'vram_required' => 45,
                    'ram_required' => 50,
                    'performance' => 90,
                    'quality' => 95,
                ],
                'Q4_K_M' => [
                    'vram_required' => 36,
                    'ram_required' => 40,
                    'performance' => 85,
                    'quality' => 90,
                ],
            ],
        ],
    ];

    private SystemRequirementsChecker $requirementsChecker;

    public function __construct()
    {
        $this->requirementsChecker = new SystemRequirementsChecker();
    }

    /**
     * รับคำแนะนำโมเดลตามทรัพยากรที่มี
     */
    public function getRecommendations(): array
    {
        $systemInfo = $this->requirementsChecker->checkAll();

        $ram = $systemInfo['ram'];
        $gpu = $systemInfo['gpu'];

        $recommendations = [
            'system_info' => [
                'ram_available' => $ram['available_gb'],
                'gpu_available' => $gpu['available'],
                'gpu_vram' => $gpu['available'] ? array_sum(array_column($gpu['gpus'], 'memory_total_gb')) : 0,
            ],
            'recommended' => [],
            'possible' => [],
            'not_recommended' => [],
        ];

        // วิเคราะห์แต่ละโมเดล
        foreach ($this->availableModels as $modelId => $modelInfo) {
            $modelAnalysis = $this->analyzeModel($modelId, $systemInfo);

            if ($modelAnalysis['status'] === 'recommended') {
                $recommendations['recommended'][] = $modelAnalysis;
            } elseif ($modelAnalysis['status'] === 'possible') {
                $recommendations['possible'][] = $modelAnalysis;
            } else {
                $recommendations['not_recommended'][] = $modelAnalysis;
            }
        }

        // เรียงตาม performance score
        usort($recommendations['recommended'], fn($a, $b) => $b['score'] <=> $a['score']);
        usort($recommendations['possible'], fn($a, $b) => $b['score'] <=> $a['score']);

        return $recommendations;
    }

    /**
     * วิเคราะห์โมเดลว่าเหมาะสมกับระบบหรือไม่
     */
    public function analyzeModel(string $modelId, ?array $systemInfo = null): array
    {
        if (!isset($this->availableModels[$modelId])) {
            return [
                'status' => 'not_found',
                'message' => 'ไม่พบข้อมูลโมเดล',
            ];
        }

        if (!$systemInfo) {
            $systemInfo = $this->requirementsChecker->checkAll();
        }

        $modelInfo = $this->availableModels[$modelId];
        $ram = $systemInfo['ram'];
        $gpu = $systemInfo['gpu'];

        $hasGpu = $gpu['available'] && !empty($gpu['gpus']);
        $totalVram = $hasGpu ? array_sum(array_column($gpu['gpus'], 'memory_total_gb')) : 0;
        $availableRam = $ram['available_gb'];

        // หา quantization ที่เหมาะสมที่สุด
        $bestQuantization = null;
        $allQuantizations = [];

        foreach ($modelInfo['quantizations'] as $quantName => $quantInfo) {
            $canRun = false;
            $useGpu = false;

            // ตรวจสอบว่ารันด้วย GPU ได้ไหม
            if ($hasGpu && $totalVram >= $quantInfo['vram_required']) {
                $canRun = true;
                $useGpu = true;
            }
            // หรือรันด้วย CPU (ใช้ RAM)
            elseif ($availableRam >= $quantInfo['ram_required']) {
                $canRun = true;
                $useGpu = false;
            }

            $quantAnalysis = [
                'name' => $quantName,
                'can_run' => $canRun,
                'use_gpu' => $useGpu,
                'vram_required' => $quantInfo['vram_required'],
                'ram_required' => $quantInfo['ram_required'],
                'performance' => $quantInfo['performance'],
                'quality' => $quantInfo['quality'],
                'speed_estimate' => $this->estimateSpeed($quantInfo, $useGpu, $systemInfo),
            ];

            $allQuantizations[] = $quantAnalysis;

            // เลือก quantization ที่ดีที่สุด
            if ($canRun && (!$bestQuantization || $quantInfo['quality'] > $bestQuantization['quality'])) {
                $bestQuantization = $quantAnalysis;
            }
        }

        // คำนวณ score
        $score = 0;
        $status = 'not_recommended';
        $reasons = [];

        if ($bestQuantization) {
            $score = $bestQuantization['quality'] * 0.6 + $bestQuantization['performance'] * 0.4;

            if ($bestQuantization['use_gpu']) {
                $score += 20; // bonus สำหรับการใช้ GPU
                $reasons[] = 'รันบน GPU ได้ - ความเร็วสูง';
            } else {
                $reasons[] = 'รันบน CPU - ช้ากว่า GPU';
            }

            // ตรวจสอบว่า RAM/VRAM เหลือเพียงพอหรือไม่
            if ($bestQuantization['use_gpu']) {
                $vramUsage = ($bestQuantization['vram_required'] / $totalVram) * 100;
                if ($vramUsage < 70) {
                    $status = 'recommended';
                    $reasons[] = 'ใช้ VRAM น้อยกว่า 70%';
                } else if ($vramUsage < 90) {
                    $status = 'possible';
                    $reasons[] = 'ใช้ VRAM ค่อนข้างสูง (' . round($vramUsage) . '%)';
                }
            } else {
                $ramUsage = ($bestQuantization['ram_required'] / $availableRam) * 100;
                if ($ramUsage < 60) {
                    $status = 'recommended';
                    $reasons[] = 'ใช้ RAM น้อยกว่า 60%';
                } else if ($ramUsage < 80) {
                    $status = 'possible';
                    $reasons[] = 'ใช้ RAM ค่อนข้างสูง (' . round($ramUsage) . '%)';
                }
            }
        } else {
            $reasons[] = 'ทรัพยากรไม่เพียงพอสำหรับโมเดลนี้';
        }

        return [
            'model_id' => $modelId,
            'model_name' => $modelInfo['name'],
            'model_size' => $modelInfo['size'],
            'description' => $modelInfo['description'],
            'capabilities' => $modelInfo['capabilities'],
            'features' => $modelInfo['features'] ?? [],
            'context_window' => $modelInfo['context_window'],
            'status' => $status,
            'score' => round($score, 1),
            'best_quantization' => $bestQuantization,
            'all_quantizations' => $allQuantizations,
            'reasons' => $reasons,
            'estimated_speed' => $bestQuantization ? $bestQuantization['speed_estimate'] : null,
        ];
    }

    /**
     * ประมาณความเร็วในการประมวลผล
     */
    private function estimateSpeed(array $quantInfo, bool $useGpu, array $systemInfo): array
    {
        // Base tokens per second
        $baseSpeed = $useGpu ? 50 : 5; // GPU vs CPU

        // Adjust by quantization
        $speedMultiplier = $quantInfo['performance'] / 100;

        // Adjust by GPU type (if available)
        if ($useGpu && !empty($systemInfo['gpu']['gpus'])) {
            $gpuName = strtolower($systemInfo['gpu']['gpus'][0]['name']);

            if (str_contains($gpuName, '4090') || str_contains($gpuName, 'a100')) {
                $baseSpeed *= 3;
            } elseif (str_contains($gpuName, '3090') || str_contains($gpuName, '4080')) {
                $baseSpeed *= 2.5;
            } elseif (str_contains($gpuName, '3080') || str_contains($gpuName, '4070')) {
                $baseSpeed *= 2;
            } elseif (str_contains($gpuName, '3070') || str_contains($gpuName, '4060')) {
                $baseSpeed *= 1.5;
            }
        }

        $tokensPerSecond = round($baseSpeed * $speedMultiplier, 1);

        return [
            'tokens_per_second' => $tokensPerSecond,
            'time_for_100_tokens' => round(100 / $tokensPerSecond, 1) . ' วินาที',
            'time_for_500_tokens' => round(500 / $tokensPerSecond, 1) . ' วินาที',
            'rating' => $tokensPerSecond >= 40 ? 'เร็วมาก' :
                        ($tokensPerSecond >= 20 ? 'เร็ว' :
                        ($tokensPerSecond >= 10 ? 'ปานกลาง' : 'ช้า')),
        ];
    }

    /**
     * รับข้อมูลโมเดลทั้งหมด
     */
    public function getAllModels(): array
    {
        return $this->availableModels;
    }

    /**
     * รับข้อมูลโมเดลเดียว
     */
    public function getModel(string $modelId): ?array
    {
        return $this->availableModels[$modelId] ?? null;
    }

    /**
     * คำนวณพื้นที่ disk ที่ต้องการสำหรับโมเดล
     */
    public function calculateDiskSpace(string $modelId, string $quantization): array
    {
        $model = $this->getModel($modelId);

        if (!$model || !isset($model['quantizations'][$quantization])) {
            return [
                'required_gb' => 0,
                'message' => 'ไม่พบข้อมูลโมเดล',
            ];
        }

        // ประมาณขนาดไฟล์จาก VRAM requirement
        $vramRequired = $model['quantizations'][$quantization]['vram_required'];
        $diskSpaceRequired = $vramRequired * 1.2; // เผื่อ overhead

        return [
            'required_gb' => round($diskSpaceRequired, 2),
            'recommended_gb' => round($diskSpaceRequired * 1.5, 2),
            'message' => 'ต้องการพื้นที่อย่างน้อย ' . round($diskSpaceRequired, 2) . ' GB',
        ];
    }

    /**
     * แนะนำการตั้งค่าที่เหมาะสมสำหรับโมเดล
     */
    public function getOptimalSettings(string $modelId, array $systemInfo): array
    {
        $analysis = $this->analyzeModel($modelId, $systemInfo);

        if (!$analysis['best_quantization']) {
            return [
                'error' => 'ไม่สามารถรันโมเดลนี้ได้ด้วยทรัพยากรปัจจุบัน',
            ];
        }

        $useGpu = $analysis['best_quantization']['use_gpu'];

        return [
            'quantization' => $analysis['best_quantization']['name'],
            'use_gpu' => $useGpu,
            'gpu_layers' => $useGpu ? 'all' : 0,
            'threads' => $systemInfo['cpu']['cores'],
            'context_size' => min(4096, $this->availableModels[$modelId]['context_window']),
            'batch_size' => $useGpu ? 512 : 128,
            'memory_lock' => true,
            'no_mmap' => false,
        ];
    }
}
