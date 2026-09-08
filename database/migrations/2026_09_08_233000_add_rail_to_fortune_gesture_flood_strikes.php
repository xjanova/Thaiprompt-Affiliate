<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม `rail` ให้ fortune_gesture_flood_strikes — 1 แถวต่อ (ลูกค้า × ช่องทาง × ราง)
     *
     * ⚠️ IMPORTANT: เป็นการ "เพิ่มคอลัมน์" ห้ามใช้ Schema::hasTable() + return
     *
     * ทำไมต้องมี: ด่านเดิมมีรางเดียว = "ท่าทางล้วน" (สติกเกอร์/อีโมจิ)
     * เพิ่มราง `volume` = "ยิงข้อความรัวๆ ทุกชนิด" ซึ่งเป็นคนละพฤติกรรม
     *
     * ถ้าใช้แถวร่วมกัน strike ของสองรางจะทับกัน — คนส่งสติกเกอร์ 2 ครั้ง
     * บวกกับพิมพ์รัว 1 ครั้ง จะกลายเป็น 3 strike = แบนทั้งที่ไม่มีรางไหนถึงเกณฑ์
     *
     * 🔒 ตารางนี้เพิ่งสร้างวันเดียวกัน (migration 223000) และยัง 0 แถว
     *    การ drop/สร้าง unique index ใหม่จึงไม่มีข้อมูลเสี่ยง
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_gesture_flood_strikes')) {
            return;
        }

        if (! Schema::hasColumn('fortune_gesture_flood_strikes', 'rail')) {
            Schema::table('fortune_gesture_flood_strikes', function (Blueprint $table) {
                $table->string('rail', 20)->default('gesture')->after('platform_user_id')
                    ->comment('gesture = สติกเกอร์/อีโมจิล้วน | volume = ยิงข้อความรัวทุกชนิด');
            });
        }

        // unique เดิมคือ (platform, platform_user_id) — ต้องรวม rail ไม่งั้นรางที่สองเขียนทับรางแรก
        try {
            Schema::table('fortune_gesture_flood_strikes', function (Blueprint $table) {
                $table->dropUnique('gesture_flood_user_unique');
            });
        } catch (\Throwable $e) {
            // ไม่มี index เดิม (เครื่องใหม่ที่รัน migration ชุดนี้รวดเดียว) → ข้าม
        }

        try {
            Schema::table('fortune_gesture_flood_strikes', function (Blueprint $table) {
                $table->unique(['platform', 'platform_user_id', 'rail'], 'gesture_flood_user_rail_unique');
            });
        } catch (\Throwable $e) {
            // มีอยู่แล้ว → ข้าม
        }
    }

    /**
     * คืนสภาพ unique เดิม + ลบคอลัมน์
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_gesture_flood_strikes')) {
            return;
        }

        try {
            Schema::table('fortune_gesture_flood_strikes', function (Blueprint $table) {
                $table->dropUnique('gesture_flood_user_rail_unique');
            });
        } catch (\Throwable $e) {
        }

        if (Schema::hasColumn('fortune_gesture_flood_strikes', 'rail')) {
            Schema::table('fortune_gesture_flood_strikes', function (Blueprint $table) {
                $table->dropColumn('rail');
            });
        }

        try {
            Schema::table('fortune_gesture_flood_strikes', function (Blueprint $table) {
                $table->unique(['platform', 'platform_user_id'], 'gesture_flood_user_unique');
            });
        } catch (\Throwable $e) {
        }
    }
};
