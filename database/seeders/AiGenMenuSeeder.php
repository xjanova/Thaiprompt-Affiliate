<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class AiGenMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Main AI Gen System Menu
        $aiGenMenu = MenuItem::create([
            'parent_id' => null,
            'title' => 'AI Gen System',
            'title_en' => 'AI Gen System',
            'title_th' => 'ระบบสร้างภาพ/วีดีโอ AI',
            'url' => null,
            'route' => null,
            'icon' => 'fas fa-magic',
            'position' => 60,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Dashboard
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'Dashboard',
            'title_en' => 'Dashboard',
            'title_th' => 'แดชบอร์ด',
            'url' => '/admin/ai-gen/dashboard',
            'route' => 'admin.ai-gen.dashboard',
            'icon' => 'fas fa-chart-line',
            'position' => 1,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Providers Management
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'AI Providers',
            'title_en' => 'AI Providers',
            'title_th' => 'จัดการ AI Providers',
            'url' => '/admin/ai-gen/providers',
            'route' => 'admin.ai-gen.providers',
            'icon' => 'fas fa-server',
            'position' => 2,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Packages Management
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'Packages',
            'title_en' => 'Packages',
            'title_th' => 'จัดการแพคเกจ',
            'url' => '/admin/ai-gen/packages',
            'route' => 'admin.ai-gen.packages',
            'icon' => 'fas fa-box',
            'position' => 3,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Quotas Management
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'Free Quotas',
            'title_en' => 'Free Quotas',
            'title_th' => 'จัดการโควต้าฟรี',
            'url' => '/admin/ai-gen/quotas',
            'route' => 'admin.ai-gen.quotas',
            'icon' => 'fas fa-gift',
            'position' => 4,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Subscriptions
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'Subscriptions',
            'title_en' => 'Subscriptions',
            'title_th' => 'จัดการสมาชิก',
            'url' => '/admin/ai-gen/subscriptions',
            'route' => 'admin.ai-gen.subscriptions',
            'icon' => 'fas fa-users',
            'position' => 5,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Usage Logs
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'Usage Logs',
            'title_en' => 'Usage Logs',
            'title_th' => 'บันทึกการใช้งาน',
            'url' => '/admin/ai-gen/usage-logs',
            'route' => 'admin.ai-gen.usage-logs',
            'icon' => 'fas fa-history',
            'position' => 6,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // All Generations
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'All Generations',
            'title_en' => 'All Generations',
            'title_th' => 'ภาพ/วีดีโอทั้งหมด',
            'url' => '/admin/ai-gen/generations',
            'route' => 'admin.ai-gen.generations',
            'icon' => 'fas fa-images',
            'position' => 7,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Settings
        MenuItem::create([
            'parent_id' => $aiGenMenu->id,
            'title' => 'Settings',
            'title_en' => 'Settings',
            'title_th' => 'ตั้งค่าระบบ',
            'url' => '/admin/ai-gen/settings',
            'route' => 'admin.ai-gen.settings',
            'icon' => 'fas fa-cog',
            'position' => 8,
            'permission' => 'admin',
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        $this->command->info('AI Gen System menu created successfully!');
    }
}
