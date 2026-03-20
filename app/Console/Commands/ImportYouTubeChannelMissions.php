<?php

namespace App\Console\Commands;

use App\Models\VideoMission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Import Video Missions from YouTube Channel
 *
 * ดึงวิดีโอทั้งหมดจากช่อง YouTube และสร้างเป็นภารกิจดูคลิป
 * ใช้ YouTube Data API v3
 *
 * @example php artisan video-missions:import-youtube UCEfNuxMsSvg8OTUzo53yIYg
 * @example php artisan video-missions:import-youtube UCEfNuxMsSvg8OTUzo53yIYg --reward=5 --limit=50
 */
class ImportYouTubeChannelMissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video-missions:import-youtube
                            {channel_id : YouTube Channel ID (เริ่มต้นด้วย UC)}
                            {--api-key= : YouTube Data API Key (หรือใช้จาก .env)}
                            {--reward=1 : รางวัลเงิน (บาท) ต่อภารกิจ}
                            {--coins=10 : รางวัล Coins ต่อภารกิจ}
                            {--exp=5 : รางวัล EXP ต่อภารกิจ}
                            {--watch-percent=80 : เปอร์เซ็นต์ที่ต้องดู}
                            {--limit=100 : จำนวนวิดีโอสูงสุดที่จะดึง}
                            {--featured : ตั้งเป็นภารกิจแนะนำ}
                            {--active : เปิดใช้งานทันที (default: true)}
                            {--dry-run : แสดงผลลัพธ์โดยไม่บันทึกลง database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'นำเข้าวิดีโอจากช่อง YouTube เป็นภารกิจดูคลิปรับรางวัล';

    /**
     * YouTube Data API base URL
     */
    protected string $apiBaseUrl = 'https://www.googleapis.com/youtube/v3';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $channelId = $this->argument('channel_id');
        $apiKey = $this->option('api-key') ?: config('services.youtube.api_key');

        if (! $apiKey) {
            $this->error('❌ ไม่พบ YouTube API Key!');
            $this->info('กรุณาระบุ --api-key หรือตั้งค่า YOUTUBE_API_KEY ใน .env');
            $this->newLine();
            $this->info('วิธีรับ API Key:');
            $this->info('1. ไปที่ https://console.cloud.google.com/');
            $this->info('2. สร้าง Project ใหม่หรือเลือก Project ที่มีอยู่');
            $this->info('3. เปิดใช้งาน YouTube Data API v3');
            $this->info('4. สร้าง API Key จากเมนู Credentials');

            return Command::FAILURE;
        }

        $this->info("🎬 กำลังดึงวิดีโอจากช่อง: {$channelId}");
        $this->newLine();

        // ดึงข้อมูลช่อง
        $channelInfo = $this->getChannelInfo($channelId, $apiKey);
        if (! $channelInfo) {
            $this->error('❌ ไม่พบช่อง YouTube หรือ API Key ไม่ถูกต้อง');

            return Command::FAILURE;
        }

        $this->info("📺 ช่อง: {$channelInfo['title']}");
        $this->info('📝 คำอธิบาย: '.substr($channelInfo['description'] ?? 'ไม่มี', 0, 100).'...');
        $this->info('👥 ผู้ติดตาม: '.number_format($channelInfo['subscriberCount'] ?? 0));
        $this->info('🎥 วิดีโอทั้งหมด: '.number_format($channelInfo['videoCount'] ?? 0));
        $this->newLine();

        // ดึง Playlist ID ของวิดีโอที่อัปโหลด
        $uploadsPlaylistId = $channelInfo['uploadsPlaylistId'] ?? null;
        if (! $uploadsPlaylistId) {
            $this->error('❌ ไม่พบ Uploads Playlist');

            return Command::FAILURE;
        }

        // ดึงรายการวิดีโอ
        $limit = (int) $this->option('limit');
        $videos = $this->getPlaylistVideos($uploadsPlaylistId, $apiKey, $limit);

        if (empty($videos)) {
            $this->warn('⚠️ ไม่พบวิดีโอในช่อง');

            return Command::SUCCESS;
        }

        $this->info('📋 พบวิดีโอ: '.count($videos).' รายการ');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 โหมด Dry Run - ไม่บันทึกลง database');
            $this->newLine();
        }

        // แสดง progress bar
        $bar = $this->output->createProgressBar(count($videos));
        $bar->start();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($videos as $video) {
            try {
                $result = $this->createMissionFromVideo($video, $apiKey);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("Import YouTube Mission Error: {$e->getMessage()}", [
                    'video_id' => $video['videoId'] ?? 'unknown',
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // แสดงสรุป
        $this->info('✅ สรุปการนำเข้า:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['สร้างใหม่', $created],
                ['ข้าม (มีอยู่แล้ว)', $skipped],
                ['เกิดข้อผิดพลาด', $errors],
                ['รวมทั้งหมด', count($videos)],
            ]
        );

        if ($created > 0 && ! $this->option('dry-run')) {
            $this->newLine();
            $this->info("🎉 นำเข้าภารกิจสำเร็จ {$created} รายการ!");
            $this->info('💰 รางวัลต่อภารกิจ: ฿'.number_format($this->option('reward'), 2).
                       ' + '.$this->option('coins').' Coins'.
                       ' + '.$this->option('exp').' EXP');
        }

        return Command::SUCCESS;
    }

    /**
     * ดึงข้อมูลช่อง YouTube
     */
    protected function getChannelInfo(string $channelId, string $apiKey): ?array
    {
        try {
            $response = Http::get("{$this->apiBaseUrl}/channels", [
                'key' => $apiKey,
                'id' => $channelId,
                'part' => 'snippet,contentDetails,statistics',
            ]);

            if (! $response->successful()) {
                $this->error('API Error: '.$response->body());

                return null;
            }

            $data = $response->json();

            if (empty($data['items'])) {
                return null;
            }

            $channel = $data['items'][0];

            return [
                'id' => $channel['id'],
                'title' => $channel['snippet']['title'] ?? '',
                'description' => $channel['snippet']['description'] ?? '',
                'thumbnail' => $channel['snippet']['thumbnails']['high']['url'] ?? null,
                'subscriberCount' => $channel['statistics']['subscriberCount'] ?? 0,
                'videoCount' => $channel['statistics']['videoCount'] ?? 0,
                'uploadsPlaylistId' => $channel['contentDetails']['relatedPlaylists']['uploads'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * ดึงวิดีโอจาก Playlist
     */
    protected function getPlaylistVideos(string $playlistId, string $apiKey, int $limit = 100): array
    {
        $videos = [];
        $nextPageToken = null;

        do {
            $params = [
                'key' => $apiKey,
                'playlistId' => $playlistId,
                'part' => 'snippet,contentDetails',
                'maxResults' => min(50, $limit - count($videos)),
            ];

            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            $response = Http::get("{$this->apiBaseUrl}/playlistItems", $params);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();

            foreach ($data['items'] ?? [] as $item) {
                $videos[] = [
                    'videoId' => $item['contentDetails']['videoId'] ?? null,
                    'title' => $item['snippet']['title'] ?? '',
                    'description' => $item['snippet']['description'] ?? '',
                    'thumbnail' => $item['snippet']['thumbnails']['high']['url']
                                ?? $item['snippet']['thumbnails']['medium']['url']
                                ?? $item['snippet']['thumbnails']['default']['url']
                                ?? null,
                    'publishedAt' => $item['snippet']['publishedAt'] ?? null,
                    'channelTitle' => $item['snippet']['channelTitle'] ?? '',
                ];

                if (count($videos) >= $limit) {
                    break 2;
                }
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken && count($videos) < $limit);

        return $videos;
    }

    /**
     * ดึงรายละเอียดวิดีโอ (ความยาว, views, etc.)
     */
    protected function getVideoDetails(string $videoId, string $apiKey): ?array
    {
        try {
            $response = Http::get("{$this->apiBaseUrl}/videos", [
                'key' => $apiKey,
                'id' => $videoId,
                'part' => 'contentDetails,statistics',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data['items'])) {
                return null;
            }

            $video = $data['items'][0];

            // แปลง ISO 8601 duration เป็นวินาที
            $duration = $video['contentDetails']['duration'] ?? 'PT0S';
            $durationSeconds = $this->parseDuration($duration);

            return [
                'duration' => $durationSeconds,
                'viewCount' => (int) ($video['statistics']['viewCount'] ?? 0),
                'likeCount' => (int) ($video['statistics']['likeCount'] ?? 0),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * แปลง ISO 8601 Duration เป็นวินาที
     */
    protected function parseDuration(string $duration): int
    {
        $interval = new \DateInterval($duration);

        return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }

    /**
     * สร้างภารกิจจากวิดีโอ
     */
    protected function createMissionFromVideo(array $video, string $apiKey): string
    {
        $videoId = $video['videoId'];

        if (! $videoId) {
            return 'error';
        }

        // ตรวจสอบว่ามีอยู่แล้วหรือไม่
        $exists = VideoMission::where('video_id', $videoId)->exists();
        if ($exists) {
            return 'skipped';
        }

        if ($this->option('dry-run')) {
            return 'created';
        }

        // ดึงรายละเอียดวิดีโอ
        $details = $this->getVideoDetails($videoId, $apiKey);
        $durationSeconds = $details['duration'] ?? 60;

        // คำนวณเวลาที่ต้องดู
        $watchPercent = (int) $this->option('watch-percent');
        $requiredWatchSeconds = (int) ceil($durationSeconds * ($watchPercent / 100));

        // ถ้าวิดีโอสั้นมาก ให้ต้องดูทั้งหมด
        if ($durationSeconds < 60) {
            $requiredWatchSeconds = $durationSeconds;
        }

        // สร้างภารกิจ
        VideoMission::create([
            'title' => $video['title'],
            'title_th' => $video['title'],
            'description' => substr($video['description'], 0, 500),
            'description_th' => substr($video['description'], 0, 500),
            'thumbnail' => $video['thumbnail'],

            'video_url' => "https://www.youtube.com/watch?v={$videoId}",
            'video_type' => 'youtube',
            'video_id' => $videoId,
            'video_duration_seconds' => $durationSeconds,

            'required_watch_seconds' => $requiredWatchSeconds,
            'required_watch_percentage' => $watchPercent,

            'reward_type' => 'multi',
            'reward_money' => (float) $this->option('reward'),
            'reward_coins' => (float) $this->option('coins'),
            'reward_exp' => (int) $this->option('exp'),

            'frequency' => 'daily',
            'cooldown_minutes' => 0,

            'anti_cheat_enabled' => true,
            'require_focus' => true,
            'detect_tab_switch' => true,
            'max_tab_switches' => 3,

            'is_active' => $this->option('active') !== false,
            'is_featured' => $this->option('featured') ?? false,
            'priority' => 0,
            'sort_order' => 0,

            'category' => 'youtube',
            'tags' => [$video['channelTitle'] ?? 'YouTube'],
        ]);

        return 'created';
    }
}
