<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiModel;
use App\Services\AI\LocalAiManager;
use Illuminate\Http\Request;

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
        $providers = AiProvider::with(['models' => function ($query) {
            $query->where('is_active', true);
        }])->get();

        // ตรวจสอบสถานะ Local AI
        $localAiStatus = null;
        $localProvider = $providers->firstWhere('name', 'deepseek-local');

        if ($localProvider) {
            $localAiStatus = $this->localAiManager->getStatus();
        }

        return view('admin.ai-providers.index', compact('providers', 'localAiStatus'));
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
        if ($provider->name === 'deepseek-local') {
            return $this->testLocalProvider();
        } else {
            return $this->testCloudProvider($provider);
        }
    }

    /**
     * ทดสอบ Local Provider
     */
    private function testLocalProvider()
    {
        $status = $this->localAiManager->getStatus();

        if (!$status['running']) {
            return response()->json([
                'success' => false,
                'message' => 'Ollama ไม่ได้ทำงาน',
                'details' => $status,
            ]);
        }

        $loadedModels = $this->localAiManager->getLoadedModels();

        return response()->json([
            'success' => true,
            'message' => 'เชื่อมต่อ Local AI สำเร็จ',
            'details' => [
                'status' => 'running',
                'uptime' => $status['uptime'],
                'loaded_models' => $loadedModels,
                'endpoint' => $status['endpoint'],
            ],
        ]);
    }

    /**
     * ทดสอบ Cloud Provider
     */
    private function testCloudProvider(AiProvider $provider)
    {
        // TODO: ทดสอบการเชื่อมต่อกับ Cloud Provider แต่ละตัว
        // ต้องมี API key ใน config

        $config = $provider->config ?? [];

        if (empty($config['api_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ API Key',
            ]);
        }

        // ตัวอย่างการทดสอบ (ต้องเพิ่ม logic สำหรับแต่ละ provider)
        return response()->json([
            'success' => true,
            'message' => 'ยังไม่ได้ implement การทดสอบ Cloud Provider',
            'provider' => $provider->display_name,
        ]);
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
}
