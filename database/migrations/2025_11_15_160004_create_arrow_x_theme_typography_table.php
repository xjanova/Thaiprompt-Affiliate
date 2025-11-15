<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง theme_typography สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('theme_typography')) {
            return;
        }

        Schema::create('theme_typography', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_setting_id')->constrained('theme_settings')->onDelete('cascade');

            // Font Families
            $table->string('primary_font', 100)->default('Inter');
            $table->string('secondary_font', 100)->default('Noto Sans Thai');
            $table->string('code_font', 100)->default('Fira Code');

            // Font Sizes (rem)
            $table->decimal('base_font_size', 3, 2)->default(1.00)->comment('rem');
            $table->decimal('heading_h1_size', 3, 2)->default(2.50);
            $table->decimal('heading_h2_size', 3, 2)->default(2.00);
            $table->decimal('heading_h3_size', 3, 2)->default(1.75);
            $table->decimal('heading_h4_size', 3, 2)->default(1.50);
            $table->decimal('heading_h5_size', 3, 2)->default(1.25);
            $table->decimal('heading_h6_size', 3, 2)->default(1.00);

            // Font Weights
            $table->integer('thin_weight')->default(300);
            $table->integer('normal_weight')->default(400);
            $table->integer('medium_weight')->default(500);
            $table->integer('semibold_weight')->default(600);
            $table->integer('bold_weight')->default(700);
            $table->integer('extrabold_weight')->default(800);

            // Line Heights
            $table->decimal('heading_line_height', 3, 2)->default(1.20);
            $table->decimal('body_line_height', 3, 2)->default(1.60);

            // Letter Spacing (em)
            $table->decimal('heading_letter_spacing', 4, 3)->default(0.000)->comment('em');
            $table->decimal('body_letter_spacing', 4, 3)->default(0.000)->comment('em');

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->index('theme_setting_id');
        });
    }

    /**
     * ลบตาราง theme_typography
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_typography');
    }
};
