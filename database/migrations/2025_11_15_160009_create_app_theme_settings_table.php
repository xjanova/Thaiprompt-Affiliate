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
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('app_theme_settings')) {
            return;
        }

        Schema::create('app_theme_settings', function (Blueprint $table) {
            $table->id();

            // Theme Identity
            $table->string('theme_name')->default('default');

            // Logo & Icons
            $table->string('logo_url')->nullable();
            $table->string('logo_dark_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('app_icon_url')->nullable();
            $table->string('splash_screen_url')->nullable();
            $table->string('splash_screen_dark_url')->nullable();

            // Primary Colors
            $table->string('primary_color', 50)->nullable();
            $table->string('primary_dark_color', 50)->nullable();
            $table->string('primary_light_color', 50)->nullable();
            $table->string('button_primary_hover', 50)->nullable();
            $table->string('button_primary_active', 50)->nullable();
            $table->string('button_primary_disabled', 50)->nullable();

            // Secondary Colors
            $table->string('secondary_color', 50)->nullable();
            $table->string('secondary_dark_color', 50)->nullable();
            $table->string('secondary_light_color', 50)->nullable();
            $table->string('button_secondary_hover', 50)->nullable();
            $table->string('button_secondary_active', 50)->nullable();
            $table->string('button_secondary_disabled', 50)->nullable();

            // Background Colors
            $table->string('background_color', 50)->nullable();
            $table->string('background_dark_color', 50)->nullable();
            $table->string('background_light_color', 50)->nullable();
            $table->string('input_background', 50)->nullable();
            $table->string('input_background_dark', 50)->nullable();
            $table->string('surface_color', 50)->nullable();
            $table->string('surface_dark_color', 50)->nullable();
            $table->string('card_background', 50)->nullable();
            $table->string('card_background_dark', 50)->nullable();
            $table->string('card_border', 50)->nullable();
            $table->string('card_border_dark', 50)->nullable();

            // Text Colors
            $table->string('text_primary_color', 50)->nullable();
            $table->string('text_primary_dark_color', 50)->nullable();
            $table->string('text_secondary_color', 50)->nullable();
            $table->string('text_secondary_dark_color', 50)->nullable();
            $table->string('input_text', 50)->nullable();
            $table->string('input_text_dark', 50)->nullable();
            $table->string('input_placeholder', 50)->nullable();
            $table->string('input_placeholder_dark', 50)->nullable();

            // Status Colors
            $table->string('success_color', 50)->nullable();
            $table->string('success_dark_color', 50)->nullable();
            $table->string('badge_success', 50)->nullable();
            $table->string('warning_color', 50)->nullable();
            $table->string('warning_dark_color', 50)->nullable();
            $table->string('badge_warning', 50)->nullable();
            $table->string('error_color', 50)->nullable();
            $table->string('error_dark_color', 50)->nullable();
            $table->string('badge_error', 50)->nullable();
            $table->string('info_color', 50)->nullable();
            $table->string('info_dark_color', 50)->nullable();
            $table->string('badge_info', 50)->nullable();
            $table->string('badge_neutral', 50)->nullable();

            // Link Colors
            $table->string('link_color', 50)->nullable();
            $table->string('link_dark_color', 50)->nullable();
            $table->string('link_hover_color', 50)->nullable();
            $table->string('link_hover_dark_color', 50)->nullable();

            // Border & Divider Colors
            $table->string('border_color', 50)->nullable();
            $table->string('border_dark_color', 50)->nullable();
            $table->string('border_light_color', 50)->nullable();
            $table->string('input_border', 50)->nullable();
            $table->string('input_border_dark', 50)->nullable();
            $table->string('input_border_focus', 50)->nullable();
            $table->string('input_border_error', 50)->nullable();
            $table->string('divider_color', 50)->nullable();
            $table->string('divider_dark_color', 50)->nullable();

            // Overlay & Modal
            $table->string('overlay_color', 50)->nullable();
            $table->decimal('overlay_opacity', 3, 2)->nullable();
            $table->string('modal_background', 50)->nullable();
            $table->string('modal_background_dark', 50)->nullable();

            // Typography - Font Family
            $table->string('font_family')->nullable();
            $table->string('font_family_en')->nullable();

            // Typography - Font Sizes
            $table->integer('font_size_base')->nullable();
            $table->integer('font_size_small')->nullable();
            $table->integer('font_size_large')->nullable();
            $table->integer('font_size_xlarge')->nullable();

            // Typography - Headings
            $table->integer('heading1_size')->nullable();
            $table->integer('heading1_weight')->nullable();
            $table->decimal('heading1_line_height', 3, 2)->nullable();
            $table->integer('heading2_size')->nullable();
            $table->integer('heading2_weight')->nullable();
            $table->decimal('heading2_line_height', 3, 2)->nullable();
            $table->integer('heading3_size')->nullable();
            $table->integer('heading3_weight')->nullable();
            $table->decimal('heading3_line_height', 3, 2)->nullable();
            $table->integer('heading4_size')->nullable();
            $table->integer('heading4_weight')->nullable();
            $table->decimal('heading4_line_height', 3, 2)->nullable();

            // Typography - Body & Caption
            $table->integer('body_size')->nullable();
            $table->integer('body_weight')->nullable();
            $table->decimal('body_line_height', 3, 2)->nullable();
            $table->integer('body_small_size')->nullable();
            $table->integer('body_small_weight')->nullable();
            $table->decimal('body_small_line_height', 3, 2)->nullable();
            $table->integer('caption_size')->nullable();
            $table->integer('caption_weight')->nullable();
            $table->decimal('caption_line_height', 3, 2)->nullable();

            // Typography - Button
            $table->integer('button_text_size')->nullable();
            $table->integer('button_text_weight')->nullable();
            $table->string('button_text_transform', 50)->nullable();

            // Border Radius
            $table->integer('border_radius_small')->nullable();
            $table->integer('border_radius_medium')->nullable();
            $table->integer('border_radius_large')->nullable();
            $table->integer('border_radius_xlarge')->nullable();
            $table->json('border_radius_tokens')->nullable();

            // Spacing
            $table->integer('spacing_small')->nullable();
            $table->integer('spacing_medium')->nullable();
            $table->integer('spacing_large')->nullable();
            $table->integer('spacing_xlarge')->nullable();
            $table->json('spacing_tokens')->nullable();

            // Shadows
            $table->json('shadow_tokens')->nullable();

            // Icons
            $table->integer('icon_size_small')->nullable();
            $table->integer('icon_size_medium')->nullable();
            $table->integer('icon_size_large')->nullable();
            $table->integer('icon_size_xlarge')->nullable();

            // Animations & Transitions
            $table->integer('animation_duration')->nullable();
            $table->string('animation_curve')->nullable();
            $table->integer('transition_fast')->nullable();
            $table->integer('transition_base')->nullable();
            $table->integer('transition_slow')->nullable();

            // Z-Index
            $table->json('z_index_tokens')->nullable();

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
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('app_theme_settings');
    }
};
