<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class AiGenMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure required columns exist
        $this->ensureColumnsExist();

        $this->command->info('🎨 Adding AI Gen System menu items...');

        // Check if the parent menu already exists
        $aiGenMenu = MenuItem::where('menu_location', 'admin')
            ->where('title', 'AI Gen System')
            ->first();

        // Create parent menu if doesn't exist
        if (!$aiGenMenu) {
            $aiGenMenu = MenuItem::create([
                'menu_location' => 'admin',
                'parent_id' => null,
                'title' => 'AI Gen System',
                'title_en' => 'AI Gen System',
                'title_th' => 'ระบบสร้างภาพ/วีดีโอ AI',
                'url' => null,
                'route' => null,
                'icon' => 'fas fa-magic',
                'order' => 60,
                'position' => 60,
                'permission' => 'admin',
                'is_active' => true,
                'open_in_new_tab' => false,
                'target' => '_self',
                'conditions' => [
                    'logged_in' => true,
                    'roles' => ['admin', 'super_admin'],
                ],
            ]);

            $this->command->info('✅ Created parent menu: AI Gen System');
        } else {
            $this->command->info('ℹ️  Parent menu already exists: AI Gen System');
        }

        // Sub-menu items
        $subMenus = [
            [
                'title' => 'Dashboard',
                'title_en' => 'Dashboard',
                'title_th' => 'แดชบอร์ด',
                'route' => 'admin.ai-gen.dashboard',
                'url' => '/admin/ai-gen/dashboard',
                'icon' => 'fas fa-chart-line',
                'order' => 1,
            ],
            [
                'title' => 'AI Providers',
                'title_en' => 'AI Providers',
                'title_th' => 'จัดการ AI Providers',
                'route' => 'admin.ai-gen.providers',
                'url' => '/admin/ai-gen/providers',
                'icon' => 'fas fa-server',
                'order' => 2,
            ],
            [
                'title' => 'Packages',
                'title_en' => 'Packages',
                'title_th' => 'จัดการแพคเกจ',
                'route' => 'admin.ai-gen.packages',
                'url' => '/admin/ai-gen/packages',
                'icon' => 'fas fa-box',
                'order' => 3,
            ],
            [
                'title' => 'Free Quotas',
                'title_en' => 'Free Quotas',
                'title_th' => 'จัดการโควต้าฟรี',
                'route' => 'admin.ai-gen.quotas',
                'url' => '/admin/ai-gen/quotas',
                'icon' => 'fas fa-gift',
                'order' => 4,
            ],
            [
                'title' => 'Subscriptions',
                'title_en' => 'Subscriptions',
                'title_th' => 'จัดการสมาชิก',
                'route' => 'admin.ai-gen.subscriptions',
                'url' => '/admin/ai-gen/subscriptions',
                'icon' => 'fas fa-users',
                'order' => 5,
            ],
            [
                'title' => 'Usage Logs',
                'title_en' => 'Usage Logs',
                'title_th' => 'บันทึกการใช้งาน',
                'route' => 'admin.ai-gen.usage-logs',
                'url' => '/admin/ai-gen/usage-logs',
                'icon' => 'fas fa-history',
                'order' => 6,
            ],
            [
                'title' => 'All Generations',
                'title_en' => 'All Generations',
                'title_th' => 'ภาพ/วีดีโอทั้งหมด',
                'route' => 'admin.ai-gen.generations',
                'url' => '/admin/ai-gen/generations',
                'icon' => 'fas fa-images',
                'order' => 7,
            ],
            [
                'title' => 'Settings',
                'title_en' => 'Settings',
                'title_th' => 'ตั้งค่าระบบ',
                'route' => 'admin.ai-gen.settings',
                'url' => '/admin/ai-gen/settings',
                'icon' => 'fas fa-cog',
                'order' => 8,
            ],
        ];

        foreach ($subMenus as $menu) {
            $exists = MenuItem::where('menu_location', 'admin')
                ->where('parent_id', $aiGenMenu->id)
                ->where('title', $menu['title'])
                ->exists();

            if (!$exists) {
                MenuItem::create([
                    'menu_location' => 'admin',
                    'parent_id' => $aiGenMenu->id,
                    'title' => $menu['title'],
                    'title_en' => $menu['title_en'] ?? $menu['title'],
                    'title_th' => $menu['title_th'] ?? $menu['title'],
                    'url' => $menu['url'] ?? null,
                    'route' => $menu['route'],
                    'target' => '_self',
                    'icon' => $menu['icon'],
                    'order' => $menu['order'],
                    'position' => $menu['order'],
                    'permission' => 'admin',
                    'is_active' => true,
                    'open_in_new_tab' => false,
                    'conditions' => [
                        'logged_in' => true,
                        'roles' => ['admin', 'super_admin'],
                    ],
                ]);

                $this->command->info("  ✅ {$menu['title']}");
            } else {
                $this->command->info("  ℹ️  Already exists: {$menu['title']}");
            }
        }

        $this->command->info('');
        $this->command->info('✨ AI Gen System menu created successfully!');
    }

    /**
     * Ensure all required columns exist in menu_items table
     */
    protected function ensureColumnsExist(): void
    {
        $requiredColumns = ['title_en', 'title_th', 'position', 'permission', 'open_in_new_tab'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('menu_items', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $this->command->warn('⚠️  Missing columns detected: ' . implode(', ', $missingColumns));
            $this->command->info('🔧 Running migration to add missing columns...');

            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2025_11_11_192200_add_missing_columns_to_menu_items_table.php',
                    '--force' => true,
                ]);

                $this->command->info('✅ Migration completed successfully!');
            } catch (\Exception $e) {
                $this->command->error('❌ Migration failed: ' . $e->getMessage());
                $this->command->error('Please run: php artisan migrate');
                throw $e;
            }
        } else {
            $this->command->info('✅ All required columns exist');
        }
    }
}
