<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ThemeSetting;
use App\Models\ThemeColor;
use App\Models\ThemeRgbEffect;
use App\Models\ThemeTypography;
use Illuminate\Support\Facades\DB;

class ArrowXThemeSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🎨 กำลัง seed ข้อมูล Arrow X Theme...');
        $this->command->info('');

        // ✅ CRITICAL: ตรวจสอบก่อนสร้าง (idempotent)
        if (ThemeSetting::where('theme_name', 'Arrow X')->exists()) {
            $this->command->info('⏭️  Arrow X Theme มีอยู่แล้ว ข้าม...');
            return;
        }

        DB::transaction(function () {
            // 1. สร้าง Theme Setting
            $themeSetting = ThemeSetting::create([
                'theme_name' => 'Arrow X',
                'theme_version' => '1.0.0',
                'is_active' => true,
                'brand_name' => 'TP-Affiliate',
                'brand_tagline' => 'Ultimate Affiliate Marketing Platform',
                'layout_type' => 'fluid',
                'sidebar_width' => 260,
                'navbar_height' => 64,
                'footer_height' => 80,
                'global_opacity' => 100,
                'sidebar_opacity' => 100,
                'navbar_opacity' => 100,
                'card_opacity' => 100,
                'modal_opacity' => 95,
                'card_blur_intensity' => 0,
                'card_border_width' => 1,
                'card_border_radius' => 16,
                'card_shadow_intensity' => 'lg',
                'default_language' => 'th',
                'available_languages' => ['th', 'en', 'ja', 'zh', 'ko'],
                'rtl_enabled' => false,
            ]);

            $this->command->info('✅ สร้าง Theme Setting สำเร็จ');

            // 2. สร้าง Theme Colors
            $themeSetting->color()->create([
                'scheme_name' => 'Arrow X Default',
                'is_default' => true,
                // Primary Gradient (Purple → Pink → Orange)
                'primary_start' => '#9333EA',
                'primary_middle' => '#EC4899',
                'primary_end' => '#F97316',
                // Secondary Gradient (Blue → Cyan → Teal)
                'secondary_start' => '#3B82F6',
                'secondary_middle' => '#06B6D4',
                'secondary_end' => '#14B8A6',
                // Accent & Status Colors
                'accent_color' => '#F59E0B',
                'success_color' => '#10B981',
                'warning_color' => '#F59E0B',
                'error_color' => '#EF4444',
                'info_color' => '#3B82F6',
                // Background Colors (Light)
                'background_primary' => '#FFFFFF',
                'background_secondary' => '#F9FAFB',
                'background_tertiary' => '#F3F4F6',
                // Background Colors (Dark)
                'dark_background_primary' => '#111827',
                'dark_background_secondary' => '#1F2937',
                'dark_background_tertiary' => '#374151',
                // Text Colors (Light)
                'text_primary' => '#111827',
                'text_secondary' => '#6B7280',
                'text_tertiary' => '#9CA3AF',
                // Text Colors (Dark)
                'dark_text_primary' => '#F9FAFB',
                'dark_text_secondary' => '#D1D5DB',
                'dark_text_tertiary' => '#9CA3AF',
                // Borders
                'border_color' => '#E5E7EB',
                'divider_color' => '#E5E7EB',
                'dark_border_color' => '#374151',
                'dark_divider_color' => '#374151',
                'gradient_direction' => 'to-right',
            ]);

            $this->command->info('✅ สร้าง Theme Colors สำเร็จ');

            // 3. สร้าง Typography
            $themeSetting->typography()->create([
                'primary_font' => 'Inter',
                'secondary_font' => 'Noto Sans Thai',
                'code_font' => 'Fira Code',
                'base_font_size' => 1.00,
                'heading_h1_size' => 2.50,
                'heading_h2_size' => 2.00,
                'heading_h3_size' => 1.75,
                'heading_h4_size' => 1.50,
                'heading_h5_size' => 1.25,
                'heading_h6_size' => 1.00,
                'thin_weight' => 300,
                'normal_weight' => 400,
                'medium_weight' => 500,
                'semibold_weight' => 600,
                'bold_weight' => 700,
                'extrabold_weight' => 800,
                'heading_line_height' => 1.20,
                'body_line_height' => 1.60,
                'heading_letter_spacing' => 0.000,
                'body_letter_spacing' => 0.000,
            ]);

            $this->command->info('✅ สร้าง Typography สำเร็จ');

            // 4. สร้าง RGB Effects ตัวอย่าง
            $rgbEffects = [
                [
                    'effect_name' => 'Active Menu Glow',
                    'target_element' => 'menu-items-active',
                    'trigger_state' => 'active',
                    'animation_type' => 'glow',
                    'rgb_colors' => ['#9333EA', '#EC4899', '#F97316'],
                    'animation_duration' => 2000,
                    'animation_timing' => 'ease-in-out',
                    'intensity' => 'medium',
                    'blur_radius' => 15,
                    'delay' => 0,
                    'iteration_count' => 'infinite',
                    'z_index' => 0,
                    'is_enabled' => true,
                ],
                [
                    'effect_name' => 'Button Click RGB',
                    'target_element' => 'buttons-active',
                    'trigger_state' => 'click',
                    'animation_type' => 'pulse',
                    'rgb_colors' => ['#3B82F6', '#EC4899', '#F97316'],
                    'animation_duration' => 1500,
                    'animation_timing' => 'ease',
                    'intensity' => 'strong',
                    'blur_radius' => 20,
                    'delay' => 0,
                    'iteration_count' => '3',
                    'z_index' => 0,
                    'is_enabled' => true,
                ],
                [
                    'effect_name' => 'Link Hover Rainbow',
                    'target_element' => 'links-active',
                    'trigger_state' => 'hover',
                    'animation_type' => 'rainbow',
                    'rgb_colors' => ['#9333EA', '#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                    'animation_duration' => 3000,
                    'animation_timing' => 'linear',
                    'intensity' => 'subtle',
                    'blur_radius' => 10,
                    'delay' => 0,
                    'iteration_count' => 'infinite',
                    'z_index' => 0,
                    'is_enabled' => false, // ปิดไว้ให้ user เปิดเอง
                ],
            ];

            foreach ($rgbEffects as $effectData) {
                $themeSetting->rgbEffects()->create($effectData);
            }

            $this->command->info('✅ สร้าง RGB Effects ตัวอย่าง สำเร็จ (3 effects)');
        });

        $this->command->info('');
        $this->command->info('✨ Seed ข้อมูล Arrow X Theme สำเร็จ!');
        $this->command->info('');
    }
}
