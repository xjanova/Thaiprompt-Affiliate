<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class VideoRewardMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎬 Adding Video Reward System menu items...');

        // Check if the parent menu already exists
        $parentMenu = MenuItem::where('menu_location', 'admin')
            ->where('title', 'ระบบวิดีโอรางวัล')
            ->first();

        // Create parent menu if doesn't exist
        if (!$parentMenu) {
            $parentMenu = MenuItem::create([
                'menu_location' => 'admin',
                'title' => 'ระบบวิดีโอรางวัล',
                'url' => null,
                'route' => 'admin.video-rewards.dashboard',
                'target' => '_self',
                'icon' => '🎬', // Video camera icon
                'order' => 100, // Place after other menus
                'parent_id' => null,
                'is_active' => true,
                'conditions' => [
                    'logged_in' => true,
                    'roles' => ['admin', 'super_admin'],
                ],
            ]);

            $this->command->info('✅ Created parent menu: ระบบวิดีโอรางวัล');
        }

        // Sub-menu items
        $subMenus = [
            [
                'title' => '📊 Dashboard',
                'route' => 'admin.video-rewards.dashboard',
                'icon' => '📊',
                'order' => 1,
            ],
            [
                'title' => '💰 คำขอแลกเหรียญ',
                'route' => 'admin.video-rewards.exchange.requests',
                'icon' => '💰',
                'order' => 2,
            ],
            [
                'title' => '📺 จัดการช่อง',
                'route' => 'admin.video-rewards.channels.index',
                'icon' => '📺',
                'order' => 3,
            ],
            [
                'title' => '🎥 จัดการวิดีโอ',
                'route' => 'admin.video-rewards.videos.index',
                'icon' => '🎥',
                'order' => 4,
            ],
            [
                'title' => '🎯 จัดการภาระกิจ',
                'route' => 'admin.video-rewards.quests.index',
                'icon' => '🎯',
                'order' => 5,
            ],
            [
                'title' => '💎 อัตราแลกเหรียญ',
                'route' => 'admin.video-rewards.exchange-rates.index',
                'icon' => '💎',
                'order' => 6,
            ],
        ];

        foreach ($subMenus as $menu) {
            $exists = MenuItem::where('menu_location', 'admin')
                ->where('parent_id', $parentMenu->id)
                ->where('title', $menu['title'])
                ->exists();

            if (!$exists) {
                MenuItem::create([
                    'menu_location' => 'admin',
                    'title' => $menu['title'],
                    'url' => null,
                    'route' => $menu['route'],
                    'target' => '_self',
                    'icon' => $menu['icon'],
                    'order' => $menu['order'],
                    'parent_id' => $parentMenu->id,
                    'is_active' => true,
                    'conditions' => [
                        'logged_in' => true,
                        'roles' => ['admin', 'super_admin'],
                    ],
                ]);

                $this->command->info("  ✅ {$menu['title']}");
            }
        }

        // Optional: Add to header menu for users
        $this->addUserMenu();

        $this->command->info('');
        $this->command->info('✨ Video Reward System menus added successfully!');
    }

    /**
     * Add user-facing menu items
     */
    protected function addUserMenu()
    {
        $this->command->info('');
        $this->command->info('🎮 Adding user menu items...');

        $userMenu = MenuItem::where('menu_location', 'header')
            ->where('title', 'วิดีโอรางวัล')
            ->first();

        if (!$userMenu) {
            MenuItem::create([
                'menu_location' => 'header',
                'title' => 'วิดีโอรางวัล',
                'url' => null,
                'route' => null, // Will need to create user-facing page
                'target' => '_self',
                'icon' => '🎬',
                'order' => 6, // After other menu items
                'parent_id' => null,
                'is_active' => true,
                'conditions' => [
                    'logged_in' => true,
                ],
            ]);

            $this->command->info('✅ Created user menu: วิดีโอรางวัล');
        }
    }
}
