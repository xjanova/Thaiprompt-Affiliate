<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiModel;
use App\Services\AI\LocalAiManager;
use App\Services\AI\AiServiceFactory;
use App\Services\AI\LlamaInstallationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderManagementController extends Controller
{
    private LocalAiManager $localAiManager;

    public function __construct()
    {
        $this->localAiManager = new LocalAiManager();
    }

    /**
     * หน้าจัดการ Providers
     */
    public function index()
    {
        $providers = AiProvider::with(['models'])->get();

        // ตรวจสอบสถานะ Local AI (ทั้ง DeepSeek และ Meta Local)
        $localAiStatus = null;
        $localProvider = $providers->firstWhere('name', 'deepseek-local')
            ?? $providers->firstWhere('name', 'meta-local');

        if ($localProvider) {
            $localAiStatus = $this->localAiManager->getStatus();
        }

        // ดึง Meta Local provider แยก
        $metaLocalProvider = $providers->firstWhere('name', 'meta-local');
        $metaLocalStatus = null;
        if ($metaLocalProvider) {
            $metaLocalStatus = $this->checkMetaLocalStatus();
        }

        // Logo mapping สำหรับ Providers
        $providerLogos = $this->getProviderLogos();

        return view('admin.ai-providers.index', compact(
            'providers',
            'localAiStatus',
            'metaLocalStatus',
            'metaLocalProvider',
            'providerLogos'
        ));
    }

    /**
     * รายการโลโก้ของ Providers
     */
    private function getProviderLogos(): array
    {
        return [
            'openai' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4d/OpenAI_Logo.svg/200px-OpenAI_Logo.svg.png',
            'anthropic' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Anthropic_logo.svg/200px-Anthropic_logo.svg.png',
            'google' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/120px-Google_%22G%22_logo.svg.png',
            'deepseek' => 'https://avatars.githubusercontent.com/u/139254846?s=200&v=4',
            'deepseek-local' => 'https://avatars.githubusercontent.com/u/139254846?s=200&v=4',
            'meta' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Meta_Platforms_Inc._logo.svg/200px-Meta_Platforms_Inc._logo.svg.png',
            'meta-local' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Meta_Platforms_Inc._logo.svg/200px-Meta_Platforms_Inc._logo.svg.png',
        ];
    }

    /**
     * ตรวจสอบสถานะ Meta Local (Ollama/llama.cpp)
     */
    private function checkMetaLocalStatus(): array
    {
        try {
            // ลองเชื่อมต่อกับ Ollama
            $response = Http::timeout(5)->get('http://localhost:11434/api/tags');

            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];
                return [
                    'running' => true,
                    'endpoint' => 'http://localhost:11434',
                    'models' => $models,
                    'models_count' => count($models),
                ];
            }
        } catch (\Exception $e) {
            // ลอง llama.cpp server แทน
            try {
                $response = Http::timeout(5)->get('http://localhost:11434/health');
                if ($response->successful()) {
                    return [
                        'running' => true,
                        'endpoint' => 'http://localhost:11434',
                        'models' => [],
                        'models_count' => 1,
                        'server_type' => 'llama.cpp',
                    ];
                }
            } catch (\Exception $e2) {
                // Server ไม่ทำงาน
            }
        }

        return [
            'running' => false,
            'endpoint' => 'http://localhost:11434',
            'models' => [],
            'models_count' => 0,
        ];
    }

    /**
     * เปิด/ปิด Provider
     */
    public function toggleProvider($providerId)
    {
        $provider = AiProvider::findOrFail($providerId);

        $provider->is_active = !$provider->is_active;
        $provider->save();

        return response()->json([
            'success' => true,
            'message' => $provider->is_active ? 'เปิดใช้งาน Provider แล้ว' : 'ปิดใช้งาน Provider แล้ว',
            'is_active' => $provider->is_active,
        ]);
    }

    /**
     * แก้ไข Configuration
     */
    public function updateConfig(Request $request, $providerId)
    {
        $provider = AiProvider::findOrFail($providerId);

        $request->validate([
            'config' => 'sometimes|array',
            'api_key' => 'sometimes|string',
            'api_endpoint' => 'sometimes|url',
        ]);

        if ($request->has('config')) {
            $provider->config = array_merge($provider->config ?? [], $request->config);
        }

        if ($request->has('api_key')) {
            $config = $provider->config ?? [];
            $config['api_key'] = $request->api_key;
            $provider->config = $config;
        }

        if ($request->has('api_endpoint')) {
            $provider->api_endpoint = $request->api_endpoint;
        }

        $provider->save();

        return response()->json([
            'success' => true,
            'message' => 'อัปเดต Configuration สำเร็จ',
        ]);
    }

    /**
     * ทดสอบการเชื่อมต่อ Provider
     */
    public function testConnection($providerId)
    {
        $provider = AiProvider::findOrFail($providerId);

        // ตรวจสอบตามประเภท provider
        if (in_array($provider->name, ['deepseek-local', 'meta-local'])) {
            return $this->testLocalProvider($provider);
        } else {
            return $this->testCloudProvider($provider);
        }
    }

    /**
     * ทดสอบ Local Provider (Ollama/llama.cpp)
     */
    private function testLocalProvider(AiProvider $provider)
    {
        $endpoint = $provider->api_endpoint ?? 'http://localhost:11434';

        try {
            // ทดสอบเชื่อมต่อ Ollama
            $response = Http::timeout(10)->get(str_replace('/v1', '', $endpoint) . '/api/tags');

            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];
                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อ Local AI สำเร็จ (' . count($models) . ' models พร้อมใช้งาน)',
                    'details' => [
                        'status' => 'running',
                        'models' => $models,
                        'endpoint' => $endpoint,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // ลอง llama.cpp health check
            try {
                $response = Http::timeout(5)->get(str_replace('/v1', '', $endpoint) . '/health');
                if ($response->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'เชื่อมต่อ llama.cpp server สำเร็จ',
                        'details' => [
                            'status' => 'running',
                            'server_type' => 'llama.cpp',
                            'endpoint' => $endpoint,
                        ],
                    ]);
                }
            } catch (\Exception $e2) {
                // ไม่สามารถเชื่อมต่อได้
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'ไม่สามารถเชื่อมต่อ Local AI Server ได้ - ตรวจสอบว่า Ollama หรือ llama.cpp กำลังทำงาน',
            'details' => [
                'endpoint' => $endpoint,
                'suggestion' => 'ลองรัน: ollama serve หรือ systemctl start llama-server',
            ],
        ]);
    }

    /**
     * ทดสอบ Cloud Provider
     */
    private function testCloudProvider(AiProvider $provider)
    {
        $config = $provider->config ?? [];

        // Self-hosted ไม่ต้องใช้ API Key
        if ($provider->provider_type !== 'self-hosted' && empty($config['api_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ API Key - กรุณาตั้งค่า API Key ก่อน',
            ]);
        }

        try {
            $endpoint = $provider->api_endpoint;
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if (!empty($config['api_key'])) {
                $headers['Authorization'] = 'Bearer ' . $config['api_key'];
            }

            // ทดสอบดึงรายการ models
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->get($endpoint . '/models');

            if ($response->successful()) {
                $models = $response->json()['data'] ?? [];
                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อ ' . $provider->display_name . ' สำเร็จ!',
                    'details' => [
                        'models_available' => count($models),
                        'endpoint' => $endpoint,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'การเชื่อมต่อล้มเหลว: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * ทดสอบแชทกับ AI (Test Chat)
     */
    public function testChat(Request $request, $providerId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'model_id' => 'required|exists:ai_models,id',
        ]);

        $provider = AiProvider::findOrFail($providerId);
        $model = AiModel::findOrFail($request->model_id);

        try {
            $endpoint = $provider->api_endpoint;
            $config = $provider->config ?? [];

            $headers = [
                'Content-Type' => 'application/json',
            ];

            if (!empty($config['api_key'])) {
                $headers['Authorization'] = 'Bearer ' . $config['api_key'];
            }

            $startTime = microtime(true);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($endpoint . '/chat/completions', [
                    'model' => $model->model_identifier,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'คุณเป็น AI Assistant ของระบบ Thaiprompt Affiliate ตอบภาษาไทยสั้นๆ กระชับ เข้าใจง่าย'
                        ],
                        [
                            'role' => 'user',
                            'content' => $request->message
                        ]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                $usage = $data['usage'] ?? [];

                return response()->json([
                    'success' => true,
                    'response' => $content,
                    'model' => $model->display_name,
                    'response_time_ms' => $responseTime,
                    'usage' => [
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'AI ไม่ตอบกลับ: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
            ]);

        } catch (\Exception $e) {
            Log::error('Test Chat Error', [
                'provider' => $provider->name,
                'model' => $model->model_identifier,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * เริ่ม Local AI
     */
    public function startLocalAi()
    {
        $result = $this->localAiManager->start();

        return response()->json($result);
    }

    /**
     * หยุด Local AI
     */
    public function stopLocalAi()
    {
        $result = $this->localAiManager->stop();

        return response()->json($result);
    }

    /**
     * Restart Local AI
     */
    public function restartLocalAi()
    {
        $result = $this->localAiManager->restart();

        return response()->json($result);
    }

    /**
     * สถานะ Local AI
     */
    public function getLocalAiStatus()
    {
        $status = $this->localAiManager->getStatus();
        $summary = $this->localAiManager->getSummaryStats();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * โหลดโมเดลเข้า memory
     */
    public function loadModel(Request $request)
    {
        $request->validate([
            'model_name' => 'required|string',
        ]);

        $result = $this->localAiManager->loadModel($request->model_name);

        return response()->json($result);
    }

    /**
     * รายการ Models ของ Provider
     */
    public function getProviderModels($providerId)
    {
        $models = AiModel::where('provider_id', $providerId)
            ->orderBy('display_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $models,
        ]);
    }

    /**
     * เปิด/ปิด Model
     */
    public function toggleModel($modelId)
    {
        $model = AiModel::findOrFail($modelId);

        $model->is_active = !$model->is_active;
        $model->save();

        return response()->json([
            'success' => true,
            'message' => $model->is_active ? 'เปิดใช้งาน Model แล้ว' : 'ปิดใช้งาน Model แล้ว',
            'is_active' => $model->is_active,
        ]);
    }

    // =====================================================
    // Llama Installation Methods
    // =====================================================

    /**
     * หน้าติดตั้ง Llama
     *
     * @return \Illuminate\View\View
     */
    public function installPage()
    {
        $installService = new LlamaInstallationService();
        $progress = $installService->getProgress();

        // รายการ Models ที่สามารถติดตั้งได้
        $availableModels = [
            'ollama' => [
                ['id' => 'llama3.2:1b', 'name' => 'Llama 3.2 1B', 'size' => '~1GB', 'ram' => '4GB', 'description' => 'เล็กที่สุด เร็วมาก'],
                ['id' => 'llama3.2:3b', 'name' => 'Llama 3.2 3B', 'size' => '~2GB', 'ram' => '8GB', 'description' => 'สมดุลระหว่างความเร็วและคุณภาพ'],
                ['id' => 'llama3.1:8b', 'name' => 'Llama 3.1 8B', 'size' => '~4.7GB', 'ram' => '16GB', 'description' => 'คุณภาพดี แนะนำ'],
                ['id' => 'llama3.1:70b-instruct-q4_K_M', 'name' => 'Llama 3.1 70B', 'size' => '~40GB', 'ram' => '64GB', 'description' => 'คุณภาพสูงสุด'],
            ],
            'huggingface' => [
                ['id' => 'Llama-4-Scout-17B-16E-Instruct-Q5_K_M', 'name' => 'Llama 4 Scout 17B', 'size' => '~12GB', 'ram' => '32GB', 'description' => 'Llama 4 รุ่นล่าสุด'],
                ['id' => 'Meta-Llama-3.1-70B-Instruct-Q4_K_M', 'name' => 'Llama 3.1 70B GGUF', 'size' => '~40GB', 'ram' => '64GB', 'description' => 'คุณภาพสูงมาก'],
            ],
        ];

        // ข้อมูลระบบ
        $systemInfo = $this->getSystemInfo();

        return view('admin.ai-providers.install', compact(
            'progress',
            'availableModels',
            'systemInfo'
        ));
    }

    /**
     * เริ่มติดตั้ง Llama
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startInstall(Request $request)
    {
        $request->validate([
            'method' => 'required|in:ollama,huggingface',
            'model' => 'required|string',
        ]);

        $installService = new LlamaInstallationService();

        // ตรวจสอบว่ากำลังติดตั้งอยู่หรือไม่
        if ($installService->isInstalling()) {
            return response()->json([
                'success' => false,
                'message' => 'กำลังติดตั้งอยู่แล้ว กรุณารอจนกว่าจะเสร็จ',
            ], 400);
        }

        $result = $installService->startInstallation(
            $request->method,
            $request->model
        );

        return response()->json($result);
    }

    /**
     * ดึง Progress การติดตั้ง
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstallProgress()
    {
        $installService = new LlamaInstallationService();
        $progress = $installService->getProgress();

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * ยกเลิกการติดตั้ง
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelInstall()
    {
        $installService = new LlamaInstallationService();
        $result = $installService->cancelInstallation();

        return response()->json($result);
    }

    /**
     * ดึง Log การติดตั้ง
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstallLog()
    {
        $installService = new LlamaInstallationService();
        $log = $installService->getLog();

        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }

    /**
     * ดึงข้อมูลระบบ (RAM, CPU)
     *
     * ใช้วิธีหลายแบบเพื่อรองรับ shared hosting
     * ที่มี open_basedir restriction
     *
     * @return array
     */
    private function getSystemInfo(): array
    {
        $totalRam = 0;
        $cpuCores = 1;
        $diskFree = 0;
        $diskTotal = 0;

        // วิธีที่ 1: ลองอ่านจาก /proc (อาจถูก block)
        try {
            if (@is_readable('/proc/meminfo')) {
                $meminfo = @file_get_contents('/proc/meminfo');
                if ($meminfo && preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $matches)) {
                    $totalRam = round($matches[1] / 1024 / 1024, 1);
                }
            }

            if (@is_readable('/proc/cpuinfo')) {
                $cpuinfo = @file_get_contents('/proc/cpuinfo');
                if ($cpuinfo) {
                    $cpuCores = preg_match_all('/^processor/m', $cpuinfo, $matches);
                }
            }
        } catch (\Exception $e) {
            // ข้าม error จาก open_basedir
        }

        // วิธีที่ 2: ถ้าวิธีที่ 1 ไม่ได้ ลองใช้ exec
        if ($totalRam == 0 && function_exists('exec')) {
            try {
                // ดึง RAM
                @exec('free -g 2>/dev/null | grep Mem | awk \'{print $2}\'', $ramOutput);
                if (!empty($ramOutput[0]) && is_numeric($ramOutput[0])) {
                    $totalRam = (float) $ramOutput[0];
                }

                // ดึง CPU cores
                @exec('nproc 2>/dev/null', $cpuOutput);
                if (!empty($cpuOutput[0]) && is_numeric($cpuOutput[0])) {
                    $cpuCores = (int) $cpuOutput[0];
                }
            } catch (\Exception $e) {
                // ข้าม
            }
        }

        // วิธีที่ 3: ถ้ายังไม่ได้ ใช้ค่า default ตาม environment
        if ($totalRam == 0) {
            // ค่า default สำหรับ shared hosting ทั่วไป
            $totalRam = 4; // 4 GB
            $cpuCores = 2;
        }

        // ดึงพื้นที่ดิสก์
        try {
            $diskFree = round(@disk_free_space(storage_path()) / 1024 / 1024 / 1024, 1);
            $diskTotal = round(@disk_total_space(storage_path()) / 1024 / 1024 / 1024, 1);
        } catch (\Exception $e) {
            $diskFree = 10;
            $diskTotal = 50;
        }

        return [
            'ram_gb' => $totalRam,
            'cpu_cores' => $cpuCores,
            'disk_free_gb' => $diskFree ?: 10,
            'disk_total_gb' => $diskTotal ?: 50,
            'recommended_model' => $this->getRecommendedModel($totalRam),
            'is_estimated' => ($totalRam <= 4), // บอกว่าเป็นค่าประมาณหรือไม่
        ];
    }

    /**
     * แนะนำ Model ตาม RAM ที่มี
     *
     * @param float $ramGb
     * @return string
     */
    private function getRecommendedModel(float $ramGb): string
    {
        if ($ramGb >= 64) {
            return 'llama3.1:70b-instruct-q4_K_M';
        } elseif ($ramGb >= 32) {
            return 'llama3.1:8b';
        } elseif ($ramGb >= 16) {
            return 'llama3.1:8b';
        } elseif ($ramGb >= 8) {
            return 'llama3.2:3b';
        } else {
            return 'llama3.2:1b';
        }
    }
}
