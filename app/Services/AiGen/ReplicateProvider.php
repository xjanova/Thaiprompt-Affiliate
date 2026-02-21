<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Replicate Provider
 *
 * เชื่อมต่อ Replicate API สำหรับรันโมเดล AI หลากหลายแบบ
 * รองรับ FLUX, SDXL, และโมเดลอื่นๆ อีกหลายร้อยตัว
 *
 * @see https://replicate.com/docs/reference/http
 */
class ReplicateProvider extends BaseAiGenProvider
{
    protected const MODELS = [
        'flux-schnell' => 'black-forest-labs/flux-schnell',
        'flux-dev' => 'black-forest-labs/flux-dev',
        'flux-pro' => 'black-forest-labs/flux-pro',
        'sdxl' => 'stability-ai/sdxl:7762fd07cf82c948538e41f63f77d685e02b063e37e496e96eefd46c929f9bdc',
    ];

    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Replicate API key ยังไม่ได้ตั้งค่า'];
        }

        $modelKey = $parameters['model'] ?? 'flux-schnell';
        $model = self::MODELS[$modelKey] ?? self::MODELS['flux-schnell'];

        $input = [
            'prompt' => $prompt,
            'num_outputs' => min($parameters['num_images'] ?? 1, 4),
        ];

        if (!empty($parameters['negative_prompt'])) {
            $input['negative_prompt'] = $parameters['negative_prompt'];
        }

        if (isset($parameters['seed'])) {
            $input['seed'] = (int) $parameters['seed'];
        }

        try {
            // สร้าง prediction
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.replicate.com/v1/models/' . $model . '/predictions', [
                'input' => $input,
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Replicate error: ' . $response->body()];
            }

            $result = $response->json();
            $predictionId = $result['id'] ?? null;

            if (!$predictionId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ prediction ID จาก Replicate'];
            }

            // รอผลลัพธ์ (poll สูงสุด 120 วินาที)
            $maxAttempts = 60;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://api.replicate.com/v1/predictions/' . $predictionId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $status = $statusData['status'] ?? 'processing';

                if ($status === 'succeeded') {
                    $output = $statusData['output'] ?? [];
                    $images = [];

                    if (is_array($output)) {
                        foreach ($output as $imageUrl) {
                            if (is_string($imageUrl)) {
                                $images[] = ['url' => $imageUrl, 'thumbnail' => $imageUrl];
                            }
                        }
                    }

                    return [
                        'success' => true,
                        'generation_id' => $predictionId,
                        'status' => 'completed',
                        'images' => $images,
                        'data' => ['model' => $model, 'provider' => 'replicate', 'images' => $images],
                    ];
                }

                if ($status === 'failed' || $status === 'canceled') {
                    return ['success' => false, 'error' => 'Replicate generation failed: ' . ($statusData['error'] ?? 'Unknown error')];
                }
            }

            return ['success' => false, 'error' => 'Replicate timeout - generation ใช้เวลานานเกินไป'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Replicate request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Replicate video generation ยังไม่รองรับในเวอร์ชันนี้'];
    }

    public function checkStatus(string $generationId): array
    {
        $apiKey = $this->getConfig('api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.replicate.com/v1/predictions/' . $generationId);

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'status' => $data['status'] ?? 'unknown', 'data' => $data];
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
            ])->timeout(15)->get('https://api.replicate.com/v1/account');

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'เชื่อมต่อ Replicate สำเร็จ' : 'เชื่อมต่อล้มเหลว: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
