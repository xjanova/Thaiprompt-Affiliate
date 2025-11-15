<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง theme_components สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('theme_components')) {
            return;
        }

        Schema::create('theme_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_setting_id')->constrained('theme_settings')->onDelete('cascade');

            // Component Info
            $table->string('component_name', 100);
            $table->enum('component_type', [
                'card', 'button', 'input', 'select', 'modal', 'dropdown',
                'table', 'badge', 'alert', 'navbar', 'sidebar', 'footer'
            ]);

            // Visibility
            $table->boolean('is_enabled')->default(true);

            // Dimensions
            $table->integer('height')->nullable()->comment('px or null for auto');
            $table->string('width', 20)->nullable()->comment('px, %, auto');
            $table->integer('padding_x')->default(16)->comment('px horizontal');
            $table->integer('padding_y')->default(12)->comment('px vertical');
            $table->integer('margin_x')->default(0);
            $table->integer('margin_y')->default(0);

            // Border & Radius
            $table->integer('border_width')->default(1)->comment('px');
            $table->integer('border_radius')->default(8)->comment('px');
            $table->string('border_color', 7)->nullable();

            // Shadow
            $table->enum('shadow_size', ['none', 'sm', 'md', 'lg', 'xl', '2xl'])->default('md');
            $table->boolean('hover_shadow_enabled')->default(true);

            // Neumorphic 3D Settings (สำหรับปุ่ม)
            $table->boolean('neumorphic_enabled')->default(false);
            $table->integer('neumorphic_depth')->default(10)->comment('px');
            $table->string('neumorphic_light_shadow', 20)->nullable()->comment('rgba(255,255,255,0.5)');
            $table->string('neumorphic_dark_shadow', 20)->nullable()->comment('rgba(0,0,0,0.3)');

            // Transparency
            $table->integer('opacity')->default(100)->comment('0-100%');
            $table->integer('blur_intensity')->default(0)->comment('0-20px');

            // Hover Effects
            $table->decimal('hover_scale', 3, 2)->default(1.00)->comment('1.0 - 1.2');
            $table->integer('hover_lift')->default(0)->comment('px translateY');
            $table->integer('animation_duration')->default(300)->comment('ms');

            // Custom CSS
            $table->text('custom_css')->nullable()->comment('Additional custom CSS');

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->index('theme_setting_id');
            $table->index('component_type');
            $table->index('is_enabled');
        });
    }

    /**
     * ลบตาราง theme_components
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_components');
    }
};
