<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoSetting;
use App\Models\VideoAutoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Suno API Service
 *
 * จัดการการสร้างเพลงผ่าน Suno AI API
 *
 * @see https://www.suno.ai/
 * @see https://github.com/gcui-art/suno-api (Unofficial API)
 */
class SunoApiService
{
    /**
     * Base URL ของ Suno API
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * API Key
     *
     * @var string|null
     */
    protected ?string $apiKey;

    /**
     * Cookie สำหรับ authentication
     *
     * @var string|null
     */
    protected ?string $cookie;

    /**
     * Timeout สำหรับ HTTP requests (วินาที)
     *
     * @var int
     */
    protected int $timeout = 120;

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
        $this->baseUrl = VideoAutoSetting::getValue('suno_api_url', 'https://api.suno.ai');
        $this->apiKey = VideoAutoSetting::getValue('suno_api_key');
        $this->cookie = VideoAutoSetting::getValue('suno_cookie');
        $this->timeout = (int) VideoAutoSetting::getValue('suno_timeout', 120);
    }

    /**
     * ตรวจสอบว่า API พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) || !empty($this->cookie);
    }

    /**
     * สร้างเพลงจาก prompt
     *
     * @param string $prompt คำอธิบายเพลงที่ต้องการ
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, data?: array, error?: string}
     *
     * @example
     * $result = $service->generateMusic(
     *     'uplifting pop song with piano and acoustic guitar',
     *     [
     *         'duration' => 180,
     *         'instrumental' => true,
     *         'style' => 'pop',
     *         'mood' => 'happy'
     *     ]
     * );
     */
    public function generateMusic(string $prompt, array $options = [], ?VideoAutoJob $job = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Suno API ยังไม่ได้ตั้งค่า กรุณาใส่ API Key หรือ Cookie',
            ];
        }

        try {
            $job?->logInfo('เริ่มสร้างเพลงด้วย Suno AI', ['prompt' => $prompt]);

            // สร้าง request body
            $body = $this->buildMusicRequestBody($prompt, $options);

            // เริ่มจับเวลา
            $startTime = microtime(true);

            // เรียก API สร้างเพลง
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/api/generate', $body);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // บันทึก API call
            $job?->logApiCall(
                'suno',
                '/api/generate',
                'POST',
                $response->status(),
                $responseTime,
                $body,
                $response->json() ?? []
            );

            if (!$response->successful()) {
                $error = $response->json('error') ?? $response->body();
                $job?->logError('Suno API ตอบกลับ error', ['error' => $error]);

                return [
                    'success' => false,
                    'error' => "Suno API error: {$error}",
                ];
            }

            $data = $response->json();

            // รอให้เพลงสร้างเสร็จ (polling)
            if (isset($data['id'])) {
                $data = $this->waitForCompletion($data['id'], $job);
            }

            $job?->logInfo('สร้างเพลงสำเร็จ', [
                'song_id' => $data['id'] ?? null,
                'title' => $data['title'] ?? null,
                'duration' => $data['duration'] ?? null,
            ]);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Suno API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $job?->logError('Suno API exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * สร้างเพลงแบบ custom (กำหนด lyrics และ style)
     *
     * @param string $lyrics เนื้อเพลง
     * @param string $style สไตล์เพลง
     * @param string $title ชื่อเพลง
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, data?: array, error?: string}
     */
    public function generateCustomMusic(
        string $lyrics,
        string $style,
        string $title = '',
        array $options = [],
        ?VideoAutoJob $job = null
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Suno API ยังไม่ได้ตั้งค่า',
            ];
        }

        try {
            $job?->logInfo('เริ่มสร้างเพลงแบบ custom', [
                'title' => $title,
                'style' => $style,
                'lyrics_length' => strlen($lyrics),
            ]);

            $body = [
                'prompt' => $lyrics,
                'tags' => $style,
                'title' => $title,
                'make_instrumental' => $options['instrumental'] ?? false,
                'model' => $options['model'] ?? 'chirp-v3-5',
                'wait_audio' => true,
            ];

            $startTime = microtime(true);

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/api/custom_generate', $body);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $job?->logApiCall(
                'suno',
                '/api/custom_generate',
                'POST',
                $response->status(),
                $responseTime,
                $body,
                $response->json() ?? []
            );

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Suno API error: ' . ($response->json('error') ?? $response->body()),
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            $job?->logError('Suno API exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงข้อมูลเพลงตาม ID
     *
     * @param string $songId ID ของเพลง
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getSong(string $songId): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/api/get?ids={$songId}");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถดึงข้อมูลเพลงได้',
                ];
            }

            $songs = $response->json();

            return [
                'success' => true,
                'data' => $songs[0] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดาวน์โหลดไฟล์เพลง
     *
     * @param string $audioUrl URL ของไฟล์เพลง
     * @param string $filename ชื่อไฟล์ที่จะบันทึก
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, path?: string, error?: string}
     */
    public function downloadMusic(string $audioUrl, string $filename, ?VideoAutoJob $job = null): array
    {
        try {
            $job?->logInfo('เริ่มดาวน์โหลดเพลง', ['url' => $audioUrl]);

            $response = Http::timeout(300)->get($audioUrl);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถดาวน์โหลดเพลงได้',
                ];
            }

            // บันทึกไฟล์
            $path = "video-automation/music/{$filename}";
            Storage::disk('public')->put($path, $response->body());

            $job?->logInfo('ดาวน์โหลดเพลงสำเร็จ', [
                'path' => $path,
                'size' => strlen($response->body()),
            ]);

            return [
                'success' => true,
                'path' => $path,
                'full_path' => Storage::disk('public')->path($path),
                'url' => Storage::disk('public')->url($path),
            ];

        } catch (\Exception $e) {
            $job?->logError('ดาวน์โหลดเพลงล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบ credit ที่เหลือ
     *
     * @return array{success: bool, credits?: int, error?: string}
     */
    public function getCredits(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/api/get_limit');

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถตรวจสอบ credit ได้',
                ];
            }

            return [
                'success' => true,
                'credits' => $response->json('credits_left') ?? 0,
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
     * ทดสอบการเชื่อมต่อ API
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API ยังไม่ได้ตั้งค่า กรุณาใส่ API Key หรือ Cookie',
            ];
        }

        $result = $this->getCredits();

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'เชื่อมต่อสำเร็จ! Credits คงเหลือ: ' . ($result['credits'] ?? 'ไม่ทราบ'),
            ];
        }

        return [
            'success' => false,
            'message' => 'เชื่อมต่อไม่สำเร็จ: ' . ($result['error'] ?? 'Unknown error'),
        ];
    }

    /**
     * สร้าง request body สำหรับสร้างเพลง
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    protected function buildMusicRequestBody(string $prompt, array $options): array
    {
        $body = [
            'prompt' => $prompt,
            'make_instrumental' => $options['instrumental'] ?? false,
            'model' => $options['model'] ?? 'chirp-v3-5',
            'wait_audio' => true,
        ];

        // เพิ่ม tags จาก options
        $tags = [];

        if (!empty($options['genre'])) {
            $tags[] = $options['genre'];
        }

        if (!empty($options['mood'])) {
            $tags[] = $options['mood'];
        }

        if (!empty($options['style'])) {
            $tags[] = $options['style'];
        }

        if (!empty($tags)) {
            $body['tags'] = implode(', ', $tags);
        }

        return $body;
    }

    /**
     * รอให้การสร้างเพลงเสร็จสิ้น
     *
     * @param string $songId
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function waitForCompletion(string $songId, ?VideoAutoJob $job = null): array
    {
        $maxAttempts = 60; // รอสูงสุด 5 นาที (60 x 5 วินาที)
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(5);
            $attempt++;

            $result = $this->getSong($songId);

            if (!$result['success']) {
                continue;
            }

            $song = $result['data'];
            $status = $song['status'] ?? 'unknown';

            $job?->updateProgress(
                (int) (($attempt / $maxAttempts) * 100),
                "กำลังสร้างเพลง... ({$status})"
            );

            if ($status === 'complete') {
                return $song;
            }

            if ($status === 'error') {
                throw new \Exception('Suno API error: ' . ($song['error_message'] ?? 'Unknown error'));
            }
        }

        throw new \Exception('Timeout: การสร้างเพลงใช้เวลานานเกินไป');
    }

    /**
     * สร้าง HTTP headers
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        if ($this->cookie) {
            $headers['Cookie'] = $this->cookie;
        }

        return $headers;
    }
}
