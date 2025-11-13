<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameAchievement;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Space Shooter Game
        $spaceShooter = Game::create([
            'slug' => 'space-shooter',
            'name' => 'Space Shooter 3D Ultimate',
            'description' => 'เกมยานอวกาศแนว 3D ที่มีระบบปลดล็อคยาน อาวุธพิเศษ และ Boss Fights! ยิงศัตรู ปลดล็อคยานเจ๋งๆ และเป็นที่ 1 ใน Leaderboard!',
            'category' => 'action',
            'min_level_required' => 1,
            'is_active' => true,
            'requires_auth' => true,
            'settings' => [
                'ships' => [
                    'basic' => [
                        'name' => 'Basic Fighter',
                        'unlock_requirement' => 'ปลดล็อคตั้งแต่เริ่ม',
                        'speed' => 1.0,
                        'health' => 100,
                    ],
                    'interceptor' => [
                        'name' => 'Interceptor',
                        'unlock_requirement' => 'ถึง Wave 5',
                        'speed' => 1.5,
                        'health' => 80,
                    ],
                    'destroyer' => [
                        'name' => 'Destroyer',
                        'unlock_requirement' => 'ถึง Wave 10',
                        'speed' => 0.8,
                        'health' => 150,
                    ],
                    'titan' => [
                        'name' => 'Titan Class',
                        'unlock_requirement' => 'ถึง Wave 15',
                        'speed' => 0.6,
                        'health' => 200,
                    ],
                ],
                'weapons' => [
                    'basic' => [
                        'name' => 'Basic Laser',
                        'unlock_requirement' => 'ปลดล็อคตั้งแต่เริ่ม',
                        'damage' => 10,
                        'fire_rate' => 1.0,
                    ],
                    'laser' => [
                        'name' => 'Dual Laser',
                        'unlock_requirement' => 'ฆ่าศัตรู 50 ตัว',
                        'damage' => 15,
                        'fire_rate' => 1.2,
                    ],
                    'spread' => [
                        'name' => 'Spread Shot',
                        'unlock_requirement' => 'ฆ่าศัตรู 100 ตัว',
                        'damage' => 8,
                        'fire_rate' => 0.8,
                    ],
                    'missile' => [
                        'name' => 'Homing Missile',
                        'unlock_requirement' => 'ฆ่าศัตรู 200 ตัว',
                        'damage' => 25,
                        'fire_rate' => 0.5,
                    ],
                    'plasma' => [
                        'name' => 'Plasma Cannon',
                        'unlock_requirement' => 'ฆ่า Boss 3 ตัว',
                        'damage' => 30,
                        'fire_rate' => 0.6,
                    ],
                ],
            ],
        ]);

        // Create Achievements
        $achievements = [
            [
                'game_id' => $spaceShooter->id,
                'slug' => 'first_blood',
                'name' => 'First Blood',
                'description' => 'ทำคะแนนได้ 1,000 แต้มแรก',
                'type' => 'score',
                'requirement' => 1000,
                'reward_points' => 10,
            ],
            [
                'game_id' => $spaceShooter->id,
                'slug' => 'wave_master',
                'name' => 'Wave Master',
                'description' => 'ถึง Wave 10',
                'type' => 'wave',
                'requirement' => 10,
                'reward_points' => 50,
            ],
            [
                'game_id' => $spaceShooter->id,
                'slug' => 'killer',
                'name' => 'Elite Killer',
                'description' => 'ฆ่าศัตรู 100 ตัว',
                'type' => 'kills',
                'requirement' => 100,
                'reward_points' => 30,
            ],
            [
                'game_id' => $spaceShooter->id,
                'slug' => 'boss_slayer',
                'name' => 'Boss Slayer',
                'description' => 'ฆ่า Boss 5 ตัว',
                'type' => 'boss',
                'requirement' => 5,
                'reward_points' => 100,
            ],
        ];

        foreach ($achievements as $achievement) {
            GameAchievement::create($achievement);
        }
    }
}
