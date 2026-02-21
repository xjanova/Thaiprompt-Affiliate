<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Luma AI Provider (Dream Machine)
 *
 * เชื่อมต่อ Luma AI API สำหรับสร้างวิดีโอด้วย Dream Machine
 * คุณภาพสูง เหมาะสำหรับ cinematic content
 *
 * @see https://docs.lumalabs.ai/docs/api
 */
class LumaAiProvider extends BaseAiGenProvider
{
    public function generateImage(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Luma AI เน้นการสร้างวิดีโอ กรุณาใช้ provider อื่นสำหรับภาพ'];
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Luma AI API key ยังไม่ได้ตั้งค่า'];
        }

        $data = [
            'prompt' => $prompt,
            'aspect_ratio' => $parameters['aspect_ratio'] ?? '16:9',
            'loop' => $parameters['loop'] ?? false,
        ];

        if (!empty($parameters['image_url'])) {
            $data['keyframes'] = [
                'frame0' => ['type' => 'image', 'url' => $parameters['image_url']],
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.lumalabs.ai/dream-machine/v1/generations', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Luma AI error: ' . $response->body()];
            }

            $result = $response->json();
            $generationId = $result['id'] ?? null;

            if (!$generationId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ generation ID จาก Luma AI'];
            }

            // รอผลลัพธ์
            $maxAttempts = 90;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://api.lumalabs.ai/dream-machine/v1/generations/' . $generationId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $state = $statusData['state'] ?? 'queued';

                if ($state === 'completed') {
                    $videoUrl = $statusData['assets']['video'] ?? null;

                    return [
                        'success' => true,
                        'generation_id' => $generationId,
                        'status' => 'completed',
                        'videos' => $videoUrl ? [['url' => $videoUrl]] : [],
                        'data' => ['provider' => 'luma-ai', 'video_url' => $videoUrl],
                    ];
                }

                if ($state === 'failed') {
                    return ['success' => false, 'error' => 'Luma AI generation failed: ' . ($statusData['failure_reason'] ?? 'Unknown')];
                }
            }

            return ['success' => false, 'error' => 'Luma AI timeout'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Luma AI request failed: ' . $e->getMessage()];
        }
    }

    public function checkStatus(string $generationId): array
    {
        $apiKey = $this->getConfig('api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.lumalabs.ai/dream-machine/v1/generations/' . $generationId);

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'status' => $data['state'] ?? 'unknown', 'data' => $data];
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
            ])->timeout(15)->get('https://api.lumalabs.ai/dream-machine/v1/generations?limit=1');

            $isValid = $response->status() !== 401 && $response->status() !== 403;

            return [
                'success' => $isValid,
                'message' => $isValid ? 'เชื่อมต่อ Luma AI สำเร็จ' : 'API key ไม่ถูกต้อง',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
