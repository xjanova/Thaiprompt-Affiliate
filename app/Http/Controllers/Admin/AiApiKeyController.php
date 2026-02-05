<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Models\AiApiKeySetting;
use App\Models\AiApiKeyUsageLog;
use App\Services\AiApiKeyPoolService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * AI API Key Controller
 *
 * จัดการ API Keys สำหรับ AI Providers ในหน้า Admin
 */
class AiApiKeyController extends Controller
{
    protected AiApiKeyPoolService $poolService;

    public function __construct(AiApiKeyPoolService $poolService)
    {
        $this->poolService = $poolService;
    }

    /**
     * หน้า Dashboard แสดงสถานะ API Keys ทั้งหมด
     */
    public function index(): View
    {
        $dashboardData = $this->poolService->getDashboardData();

        return view('admin.ai-api-keys.index', [
            'summary' => $dashboardData['summary'],
            'providers' => $dashboardData['providers'],
            'rotationModes' => $dashboardData['rotation_modes'],
            'allProviders' => AiApiKey::PROVIDERS,
            'pageTitle' => 'จัดการ AI API Keys',
        ]);
    }

    /**
     * แสดง keys ของ provider ที่ระบุ
     */
    public function provider(string $provider): View
    {
        $providerName = AiApiKey::PROVIDERS[$provider] ?? $provider;
        $keys = $this->poolService->getKeysWithStats($provider);
        $settings = AiApiKeySetting::forProvider($provider);
        $stats = $this->poolService->getProviderStats($provider);

        return view('admin.ai-api-keys.provider', [
            'provider' => $provider,
            'providerName' => $providerName,
            'keys' => $keys,
            'settings' => $settings,
            'stats' => $stats,
            'rotationModes' => AiApiKey::ROTATION_MODES,
            'pageTitle' => "จัดการ {$providerName} API Keys",
        ]);
    }

    /**
     * ฟอร์มสร้าง key ใหม่
     */
    public function create(?string $provider = null): View
    {
        return view('admin.ai-api-keys.create', [
            'provider' => $provider,
            'providers' => AiApiKey::PROVIDERS,
            'pageTitle' => 'เพิ่ม API Key ใหม่',
        ]);
    }

    /**
     * บันทึก key ใหม่
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'provider' => 'required|string|in:' . implode(',', array_keys(AiApiKey::PROVIDERS)),
            'api_key' => 'required|string|min:10',
            'priority' => 'nullable|integer|min:0|max:100',
            'tokens_limit_daily' => 'nullable|integer|min:0',
            'tokens_limit_monthly' => 'nullable|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $key = $this->poolService->addKey($validated);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่ม API Key สำเร็จ',
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'provider' => $key->provider,
            ],
        ]);
    }

    /**
     * ฟอร์มแก้ไข key
     */
    public function edit(int $id): View
    {
        $key = AiApiKey::findOrFail($id);

        return view('admin.ai-api-keys.edit', [
            'key' => $key,
            'providers' => AiApiKey::PROVIDERS,
            'pageTitle' => "แก้ไข {$key->name}",
        ]);
    }

    /**
     * อัพเดท key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'api_key' => 'nullable|string|min:10',  // nullable เพื่อไม่ต้องส่งถ้าไม่เปลี่ยน
            'priority' => 'nullable|integer|min:0|max:100',
            'tokens_limit_daily' => 'nullable|integer|min:0',
            'tokens_limit_monthly' => 'nullable|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // ลบ api_key ถ้าว่าง (ไม่เปลี่ยน)
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $key = $this->poolService->updateKey($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดท API Key สำเร็จ',
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'is_active' => $key->is_active,
            ],
        ]);
    }

    /**
     * ลบ key
     */
    public function destroy(int $id): JsonResponse
    {
        $this->poolService->deleteKey($id);

        return response()->json([
            'success' => true,
            'message' => 'ลบ API Key สำเร็จ',
        ]);
    }

    /**
     * Toggle enable/disable key
     */
    public function toggle(int $id): JsonResponse
    {
        $key = $this->poolService->toggleKey($id);

        return response()->json([
            'success' => true,
            'message' => $key->is_active ? 'เปิดใช้งาน API Key แล้ว' : 'ปิดใช้งาน API Key แล้ว',
            'data' => [
                'id' => $key->id,
                'is_active' => $key->is_active,
            ],
        ]);
    }

