<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Runway ML Provider (Gen-3 Alpha)
 *
 * เชื่อมต่อ Runway ML API สำหรับสร้างวิดีโอด้วย AI ระดับ top tier
 * รองรับ text-to-video และ image-to-video
 *
 * @see https://docs.dev.runwayml.com
 */
class RunwayMlProvider extends BaseAiGenProvider
{
    public function generateImage(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'Runway ML เน้นการสร้างวิดีโอ กรุณาใช้ provider อื่นสำหรับภาพ'];
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'Runway ML API key ยังไม่ได้ตั้งค่า'];
        }

        $data = [
            'promptText' => $prompt,
            'model' => $parameters['model'] ?? 'gen3a_turbo',
            'duration' => min($parameters['duration'] ?? 5, 10),
            'ratio' => $parameters['aspect_ratio'] ?? '16:9',
        ];

        if (!empty($parameters['image_url'])) {
            $data['promptImage'] = $parameters['image_url'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'X-Runway-Version' => '2024-11-06',
            ])->timeout(30)->post('https://api.dev.runwayml.com/v1/image_to_video', $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Runway ML error: ' . $response->body()];
            }

            $result = $response->json();
            $taskId = $result['id'] ?? null;

            if (!$taskId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ task ID จาก Runway ML'];
            }

            // รอผลลัพธ์ (poll สูงสุด 180 วินาที - วิดีโอใช้เวลานาน)
            $maxAttempts = 90;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'X-Runway-Version' => '2024-11-06',
                ])->get('https://api.dev.runwayml.com/v1/tasks/' . $taskId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $status = $statusData['status'] ?? 'PENDING';

                if ($status === 'SUCCEEDED') {
                    $videoUrl = $statusData['output'][0] ?? null;

                    return [
                        'success' => true,
                        'generation_id' => $taskId,
                        'status' => 'completed',
                        'videos' => $videoUrl ? [['url' => $videoUrl]] : [],
                        'data' => ['provider' => 'runway-ml', 'video_url' => $videoUrl],
                    ];
                }

                if ($status === 'FAILED') {
                    return ['success' => false, 'error' => 'Runway ML generation failed: ' . ($statusData['failure'] ?? 'Unknown')];
                }
            }

            return ['success' => false, 'error' => 'Runway ML timeout - วิดีโอใช้เวลานานเกินไป'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Runway ML request failed: ' . $e->getMessage()];
        }
    }

    public function checkStatus(string $generationId): array
    {
        $apiKey = $this->getConfig('api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'X-Runway-Version' => '2024-11-06',
            ])->get('https://api.dev.runwayml.com/v1/tasks/' . $generationId);

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'status' => strtolower($data['status'] ?? 'unknown'), 'data' => $data];
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
            // Runway ไม่มี /me endpoint - ลองเช็คด้วย GET tasks
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'X-Runway-Version' => '2024-11-06',
            ])->timeout(15)->get('https://api.dev.runwayml.com/v1/tasks?limit=1');

            $isValid = $response->status() !== 401 && $response->status() !== 403;

            return [
                'success' => $isValid,
                'message' => $isValid ? 'เชื่อมต่อ Runway ML สำเร็จ' : 'API key ไม่ถูกต้อง',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
