<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoPlatform;
use App\Models\VideoAutoJob;
use App\Models\VideoAutoProject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Social Publisher Service
 *
 * จัดการการโพสวีดีโอไปยัง Social Media Platforms ต่างๆ
 */
class SocialPublisherService
{
    /**
     * YouTube Service
     *
     * @var YouTubeApiService
     */
    protected YouTubeApiService $youtubeService;

    /**
     * สร้าง instance ใหม่
     */
    public function __construct()
    {
        $this->youtubeService = new YouTubeApiService();
    }

    /**
     * โพสวีดีโอไปยังทุก platform ที่ตั้งค่าไว้
     *
     * @param VideoAutoProject $project โปรเจกต์
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, results: array}
     */
    public function publishToAll(VideoAutoProject $project, array $options = [], ?VideoAutoJob $job = null): array
    {
        $results = [];
        $targetPlatforms = $project->target_platforms ?? [];

        if (empty($targetPlatforms)) {
            // ถ้าไม่ได้ระบุ ให้ใช้ทุก platform ที่เชื่อมต่อแล้ว
            $platforms = VideoAutoPlatform::active()->connected()->ordered()->get();
            $targetPlatforms = $platforms->pluck('platform_type')->toArray();
        }

        $job?->logInfo('เริ่มโพสไปยัง platforms', ['platforms' => $targetPlatforms]);

        $total = count($targetPlatforms);
        $current = 0;

        foreach ($targetPlatforms as $platformType) {
            $current++;
            $job?->updateProgress(
                (int) (($current / $total) * 100),
                "กำลังโพสไปยัง " . ucfirst($platformType) . "..."
            );

            $result = $this->publishToPlatform($project, $platformType, $job);

            $results[$platformType] = $result;

            // บันทึกผลการโพส
            $project->recordPublishResult($platformType, $result['success'], $result);

            // หน่วงเวลาถ้าตั้งค่า stagger
            if ($options['stagger'] ?? false) {
                $staggerInterval = $options['stagger_interval'] ?? 30;
                sleep($staggerInterval);
            }
        }

        // ตรวจสอบว่าทุก platform สำเร็จหรือไม่
        $allSuccess = collect($results)->every(fn($r) => $r['success']);
        $anySuccess = collect($results)->contains(fn($r) => $r['success']);

        // อัพเดท status ของ project
        if ($anySuccess) {
            $project->updateStatus('published');
        }

        return [
            'success' => $anySuccess,
            'all_success' => $allSuccess,
            'results' => $results,
        ];
    }

