<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameAchievement;
use App\Models\GameSkin;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Snake.io Game
        $snakeIo = Game::updateOrCreate(
            ['slug' => 'snake-io'],
            [
                'name' => 'Snake.io',
                'description' => 'เกมหนอนออนไลน์สุดมันส์! กินอาหาร โตขึ้น และหลีกเลี่ยงการชน - เล่นได้ทุกคน แต่สมาชิกจะมี skins พิเศษและบันทึกสกอร์!',
                'category' => 'io',
                'min_level_required' => 1,
                'is_active' => true,
                'requires_auth' => false,
                'settings' => ['world_size' => 200, 'food_count' => 100, 'bot_count' => 10],
            ]
        );

        // Skins for Snake.io
        $skins = [
            ['slug' => 'classic', 'name' => 'Classic Green', 'price' => 0, 'color_primary' => '#00ff00', 'color_secondary' => '#00aa00'],
            ['slug' => 'fire', 'name' => 'Fire Snake', 'price' => 100, 'color_primary' => '#ff4400', 'color_secondary' => '#aa2200', 'is_premium' => true],
            ['slug' => 'ice', 'name' => 'Ice Snake', 'price' => 100, 'color_primary' => '#00aaff', 'color_secondary' => '#0077cc', 'is_premium' => true],
            ['slug' => 'gold', 'name' => 'Golden Snake', 'price' => 500, 'color_primary' => '#ffd700', 'color_secondary' => '#ffaa00', 'is_premium' => true],
            ['slug' => 'rainbow', 'name' => 'Rainbow Snake', 'price' => 1000, 'color_primary' => '#ff00ff', 'color_secondary' => '#00ffff', 'is_premium' => true, 'is_limited' => true],
        ];

        foreach ($skins as $skin) {
            GameSkin::updateOrCreate(
                ['game_id' => $snakeIo->id, 'slug' => $skin['slug']],
                array_merge(['is_active' => true], $skin)
            );
        }

        // Create Space Shooter Game (Future)
        $spaceShooter = Game::updateOrCreate(
            ['slug' => 'space-shooter'],
            [
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
            ]
        );

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
            GameAchievement::updateOrCreate(
                ['game_id' => $achievement['game_id'], 'slug' => $achievement['slug']],
                $achievement
            );
        }
    }
}
