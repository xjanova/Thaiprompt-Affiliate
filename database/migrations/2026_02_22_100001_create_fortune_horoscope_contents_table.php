<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_horoscope_contents
 *
 * เก็บเนื้อหาดวงรายวันที่ AI สร้าง
 * แยกเป็นแต่ละวันเกิด (0=อาทิตย์ ถึง 6=เสาร์)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_horoscope_contents')) {
            return;
        }

        Schema::create('fortune_horoscope_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('fortune_horoscope_campaigns')->cascadeOnDelete();
            $table->date('target_date');
            $table->tinyInteger('birth_day');
            $table->string('birth_day_name');

            // Astrology Data
            $table->string('main_planet')->nullable();
            $table->json('planet_positions')->nullable();
            $table->json('chaochana_data')->nullable();

            // AI Generated Text
            $table->longText('ai_prediction')->nullable();
            $table->text('ai_prompt_used')->nullable();
            $table->string('ai_text_provider_used')->nullable();
            $table->string('ai_text_model_used')->nullable();

            // AI Generated Image
            $table->string('image_url')->nullable();
            $table->string('image_path')->nullable();
            $table->text('image_prompt_used')->nullable();
            $table->string('ai_image_provider_used')->nullable();

            // Lucky Info
            $table->string('lucky_color')->nullable();
            $table->string('lucky_number')->nullable();
            $table->string('lucky_direction')->nullable();

            // Status
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->unique(['campaign_id', 'target_date', 'birth_day'], 'horoscope_content_unique');
            $table->index(['target_date', 'status'], 'horoscope_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_horoscope_contents');
    }
};
