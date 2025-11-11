<?php

namespace Database\Seeders;

use App\Models\VideoChannel;
use App\Models\VideoContent;
use App\Models\VideoLevel;
use App\Models\VideoQuest;
use App\Models\CoinExchangeRate;
use Illuminate\Database\Seeder;

class VideoRewardSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedVideoLevels();
        $this->seedVideoChannels();
        $this->seedVideoContents();
        $this->seedVideoQuests();
        $this->seedCoinExchangeRates();

        $this->command->info('✅ Video Reward System seeded successfully!');
    }

    /**
     * Seed video levels
     */
    protected function seedVideoLevels()
    {
        $levels = [
            [
                'level' => 1,
                'name' => 'มือใหม่',
                'icon' => '🌱',
                'required_exp' => 0,
                'max_daily_quests' => 3,
                'max_weekly_quests' => 10,
                'reward_multiplier' => 1.00,
                'coin_bonus' => 0,
                'description' => 'เริ่มต้นการเดินทาง',
            ],
            [
                'level' => 2,
                'name' => 'ผู้สนใจ',
                'icon' => '🌿',
                'required_exp' => 100,
                'max_daily_quests' => 4,
                'max_weekly_quests' => 15,
                'reward_multiplier' => 1.10,
                'coin_bonus' => 50,
                'description' => 'เริ่มมีความสนใจ',
            ],
            [
                'level' => 3,
                'name' => 'ผู้ติดตาม',
                'icon' => '🌾',
                'required_exp' => 300,
                'max_daily_quests' => 5,
                'max_weekly_quests' => 20,
                'reward_multiplier' => 1.25,
                'coin_bonus' => 100,
                'description' => 'ติดตามอย่างสม่ำเสมอ',
            ],
            [
                'level' => 4,
                'name' => 'แฟนตัวจริง',
                'icon' => '🌻',
                'required_exp' => 600,
                'max_daily_quests' => 6,
                'max_weekly_quests' => 25,
                'reward_multiplier' => 1.50,
                'coin_bonus' => 200,
                'description' => 'แฟนคลับตัวจริง',
            ],
            [
                'level' => 5,
                'name' => 'ผู้เชี่ยวชาญ',
                'icon' => '🌲',
                'required_exp' => 1200,
                'max_daily_quests' => 7,
                'max_weekly_quests' => 30,
                'reward_multiplier' => 1.75,
                'coin_bonus' => 350,
                'description' => 'เชี่ยวชาญในการดูคลิป',
            ],
            [
                'level' => 6,
                'name' => 'ปรมาจารย์',
                'icon' => '🏆',
                'required_exp' => 2500,
                'max_daily_quests' => 8,
                'max_weekly_quests' => 35,
                'reward_multiplier' => 2.00,
                'coin_bonus' => 500,
                'description' => 'ระดับปรมาจารย์',
            ],
            [
                'level' => 7,
                'name' => 'ตำนาน',
                'icon' => '👑',
                'required_exp' => 5000,
                'max_daily_quests' => 10,
                'max_weekly_quests' => 50,
                'reward_multiplier' => 2.50,
                'coin_bonus' => 1000,
                'description' => 'เป็นตำนานแห่งระบบ',
            ],
        ];

        foreach ($levels as $level) {
            VideoLevel::create($level);
        }

        $this->command->info('📊 Video levels seeded');
    }

    /**
     * Seed video channels
     */
    protected function seedVideoChannels()
    {
        $channels = [
            [
                'name' => 'ช่องการศึกษา',
                'description' => 'คลิปความรู้และการเรียนรู้',
                'thumbnail' => null,
                'channel_url' => 'https://www.youtube.com/c/education',
                'is_active' => true,
                'reward_enabled' => true,
                'base_coins_per_video' => 10,
                'priority' => 1,
            ],
            [
                'name' => 'ช่องบันเทิง',
                'description' => 'คลิปสนุกๆ และความบันเทิง',
                'thumbnail' => null,
                'channel_url' => 'https://www.youtube.com/c/entertainment',
                'is_active' => true,
                'reward_enabled' => true,
                'base_coins_per_video' => 8,
                'priority' => 2,
            ],
            [
                'name' => 'ช่องเทคโนโลยี',
                'description' => 'เทคโนโลยีและนวัตกรรม',
                'thumbnail' => null,
                'channel_url' => 'https://www.youtube.com/c/technology',
                'is_active' => true,
                'reward_enabled' => true,
                'base_coins_per_video' => 12,
                'priority' => 3,
            ],
            [
                'name' => 'ช่องการเงิน',
                'description' => 'เรียนรู้การบริหารเงินและการลงทุน',
                'thumbnail' => null,
                'channel_url' => 'https://www.youtube.com/c/finance',
                'is_active' => true,
                'reward_enabled' => true,
                'base_coins_per_video' => 15,
                'priority' => 4,
            ],
        ];

        foreach ($channels as $channel) {
            VideoChannel::create($channel);
        }

        $this->command->info('📺 Video channels seeded');
    }

    /**
     * Seed video contents
     */
    protected function seedVideoContents()
    {
        $videos = [
            // ช่องการศึกษา
            [
                'channel_id' => 1,
                'title' => 'พื้นฐานการเขียนโปรแกรม PHP',
                'description' => 'เรียนรู้พื้นฐาน PHP สำหรับมือใหม่',
                'video_url' => 'https://www.youtube.com/watch?v=example1',
                'video_type' => 'youtube',
                'video_id' => 'example1',
                'duration_seconds' => 600, // 10 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 10,
                'reward_exp' => 20,
                'is_active' => true,
                'reward_enabled' => true,
            ],
            [
                'channel_id' => 1,
                'title' => 'Laravel สำหรับผู้เริ่มต้น',
                'description' => 'เริ่มต้นเรียนรู้ Laravel Framework',
                'video_url' => 'https://www.youtube.com/watch?v=example2',
                'video_type' => 'youtube',
                'video_id' => 'example2',
                'duration_seconds' => 900, // 15 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 15,
                'reward_exp' => 30,
                'is_active' => true,
                'reward_enabled' => true,
            ],
            [
                'channel_id' => 1,
                'title' => 'Database Design 101',
                'description' => 'ออกแบบฐานข้อมูลอย่างมืออาชีพ',
                'video_url' => 'https://www.youtube.com/watch?v=example3',
                'video_type' => 'youtube',
                'video_id' => 'example3',
                'duration_seconds' => 720, // 12 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 12,
                'reward_exp' => 25,
                'is_active' => true,
                'reward_enabled' => true,
            ],

            // ช่องบันเทิง
            [
                'channel_id' => 2,
                'title' => 'Top 10 เรื่องสนุกๆ',
                'description' => 'รวมเรื่องสนุกๆ ที่คุณไม่ควรพลาด',
                'video_url' => 'https://www.youtube.com/watch?v=example4',
                'video_type' => 'youtube',
                'video_id' => 'example4',
                'duration_seconds' => 480, // 8 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 8,
                'reward_exp' => 15,
                'is_active' => true,
                'reward_enabled' => true,
            ],
            [
                'channel_id' => 2,
                'title' => 'เบื้องหลังการทำ VFX',
                'description' => 'เทคนิคการทำ Visual Effects',
                'video_url' => 'https://www.youtube.com/watch?v=example5',
                'video_type' => 'youtube',
                'video_id' => 'example5',
                'duration_seconds' => 540, // 9 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 9,
                'reward_exp' => 18,
                'is_active' => true,
                'reward_enabled' => true,
            ],

            // ช่องเทคโนโลยี
            [
                'channel_id' => 3,
                'title' => 'AI และ Machine Learning',
                'description' => 'แนะนำ AI และการเรียนรู้ของเครื่อง',
                'video_url' => 'https://www.youtube.com/watch?v=example6',
                'video_type' => 'youtube',
                'video_id' => 'example6',
                'duration_seconds' => 1200, // 20 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 20,
                'reward_exp' => 40,
                'is_active' => true,
                'reward_enabled' => true,
            ],
            [
                'channel_id' => 3,
                'title' => 'Blockchain Technology',
                'description' => 'เข้าใจเทคโนโลยี Blockchain',
                'video_url' => 'https://www.youtube.com/watch?v=example7',
                'video_type' => 'youtube',
                'video_id' => 'example7',
                'duration_seconds' => 960, // 16 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 16,
                'reward_exp' => 35,
                'is_active' => true,
                'reward_enabled' => true,
            ],

            // ช่องการเงิน
            [
                'channel_id' => 4,
                'title' => 'การออมเงินอย่างฉลาด',
                'description' => 'เทคนิคการออมเงินที่ใช้ได้จริง',
                'video_url' => 'https://www.youtube.com/watch?v=example8',
                'video_type' => 'youtube',
                'video_id' => 'example8',
                'duration_seconds' => 840, // 14 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 14,
                'reward_exp' => 28,
                'is_active' => true,
                'reward_enabled' => true,
            ],
            [
                'channel_id' => 4,
                'title' => 'การลงทุนหุ้นสำหรับมือใหม่',
                'description' => 'เริ่มต้นลงทุนหุ้นอย่างถูกต้อง',
                'video_url' => 'https://www.youtube.com/watch?v=example9',
                'video_type' => 'youtube',
                'video_id' => 'example9',
                'duration_seconds' => 1080, // 18 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 18,
                'reward_exp' => 36,
                'is_active' => true,
                'reward_enabled' => true,
            ],
            [
                'channel_id' => 4,
                'title' => 'Passive Income Ideas',
                'description' => 'ไอเดียสร้างรายได้ passive',
                'video_url' => 'https://www.youtube.com/watch?v=example10',
                'video_type' => 'youtube',
                'video_id' => 'example10',
                'duration_seconds' => 1320, // 22 นาที
                'required_watch_percentage' => 80,
                'reward_coins' => 22,
                'reward_exp' => 45,
                'is_active' => true,
                'reward_enabled' => true,
            ],
        ];

        foreach ($videos as $video) {
            VideoContent::create($video);
        }

        $this->command->info('🎬 Video contents seeded');
    }

    /**
     * Seed video quests
     */
    protected function seedVideoQuests()
    {
        $quests = [
            // Daily Quests
            [
                'name' => 'ดูคลิป 3 คลิปวันนี้',
                'description' => 'ดูคลิปให้ครบ 3 คลิปในวันนี้',
                'icon' => '🎯',
                'type' => 'watch_videos',
                'frequency' => 'daily',
                'target_value' => 3,
                'min_watch_percentage' => 80,
                'reward_coins' => 30,
                'reward_exp' => 50,
                'reward_money' => 0,
                'bonus_coins_per_extra' => 5,
                'min_level' => 1,
                'is_active' => true,
                'priority' => 1,
            ],
            [
                'name' => 'ดูคลิปรวม 30 นาที',
                'description' => 'ดูคลิปให้ได้รวม 30 นาทีในวันนี้',
                'icon' => '⏱️',
                'type' => 'watch_duration',
                'frequency' => 'daily',
                'target_value' => 1800, // 30 minutes in seconds
                'min_watch_percentage' => 80,
                'reward_coins' => 40,
                'reward_exp' => 60,
                'reward_money' => 0,
                'bonus_coins_per_extra' => 0.05, // per second
                'min_level' => 1,
                'is_active' => true,
                'priority' => 2,
            ],

            // Weekly Quests
            [
                'name' => 'ดูคลิป 20 คลิปในสัปดาห์นี้',
                'description' => 'ดูคลิปให้ครบ 20 คลิปในสัปดาห์นี้',
                'icon' => '🏅',
                'type' => 'watch_videos',
                'frequency' => 'weekly',
                'target_value' => 20,
                'min_watch_percentage' => 80,
                'reward_coins' => 200,
                'reward_exp' => 300,
                'reward_money' => 10,
                'bonus_coins_per_extra' => 10,
                'min_level' => 1,
                'is_active' => true,
                'priority' => 3,
            ],
            [
                'name' => 'ดูคลิปจากช่องการศึกษา 5 คลิป',
                'description' => 'ดูคลิปจากช่องการศึกษาให้ครบ 5 คลิป',
                'icon' => '📚',
                'type' => 'watch_channel',
                'frequency' => 'weekly',
                'target_value' => 5,
                'channel_id' => 1,
                'min_watch_percentage' => 80,
                'reward_coins' => 100,
                'reward_exp' => 150,
                'reward_money' => 5,
                'bonus_coins_per_extra' => 15,
                'min_level' => 2,
                'is_active' => true,
                'priority' => 4,
            ],

            // Referral Quests
            [
                'name' => 'ชวนเพื่อน 3 คน',
                'description' => 'ชวนเพื่อนมาสมัครใหม่ 3 คน',
                'icon' => '👥',
                'type' => 'refer_users',
                'frequency' => 'once',
                'target_value' => 3,
                'min_watch_percentage' => 0,
                'reward_coins' => 300,
                'reward_exp' => 500,
                'reward_money' => 50,
                'bonus_coins_per_extra' => 100,
                'min_level' => 3,
                'is_active' => true,
                'priority' => 5,
            ],
            [
                'name' => 'เพื่อนที่ชวนดูคลิป 10 คลิป',
                'description' => 'เพื่อนที่คุณชวนดูคลิปครบ 10 คลิป',
                'icon' => '🎁',
                'type' => 'referred_watch',
                'frequency' => 'repeatable',
                'target_value' => 10,
                'min_watch_percentage' => 80,
                'reward_coins' => 150,
                'reward_exp' => 200,
                'reward_money' => 20,
                'bonus_coins_per_extra' => 10,
                'min_level' => 3,
                'is_active' => true,
                'priority' => 6,
            ],

            // Streak Quest
            [
                'name' => 'ดูคลิปติดต่อกัน 7 วัน',
                'description' => 'ดูคลิปอย่างน้อย 1 คลิปทุกวัน เป็นเวลา 7 วัน',
                'icon' => '🔥',
                'type' => 'daily_streak',
                'frequency' => 'once',
                'target_value' => 7,
                'min_watch_percentage' => 80,
                'reward_coins' => 500,
                'reward_exp' => 700,
                'reward_money' => 100,
                'bonus_coins_per_extra' => 50,
                'min_level' => 2,
                'is_active' => true,
                'priority' => 7,
            ],

            // Level Quest
            [
                'name' => 'ขึ้นถึงระดับ 5',
                'description' => 'พัฒนาตัวเองจนถึงระดับ 5',
                'icon' => '⭐',
                'type' => 'reach_level',
                'frequency' => 'once',
                'target_value' => 5,
                'min_watch_percentage' => 0,
                'reward_coins' => 1000,
                'reward_exp' => 0,
                'reward_money' => 200,
                'bonus_coins_per_extra' => 0,
                'min_level' => 1,
                'is_active' => true,
                'priority' => 8,
            ],
        ];

        foreach ($quests as $quest) {
            VideoQuest::create($quest);
        }

        $this->command->info('🎮 Video quests seeded');
    }

    /**
     * Seed coin exchange rates
     */
    protected function seedCoinExchangeRates()
    {
        $rates = [
            [
                'coins_amount' => 100,
                'money_amount' => 10,
                'rate' => 0.10, // 100 coins = 10 THB (1 coin = 0.10 THB)
                'min_level' => 1,
                'min_coins' => 100,
                'max_coins_per_day' => 1000,
                'max_coins_per_month' => 10000,
                'is_active' => true,
                'effective_from' => now(),
                'effective_until' => null,
            ],
            [
                'coins_amount' => 500,
                'money_amount' => 55,
                'rate' => 0.11, // Better rate for bulk exchange
                'min_level' => 3,
                'min_coins' => 500,
                'max_coins_per_day' => 2000,
                'max_coins_per_month' => 20000,
                'is_active' => true,
                'effective_from' => now(),
                'effective_until' => null,
            ],
            [
                'coins_amount' => 1000,
                'money_amount' => 120,
                'rate' => 0.12, // Best rate for premium members
                'min_level' => 5,
                'min_coins' => 1000,
                'max_coins_per_day' => 5000,
                'max_coins_per_month' => 50000,
                'is_active' => true,
                'effective_from' => now(),
                'effective_until' => null,
            ],
        ];

        foreach ($rates as $rate) {
            CoinExchangeRate::create($rate);
        }

        $this->command->info('💰 Coin exchange rates seeded');
    }
}
