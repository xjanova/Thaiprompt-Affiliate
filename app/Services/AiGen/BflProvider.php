<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Black Forest Labs Provider (FLUX Pro)
 *
 * เชื่อมต่อ BFL API โดยตรงสำหรับเจนภาพด้วย FLUX Pro / FLUX 1.1 Pro
 * คุณภาพสูงสุดจากทีมผู้สร้าง FLUX โดยตรง
 *
 * @see https://docs.bfl.ml
 */
class BflProvider extends BaseAiGenProvider
{
    protected const MODELS = [
        'flux-pro-1.1' => 'flux-pro-1.1',
        'flux-pro' => 'flux-pro',
        'flux-dev' => 'flux-dev',
    ];

    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'BFL API key ยังไม่ได้ตั้งค่า'];
        }

        $model = $parameters['model'] ?? 'flux-pro-1.1';

        $data = [
            'prompt' => $prompt,
            'width' => $parameters['width'] ?? 1024,
            'height' => $parameters['height'] ?? 1024,
        ];

        if (isset($parameters['seed'])) {
            $data['seed'] = (int) $parameters['seed'];
        }

        try {
            $response = Http::withHeaders([
                'X-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.bfl.ml/v1/' . $model, $data);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'BFL error: ' . $response->body()];
            }

            $result = $response->json();
            $requestId = $result['id'] ?? null;

            if (!$requestId) {
                return ['success' => false, 'error' => 'ไม่ได้รับ request ID จาก BFL'];
            }

            // รอผลลัพธ์
            $maxAttempts = 60;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'X-Key' => $apiKey,
                ])->get('https://api.bfl.ml/v1/get_result?id=' . $requestId);

                if (!$statusResponse->successful()) {
                    continue;
                }

                $statusData = $statusResponse->json();
                $status = $statusData['status'] ?? 'Pending';

                if ($status === 'Ready') {
                    $imageUrl = $statusData['result']['sample'] ?? null;

                    $images = $imageUrl ? [['url' => $imageUrl, 'thumbnail' => $imageUrl]] : [];

                    return [
                        'success' => true,
                        'generation_id' => $requestId,
                        'status' => 'completed',
                        'images' => $images,
                        'data' => ['model' => $model, 'provider' => 'bfl', 'images' => $images],
                    ];
                }

                if ($status === 'Error') {
                    return ['success' => false, 'error' => 'BFL generation failed'];
                }
            }

            return ['success' => false, 'error' => 'BFL timeout'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'BFL request failed: ' . $e->getMessage()];
        }
    }

    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return ['success' => false, 'error' => 'BFL ไม่รองรับการสร้างวิดีโอ'];
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
            // ทดสอบด้วย get_result endpoint ว่า key ใช้ได้ไหม
            $response = Http::withHeaders([
                'X-Key' => $apiKey,
            ])->timeout(15)->get('https://api.bfl.ml/v1/get_result?id=test');

            // ถ้า 401/403 = key ไม่ถูก, ถ้า 404/422 = key ถูกแต่ ID ไม่มี
            $isValid = $response->status() !== 401 && $response->status() !== 403;

            return [
                'success' => $isValid,
                'message' => $isValid ? 'เชื่อมต่อ BFL สำเร็จ' : 'API key ไม่ถูกต้อง',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()];
        }
    }
}
