<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มสวิตช์ "แต่งหัวข้อคำทำนายบน Facebook" (2026-09-13)
 *
 * เจ้าของสั่ง: "ทำไมไม่ทำให้การตอบแม่หมอเป็นสัดส่วน สวยงาม ... แชทเฟซบุ๊คมันมีข้อจำกัด"
 * แล้วสั่งต่อ "แก้ให้หมดแล้วพุชพร้อมกัน" ⇒ ใช้แบบ ก (【 หัวข้อ 】 + เส้นคั่นสั้น) ที่แนะนำไว้
 *
 * - fortune_messenger_format_fb : เปิด/ปิดการแต่งหัวข้อ (default **เปิด**)
 *   มีสวิตช์ไว้ให้เจ้าของปิดเองได้ทันทีจากหลังบ้าน ถ้าหน้าตาไม่ถูกใจ ไม่ต้องรอ deploy
 *   ค่า default ของคอลัมน์ใหม่ลงแถวเดิมตอน ALTER เลย (ไม่ใช่ $attributes ในโมเดล — ไม่ต้อง data migration)
 *
 * ⚠️ เป็น ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สวิตช์แต่งหัวข้อ
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_messenger_format_fb')) {
                $table->boolean('fortune_messenger_format_fb')
                    ->default(true)
                    ->comment('FB: แต่งหัวข้อคำทำนายเป็น 【 หัวข้อ 】 + เส้นคั่นสั้น');
            }
        });
    }

    /**
     * ถอนคอลัมน์สวิตช์แต่งหัวข้อ
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'fortune_messenger_format_fb')) {
                $table->dropColumn('fortune_messenger_format_fb');
            }
        });
    }
};
