<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * จัดการ AI Providers (เฉพาะผู้ให้บริการแบบ Cloud)
 *
 * หมายเหตุ: ระบบ AI แบบ local (Ollama / llama.cpp / PostXAgent / เช่า GPU cloud)
 * ถูกถอดออกจากระบบทั้งหมดแล้ว เหลือเฉพาะ provider ที่เรียกผ่าน API เท่านั้น
 */
class AiProviderManagementController extends Controller
{
    /**
     * หน้าจัดการ Providers
     */
    public function index()
    {
        $providers = AiProvider::with(['models'])->get();

        // Logo mapping สำหรับ Providers
        $providerLogos = $this->getProviderLogos();

        return view('admin.ai-providers.index', compact('providers', 'providerLogos'));
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
            'meta' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Meta_Platforms_Inc._logo.svg/200px-Meta_Platforms_Inc._logo.svg.png',
        ];
    }

    /**
     * เปิด/ปิด Provider
     */
    public function toggleProvider($providerId)
    {
        $provider = AiProvider::findOrFail($providerId);

        $provider->is_active = ! $provider->is_active;
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

        return $this->testCloudProvider($provider);
    }

    /**
     * ทดสอบ Cloud Provider
     */
    private function testCloudProvider(AiProvider $provider)
    {
        $config = $provider->config ?? [];

        if (empty($config['api_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ API Key - กรุณาตั้งค่า API Key ก่อน',
            ]);
        }

        try {
            $endpoint = $provider->api_endpoint;
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$config['api_key'],
            ];

            // ทดสอบดึงรายการ models
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->get($endpoint.'/models');

            if ($response->successful()) {
                $models = $response->json()['data'] ?? [];

                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อ '.$provider->display_name.' สำเร็จ!',
                    'details' => [
                        'models_available' => count($models),
                        'endpoint' => $endpoint,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'การเชื่อมต่อล้มเหลว: '.($response->json()['error']['message'] ?? 'Unknown error'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
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

            if (! empty($config['api_key'])) {
                $headers['Authorization'] = 'Bearer '.$config['api_key'];
            }

            $startTime = microtime(true);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($endpoint.'/chat/completions', [
                    'model' => $model->model_identifier,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'คุณเป็น AI Assistant ของระบบ Thaiprompt Affiliate ตอบภาษาไทยสั้นๆ กระชับ เข้าใจง่าย',
                        ],
                        [
                            'role' => 'user',
                            'content' => $request->message,
                        ],
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
                'message' => 'AI ไม่ตอบกลับ: '.($response->json()['error']['message'] ?? 'Unknown error'),
            ]);

        } catch (\Exception $e) {
            Log::error('Test Chat Error', [
                'provider' => $provider->name,
                'model' => $model->model_identifier,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);
        }
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

        $model->is_active = ! $model->is_active;
        $model->save();

        return response()->json([
            'success' => true,
            'message' => $model->is_active ? 'เปิดใช้งาน Model แล้ว' : 'ปิดใช้งาน Model แล้ว',
            'is_active' => $model->is_active,
        ]);
    }
}
