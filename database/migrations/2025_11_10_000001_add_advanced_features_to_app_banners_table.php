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
        Schema::table('app_banners', function (Blueprint $table) {
            // Display Type: popup, banner, marquee, or combination
            $table->string('display_type')->default('banner')->after('type')
                ->comment('popup, banner, marquee, popup_and_banner, popup_and_marquee, banner_and_marquee, all');

            // Display Position: where to show the alert
            $table->string('display_position')->default('top')->after('display_type')
                ->comment('For banner: top, bottom. For popup: top-right, top-left, bottom-right, bottom-left, center');

            // Icon: emoji or icon class
            $table->string('icon')->nullable()->after('image_url_dark')
                ->comment('Emoji or icon class (e.g., fa-bell, 🔔)');

            // Size: small, medium, large, full
            $table->string('size')->default('medium')->after('icon')
                ->comment('small, medium, large, full');

            // Colors
            $table->string('background_color')->nullable()->after('size')
                ->comment('Custom background color (hex or rgb)');

            $table->string('text_color')->nullable()->after('background_color')
                ->comment('Custom text color (hex or rgb)');

            $table->string('border_color')->nullable()->after('text_color')
                ->comment('Custom border color (hex or rgb)');

            // Marquee Settings
            $table->integer('scroll_speed')->default(50)->after('border_color')
                ->comment('Scroll speed for marquee (1-100, higher is faster)');

            $table->string('scroll_direction')->default('left')->after('scroll_speed')
                ->comment('left, right, up, down');

            // Auto Dismiss
            $table->integer('auto_dismiss_seconds')->nullable()->after('scroll_direction')
                ->comment('Auto dismiss after X seconds (null = never)');

            // Sound
            $table->boolean('sound_enabled')->default(false)->after('auto_dismiss_seconds')
                ->comment('Play sound when alert appears');

            $table->string('sound_type')->nullable()->after('sound_enabled')
                ->comment('default, success, warning, error, custom');

            $table->string('sound_url')->nullable()->after('sound_type')
                ->comment('Custom sound file URL');

            // Animation
            $table->string('animation_style')->default('fade')->after('sound_url')
                ->comment('fade, slide, bounce, zoom, shake');

            $table->integer('animation_duration')->default(300)->after('animation_style')
                ->comment('Animation duration in milliseconds');

            // Priority (for ordering when multiple alerts)
            $table->integer('priority')->default(0)->after('sort_order')
                ->comment('Higher priority shows first (0-100)');

            // Show once per user
            $table->boolean('show_once')->default(false)->after('is_dismissible')
                ->comment('Show only once per user');

            // Require action (cannot dismiss until action taken)
            $table->boolean('require_action')->default(false)->after('show_once')
                ->comment('User must click action button to dismiss');

            // Full screen mode
            $table->boolean('fullscreen')->default(false)->after('require_action')
                ->comment('Show as fullscreen overlay');

            // Button customization
            $table->string('button_text')->nullable()->after('action_value')
                ->comment('Custom button text');

            $table->string('button_text_en')->nullable()->after('button_text')
                ->comment('Custom button text (English)');

            $table->string('button_color')->nullable()->after('button_text_en')
                ->comment('Custom button color');

            // Secondary button
            $table->string('secondary_button_text')->nullable()->after('button_color')
                ->comment('Secondary button text (e.g., "Cancel", "Later")');

            $table->string('secondary_button_text_en')->nullable()->after('secondary_button_text')
                ->comment('Secondary button text (English)');

            $table->string('secondary_button_action')->nullable()->after('secondary_button_text_en')
                ->comment('Secondary button action (url, route, dismiss)');

            // Add index for priority
            $table->index('priority');
            $table->index('display_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_banners', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropIndex(['display_type']);

            $table->dropColumn([
                'display_type',
                'display_position',
                'icon',
                'size',
                'background_color',
                'text_color',
                'border_color',
                'scroll_speed',
                'scroll_direction',
                'auto_dismiss_seconds',
                'sound_enabled',
                'sound_type',
                'sound_url',
                'animation_style',
                'animation_duration',
                'priority',
                'show_once',
                'require_action',
                'fullscreen',
                'button_text',
                'button_text_en',
                'button_color',
                'secondary_button_text',
                'secondary_button_text_en',
                'secondary_button_action',
            ]);
        });
    }
};
