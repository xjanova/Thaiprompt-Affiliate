<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มสวิตช์ "แม่หมอส่งสติกเกอร์ / กดหัวใจตอบ บน Facebook" (2026-09-27)
 *
 * เจ้าของถาม: "ทำให้แม่หมอส่งสติกเกอร์กลับโต้ตอบได้ตามสถานการณ์ เช่น ใช้รูปมือแทนคำพูดเร่งให้เลือกแพคเกจ
 * หรืออีโมชั่นอื่นๆ ของเฟซบุ๊ค เพื่อให้เหมือนคน" → ตอบ "ทำตามสมควร" · "ไลน์ไม่ต้องทำ"
 *
 * - fortune_gestures_fb : เปิด/ปิดท่าทางของแม่หมอฝั่ง Facebook (default **เปิด**)
 *   ปิดแล้วทุกจุดกลับไปเป็นข้อความเดิมทุกตัวอักษร มีผลทันทีไม่ต้อง deploy
 *   ค่า default ของคอลัมน์ใหม่ลงแถวเดิมตอน ALTER เลย (ไม่ใช่ $attributes ในโมเดล)
 *
 * ⚠️ เป็น ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สวิตช์ท่าทางแม่หมอ
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_gestures_fb')) {
                $table->boolean('fortune_gestures_fb')
                    ->default(true)
                    ->comment('FB: แม่หมอส่งสติกเกอร์/กดหัวใจตอบตามสถานการณ์');
            }
        });
    }

    /**
     * ถอนคอลัมน์สวิตช์ท่าทางแม่หมอ
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'fortune_gestures_fb')) {
                $table->dropColumn('fortune_gestures_fb');
            }
        });
    }
};
