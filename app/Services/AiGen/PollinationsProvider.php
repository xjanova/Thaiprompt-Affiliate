<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pollinations.ai Provider
 *
 * เชื่อมต่อ Pollinations.ai API สำหรับเจนภาพ
 * ฟรี 100% ไม่ต้อง API key ไม่ต้องสมัครสมาชิก
 * ใช้โมเดล FLUX, Stable Diffusion และอื่นๆ
 *
 * @see https://pollinations.ai
 * @see https://github.com/pollinations/pollinations
 */
class PollinationsProvider extends BaseAiGenProvider
{
    /**
     * โมเดลที่รองรับ
     */
    protected const MODELS = [
        'flux' => 'flux',
        'flux-realism' => 'flux-realism',
        'flux-anime' => 'flux-anime',
        'flux-3d' => 'flux-3d',
        'flux-pro' => 'flux-pro',
        'turbo' => 'turbo',
    ];

    /**
     * Base URL สำหรับ API
     */
    protected const BASE_URL = 'https://gen.pollinations.ai/image';

    /**
     * เจนภาพด้วย Pollinations.ai
     *
     * Pollinations ใช้ GET request + query parameters
     * รองรับทั้งแบบมี API key และไม่มี (ฟรีมี rate limit)
     *
     * @param string $prompt คำอธิบายภาพที่ต้องการเจน
     * @param array $parameters พารามิเตอร์เพิ่มเติม (size, style, model, num_images, seed)
     * @return array ผลลัพธ์การเจนภาพ
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        // เลือกโมเดล
        $modelKey = $parameters['model'] ?? 'flux';
        $model = self::MODELS[$modelKey] ?? 'flux';

        // กำหนดขนาดภาพ
        $size = $this->parseSize($parameters['size'] ?? '1024x1024');

        // เตรียม prompt ตาม style
        $enhancedPrompt = $this->enhancePrompt($prompt, $parameters['style'] ?? null);

        // เตรียม query parameters
        $queryParams = [
            'width' => $size['width'],
            'height' => $size['height'],
            'model' => $model,
            'nologo' => 'true',
        ];

        // เพิ่ม API key ถ้ามี (รองรับ ?key= query parameter)
        $apiKey = $this->getConfig('api_key');
        if ($apiKey) {
            $queryParams['key'] = $apiKey;
        }

        // เพิ่ม seed ถ้ามี
        if (isset($parameters['seed'])) {
            $queryParams['seed'] = (int) $parameters['seed'];
        }

        try {
            $numImages = min($parameters['num_images'] ?? 1, 4);
            $images = [];

            for ($i = 0; $i < $numImages; $i++) {
                // เปลี่ยน seed สำหรับแต่ละภาพเพื่อให้ได้ภาพต่างกัน
                if ($i > 0) {
                    $queryParams['seed'] = ($queryParams['seed'] ?? rand(1, 999999)) + $i;
                }

                // สร้าง URL (prompt อยู่ใน path)
                $encodedPrompt = rawurlencode($enhancedPrompt);
                $url = self::BASE_URL.'/'.$encodedPrompt.'?'.http_build_query($queryParams);

                // เรียก API (GET request, returns binary image)
                // ส่ง Authorization header ถ้ามี API key
                $http = Http::timeout(120);
                if ($apiKey) {
                    $http = $http->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                    ]);
                }
                $response = $http->get($url);

                if (! $response->successful()) {
                    if ($i === 0) {
                        // ดึง error message จาก response body ถ้าเป็น JSON
                        $errorMsg = 'Pollinations API error: HTTP '.$response->status();
                        try {
                            $errorBody = $response->json();
                            if (isset($errorBody['error']['message'])) {
                                $errorMsg = $errorBody['error']['message'];
                            }
                        } catch (\Exception $e) {
                            // ไม่สามารถ parse JSON ได้ ใช้ default message
                        }

                        return [
                            'success' => false,
                            'error' => $errorMsg,
                        ];
                    }
                    break;
                }

                // บันทึก binary image
                $contentType = $response->header('content-type');
                $extension = str_contains($contentType ?? '', 'jpeg') || str_contains($contentType ?? '', 'jpg') ? 'jpg' : 'png';

                $savedImage = $this->saveBinaryImage(
                    $response->body(),
                    'pollinations',
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
                    'error' => 'ไม่ได้รับภาพจาก Pollinations.ai',
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
                    'provider' => 'pollinations',
                    'images' => $images,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Pollinations request failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Pollinations ไม่รองรับ video generation
     *
     * @param string $prompt
     * @param array $parameters
     * @return array
     */
    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return [
            'success' => false,
            'error' => 'Pollinations.ai ไม่รองรับการสร้างวิดีโอ กรุณาใช้ provider อื่น',
        ];
    }

    /**
     * เช็คสถานะการเจน (Pollinations เจนเสร็จทันทีไม่ต้อง poll)
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
     * Pollinations ต้องมี API key (ฟรีสมัครได้ที่ enter.pollinations.ai)
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        // ตรวจสอบว่ามี API key หรือไม่
        $apiKey = $this->getConfig('api_key');

        return ! empty($apiKey);
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $apiKey = $this->getConfig('api_key');

            // สร้าง URL ทดสอบ
            $queryParams = [
                'width' => 256,
                'height' => 256,
                'model' => 'flux',
                'nologo' => 'true',
                'seed' => 42,
            ];

            if ($apiKey) {
                $queryParams['key'] = $apiKey;
            }

            $testUrl = self::BASE_URL.'/test?'.http_build_query($queryParams);

            // เรียก API พร้อม Authorization header
            $http = Http::timeout(30);
            if ($apiKey) {
                $http = $http->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ]);
            }
            $response = $http->get($testUrl);

            if ($response->successful()) {
                $contentType = $response->header('content-type');
                $bodyLength = strlen($response->body());

                return [
                    'success' => true,
                    'message' => "เชื่อมต่อ Pollinations.ai สำเร็จ! (ได้รับภาพ {$bodyLength} bytes, type: {$contentType})",
                ];
            }

            // ดึง error message จาก response
            $errorMsg = 'HTTP '.$response->status();
            try {
                $errorBody = $response->json();
                if (isset($errorBody['error']['message'])) {
                    $errorMsg = $errorBody['error']['message'];
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

        // Pollinations รองรับขนาดสูงสุด 2048
        return [
            'width' => min(max($width, 256), 2048),
            'height' => min(max($height, 256), 2048),
        ];
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
     * บันทึก binary image เป็นไฟล์
     *
     * @param string $binaryData ข้อมูลภาพ binary
     * @param string $providerName ชื่อ provider
     * @param int $index ลำดับภาพ
     * @param string $extension นามสกุลไฟล์
     * @return array|null ข้อมูลไฟล์ที่บันทึก
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
