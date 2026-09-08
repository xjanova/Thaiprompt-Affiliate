<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มค่าตั้งของราง "ยิงข้อความรัวทุกชนิด" (volume rail)
     *
     * ⚠️ IMPORTANT: เป็นการ "เพิ่มคอลัมน์" ห้ามใช้ Schema::hasTable() + return
     *
     * ทำไมต้องมีราง 2: ด่านสแปมเดิม (`isUserSpamming` Rule 1) ตั้งไว้ที่
     * **> 10 ข้อความ / 30 วินาที = เกิน 20 ข้อความ/นาที** ซึ่งเป็นเกณฑ์สำหรับบอท
     *
     * เคสจริง 2026-09-08 (แสนที เล็ก): ยิง **7.5 ข้อความ/นาที ต่อเนื่อง 15 นาที**
     * (113 ใบ) — ไม่เคยแตะ Rule 1 เลยสักครั้ง เพราะคนพิมพ์มือทำได้สบายๆ ใต้เพดานนั้น
     * และต่อให้แตะ ด่านเดิมก็แค่ "เงียบ 5 นาที" แล้ววนใหม่ได้ไม่จำกัด ไม่มีทางไปสู่การแบน
     *
     * ⚡ จงใจ **ไม่ลด** เกณฑ์ Rule 1 เดิม — การลดเพดานทำให้คนถูกปิดปากมากขึ้นทันที
     * (เสี่ยง false-positive กับลูกค้าจริงที่ตื่นเต้นพิมพ์รัว) จึงเพิ่มรางใหม่ที่วัด
     * "ปริมาณสะสมระยะยาว" แทน: 40 ใบ / 10 นาที = 4 ใบ/นาทีต่อเนื่อง ซึ่งคนคุยปกติไม่ถึง
     * แต่คนตั้งใจกวนถึงแน่นอน (แสนที = ~75 ใบ/10 นาที)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_volume_max')) {
                $table->unsignedSmallInteger('gesture_flood_volume_max')->default(40)
                    ->comment('ยิงข้อความ (ทุกชนิด) ครบกี่ใบในหน้าต่างถึงนับเป็นความผิด · 0 = ปิดรางนี้');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_volume_window_sec')) {
                $table->unsignedSmallInteger('gesture_flood_volume_window_sec')->default(600)
                    ->comment('หน้าต่างเวลาของรางปริมาณ (วินาที)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['gesture_flood_volume_max', 'gesture_flood_volume_window_sec'] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
