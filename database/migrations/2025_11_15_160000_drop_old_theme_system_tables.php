<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลบ Old Theme System Tables (ก่อนเปลี่ยนมาใช้ Arrow X Theme System)
     *
     * Tables ที่ลบ:
     * - themes (old multi-theme system)
     * - user_themes (old user theme preferences)
     * - theme_presets (old theme presets)
     * - app_theme_settings (old app theme settings)
     *
     * Arrow X Theme System ใช้ tables ใหม่:
     * - theme_settings (Arrow X main settings)
     * - theme_colors (Arrow X color gradients)
     * - theme_rgb_effects (Arrow X RGB effects)
     * - theme_typography (Arrow X typography)
     * - theme_components (Arrow X component settings)
     * - translation_cache (Google Translate cache)
     * - google_translate_settings (Google Translate API settings)
     *
     * @return void
     */
    public function up(): void
    {
        // ลบ user_themes ก่อน (มี foreign key ไป themes)
        if (Schema::hasTable('user_themes')) {
            Schema::dropIfExists('user_themes');
        }

        // ลบ theme_presets
        if (Schema::hasTable('theme_presets')) {
            Schema::dropIfExists('theme_presets');
        }

        // ลบ themes table
        if (Schema::hasTable('themes')) {
            Schema::dropIfExists('themes');
        }

        // ลบ app_theme_settings table
        if (Schema::hasTable('app_theme_settings')) {
            Schema::dropIfExists('app_theme_settings');
        }
    }

    /**
     * Reverse migration - สร้าง tables กลับ (ไม่แนะนำ - ควรใช้ Arrow X แทน)
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่ reverse migration - ถ้าต้องการย้อนกลับให้ใช้ backup database
        // หรือ rollback ก่อน migrate ตัวนี้
        //
        // เหตุผล: Old theme system deprecated แล้ว, ควรใช้ Arrow X Theme System
    }
};
