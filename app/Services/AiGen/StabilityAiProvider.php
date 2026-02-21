<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stability AI Provider (Stable Diffusion 3.5, SDXL)
 *
 * เชื่อมต่อ Stability AI REST API สำหรับเจนภาพด้วย SD3.5 / SDXL
 *
 * @see https://platform.stability.ai/docs/api-reference
 */
class StabilityAiProvider extends BaseAiGenProvider
{
    protected const MODELS = [
        'sd3.5-large' => 'sd3.5-large',
        'sd3.5-medium' => 'sd3.5-medium',
        'sd3-medium' => 'sd3-medium',
    ];

    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Stability AI API key ยังไม่ได้ตั้งค่า'];
        }

        $model = $parameters['model'] ?? 'sd3.5-large';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->timeout(120)->post('https://api.stability.ai/v2beta/stable-image/generate/sd3', [
                'prompt' => $prompt,
                'model' => self::MODELS[$model] ?? 'sd3.5-large',
                'output_format' => 'png',
                'aspect_ratio' => $parameters['aspect_ratio'] ?? '1:1',
                'negative_prompt' => $parameters['negative_prompt'] ?? '',
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Stability AI error: ' . $response->body()];
            }

            $result = $response->json();
            $images = [];

            if (isset($result['image'])) {
                $saved = $this->saveBase64Image($result['image'], 'stability-ai', 0);
                if ($saved) {
                    $images[] = ['url' => $saved['url'], 'thumbnail' => $saved['url']];
                }
            }

            if (empty($images)) {
                return ['success' => false, 'error' => 'ไม่ได้รับภาพจาก Stability AI'];
            }

            return [
                'success' => true,
                'generation_id' => $result['seed'] ?? Str::uuid()->toString(),
                'status' => 'completed',
                'images' => $images,
                'data' => ['model' => $model, 'provider' => 'stability-ai', 'images' => $images],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Stability AI request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Stability AI ไม่รองรับการสร้างวิดีโอ'];
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
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(15)->get('https://api.stability.ai/v1/user/account');

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'เชื่อมต่อ Stability AI สำเร็จ' : 'เชื่อมต่อล้มเหลว: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }

    protected function saveBase64Image(string $base64Data, string $providerName, int $index = 0): ?array
    {
        if (empty($base64Data)) {
            return null;
        }

        try {
            $imageData = base64_decode($base64Data);
            $filename = $providerName . '_' . date('Ymd_His') . '_' . $index . '_' . Str::random(8) . '.png';
            $path = 'ai-gen/' . $providerName . '/' . date('Y/m') . '/' . $filename;
            Storage::disk('public')->put($path, $imageData);

            return ['url' => Storage::disk('public')->url($path), 'path' => $path, 'filename' => $filename];
        } catch (\Exception $e) {
            return null;
        }
    }
}
