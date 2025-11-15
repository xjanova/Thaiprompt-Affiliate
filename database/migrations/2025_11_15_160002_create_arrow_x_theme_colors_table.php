<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง theme_colors สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('theme_colors')) {
            return;
        }

        Schema::create('theme_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_setting_id')->constrained('theme_settings')->onDelete('cascade');

            // Color Scheme Name
            $table->string('scheme_name', 100)->default('Arrow X Default');
            $table->boolean('is_default')->default(true);

            // Primary Gradient Colors (3 colors for gradient)
            $table->string('primary_start', 7)->default('#9333EA')->comment('Purple-600');
            $table->string('primary_middle', 7)->default('#EC4899')->comment('Pink-500');
            $table->string('primary_end', 7)->default('#F97316')->comment('Orange-500');

            // Secondary Gradient Colors
            $table->string('secondary_start', 7)->default('#3B82F6')->comment('Blue-500');
            $table->string('secondary_middle', 7)->default('#06B6D4')->comment('Cyan-500');
            $table->string('secondary_end', 7)->default('#14B8A6')->comment('Teal-500');

            // Accent Colors
            $table->string('accent_color', 7)->default('#F59E0B')->comment('Amber-500');
            $table->string('success_color', 7)->default('#10B981')->comment('Green-500');
            $table->string('warning_color', 7)->default('#F59E0B')->comment('Amber-500');
            $table->string('error_color', 7)->default('#EF4444')->comment('Red-500');
            $table->string('info_color', 7)->default('#3B82F6')->comment('Blue-500');

            // Background Colors (Light Mode)
            $table->string('background_primary', 7)->default('#FFFFFF')->comment('White');
            $table->string('background_secondary', 7)->default('#F9FAFB')->comment('Gray-50');
            $table->string('background_tertiary', 7)->default('#F3F4F6')->comment('Gray-100');

            // Background Colors (Dark Mode)
            $table->string('dark_background_primary', 7)->default('#111827')->comment('Gray-900');
            $table->string('dark_background_secondary', 7)->default('#1F2937')->comment('Gray-800');
            $table->string('dark_background_tertiary', 7)->default('#374151')->comment('Gray-700');

            // Text Colors (Light Mode)
            $table->string('text_primary', 7)->default('#111827')->comment('Gray-900');
            $table->string('text_secondary', 7)->default('#6B7280')->comment('Gray-500');
            $table->string('text_tertiary', 7)->default('#9CA3AF')->comment('Gray-400');

            // Text Colors (Dark Mode)
            $table->string('dark_text_primary', 7)->default('#F9FAFB')->comment('Gray-50');
            $table->string('dark_text_secondary', 7)->default('#D1D5DB')->comment('Gray-300');
            $table->string('dark_text_tertiary', 7)->default('#9CA3AF')->comment('Gray-400');

            // Border & Divider Colors (Light Mode)
            $table->string('border_color', 7)->default('#E5E7EB')->comment('Gray-200');
            $table->string('divider_color', 7)->default('#E5E7EB')->comment('Gray-200');

            // Border & Divider Colors (Dark Mode)
            $table->string('dark_border_color', 7)->default('#374151')->comment('Gray-700');
            $table->string('dark_divider_color', 7)->default('#374151')->comment('Gray-700');

            // Gradient Direction
            $table->enum('gradient_direction', [
                'to-right', 'to-left', 'to-top', 'to-bottom',
                'to-top-right', 'to-top-left', 'to-bottom-right', 'to-bottom-left'
            ])->default('to-right');

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->index('theme_setting_id');
            $table->index('is_default');
        });
    }

    /**
     * ลบตาราง theme_colors
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_colors');
    }
};
