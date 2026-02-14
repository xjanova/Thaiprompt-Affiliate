<?php

namespace Database\Seeders;

use App\Models\VideoMission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Video Mission YouTube Seeder
 *
 * นำเข้าวิดีโอจากช่อง YouTube ที่กำหนดเป็นภารกิจดูคลิป
 * รองรับทั้งการนำเข้าผ่าน YouTube API และ hard-coded videos
 *
 * ช่อง Default: @Metal-XProject (UCEfNuxMsSvg8OTUzo53yIYg)
 *
 * @see App\Console\Commands\ImportYouTubeChannelMissions
 */
class VideoMissionYouTubeSeeder extends Seeder
{
    /**
     * Channel ID ของช่อง @Metal-XProject
     */
    protected string $channelId = 'UCEfNuxMsSvg8OTUzo53yIYg';

    /**
     * Channel Handle
     */
    protected string $channelHandle = '@Metal-XProject';

    /**
     * รางวัลเริ่มต้น
     */
    protected array $defaultRewards = [
        'money' => 1.00,
        'coins' => 10,
        'exp' => 5,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎬 Video Mission YouTube Seeder');
        $this->command->info("📺 ช่อง: {$this->channelHandle}");
        $this->command->newLine();

        // ตรวจสอบว่ามี YouTube API Key หรือไม่
        $apiKey = env('YOUTUBE_API_KEY');

        if ($apiKey) {
            // ใช้ Command นำเข้าจาก YouTube API
            $this->importFromYouTubeApi($apiKey);
        } else {
            // ใช้ hard-coded videos
            $this->command->warn('⚠️ ไม่พบ YOUTUBE_API_KEY - ใช้วิดีโอตัวอย่างแทน');
            $this->command->info('💡 เพิ่ม YOUTUBE_API_KEY ใน .env เพื่อนำเข้าวิดีโอทั้งหมดจากช่อง');
            $this->command->newLine();
            $this->seedHardcodedVideos();
        }
    }

    /**
     * นำเข้าจาก YouTube API
     */
    protected function importFromYouTubeApi(string $apiKey): void
    {
        $this->command->info('🔄 กำลังนำเข้าจาก YouTube API...');

        try {
            Artisan::call('video-missions:import-youtube', [
                'channel_id' => $this->channelId,
                '--api-key' => $apiKey,
                '--reward' => $this->defaultRewards['money'],
                '--coins' => $this->defaultRewards['coins'],
                '--exp' => $this->defaultRewards['exp'],
                '--limit' => 100,
                '--active' => true,
            ]);

            $this->command->info(Artisan::output());
        } catch (\Exception $e) {
            $this->command->error("❌ Error: {$e->getMessage()}");
            Log::error('VideoMissionYouTubeSeeder Error', [
                'error' => $e->getMessage(),
            ]);

            // Fallback to hardcoded videos
            $this->command->info('🔄 ใช้วิดีโอตัวอย่างแทน...');
            $this->seedHardcodedVideos();
        }
    }

    /**
     * สร้างภารกิจจาก hard-coded videos
     *
     * วิดีโอเหล่านี้เป็นตัวอย่าง - ควรใช้ Command นำเข้าจาก YouTube API แทน
     */
    protected function seedHardcodedVideos(): void
    {
        $videos = $this->getHardcodedVideos();

        $created = 0;
        $skipped = 0;

        foreach ($videos as $video) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $exists = VideoMission::where('video_id', $video['video_id'])->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            VideoMission::create([
                'title' => $video['title'],
                'title_th' => $video['title'],
                'description' => $video['description'] ?? null,
                'description_th' => $video['description'] ?? null,
                'thumbnail' => "https://img.youtube.com/vi/{$video['video_id']}/maxresdefault.jpg",

                'video_url' => "https://www.youtube.com/watch?v={$video['video_id']}",
                'video_type' => 'youtube',
                'video_id' => $video['video_id'],
                'video_duration_seconds' => $video['duration'] ?? 180,

                'required_watch_seconds' => (int) (($video['duration'] ?? 180) * 0.8),
                'required_watch_percentage' => 80,

                'reward_type' => 'multi',
                'reward_money' => $this->defaultRewards['money'],
                'reward_coins' => $this->defaultRewards['coins'],
                'reward_exp' => $this->defaultRewards['exp'],

                'frequency' => 'daily',
                'cooldown_minutes' => 0,

                'anti_cheat_enabled' => true,
                'require_focus' => true,
                'detect_tab_switch' => true,
                'max_tab_switches' => 3,

                'is_active' => true,
                'is_featured' => $video['featured'] ?? false,
                'priority' => $video['priority'] ?? 0,
                'sort_order' => $video['sort_order'] ?? 0,

                'category' => 'youtube',
                'tags' => ['Metal-XProject', 'YouTube'],
            ]);

            $created++;
        }

        $this->command->info("✅ สร้างภารกิจ: {$created} รายการ");
        $this->command->info("⏭️ ข้าม (มีอยู่แล้ว): {$skipped} รายการ");
        $this->command->newLine();

        $this->command->info('💡 เพื่อนำเข้าวิดีโอทั้งหมดจากช่อง ให้รัน:');
        $this->command->info("   php artisan video-missions:import-youtube {$this->channelId} --api-key=YOUR_API_KEY");
    }

    /**
     * รายการวิดีโอตัวอย่าง
     *
     * ⚠️ หมายเหตุ: ควรใช้ YouTube API เพื่อดึงวิดีโอจริงๆ
     * รายการนี้เป็นเพียงตัวอย่างสำหรับทดสอบระบบ
     *
     * วิธีรับ API Key:
     * 1. ไปที่ https://console.cloud.google.com/
     * 2. สร้าง Project ใหม่
     * 3. เปิดใช้งาน YouTube Data API v3
     * 4. สร้าง API Key
     * 5. เพิ่มใน .env: YOUTUBE_API_KEY=xxx
     * 6. รัน: php artisan video-missions:import-youtube UCEfNuxMsSvg8OTUzo53yIYg
     */
    protected function getHardcodedVideos(): array
    {
        // ⚠️ วิดีโอตัวอย่าง - ควรใช้ YouTube API เพื่อนำเข้าวิดีโอจริง
        // คุณสามารถเพิ่ม video_id จาก URL ของ YouTube ได้ที่นี่
        // ตัวอย่าง: https://www.youtube.com/watch?v=VIDEO_ID

        return [
            // ===== เพิ่มวิดีโอจากช่อง @Metal-XProject ที่นี่ =====
            // ตัวอย่างรูปแบบ:
            // [
            //     'video_id' => 'xxxxxxxxxxx', // 11 ตัวอักษร
            //     'title' => 'ชื่อวิดีโอ',
            //     'description' => 'คำอธิบาย',
            //     'duration' => 180, // วินาที
            //     'featured' => true,
            //     'priority' => 10,
            // ],

            // ⚠️ Placeholder - ให้ผู้ดูแลระบบเพิ่ม video IDs จริง
            // หรือใช้ Command: php artisan video-missions:import-youtube UCEfNuxMsSvg8OTUzo53yIYg --api-key=xxx
        ];
    }
}
