<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Leonardo AI Provider
 *
 * เชื่อมต่อ Leonardo AI API สำหรับเจนภาพเน้นคุณภาพสูง
 * โดดเด่นด้าน game art, character design, concept art
 *
 * @see https://docs.leonardo.ai/docs
 */
class LeonardoAiProvider extends BaseAiGenProvider
{
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Leonardo AI API key ยังไม่ได้ตั้งค่า'];
        }

        $data = [
            'prompt' => $prompt,
            'modelId' => $parameters['model_id'] ?? null,
            'width' => $parameters['width'] ?? 1024,
            'height' => $parameters['height'] ?? 1024,
            'num_images' => min($parameters['num_images'] ?? 1, 4),
            'alchemy' => true,
            'photoReal' => ($parameters['style'] ?? '') === 'photorealistic',
        ];

        if (!empty($parameters['negative_prompt'])) {
            $data['negative_prompt'] = $parameters['negative_prompt'];
        }

        try {
            // สร้าง generation
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://cloud.leonardo.ai/api/rest/v1/generations', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Leonardo AI error: ' . $response->body()];
            }

            $result = $response->json();
            $generationId = $result['sdGenerationJob']['generationId'] ?? null;

            if (!$generationId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ generation ID จาก Leonardo AI'];
            }

            // รอผลลัพธ์ (poll สูงสุด 120 วินาที)
            $maxAttempts = 60;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $genData = $statusData['generations_by_pk'] ?? null;

                if ($genData && $genData['status'] === 'COMPLETE') {
                    $images = [];
                    foreach ($genData['generated_images'] ?? [] as $img) {
                        $url = $img['url'] ?? null;
                        if ($url) {
                            $images[] = ['url' => $url, 'thumbnail' => $url];
                        }
                    }

                    return [
                        'success' => true,
                        'generation_id' => $generationId,
                        'status' => 'completed',
                        'images' => $images,
                        'data' => ['provider' => 'leonardo-ai', 'images' => $images],
                    ];
                }

                if ($genData && $genData['status'] === 'FAILED') {
                    return ['success' => false, 'error' => 'Leonardo AI generation failed'];
                }
            }

            return ['success' => false, 'error' => 'Leonardo AI timeout'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Leonardo AI request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Leonardo AI ไม่รองรับการสร้างวิดีโอ'];
    }

    public function checkStatus(string $generationId): array
    {
        $apiKey = $this->getConfig('api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['generations_by_pk']['status'] ?? 'unknown';
                return ['success' => true, 'status' => strtolower($status), 'data' => $data];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(15)->get('https://cloud.leonardo.ai/api/rest/v1/me');

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'เชื่อมต่อ Leonardo AI สำเร็จ' : 'เชื่อมต่อล้มเหลว: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
