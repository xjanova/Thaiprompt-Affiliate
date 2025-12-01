<?php

namespace Database\Seeders;

use App\Models\VideoMission;
use App\Models\VideoMissionRankLimit;
use App\Models\Rank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * VideoMissionSystemSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับระบบภารกิจดูคลิปรับรางวัล
 * รวมถึง Demo missions และ Rank limits
 */
class VideoMissionSystemSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับระบบภารกิจดูคลิป
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🎬 กำลัง seed ข้อมูลระบบภารกิจดูคลิป...');

        // Seed Rank Limits
        $this->seedRankLimits();

        // Seed Demo Missions
        $this->seedDemoMissions();

        $this->command->info('✅ Seed ระบบภารกิจดูคลิปสำเร็จ!');
    }

    /**
     * สร้าง Rank Limits สำหรับทุก Rank
     *
     * @return void
     */
    private function seedRankLimits(): void
    {
        $this->command->info('   📊 กำลังสร้าง Rank Limits...');

        $ranks = Rank::active()->get();

        if ($ranks->isEmpty()) {
            $this->command->warn('   ⚠️ ไม่พบ Rank ในระบบ - ข้าม Rank Limits');
            return;
        }

        foreach ($ranks as $rank) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            if (VideoMissionRankLimit::where('rank_id', $rank->id)->exists()) {
                continue;
            }

            // กำหนดค่าตาม Level ของ Rank
            $multiplier = 1.0 + ($rank->level * 0.1); // Level 1 = 1.1x, Level 5 = 1.5x
            $dailyLimit = 5 + ($rank->level * 2);     // Level 1 = 7, Level 5 = 15
            $weeklyLimit = $dailyLimit * 5;
            $monthlyLimit = $dailyLimit * 20;

            VideoMissionRankLimit::create([
                'rank_id' => $rank->id,
                'daily_mission_limit' => min($dailyLimit, 30),
                'weekly_mission_limit' => min($weeklyLimit, 100),
                'monthly_mission_limit' => min($monthlyLimit, 300),
                'daily_reward_limit' => 500 + ($rank->level * 200),
                'weekly_reward_limit' => 2000 + ($rank->level * 500),
                'monthly_reward_limit' => 5000 + ($rank->level * 1500),
                'reward_multiplier' => round($multiplier, 2),
                'bonus_reward_percentage' => $rank->level * 5,
                'bonus_exp_percentage' => $rank->level * 10,
                'can_access_premium_missions' => $rank->level >= 3,
                'can_access_featured_missions' => true,
                'skip_cooldown' => $rank->level >= 5,
                'cooldown_reduction_percent' => min($rank->level * 10, 50),
                'priority_in_queue' => $rank->level,
                'is_active' => true,
            ]);
        }

        $this->command->info('   ✓ สร้าง Rank Limits เรียบร้อย');
    }

    /**
     * สร้างภารกิจตัวอย่าง
     *
     * @return void
     */
    private function seedDemoMissions(): void
    {
        $this->command->info('   🎥 กำลังสร้างภารกิจตัวอย่าง...');

        // ตรวจสอบว่ามีภารกิจอยู่แล้วหรือไม่
        if (VideoMission::count() > 0) {
            $this->command->info('   ⏩ มีภารกิจอยู่แล้ว - ข้าม');
            return;
        }

        $missions = [
            [
                'title' => 'Welcome Video',
                'title_th' => 'วิดีโอต้อนรับ',
                'description' => 'Watch our welcome video to learn about the platform',
                'description_th' => 'ดูวิดีโอแนะนำแพลตฟอร์มของเรา',
                'icon' => '👋',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'video_type' => 'youtube',
                'video_id' => 'dQw4w9WgXcQ',
                'required_watch_seconds' => 60,
                'reward_type' => 'coins',
                'reward_coins' => 50,
                'reward_exp' => 10,
                'frequency' => 'once',
                'is_featured' => true,
                'priority' => 100,
                'category' => 'แนะนำ',
            ],
            [
                'title' => 'Daily Reward Video',
                'title_th' => 'รางวัลประจำวัน',
                'description' => 'Watch daily to earn rewards',
                'description_th' => 'ดูทุกวันเพื่อรับรางวัล',
                'icon' => '🎁',
                'video_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0',
                'video_type' => 'youtube',
                'video_id' => '9bZkp7q19f0',
                'required_watch_seconds' => 30,
                'reward_type' => 'mixed',
                'reward_coins' => 20,
                'reward_money' => 1.00,
                'reward_exp' => 5,
                'frequency' => 'daily',
                'is_featured' => true,
                'priority' => 90,
                'category' => 'รายวัน',
            ],
            [
                'title' => 'Tutorial: How to Use',
                'title_th' => 'สอนใช้งาน: เริ่มต้นใช้งาน',
                'description' => 'Learn the basics of our platform',
                'description_th' => 'เรียนรู้พื้นฐานการใช้งานแพลตฟอร์ม',
                'icon' => '📚',
                'video_url' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk',
                'video_type' => 'youtube',
                'video_id' => 'kJQP7kiw5Fk',
                'required_watch_seconds' => 120,
                'reward_type' => 'coins',
                'reward_coins' => 100,
                'reward_exp' => 25,
                'frequency' => 'once',
                'is_featured' => false,
                'priority' => 80,
                'category' => 'สอนใช้งาน',
            ],
            [
                'title' => 'Premium Content',
                'title_th' => 'เนื้อหาพิเศษ',
                'description' => 'Exclusive content for premium members',
                'description_th' => 'เนื้อหาพิเศษสำหรับสมาชิก Premium',
                'icon' => '👑',
                'video_url' => 'https://www.youtube.com/watch?v=RgKAFK5djSk',
                'video_type' => 'youtube',
                'video_id' => 'RgKAFK5djSk',
                'required_watch_seconds' => 180,
                'reward_type' => 'mixed',
                'reward_coins' => 200,
                'reward_money' => 10.00,
                'reward_exp' => 50,
                'frequency' => 'weekly',
                'is_featured' => true,
                'is_premium' => true,
                'priority' => 95,
                'category' => 'พรีเมียม',
            ],
            [
                'title' => 'Quick Reward',
                'title_th' => 'รางวัลด่วน',
                'description' => 'Quick 15 second video for instant reward',
                'description_th' => 'วิดีโอสั้น 15 วินาที รับรางวัลทันที',
                'icon' => '⚡',
                'video_url' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0',
                'video_type' => 'youtube',
                'video_id' => 'OPf0YbXqDm0',
                'required_watch_seconds' => 15,
                'reward_type' => 'coins',
                'reward_coins' => 5,
                'reward_exp' => 2,
                'frequency' => 'unlimited',
                'cooldown_minutes' => 60,
                'is_featured' => false,
                'priority' => 50,
                'category' => 'รางวัลด่วน',
            ],
            [
                'title' => 'Weekly Challenge',
                'title_th' => 'ชาเลนจ์ประจำสัปดาห์',
                'description' => 'Complete the weekly challenge for bonus rewards',
                'description_th' => 'ทำชาเลนจ์ประจำสัปดาห์รับโบนัสพิเศษ',
                'icon' => '🏆',
                'video_url' => 'https://www.youtube.com/watch?v=fRh_vgS2dFE',
                'video_type' => 'youtube',
                'video_id' => 'fRh_vgS2dFE',
                'required_watch_seconds' => 300,
                'reward_type' => 'mixed',
                'reward_coins' => 500,
                'reward_money' => 25.00,
                'reward_points' => 100,
                'reward_exp' => 100,
                'frequency' => 'weekly',
                'is_featured' => true,
                'priority' => 85,
                'category' => 'ชาเลนจ์',
            ],
        ];

        foreach ($missions as $missionData) {
            VideoMission::create(array_merge($missionData, [
                'video_duration_seconds' => $missionData['required_watch_seconds'] + 60,
                'required_watch_percentage' => 80,
                'anti_cheat_enabled' => true,
                'require_focus' => true,
                'detect_tab_switch' => true,
                'max_tab_switches' => 3,
                'is_active' => true,
                'created_by' => 1,
            ]));
        }

        $this->command->info('   ✓ สร้างภารกิจตัวอย่าง ' . count($missions) . ' รายการเรียบร้อย');
    }
}
