<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CentralAiSetting;
use App\Services\AI\LocalAiManager;
use App\Services\AI\LlamaInstallationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Central AI Controller
 *
 * ควบคุมระบบ Central AI สำหรับจัดการ Ollama และ AI Providers
 */
class CentralAiController extends Controller
{
    /**
     * LocalAiManager instance
     *
     * @var LocalAiManager
     */
    protected $localAiManager;

    /**
     * LlamaInstallationService instance
     *
     * @var LlamaInstallationService
     */
    protected $llamaInstaller;

    /**
     * Constructor
     *
     * @param LocalAiManager $localAiManager
     * @param LlamaInstallationService $llamaInstaller
     */
    public function __construct(
        LocalAiManager $localAiManager,
        LlamaInstallationService $llamaInstaller
    ) {
        $this->localAiManager = $localAiManager;
        $this->llamaInstaller = $llamaInstaller;
    }

    /**
     * แสดงหน้าหลัก Central AI
     * ถ้ายังไม่ได้ติดตั้ง → แสดง Wizard
     * ถ้าติดตั้งแล้ว → แสดง Dashboard
     *
     * @return View
     */
    public function index(): View
    {
        $setting = CentralAiSetting::getActive();

        // ถ้ายังไม่ได้ติดตั้ง แสดง Wizard
        if (!$setting->setup_completed) {
            return $this->wizard();
        }

        // ถ้าติดตั้งแล้ว แสดง Dashboard
        return $this->dashboard();
    }

    /**
     * แสดงหน้า Setup Wizard
     *
     * @return View
     */
    public function wizard(): View
    {
        $setting = CentralAiSetting::getActive();

        // ตรวจสอบ system resources
        $systemResources = $this->detectSystemResources();

        // อัพเดท system resources ใน setting
        $setting->update([
            'cpu_cores' => $systemResources['cpu_cores'],
            'ram_gb' => $systemResources['ram_gb'],
            'disk_gb' => $systemResources['disk_gb'],
            'disk_available_gb' => $systemResources['disk_available_gb'],
            'os_type' => $systemResources['os_type'],
        ]);

        return view('admin.central-ai.wizard', [
            'setting' => $setting,
            'systemResources' => $systemResources,
            'recommendedModels' => $this->getRecommendedModels($systemResources),
        ]);
    }

    /**
     * แสดงหน้า Dashboard
     *
     * @return View
     */
    public function dashboard(): View
    {
        $setting = CentralAiSetting::getActive();

        // ทำ health check อัตโนมัติถ้าเกิน 5 นาที
        if (
            !$setting->last_health_check_at ||
            $setting->last_health_check_at->lt(now()->subMinutes(5))
        ) {
            $setting->performHealthCheck();
            $setting->refresh();
        }

        return view('admin.central-ai.dashboard', [
            'setting' => $setting,
            'healthStatus' => $setting->health_check_result,
            'statistics' => [
                'total_requests' => $setting->total_requests,
                'successful_requests' => $setting->successful_requests,
                'failed_requests' => $setting->failed_requests,
                'success_rate' => $setting->success_rate,
            ],
        ]);
    }

