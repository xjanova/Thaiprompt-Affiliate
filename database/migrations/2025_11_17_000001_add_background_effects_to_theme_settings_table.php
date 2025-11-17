<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์สำหรับ Background Effects และ Footer Logo
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนแก้ไข
        if (!Schema::hasTable('theme_settings')) {
            return;
        }

        Schema::table('theme_settings', function (Blueprint $table) {
            // Footer Logo (โลโก้มุมล่างซ้าย sidebar)
            if (!Schema::hasColumn('theme_settings', 'footer_logo_path')) {
                $table->string('footer_logo_path')->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('theme_settings', 'footer_logo_animation')) {
                $table->enum('footer_logo_animation', ['none', 'float', 'spin', 'bounce', 'pulse', 'swing'])
                      ->default('float')
                      ->comment('Footer Logo Animation Style');
            }

            // Background Effects - Enable/Disable
            if (!Schema::hasColumn('theme_settings', 'bg_effects_enabled')) {
                $table->boolean('bg_effects_enabled')->default(true)->after('rtl_enabled');
            }

            // Background Circle 1 (Cyan-Blue)
            if (!Schema::hasColumn('theme_settings', 'bg_circle1_color1')) {
                $table->string('bg_circle1_color1', 7)->default('#22d3ee')->comment('Circle 1 Start Color');
            }
            if (!Schema::hasColumn('theme_settings', 'bg_circle1_color2')) {
                $table->string('bg_circle1_color2', 7)->default('#2563eb')->comment('Circle 1 End Color');
            }

            // Background Circle 2 (Pink-Purple)
            if (!Schema::hasColumn('theme_settings', 'bg_circle2_color1')) {
                $table->string('bg_circle2_color1', 7)->default('#f472b6')->comment('Circle 2 Start Color');
            }
            if (!Schema::hasColumn('theme_settings', 'bg_circle2_color2')) {
                $table->string('bg_circle2_color2', 7)->default('#9333ea')->comment('Circle 2 End Color');
            }

            // Background Circle 3 (Yellow-Orange)
            if (!Schema::hasColumn('theme_settings', 'bg_circle3_color1')) {
                $table->string('bg_circle3_color1', 7)->default('#fbbf24')->comment('Circle 3 Start Color');
            }
            if (!Schema::hasColumn('theme_settings', 'bg_circle3_color2')) {
                $table->string('bg_circle3_color2', 7)->default('#f97316')->comment('Circle 3 End Color');
            }

            // Animation Settings
            if (!Schema::hasColumn('theme_settings', 'bg_animation_speed')) {
                $table->enum('bg_animation_speed', ['slow', 'normal', 'fast'])->default('normal')->comment('Animation Speed');
            }
            if (!Schema::hasColumn('theme_settings', 'bg_circle_opacity')) {
                $table->integer('bg_circle_opacity')->default(15)->comment('Circle Opacity (0-100)');
            }
            if (!Schema::hasColumn('theme_settings', 'bg_circle_blur')) {
                $table->integer('bg_circle_blur')->default(96)->comment('Circle Blur Intensity (0-200)');
            }
            if (!Schema::hasColumn('theme_settings', 'bg_circle_size')) {
                $table->integer('bg_circle_size')->default(384)->comment('Circle Size in px (200-800)');
            }
        });
    }

    /**
     * ย้อนกลับการเปลี่ยนแปลง
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('theme_settings')) {
            return;
        }

        Schema::table('theme_settings', function (Blueprint $table) {
            $columns = [
                'footer_logo_path',
                'footer_logo_animation',
                'bg_effects_enabled',
                'bg_circle1_color1',
                'bg_circle1_color2',
                'bg_circle2_color1',
                'bg_circle2_color2',
                'bg_circle3_color1',
                'bg_circle3_color2',
                'bg_animation_speed',
                'bg_circle_opacity',
                'bg_circle_blur',
                'bg_circle_size',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('theme_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
