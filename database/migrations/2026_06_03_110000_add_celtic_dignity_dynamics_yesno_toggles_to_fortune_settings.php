<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔥💧📍🎯 (2026-06-03) เพิ่ม toggle กลไกอ่าน "ปฏิสัมพันธ์" 3 ตัวสุดท้าย
     *
     * Reading mechanics ระดับสูง (ต่อจาก combos + patterns):
     *   - enable_celtic_dignity  = ธาตุเสริม-ขัด (Elemental Dignities — Golden Dawn)
     *   - enable_celtic_dynamics = ความสัมพันธ์ตำแหน่ง Celtic (อดีต→อนาคต, ตัวเอง×สิ่งแวดล้อม ฯลฯ)
     *   - enable_celtic_yesno    = น้ำหนัก Yes/No (สรุปฟันธงคำถาม yes/no)
     *
     * default = เปิด (ค่ายังไม่มีคอลัมน์ก็ถือว่าเปิด — โค้ดใช้ ?? true)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ต่อท้าย enable_celtic_patterns ตาม pattern เดิม
            $previous = 'enable_celtic_patterns';
            foreach (['enable_celtic_dignity', 'enable_celtic_dynamics', 'enable_celtic_yesno'] as $col) {
                // เช็คทีละคอลัมน์ — ห้ามใช้ Schema::hasTable()+return ตอนเพิ่มคอลัมน์
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    // เผื่อ migration ก่อนหน้ายังไม่รัน → เพิ่มท้ายตาราง
                    if (Schema::hasColumn('fortune_telling_settings', $previous)) {
                        $table->boolean($col)->default(true)->after($previous);
                    } else {
                        $table->boolean($col)->default(true);
                    }
                }
                $previous = $col;
            }
        });
    }

    /**
     * ลบ toggle 3 ตัว
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['enable_celtic_dignity', 'enable_celtic_dynamics', 'enable_celtic_yesno'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
