<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🎬 (2026-09-04) เพิ่ม toggle `enable_celtic_story_mode`
     *
     * โหมดซีรี่ส์ = เล่าคำทำนายเป็นเรื่องที่มี "ตัวละคร (รูปพรรณ/วัย/ท่าที) + เหตุการณ์ข้างหน้า
     *   (เดินทางมีเรื่อง/อุบัติเหตุ/คนสร้างเรื่อง/คนนำโชค/คดี/โรค)" ดึงจากคลังความรู้รายไพ่
     *   ทำงานเมื่อ *ลูกค้าถามถึงอนาคต* + *ไพ่มีสัญญาณจริง* (ดู CelticCrossService::buildStoryModeDirective)
     *
     * ⚠️ ทำไมต้องมีคอลัมน์นี้: โค้ดอ่าน `$this->settings->enable_celtic_story_mode ?? true` ไว้ตั้งแต่แรก
     *   แต่ **คอลัมน์ไม่เคยถูกสร้าง** ⇒ ค่าเป็น null เสมอ ⇒ `?? true` ⇒ เปิดตลอด ปิดไม่ได้
     *   ขณะที่ toggle celtic ตัวอื่นอีก 25 ตัวมีคอลัมน์จริงหมด (ปิดผ่าน DB UPDATE ได้)
     *   = ความไม่สมมาตรที่แอดมินจะงงตอนอยากปิด
     *
     * default = true (พฤติกรรมเดิมก่อนมี migration นี้ ไม่มีอะไรเปลี่ยนสำหรับบิลที่วิ่งอยู่)
     * admin ปิดได้ผ่าน: UPDATE fortune_telling_settings SET enable_celtic_story_mode = 0
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ⚠️ เช็คคอลัมน์ก่อนเพิ่ม — ห้ามใช้ Schema::hasTable() + return ตอนเพิ่มคอลัมน์
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_story_mode')) {
                $table->boolean('enable_celtic_story_mode')
                    ->default(true)
                    ->after('enable_celtic_person_role');
            }
        });
    }

    /**
     * ลบ toggle (โค้ดมี `?? true` รองรับอยู่แล้ว — ถอยแล้วโหมดซีรี่ส์กลับไปเปิดตลอด)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_story_mode')) {
                $table->dropColumn('enable_celtic_story_mode');
            }
        });
    }
};
