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
        Schema::create('app_theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme_name')->default('default');

            // Logos & Icons
            $table->string('logo_url')->nullable();
            $table->string('logo_dark_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('app_icon_url')->nullable();
            $table->string('splash_screen_url')->nullable();
            $table->string('splash_screen_dark_url')->nullable();

            // Primary Colors
            $table->string('primary_color')->default('#3B82F6');
            $table->string('primary_dark_color')->default('#2563EB');
            $table->string('primary_light_color')->default('#60A5FA');

            // Secondary Colors
            $table->string('secondary_color')->default('#8B5CF6');
            $table->string('secondary_dark_color')->default('#7C3AED');
            $table->string('secondary_light_color')->default('#A78BFA');

            // Background Colors
            $table->string('background_color')->default('#FFFFFF');
            $table->string('background_dark_color')->default('#1F2937');
            $table->string('surface_color')->default('#F9FAFB');
            $table->string('surface_dark_color')->default('#374151');

            // Text Colors
            $table->string('text_primary_color')->default('#111827');
            $table->string('text_primary_dark_color')->default('#F9FAFB');
            $table->string('text_secondary_color')->default('#6B7280');
            $table->string('text_secondary_dark_color')->default('#D1D5DB');

            // Status Colors
            $table->string('success_color')->default('#10B981');
            $table->string('warning_color')->default('#F59E0B');
            $table->string('error_color')->default('#EF4444');
            $table->string('info_color')->default('#3B82F6');

            // Border & Divider
            $table->string('border_color')->default('#E5E7EB');
            $table->string('border_dark_color')->default('#4B5563');
            $table->string('divider_color')->default('#E5E7EB');
            $table->string('divider_dark_color')->default('#4B5563');

            // Fonts
            $table->string('font_family')->default('Noto Sans Thai');
            $table->string('font_family_en')->default('Inter');
            $table->integer('font_size_base')->default(14);
            $table->integer('font_size_small')->default(12);
            $table->integer('font_size_large')->default(16);
            $table->integer('font_size_xlarge')->default(20);

            // Border Radius
            $table->integer('border_radius_small')->default(4);
            $table->integer('border_radius_medium')->default(8);
            $table->integer('border_radius_large')->default(12);
            $table->integer('border_radius_xlarge')->default(16);

            // Spacing
            $table->integer('spacing_small')->default(8);
            $table->integer('spacing_medium')->default(16);
            $table->integer('spacing_large')->default(24);
            $table->integer('spacing_xlarge')->default(32);

            // Animation
            $table->integer('animation_duration')->default(300);
            $table->string('animation_curve')->default('ease-in-out');

            // Additional Settings
            $table->boolean('dark_mode_enabled')->default(true);
            $table->boolean('dark_mode_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_theme_settings');
    }
};
