<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Preserves existing menu items, adds only missing ones.
     * DANGEROUS: Old version deleted all header menu items!
     */
    public function run(): void
    {
        // Check if menu items already exist
        $existingMenuCount = MenuItem::where('menu_location', 'header')->count();

        if ($existingMenuCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all menu items
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all header menu items...');

        $menuItems = $this->getAllMenuItems();

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }

        $this->command->info('✅ Menu items seeded successfully: ' . count($menuItems) . ' items');
    }

    /**
     * Update mode - add only missing menu items
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing menu items detected!');
        $this->command->info('   Running in UPDATE mode (adding missing items only)...');

        $menuItems = $this->getAllMenuItems();
        $added = 0;
        $skipped = 0;

        foreach ($menuItems as $item) {
            $exists = MenuItem::where('menu_location', $item['menu_location'])
                ->where('route', $item['route'])
                ->exists();

            if (!$exists) {
                MenuItem::create($item);
                $this->command->info("   ➕ Added: {$item['title']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new menu items.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing items (preserved).");
        }
    }

    /**
     * Get all default menu items
     */
    private function getAllMenuItems(): array
    {
        return [
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
    }
}
