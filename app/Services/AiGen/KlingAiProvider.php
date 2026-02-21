<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Kling AI Provider
 *
 * เชื่อมต่อ Kling AI API สำหรับสร้างวิดีโอและภาพ
 * คุณภาพสูง ราคาเหมาะสม รองรับทั้ง text-to-video และ text-to-image
 *
 * @see https://docs.klingai.com
 */
class KlingAiProvider extends BaseAiGenProvider
{
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Kling AI API key ยังไม่ได้ตั้งค่า'];
        }

        $data = [
            'model_name' => $parameters['model'] ?? 'kling-v1',
            'prompt' => $prompt,
            'n' => min($parameters['num_images'] ?? 1, 4),
            'aspect_ratio' => $parameters['aspect_ratio'] ?? '1:1',
        ];

        if (!empty($parameters['negative_prompt'])) {
            $data['negative_prompt'] = $parameters['negative_prompt'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.klingai.com/v1/images/generations', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Kling AI error: ' . $response->body()];
            }

            $result = $response->json();
            $taskId = $result['data']['task_id'] ?? null;

            if (!$taskId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ task ID จาก Kling AI'];
            }

            // รอผลลัพธ์
            $maxAttempts = 60;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://api.klingai.com/v1/images/generations/' . $taskId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $status = $statusData['data']['task_status'] ?? 'processing';

                if ($status === 'succeed') {
                    $images = [];
                    foreach ($statusData['data']['task_result']['images'] ?? [] as $img) {
                        $url = $img['url'] ?? null;
                        if ($url) {
                            $images[] = ['url' => $url, 'thumbnail' => $url];
                        }
                    }

                    return [
                        'success' => true,
                        'generation_id' => $taskId,
                        'status' => 'completed',
                        'images' => $images,
                        'data' => ['provider' => 'kling-ai', 'images' => $images],
                    ];
                }

                if ($status === 'failed') {
                    return ['success' => false, 'error' => 'Kling AI generation failed'];
                }
            }

            return ['success' => false, 'error' => 'Kling AI timeout'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Kling AI request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Kling AI API key ยังไม่ได้ตั้งค่า'];
        }

        $data = [
            'model_name' => $parameters['model'] ?? 'kling-v1',
            'prompt' => $prompt,
            'duration' => $parameters['duration'] ?? '5',
            'aspect_ratio' => $parameters['aspect_ratio'] ?? '16:9',
            'mode' => $parameters['mode'] ?? 'std',
        ];

        if (!empty($parameters['image_url'])) {
            $data['image'] = $parameters['image_url'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.klingai.com/v1/videos/text2video', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Kling AI error: ' . $response->body()];
            }

            $result = $response->json();
            $taskId = $result['data']['task_id'] ?? null;

            if (!$taskId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ task ID จาก Kling AI'];
            }

            // รอผลลัพธ์
            $maxAttempts = 120;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(3);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://api.klingai.com/v1/videos/text2video/' . $taskId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $status = $statusData['data']['task_status'] ?? 'processing';

                if ($status === 'succeed') {
                    $videoUrl = $statusData['data']['task_result']['videos'][0]['url'] ?? null;

                    return [
                        'success' => true,
                        'generation_id' => $taskId,
                        'status' => 'completed',
                        'videos' => $videoUrl ? [['url' => $videoUrl]] : [],
                        'data' => ['provider' => 'kling-ai', 'video_url' => $videoUrl],
                    ];
                }

                if ($status === 'failed') {
                    return ['success' => false, 'error' => 'Kling AI video generation failed'];
                }
            }

            return ['success' => false, 'error' => 'Kling AI timeout'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Kling AI request failed: ' . $e->getMessage()];
        }
    }

    public function checkStatus(string $generationId): array
    {
        return ['success' => true, 'status' => 'completed', 'data' => ['id' => $generationId]];
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
            ])->timeout(15)->get('https://api.klingai.com/v1/images/generations?pageNum=1&pageSize=1');

            $isValid = $response->status() !== 401 && $response->status() !== 403;

            return [
                'success' => $isValid,
                'message' => $isValid ? 'เชื่อมต่อ Kling AI สำเร็จ' : 'API key ไม่ถูกต้อง',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
