<?php

namespace Database\Seeders;

use App\Models\ComponentSetting;
use Illuminate\Database\Seeder;

class ComponentSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Smart Seeding Strategy (NEVER DELETES USER DATA):
     * - Check each component individually by component_id
     * - If exists: SKIP (preserve user customization)
     * - If missing: ADD (insert default settings)
     */
    public function run(): void
    {
        $components = [
            // Button Components
            [
                'component_id' => 'button_primary',
                'component_name' => 'ปุ่มหลัก',
                'component_name_en' => 'Primary Button',
                'component_type' => 'button',
                'description' => 'ปุ่มหลักสำหรับการดำเนินการสำคัญ',
                'min_height' => 44,
                'padding_top' => 12,
                'padding_bottom' => 12,
                'padding_left' => 24,
                'padding_right' => 24,
                'font_size' => 16,
                'font_weight' => 'bold',
                'text_align' => 'center',
                'background_color' => '#3B82F6',
                'background_dark_color' => '#2563EB',
                'text_color' => '#FFFFFF',
                'text_dark_color' => '#FFFFFF',
                'border_width' => 0,
                'corner_radius' => 8,
                'shadow_elevation' => 2,
                'hover_background_color' => '#2563EB',
                'active_background_color' => '#1D4ED8',
                'disabled_background_color' => '#9CA3AF',
                'disabled_opacity' => 0.5,
                'animation_enabled' => true,
                'animation_type' => 'ripple',
                'animation_duration' => 200,
                'platform' => 'all',
                'is_enabled' => true,
            ],
            [
                'component_id' => 'button_secondary',
                'component_name' => 'ปุ่มรอง',
                'component_name_en' => 'Secondary Button',
                'component_type' => 'button',
                'description' => 'ปุ่มรองสำหรับการดำเนินการทั่วไป',
                'min_height' => 44,
                'padding_top' => 12,
                'padding_bottom' => 12,
                'padding_left' => 24,
                'padding_right' => 24,
                'font_size' => 16,
                'font_weight' => '600',
                'text_align' => 'center',
                'background_color' => 'transparent',
                'text_color' => '#3B82F6',
                'text_dark_color' => '#60A5FA',
                'border_color' => '#3B82F6',
                'border_dark_color' => '#60A5FA',
                'border_width' => 2,
                'corner_radius' => 8,
                'hover_border_color' => '#2563EB',
                'active_border_color' => '#1D4ED8',
                'disabled_border_color' => '#9CA3AF',
                'disabled_text_color' => '#9CA3AF',
                'disabled_opacity' => 0.5,
                'platform' => 'all',
                'is_enabled' => true,
            ],

            // Input Components
            [
                'component_id' => 'input_text',
                'component_name' => 'ช่องกรอกข้อความ',
                'component_name_en' => 'Text Input',
                'component_type' => 'input',
                'description' => 'ช่องกรอกข้อความทั่วไป',
                'min_height' => 48,
                'padding_top' => 12,
                'padding_bottom' => 12,
                'padding_left' => 16,
                'padding_right' => 16,
                'font_size' => 16,
                'font_weight' => 'normal',
                'background_color' => '#F9FAFB',
                'background_dark_color' => '#374151',
                'text_color' => '#111827',
                'text_dark_color' => '#F9FAFB',
                'border_color' => '#D1D5DB',
                'border_dark_color' => '#4B5563',
                'border_width' => 1,
                'corner_radius' => 8,
                'focus_border_color' => '#3B82F6',
                'focus_border_width' => 2,
                'platform' => 'all',
                'is_enabled' => true,
            ],

            // Card Components
            [
                'component_id' => 'card_default',
                'component_name' => 'การ์ดมาตรฐาน',
                'component_name_en' => 'Default Card',
                'component_type' => 'card',
                'description' => 'การ์ดสำหรับแสดงเนื้อหา',
                'padding_top' => 16,
                'padding_bottom' => 16,
                'padding_left' => 16,
                'padding_right' => 16,
                'margin_bottom' => 16,
                'background_color' => '#FFFFFF',
                'background_dark_color' => '#1F2937',
                'border_color' => '#E5E7EB',
                'border_dark_color' => '#374151',
                'border_width' => 1,
                'corner_radius' => 12,
                'shadow_elevation' => 1,
                'shadow_color' => '#000000',
                'shadow_offset_y' => 2,
                'shadow_blur_radius' => 8,
                'platform' => 'all',
                'is_enabled' => true,
            ],

            // Text Components
            [
                'component_id' => 'text_heading',
                'component_name' => 'หัวข้อ',
                'component_name_en' => 'Heading',
                'component_type' => 'text',
                'description' => 'ข้อความหัวข้อ',
                'font_size' => 24,
                'font_weight' => 'bold',
                'line_height' => 1.3,
                'text_color' => '#111827',
                'text_dark_color' => '#F9FAFB',
                'margin_bottom' => 8,
                'platform' => 'all',
                'is_enabled' => true,
            ],
            [
                'component_id' => 'text_body',
                'component_name' => 'เนื้อหา',
                'component_name_en' => 'Body Text',
                'component_type' => 'text',
                'description' => 'ข้อความเนื้อหา',
                'font_size' => 16,
                'font_weight' => 'normal',
                'line_height' => 1.5,
                'text_color' => '#374151',
                'text_dark_color' => '#D1D5DB',
                'margin_bottom' => 8,
                'platform' => 'all',
                'is_enabled' => true,
            ],
            [
                'component_id' => 'text_caption',
                'component_name' => 'คำบรรยาย',
                'component_name_en' => 'Caption',
                'component_type' => 'text',
                'description' => 'ข้อความคำบรรยาย',
                'font_size' => 12,
                'font_weight' => 'normal',
                'line_height' => 1.4,
                'text_color' => '#6B7280',
                'text_dark_color' => '#9CA3AF',
                'platform' => 'all',
                'is_enabled' => true,
            ],

            // Container Components
            [
                'component_id' => 'container_page',
                'component_name' => 'คอนเทนเนอร์หน้า',
                'component_name_en' => 'Page Container',
                'component_type' => 'container',
                'description' => 'คอนเทนเนอร์หลักของหน้า',
                'padding_top' => 16,
                'padding_bottom' => 16,
                'padding_left' => 16,
                'padding_right' => 16,
                'background_color' => '#F9FAFB',
                'background_dark_color' => '#111827',
                'platform' => 'all',
                'is_enabled' => true,
            ],
        ];

        $this->command->info('🔄 Running Smart Seeding for Component Settings...');
        $this->command->info('   Strategy: Add missing components only (never delete/overwrite)');

        $added = 0;
        $skipped = 0;

        foreach ($components as $component) {
            // Check if component already exists
            if (!ComponentSetting::where('component_id', $component['component_id'])->exists()) {
                ComponentSetting::create($component);
                $this->command->info("   ✅ Added: {$component['component_id']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✨ Added {$added} new components.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing components (preserved user customizations).");
        }

        if ($added === 0 && $skipped > 0) {
            $this->command->info('✅ All components are up to date. No changes needed.');
        }
    }
}
