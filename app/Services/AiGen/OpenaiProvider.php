<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * OpenAI Provider (DALL-E 3 / gpt-image-1)
 *
 * เชื่อมต่อ OpenAI API สำหรับเจนภาพด้วย DALL-E 3 และ gpt-image-1
 *
 * @see https://platform.openai.com/docs/guides/images
 */
class OpenaiProvider extends BaseAiGenProvider
{
    /**
     * โมเดลที่รองรับ
     */
    protected const MODELS = [
        'dall-e-3' => 'dall-e-3',
        'gpt-image-1' => 'gpt-image-1',
    ];

    /**
     * ขนาดภาพที่รองรับ
     */
    protected const SIZES = [
        '1024x1024' => '1024x1024',
        '1792x1024' => '1792x1024',
        '1024x1792' => '1024x1792',
    ];

    /**
     * เจนภาพด้วย OpenAI API
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');
        $baseUrl = $this->getConfig('api_endpoint', 'https://api.openai.com/v1');

        if (! $apiKey) {
            return ['success' => false, 'error' => 'OpenAI API key ยังไม่ได้ตั้งค่า'];
        }

        $model = self::MODELS[$parameters['model'] ?? 'dall-e-3'] ?? 'dall-e-3';
        $size = self::SIZES[$parameters['size'] ?? '1024x1024'] ?? '1024x1024';

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => min($parameters['num_images'] ?? 1, 4),
            'size' => $size,
        ];

        if ($model === 'dall-e-3') {
            // ⚠️ response_format ใส่ได้เฉพาะ dall-e-3 — gpt-image-1 ไม่รับพารามิเตอร์นี้ (400)
            //    และคืน b64_json เป็น default อยู่แล้ว จึงอ่านผลลัพธ์แบบเดียวกันได้
            $data['response_format'] = 'b64_json';
            $data['quality'] = $parameters['quality'] ?? 'standard';
            $data['style'] = $parameters['style'] ?? 'vivid';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($baseUrl.'/images/generations', $data);

            if (! $response->successful()) {
                $errorBody = $response->json();

                return ['success' => false, 'error' => 'OpenAI error: '.($errorBody['error']['message'] ?? $response->body())];
            }

            $result = $response->json();
            $images = [];

            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $index => $imageData) {
                    $saved = $this->saveBase64Image($imageData['b64_json'] ?? '', 'openai', $index);
                    if ($saved) {
                        $images[] = ['url' => $saved['url'], 'thumbnail' => $saved['url']];
                    }
                }
            }

            if (empty($images)) {
                return ['success' => false, 'error' => 'ไม่ได้รับภาพจาก OpenAI'];
            }

            return [
                'success' => true,
                'generation_id' => $result['created'] ?? Str::uuid()->toString(),
                'status' => 'completed',
                'images' => $images,
                'data' => ['model' => $model, 'provider' => 'openai', 'images' => $images],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'OpenAI request failed: '.$e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'OpenAI ไม่รองรับการสร้างวิดีโอ'];
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
        return ! empty($this->getConfig('api_key'));
    }

    public function testConnection(): array
    {
        $apiKey = $this->getConfig('api_key');
        if (! $apiKey) {
            return ['success' => false, 'message' => 'API key ยังไม่ได้ตั้งค่า'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(15)->get('https://api.openai.com/v1/models');

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'เชื่อมต่อ OpenAI สำเร็จ' : 'เชื่อมต่อล้มเหลว: '.$response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: '.$e->getMessage()];
        }
    }

    protected function saveBase64Image(string $base64Data, string $providerName, int $index = 0): ?array
    {
        if (empty($base64Data)) {
            return null;
        }

        try {
            $imageData = base64_decode($base64Data);
            $filename = $providerName.'_'.date('Ymd_His').'_'.$index.'_'.Str::random(8).'.png';
            $path = 'ai-gen/'.$providerName.'/'.date('Y/m').'/'.$filename;
            Storage::disk('public')->put($path, $imageData);

            return ['url' => Storage::disk('public')->url($path), 'path' => $path, 'filename' => $filename];
        } catch (\Exception $e) {
            return null;
        }
    }
}
