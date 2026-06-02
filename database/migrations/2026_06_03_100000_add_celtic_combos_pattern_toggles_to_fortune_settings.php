<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔗🎴 (2026-06-03) เพิ่ม toggle กลไกอ่าน "ความสัมพันธ์ระหว่างไพ่" 2 ตัว
     *
     * ต่อยอดจากคลังความรู้รายใบ → เพิ่ม "กลไกการอ่าน" (reading mechanics):
     *   - enable_celtic_combos   = ไพ่คู่/ไพ่สัมพันธ์ (เชื่อมโยงไพ่ตามที่หลักการสั่ง)
     *   - enable_celtic_patterns = อ่านภาพรวมสำรับ (Major/สำรับเด่น/กลับหัว/ราชสำนัก/Ace)
     *
     * default = เปิด (ค่ายังไม่มีคอลัมน์ก็ถือว่าเปิด — โค้ดใช้ ?? true)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ต่อท้าย enable_celtic_remedy ตาม pattern เดิม
            $previous = 'enable_celtic_remedy';
            foreach (['enable_celtic_combos', 'enable_celtic_patterns'] as $col) {
                // เช็คทีละคอลัมน์ — ห้ามใช้ Schema::hasTable()+return ตอนเพิ่มคอลัมน์
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    // ถ้าคอลัมน์อ้างอิงไม่มี (เผื่อ migration เดิมยังไม่รัน) → ไม่ระบุ after
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
     * ลบ toggle 2 ตัว (ไม่กระทบข้อมูลความรู้ใน config — combos/patterns เก็บใน config ล้วน)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['enable_celtic_combos', 'enable_celtic_patterns'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
