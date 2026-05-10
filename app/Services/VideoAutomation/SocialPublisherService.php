<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoJob;
use App\Models\VideoAutoPlatform;
use App\Models\VideoAutoProject;
use Illuminate\Support\Facades\Http;

/**
 * Social Publisher Service
 *
 * จัดการการโพสวีดีโอไปยัง Social Media Platforms ต่างๆ
 */
class SocialPublisherService
{
    /**
     * YouTube Service (lazy loaded)
     */
    protected ?YouTubeApiService $youtubeService = null;

    /**
     * สร้าง instance ใหม่
     *
     * ไม่ได้สร้าง YouTubeApiService ใน constructor แล้ว
     * ใช้ lazy loading เพื่อหลีกเลี่ยง database query ตอน route:cache
     */
    public function __construct()
    {
        // ไม่สร้าง service ใน constructor - ใช้ lazy loading แทน
    }

    /**
     * ดึง YouTube service แบบ lazy loading
     */
    protected function getYouTubeService(): YouTubeApiService
    {
        if ($this->youtubeService === null) {
            $this->youtubeService = new YouTubeApiService;
        }

        return $this->youtubeService;
    }

    /**
     * โพสวีดีโอไปยังทุก platform ที่ตั้งค่าไว้
     *
     * @param  VideoAutoProject  $project  โปรเจกต์
     * @param  array  $options  ตัวเลือกเพิ่มเติม
     * @param  VideoAutoJob|null  $job  Job สำหรับ logging
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
                'กำลังโพสไปยัง '.ucfirst($platformType).'...'
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
        $allSuccess = collect($results)->every(fn ($r) => $r['success']);
        $anySuccess = collect($results)->contains(fn ($r) => $r['success']);

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
            'lemon8' => $this->publishToLemon8($project, $job),
            'line_voom' => $this->publishToLineVoom($project, $job),
            default => [
                'success' => false,
                'error' => "ไม่รองรับ platform: {$platformType}",
            ],
        };
    }

    /**
     * โพสไปยัง YouTube
     */
    protected function publishToYouTube(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $videoPath = $project->generated_video_path;

        if (! $videoPath || ! file_exists($videoPath)) {
            // ลองหา path จาก storage
            $videoPath = storage_path('app/public/'.$project->generated_video_path);
        }

        if (! file_exists($videoPath)) {
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

        return $this->getYouTubeService()->uploadVideo($videoPath, $metadata, $job);
    }

    /**
     * โพสไปยัง Facebook
     */
    protected function publishToFacebook(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('facebook')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ Facebook',
            ];
        }

