<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing menu items for header location
        MenuItem::where('menu_location', 'header')->delete();

        // Create header menu items
        $menuItems = [
            [
                'menu_location' => 'header',
                'title' => 'หน้าแรก',
                'route' => 'home',
                'target' => '_self',
                'icon' => null,
                'order' => 1,
                'is_active' => true,
                'conditions' => null,
            ],
            [
                'menu_location' => 'header',
                'title' => 'ตลาดบอท',
                'route' => 'marketplace.index',
                'target' => '_self',
                'icon' => '🤖',
                'order' => 2,
                'is_active' => true,
                'conditions' => null,
            ],
            [
                'menu_location' => 'header',
                'title' => 'การเช่าของฉัน',
                'route' => 'my-rentals.index',
                'target' => '_self',
                'icon' => '💼',
                'order' => 3,
                'is_active' => true,
                'conditions' => [
                    'logged_in' => true,
                ],
            ],
            [
                'menu_location' => 'header',
                'title' => 'เกี่ยวกับเรา',
                'route' => 'about',
                'target' => '_self',
                'icon' => null,
                'order' => 4,
                'is_active' => true,
                'conditions' => null,
            ],
            [
                'menu_location' => 'header',
                'title' => 'ติดต่อเรา',
                'route' => 'contact',
                'target' => '_self',
                'icon' => null,
                'order' => 5,
                'is_active' => true,
                'conditions' => null,
            ],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }

        $this->command->info('Menu items seeded successfully!');
    }
}
