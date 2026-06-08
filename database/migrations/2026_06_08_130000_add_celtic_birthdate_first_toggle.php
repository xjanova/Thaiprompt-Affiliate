<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม toggle "ถามวันเดือนปีเกิดก่อนใน Celtic 99 + ทำนายพื้นดวงผสมไพ่"
     *
     * สเปค (2026-06-08):
     *   - Celtic 99 เพิ่มคำถามแรกบังคับ = วันเดือนปีเกิด (หลังเปิดไพ่ครบ 10 ใบ)
     *   - ถ้าเคยให้วันเกิด/ทำ 39 มาก่อน (มี birth_date ในฐาน) → ใช้เลย ไม่ถามซ้ำ
     *   - ถ้าไม่เคย → ถามวันเกิด → ทำนาย "พื้นดวง" (ดาวเจ้าชนะแบบ 39) 1 ชุด
     *     → จากนั้นทำนายไพ่ผสมดวงดาวต่อไปทุกคำถาม
     *   - แอดมินปิดได้ทันที (default เปิด) — กันกรณีกระทบ flow เรือธง
     */
    public function up(): void
    {
        // ✅ เพิ่มคอลัมน์ใหม่อย่างปลอดภัย — เช็คก่อนเพิ่ม (ALTER TABLE ห้ามใช้ hasTable()+return)
        if (Schema::hasTable('fortune_telling_settings')
            && ! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_birthdate_first')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                $table->boolean('enable_celtic_birthdate_first')
                    ->default(true)
                    ->comment('Celtic 99: ถามวันเกิดเป็นคำถามแรก + ทำนายพื้นดวง (ดาวเจ้าชนะ) ผสมไพ่');
            });
        }

        // ✅ เปิดให้ row การตั้งค่าที่มีอยู่ (default true)
        if (Schema::hasTable('fortune_telling_settings')) {
            try {
                DB::table('fortune_telling_settings')->update([
                    'enable_celtic_birthdate_first' => true,
                ]);
            } catch (\Throwable $e) {
                // non-blocking — ถ้า update พลาด ตัว default ของคอลัมน์ก็เป็น true อยู่แล้ว
            }
        }
    }

    /**
     * ย้อนกลับ — ลบคอลัมน์ (เช็คก่อนลบ)
     */
    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings')
            && Schema::hasColumn('fortune_telling_settings', 'enable_celtic_birthdate_first')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                $table->dropColumn('enable_celtic_birthdate_first');
            });
        }
    }
};
