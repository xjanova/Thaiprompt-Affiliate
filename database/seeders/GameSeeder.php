<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Game;

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
                'title' => '3D Navigation',
                'title_en' => '3D Navigation',
                'description' => 'สำรวจโลก 3D ที่สวยงาม' . "\n" . 'ด้วยระบบนำทางที่ทันสมัย',
                'description_en' => 'Explore beautiful 3D world' . "\n" . 'With modern navigation system',
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
                'title' => 'Space Shooter',
                'title_en' => 'Space Shooter',
                'description' => 'ยิงยานอวกาศศัตรู' . "\n" . 'ในสงครามอวกาศที่ตื่นเต้น',
                'description_en' => 'Shoot enemy spaceships' . "\n" . 'In exciting space war',
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
                'title' => 'Loading Demo',
                'title_en' => 'Loading Demo',
                'description' => 'ชมเอฟเฟกต์การโหลด' . "\n" . 'ที่สวยงามและทันสมัย',
                'description_en' => 'Watch loading effects' . "\n" . 'Beautiful and modern',
                'icon' => '⚡',
                'url' => route('demo.loading'),
                'primary_color' => '#ffff00',
                'secondary_color' => '#ff8800',
                'glow_color' => 'rgba(255, 255, 0, 0.8)',
                'order' => 2,
                'is_active' => true,
                'card_style' => 'neon',
            ],
        ];

        foreach ($games as $gameData) {
            Game::create($gameData);
        }

        $this->command->info('✅ Game seeder completed successfully!');
        $this->command->info('📊 Created ' . count($games) . ' games');
    }
}