        try {
            $videoPath = storage_path('app/public/'.$project->generated_video_path);

            if (! file_exists($videoPath)) {
                return [
                    'success' => false,
                    'error' => 'ไม่พบไฟล์วีดีโอ',
                ];
            }

            // Facebook Graph API - Video Upload
            $pageId = $platform->channel_id;
            $accessToken = $platform->decrypted_access_token;

            // Step 1: เริ่ม upload session
            $response = Http::post("https://graph.facebook.com/v21.0/{$pageId}/videos", [
                'access_token' => $accessToken,
                'upload_phase' => 'start',
                'file_size' => filesize($videoPath),
            ]);

            if (! $response->successful()) {
                throw new \Exception('ไม่สามารถเริ่ม upload session ได้');
            }

            $data = $response->json();
            $uploadSessionId = $data['upload_session_id'];
            $videoId = $data['video_id'];

            // Step 2: อัปโหลดไฟล์
            $startOffset = 0;
            $fileHandle = fopen($videoPath, 'rb');

            while (! feof($fileHandle)) {
                $chunk = fread($fileHandle, 10 * 1024 * 1024); // 10 MB

                $response = Http::attach('video_file_chunk', $chunk, 'video.mp4')
                    ->post("https://graph.facebook.com/v21.0/{$pageId}/videos", [
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
            $response = Http::post("https://graph.facebook.com/v21.0/{$pageId}/videos", [
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

            throw new \Exception('อัปโหลดไม่สำเร็จ: '.$response->body());
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
     */
    protected function publishToInstagram(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('instagram')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
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
            $response = Http::post("https://graph.facebook.com/v21.0/{$igUserId}/media", [
                'access_token' => $accessToken,
                'media_type' => 'REELS',
                'video_url' => $videoUrl,
                'caption' => $project->video_description ?: $project->video_title,
                'share_to_feed' => true,
            ]);

            if (! $response->successful()) {
                throw new \Exception('ไม่สามารถสร้าง media container ได้');
            }

            $containerId = $response->json('id');

            // Step 2: รอให้ upload เสร็จ
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;

                $statusResponse = Http::get("https://graph.facebook.com/v21.0/{$containerId}", [
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
            $publishResponse = Http::post("https://graph.facebook.com/v21.0/{$igUserId}/media_publish", [
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

            throw new \Exception('Publish ล้มเหลว: '.$publishResponse->body());
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
     */
    protected function publishToTikTok(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('tiktok')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
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
                'Authorization' => 'Bearer '.$accessToken,
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

            if (! $response->successful()) {
                throw new \Exception('ไม่สามารถเริ่ม upload ได้: '.$response->body());
            }

            $publishId = $response->json('data.publish_id');

            // Step 2: รอให้ upload เสร็จ
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
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
     */
    protected function publishToTwitter(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('twitter')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
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
     */
    protected function publishToThreads(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('threads')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
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
     * โพสไปยัง Lemon8
     *
     * Lemon8 เป็น platform จาก ByteDance (เจ้าเดียวกับ TikTok)
     * รองรับการโพสวีดีโอและรูปภาพ พร้อมแคปชัน
     */
    protected function publishToLemon8(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('lemon8')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ Lemon8',
            ];
        }

        try {
            $videoUrl = $project->generated_video_url;
            $accessToken = $platform->decrypted_access_token;

            // Lemon8 API (คล้าย TikTok Content Posting API)
            // ใช้ ByteDance Open Platform
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post('https://open.lemon8.com/v2/post/publish/video/init/', [
                'post_info' => [
                    'title' => $project->video_title ?: $project->name,
                    'description' => $project->video_description ?: '',
                    'privacy_level' => 'PUBLIC',
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $videoUrl,
                ],
            ]);

            if (! $response->successful()) {
                throw new \Exception('ไม่สามารถเริ่ม upload ได้: '.$response->body());
            }

            $publishId = $response->json('data.publish_id');

            // รอให้ upload เสร็จ
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                ])->post('https://open.lemon8.com/v2/post/publish/status/fetch/', [
                    'publish_id' => $publishId,
                ]);

                $status = $statusResponse->json('data.status');

                if ($status === 'PUBLISH_COMPLETE') {
                    $platform->recordPost(true);
                    $postId = $statusResponse->json('data.post_id');
                    $job?->logInfo('โพส Lemon8 สำเร็จ', ['post_id' => $postId]);

                    return [
                        'success' => true,
                        'publish_id' => $publishId,
                        'post_id' => $postId,
                        'url' => "https://www.lemon8-app.com/post/{$postId}",
                    ];
                } elseif ($status === 'FAILED') {
                    throw new \Exception('Publish ล้มเหลว');
                }
            }

            throw new \Exception('Timeout: รอนานเกินไป');
        } catch (\Exception $e) {
            $platform?->recordPost(false);
            $job?->logError('โพส Lemon8 ล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * โพสไปยัง LINE VOOM
     *
     * LINE VOOM เป็น platform วีดีโอสั้นของ LINE
     * ใช้ LINE Messaging API หรือ LINE Official Account API
     */
    protected function publishToLineVoom(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $platform = VideoAutoPlatform::ofType('line_voom')
            ->active()
            ->connected()
            ->first();

        if (! $platform) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้เชื่อมต่อ LINE VOOM',
            ];
        }

        try {
            $videoUrl = $project->generated_video_url;
            $accessToken = $platform->decrypted_access_token;

            // LINE Content API สำหรับ LINE VOOM
            // ใช้ LINE Official Account API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post('https://api.line.me/v2/bot/voom/post', [
                'type' => 'video',
                'originalContentUrl' => $videoUrl,
                'previewImageUrl' => $project->thumbnail_url ?? $project->video_thumbnail ?? '',
                'caption' => [
                    'text' => ($project->video_title ?: $project->name)."\n\n".($project->video_description ?: ''),
                ],
                'visibility' => 'public',
            ]);

            if ($response->successful()) {
                $platform->recordPost(true);
                $postId = $response->json('postId');
                $job?->logInfo('โพส LINE VOOM สำเร็จ', ['post_id' => $postId]);

                return [
                    'success' => true,
                    'post_id' => $postId,
                    'url' => "https://voom.line.me/post/{$postId}",
                ];
            }

            throw new \Exception('Publish ล้มเหลว: '.$response->body());
        } catch (\Exception $e) {
            $platform?->recordPost(false);
            $job?->logError('โพส LINE VOOM ล้มเหลว', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบสถานะการเชื่อมต่อทุก platform
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
