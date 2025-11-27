<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoSetting;
use App\Models\VideoAutoPlatform;
use App\Models\VideoAutoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * YouTube API Service
 *
 * จัดการการอัปโหลดวีดีโอไปยัง YouTube
 *
 * @see https://developers.google.com/youtube/v3/docs
 */
class YouTubeApiService
{
    /**
     * Base URL ของ YouTube Data API
     *
     * @var string
     */
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    /**
     * Upload URL
     *
     * @var string
     */
    protected string $uploadUrl = 'https://www.googleapis.com/upload/youtube/v3/videos';

    /**
     * OAuth URL
     *
     * @var string
     */
    protected string $oauthUrl = 'https://oauth2.googleapis.com';

    /**
     * Platform connection
     *
     * @var VideoAutoPlatform|null
     */
    protected ?VideoAutoPlatform $platform = null;

    /**
     * สร้าง instance ใหม่
     *
     * @param VideoAutoPlatform|null $platform
     */
    public function __construct(?VideoAutoPlatform $platform = null)
    {
        if ($platform) {
            $this->platform = $platform;
        } else {
            // ใช้ default YouTube platform
            $this->platform = VideoAutoPlatform::ofType('youtube')
                ->active()
                ->connected()
                ->orderByDesc('is_default')
                ->first();
        }
    }

