<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขคอลัมน์ ai_response และ ai_provider ให้เป็น nullable
 *
 * ปัญหา: เมื่อสร้าง FortuneReading ใหม่ ยังไม่มีค่า ai_response และ ai_provider
 * เพราะต้องเรียก AI ก่อนแล้วค่อยอัพเดท แต่คอลัมน์เป็น NOT NULL
 * ทำให้ INSERT ล้มเหลว: "Field 'ai_response' doesn't have a default value"
 */
return new class extends Migration
{
    /**
     * แก้ไขคอลัมน์ให้เป็น nullable
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            // ai_response - ยังไม่มีค่าตอนสร้าง record ใหม่ (จะอัพเดทหลัง AI ตอบ)
            $table->longText('ai_response')->nullable()->comment('คำทำนายจาก AI')->change();

            // ai_provider - ยังไม่มีค่าตอนสร้าง record ใหม่
            $table->string('ai_provider', 50)->nullable()->comment('AI Provider ที่ใช้ (gemini, groq, qwen)')->change();
        });
    }

    /**
     * คืนค่าเดิม (NOT NULL)
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $table->longText('ai_response')->nullable(false)->comment('คำทำนายจาก AI')->change();
            $table->string('ai_provider', 50)->nullable(false)->comment('AI Provider ที่ใช้ (gemini, groq, qwen)')->change();
        });
    }
};
