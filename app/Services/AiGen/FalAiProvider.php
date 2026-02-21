<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FAL AI Provider
 *
 * เชื่อมต่อ FAL AI สำหรับเจนภาพด้วย FLUX Pro, FLUX Dev, SDXL Lightning
 * ความเร็วสูงมาก (inference < 2 วินาที)
 *
 * @see https://fal.ai/docs
 */
class FalAiProvider extends BaseAiGenProvider
{
    protected const MODELS = [
        'flux-pro' => 'fal-ai/flux-pro/v1.1',
        'flux-dev' => 'fal-ai/flux/dev',
        'flux-schnell' => 'fal-ai/flux/schnell',
        'flux-realism' => 'fal-ai/flux-realism',
    ];

    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'FAL AI API key ยังไม่ได้ตั้งค่า'];
        }

        $modelKey = $parameters['model'] ?? 'flux-schnell';
        $model = self::MODELS[$modelKey] ?? self::MODELS['flux-schnell'];

        $data = [
            'prompt' => $prompt,
            'image_size' => $parameters['size'] ?? 'landscape_4_3',
            'num_images' => min($parameters['num_images'] ?? 1, 4),
            'num_inference_steps' => $parameters['steps'] ?? 4,
        ];

        if (!empty($parameters['negative_prompt'])) {
            $data['negative_prompt'] = $parameters['negative_prompt'];
        }

        if (isset($parameters['seed'])) {
            $data['seed'] = (int) $parameters['seed'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://fal.run/' . $model, $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'FAL AI error: ' . $response->body()];
            }

            $result = $response->json();
            $images = [];

            if (isset($result['images']) && is_array($result['images'])) {
                foreach ($result['images'] as $index => $img) {
                    $imageUrl = $img['url'] ?? null;
                    if ($imageUrl) {
                        $images[] = ['url' => $imageUrl, 'thumbnail' => $imageUrl];
                    }
                }
            }

            if (empty($images)) {
                return ['success' => false, 'error' => 'ไม่ได้รับภาพจาก FAL AI'];
            }

            return [
                'success' => true,
                'generation_id' => $result['request_id'] ?? Str::uuid()->toString(),
                'status' => 'completed',
                'images' => $images,
                'data' => ['model' => $model, 'provider' => 'fal-ai', 'images' => $images],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'FAL AI request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'FAL AI ยังไม่รองรับการสร้างวิดีโอในเวอร์ชันนี้'];
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
            // ทดสอบ FLUX schnell ด้วย prompt เล็กๆ
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://fal.run/fal-ai/flux/schnell', [
                'prompt' => 'test',
                'image_size' => 'square',
                'num_images' => 1,
                'num_inference_steps' => 1,
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'เชื่อมต่อ FAL AI สำเร็จ' : 'เชื่อมต่อล้มเหลว: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
