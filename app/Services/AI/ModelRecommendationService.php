<?php

namespace App\Services\AI;

class ModelRecommendationService
{
    /**
     * รายการโมเดล AI ที่มีจริงใน Ollama และทำงานได้
     */
    private array $availableModels = [
        'qwen2.5:0.5b' => [
            'name' => 'Qwen 2.5 (0.5B)',
            'size' => '0.5B',
            'description' => 'โมเดลขนาดเล็กมากที่รองรับภาษาไทย เร็ว ประหยัดทรัพยากร เหมาะสำหรับเซิร์ฟเวอร์ที่มี RAM น้อย',
            'capabilities' => ['chat', 'multilingual', 'basic_reasoning'],
            'context_window' => 32768,
            'features' => [
                'รองรับภาษาไทยได้ดีมาก',
                'ตอบคำถามพื้นฐาน',
                'สนทนาและช่วยเหลือทั่วไป',
                'ความเร็วสูงมาก',
                'ใช้ RAM เพียง 1-2 GB'
            ],
            'quantizations' => [
                'Q4_K_M' => [
                    'vram_required' => 0.8,
                    'ram_required' => 1.5,
                    'performance' => 100,
                    'quality' => 85,
                ],
            ],
        ],
        'qwen2.5:1.5b' => [
            'name' => 'Qwen 2.5 (1.5B)',
            'size' => '1.5B',
            'description' => 'โมเดลขนาดเล็กที่รองรับภาษาไทยได้ดี มีความสามารถสูงกว่า 0.5B เหมาะสำหรับงานทั่วไป',
            'capabilities' => ['chat', 'multilingual', 'reasoning'],
            'context_window' => 32768,
            'features' => [
                'รองรับภาษาไทยและหลายภาษา',
                'ตอบคำถามและให้คำแนะนำ',
                'วิเคราะห์และสรุปข้อมูล',
                'เร็วและใช้ทรัพยากรน้อย',
                'ใช้ RAM 2-3 GB'
            ],
            'quantizations' => [
                'Q4_K_M' => [
                    'vram_required' => 1.5,
                    'ram_required' => 2.5,
                    'performance' => 95,
                    'quality' => 88,
                ],
            ],
        ],
        'gemma2:2b' => [
            'name' => 'Gemma 2 (2B)',
            'size' => '2B',
            'description' => 'โมเดลจาก Google ขนาดเล็กแต่มีประสิทธิภาพสูง รองรับหลายภาษารวมไทย',
            'capabilities' => ['chat', 'reasoning', 'multilingual'],
            'context_window' => 8192,
            'features' => [
                'ประสิทธิภาพสูงจาก Google',
                'รองรับภาษาไทย',
                'วิเคราะห์และตอบคำถาม',
                'สนทนาได้เป็นธรรมชาติ',
                'ใช้ RAM 3-4 GB'
            ],
            'quantizations' => [
                'Q4_K_M' => [
                    'vram_required' => 2,
                    'ram_required' => 3,
                    'performance' => 90,
                    'quality' => 90,
                ],
            ],
        ],
        'llama3.2:3b' => [
            'name' => 'Llama 3.2 (3B)',
            'size' => '3B',
            'description' => 'โมเดลจาก Meta ขนาดกลางที่มีความสมดุลดีระหว่างประสิทธิภาพและทรัพยากร',
            'capabilities' => ['chat', 'reasoning', 'coding', 'multilingual'],
            'context_window' => 131072,
            'features' => [
                'โมเดลคุณภาพสูงจาก Meta',
                'เขียนโค้ดได้',
                'วิเคราะห์และแก้ปัญหา',
                'รองรับหลายภาษา',
                'ใช้ RAM 4-5 GB'
            ],
            'quantizations' => [
                'Q4_K_M' => [
                    'vram_required' => 3,
                    'ram_required' => 4,
                    'performance' => 88,
                    'quality' => 92,
                ],
            ],
        ],
        'phi3:3.8b' => [
            'name' => 'Phi 3 (3.8B)',
            'size' => '3.8B',
            'description' => 'โมเดลจาก Microsoft ขนาดกลางที่มีประสิทธิภาพสูงมาก เหมาะสำหรับงานที่ต้องการความแม่นยำ',
            'capabilities' => ['chat', 'reasoning', 'coding', 'complex_tasks'],
            'context_window' => 128000,
            'features' => [
                'ประสิทธิภาพสูงมากจาก Microsoft',
                'เขียนและวิเคราะห์โค้ด',
                'แก้ปัญหาซับซ้อน',
                'วิเคราะห์เชิงลึก',
                'ใช้ RAM 4-6 GB'
            ],
            'quantizations' => [
                'Q4_K_M' => [
                    'vram_required' => 3.5,
                    'ram_required' => 5,
                    'performance' => 85,
                    'quality' => 95,
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
