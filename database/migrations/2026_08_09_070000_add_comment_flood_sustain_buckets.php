<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มเกณฑ์ "ความต่อเนื่อง" — ตัวชี้ขาดว่าเป็นคนกวนจริงหรือแฟนตัวยง
     *
     * เจ้าของนิยามไว้ชัด (2026-08-09):
     * "คนที่ระบบจะแบนเอง คือคนที่คอมเมนต์ผิดปกติจริงๆ เช่น คอมติดๆ กันตลอดเวลา"
     *
     * คำสำคัญคือ **ตลอดเวลา** ไม่ใช่ **รัว** — ข้อมูลจริงบน prod แยกสองอย่างนี้ชัด:
     * - แฟนสายมู: คอม 4-6 ครั้งใน 1-2 นาที แล้วหายไป (กระจุกอยู่ 1-2 ช่วง)
     * - คนกวน  : คอมเรื่อยๆ ไม่หยุด กระจายหลายช่วงติดกันเป็นชั่วโมง
     *
     * วิธีวัด: นับ "จำนวนช่วง 10 นาที ที่คนนี้มีคอมเมนต์" ภายใน 3 ชม.
     *   3 ช่วง  = แวะมา 3 ครั้ง (ปกติมาก)
     *   10 ช่วง = คอมแทบไม่หยุดตลอด 100 นาที (ผิดปกติจริง)
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'comment_flood_sustain_buckets')) {
                $table->unsignedSmallInteger('comment_flood_sustain_buckets')
                    ->default(10)
                    ->comment('คอมเมนต์กี่ "ช่วง 10 นาที" ภายใน 3 ชม. ถึงถือว่าคอมตลอดเวลา (ผิดปกติ)');
            }
        });

        Schema::table('fortune_comment_link_blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_comment_link_blocks', 'sustain_buckets')) {
                $table->unsignedSmallInteger('sustain_buckets')
                    ->nullable()
                    ->after('flood_count')
                    ->comment('จำนวนช่วง 10 นาทีที่มีคอมเมนต์ ณ ตอนที่ถูกจับ (ใช้เป็นหลักฐาน)');
            }
        });
    }

    /**
     * ย้อนกลับ
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'comment_flood_sustain_buckets')) {
                $table->dropColumn('comment_flood_sustain_buckets');
            }
        });

        Schema::table('fortune_comment_link_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_comment_link_blocks', 'sustain_buckets')) {
                $table->dropColumn('sustain_buckets');
            }
        });
    }
};
