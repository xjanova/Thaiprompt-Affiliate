<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง horoscope_dream_readings — บันทึกการทำนายฝัน AI
     *
     * เก็บข้อมูลการทำนายฝันแต่ละครั้ง:
     * คำอธิบายฝัน + คำสำคัญที่ match + ผลทำนาย AI + เลขเด็ด
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('horoscope_dream_readings')) {
            return;
        }

        Schema::create('horoscope_dream_readings', function (Blueprint $table) {
            $table->id();

            // ผู้ใช้ (nullable สำหรับ guest)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->string('session_id', 64)->comment('Session ID สำหรับ guest tracking');

            // ข้อมูลฝัน
            $table->text('dream_description')->comment('คำอธิบายฝันจากผู้ใช้');
            $table->json('matched_keywords')->nullable()->comment('คำสำคัญที่ match จาก dictionary');

            // ผลทำนาย AI
            $table->text('ai_interpretation')->nullable()->comment('ผลทำนายฝันจาก AI');
            $table->json('lucky_numbers')->nullable()->comment('เลขเด็ดจากฝัน');

            // AI ที่ใช้
            $table->string('ai_provider_used', 50)->nullable();
            $table->string('ai_model_used', 100)->nullable();

            // สถิติ
            $table->boolean('is_free')->default(true)->comment('ใช้ฟรี หรือ ใช้เครดิต');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);

            // ข้อมูลผู้ใช้
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('session_id', 'dream_read_session_idx');
            $table->index('user_id', 'dream_read_user_idx');
            $table->index('created_at', 'dream_read_date_idx');
        });
    }

    /**
     * ลบตาราง horoscope_dream_readings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('horoscope_dream_readings');
    }
};
