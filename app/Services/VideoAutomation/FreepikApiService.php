<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoSetting;
use App\Models\VideoAutoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Freepik API Service
 *
 * จัดการการสร้างและค้นหาภาพผ่าน Freepik API
 *
 * @see https://www.freepik.com/api
 * @see https://docs.freepik.com/
 */
class FreepikApiService
{
    /**
     * Base URL ของ Freepik API
     *
     * @var string
     */
    protected string $baseUrl = 'https://api.freepik.com/v1';

    /**
     * API Key
     *
     * @var string|null
     */
    protected ?string $apiKey;

    /**
     * Timeout สำหรับ HTTP requests (วินาที)
     *
     * @var int
     */
    protected int $timeout = 60;

    /**
     * สร้าง instance ใหม่
     */
    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * โหลดการตั้งค่าจาก database
     *
     * @return void
     */
    protected function loadSettings(): void
    {
        $this->apiKey = VideoAutoSetting::getValue('freepik_api_key');
        $this->baseUrl = VideoAutoSetting::getValue('freepik_api_url', 'https://api.freepik.com/v1');
        $this->timeout = (int) VideoAutoSetting::getValue('freepik_timeout', 60);
    }

    /**
     * ตรวจสอบว่า API พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * สร้างภาพด้วย AI
     *
     * @param string $prompt คำอธิบายภาพที่ต้องการ
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, data?: array, error?: string}
     *
     * @example
     * $result = $service->generateImage(
     *     'beautiful sunset over mountains, vibrant colors, cinematic',
     *     [
     *         'style' => 'realistic',
     *         'ratio' => '16:9',
     *         'quality' => 'hd',
     *     ]
     * );
     */
    public function generateImage(string $prompt, array $options = [], ?VideoAutoJob $job = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Freepik API ยังไม่ได้ตั้งค่า กรุณาใส่ API Key',
            ];
        }

        try {
            $job?->logInfo('เริ่มสร้างภาพด้วย Freepik AI', ['prompt' => $prompt]);

            // สร้าง request body
            $body = [
                'prompt' => $prompt,
                'num_images' => $options['count'] ?? 1,
                'image' => [
                    'size' => $this->getImageSize($options['ratio'] ?? '16:9'),
                ],
            ];

            // เพิ่ม style
            if (!empty($options['style'])) {
                $body['style'] = $options['style'];
            }

            // เพิ่ม negative prompt
            if (!empty($options['negative_prompt'])) {
                $body['negative_prompt'] = $options['negative_prompt'];
            }

            $startTime = microtime(true);

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/ai/text-to-image', $body);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $job?->logApiCall(
                'freepik',
                '/ai/text-to-image',
                'POST',
                $response->status(),
                $responseTime,
                $body,
                $response->json() ?? []
            );

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                $job?->logError('Freepik API error', ['error' => $error]);

                return [
                    'success' => false,
                    'error' => "Freepik API error: {$error}",
                ];
            }

            $data = $response->json();

            $job?->logInfo('สร้างภาพสำเร็จ', [
                'images_count' => count($data['data'] ?? []),
            ]);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Freepik API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $job?->logError('Freepik API exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * สร้างภาพหลายรูปจาก prompts
     *
     * @param array $prompts รายการ prompts
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, images?: array, error?: string}
     */
    public function generateMultipleImages(array $prompts, array $options = [], ?VideoAutoJob $job = null): array
    {
        $images = [];
        $errors = [];
        $total = count($prompts);

        foreach ($prompts as $index => $prompt) {
            $job?->updateProgress(
                (int) (($index / $total) * 100),
                "กำลังสร้างภาพ {$index}/{$total}"
            );

            $result = $this->generateImage($prompt, $options, $job);

            if ($result['success']) {
                $imageData = $result['data']['data'][0] ?? null;

                if ($imageData) {
                    $images[] = [
                        'prompt' => $prompt,
                        'url' => $imageData['base64'] ?? $imageData['url'] ?? null,
                        'is_base64' => isset($imageData['base64']),
                    ];
                }
            } else {
                $errors[] = [
                    'prompt' => $prompt,
                    'error' => $result['error'],
                ];
            }

            // หน่วงเวลาเล็กน้อยระหว่าง requests
            if ($index < $total - 1) {
                usleep(500000); // 0.5 วินาที
            }
        }

        return [
            'success' => count($images) > 0,
            'images' => $images,
            'errors' => $errors,
            'total_requested' => $total,
            'total_generated' => count($images),
        ];
    }

    /**
     * ค้นหาภาพจาก Freepik
     *
     * @param string $query คำค้นหา
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, data?: array, error?: string}
     */
    public function searchImages(string $query, array $options = [], ?VideoAutoJob $job = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Freepik API ยังไม่ได้ตั้งค่า',
            ];
        }

        try {
            $params = [
                'term' => $query,
                'page' => $options['page'] ?? 1,
                'limit' => $options['limit'] ?? 10,
                'filters' => [
                    'content_type' => ['photo', 'vector', 'psd', 'ai-generated'],
                ],
            ];

            // กรองตาม orientation
            if (!empty($options['orientation'])) {
                $params['filters']['orientation'] = $options['orientation'];
            }

            // กรองตาม color
            if (!empty($options['color'])) {
                $params['filters']['color'] = $options['color'];
            }

            $startTime = microtime(true);

            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/resources', $params);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $job?->logApiCall(
                'freepik',
                '/resources',
                'GET',
                $response->status(),
                $responseTime,
                $params,
                []
            );

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'ค้นหาภาพไม่สำเร็จ: ' . $response->body(),
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดาวน์โหลดภาพ
     *
     * @param string $imageUrl URL หรือ base64 ของภาพ
     * @param string $filename ชื่อไฟล์
     * @param bool $isBase64 เป็น base64 หรือไม่
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, path?: string, error?: string}
     */
    public function downloadImage(
        string $imageUrl,
        string $filename,
        bool $isBase64 = false,
        ?VideoAutoJob $job = null
    ): array {
        try {
            $job?->logInfo('เริ่มดาวน์โหลดภาพ', ['is_base64' => $isBase64]);

            if ($isBase64) {
                // Decode base64
                $imageData = base64_decode($imageUrl);
            } else {
                // Download from URL
                $response = Http::timeout(120)->get($imageUrl);

                if (!$response->successful()) {
                    return [
                        'success' => false,
                        'error' => 'ไม่สามารถดาวน์โหลดภาพได้',
                    ];
                }

                $imageData = $response->body();
            }

            // ตรวจสอบว่าเป็นรูปภาพจริง
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);

            if (!str_starts_with($mimeType, 'image/')) {
                return [
                    'success' => false,
                    'error' => 'ไฟล์ที่ดาวน์โหลดไม่ใช่รูปภาพ',
                ];
            }

            // กำหนด extension จาก mime type
            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };

            // เพิ่ม extension ถ้าไม่มี
            if (!str_contains($filename, '.')) {
                $filename .= '.' . $extension;
            }

            // บันทึกไฟล์
            $path = "video-automation/images/{$filename}";
            Storage::disk('public')->put($path, $imageData);

            $job?->logInfo('ดาวน์โหลดภาพสำเร็จ', [
                'path' => $path,
                'size' => strlen($imageData),
                'mime' => $mimeType,
            ]);

            return [
                'success' => true,
                'path' => $path,
                'full_path' => Storage::disk('public')->path($path),
                'url' => Storage::disk('public')->url($path),
                'size' => strlen($imageData),
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            $job?->logError('ดาวน์โหลดภาพล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดาวน์โหลดภาพหลายรูป
     *
     * @param array $images รายการภาพ
     * @param string $projectId ID ของโปรเจกต์
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, downloaded?: array, errors?: array}
     */
    public function downloadMultipleImages(array $images, string $projectId, ?VideoAutoJob $job = null): array
    {
        $downloaded = [];
        $errors = [];
        $total = count($images);

        foreach ($images as $index => $image) {
            $job?->updateProgress(
                (int) (($index / $total) * 100),
                "กำลังดาวน์โหลดภาพ {$index}/{$total}"
            );

            $filename = "{$projectId}_" . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            $result = $this->downloadImage(
                $image['url'],
                $filename,
                $image['is_base64'] ?? false,
                $job
            );

            if ($result['success']) {
                $downloaded[] = array_merge($result, [
                    'index' => $index,
                    'original_prompt' => $image['prompt'] ?? null,
                ]);
            } else {
                $errors[] = [
                    'index' => $index,
                    'error' => $result['error'],
                ];
            }

            // หน่วงเวลาเล็กน้อย
            usleep(100000); // 0.1 วินาที
        }

        return [
            'success' => count($downloaded) > 0,
            'downloaded' => $downloaded,
            'errors' => $errors,
            'total_requested' => $total,
            'total_downloaded' => count($downloaded),
        ];
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API ยังไม่ได้ตั้งค่า กรุณาใส่ API Key',
            ];
        }

        try {
            // ทดสอบด้วยการค้นหาภาพง่ายๆ
            $response = Http::timeout(15)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/resources', [
                    'term' => 'nature',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ Freepik API สำเร็จ!',
                ];
            }

            return [
                'success' => false,
                'message' => 'เชื่อมต่อไม่สำเร็จ: ' . ($response->json('error.message') ?? $response->status()),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * แปลง ratio เป็นขนาดภาพ
     *
     * @param string $ratio
     * @return string
     */
    protected function getImageSize(string $ratio): string
    {
        return match ($ratio) {
            '16:9' => 'landscape_16_9',
            '9:16' => 'portrait_9_16',
            '1:1' => 'square',
            '4:3' => 'landscape_4_3',
            '3:4' => 'portrait_3_4',
            '21:9' => 'ultrawide',
            default => 'landscape_16_9',
        };
    }

    /**
     * สร้าง HTTP headers
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-freepik-api-key' => $this->apiKey,
        ];
    }
}
