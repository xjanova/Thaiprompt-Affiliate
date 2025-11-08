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
        Schema::create('windows_ui_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, color
            $table->timestamps();

            $table->index('key');
        });

        // Insert default Windows UI settings
        $this->insertDefaultSettings();
    }

    /**
     * Insert default Windows UI settings
     */
    private function insertDefaultSettings(): void
    {
        $defaults = [
            // Start Menu Items (JSON array of menu items from navigation)
            [
                'key' => 'windows_start_menu_items',
                'value' => json_encode([
                    ['icon' => '🏠', 'label' => 'หน้าแรก', 'url' => '/', 'order' => 1],
                    ['icon' => '🤖', 'label' => 'ตลาดบอท', 'url' => '/marketplace', 'order' => 2],
                    ['icon' => '🛍️', 'label' => 'ช๊อปปิ้ง', 'url' => '/shop', 'order' => 3],
                    ['icon' => 'ℹ️', 'label' => 'เกี่ยวกับเรา', 'url' => '/about-us', 'order' => 4],
                    ['icon' => '📚', 'label' => 'Platform Wiki', 'url' => '/platform-wiki', 'order' => 5],
                    ['icon' => '✉️', 'label' => 'ติดต่อเรา', 'url' => '/contact', 'order' => 6],
                ]),
                'type' => 'json',
            ],

            // Taskbar Apps (JSON array of quick access apps)
            [
                'key' => 'windows_taskbar_apps',
                'value' => json_encode([
                    ['icon' => '🛍️', 'label' => 'ช๊อปปิ้ง', 'url' => '/shop', 'order' => 1],
                    ['icon' => '🔮', 'label' => 'ดูไพ่ทาโร่', 'url' => '#tarot', 'order' => 2],
                ]),
                'type' => 'json',
            ],

            // System Tray Icons (JSON array of system icons)
            [
                'key' => 'windows_system_tray_icons',
                'value' => json_encode([
                    ['icon' => 'cart', 'label' => 'ตะกร้าสินค้า', 'url' => '/cart', 'requires_auth' => true, 'order' => 1],
                    ['icon' => 'user', 'label' => 'บัญชีของฉัน', 'url' => '/user/dashboard', 'requires_auth' => true, 'order' => 2],
                    ['icon' => 'login', 'label' => 'เข้าสู่ระบบ', 'url' => '/login', 'requires_guest' => true, 'order' => 3],
                ]),
                'type' => 'json',
            ],

            // RGB Animation Settings
            ['key' => 'windows_rgb_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'windows_rgb_colors', 'value' => json_encode(['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00']), 'type' => 'json'],
            ['key' => 'windows_rgb_speed', 'value' => '3', 'type' => 'integer'], // seconds for full cycle
            ['key' => 'windows_rgb_glow', 'value' => 'true', 'type' => 'boolean'],

            // Taskbar Settings
            ['key' => 'windows_taskbar_position', 'value' => 'top', 'type' => 'string'], // top or bottom
            ['key' => 'windows_taskbar_height', 'value' => '48', 'type' => 'integer'], // pixels
            ['key' => 'windows_taskbar_blur', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'windows_taskbar_transparency', 'value' => '95', 'type' => 'integer'], // 0-100

            // Start Button Settings
            ['key' => 'windows_start_button_text', 'value' => 'เริ่ม', 'type' => 'string'],
            ['key' => 'windows_start_button_use_logo', 'value' => 'true', 'type' => 'boolean'],

            // System Tray Info
            ['key' => 'windows_license_text', 'value' => 'Licensed', 'type' => 'string'],
            ['key' => 'windows_copyright_text', 'value' => '© 2025 TP-Affiliate', 'type' => 'string'],

            // Theme
            ['key' => 'windows_theme_mode', 'value' => 'auto', 'type' => 'string'], // auto, light, dark
            ['key' => 'windows_accent_color', 'value' => '#0078D4', 'type' => 'color'], // Windows blue

            // Spaceship Theme
            ['key' => 'windows_spaceship_theme', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'windows_spaceship_bg_image', 'value' => '', 'type' => 'string'], // URL to space background
            ['key' => 'windows_spaceship_stars', 'value' => 'true', 'type' => 'boolean'],
        ];

        foreach ($defaults as $setting) {
            DB::table('windows_ui_settings')->insert($setting + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('windows_ui_settings');
    }
};
