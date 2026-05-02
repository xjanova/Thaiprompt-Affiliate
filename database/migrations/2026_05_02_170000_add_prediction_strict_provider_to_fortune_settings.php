<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-02) เพิ่ม column `prediction_strict_provider` ใน fortune_telling_settings
 *
 * User request:
 *   "เพิ่มออปชั่นว่าจะ fallback ไป provider อื่นไหมสำหรับคำทำนาย
 *    หรือจะใช้แค่ Gemini เท่านั้น"
 *
 * Values:
 *   - false (default) = fallback ได้ — primary fail → ลอง provider อื่น (smart pool)
 *   - true            = strict mode — primary fail → return error ทันที (ไม่ fallback)
 *
 * Use case:
 *   admin ตั้ง Gemini เป็น primary + ต้องการให้ใช้ Gemini เท่านั้น
 *   ถ้า Gemini fail → admin จะรู้ทันทีว่า quota หมด → ต้องเปลี่ยน key
 *   ไม่ปลอม fallback ไป Groq ที่อาจให้ผลลัพธ์ต่างกัน
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings') ||
            Schema::hasColumn('fortune_telling_settings', 'prediction_strict_provider')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $table->boolean('prediction_strict_provider')
                ->default(false)
                ->after('ai_model')
                ->comment('true=ใช้แค่ ai_provider เท่านั้น (ไม่ fallback) / false=fallback ได้ (default)');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings') ||
            ! Schema::hasColumn('fortune_telling_settings', 'prediction_strict_provider')) {
            return;
        }
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $table->dropColumn('prediction_strict_provider');
        });
    }
};
