<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปิด NavFloodGuard ให้ "แถวที่มีอยู่แล้ว" ในตาราง fortune_telling_settings
     *
     * 🚨 บทเรียนที่ migration นี้แก้ (2026-09-09):
     *   commit ก่อนหน้าแก้ค่า default ใน `FortuneTellingSetting::$attributes` เป็น
     *   `enable_nav_flood_guard => true` แล้วเข้าใจว่าเปิดแล้ว
     *   **แต่ `$attributes` มีผลกับ instance ที่สร้างใหม่เท่านั้น** — `self::first()`
     *   hydrate จาก DB จึงยังได้ค่าเก่า (0) ⇒ prod ไม่ขยับเลย
     *
     *   ต่างจากคอลัมน์ใหม่ที่ `->default(true)` ใน `Schema::table()` ซึ่ง MySQL
     *   เติมค่าให้แถวเดิมตอน ALTER ให้เอง — การแก้ default ฝั่ง PHP ไม่มีผลย้อนหลัง
     *
     * ⚠️ เงื่อนไข `WHERE enable_nav_flood_guard = 0` — ถ้าแอดมินตั้งใจปิดไว้ทีหลัง
     *    migration นี้รันครั้งเดียวจบ ไม่ย้อนกลับมาเปิดซ้ำ
     *
     * เหตุที่ต้องเปิด: ด่านนี้เขียนเสร็จตั้งแต่ 2026-08-21 แต่ `fortune_nav_flood_strikes`
     * มี 0 แถวมาตลอด ทั้งที่เคยมีเคสจริงกดปุ่มเดิม 10+ ครั้ง/2 นาที กินโควตาส่งของเพจ 26%/ชม.
     * ขั้นบันไดของมันเองกันไว้อยู่แล้ว: เบรกเงียบ → เตือน → เตือนสุดท้าย → แบน 7 วัน
     * + ลูกค้าจ่ายเงินยกเว้นทุกขั้น + REPEAT_SAFE_PREFIXES กันปุ่มที่ตั้งใจให้กดซ้ำ
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')
            || ! Schema::hasColumn('fortune_telling_settings', 'enable_nav_flood_guard')) {
            return;
        }

        DB::table('fortune_telling_settings')
            ->where('enable_nav_flood_guard', 0)
            ->update([
                'enable_nav_flood_guard' => 1,
                'nav_flood_mode' => 'enforce',
            ]);
    }

    /**
     * ปิดกลับเป็นค่าเดิม (log_only)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')
            || ! Schema::hasColumn('fortune_telling_settings', 'enable_nav_flood_guard')) {
            return;
        }

        DB::table('fortune_telling_settings')->update([
            'enable_nav_flood_guard' => 0,
            'nav_flood_mode' => 'log_only',
        ]);
    }
};
