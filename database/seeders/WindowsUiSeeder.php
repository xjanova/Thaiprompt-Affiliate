<?php

namespace Database\Seeders;

use App\Models\WindowsUiSetting;
use Illuminate\Database\Seeder;

class WindowsUiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ⚠️  IMPORTANT NOTICE (Updated 2025-01-11):
     * This seeder NO LONGER creates menu items. All menus are now hard-coded.
     *
     * Smart Seeding Strategy (NEVER DELETES USER DATA):
     * - Check each setting individually
     * - If exists: SKIP (preserve user customization)
     * - If missing: ADD (insert default value)
     *
     * CRITICAL RULES:
     * 1. ❌ NEVER delete existing settings
     * 2. ❌ NEVER overwrite existing settings
     * 3. ✅ ALWAYS add only missing settings
     * 4. ✅ ALWAYS preserve user customizations
     * 5. ❌ NEVER seed menu items (they are hard-coded now)
     *
     * SCOPE:
     * - ✅ Seeds: UI customization settings (colors, sizes, themes)
     * - ❌ Does NOT seed: Menu items, taskbar apps, system tray icons
     *
     * This follows the Smart Seeding Guidelines in .claude/seeder-guidelines.md
     */
    public function run(): void
    {
        $this->command->info('🔄 Running Smart Seeding for Windows UI Settings...');
        $this->command->info('   Strategy: Add missing settings only (never delete/overwrite)');

        $added = 0;
        $skipped = 0;

        // Seed all settings using Smart Seeding
        $allSettings = $this->getAllSettings();

        foreach ($allSettings as $key => $config) {
            if (!WindowsUiSetting::where('key', $key)->exists()) {
                WindowsUiSetting::set($key, $config['value'], $config['type']);
                $this->command->info("   ✅ Added: {$key}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✨ Added {$added} new settings.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing settings (preserved user customizations).");
        }

        if ($added === 0 && $skipped > 0) {
            $this->command->info('✅ All settings are up to date. No changes needed.');
        }
    }

    /**
     * Get all default settings
     * Returns all settings that should exist in the system
     */
    private function getAllSettings(): array
    {
        // ========================================
        // ⚠️  IMPORTANT: MENUS ARE NOW HARD-CODED
        // ========================================
        //
        // As of 2025-01-11, all menu items are hard-coded in:
        // - /resources/views/components/millennium-start-menu.blade.php
        //
        // ❌ DO NOT SEED:
        // - windows_start_menu_items_admin
        // - windows_start_menu_items_user
        // - windows_start_menu_items_seller
        // - windows_taskbar_apps
        // - windows_system_tray_icons
        //
        // ✅ ONLY SEED:
        // - Visual customization settings (colors, sizes, positions)
        // - Theme settings
        // - RGB effects
        // - UI preferences
        //
        // 📝 Reason: Changed from hybrid approach to hard-coded menus for:
        // - Consistency across all environments
        // - Easier maintenance and updates
        // - No database sync issues
        // - All 53 new menu items are now available by default
        //
        // ========================================

        $settings = [
            // Taskbar Settings
            'windows_taskbar_position' => ['value' => 'top', 'type' => 'string'],
            'windows_taskbar_height' => ['value' => 60, 'type' => 'integer'],
            'windows_taskbar_blur' => ['value' => true, 'type' => 'boolean'],
            'windows_taskbar_transparency' => ['value' => 95, 'type' => 'integer'],

            // Taskbar Color Settings
            'windows_taskbar_bg_color' => ['value' => '#1e293b', 'type' => 'color'],
            'windows_taskbar_text_color' => ['value' => '#ffffff', 'type' => 'color'],
            'windows_taskbar_hover_bg_color' => ['value' => '#334155', 'type' => 'color'],
            'windows_taskbar_active_bg_color' => ['value' => '#475569', 'type' => 'color'],
            'windows_taskbar_border_color' => ['value' => '#475569', 'type' => 'color'],
            'windows_taskbar_use_gradient' => ['value' => false, 'type' => 'boolean'],
            'windows_taskbar_gradient_from' => ['value' => '#1e293b', 'type' => 'color'],
            'windows_taskbar_gradient_to' => ['value' => '#0f172a', 'type' => 'color'],

            // Start Button Settings
            'windows_start_button_text' => ['value' => 'เริ่ม', 'type' => 'string'],
            'windows_start_button_use_logo' => ['value' => true, 'type' => 'boolean'],
            'windows_start_button_position' => ['value' => 'center', 'type' => 'string'],

            // Back Button Settings
            'millennium_back_button_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_back_button_text' => ['value' => 'กลับ', 'type' => 'string'],

            // Center Section Settings
            'millennium_center_section_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_center_section_text' => ['value' => 'Thai Prompt Affiliate', 'type' => 'string'],

            // RGB Settings
            'millennium_rgb_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_rgb_speed' => ['value' => 5, 'type' => 'integer'],

            // Millennium Start Menu Settings
            'millennium_menu_position' => ['value' => 'center', 'type' => 'string'],
            'millennium_menu_width' => ['value' => '400', 'type' => 'string'],
            'millennium_menu_width_unit' => ['value' => 'px', 'type' => 'string'],
            'millennium_menu_max_height' => ['value' => '600', 'type' => 'string'],
            'millennium_menu_max_height_unit' => ['value' => 'px', 'type' => 'string'],

            // Menu Logo Settings
            'millennium_menu_logo' => ['value' => null, 'type' => 'string'], // Custom logo path (falls back to main logo if null)
            'millennium_menu_show_logo' => ['value' => true, 'type' => 'boolean'], // Show/hide logo
            'millennium_menu_logo_size' => ['value' => 40, 'type' => 'integer'], // Logo size in px (20-100)

            // Menu Text Settings
            'millennium_menu_show_app_name' => ['value' => true, 'type' => 'boolean'], // Show/hide app name
            'millennium_menu_show_subtitle' => ['value' => true, 'type' => 'boolean'], // Show/hide subtitle
            'millennium_menu_app_name' => ['value' => null, 'type' => 'string'], // Custom app name (falls back to main app name)
            'millennium_menu_subtitle' => ['value' => null, 'type' => 'string'], // Custom subtitle (falls back to "{role} Dashboard")

            // Menu Appearance
            'millennium_menu_item_spacing' => ['value' => 8, 'type' => 'integer'], // Gap between menu items in px
            'millennium_menu_padding' => ['value' => 12, 'type' => 'integer'], // Padding inside each menu item in px
            'millennium_menu_item_height' => ['value' => null, 'type' => 'integer'], // Main menu item height in px (null = auto)
            'millennium_menu_subitem_height' => ['value' => null, 'type' => 'integer'], // Submenu item height in px (null = auto)

            // Menu Colors (gradient และสี)
            'millennium_menu_use_gradient' => ['value' => true, 'type' => 'boolean'], // Use gradient or solid color
            'millennium_menu_gradient_from' => ['value' => '#9333ea', 'type' => 'color'], // Menu gradient start color
            'millennium_menu_gradient_to' => ['value' => '#db2777', 'type' => 'color'], // Menu gradient end color
            'millennium_menu_bg_color' => ['value' => '#9333ea', 'type' => 'color'], // Solid background color (if gradient disabled)
            'millennium_menu_text_color' => ['value' => '#ffffff', 'type' => 'color'], // Menu text color
            'millennium_menu_border_color' => ['value' => '#9333ea', 'type' => 'color'], // Menu border color

            // Menu RGB Effects
            'millennium_menu_rgb_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_menu_item_hover_rgb' => ['value' => true, 'type' => 'boolean'],
            'millennium_menu_rgb_speed' => ['value' => 5, 'type' => 'integer'],
            'millennium_menu_rgb_border_width' => ['value' => 2, 'type' => 'integer'],
            'millennium_menu_rgb_glow_size' => ['value' => 15, 'type' => 'integer'],

            // Responsive Taskbar Settings
            'millennium_taskbar_collapse_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_taskbar_collapse_breakpoint' => ['value' => 768, 'type' => 'integer'],

            // Clock Settings
            'millennium_clock_style' => ['value' => 'digital', 'type' => 'string'],
            'millennium_clock_format' => ['value' => '24h', 'type' => 'string'],
            'millennium_clock_show_seconds' => ['value' => false, 'type' => 'boolean'],
            'millennium_clock_show_date' => ['value' => false, 'type' => 'boolean'],
            'millennium_clock_date_format' => ['value' => 'short', 'type' => 'string'],

            // RGB Settings
            'windows_rgb_enabled' => ['value' => true, 'type' => 'boolean'],
            'windows_rgb_speed' => ['value' => 3, 'type' => 'integer'],
            'windows_rgb_glow' => ['value' => true, 'type' => 'boolean'],
            'windows_rgb_colors' => ['value' => ['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00'], 'type' => 'json'],

            // Theme Settings
            'windows_theme_mode' => ['value' => 'auto', 'type' => 'string'],
            'windows_accent_color' => ['value' => '#667eea', 'type' => 'color'],

            // Spaceship Theme
            'windows_spaceship_theme' => ['value' => true, 'type' => 'boolean'],
            'windows_spaceship_stars' => ['value' => true, 'type' => 'boolean'],

            // System Tray Info
            'windows_license_text' => ['value' => 'Licensed to Thai Prompt', 'type' => 'string'],
            'windows_copyright_text' => ['value' => '© 2025 TP-Affiliate Platform', 'type' => 'string'],

            // Content Width Settings
            'content_width_mode' => ['value' => 'max', 'type' => 'string'],
            'content_width_custom' => ['value' => 1400, 'type' => 'integer'],
        ];

        return $settings;
    }
}
