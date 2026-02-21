<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Minimax Provider (Hailuo AI)
 *
 * เชื่อมต่อ Minimax/Hailuo API สำหรับสร้างวิดีโอคุณภาพสูง
 * โดดเด่นเรื่อง realistic video generation
 *
 * @see https://platform.minimaxi.com/document/video-generation
 */
class MinimaxProvider extends BaseAiGenProvider
{
    public function generateImage(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Minimax/Hailuo เน้นการสร้างวิดีโอ กรุณาใช้ provider อื่นสำหรับภาพ'];
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Minimax API key ยังไม่ได้ตั้งค่า'];
        }

        $data = [
            'model' => $parameters['model'] ?? 'video-01',
            'prompt' => $prompt,
        ];

        if (!empty($parameters['image_url'])) {
            $data['first_frame_image'] = $parameters['image_url'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.minimaxi.chat/v1/video_generation', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Minimax error: ' . $response->body()];
            }

            $result = $response->json();
            $taskId = $result['task_id'] ?? null;

            if (!$taskId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ task ID จาก Minimax'];
            }

            // รอผลลัพธ์
            $maxAttempts = 120;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(3);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://api.minimaxi.chat/v1/query/video_generation?task_id=' . $taskId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $status = $statusData['status'] ?? 'Processing';

                if ($status === 'Success') {
                    $fileId = $statusData['file_id'] ?? null;
                    $videoUrl = $fileId ? "https://api.minimaxi.chat/v1/files/retrieve?file_id={$fileId}" : null;

                    return [
                        'success' => true,
                        'generation_id' => $taskId,
                        'status' => 'completed',
                        'videos' => $videoUrl ? [['url' => $videoUrl]] : [],
                        'data' => ['provider' => 'minimax', 'video_url' => $videoUrl, 'file_id' => $fileId],
                    ];
                }

                if ($status === 'Fail') {
                    return ['success' => false, 'error' => 'Minimax generation failed'];
                }
            }

            return ['success' => false, 'error' => 'Minimax timeout'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Minimax request failed: ' . $e->getMessage()];
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
            ])->timeout(15)->get('https://api.minimaxi.chat/v1/files/list?purpose=video_generation');

            $isValid = $response->status() !== 401 && $response->status() !== 403;

            return [
                'success' => $isValid,
                'message' => $isValid ? 'เชื่อมต่อ Minimax สำเร็จ' : 'API key ไม่ถูกต้อง',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
