<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'slug' => '3d-navigation',
                'name' => '3D Navigation',
                'title' => '3D Navigation',
                'title_en' => '3D Navigation',
                'description' => 'สำรวจโลก 3D ที่สวยงาม'."\n".'ด้วยระบบนำทางที่ทันสมัย',
                'description_en' => 'Explore beautiful 3D world'."\n".'With modern navigation system',
                'icon' => '🧭',
                'url' => route('demo.3d-navigation'),
                'primary_color' => '#00ffff',
                'secondary_color' => '#0080ff',
                'glow_color' => 'rgba(0, 255, 255, 0.8)',
                'order' => 0,
                'is_active' => true,
                'card_style' => 'default',
            ],
            [
                'slug' => 'space-shooter',
                'name' => 'Space Shooter',
                'title' => 'Space Shooter',
                'title_en' => 'Space Shooter',
                'description' => 'ยิงยานอวกาศศัตรู'."\n".'ในสงครามอวกาศที่ตื่นเต้น',
                'description_en' => 'Shoot enemy spaceships'."\n".'In exciting space war',
                'icon' => '🚀',
                'url' => route('demo.space-shooter'),
                'primary_color' => '#ff00ff',
                'secondary_color' => '#ff0080',
                'glow_color' => 'rgba(255, 0, 255, 0.8)',
                'order' => 1,
                'is_active' => true,
                'card_style' => 'gradient',
            ],
            [
                'slug' => 'loading-demo',
                'name' => 'Loading Demo',
                'title' => 'Loading Demo',
                'title_en' => 'Loading Demo',
                'description' => 'ชมเอฟเฟกต์การโหลด'."\n".'ที่สวยงามและทันสมัย',
                'description_en' => 'Watch loading effects'."\n".'Beautiful and modern',
                'icon' => '⚡',
                'url' => route('demo.loading'),
                'primary_color' => '#ffff00',
                'secondary_color' => '#ff8800',
                'glow_color' => 'rgba(255, 255, 0, 0.8)',
                'order' => 2,
                'is_active' => true,
                'card_style' => 'neon',
            ],
            [
                'slug' => 'tetris',
                'name' => 'Tetris',
                'title' => 'Tetris',
                'title_en' => 'Tetris',
                'description' => 'เกมเตตริสคลาสสิค'."\n".'พร้อมเสียงและบันทึกคะแนน!',
                'description_en' => 'Classic Tetris game'."\n".'With sound and leaderboard!',
                'icon' => '🎮',
                'url' => '/games/tetris',
                'thumbnail' => null,
                'category' => 'puzzle',
                'primary_color' => '#a855f7',
                'secondary_color' => '#6366f1',
                'glow_color' => 'rgba(168, 85, 247, 0.8)',
                'order' => 3,
                'is_active' => true,
                'requires_auth' => false,
                'min_level_required' => 1,
                'card_style' => 'gradient',
                'settings' => json_encode([
                    'has_sound' => true,
                    'has_leaderboard' => true,
                    'has_hold' => true,
                    'has_combo' => true,
                ]),
            ],
        ];

        foreach ($games as $gameData) {
            Game::updateOrCreate(
                ['slug' => $gameData['slug']],
                $gameData
            );
        }

        $this->command->info('✅ Game seeder completed successfully!');
        $this->command->info('📊 Created '.count($games).' games');
    }
}
