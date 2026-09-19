<?php

namespace App\Services\AiGen;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cloudflare Workers AI Provider
 *
 * เชื่อมต่อ Cloudflare Workers AI API สำหรับเจนภาพ
 * ฟรี 10,000 Neurons/วัน (~40 ภาพ/วัน) หลังจากนั้น ~$0.003/ภาพ
 *
 * @see https://developers.cloudflare.com/workers-ai/models/
 */
class CloudflareAiProvider extends BaseAiGenProvider
{
    /**
     * โมเดลที่รองรับ
     */
    protected const MODELS = [
        'flux-1-schnell' => '@cf/black-forest-labs/flux-1-schnell',
        'sdxl-base' => '@cf/stabilityai/stable-diffusion-xl-base-1.0',
        'sdxl-lightning' => '@cf/bytedance/stable-diffusion-xl-lightning',
    ];

    /**
     * เจนภาพด้วย Cloudflare Workers AI
     *
     * Cloudflare ใช้ REST API ที่แตกต่างจาก OpenAI format
     * ส่ง binary image กลับมาโดยตรง
     *
     * @param  string  $prompt  คำอธิบายภาพที่ต้องการเจน
     * @param  array  $parameters  พารามิเตอร์เพิ่มเติม (size, style, model, steps, num_images)
     * @return array ผลลัพธ์การเจนภาพ
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        // ลำดับการดึง credential: DB config (admin UI) → .env (CLOUDFLARE_API_TOKEN/ACCOUNT_ID)
        // วิธีนี้ใช้ token เดียวกับ TPIX ได้โดยไม่ต้องตั้งค่า admin UI ใหม่
        $apiToken = $this->getConfig('api_key') ?: config('services.cloudflare.api_token');
        $accountId = $this->getConfig('account_id') ?: config('services.cloudflare.account_id');

        if (! $apiToken || ! $accountId) {
            return [
                'success' => false,
                'error' => 'Cloudflare API token หรือ Account ID ยังไม่ได้ตั้งค่า (ลองใส่ CLOUDFLARE_API_TOKEN + CLOUDFLARE_ACCOUNT_ID ใน .env)',
            ];
        }

        // เลือกโมเดล
        $modelKey = $parameters['model'] ?? 'flux-1-schnell';
        $model = self::MODELS[$modelKey] ?? self::MODELS['flux-1-schnell'];

        // กำหนดขนาดภาพ
        $size = $this->parseSize($parameters['size'] ?? '1024x1024');

        // 🔧 (2026-09-19) Cloudflare รัดกุม schema ขาเข้าแล้ว — ส่งคีย์ที่โมเดลไม่รู้จัก
        //   **ตีกลับทั้งคำขอ** ไม่ใช่เมินคีย์นั้นทิ้ง:
        //     "Bad input: Additional or unevaluated properties '/num_steps, /width, /height, /seed' not allowed"
        //   โค้ดเดิมส่ง num_steps ให้ทุกโมเดล + width/height ให้ flux + seed ให้ทุกตัว
        //   ⇒ flux ตาย 400 ทุกครั้ง (ยืนยันกับ API จริง 2026-09-19)
        //   ชุดที่ยิงจริงแล้วผ่าน:
        //     flux-1-schnell            {prompt, steps}                                    → 200 JSON base64
        //     stable-diffusion-xl-*     {prompt, num_steps, width, height, seed, negative}  → 200 image/png
        //   ⚠️ flux **ไม่รับ seed** ⇒ ตัวกันรูปซ้ำฝั่ง FortuneHoroscopeService ใช้ seed ไม่ได้กับโมเดลนี้
        //      แต่ flux สุ่มใหม่ทุกครั้งอยู่แล้ว และด่านเทียบ md5 ยังทำงาน — ไม่ทำให้รูปซ้ำ
        $isFlux = str_contains($model, 'flux');

        $data = [
            'prompt' => $this->enhancePrompt($prompt, $parameters['style'] ?? null),
        ];

        if ($isFlux) {
            // steps ของ flux-1-schnell รับ 1-8 เท่านั้น — เกินช่วงก็ 400 เหมือนกัน
            $data['steps'] = max(1, min(8, (int) ($parameters['steps'] ?? 4)));
        } else {
            $data['num_steps'] = (int) ($parameters['steps'] ?? 4);
            $data['width'] = $size['width'];
            $data['height'] = $size['height'];

            if (! empty($parameters['negative_prompt'])) {
                $data['negative_prompt'] = $parameters['negative_prompt'];
            }

            if (isset($parameters['seed'])) {
                $data['seed'] = (int) $parameters['seed'];
            }
        }

        $baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$model}";

        $headers = [
            'Authorization' => 'Bearer '.$apiToken,
            'Content-Type' => 'application/json',
        ];

        try {
            $numImages = min($parameters['num_images'] ?? 1, 4);
            $images = [];

            // Cloudflare AI สร้างได้ทีละ 1 ภาพ ต้อง loop
            for ($i = 0; $i < $numImages; $i++) {
                $response = Http::withHeaders($headers)
                    ->timeout(120)
                    ->post($baseUrl, $data);

                if (! $response->successful()) {
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['errors'][0]['message'] ?? $response->body();

                    // ถ้าภาพแรกก็ fail ให้ return error เลย
                    if ($i === 0) {
                        return [
                            'success' => false,
                            'error' => 'Cloudflare AI error: '.$errorMsg,
                        ];
                    }
                    // ถ้าไม่ใช่ภาพแรก ให้หยุดแค่นี้
                    break;
                }

                // Cloudflare อาจส่ง binary image หรือ JSON (ขึ้นกับ model)
                $contentType = $response->header('content-type');

                if (str_contains($contentType, 'image/') || str_contains($contentType, 'octet-stream')) {
                    // Binary image response
                    $savedImage = $this->saveBinaryImage(
                        $response->body(),
                        'cloudflare-ai',
                        $i
                    );
                } else {
                    // JSON response (บาง model ส่ง base64)
                    $result = $response->json();
                    $imageB64 = $result['result']['image'] ?? $result['image'] ?? null;

                    if ($imageB64) {
                        $savedImage = $this->saveBase64Image($imageB64, 'cloudflare-ai', $i);
                    } else {
                        $savedImage = null;
                    }
                }

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
                    'error' => 'ไม่ได้รับภาพจาก Cloudflare AI',
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
                    'provider' => 'cloudflare-ai',
                    'images' => $images,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Cloudflare AI request failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Cloudflare AI ไม่รองรับ video generation โดยตรง
     */
    public function generateVideo(string $prompt, array $parameters = []): array
    {
        return [
            'success' => false,
            'error' => 'Cloudflare AI ไม่รองรับการสร้างวิดีโอ กรุณาใช้ provider อื่น',
        ];
    }

