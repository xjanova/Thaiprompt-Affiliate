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
        Schema::create('component_settings', function (Blueprint $table) {
            $table->id();

            // Component Info
            $table->string('component_id')->unique(); // button_primary, input_field, card_container, etc.
            $table->string('component_name');
            $table->string('component_name_en')->nullable();
            $table->string('component_type'); // button, input, container, text, image, card, etc.
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();

            // Size
            $table->integer('min_height')->nullable(); // in dp/points
            $table->integer('max_height')->nullable();
            $table->integer('min_width')->nullable();
            $table->integer('max_width')->nullable();
            $table->integer('fixed_height')->nullable(); // ถ้ากำหนดความสูงแน่นอน
            $table->integer('fixed_width')->nullable(); // ถ้ากำหนดความกว้างแน่นอน

            // Spacing
            $table->integer('padding_top')->nullable();
            $table->integer('padding_bottom')->nullable();
            $table->integer('padding_left')->nullable();
            $table->integer('padding_right')->nullable();
            $table->integer('margin_top')->nullable();
            $table->integer('margin_bottom')->nullable();
            $table->integer('margin_left')->nullable();
            $table->integer('margin_right')->nullable();

            // Typography (สำหรับ text components)
            $table->integer('font_size')->nullable();
            $table->string('font_weight')->nullable(); // normal, bold, 100-900
            $table->decimal('line_height', 4, 2)->nullable(); // 1.00 - 3.00
            $table->string('text_align')->nullable(); // left, center, right, justify
            $table->string('text_decoration')->nullable(); // none, underline, line-through
            $table->string('text_transform')->nullable(); // none, uppercase, lowercase, capitalize

            // Colors
            $table->string('background_color')->nullable();
            $table->string('background_dark_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('text_dark_color')->nullable();
            $table->string('border_color')->nullable();
            $table->string('border_dark_color')->nullable();

            // Border & Shape
            $table->integer('border_width')->default(0);
            $table->integer('corner_radius')->nullable(); // border radius
            $table->integer('corner_radius_top_left')->nullable();
            $table->integer('corner_radius_top_right')->nullable();
            $table->integer('corner_radius_bottom_left')->nullable();
            $table->integer('corner_radius_bottom_right')->nullable();

            // Shadow & Elevation
            $table->integer('shadow_elevation')->default(0);
            $table->string('shadow_color')->nullable();
            $table->integer('shadow_offset_x')->nullable();
            $table->integer('shadow_offset_y')->nullable();
            $table->integer('shadow_blur_radius')->nullable();

            // Interaction States
            $table->string('hover_background_color')->nullable();
            $table->string('hover_text_color')->nullable();
            $table->string('hover_border_color')->nullable();
            $table->string('active_background_color')->nullable();
            $table->string('active_text_color')->nullable();
            $table->string('active_border_color')->nullable();
            $table->string('disabled_background_color')->nullable();
            $table->string('disabled_text_color')->nullable();
            $table->string('disabled_border_color')->nullable();
            $table->decimal('disabled_opacity', 3, 2)->default(0.50);

            // Focus States
            $table->string('focus_border_color')->nullable();
            $table->integer('focus_border_width')->nullable();
            $table->string('focus_shadow_color')->nullable();

            // Icon
            $table->integer('icon_size')->nullable();
            $table->string('icon_color')->nullable();
            $table->string('icon_dark_color')->nullable();
            $table->string('icon_position')->nullable(); // left, right, top, bottom

            // Animation
            $table->boolean('animation_enabled')->default(false);
            $table->string('animation_type')->nullable(); // fade, scale, slide, bounce, ripple
            $table->integer('animation_duration')->default(200);

            // Other Properties
            $table->decimal('opacity', 3, 2)->default(1.00);
            $table->json('custom_properties')->nullable(); // ข้อมูลเพิ่มเติม

            // Platform & Version
            $table->string('platform')->default('all'); // android, ios, all
            $table->string('min_app_version')->nullable();

            // Status
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('component_id');
            $table->index('component_type');
            $table->index('platform');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_settings');
    }
};
