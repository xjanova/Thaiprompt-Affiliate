<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Ideogram Provider
 *
 * เชื่อมต่อ Ideogram API สำหรับเจนภาพที่เน้นข้อความในภาพ (text rendering)
 * โดดเด่นเรื่องการสร้างภาพที่มีตัวอักษรถูกต้อง
 *
 * @see https://developer.ideogram.ai/api-reference
 */
class IdeogramProvider extends BaseAiGenProvider
{
    protected const MODELS = [
        'v2' => 'V_2',
        'v2-turbo' => 'V_2_TURBO',
    ];

    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Ideogram API key ยังไม่ได้ตั้งค่า'];
        }

        $model = self::MODELS[$parameters['model'] ?? 'v2-turbo'] ?? 'V_2_TURBO';

        $data = [
            'image_request' => [
                'prompt' => $prompt,
                'model' => $model,
                'aspect_ratio' => $parameters['aspect_ratio'] ?? 'ASPECT_1_1',
                'magic_prompt_option' => 'AUTO',
            ],
        ];

        if (!empty($parameters['negative_prompt'])) {
            $data['image_request']['negative_prompt'] = $parameters['negative_prompt'];
        }

        if (!empty($parameters['style'])) {
            $data['image_request']['style_type'] = strtoupper($parameters['style']);
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.ideogram.ai/generate', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Ideogram error: ' . $response->body()];
            }

            $result = $response->json();
            $images = [];

            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $img) {
                    $url = $img['url'] ?? null;
                    if ($url) {
                        $images[] = ['url' => $url, 'thumbnail' => $url];
                    }
                }
            }

            if (empty($images)) {
                return ['success' => false, 'error' => 'ไม่ได้รับภาพจาก Ideogram'];
            }

            return [
                'success' => true,
                'generation_id' => $result['created'] ?? Str::uuid()->toString(),
                'status' => 'completed',
                'images' => $images,
                'data' => ['model' => $model, 'provider' => 'ideogram', 'images' => $images],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Ideogram request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Ideogram ไม่รองรับการสร้างวิดีโอ'];
    }

    public function checkStatus(string $generationId): array
    {
        return ['success' => true, 'status' => 'completed', 'data' => ['id' => $generationId, 'status' => 'completed']];
    }

    public function getResult(string $generationId): array
    {
        return $this->checkStatus($generationId);
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('api_key'));
    }

    public function testConnection(): array
    {
        $apiKey = $this->getConfig('api_key');
        if (!$apiKey) {
            return ['success' => false, 'message' => 'API key ยังไม่ได้ตั้งค่า'];
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $apiKey,
            ])->timeout(15)->get('https://api.ideogram.ai/describe');

            // Ideogram อาจไม่มี endpoint เช็ค - ถ้า 401 = key ไม่ถูก, อื่นๆ = ใช้ได้
            $isValid = $response->status() !== 401 && $response->status() !== 403;

            return [
                'success' => $isValid,
                'message' => $isValid ? 'เชื่อมต่อ Ideogram สำเร็จ' : 'API key ไม่ถูกต้อง',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
