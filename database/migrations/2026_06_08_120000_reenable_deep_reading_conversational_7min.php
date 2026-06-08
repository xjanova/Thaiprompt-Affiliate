<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปิดระบบ "ดูดวง 39" กลับมาอีกครั้ง (เวอร์ชันคุยโต้ตอบใหม่)
     *
     * สเปคใหม่ (2026-06-08):
     *   - เปิด enable_deep_reading = true (เดิมปิดไว้ตั้งแต่ 2026-05-23 เหลือแต่ Celtic 99)
     *   - แพคเกจ 39 ใหม่: เก็บวันเกิด → เปิดไพ่ → ทำนาย "พื้นดวงทั่วไป" ทันที (ไม่มีขั้นรับคำถาม)
     *     → คุยโต้ตอบกับแม่หมอต่อได้ภายในเวลาที่กำหนด (เหมือนแพคเกจ 99 แต่สั้นกว่า)
     *   - เพิ่มคอลัมน์ deep_reading_qa_window_minutes (หน้าต่างคุยหลังเปิดไพ่) default 7 นาที
     *     คู่ขนานกับ celtic_cross_qa_window_minutes ของแพคเกจ 99 (แอดมินปรับได้)
     */
    public function up(): void
    {
        // ✅ เพิ่มคอลัมน์ใหม่อย่างปลอดภัย — เช็คก่อนเพิ่ม (ALTER TABLE ห้ามใช้ hasTable()+return)
        if (Schema::hasTable('fortune_telling_settings')
            && ! Schema::hasColumn('fortune_telling_settings', 'deep_reading_qa_window_minutes')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                $table->integer('deep_reading_qa_window_minutes')
                    ->default(7)
                    ->after('deep_reading_price')
                    ->comment('หน้าต่างคุยกับแม่หมอ (นาที) หลังเปิดไพ่ ดูดวง 39');
            });
        }

        // ✅ เปิด 39 กลับมา + ตั้งหน้าต่างคุย 7 นาที ให้ row การตั้งค่าที่มีอยู่
        if (Schema::hasTable('fortune_telling_settings')) {
            DB::table('fortune_telling_settings')->update([
                'enable_deep_reading' => true,
                'deep_reading_qa_window_minutes' => 7,
            ]);
        }
    }

    /**
     * ย้อนกลับ — ปิด 39 (กลับเป็น Celtic-only) แต่ไม่ลบคอลัมน์ (กันข้อมูลหาย)
     */
    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            DB::table('fortune_telling_settings')->update([
                'enable_deep_reading' => false,
            ]);
        }
    }
};