    /**
     * Reset error counters
     */
    public function resetErrors(int $id): JsonResponse
    {
        $key = AiApiKey::findOrFail($id);
        $key->update([
            'consecutive_errors' => 0,
            'last_error' => null,
            'last_error_at' => null,
            'disabled_until' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ต error counters สำเร็จ',
        ]);
    }

    /**
     * อัพเดท settings ของ provider
     */
    public function updateSettings(Request $request, string $provider): JsonResponse
    {
        $validated = $request->validate([
            'rotation_mode' => 'required|string|in:' . implode(',', array_keys(AiApiKey::ROTATION_MODES)),
            'max_consecutive_errors' => 'nullable|integer|min:1|max:10',
            'disable_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'auto_disable_on_limit' => 'nullable|boolean',
            'default_model' => 'nullable|string|max:100',
        ]);

        $settings = $this->poolService->updateProviderSettings($provider, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดท settings สำเร็จ',
            'data' => [
                'rotation_mode' => $settings->rotation_mode,
            ],
        ]);
    }

    /**
     * ดึงสถิติแบบ real-time (สำหรับ AJAX refresh)
     */
    public function stats(?string $provider = null): JsonResponse
    {
        if ($provider) {
            $stats = $this->poolService->getProviderStats($provider);
            $keys = $this->poolService->getKeysWithStats($provider);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'keys' => $keys,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->poolService->getDashboardData(),
        ]);
    }

    /**
     * ดึง usage logs
     */
    public function logs(Request $request, ?int $keyId = null): View
    {
        $query = AiApiKeyUsageLog::with('apiKey')
            ->orderByDesc('created_at');

        if ($keyId) {
            $query->where('ai_api_key_id', $keyId);
        }

        if ($request->filled('provider')) {
            $query->whereHas('apiKey', function ($q) use ($request) {
                $q->where('provider', $request->provider);
            });
        }

        if ($request->filled('success')) {
            $query->where('is_success', $request->success === 'true');
        }

        $logs = $query->paginate(50);

        return view('admin.ai-api-keys.logs', [
            'logs' => $logs,
            'keyId' => $keyId,
            'providers' => AiApiKey::PROVIDERS,
            'pageTitle' => 'Usage Logs',
        ]);
    }

    /**
     * ทดสอบ API Key
     */
    public function test(int $id): JsonResponse
    {
        $key = AiApiKey::findOrFail($id);

        try {
            // ทดสอบตาม provider
            $result = $this->testApiKey($key);

            return response()->json([
                'success' => true,
                'message' => 'API Key ใช้งานได้ปกติ',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API Key ไม่สามารถใช้งานได้: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ทดสอบ API Key ตาม provider
     */
    protected function testApiKey(AiApiKey $key): array
    {
        $startTime = microtime(true);

        // ทดสอบตาม provider
        switch ($key->provider) {
            case 'grok':
                $response = $this->testGrokApi($key->api_key);
                break;
            case 'groq':
                $response = $this->testGroqApi($key->api_key);
                break;
            case 'openai':
                $response = $this->testOpenAiApi($key->api_key);
                break;
            case 'gemini':
                $response = $this->testGeminiApi($key->api_key);
                break;
            default:
                throw new \Exception('Provider ไม่รองรับการทดสอบ');
        }

        $responseTime = round((microtime(true) - $startTime) * 1000);

        return [
            'provider' => $key->provider,
            'response_time_ms' => $responseTime,
            'status' => 'ok',
        ];
    }

    /**
     * ทดสอบ Grok API
     */
    protected function testGrokApi(string $apiKey): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://api.x.ai/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * ทดสอบ Groq API
     */
    protected function testGroqApi(string $apiKey): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://api.groq.com/openai/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * ทดสอบ OpenAI API
     */
    protected function testOpenAiApi(string $apiKey): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * ทดสอบ Gemini API
     */
    protected function testGeminiApi(string $apiKey): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}", [
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }
}
