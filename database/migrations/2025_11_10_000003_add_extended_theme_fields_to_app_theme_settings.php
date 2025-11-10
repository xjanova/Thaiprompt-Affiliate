<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_theme_settings', function (Blueprint $table) {
            // Typography Scale (Heading Styles)
            if (!Schema::hasColumn('app_theme_settings', 'heading1_size')) {
                $table->integer('heading1_size')->default(32)->after('font_size_xlarge');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading1_weight')) {
                $table->string('heading1_weight')->default('bold')->after('heading1_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading1_line_height')) {
                $table->decimal('heading1_line_height', 4, 2)->default(1.25)->after('heading1_weight');
            }

            if (!Schema::hasColumn('app_theme_settings', 'heading2_size')) {
                $table->integer('heading2_size')->default(28)->after('heading1_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading2_weight')) {
                $table->string('heading2_weight')->default('bold')->after('heading2_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading2_line_height')) {
                $table->decimal('heading2_line_height', 4, 2)->default(1.30)->after('heading2_weight');
            }

            if (!Schema::hasColumn('app_theme_settings', 'heading3_size')) {
                $table->integer('heading3_size')->default(24)->after('heading2_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading3_weight')) {
                $table->string('heading3_weight')->default('600')->after('heading3_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading3_line_height')) {
                $table->decimal('heading3_line_height', 4, 2)->default(1.35)->after('heading3_weight');
            }

            if (!Schema::hasColumn('app_theme_settings', 'heading4_size')) {
                $table->integer('heading4_size')->default(20)->after('heading3_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading4_weight')) {
                $table->string('heading4_weight')->default('600')->after('heading4_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'heading4_line_height')) {
                $table->decimal('heading4_line_height', 4, 2)->default(1.40)->after('heading4_weight');
            }

            // Body & Caption Typography
            if (!Schema::hasColumn('app_theme_settings', 'body_size')) {
                $table->integer('body_size')->default(16)->after('heading4_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'body_weight')) {
                $table->string('body_weight')->default('normal')->after('body_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'body_line_height')) {
                $table->decimal('body_line_height', 4, 2)->default(1.50)->after('body_weight');
            }

            if (!Schema::hasColumn('app_theme_settings', 'body_small_size')) {
                $table->integer('body_small_size')->default(14)->after('body_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'body_small_weight')) {
                $table->string('body_small_weight')->default('normal')->after('body_small_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'body_small_line_height')) {
                $table->decimal('body_small_line_height', 4, 2)->default(1.45)->after('body_small_weight');
            }

            if (!Schema::hasColumn('app_theme_settings', 'caption_size')) {
                $table->integer('caption_size')->default(12)->after('body_small_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'caption_weight')) {
                $table->string('caption_weight')->default('normal')->after('caption_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'caption_line_height')) {
                $table->decimal('caption_line_height', 4, 2)->default(1.35)->after('caption_weight');
            }

            // Button Typography
            if (!Schema::hasColumn('app_theme_settings', 'button_text_size')) {
                $table->integer('button_text_size')->default(16)->after('caption_line_height');
            }
            if (!Schema::hasColumn('app_theme_settings', 'button_text_weight')) {
                $table->string('button_text_weight')->default('600')->after('button_text_size');
            }
            if (!Schema::hasColumn('app_theme_settings', 'button_text_transform')) {
                $table->string('button_text_transform')->nullable()->after('button_text_weight'); // uppercase, lowercase, capitalize
            }

            // Spacing Scale (Design Tokens)
            if (!Schema::hasColumn('app_theme_settings', 'spacing_tokens')) {
                $table->json('spacing_tokens')->nullable()->after('spacing_xlarge'); // {xs: 4, sm: 8, md: 16, lg: 24, xl: 32, 2xl: 48, 3xl: 64}
            }

            // Color Variants - Button States
            if (!Schema::hasColumn('app_theme_settings', 'button_primary_hover')) {
                $table->string('button_primary_hover')->nullable()->after('primary_light_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'button_primary_active')) {
                $table->string('button_primary_active')->nullable()->after('button_primary_hover');
            }
            if (!Schema::hasColumn('app_theme_settings', 'button_primary_disabled')) {
                $table->string('button_primary_disabled')->nullable()->after('button_primary_active');
            }

            if (!Schema::hasColumn('app_theme_settings', 'button_secondary_hover')) {
                $table->string('button_secondary_hover')->nullable()->after('secondary_light_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'button_secondary_active')) {
                $table->string('button_secondary_active')->nullable()->after('button_secondary_hover');
            }
            if (!Schema::hasColumn('app_theme_settings', 'button_secondary_disabled')) {
                $table->string('button_secondary_disabled')->nullable()->after('button_secondary_active');
            }

            // Input States
            if (!Schema::hasColumn('app_theme_settings', 'input_background')) {
                $table->string('input_background')->nullable()->after('background_dark_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_background_dark')) {
                $table->string('input_background_dark')->nullable()->after('input_background');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_border')) {
                $table->string('input_border')->nullable()->after('border_dark_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_border_dark')) {
                $table->string('input_border_dark')->nullable()->after('input_border');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_border_focus')) {
                $table->string('input_border_focus')->nullable()->after('input_border_dark');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_border_error')) {
                $table->string('input_border_error')->nullable()->after('input_border_focus');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_text')) {
                $table->string('input_text')->nullable()->after('text_secondary_dark_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_text_dark')) {
                $table->string('input_text_dark')->nullable()->after('input_text');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_placeholder')) {
                $table->string('input_placeholder')->nullable()->after('input_text_dark');
            }
            if (!Schema::hasColumn('app_theme_settings', 'input_placeholder_dark')) {
                $table->string('input_placeholder_dark')->nullable()->after('input_placeholder');
            }

            // Link Colors
            if (!Schema::hasColumn('app_theme_settings', 'link_color')) {
                $table->string('link_color')->nullable()->after('info_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'link_dark_color')) {
                $table->string('link_dark_color')->nullable()->after('link_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'link_hover_color')) {
                $table->string('link_hover_color')->nullable()->after('link_dark_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'link_hover_dark_color')) {
                $table->string('link_hover_dark_color')->nullable()->after('link_hover_color');
            }

            // Card & Container Colors
            if (!Schema::hasColumn('app_theme_settings', 'card_background')) {
                $table->string('card_background')->nullable()->after('surface_dark_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'card_background_dark')) {
                $table->string('card_background_dark')->nullable()->after('card_background');
            }
            if (!Schema::hasColumn('app_theme_settings', 'card_border')) {
                $table->string('card_border')->nullable()->after('card_background_dark');
            }
            if (!Schema::hasColumn('app_theme_settings', 'card_border_dark')) {
                $table->string('card_border_dark')->nullable()->after('card_border');
            }

            // Overlay & Modal
            if (!Schema::hasColumn('app_theme_settings', 'overlay_color')) {
                $table->string('overlay_color')->nullable()->after('card_border_dark');
            }
            if (!Schema::hasColumn('app_theme_settings', 'overlay_opacity')) {
                $table->decimal('overlay_opacity', 3, 2)->default(0.50)->after('overlay_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'modal_background')) {
                $table->string('modal_background')->nullable()->after('overlay_opacity');
            }
            if (!Schema::hasColumn('app_theme_settings', 'modal_background_dark')) {
                $table->string('modal_background_dark')->nullable()->after('modal_background');
            }

            // Status Badge Colors
            if (!Schema::hasColumn('app_theme_settings', 'badge_success')) {
                $table->string('badge_success')->nullable()->after('success_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'badge_warning')) {
                $table->string('badge_warning')->nullable()->after('warning_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'badge_error')) {
                $table->string('badge_error')->nullable()->after('error_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'badge_info')) {
                $table->string('badge_info')->nullable()->after('info_color');
            }
            if (!Schema::hasColumn('app_theme_settings', 'badge_neutral')) {
                $table->string('badge_neutral')->nullable()->after('badge_info');
            }

            // Shadow Tokens
            if (!Schema::hasColumn('app_theme_settings', 'shadow_tokens')) {
                $table->json('shadow_tokens')->nullable()->after('badge_neutral');
                // {small: {elevation: 2, ...}, medium: {elevation: 4, ...}, large: {elevation: 8, ...}}
            }

            // Border Radius Tokens (Extended)
            if (!Schema::hasColumn('app_theme_settings', 'border_radius_tokens')) {
                $table->json('border_radius_tokens')->nullable()->after('border_radius_xlarge');
                // {xs: 2, sm: 4, md: 8, lg: 16, xl: 24, 2xl: 32, full: 9999}
            }

            // Icon Sizes
            if (!Schema::hasColumn('app_theme_settings', 'icon_size_small')) {
                $table->integer('icon_size_small')->default(16)->after('border_radius_tokens');
            }
            if (!Schema::hasColumn('app_theme_settings', 'icon_size_medium')) {
                $table->integer('icon_size_medium')->default(24)->after('icon_size_small');
            }
            if (!Schema::hasColumn('app_theme_settings', 'icon_size_large')) {
                $table->integer('icon_size_large')->default(32)->after('icon_size_medium');
            }
            if (!Schema::hasColumn('app_theme_settings', 'icon_size_xlarge')) {
                $table->integer('icon_size_xlarge')->default(48)->after('icon_size_large');
            }

            // Transition Durations
            if (!Schema::hasColumn('app_theme_settings', 'transition_fast')) {
                $table->integer('transition_fast')->default(150)->after('animation_duration'); // ms
            }
            if (!Schema::hasColumn('app_theme_settings', 'transition_base')) {
                $table->integer('transition_base')->default(300)->after('transition_fast');
            }
            if (!Schema::hasColumn('app_theme_settings', 'transition_slow')) {
                $table->integer('transition_slow')->default(500)->after('transition_base');
            }

            // Z-Index Scale
            if (!Schema::hasColumn('app_theme_settings', 'z_index_tokens')) {
                $table->json('z_index_tokens')->nullable()->after('transition_slow');
                // {dropdown: 1000, sticky: 1020, fixed: 1030, modal_backdrop: 1040, modal: 1050, popover: 1060, tooltip: 1070}
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_theme_settings', function (Blueprint $table) {
            $table->dropColumn([
                // Typography Scale
                'heading1_size', 'heading1_weight', 'heading1_line_height',
                'heading2_size', 'heading2_weight', 'heading2_line_height',
                'heading3_size', 'heading3_weight', 'heading3_line_height',
                'heading4_size', 'heading4_weight', 'heading4_line_height',
                'body_size', 'body_weight', 'body_line_height',
                'body_small_size', 'body_small_weight', 'body_small_line_height',
                'caption_size', 'caption_weight', 'caption_line_height',
                'button_text_size', 'button_text_weight', 'button_text_transform',

                // Spacing & Border Radius Tokens
                'spacing_tokens', 'border_radius_tokens',

                // Color Variants
                'button_primary_hover', 'button_primary_active', 'button_primary_disabled',
                'button_secondary_hover', 'button_secondary_active', 'button_secondary_disabled',
                'input_background', 'input_background_dark',
                'input_border', 'input_border_dark', 'input_border_focus', 'input_border_error',
                'input_text', 'input_text_dark', 'input_placeholder', 'input_placeholder_dark',
                'link_color', 'link_dark_color', 'link_hover_color', 'link_hover_dark_color',
                'card_background', 'card_background_dark', 'card_border', 'card_border_dark',
                'overlay_color', 'overlay_opacity', 'modal_background', 'modal_background_dark',
                'badge_success', 'badge_warning', 'badge_error', 'badge_info', 'badge_neutral',

                // Shadow & Icon Tokens
                'shadow_tokens',
                'icon_size_small', 'icon_size_medium', 'icon_size_large', 'icon_size_xlarge',

                // Transitions & Z-Index
                'transition_fast', 'transition_base', 'transition_slow',
                'z_index_tokens',
            ]);
        });
    }
};
