<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AcademyMenuSeeder
 *
 * เพิ่มเมนู ศูนย์การเรียนรู้ (Academy) สำหรับ User Dashboard
 * ใช้สำหรับกรณีที่ต้องการเพิ่มเมนูเข้าไปในระบบที่มีข้อมูลอยู่แล้ว
 *
 * @version 1.0.0
 * @since 2025-12-05
 */
class AcademyMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * เพิ่มเมนู Academy และ submenu ในตาราง menu_items
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🎓 กำลังเพิ่มเมนู ศูนย์การเรียนรู้ (Academy)...');

        // ตรวจสอบว่าตาราง menu_items มีอยู่หรือไม่
        if (!Schema::hasTable('menu_items')) {
            $this->command->warn('⚠️ ไม่พบตาราง menu_items กรุณา run migration ก่อน');
            return;
        }

        // ตรวจสอบว่าเมนู Academy มีอยู่แล้วหรือไม่
        if (MenuItem::where('menu_key', 'user.academy')->exists()) {
            $this->command->info('ℹ️ เมนู Academy มีอยู่แล้ว ข้าม...');
            return;
        }

        DB::transaction(function () {
            // สร้างเมนูหลัก Academy
            $academyMenu = MenuItem::create([
                'menu_key' => 'user.academy',
                'dashboard_type' => 'user',
                'parent_id' => null,
                'label' => 'ศูนย์การเรียนรู้',
                'icon' => '🎓',
                'route' => null,
                'url' => null,
                'order' => 6.1,
                'is_active' => true,
                'is_visible' => true,
                'permissions' => [],
                'condition' => null,
                'hide_if_kyc_verified' => false,
                'badge' => 'NEW',
                'badge_color' => 'bg-gradient-to-r from-indigo-500 to-purple-500',
                'description' => 'เรียนรู้และพัฒนาทักษะ',
                'is_divider' => false,
            ]);

            $this->command->info("   ✅ สร้างเมนูหลัก: {$academyMenu->label}");

            // สร้าง submenu
            $submenus = [
                [
                    'menu_key' => 'user.academy.index',
                    'label' => '🏠 หน้าหลัก',
                    'route' => 'user.academy.index',
                    'order' => 1,
                    'description' => 'ศูนย์การเรียนรู้',
                ],
                [
                    'menu_key' => 'user.academy.my-progress',
                    'label' => '📊 ความก้าวหน้าของฉัน',
                    'route' => 'user.academy.my-progress',
                    'order' => 2,
                    'description' => 'ติดตามผลการเรียน',
                ],
            ];

            foreach ($submenus as $submenu) {
                MenuItem::create([
                    'menu_key' => $submenu['menu_key'],
                    'dashboard_type' => 'user',
                    'parent_id' => $academyMenu->id,
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

                $this->command->info("      ✅ สร้าง submenu: {$submenu['label']}");
            }
        });

        $this->command->info('');
        $this->command->info('✨ เพิ่มเมนู ศูนย์การเรียนรู้ สำเร็จ!');
    }
}
