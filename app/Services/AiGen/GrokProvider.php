<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Grok Provider (xAI Aurora)
 *
 * เชื่อมต่อ xAI API สำหรับเจนภาพด้วย Grok Aurora
 * ใช้ OpenAI-compatible API ($0.07/ภาพ - คุณภาพสูง)
 *
 * @see https://docs.x.ai/docs/guides/image-generations
 */
class GrokProvider extends BaseAiGenProvider
{
    /**
     * โมเดลที่รองรับ
     */
    protected const MODELS = [
        'grok-2-image' => 'grok-2-image',
    ];

    /**
     * เจนภาพด้วย xAI Grok Aurora API
     *
     * xAI ใช้ OpenAI-compatible API format
     * ราคา $0.07 ต่อภาพ - คุณภาพสูงมาก
     *
     * @param string $prompt คำอธิบายภาพที่ต้องการเจน
     * @param array $parameters พารามิเตอร์เพิ่มเติม (size, style, model, n)
     * @return array ผลลัพธ์การเจนภาพ
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');
        $baseUrl = $this->getConfig('api_endpoint', 'https://api.x.ai/v1');

        if (! $apiKey) {
            return [
                'success' => false,
                'error' => 'xAI (Grok) API key ยังไม่ได้ตั้งค่า',
            ];
        }

        // เลือกโมเดล
        $model = $parameters['model'] ?? 'grok-2-image';

        // เตรียมข้อมูลส่ง API (OpenAI-compatible format)
        $data = [
            'model' => $model,
            'prompt' => $this->enhancePrompt($prompt, $parameters['style'] ?? null),
            'n' => min($parameters['num_images'] ?? 1, 10),
            'response_format' => 'b64_json',
        ];

        // Grok รองรับ size ในรูปแบบ string
        if (isset($parameters['size'])) {
            $data['size'] = $parameters['size'];
        }

        $headers = [
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(120)
                ->post($baseUrl.'/images/generations', $data);

            if (! $response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();

                return [
                    'success' => false,
                    'error' => 'Grok error: '.$errorMsg,
                ];
            }

            $result = $response->json();
            $images = [];

            // xAI ส่ง base64 กลับมา
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $index => $imageData) {
                    // Grok อาจส่ง b64_json หรือ url
                    if (isset($imageData['b64_json'])) {
                        $savedImage = $this->saveBase64Image(
                            $imageData['b64_json'],
                            'grok',
                            $index
                        );
                        if ($savedImage) {
                            $images[] = [
                                'url' => $savedImage['url'],
                                'thumbnail' => $savedImage['url'],
                            ];
                        }
                    } elseif (isset($imageData['url'])) {
                        // ดาวน์โหลดจาก URL แล้วบันทึก
                        $savedImage = $this->saveFromUrl(
                            $imageData['url'],
                            'grok',
                            $index
                        );
                        if ($savedImage) {
                            $images[] = [
                                'url' => $savedImage['url'],
                                'thumbnail' => $savedImage['url'],
                            ];
                        }
                    }
                }
            }

            if (empty($images)) {
                return [
                    'success' => false,
                    'error' => 'ไม่ได้รับภาพจาก Grok',
                ];
            }

            return [
                'success' => true,
                'generation_id' => $result['id'] ?? Str::uuid()->toString(),
                'status' => 'completed',
                'images' => $images,
                'data' => [
                    'model' => $model,
                    'provider' => 'grok',
                    'images' => $images,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Grok request failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Grok ไม่รองรับ video generation
     *
     * @param string $prompt
     * @param array $parameters
     * @return array
     */
    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return [
            'success' => false,
            'error' => 'Grok ไม่รองรับการสร้างวิดีโอ กรุณาใช้ provider อื่น',
        ];
    }

    /**
     * เช็คสถานะการเจน (Grok เจนเสร็จทันทีไม่ต้อง poll)
     *
     * @param string $generationId
     * @return array
     */
    public function checkStatus(string $generationId): array
    {
        return [
            'success' => true,
            'status' => 'completed',
            'data' => [
                'id' => $generationId,
                'status' => 'completed',
            ],
        ];
    }

    /**
     * ดึงผลลัพธ์ตาม ID
     *
     * @param string $generationId
     * @return array
     */
    public function getResult(string $generationId): array
    {
        return $this->checkStatus($generationId);
    }

    /**
     * ตรวจสอบว่า provider ตั้งค่าเรียบร้อยหรือยัง
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return ! empty($this->getConfig('api_key'));
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     *
     * @return array
     */
    public function testConnection(): array
    {
        $apiKey = $this->getConfig('api_key');

        if (! $apiKey) {
            return [
                'success' => false,
                'message' => 'xAI API key ยังไม่ได้ตั้งค่า',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(30)
                ->get('https://api.x.ai/v1/models');

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'เชื่อมต่อ xAI (Grok) สำเร็จ'
                    : 'เชื่อมต่อล้มเหลว: '.$response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เชื่อมต่อล้มเหลว: '.$e->getMessage(),
            ];
        }
    }

    /**
     * ปรับปรุง prompt ตาม style ที่เลือก
     *
     * @param string $prompt
     * @param string|null $style
     * @return string
     */
    protected function enhancePrompt(string $prompt, ?string $style = null): string
    {
        if (! $style || $style === 'realistic') {
            return $prompt;
        }

        $styleModifiers = [
            'artistic' => 'artistic style, fine art, painterly, ',
            'anime' => 'anime style, manga art, Japanese animation style, ',
            'cartoon' => 'cartoon style, vibrant colors, animated, ',
            '3d' => '3D render, octane render, cinema 4D, photorealistic 3D, ',
        ];

        $modifier = $styleModifiers[$style] ?? '';

        return $modifier.$prompt;
    }

    /**
     * บันทึก base64 image เป็นไฟล์
     *
     * @param string $base64Data ข้อมูลภาพ base64
     * @param string $providerName ชื่อ provider
     * @param int $index ลำดับภาพ
     * @return array|null ข้อมูลไฟล์ที่บันทึก
     */
    protected function saveBase64Image(string $base64Data, string $providerName, int $index = 0): ?array
    {
        if (empty($base64Data)) {
            return null;
        }

        try {
            $imageData = base64_decode($base64Data);
            $filename = $providerName.'_'.date('Ymd_His').'_'.$index.'_'.Str::random(8).'.jpg';
            $path = 'ai-gen/'.$providerName.'/'.date('Y/m').'/'.$filename;

            Storage::disk('public')->put($path, $imageData);

            return [
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ดาวน์โหลดภาพจาก URL แล้วบันทึกลง storage
     *
     * @param string $url URL ของภาพ
     * @param string $providerName ชื่อ provider
     * @param int $index ลำดับภาพ
     * @return array|null ข้อมูลไฟล์ที่บันทึก
     */
    protected function saveFromUrl(string $url, string $providerName, int $index = 0): ?array
    {
        try {
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $filename = $providerName.'_'.date('Ymd_His').'_'.$index.'_'.Str::random(8).'.jpg';
            $path = 'ai-gen/'.$providerName.'/'.date('Y/m').'/'.$filename;

            Storage::disk('public')->put($path, $response->body());

            return [
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