    /**
     * เช็คสถานะการเจน (Cloudflare AI เจนเสร็จทันทีไม่ต้อง poll)
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
        // เช็ค DB config + env fallback (ใช้ TPIX key ที่ใส่ไว้แล้ว)
        $token = $this->getConfig('api_key') ?: config('services.cloudflare.api_token');
        $accountId = $this->getConfig('account_id') ?: config('services.cloudflare.account_id');

        return ! empty($token) && ! empty($accountId);
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     */
    public function testConnection(): array
    {
        // ใช้ DB config → fallback ไป env (CLOUDFLARE_API_TOKEN/ACCOUNT_ID)
        $apiToken = $this->getConfig('api_key') ?: config('services.cloudflare.api_token');
        $accountId = $this->getConfig('account_id') ?: config('services.cloudflare.account_id');

        if (! $apiToken || ! $accountId) {
            return [
                'success' => false,
                'message' => 'API token หรือ Account ID ยังไม่ได้ตั้งค่า (ใส่ใน admin UI หรือ .env)',
            ];
        }

        try {
            // ทดสอบโดยดึงรายการโมเดล
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiToken,
            ])->timeout(30)
                ->get("https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/models/search", [
                    'task' => 'Text to Image',
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'เชื่อมต่อ Cloudflare Workers AI สำเร็จ'
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
     * แปลงขนาดภาพจาก string เป็น array
     *
     * @param  string  $size  เช่น "1024x1024"
     * @return array ['width' => int, 'height' => int]
     */
    protected function parseSize(string $size): array
    {
        $parts = explode('x', $size);

        // Cloudflare FLUX รองรับขนาดที่หาร 32 ลงตัว, สูงสุด 1024
        $width = min((int) ($parts[0] ?? 1024), 1024);
        $height = min((int) ($parts[1] ?? 1024), 1024);

        // ปัดให้หาร 32 ลงตัว
        $width = (int) (floor($width / 32) * 32);
        $height = (int) (floor($height / 32) * 32);

        return [
            'width' => max($width, 256),
            'height' => max($height, 256),
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
     * บันทึก binary image เป็นไฟล์
     *
     * @param  string  $binaryData  ข้อมูลภาพ binary
     * @param  string  $providerName  ชื่อ provider
     * @param  int  $index  ลำดับภาพ
     * @return array|null ข้อมูลไฟล์ที่บันทึก
     */
    protected function saveBinaryImage(string $binaryData, string $providerName, int $index = 0): ?array
    {
        if (empty($binaryData)) {
            return null;
        }

        try {
            $filename = $providerName.'_'.date('Ymd_His').'_'.$index.'_'.Str::random(8).'.png';
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

    /**
     * บันทึก base64 image เป็นไฟล์
     *
     * @param  string  $base64Data  ข้อมูลภาพ base64
     * @param  string  $providerName  ชื่อ provider
     * @param  int  $index  ลำดับภาพ
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
