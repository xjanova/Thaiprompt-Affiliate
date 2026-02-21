<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Hugging Face Inference API Provider
 *
 * เชื่อมต่อ Hugging Face Serverless Inference API สำหรับเจนภาพ
 * ฟรี! สมัครง่าย ไม่ต้องใส่บัตรเครดิต มี free monthly credits
 * ใช้โมเดล FLUX.1-schnell (เร็ว คุณภาพดี)
 * รองรับ prompt ภาษาไทย (แปลอัตโนมัติ)
 *
 * @see https://huggingface.co/docs/api-inference
 * @see https://huggingface.co/black-forest-labs/FLUX.1-schnell
 */
class HuggingfaceProvider extends BaseAiGenProvider
{
    /**
     * โมเดลที่รองรับ
     */
    protected const MODELS = [
        'flux-schnell' => 'black-forest-labs/FLUX.1-schnell',
        'flux-dev' => 'black-forest-labs/FLUX.1-dev',
        'sdxl' => 'stabilityai/stable-diffusion-xl-base-1.0',
        'sd-3-5' => 'stabilityai/stable-diffusion-3.5-large',
    ];

    /**
     * Base URL สำหรับ Inference API
     */
    protected const BASE_URL = 'https://api-inference.huggingface.co/models';

    /**
     * เจนภาพด้วย Hugging Face Inference API
     *
     * ใช้ POST request ส่ง JSON payload
     * ได้ binary image กลับมา
     * รองรับ prompt ภาษาไทย (แปลเป็นอังกฤษอัตโนมัติ)
     *
     * @param string $prompt คำอธิบายภาพที่ต้องการเจน
     * @param array $parameters พารามิเตอร์เพิ่มเติม
     * @return array ผลลัพธ์การเจนภาพ
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'กรุณาตั้งค่า Hugging Face API Token ก่อน (สมัครฟรีที่ huggingface.co)',
            ];
        }

        // เลือกโมเดล
        $modelKey = $parameters['model'] ?? 'flux-schnell';
        $model = self::MODELS[$modelKey] ?? self::MODELS['flux-schnell'];

        // กำหนดขนาดภาพ
        $size = $this->parseSize($parameters['size'] ?? '1024x1024');

        // เตรียม prompt - แปลภาษาไทยอัตโนมัติ
        $enhancedPrompt = $this->enhancePrompt($prompt, $parameters['style'] ?? null);
        $translatedPrompt = $this->translateThaiToEnglish($enhancedPrompt);

        try {
            $numImages = min($parameters['num_images'] ?? 1, 4);
            $images = [];

            for ($i = 0; $i < $numImages; $i++) {
                // เตรียม payload
                $payload = [
                    'inputs' => $translatedPrompt,
                    'parameters' => [
                        'width' => $size['width'],
                        'height' => $size['height'],
                    ],
                ];

                // เพิ่ม seed สำหรับแต่ละภาพ
                $seed = $parameters['seed'] ?? rand(1, 999999);
                if ($i > 0) {
                    $seed += $i;
                }
                $payload['parameters']['seed'] = $seed;

                // สร้าง URL
                $url = self::BASE_URL.'/'.$model;

                // เรียก API
                $response = Http::timeout(120)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, $payload);

                if (! $response->successful()) {
                    if ($i === 0) {
                        // ดึง error message จาก response
                        $errorMsg = 'Hugging Face API error: HTTP '.$response->status();
                        try {
                            $errorBody = $response->json();
                            if (isset($errorBody['error'])) {
                                $errorMsg = is_string($errorBody['error'])
                                    ? $errorBody['error']
                                    : json_encode($errorBody['error']);
                            }
                        } catch (\Exception $e) {
                            // ไม่สามารถ parse JSON ได้
                        }

                        return [
                            'success' => false,
                            'error' => $errorMsg,
                        ];
                    }
                    break;
                }

                // เช็คว่าเป็น image หรือ JSON error
                $contentType = $response->header('content-type');
                if (str_contains($contentType ?? '', 'json')) {
                    $jsonBody = $response->json();
                    if (isset($jsonBody['error'])) {
                        if ($i === 0) {
                            return [
                                'success' => false,
                                'error' => $jsonBody['error'],
                            ];
                        }
                        break;
                    }
                }

                // บันทึก binary image
                $extension = str_contains($contentType ?? '', 'jpeg') || str_contains($contentType ?? '', 'jpg') ? 'jpg' : 'png';

                $savedImage = $this->saveBinaryImage(
                    $response->body(),
                    'huggingface',
                    $i,
                    $extension
                );

                if ($savedImage) {
                    $images[] = [
                        'url' => $savedImage['url'],
                        'thumbnail' => $savedImage['url'],
                    ];
                }
            }

            if (empty($images)) {
                return [
                    'success' => false,
                    'error' => 'ไม่ได้รับภาพจาก Hugging Face API',
                ];
            }

            $generationId = Str::uuid()->toString();

            return [
                'success' => true,
                'generation_id' => $generationId,
                'status' => 'completed',
                'images' => $images,
                'data' => [
                    'model' => $model,
                    'provider' => 'huggingface',
                    'original_prompt' => $prompt,
                    'translated_prompt' => $translatedPrompt,
                    'images' => $images,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Hugging Face request failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Hugging Face ไม่รองรับ video generation ในฟรี tier
     *
     * @param string $prompt
     * @param array $parameters
     * @return array
     */
    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return [
            'success' => false,
            'error' => 'Hugging Face Inference API ไม่รองรับการสร้างวิดีโอ กรุณาใช้ provider อื่น',
        ];
    }

    /**
     * เช็คสถานะ (HF Inference เจนเสร็จทันที)
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
     */
    public function getResult(string $generationId): array
    {
        return $this->checkStatus($generationId);
    }

    /**
     * ตรวจสอบว่า provider ตั้งค่าเรียบร้อยหรือยัง
     */
    public function isConfigured(): bool
    {
        $apiKey = $this->getConfig('api_key');

        return ! empty($apiKey);
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     */
    public function testConnection(): array
    {
        try {
            $apiKey = $this->getConfig('api_key');
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'message' => 'ยังไม่ได้ตั้งค่า API Token - สมัครฟรีที่ huggingface.co',
                ];
            }

            // ทดสอบโดยเจนภาพเล็กๆ
            $model = self::MODELS['flux-schnell'];
            $url = self::BASE_URL.'/'.$model;

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'inputs' => 'test image, simple circle',
                    'parameters' => [
                        'width' => 256,
                        'height' => 256,
                        'seed' => 42,
                    ],
                ]);

            if ($response->successful()) {
                $contentType = $response->header('content-type');

                // เช็คว่าได้ image จริงๆ ไม่ใช่ JSON error
                if (str_contains($contentType ?? '', 'json')) {
                    $jsonBody = $response->json();
                    if (isset($jsonBody['error'])) {
                        return [
                            'success' => false,
                            'message' => 'เชื่อมต่อล้มเหลว: '.$jsonBody['error'],
                        ];
                    }
                }

                $bodyLength = strlen($response->body());

                return [
                    'success' => true,
                    'message' => "เชื่อมต่อ Hugging Face สำเร็จ! (ได้รับภาพ {$bodyLength} bytes, model: FLUX.1-schnell)",
                ];
            }

            // ดึง error message
            $errorMsg = 'HTTP '.$response->status();
            try {
                $errorBody = $response->json();
                if (isset($errorBody['error'])) {
                    $errorMsg = is_string($errorBody['error'])
                        ? $errorBody['error']
                        : json_encode($errorBody['error']);
                }
            } catch (\Exception $e) {
                // ไม่สามารถ parse JSON ได้
            }

            return [
                'success' => false,
                'message' => 'เชื่อมต่อล้มเหลว: '.$errorMsg,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เชื่อมต่อล้มเหลว: '.$e->getMessage(),
            ];
        }
    }

    /**
     * แปลงขนาดภาพจาก string เป็น array
     *
     * @param string $size เช่น "1024x1024"
     * @return array ['width' => int, 'height' => int]
     */
    protected function parseSize(string $size): array
    {
        $parts = explode('x', $size);
        $width = (int) ($parts[0] ?? 1024);
        $height = (int) ($parts[1] ?? 1024);

        // FLUX.1-schnell รองรับขนาดสูงสุด 1024
        return [
            'width' => min(max($width, 256), 1024),
            'height' => min(max($height, 256), 1024),
        ];
    }

    /**
     * ปรับปรุง prompt ตาม style ที่เลือก
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
     * แปล prompt ภาษาไทยเป็นอังกฤษอัตโนมัติ
     *
     * ตรวจจับว่า prompt มีภาษาไทยหรือไม่
     * ถ้ามี จะแปลเป็นอังกฤษเพื่อให้โมเดล AI เข้าใจ
     *
     * @param string $prompt
     * @return string prompt ที่แปลแล้ว (หรือ prompt เดิมถ้าเป็นอังกฤษ)
     */
    protected function translateThaiToEnglish(string $prompt): string
    {
        // เช็คว่ามีตัวอักษรภาษาไทยหรือไม่
        if (! preg_match('/[\x{0E00}-\x{0E7F}]/u', $prompt)) {
            // ไม่มีภาษาไทย ส่ง prompt เดิม
            return $prompt;
        }

        try {
            // ใช้ Google Cloud Translation API
            $apiKey = config('services.google.translate_api_key')
                ?? config('services.google_cloud.api_key')
                ?? env('GOOGLE_CLOUD_TRANSLATE_API_KEY');

            if (empty($apiKey)) {
                // ถ้าไม่มี Google API key ส่ง prompt เดิม
                return $prompt;
            }

            $response = Http::timeout(10)->post(
                'https://translation.googleapis.com/language/translate/v2',
                [
                    'q' => $prompt,
                    'source' => 'th',
                    'target' => 'en',
                    'format' => 'text',
                    'key' => $apiKey,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $translatedText = $data['data']['translations'][0]['translatedText'] ?? null;

                if ($translatedText) {
                    return $translatedText;
                }
            }

            // ถ้าแปลไม่ได้ ส่ง prompt เดิม
            return $prompt;
        } catch (\Exception $e) {
            // ถ้าเกิดข้อผิดพลาด ส่ง prompt เดิม
            return $prompt;
        }
    }

    /**
     * บันทึก binary image เป็นไฟล์
     */
    protected function saveBinaryImage(string $binaryData, string $providerName, int $index = 0, string $extension = 'png'): ?array
    {
        if (empty($binaryData)) {
            return null;
        }

        try {
            $filename = $providerName.'_'.date('Ymd_His').'_'.$index.'_'.Str::random(8).'.'.$extension;
            $path = 'ai-gen/'.$providerName.'/'.date('Y/m').'/'.$filename;

            Storage::disk('public')->put($path, $binaryData);

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
