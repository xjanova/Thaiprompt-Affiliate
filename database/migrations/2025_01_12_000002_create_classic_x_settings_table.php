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
        Schema::create('classic_x_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, color
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default Classic X theme settings
        $defaultSettings = [
            // Sidebar Settings
            ['key' => 'classic_x_sidebar_width', 'value' => '280', 'type' => 'integer', 'description' => 'Width of the sidebar in pixels'],
            ['key' => 'classic_x_sidebar_collapsed_width', 'value' => '64', 'type' => 'integer', 'description' => 'Width when collapsed'],
            ['key' => 'classic_x_sidebar_position', 'value' => 'left', 'type' => 'string', 'description' => 'Sidebar position: left or right'],
            ['key' => 'classic_x_sidebar_collapsible', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow sidebar to collapse'],

            // Color Settings
            ['key' => 'classic_x_primary_color', 'value' => '#2271b1', 'type' => 'color', 'description' => 'Primary brand color'],
            ['key' => 'classic_x_sidebar_bg', 'value' => '#1d2327', 'type' => 'color', 'description' => 'Sidebar background color'],
            ['key' => 'classic_x_sidebar_text', 'value' => '#ffffff', 'type' => 'color', 'description' => 'Sidebar text color'],
            ['key' => 'classic_x_sidebar_hover_bg', 'value' => '#2c3338', 'type' => 'color', 'description' => 'Hover background color'],
            ['key' => 'classic_x_sidebar_active_bg', 'value' => '#0073aa', 'type' => 'color', 'description' => 'Active menu item background'],
            ['key' => 'classic_x_sidebar_active_text', 'value' => '#ffffff', 'type' => 'color', 'description' => 'Active menu item text'],
            ['key' => 'classic_x_submenu_bg', 'value' => '#23282d', 'type' => 'color', 'description' => 'Submenu background color'],

            // Typography
            ['key' => 'classic_x_font_family', 'value' => '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif', 'type' => 'string', 'description' => 'Font family'],
            ['key' => 'classic_x_menu_font_size', 'value' => '14', 'type' => 'integer', 'description' => 'Menu font size in pixels'],
            ['key' => 'classic_x_submenu_font_size', 'value' => '13', 'type' => 'integer', 'description' => 'Submenu font size'],

            // Visual Effects
            ['key' => 'classic_x_enable_shadows', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable shadow effects'],
            ['key' => 'classic_x_enable_gradients', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable gradient backgrounds'],
            ['key' => 'classic_x_enable_animations', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable smooth animations'],
            ['key' => 'classic_x_shadow_intensity', 'value' => 'medium', 'type' => 'string', 'description' => 'Shadow intensity: light, medium, strong'],
            ['key' => 'classic_x_animation_speed', 'value' => '300', 'type' => 'integer', 'description' => 'Animation speed in milliseconds'],

            // Menu Behavior
            ['key' => 'classic_x_auto_collapse_mobile', 'value' => 'true', 'type' => 'boolean', 'description' => 'Auto collapse on mobile'],
            ['key' => 'classic_x_mobile_breakpoint', 'value' => '768', 'type' => 'integer', 'description' => 'Mobile breakpoint in pixels'],
            ['key' => 'classic_x_sticky_sidebar', 'value' => 'true', 'type' => 'boolean', 'description' => 'Make sidebar sticky'],
            ['key' => 'classic_x_submenu_indicator', 'value' => 'chevron', 'type' => 'string', 'description' => 'Submenu indicator: chevron, plus, arrow'],

            // Advanced
            ['key' => 'classic_x_enable_3d_depth', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable 3D depth effects'],
            ['key' => 'classic_x_depth_intensity', 'value' => 'medium', 'type' => 'string', 'description' => 'Depth effect intensity'],
            ['key' => 'classic_x_enable_blur', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable backdrop blur'],
            ['key' => 'classic_x_blur_amount', 'value' => '10', 'type' => 'integer', 'description' => 'Blur amount in pixels'],

            // Logo Settings
            ['key' => 'classic_x_show_logo', 'value' => 'true', 'type' => 'boolean', 'description' => 'Show logo in sidebar'],
            ['key' => 'classic_x_logo_height', 'value' => '48', 'type' => 'integer', 'description' => 'Logo height in pixels'],
            ['key' => 'classic_x_logo_padding', 'value' => '16', 'type' => 'integer', 'description' => 'Logo padding'],

            // Badge Settings
            ['key' => 'classic_x_enable_badges', 'value' => 'true', 'type' => 'boolean', 'description' => 'Show notification badges'],
            ['key' => 'classic_x_badge_style', 'value' => 'rounded', 'type' => 'string', 'description' => 'Badge style: rounded, square, pill'],
            ['key' => 'classic_x_badge_color', 'value' => '#d63638', 'type' => 'color', 'description' => 'Badge background color'],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('classic_x_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classic_x_settings');
    }
};
