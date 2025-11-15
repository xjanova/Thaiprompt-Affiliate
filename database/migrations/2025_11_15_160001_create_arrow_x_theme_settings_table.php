<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง theme_settings สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('theme_settings')) {
            return;
        }

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('theme_name', 100)->default('Arrow X');
            $table->string('theme_version', 20)->default('1.0.0');
            $table->boolean('is_active')->default(true);

            // Logo & Branding
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('brand_name', 100)->default('TP-Affiliate');
            $table->string('brand_tagline')->nullable();

            // Layout Settings
            $table->enum('layout_type', ['fixed', 'fluid', 'boxed'])->default('fluid');
            $table->integer('sidebar_width')->default(260)->comment('px');
            $table->integer('navbar_height')->default(64)->comment('px');
            $table->integer('footer_height')->default(80)->comment('px');

            // Transparency Settings (0-100)
            $table->integer('global_opacity')->default(100)->comment('0-100%');
            $table->integer('sidebar_opacity')->default(100);
            $table->integer('navbar_opacity')->default(100);
            $table->integer('card_opacity')->default(100);
            $table->integer('modal_opacity')->default(95);

            // Card Settings
            $table->integer('card_blur_intensity')->default(0)->comment('0-20px');
            $table->integer('card_border_width')->default(1)->comment('px');
            $table->integer('card_border_radius')->default(16)->comment('px');
            $table->enum('card_shadow_intensity', ['none', 'sm', 'md', 'lg', 'xl', '2xl'])->default('lg');

            // Language Settings
            $table->string('default_language', 5)->default('th')->comment('th, en, etc.');
            $table->json('available_languages')->default(json_encode(['th', 'en']));
            $table->boolean('rtl_enabled')->default(false);

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง theme_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
