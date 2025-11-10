<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert new Millennium Taskbar customization settings
        $this->insertMillenniumSettings();
    }

    /**
     * Insert Millennium Taskbar customization settings
     */
    private function insertMillenniumSettings(): void
    {
        $settings = [
            // Start Button Position
            ['key' => 'millennium_start_button_position', 'value' => 'center', 'type' => 'string'], // left, center, right

            // Start Button Size
            ['key' => 'millennium_start_button_width', 'value' => '120', 'type' => 'integer'], // pixels
            ['key' => 'millennium_start_button_height', 'value' => '48', 'type' => 'integer'], // pixels

            // Start Button Shape
            ['key' => 'millennium_start_button_shape', 'value' => 'rounded', 'type' => 'string'], // square, rounded, pill, circle
            ['key' => 'millennium_start_button_border_radius', 'value' => '16', 'type' => 'integer'], // pixels (for custom)

            // Start Button Display
            ['key' => 'millennium_start_button_show_icon', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_start_button_show_text', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_start_button_icon_size', 'value' => '32', 'type' => 'integer'], // pixels
            ['key' => 'millennium_start_button_font_size', 'value' => '20', 'type' => 'integer'], // pixels

            // Taskbar Transparency
            ['key' => 'millennium_taskbar_opacity', 'value' => '95', 'type' => 'integer'], // 0-100
            ['key' => 'millennium_taskbar_blur_amount', 'value' => '20', 'type' => 'integer'], // pixels

            // RGB Settings
            ['key' => 'millennium_rgb_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_rgb_speed', 'value' => '5', 'type' => 'integer'], // seconds
            ['key' => 'millennium_rgb_blur', 'value' => '2', 'type' => 'integer'], // pixels
            ['key' => 'millennium_rgb_glow_size', 'value' => '15', 'type' => 'integer'], // pixels
            ['key' => 'millennium_rgb_colors', 'value' => json_encode(['#FF0080', '#00F0FF', '#7F00FF', '#FFD700']), 'type' => 'json'],

            // Back Button
            ['key' => 'millennium_back_button_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_back_button_text', 'value' => 'กลับ', 'type' => 'string'],
            ['key' => 'millennium_back_button_show_icon', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_back_button_show_text', 'value' => 'true', 'type' => 'boolean'],

            // Clock Format
            ['key' => 'millennium_clock_format', 'value' => '24h', 'type' => 'string'], // 24h, 12h, custom
            ['key' => 'millennium_clock_show_seconds', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'millennium_clock_show_date', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'millennium_clock_date_format', 'value' => 'short', 'type' => 'string'], // short, long, custom
            ['key' => 'millennium_clock_style', 'value' => 'digital', 'type' => 'string'], // digital, analog, text

            // Taskbar Icons Customization
            ['key' => 'millennium_taskbar_icons', 'value' => json_encode([
                ['icon' => '🛒', 'label' => 'รถเข็น', 'url' => '/cart', 'border' => false, 'opacity' => 10, 'order' => 1],
                ['icon' => '🔮', 'label' => 'ดูดวง', 'url' => '/tarot', 'border' => false, 'opacity' => 10, 'order' => 2],
                ['icon' => '🤖', 'label' => 'เช่าบอท', 'url' => '/marketplace', 'border' => false, 'opacity' => 10, 'order' => 3],
                ['icon' => '💰', 'label' => 'กระเป๋าเงิน', 'url' => '/user/wallet', 'border' => false, 'opacity' => 10, 'order' => 4],
                ['icon' => '📈', 'label' => 'การลงทุน ROI', 'url' => '/user/investments', 'border' => false, 'opacity' => 10, 'order' => 5],
                ['icon' => '📚', 'label' => 'Platform Wiki', 'url' => '/platform-wiki', 'border' => false, 'opacity' => 10, 'order' => 6],
            ]), 'type' => 'json'],

            // Taskbar Icon Style
            ['key' => 'millennium_taskbar_icon_size', 'value' => '48', 'type' => 'integer'], // pixels
            ['key' => 'millennium_taskbar_icon_border_radius', 'value' => '12', 'type' => 'integer'], // pixels

            // Center Section
            ['key' => 'millennium_center_section_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_center_section_text', 'value' => '', 'type' => 'string'],

            // === MENU CUSTOMIZATION SETTINGS ===

            // Menu Size & Spacing
            ['key' => 'millennium_menu_width', 'value' => '400', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_max_height', 'value' => '600', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_padding', 'value' => '16', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_item_spacing', 'value' => '8', 'type' => 'integer'], // pixels

            // Menu Position
            ['key' => 'millennium_menu_position', 'value' => 'center', 'type' => 'string'], // left, center, right
            ['key' => 'millennium_menu_offset_x', 'value' => '0', 'type' => 'integer'], // pixels from edge
            ['key' => 'millennium_menu_offset_y', 'value' => '10', 'type' => 'integer'], // pixels from taskbar

            // Menu Main Header Style
            ['key' => 'millennium_menu_main_icon_size', 'value' => '28', 'type' => 'integer'], // pixels (3xl = ~28px)
            ['key' => 'millennium_menu_main_font_size', 'value' => '16', 'type' => 'integer'], // pixels (base = 16px)
            ['key' => 'millennium_menu_main_font_weight', 'value' => 'bold', 'type' => 'string'], // normal, medium, semibold, bold
            ['key' => 'millennium_menu_main_padding_x', 'value' => '16', 'type' => 'integer'], // pixels (px-4 = 16px)
            ['key' => 'millennium_menu_main_padding_y', 'value' => '12', 'type' => 'integer'], // pixels (py-3 = 12px)
            ['key' => 'millennium_menu_main_border_radius', 'value' => '12', 'type' => 'integer'], // pixels (rounded-xl = 12px)
            ['key' => 'millennium_menu_main_border_width', 'value' => '2', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_main_gradient_from', 'value' => '#9333ea', 'type' => 'color'], // purple-600
            ['key' => 'millennium_menu_main_gradient_to', 'value' => '#db2777', 'type' => 'color'], // pink-600

            // Menu Submenu Item Style
            ['key' => 'millennium_menu_sub_font_size', 'value' => '14', 'type' => 'integer'], // pixels (sm = 14px)
            ['key' => 'millennium_menu_sub_font_weight', 'value' => 'medium', 'type' => 'string'], // normal, medium, semibold, bold
            ['key' => 'millennium_menu_sub_padding_x', 'value' => '12', 'type' => 'integer'], // pixels (px-3 = 12px)
            ['key' => 'millennium_menu_sub_padding_y', 'value' => '8', 'type' => 'integer'], // pixels (py-2 = 8px)
            ['key' => 'millennium_menu_sub_border_radius', 'value' => '8', 'type' => 'integer'], // pixels (rounded-lg = 8px)
            ['key' => 'millennium_menu_sub_indent', 'value' => '32', 'type' => 'integer'], // pixels (ml-8 = 32px)
            ['key' => 'millennium_menu_sub_bullet_size', 'value' => '6', 'type' => 'integer'], // pixels (w-1.5 = 6px)

            // Menu Background & Effects
            ['key' => 'millennium_menu_bg_opacity', 'value' => '95', 'type' => 'integer'], // 0-100
            ['key' => 'millennium_menu_blur_amount', 'value' => '24', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_shadow_size', 'value' => 'xl', 'type' => 'string'], // sm, md, lg, xl, 2xl
            ['key' => 'millennium_menu_border_width', 'value' => '1', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_border_color', 'value' => 'rgba(255, 255, 255, 0.2)', 'type' => 'string'],

            // Menu Animation
            ['key' => 'millennium_menu_animation_duration', 'value' => '200', 'type' => 'integer'], // milliseconds
            ['key' => 'millennium_menu_animation_style', 'value' => 'slide', 'type' => 'string'], // slide, fade, scale, none

            // Menu RGB Border (separate from taskbar RGB)
            ['key' => 'millennium_menu_rgb_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_menu_rgb_border_width', 'value' => '2', 'type' => 'integer'], // pixels
            ['key' => 'millennium_menu_rgb_glow_size', 'value' => '10', 'type' => 'integer'], // pixels

            // Menu Search Bar
            ['key' => 'millennium_menu_search_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_menu_search_placeholder', 'value' => 'ค้นหาเมนู...', 'type' => 'string'],

            // Menu Footer
            ['key' => 'millennium_menu_footer_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'millennium_menu_footer_text', 'value' => 'Licensed © 2025 TP-Affiliate', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            // Check if key already exists
            $exists = DB::table('windows_ui_settings')->where('key', $setting['key'])->exists();

            if (!$exists) {
                DB::table('windows_ui_settings')->insert($setting + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            // Taskbar Settings
            'millennium_start_button_position',
            'millennium_start_button_width',
            'millennium_start_button_height',
            'millennium_start_button_shape',
            'millennium_start_button_border_radius',
            'millennium_start_button_show_icon',
            'millennium_start_button_show_text',
            'millennium_start_button_icon_size',
            'millennium_start_button_font_size',
            'millennium_taskbar_opacity',
            'millennium_taskbar_blur_amount',
            'millennium_rgb_enabled',
            'millennium_rgb_speed',
            'millennium_rgb_blur',
            'millennium_rgb_glow_size',
            'millennium_rgb_colors',
            'millennium_back_button_enabled',
            'millennium_back_button_text',
            'millennium_back_button_show_icon',
            'millennium_back_button_show_text',
            'millennium_clock_format',
            'millennium_clock_show_seconds',
            'millennium_clock_show_date',
            'millennium_clock_date_format',
            'millennium_clock_style',
            'millennium_taskbar_icons',
            'millennium_taskbar_icon_size',
            'millennium_taskbar_icon_border_radius',
            'millennium_center_section_enabled',
            'millennium_center_section_text',

            // Menu Settings
            'millennium_menu_width',
            'millennium_menu_max_height',
            'millennium_menu_padding',
            'millennium_menu_item_spacing',
            'millennium_menu_position',
            'millennium_menu_offset_x',
            'millennium_menu_offset_y',
            'millennium_menu_main_icon_size',
            'millennium_menu_main_font_size',
            'millennium_menu_main_font_weight',
            'millennium_menu_main_padding_x',
            'millennium_menu_main_padding_y',
            'millennium_menu_main_border_radius',
            'millennium_menu_main_border_width',
            'millennium_menu_main_gradient_from',
            'millennium_menu_main_gradient_to',
            'millennium_menu_sub_font_size',
            'millennium_menu_sub_font_weight',
            'millennium_menu_sub_padding_x',
            'millennium_menu_sub_padding_y',
            'millennium_menu_sub_border_radius',
            'millennium_menu_sub_indent',
            'millennium_menu_sub_bullet_size',
            'millennium_menu_bg_opacity',
            'millennium_menu_blur_amount',
            'millennium_menu_shadow_size',
            'millennium_menu_border_width',
            'millennium_menu_border_color',
            'millennium_menu_animation_duration',
            'millennium_menu_animation_style',
            'millennium_menu_rgb_enabled',
            'millennium_menu_rgb_border_width',
            'millennium_menu_rgb_glow_size',
            'millennium_menu_search_enabled',
            'millennium_menu_search_placeholder',
            'millennium_menu_footer_enabled',
            'millennium_menu_footer_text',
        ];

        DB::table('windows_ui_settings')->whereIn('key', $keys)->delete();
    }
};
