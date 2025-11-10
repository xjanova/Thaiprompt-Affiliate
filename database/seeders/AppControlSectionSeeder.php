<?php

namespace Database\Seeders;

use App\Models\AppControlSection;
use Illuminate\Database\Seeder;

class AppControlSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [
            [
                'section_id' => 'navigation_bar',
                'section_name' => 'แถบนำทาง',
                'section_name_en' => 'Navigation Bar',
                'description' => 'แถบนำทางหลักของแอพพลิเคชัน',
                'description_en' => 'Main navigation bar of the application',
                'is_visible' => true,
                'display_position' => 'top',
                'sort_order' => 1,
                'layout_type' => 'horizontal',
                'min_height' => 56,
                'background_color' => '#FFFFFF',
                'background_dark_color' => '#1F2937',
                'elevation' => 2,
                'padding_top' => 8,
                'padding_bottom' => 8,
                'padding_left' => 16,
                'padding_right' => 16,
                'platform' => 'all',
                'is_active' => true,
            ],
            [
                'section_id' => 'tab_bar',
                'section_name' => 'แถบแท็บ',
                'section_name_en' => 'Tab Bar',
                'description' => 'แถบแท็บด้านล่างสำหรับการนำทาง',
                'description_en' => 'Bottom tab bar for navigation',
                'is_visible' => true,
                'display_position' => 'bottom',
                'sort_order' => 2,
                'layout_type' => 'horizontal',
                'min_height' => 60,
                'max_height' => 60,
                'background_color' => '#FFFFFF',
                'background_dark_color' => '#1F2937',
                'border_color' => '#E5E7EB',
                'border_dark_color' => '#374151',
                'border_width' => 1,
                'elevation' => 4,
                'padding_top' => 8,
                'padding_bottom' => 8,
                'platform' => 'all',
                'is_active' => true,
            ],
            [
                'section_id' => 'header',
                'section_name' => 'ส่วนหัว',
                'section_name_en' => 'Header',
                'description' => 'ส่วนหัวของหน้าจอ',
                'description_en' => 'Page header section',
                'is_visible' => true,
                'display_position' => 'top',
                'sort_order' => 3,
                'layout_type' => 'vertical',
                'background_color' => '#3B82F6',
                'background_dark_color' => '#1E40AF',
                'border_radius' => 0,
                'padding_top' => 16,
                'padding_bottom' => 16,
                'padding_left' => 16,
                'padding_right' => 16,
                'platform' => 'all',
                'is_active' => true,
            ],
            [
                'section_id' => 'content_area',
                'section_name' => 'พื้นที่เนื้อหา',
                'section_name_en' => 'Content Area',
                'description' => 'พื้นที่หลักสำหรับแสดงเนื้อหา',
                'description_en' => 'Main content area',
                'is_visible' => true,
                'display_position' => 'center',
                'sort_order' => 4,
                'layout_type' => 'vertical',
                'background_color' => '#F9FAFB',
                'background_dark_color' => '#111827',
                'padding_top' => 16,
                'padding_bottom' => 16,
                'padding_left' => 16,
                'padding_right' => 16,
                'platform' => 'all',
                'is_active' => true,
            ],
            [
                'section_id' => 'floating_action_button',
                'section_name' => 'ปุ่มลอยตัว',
                'section_name_en' => 'Floating Action Button',
                'description' => 'ปุ่มลอยตัวสำหรับการดำเนินการหลัก',
                'description_en' => 'Floating action button for primary actions',
                'is_visible' => true,
                'display_position' => 'bottom-right',
                'sort_order' => 5,
                'layout_type' => 'stack',
                'min_height' => 56,
                'max_height' => 56,
                'min_width' => 56,
                'max_width' => 56,
                'background_color' => '#3B82F6',
                'background_dark_color' => '#2563EB',
                'border_radius' => 28,
                'elevation' => 6,
                'margin_bottom' => 16,
                'margin_right' => 16,
                'animation_enabled' => true,
                'animation_type' => 'scale',
                'animation_duration' => 200,
                'platform' => 'all',
                'is_active' => true,
            ],
        ];

        foreach ($sections as $section) {
            AppControlSection::updateOrCreate(
                ['section_id' => $section['section_id']],
                $section
            );
        }
    }
}
