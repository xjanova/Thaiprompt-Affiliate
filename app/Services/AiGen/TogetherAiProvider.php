<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Together AI Provider
 *
 * เชื่อมต่อ Together AI API สำหรับเจนภาพด้วย FLUX.1 schnell (ฟรีไม่อั้น)
 * รองรับ FLUX.1 schnell-Free (ฟรี) และ FLUX Pro (เสียเงิน)
 *
 * @see https://docs.together.ai/reference/images
 */
class TogetherAiProvider extends BaseAiGenProvider
{
    /**
     * โมเดลที่รองรับ
     */
    protected const MODELS = [
        'flux-schnell-free' => 'black-forest-labs/FLUX.1-schnell-Free',
        'flux-schnell' => 'black-forest-labs/FLUX.1-schnell',
        'flux-dev' => 'black-forest-labs/FLUX.1-dev',
    ];

    /**
     * ขนาดภาพที่รองรับ (width x height)
     */
    protected const SIZES = [
        '1024x1024' => ['width' => 1024, 'height' => 1024],
        '1024x768' => ['width' => 1024, 'height' => 768],
        '768x1024' => ['width' => 768, 'height' => 1024],
        '1280x720' => ['width' => 1280, 'height' => 720],
        '720x1280' => ['width' => 720, 'height' => 1280],
    ];

    /**
     * เจนภาพด้วย Together AI API
     *
     * Together AI ใช้ OpenAI-compatible API
     * FLUX.1 schnell-Free เจนภาพได้ฟรีไม่อั้น
     *
     * @param string $prompt คำอธิบายภาพที่ต้องการเจน
     * @param array $parameters พารามิเตอร์เพิ่มเติม (size, style, model, steps, seed)
     * @return array ผลลัพธ์การเจนภาพ
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');
        $baseUrl = $this->getConfig('api_endpoint', 'https://api.together.xyz/v1');

        if (! $apiKey) {
            return [
                'success' => false,
                'error' => 'Together AI API key ยังไม่ได้ตั้งค่า',
            ];
        }

        // เลือกโมเดล (ค่าเริ่มต้น = ฟรีไม่อั้น)
        $modelKey = $parameters['model'] ?? 'flux-schnell-free';
        $model = self::MODELS[$modelKey] ?? self::MODELS['flux-schnell-free'];

        // กำหนดขนาดภาพ
        $sizeKey = $parameters['size'] ?? '1024x1024';
        $size = self::SIZES[$sizeKey] ?? self::SIZES['1024x1024'];

        // เตรียมข้อมูลส่ง API
        $data = [
            'model' => $model,
            'prompt' => $this->enhancePrompt($prompt, $parameters['style'] ?? null),
            'width' => $size['width'],
            'height' => $size['height'],
            'steps' => $parameters['steps'] ?? 4,
            'n' => $parameters['num_images'] ?? 1,
            'response_format' => 'b64_json',
        ];

        // เพิ่ม seed ถ้ามี
        if (isset($parameters['seed'])) {
            $data['seed'] = (int) $parameters['seed'];
        }

        // เพิ่ม negative prompt ถ้ามี
        if (! empty($parameters['negative_prompt'])) {
            $data['negative_prompt'] = $parameters['negative_prompt'];
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
                    'error' => 'Together AI error: '.$errorMsg,
                ];
            }

            $result = $response->json();
            $images = [];

            // Together AI ส่ง base64 กลับมา - บันทึกเป็นไฟล์
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $index => $imageData) {
                    $savedImage = $this->saveBase64Image(
                        $imageData['b64_json'] ?? '',
                        'together-ai',
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

            if (empty($images)) {
                return [
                    'success' => false,
                    'error' => 'ไม่ได้รับภาพจาก Together AI',
                ];
            }

            return [
                'success' => true,
                'generation_id' => $result['id'] ?? Str::uuid()->toString(),
                'status' => 'completed',
                'images' => $images,
                'data' => [
                    'model' => $model,
                    'provider' => 'together-ai',
                    'images' => $images,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Together AI request failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Together AI ไม่รองรับ video generation
     *
     * @param string $prompt
     * @param array $parameters
     * @return array
     */
    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return [
            'success' => false,
            'error' => 'Together AI ไม่รองรับการสร้างวิดีโอ กรุณาใช้ provider อื่น',
        ];
    }

    /**
     * เช็คสถานะการเจน (Together AI เจนเสร็จทันทีไม่ต้อง poll)
     *
     * @param string $generationId
     * @return array
     */
    public function checkStatus(string $generationId): array
    {
        // Together AI สร้างภาพเสร็จทันที (synchronous)
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
                'message' => 'API key ยังไม่ได้ตั้งค่า',
            ];
        }

        try {
            // ทดสอบโดยเจนภาพเล็กๆ
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(30)
                ->get('https://api.together.xyz/v1/models');

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'เชื่อมต่อ Together AI สำเร็จ'
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
            $filename = $providerName.'_'.date('Ymd_His').'_'.$index.'_'.Str::random(8).'.png';
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
}
