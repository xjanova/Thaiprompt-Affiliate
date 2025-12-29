<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง app_theme_settings
     *
     * หมายเหตุ: ตารางนี้ถูกปิดการใช้งานแล้ว เนื่องจากบังคับใช้ Arrow X Theme เท่านั้น
     * แต่ยังคงสร้างไว้เพื่อความเข้ากันได้ย้อนหลัง (backward compatibility)
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('app_theme_settings')) {
            return;
        }

        // ⚠️ ตารางนี้มี columns เยอะมาก - ใช้ JSON เก็บ theme data แทน
        // เพื่อหลีกเลี่ยง "Row size too large" error
        Schema::create('app_theme_settings', function (Blueprint $table) {
            $table->id();

            // Theme Identity
            $table->string('theme_name')->default('default');

            // เก็บ settings ทั้งหมดใน JSON columns เพื่อลดจำนวน columns
            $table->json('logo_settings')->nullable(); // logo_url, logo_dark_url, favicon_url, app_icon_url, splash_screen_url, splash_screen_dark_url
            $table->json('color_settings')->nullable(); // primary, secondary, background, text, status, link, border colors
            $table->json('typography_settings')->nullable(); // font_family, font_sizes, headings, body, caption, button text
            $table->json('layout_settings')->nullable(); // border_radius, spacing, shadows, icons, z-index tokens
            $table->json('animation_settings')->nullable(); // animation_duration, animation_curve, transitions

            // Dark Mode Settings
            $table->boolean('dark_mode_enabled')->default(true);
            $table->boolean('dark_mode_default')->default(false);

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง app_theme_settings
     */
    public function down(): void
    {
        Schema::dropIfExists('app_theme_settings');
    }
};
