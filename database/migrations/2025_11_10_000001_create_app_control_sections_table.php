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
        Schema::create('app_control_sections', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('section_id')->unique(); // header, sidebar, footer, navigation, tab_bar, etc.
            $table->string('section_name');
            $table->string('section_name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();

            // Visibility & Positioning
            $table->boolean('is_visible')->default(true);
            $table->string('display_position')->nullable(); // top, bottom, left, right, center
            $table->integer('sort_order')->default(0);

            // Layout Properties
            $table->string('layout_type')->nullable(); // horizontal, vertical, grid, stack
            $table->integer('min_height')->nullable(); // in dp/points
            $table->integer('max_height')->nullable();
            $table->integer('min_width')->nullable();
            $table->integer('max_width')->nullable();

            // Styling
            $table->string('background_color')->nullable();
            $table->string('background_dark_color')->nullable();
            $table->string('border_color')->nullable();
            $table->string('border_dark_color')->nullable();
            $table->integer('border_width')->default(0);
            $table->integer('border_radius')->nullable();
            $table->integer('elevation')->default(0); // shadow depth
            $table->decimal('opacity', 3, 2)->default(1.00); // 0.00 to 1.00

            // Spacing
            $table->integer('padding_top')->nullable();
            $table->integer('padding_bottom')->nullable();
            $table->integer('padding_left')->nullable();
            $table->integer('padding_right')->nullable();
            $table->integer('margin_top')->nullable();
            $table->integer('margin_bottom')->nullable();
            $table->integer('margin_left')->nullable();
            $table->integer('margin_right')->nullable();

            // Animation
            $table->boolean('animation_enabled')->default(false);
            $table->string('animation_type')->nullable(); // fade, slide, scale, bounce
            $table->integer('animation_duration')->default(300); // milliseconds

            // Custom Properties (JSON)
            $table->json('custom_properties')->nullable(); // ข้อมูลเพิ่มเติมที่เป็น key-value

            // Platform & Version
            $table->string('platform')->default('all'); // android, ios, all
            $table->string('min_app_version')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('section_id');
            $table->index('is_visible');
            $table->index('platform');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_control_sections');
    }
};
