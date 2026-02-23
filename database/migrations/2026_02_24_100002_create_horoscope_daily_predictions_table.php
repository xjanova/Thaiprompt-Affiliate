<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง horoscope_daily_predictions — ดวงรายวัน AI-generated
     *
     * เก็บคำทำนายรายวัน 5 ด้าน (ภาพรวม, ความรัก, การงาน, การเงิน, สุขภาพ)
     * พร้อมคะแนน 1-5 และเลข/สี/ทิศมงคล
     * รองรับทั้ง 12 ราศี และ 7 วันเกิด
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('horoscope_daily_predictions')) {
            return;
        }

        Schema::create('horoscope_daily_predictions', function (Blueprint $table) {
            $table->id();

            // อ้างอิงราศี (nullable สำหรับ birth_day predictions)
            $table->foreignId('zodiac_sign_id')
                ->nullable()
                ->constrained('horoscope_zodiac_signs')
                ->onDelete('cascade');

            // วันที่ทำนาย
            $table->date('target_date')->comment('วันที่ทำนาย');
            $table->unsignedTinyInteger('birth_day')->nullable()->comment('วันเกิด 0=อาทิตย์..6=เสาร์ (สำหรับ birth_day type)');
            $table->enum('prediction_type', ['zodiac', 'birth_day'])->default('zodiac')->comment('ประเภท: ตามราศี หรือ ตามวันเกิด');

            // คำทำนาย 5 ด้าน
            $table->text('overall_prediction_th')->nullable()->comment('คำทำนายภาพรวม');
            $table->text('love_prediction_th')->nullable()->comment('คำทำนายความรัก');
            $table->text('career_prediction_th')->nullable()->comment('คำทำนายการงาน');
            $table->text('finance_prediction_th')->nullable()->comment('คำทำนายการเงิน');
            $table->text('health_prediction_th')->nullable()->comment('คำทำนายสุขภาพ');

            // คะแนน 1-5
            $table->unsignedTinyInteger('overall_score')->default(3)->comment('คะแนนภาพรวม 1-5');
            $table->unsignedTinyInteger('love_score')->default(3)->comment('คะแนนความรัก 1-5');
            $table->unsignedTinyInteger('career_score')->default(3)->comment('คะแนนการงาน 1-5');
            $table->unsignedTinyInteger('finance_score')->default(3)->comment('คะแนนการเงิน 1-5');
            $table->unsignedTinyInteger('health_score')->default(3)->comment('คะแนนสุขภาพ 1-5');

            // เลข/สี/ทิศมงคล
            $table->string('lucky_number', 50)->nullable()->comment('เลขมงคล เช่น 3, 7, 21');
            $table->string('lucky_color_th', 100)->nullable()->comment('สีมงคลภาษาไทย');
            $table->string('lucky_direction_th', 100)->nullable()->comment('ทิศมงคลภาษาไทย');

            // AI ที่ใช้สร้าง
            $table->string('ai_provider_used', 50)->nullable()->comment('AI provider เช่น gemini, groq');
            $table->string('ai_model_used', 100)->nullable()->comment('AI model เช่น gemini-2.0-flash');

            // สถิติ
            $table->unsignedInteger('view_count')->default(0)->comment('จำนวนการดู');
            $table->unsignedInteger('share_count')->default(0)->comment('จำนวนการแชร์');

            // สถานะ
            $table->enum('status', ['pending', 'generating', 'generated', 'failed'])
                ->default('pending')
                ->comment('สถานะการสร้าง');
            $table->text('error_message')->nullable()->comment('ข้อผิดพลาด (ถ้ามี)');
            $table->timestamp('generated_at')->nullable()->comment('เวลาที่สร้างเสร็จ');

            $table->timestamps();

            // Indexes สำหรับ query หลัก
            $table->unique(['target_date', 'zodiac_sign_id', 'prediction_type'], 'daily_zodiac_unique');
            $table->unique(['target_date', 'birth_day', 'prediction_type'], 'daily_birthday_unique');
            $table->index(['target_date', 'status'], 'daily_date_status_idx');
        });
    }

    /**
     * ลบตาราง horoscope_daily_predictions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('horoscope_daily_predictions');
    }
};
