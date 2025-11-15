<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง theme_rgb_effects สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('theme_rgb_effects')) {
            return;
        }

        Schema::create('theme_rgb_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_setting_id')->constrained('theme_settings')->onDelete('cascade');

            // Effect Info
            $table->string('effect_name', 100);
            $table->boolean('is_enabled')->default(true);

            // Target Element
            $table->enum('target_element', [
                'sidebar', 'navbar', 'menu-items', 'menu-items-active', 'cards', 'buttons', 'buttons-active',
                'links-active', 'headers', 'titles', 'badges', 'borders', 'backgrounds',
                'custom'
            ]);
            $table->string('custom_selector')->nullable()->comment('CSS selector if target=custom');

            // Trigger State (เมื่อไหร่จะแสดงเอฟเฟกต์)
            $table->enum('trigger_state', [
                'always', 'hover', 'active', 'focus', 'click'
            ])->default('always')->comment('always=แสดงตลอด, hover=เมื่อ hover, active=เมื่อ element เป็น active');

            // RGB Animation Settings
            $table->enum('animation_type', [
                'rainbow', 'wave', 'pulse', 'glow', 'breathing',
                'slide', 'rotate', 'flash', 'static'
            ])->default('rainbow');

            // Colors for RGB cycle
            $table->json('rgb_colors')->comment('["#FF0000", "#00FF00", "#0000FF", ...]');

            // Animation Speed
            $table->integer('animation_duration')->default(3000)->comment('milliseconds');
            $table->enum('animation_timing', ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'])->default('linear');

            // Effect Intensity
            $table->enum('intensity', ['subtle', 'medium', 'strong', 'extreme'])->default('medium');
            $table->integer('blur_radius')->default(10)->comment('px for glow effect');

            // Timing
            $table->integer('delay')->default(0)->comment('milliseconds delay before start');
            $table->string('iteration_count', 20)->default('infinite')->comment('infinite or number');

            // Position (z-index for layering)
            $table->integer('z_index')->default(0);

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->index('theme_setting_id');
            $table->index('target_element');
            $table->index('is_enabled');
            $table->index('trigger_state');
        });
    }

    /**
     * ลบตาราง theme_rgb_effects
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_rgb_effects');
    }
};