    /**
     * โพสวีดีโอไปยัง platform ที่ระบุ
     *
     * @param VideoAutoProject $project
     * @param string $platformType
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function publishToPlatform(
        VideoAutoProject $project,
        string $platformType,
        ?VideoAutoJob $job = null
    ): array {
        $job?->logInfo("กำลังโพสไปยัง {$platformType}");

        return match ($platformType) {
            'youtube' => $this->publishToYouTube($project, $job),
            'facebook' => $this->publishToFacebook($project, $job),
            'instagram' => $this->publishToInstagram($project, $job),
            'tiktok' => $this->publishToTikTok($project, $job),
            'twitter' => $this->publishToTwitter($project, $job),
            'threads' => $this->publishToThreads($project, $job),
            default => [
                'success' => false,
                'error' => "ไม่รองรับ platform: {$platformType}",
            ],
        };
    }

    /**
     * โพสไปยัง YouTube
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function publishToYouTube(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $videoPath = $project->generated_video_path;

        if (!$videoPath || !file_exists($videoPath)) {
            // ลองหา path จาก storage
            $videoPath = storage_path('app/public/' . $project->generated_video_path);
        }

        if (!file_exists($videoPath)) {
            return [
                'success' => false,
                'error' => 'ไม่พบไฟล์วีดีโอ',
            ];
        }

        $metadata = [
            'title' => $project->video_title ?: $project->name,
            'description' => $project->video_description ?: '',
            'tags' => $project->video_tags ?: [],
            'category' => $project->video_category ?: '10', // Music
            'privacy' => $project->video_privacy ?: 'public',
            'thumbnail' => $project->video_thumbnail,
        ];

        return $this->youtubeService->uploadVideo($videoPath, $metadata, $job);
    }

    /**
     * โพสไปยัง Facebook
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function publishToFacebook(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('facebook')
            ->active()
            ->connected()
            ->first();

        if (!$platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ Facebook',
            ];
        }

        try {
            $videoPath = storage_path('app/public/' . $project->generated_video_path);

            if (!file_exists($videoPath)) {
                return [
                    'success' => false,
                    'error' => 'ไม่พบไฟล์วีดีโอ',
                ];
            }

            // Facebook Graph API - Video Upload
            $pageId = $platform->channel_id;
            $accessToken = $platform->decrypted_access_token;

            // Step 1: เริ่ม upload session
            $response = Http::post("https://graph.facebook.com/v18.0/{$pageId}/videos", [
                'access_token' => $accessToken,
                'upload_phase' => 'start',
                'file_size' => filesize($videoPath),
            ]);

            if (!$response->successful()) {
                throw new \Exception('ไม่สามารถเริ่ม upload session ได้');
            }

            $data = $response->json();
            $uploadSessionId = $data['upload_session_id'];
            $videoId = $data['video_id'];

            // Step 2: อัปโหลดไฟล์
            $startOffset = 0;
            $fileHandle = fopen($videoPath, 'rb');

            while (!feof($fileHandle)) {
                $chunk = fread($fileHandle, 10 * 1024 * 1024); // 10 MB

                $response = Http::attach('video_file_chunk', $chunk, 'video.mp4')
                    ->post("https://graph.facebook.com/v18.0/{$pageId}/videos", [
                        'access_token' => $accessToken,
                        'upload_phase' => 'transfer',
                        'upload_session_id' => $uploadSessionId,
                        'start_offset' => $startOffset,
                    ]);

                $result = $response->json();
                $startOffset = $result['start_offset'] ?? $startOffset + strlen($chunk);
            }

            fclose($fileHandle);

            // Step 3: เสร็จสิ้น upload
            $response = Http::post("https://graph.facebook.com/v18.0/{$pageId}/videos", [
                'access_token' => $accessToken,
                'upload_phase' => 'finish',
                'upload_session_id' => $uploadSessionId,
                'title' => $project->video_title ?: $project->name,
                'description' => $project->video_description ?: '',
            ]);

            if ($response->successful()) {
                $platform->recordPost(true);
                $job?->logInfo('โพส Facebook สำเร็จ', ['video_id' => $videoId]);

                return [
                    'success' => true,
                    'video_id' => $videoId,
                    'url' => "https://www.facebook.com/{$pageId}/videos/{$videoId}",
                ];
            }

            throw new \Exception('อัปโหลดไม่สำเร็จ: ' . $response->body());

        } catch (\Exception $e) {
            $platform?->recordPost(false);
            $job?->logError('โพส Facebook ล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * โพสไปยัง Instagram
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function publishToInstagram(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('instagram')
            ->active()
            ->connected()
            ->first();

        if (!$platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ Instagram',
            ];
        }

        try {
            $videoUrl = $project->generated_video_url;
            $accessToken = $platform->decrypted_access_token;
            $igUserId = $platform->channel_id;

            // Instagram Graph API - Reels Upload
            // Step 1: สร้าง container
            $response = Http::post("https://graph.facebook.com/v18.0/{$igUserId}/media", [
                'access_token' => $accessToken,
                'media_type' => 'REELS',
                'video_url' => $videoUrl,
                'caption' => $project->video_description ?: $project->video_title,
                'share_to_feed' => true,
            ]);

            if (!$response->successful()) {
                throw new \Exception('ไม่สามารถสร้าง media container ได้');
            }

            $containerId = $response->json('id');

            // Step 2: รอให้ upload เสร็จ
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;

                $statusResponse = Http::get("https://graph.facebook.com/v18.0/{$containerId}", [
                    'access_token' => $accessToken,
                    'fields' => 'status_code',
                ]);

                $status = $statusResponse->json('status_code');

                if ($status === 'FINISHED') {
                    break;
                } elseif ($status === 'ERROR') {
                    throw new \Exception('Upload ล้มเหลว');
                }
            }

            // Step 3: Publish
            $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$igUserId}/media_publish", [
                'access_token' => $accessToken,
                'creation_id' => $containerId,
            ]);

            if ($publishResponse->successful()) {
                $platform->recordPost(true);
                $mediaId = $publishResponse->json('id');
                $job?->logInfo('โพส Instagram สำเร็จ', ['media_id' => $mediaId]);

                return [
                    'success' => true,
                    'media_id' => $mediaId,
                    'url' => "https://www.instagram.com/reel/{$mediaId}",
                ];
            }

            throw new \Exception('Publish ล้มเหลว: ' . $publishResponse->body());

        } catch (\Exception $e) {
            $platform?->recordPost(false);
            $job?->logError('โพส Instagram ล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * โพสไปยัง TikTok
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function publishToTikTok(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('tiktok')
            ->active()
            ->connected()
            ->first();

        if (!$platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ TikTok',
            ];
        }

        try {
            $videoUrl = $project->generated_video_url;
            $accessToken = $platform->decrypted_access_token;

            // TikTok Content Posting API
            // Step 1: Initialize upload
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post('https://open.tiktokapis.com/v2/post/publish/video/init/', [
                'post_info' => [
                    'title' => $project->video_title ?: $project->name,
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $videoUrl,
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('ไม่สามารถเริ่ม upload ได้: ' . $response->body());
            }

            $publishId = $response->json('data.publish_id');

            // Step 2: รอให้ upload เสร็จ
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', [
                    'publish_id' => $publishId,
                ]);

                $status = $statusResponse->json('data.status');

                if ($status === 'PUBLISH_COMPLETE') {
                    $platform->recordPost(true);
                    $job?->logInfo('โพส TikTok สำเร็จ');

                    return [
                        'success' => true,
                        'publish_id' => $publishId,
                    ];
                } elseif ($status === 'FAILED') {
                    throw new \Exception('Publish ล้มเหลว');
                }
            }

            throw new \Exception('Timeout: รอนานเกินไป');

        } catch (\Exception $e) {
            $platform?->recordPost(false);
            $job?->logError('โพส TikTok ล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * โพสไปยัง Twitter/X
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function publishToTwitter(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('twitter')
            ->active()
            ->connected()
            ->first();

        if (!$platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ Twitter/X',
            ];
        }

        // TODO: Implement Twitter/X API v2 video upload
        // ต้องใช้ OAuth 1.0a และ Media Upload API

        $job?->logInfo('Twitter/X upload ยังไม่พร้อมใช้งาน');

        return [
            'success' => false,
            'error' => 'Twitter/X upload ยังไม่พร้อมใช้งาน - กำลังพัฒนา',
        ];
    }

    /**
     * โพสไปยัง Threads
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function publishToThreads(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('threads')
            ->active()
            ->connected()
            ->first();

        if (!$platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ Threads',
            ];
        }

        // Threads API (ใช้ Instagram Graph API)
        // TODO: Implement when Threads API is fully available

        $job?->logInfo('Threads upload ยังไม่พร้อมใช้งาน');

        return [
            'success' => false,
            'error' => 'Threads upload ยังไม่พร้อมใช้งาน - กำลังพัฒนา',
        ];
    }

    /**
     * ตรวจสอบสถานะการเชื่อมต่อทุก platform
     *
     * @return array
     */
    public function checkAllConnections(): array
    {
        $platforms = VideoAutoPlatform::active()->get();
        $results = [];

        foreach ($platforms as $platform) {
            $results[$platform->platform_type] = [
                'name' => $platform->name,
                'status' => $platform->status,
                'connected' => $platform->status === 'connected',
                'channel_name' => $platform->channel_name,
                'is_default' => $platform->is_default,
                'success_rate' => $platform->success_rate,
            ];
        }

        return $results;
    }
}
