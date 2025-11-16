<?php

namespace Database\Seeders;

use App\Models\ThemePreset;
use Illuminate\Database\Seeder;

/**
 * ThemePresetSeeder
 *
 * สร้างชุด preset สีสำหรับ Arrow X Theme
 */
class ThemePresetSeeder extends Seeder
{
    /**
     * สร้างข้อมูล theme presets
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🎨 กำลัง seed ข้อมูล Theme Presets...');

        // ตรวจสอบก่อนสร้าง (idempotent)
        if (ThemePreset::count() > 0) {
            $this->command->info('ข้อมูล Theme Presets มีอยู่แล้ว ข้าม...');
            return;
        }

        $presets = [
            // 1. Classic (Default Arrow X) - แนะนำ ⭐
            [
                'name' => 'classic',
                'display_name' => 'Classic',
                'description' => 'ชุดสีคลาสสิกของ Arrow X (Purple → Pink → Orange)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#9333EA',
                    'primary_middle' => '#EC4899',
                    'primary_end' => '#F97316',
                    'secondary_start' => '#3B82F6',
                    'secondary_middle' => '#06B6D4',
                    'secondary_end' => '#14B8A6',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#F59E0B',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => true,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 2. Modern Blue - แนะนำ ⭐
            [
                'name' => 'modern-blue',
                'display_name' => 'Modern Blue',
                'description' => 'โทนสีน้ำเงินสดใส ทันสมัย (Blue → Cyan → Teal)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#3B82F6',
                    'primary_middle' => '#06B6D4',
                    'primary_end' => '#14B8A6',
                    'secondary_start' => '#0EA5E9',
                    'secondary_middle' => '#22D3EE',
                    'secondary_end' => '#2DD4BF',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#14B8A6',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => true,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 3. Sunset 🌅
            [
                'name' => 'sunset',
                'display_name' => 'Sunset',
                'description' => 'โทนสีพระอาทิตย์ตกดิน อบอุ่น (Orange → Red → Pink)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#F97316',
                    'primary_middle' => '#EF4444',
                    'primary_end' => '#EC4899',
                    'secondary_start' => '#FB923C',
                    'secondary_middle' => '#F87171',
                    'secondary_end' => '#F472B6',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#EC4899',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => true,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 4. Ocean 🌊
            [
                'name' => 'ocean',
                'display_name' => 'Ocean',
                'description' => 'โทนสีท้องทะเลน้ำเงินเขียว สดชื่น (Teal → Cyan → Blue)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#14B8A6',
                    'primary_middle' => '#06B6D4',
                    'primary_end' => '#3B82F6',
                    'secondary_start' => '#2DD4BF',
                    'secondary_middle' => '#22D3EE',
                    'secondary_end' => '#60A5FA',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#06B6D4',
                    'accent_color' => '#14B8A6',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 5. Forest 🌲
            [
                'name' => 'forest',
                'display_name' => 'Forest',
                'description' => 'โทนสีป่าไม้เขียวขจี ธรรมชาติ (Green → Emerald → Teal)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#22C55E',
                    'primary_middle' => '#10B981',
                    'primary_end' => '#14B8A6',
                    'secondary_start' => '#4ADE80',
                    'secondary_middle' => '#34D399',
                    'secondary_end' => '#2DD4BF',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#14B8A6',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 6. Vibrant ✨
            [
                'name' => 'vibrant',
                'display_name' => 'Vibrant',
                'description' => 'โทนสีสดใส ตื่นตา พลังจัด (Pink → Purple → Blue)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#EC4899',
                    'primary_middle' => '#A855F7',
                    'primary_end' => '#3B82F6',
                    'secondary_start' => '#F472B6',
                    'secondary_middle' => '#C084FC',
                    'secondary_end' => '#60A5FA',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#A855F7',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 7. Dark Professional 🌑
            [
                'name' => 'dark-professional',
                'display_name' => 'Dark Professional',
                'description' => 'โทนสีม่วงเข้ม มืดมนเป็นมืออาชีพ (Indigo → Purple → Blue)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#6366F1',
                    'primary_middle' => '#8B5CF6',
                    'primary_end' => '#3B82F6',
                    'secondary_start' => '#818CF8',
                    'secondary_middle' => '#A78BFA',
                    'secondary_end' => '#60A5FA',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#6366F1',
                    'accent_color' => '#8B5CF6',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 8. Minimal ⚪
            [
                'name' => 'minimal',
                'display_name' => 'Minimal',
                'description' => 'โทนสีเรียบง่าย มินิมอล (Gray → Slate → Stone)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#64748B',
                    'primary_middle' => '#475569',
                    'primary_end' => '#334155',
                    'secondary_start' => '#94A3B8',
                    'secondary_middle' => '#64748B',
                    'secondary_end' => '#475569',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#475569',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 9. Corporate 💼
            [
                'name' => 'corporate',
                'display_name' => 'Corporate',
                'description' => 'โทนสีน้ำเงินเข้ม เหมาะกับองค์กร (Navy → Blue → Sky)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#1E3A8A',
                    'primary_middle' => '#1E40AF',
                    'primary_end' => '#2563EB',
                    'secondary_start' => '#1D4ED8',
                    'secondary_middle' => '#3B82F6',
                    'secondary_end' => '#60A5FA',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#EF4444',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#2563EB',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],

            // 10. Creative 🎨
            [
                'name' => 'creative',
                'display_name' => 'Creative',
                'description' => 'โทนสีแดง-ส้ม-เหลือง สร้างสรรค์ โดดเด่น (Red → Orange → Amber)',
                'preview_image' => null,
                'colors' => [
                    'primary_start' => '#DC2626',
                    'primary_middle' => '#F97316',
                    'primary_end' => '#F59E0B',
                    'secondary_start' => '#EF4444',
                    'secondary_middle' => '#FB923C',
                    'secondary_end' => '#FBBF24',
                    'success_color' => '#10B981',
                    'warning_color' => '#F59E0B',
                    'error_color' => '#DC2626',
                    'info_color' => '#3B82F6',
                    'accent_color' => '#F97316',
                    'gradient_direction' => 'to-right',
                ],
                'is_featured' => false,
                'is_active' => true,
                'usage_count' => 0,
            ],
        ];

        // สร้าง presets
        foreach ($presets as $preset) {
            ThemePreset::create($preset);
            $this->command->info("  ✓ สร้าง preset: {$preset['display_name']}");
        }

        $this->command->info('✅ Seed ข้อมูล Theme Presets สำเร็จ! (สร้าง ' . count($presets) . ' presets)');
    }
}
