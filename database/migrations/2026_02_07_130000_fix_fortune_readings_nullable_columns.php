<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขคอลัมน์ในตาราง fortune_readings
 *
 * 1. ai_response, ai_provider → nullable (สร้าง record ก่อนเรียก AI)
 * 2. basic_response, deep_response → longText (text จำกัด 65KB ไม่พอ)
 */
return new class extends Migration
{
    /**
     * แก้ไขคอลัมน์
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

            // basic_response, deep_response - เปลี่ยนจาก text เป็น longText (text จำกัด 65KB ไม่พอสำหรับคำทำนายยาว)
            if (Schema::hasColumn('fortune_readings', 'basic_response')) {
                $table->longText('basic_response')->nullable()->comment('คำทำนายพื้นฐาน')->change();
            }
            if (Schema::hasColumn('fortune_readings', 'deep_response')) {
                $table->longText('deep_response')->nullable()->comment('คำทำนายเชิงลึก')->change();
            }
        });
    }

    /**
     * คืนค่าเดิม
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $table->longText('ai_response')->nullable(false)->comment('คำทำนายจาก AI')->change();
            $table->string('ai_provider', 50)->nullable(false)->comment('AI Provider ที่ใช้ (gemini, groq, qwen)')->change();

            if (Schema::hasColumn('fortune_readings', 'basic_response')) {
                $table->text('basic_response')->nullable()->comment('คำทำนายพื้นฐาน')->change();
            }
            if (Schema::hasColumn('fortune_readings', 'deep_response')) {
                $table->text('deep_response')->nullable()->comment('คำทำนายเชิงลึก')->change();
            }
        });
    }
};
