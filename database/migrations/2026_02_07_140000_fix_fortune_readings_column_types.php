<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขคอลัมน์ในตาราง fortune_readings (รอบที่ 2)
 *
 * 1. ai_response, ai_provider → nullable (สร้าง record ก่อนเรียก AI)
 * 2. basic_response, deep_response → longText (text จำกัด 65KB ไม่พอ)
 *
 * หมายเหตุ: migration 2026_02_07_130000 ถูก mark ว่ารันแล้วแต่ไม่ได้รัน SQL จริง
 * migration นี้จึงรัน SQL เดียวกันอีกครั้ง (idempotent - safe to run ซ้ำ)
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
            // ai_response - nullable เพราะสร้าง record ก่อนเรียก AI
            $table->longText('ai_response')->nullable()->comment('คำทำนายจาก AI')->change();

            // ai_provider - nullable เพราะสร้าง record ก่อนเรียก AI
            $table->string('ai_provider', 50)->nullable()->comment('AI Provider ที่ใช้ (gemini, groq, qwen)')->change();

            // basic_response - เปลี่ยนจาก text เป็น longText
            if (Schema::hasColumn('fortune_readings', 'basic_response')) {
                $table->longText('basic_response')->nullable()->comment('คำทำนายพื้นฐาน')->change();
            }

            // deep_response - เปลี่ยนจาก text เป็น longText
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
        // ไม่ revert เพราะ nullable และ longText เป็นค่าที่ถูกต้อง
    }
};