    /**
     * ตรวจสอบว่า API พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->platform
            && $this->platform->status === 'connected'
            && !empty($this->platform->decrypted_access_token);
    }

    /**
     * อัปโหลดวีดีโอ
     *
     * @param string $videoPath Path ของไฟล์วีดีโอ
     * @param array $metadata ข้อมูลวีดีโอ
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, data?: array, error?: string}
     *
     * @example
     * $result = $service->uploadVideo('/path/to/video.mp4', [
     *     'title' => 'ชื่อวีดีโอ',
     *     'description' => 'คำอธิบาย',
     *     'tags' => ['tag1', 'tag2'],
     *     'category' => '10', // Music
     *     'privacy' => 'public', // public, private, unlisted
     * ]);
     */
    public function uploadVideo(string $videoPath, array $metadata, ?VideoAutoJob $job = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'YouTube API ยังไม่ได้เชื่อมต่อ กรุณาเชื่อมต่อบัญชี YouTube ก่อน',
            ];
        }

        try {
            $job?->logInfo('เริ่มอัปโหลดวีดีโอไป YouTube', [
                'video_path' => $videoPath,
                'title' => $metadata['title'] ?? '',
            ]);

            // ตรวจสอบว่าไฟล์มีอยู่
            if (!file_exists($videoPath)) {
                throw new \Exception('ไม่พบไฟล์วีดีโอ: ' . $videoPath);
            }

            // Refresh token ถ้าหมดอายุ
            if ($this->platform->is_token_expired) {
                $refreshResult = $this->refreshAccessToken();
                if (!$refreshResult['success']) {
                    return $refreshResult;
                }
            }

            // เตรียม metadata
            $videoMetadata = [
                'snippet' => [
                    'title' => $metadata['title'] ?? 'Untitled Video',
                    'description' => $metadata['description'] ?? '',
                    'tags' => $metadata['tags'] ?? [],
                    'categoryId' => $metadata['category'] ?? '10', // Music
                    'defaultLanguage' => 'th',
                    'defaultAudioLanguage' => 'th',
                ],
                'status' => [
                    'privacyStatus' => $metadata['privacy'] ?? 'private',
                    'selfDeclaredMadeForKids' => false,
                ],
            ];

            // Step 1: เริ่ม resumable upload
            $job?->updateProgress(10, 'กำลังเริ่ม upload session...');
            $uploadSession = $this->initiateUpload($videoMetadata, filesize($videoPath));

            if (!$uploadSession['success']) {
                return $uploadSession;
            }

            $uploadUri = $uploadSession['upload_uri'];

            // Step 2: อัปโหลดไฟล์
            $job?->updateProgress(20, 'กำลังอัปโหลดวีดีโอ...');
            $uploadResult = $this->uploadVideoFile($uploadUri, $videoPath, $job);

            if (!$uploadResult['success']) {
                return $uploadResult;
            }

            // Step 3: อัปโหลด thumbnail (ถ้ามี)
            if (!empty($metadata['thumbnail'])) {
                $job?->updateProgress(90, 'กำลังอัปโหลด thumbnail...');
                $this->uploadThumbnail($uploadResult['data']['id'], $metadata['thumbnail'], $job);
            }

            // บันทึกสถิติ
            $this->platform->recordPost(true);

            $job?->logInfo('อัปโหลดวีดีโอสำเร็จ', [
                'video_id' => $uploadResult['data']['id'] ?? null,
                'url' => 'https://www.youtube.com/watch?v=' . ($uploadResult['data']['id'] ?? ''),
            ]);

            return [
                'success' => true,
                'data' => $uploadResult['data'],
                'url' => 'https://www.youtube.com/watch?v=' . ($uploadResult['data']['id'] ?? ''),
            ];

        } catch (\Exception $e) {
            Log::error('YouTube API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $job?->logError('YouTube API exception', ['message' => $e->getMessage()]);

            $this->platform?->recordPost(false);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * เริ่ม resumable upload session
     *
     * @param array $metadata
     * @param int $fileSize
     * @return array
     */
    protected function initiateUpload(array $metadata, int $fileSize): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->platform->decrypted_access_token,
            'Content-Type' => 'application/json',
            'X-Upload-Content-Length' => $fileSize,
            'X-Upload-Content-Type' => 'video/*',
        ])->post($this->uploadUrl . '?uploadType=resumable&part=snippet,status', $metadata);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถเริ่ม upload session ได้: ' . $response->body(),
            ];
        }

        $uploadUri = $response->header('Location');

        if (!$uploadUri) {
            return [
                'success' => false,
                'error' => 'ไม่ได้รับ upload URI',
            ];
        }

        return [
            'success' => true,
            'upload_uri' => $uploadUri,
        ];
    }

    /**
     * อัปโหลดไฟล์วีดีโอ
     *
     * @param string $uploadUri
     * @param string $videoPath
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function uploadVideoFile(string $uploadUri, string $videoPath, ?VideoAutoJob $job = null): array
    {
        $fileSize = filesize($videoPath);
        $chunkSize = 10 * 1024 * 1024; // 10 MB chunks
        $handle = fopen($videoPath, 'rb');
        $uploadedBytes = 0;

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            $chunkLength = strlen($chunk);
            $endByte = $uploadedBytes + $chunkLength - 1;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->platform->decrypted_access_token,
                'Content-Type' => 'video/*',
                'Content-Length' => $chunkLength,
                'Content-Range' => "bytes {$uploadedBytes}-{$endByte}/{$fileSize}",
            ])
                ->timeout(300)
                ->withBody($chunk, 'video/*')
                ->put($uploadUri);

            $uploadedBytes += $chunkLength;

            // อัพเดท progress
            $progress = 20 + (int) (($uploadedBytes / $fileSize) * 60); // 20-80%
            $job?->updateProgress($progress, sprintf('อัปโหลด %.1f%%', ($uploadedBytes / $fileSize) * 100));

            // ตรวจสอบ response
            if ($response->status() === 200 || $response->status() === 201) {
                // Upload complete
                fclose($handle);

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } elseif ($response->status() !== 308) {
                // Error
                fclose($handle);

                return [
                    'success' => false,
                    'error' => 'อัปโหลดล้มเหลว: ' . $response->body(),
                ];
            }
        }

        fclose($handle);

        return [
            'success' => false,
            'error' => 'อัปโหลดไม่สมบูรณ์',
        ];
    }

    /**
     * อัปโหลด thumbnail
     *
     * @param string $videoId
     * @param string $thumbnailPath
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function uploadThumbnail(string $videoId, string $thumbnailPath, ?VideoAutoJob $job = null): array
    {
        try {
            if (!file_exists($thumbnailPath)) {
                return [
                    'success' => false,
                    'error' => 'ไม่พบไฟล์ thumbnail',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->platform->decrypted_access_token,
            ])
                ->attach('media', file_get_contents($thumbnailPath), basename($thumbnailPath))
                ->post($this->baseUrl . '/thumbnails/set?videoId=' . $videoId);

            if ($response->successful()) {
                $job?->logInfo('อัปโหลด thumbnail สำเร็จ');
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'error' => 'อัปโหลด thumbnail ล้มเหลว: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh access token
     *
     * @return array
     */
    public function refreshAccessToken(): array
    {
        try {
            $clientId = VideoAutoSetting::getValue('youtube_client_id');
            $clientSecret = VideoAutoSetting::getValue('youtube_client_secret');
            $refreshToken = $this->platform->decrypted_refresh_token;

            if (!$refreshToken) {
                return [
                    'success' => false,
                    'error' => 'ไม่มี refresh token',
                ];
            }

            $response = Http::asForm()->post($this->oauthUrl . '/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                $this->platform->updateConnectionStatus('expired', 'Token refresh failed');

                return [
                    'success' => false,
                    'error' => 'ไม่สามารถ refresh token ได้',
                ];
            }

            $data = $response->json();

            // อัพเดท platform
            $this->platform->access_token = $data['access_token'];
            $this->platform->token_expires_at = now()->addSeconds($data['expires_in'] ?? 3600);
            $this->platform->status = 'connected';
            $this->platform->save();

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดึง OAuth URL สำหรับ authorization
     *
     * @param string $redirectUri
     * @return string
     */
    public function getAuthorizationUrl(string $redirectUri): string
    {
        $clientId = VideoAutoSetting::getValue('youtube_client_id');

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/youtube.upload',
                'https://www.googleapis.com/auth/youtube',
                'https://www.googleapis.com/auth/youtube.readonly',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * แลก authorization code เป็น access token
     *
     * @param string $code
     * @param string $redirectUri
     * @return array
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        try {
            $clientId = VideoAutoSetting::getValue('youtube_client_id');
            $clientSecret = VideoAutoSetting::getValue('youtube_client_secret');

            $response = Http::asForm()->post($this->oauthUrl . '/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถแลก code ได้: ' . $response->body(),
                ];
            }

            $data = $response->json();

            // ดึงข้อมูล channel
            $channelInfo = $this->getChannelInfo($data['access_token']);

            return [
                'success' => true,
                'data' => array_merge($data, ['channel' => $channelInfo]),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงข้อมูล channel
     *
     * @param string $accessToken
     * @return array|null
     */
    public function getChannelInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->baseUrl . '/channels', [
                'part' => 'snippet,contentDetails,statistics',
                'mine' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['items'][0] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ทดสอบการเชื่อมต่อ
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (!$this->platform) {
            return [
                'success' => false,
                'message' => 'ยังไม่ได้เชื่อมต่อบัญชี YouTube',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Token หมดอายุหรือไม่ถูกต้อง กรุณาเชื่อมต่อใหม่',
            ];
        }

        // ทดสอบด้วยการดึงข้อมูล channel
        $channelInfo = $this->getChannelInfo($this->platform->decrypted_access_token);

        if ($channelInfo) {
            return [
                'success' => true,
                'message' => 'เชื่อมต่อสำเร็จ! Channel: ' . ($channelInfo['snippet']['title'] ?? 'Unknown'),
            ];
        }

        return [
            'success' => false,
            'message' => 'ไม่สามารถดึงข้อมูล channel ได้',
        ];
    }
}
