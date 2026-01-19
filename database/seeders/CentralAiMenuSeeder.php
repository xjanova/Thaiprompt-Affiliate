<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CentralAiMenuSeeder
 *
 * เพิ่มเมนู Central AI เข้าสู่ระบบ
 * สำหรับจัดการ Ollama และ PostXAgent
 *
 * @version 1.0.0
 * @since 2026-01-19
 */
class CentralAiMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * เพิ่มเมนู Central AI เข้าสู่ database
     */
    public function run(): void
    {
        $this->command->info('🧠 กำลังเพิ่มเมนู Central AI...');

        // ตรวจสอบว่ามีเมนู Central AI อยู่แล้วหรือไม่
        $existingMenu = MenuItem::where('menu_key', 'admin.central-ai')->first();

        if ($existingMenu) {
            $this->command->warn('⚠️  เมนู Central AI มีอยู่แล้ว กำลังข้าม...');
            return;
        }

        DB::transaction(function () {
            // สร้างเมนูหลัก Central AI
            $centralAiMenu = MenuItem::create([
                'menu_key' => 'admin.central-ai',
                'dashboard_type' => 'admin',
                'parent_id' => null,
                'label' => 'Central AI',
                'icon' => '🧠',
                'route' => null,
                'url' => null,
                'order' => 4.2,
                'is_active' => true,
                'is_visible' => true,
                'permissions' => [],
                'condition' => null,
                'hide_if_kyc_verified' => false,
                'badge' => 'NEW',
                'badge_color' => 'bg-gradient-to-r from-blue-500 to-purple-600',
                'description' => 'ระบบจัดการ AI แบบรวมศูนย์สำหรับควบคุม Ollama และ PostXAgent',
                'is_divider' => false,
            ]);

            $this->command->info('   ✅ สร้างเมนูหลัก Central AI สำเร็จ (ID: ' . $centralAiMenu->id . ')');

            // สร้าง submenu
            $submenus = [
                [
                    'label' => '🎯 Dashboard',
                    'route' => 'admin.central-ai.dashboard',
                    'description' => 'ควบคุมและติดตามระบบ',
                    'order' => 1,
                ],
                [
                    'label' => '🔧 Setup Wizard',
                    'route' => 'admin.central-ai.wizard',
                    'description' => 'ติดตั้งและตั้งค่า',
                    'order' => 2,
                ],
                [
                    'label' => '🤖 Ollama Management',
                    'route' => 'admin.central-ai.ollama.status',
                    'description' => 'จัดการ Ollama Service',
                    'order' => 3,
                ],
                [
                    'label' => '⚙️ Settings',
                    'route' => 'admin.central-ai.index',
                    'description' => 'ตั้งค่าระบบ',
                    'order' => 4,
                ],
            ];

            foreach ($submenus as $submenu) {
                MenuItem::create([
                    'menu_key' => 'admin.central-ai.' . str_replace(['🎯 ', '🔧 ', '🤖 ', '⚙️ '], '', strtolower(str_replace(' ', '-', $submenu['label']))),
                    'dashboard_type' => 'admin',
                    'parent_id' => $centralAiMenu->id,
                    'label' => $submenu['label'],
                    'icon' => null,
                    'route' => $submenu['route'],
                    'url' => null,
                    'order' => $submenu['order'],
                    'is_active' => true,
                    'is_visible' => true,
                    'permissions' => [],
                    'condition' => null,
                    'hide_if_kyc_verified' => false,
                    'badge' => null,
                    'badge_color' => null,
                    'description' => $submenu['description'],
                    'is_divider' => false,
                ]);
            }

            $this->command->info('   ✅ สร้าง submenu ' . count($submenus) . ' รายการสำเร็จ');
        });

        $this->command->info('');
        $this->command->info('✨ เพิ่มเมนู Central AI เสร็จสมบูรณ์!');
    }
}