    /**
     * ตรวจสอบ system resources (CPU, RAM, Disk)
     *
     * @return JsonResponse
     */
    public function checkSystemResources(): JsonResponse
    {
        try {
            $resources = $this->detectSystemResources();

            return response()->json([
                'success' => true,
                'data' => $resources,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to detect system resources', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถตรวจสอบทรัพยากรระบบได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ Ollama
     *
     * @return JsonResponse
     */
    public function checkOllamaStatus(): JsonResponse
    {
        try {
            $setting = CentralAiSetting::getActive();
            $status = $setting->checkOllamaStatus();

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check Ollama status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถตรวจสอบสถานะ Ollama ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ติดตั้ง Ollama
     *
     * @return JsonResponse
     */
    public function installOllama(): JsonResponse
    {
        try {
            // ตรวจสอบว่าติดตั้งแล้วหรือยัง
            $status = $this->llamaInstaller->checkInstallationStatus();

            if ($status['is_installed']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ollama ติดตั้งแล้ว',
                    'data' => $status,
                ]);
            }

            // ติดตั้ง Ollama
            $result = $this->llamaInstaller->installOllama();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'การติดตั้ง Ollama ล้มเหลว',
                    'error' => $result['message'] ?? 'Unknown error',
                ], 500);
            }

            // อัพเดทสถานะใน settings
            $setting = CentralAiSetting::getActive();
            $setting->update([
                'is_ollama_installed' => true,
                'ollama_status' => 'stopped',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ติดตั้ง Ollama สำเร็จ',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to install Ollama', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'การติดตั้ง Ollama ล้มเหลว',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดาวน์โหลดและติดตั้งโมเดล
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function downloadModel(Request $request): JsonResponse
    {
        $request->validate([
            'model' => 'required|string',
        ]);

        try {
            $modelName = $request->input('model');

            // ดาวน์โหลดโมเดล
            $result = $this->llamaInstaller->downloadModel($modelName);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'การดาวน์โหลดโมเดลล้มเหลว',
                    'error' => $result['message'] ?? 'Unknown error',
                ], 500);
            }

            // อัพเดทรายการโมเดลใน settings
            $setting = CentralAiSetting::getActive();
            $status = $setting->checkOllamaStatus();

            return response()->json([
                'success' => true,
                'message' => "ดาวน์โหลดโมเดล {$modelName} สำเร็จ",
                'data' => [
                    'model' => $modelName,
                    'installed_models' => $status['models'] ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to download model', [
                'model' => $request->input('model'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'การดาวน์โหลดโมเดลล้มเหลว',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เริ่มการทำงานของ Ollama service
     *
     * @return JsonResponse
     */
    public function startOllama(): JsonResponse
    {
        try {
            $result = $this->localAiManager->start();

            // อัพเดทสถานะ
            $setting = CentralAiSetting::getActive();
            $setting->checkOllamaStatus();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'เริ่ม Ollama สำเร็จ' : 'ไม่สามารถเริ่ม Ollama ได้',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start Ollama', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเริ่ม Ollama ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * หยุดการทำงานของ Ollama service
     *
     * @return JsonResponse
     */
    public function stopOllama(): JsonResponse
    {
        try {
            $result = $this->localAiManager->stop();

            // อัพเดทสถานะ
            $setting = CentralAiSetting::getActive();
            $setting->update(['ollama_status' => 'stopped']);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'หยุด Ollama สำเร็จ' : 'ไม่สามารถหยุด Ollama ได้',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to stop Ollama', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถหยุด Ollama ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * รีสตาร์ท Ollama service
     *
     * @return JsonResponse
     */
    public function restartOllama(): JsonResponse
    {
        try {
            $result = $this->localAiManager->restart();

            // อัพเดทสถานะ
            $setting = CentralAiSetting::getActive();
            $setting->checkOllamaStatus();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'รีสตาร์ท Ollama สำเร็จ' : 'ไม่สามารถรีสตาร์ท Ollama ได้',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restart Ollama', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถรีสตาร์ท Ollama ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ PostXAgent
     *
     * @return JsonResponse
     */
    public function checkPostXAgentStatus(): JsonResponse
    {
        try {
            $setting = CentralAiSetting::getActive();
            $status = $setting->checkPostXAgentStatus();

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check PostXAgent status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถตรวจสอบสถานะ PostXAgent ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * บันทึกการตั้งค่า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'ollama_host' => 'nullable|string',
            'ollama_port' => 'nullable|integer',
            'default_ollama_model' => 'nullable|string',
            'postxagent_enabled' => 'nullable|boolean',
            'postxagent_host' => 'nullable|string',
            'postxagent_api_port' => 'nullable|integer',
            'postxagent_signalr_port' => 'nullable|integer',
            'postxagent_api_key' => 'nullable|string',
            'postxagent_preferred_provider' => 'nullable|string',
            'enable_rag' => 'nullable|boolean',
            'embedding_provider' => 'nullable|string',
            'embedding_model' => 'nullable|string',
            'similarity_threshold' => 'nullable|numeric',
            'max_context_chunks' => 'nullable|integer',
            'auto_reply_enabled' => 'nullable|boolean',
            'response_max_tokens' => 'nullable|integer',
            'default_temperature' => 'nullable|numeric',
            'fallback_message' => 'nullable|string',
        ]);

        try {
            $setting = CentralAiSetting::getActive();

            // อัพเดทการตั้งค่า
            $setting->update($request->only([
                'ollama_host',
                'ollama_port',
                'default_ollama_model',
                'postxagent_enabled',
                'postxagent_host',
                'postxagent_api_port',
                'postxagent_signalr_port',
                'postxagent_api_key',
                'postxagent_preferred_provider',
                'enable_rag',
                'embedding_provider',
                'embedding_model',
                'similarity_threshold',
                'max_context_chunks',
                'auto_reply_enabled',
                'response_max_tokens',
                'default_temperature',
                'fallback_message',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าสำเร็จ',
                'data' => $setting->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save settings', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกการตั้งค่าได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เสร็จสิ้นการติดตั้ง (Wizard Step สุดท้าย)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeSetup(Request $request): JsonResponse
    {
        try {
            $setting = CentralAiSetting::getActive();

            // อัพเดทสถานะว่าติดตั้งเสร็จแล้ว
            $setting->update([
                'setup_completed' => true,
                'setup_step' => 5,
                'is_active' => true,
            ]);

            // ทำ health check
            $setting->performHealthCheck();

            return response()->json([
                'success' => true,
                'message' => 'ติดตั้ง Central AI เสร็จสมบูรณ์',
                'data' => $setting->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to complete setup', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเสร็จสิ้นการติดตั้งได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ทำ Health Check
     *
     * @return JsonResponse
     */
    public function performHealthCheck(): JsonResponse
    {
        try {
            $setting = CentralAiSetting::getActive();
            $result = $setting->performHealthCheck();

            return response()->json([
                'success' => true,
                'message' => 'Health check เสร็จสมบูรณ์',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to perform health check', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถทำ health check ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตรวจจับ system resources (CPU, RAM, Disk, OS)
     *
     * @return array
     */
    protected function detectSystemResources(): array
    {
        $resources = [
            'cpu_cores' => null,
            'ram_gb' => null,
            'disk_gb' => null,
            'disk_available_gb' => null,
            'os_type' => PHP_OS_FAMILY,
        ];

        try {
            // ตรวจจับ CPU cores
            if (function_exists('shell_exec')) {
                if (stripos(PHP_OS, 'WIN') === 0) {
                    // Windows
                    $cpuInfo = shell_exec('wmic cpu get NumberOfCores');
                    preg_match('/\d+/', $cpuInfo, $matches);
                    $resources['cpu_cores'] = isset($matches[0]) ? (int) $matches[0] : null;
                } else {
                    // Linux/Mac
                    $cpuInfo = shell_exec('nproc');
                    $resources['cpu_cores'] = $cpuInfo ? (int) trim($cpuInfo) : null;
                }
            }

            // ตรวจจับ RAM
            if (function_exists('shell_exec')) {
                if (stripos(PHP_OS, 'WIN') === 0) {
                    // Windows
                    $ramInfo = shell_exec('wmic OS get TotalVisibleMemorySize /Value');
                    preg_match('/TotalVisibleMemorySize=(\d+)/', $ramInfo, $matches);
                    if (isset($matches[1])) {
                        $resources['ram_gb'] = round((int) $matches[1] / 1024 / 1024, 2);
                    }
                } else {
                    // Linux/Mac
                    $ramInfo = shell_exec('free -b | grep Mem');
                    if ($ramInfo) {
                        $parts = preg_split('/\s+/', trim($ramInfo));
                        if (isset($parts[1])) {
                            $resources['ram_gb'] = round((int) $parts[1] / 1024 / 1024 / 1024, 2);
                        }
                    }
                }
            }

            // ตรวจจับพื้นที่ disk
            $diskTotal = disk_total_space('/');
            $diskFree = disk_free_space('/');

            if ($diskTotal !== false) {
                $resources['disk_gb'] = round($diskTotal / 1024 / 1024 / 1024, 2);
            }

            if ($diskFree !== false) {
                $resources['disk_available_gb'] = round($diskFree / 1024 / 1024 / 1024, 2);
            }
        } catch (\Exception $e) {
            Log::warning('Some system resources could not be detected', [
                'error' => $e->getMessage(),
            ]);
        }

        return $resources;
    }

    /**
     * แนะนำโมเดลที่เหมาะสมตาม system resources
     *
     * @param array $resources
     * @return array
     */
    protected function getRecommendedModels(array $resources): array
    {
        $ramGb = $resources['ram_gb'] ?? 0;
        $models = [];

        // โมเดลขนาดเล็ก (< 8GB RAM)
        if ($ramGb >= 4) {
            $models[] = [
                'name' => 'llama3.2:3b',
                'size' => '2GB',
                'description' => 'โมเดลขนาดเล็ก เหมาะกับ RAM 4-8GB',
                'recommended' => $ramGb < 8,
            ];
        }

        // โมเดลขนาดกลาง (8-16GB RAM)
        if ($ramGb >= 8) {
            $models[] = [
                'name' => 'llama3:8b',
                'size' => '4.7GB',
                'description' => 'โมเดลขนาดกลาง เหมาะกับ RAM 8-16GB',
                'recommended' => $ramGb >= 8 && $ramGb < 16,
            ];
        }

        // โมเดลขนาดใหญ่ (> 16GB RAM)
        if ($ramGb >= 16) {
            $models[] = [
                'name' => 'llama3.1:8b',
                'size' => '4.9GB',
                'description' => 'โมเดลขนาดกลาง-ใหญ่ เหมาะกับ RAM 16GB+',
                'recommended' => $ramGb >= 16 && $ramGb < 32,
            ];
        }

        if ($ramGb >= 32) {
            $models[] = [
                'name' => 'llama3.1:70b',
                'size' => '40GB',
                'description' => 'โมเดลขนาดใหญ่ เหมาะกับ RAM 32GB+',
                'recommended' => $ramGb >= 32,
            ];
        }

        // ถ้า RAM น้อยเกินไป
        if (empty($models)) {
            $models[] = [
                'name' => 'llama3.2:1b',
                'size' => '1.3GB',
                'description' => 'โมเดลขนาดเล็กมาก สำหรับ RAM < 4GB',
                'recommended' => true,
            ];
        }

        return $models;
    }
}
