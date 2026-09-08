<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มค่าตั้งของด่าน "ยิงสติกเกอร์/อีโมจิรัว" ลง fortune_telling_settings
     *
     * ⚠️ IMPORTANT: เป็นการ "เพิ่มคอลัมน์" ห้ามใช้ Schema::hasTable() + return
     *    ต้องใช้ Schema::table() แล้วเช็คทีละคอลัมน์ ไม่งั้นคอลัมน์ใหม่จะไม่ถูกสร้าง
     *
     * ⚡ ต่างจาก nav_flood: ตัวนี้ default = เปิด + enforce ตั้งแต่แรก (เจ้าของสั่ง 2026-09-08
     *    "เปิดเลย แต่ไม่ backfill") เพราะมีเคสจริงกำลังกวนอยู่ ณ ตอนตั้งค่า
     *    ความเสี่ยง false-positive ถูกกดด้วยขั้นบันไดแทน: ตอบปกติ 2 ใบแรก → เงียบ →
     *    เตือน 2 ครั้ง → ค่อยระงับ 7 วัน (ต้องยิงต่อข้าม 3 หน้าต่างคูลดาวน์)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_gesture_flood_guard')) {
                $table->boolean('enable_gesture_flood_guard')->default(true)
                    ->comment('เปิดด่านยิงสติกเกอร์/อีโมจิรัว');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_mode')) {
                $table->string('gesture_flood_mode', 20)->default('enforce')
                    ->comment('log_only = คำนวณครบแต่ไม่บล็อกใคร | enforce = บังคับใช้จริง');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_free_replies')) {
                $table->unsignedTinyInteger('gesture_flood_free_replies')->default(2)
                    ->comment('ตอบให้ตามปกติกี่ใบแรกในหน้าต่าง (คนแก่ส่งสติกเกอร์ทักทายต้องได้คำตอบ)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_max')) {
                $table->unsignedTinyInteger('gesture_flood_max')->default(6)
                    ->comment('ยิงครบกี่ใบในหน้าต่างถึงนับเป็นความผิด (แตะแล้วเตือน)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_window_sec')) {
                $table->unsignedSmallInteger('gesture_flood_window_sec')->default(300)
                    ->comment('หน้าต่างเวลานับความถี่ (วินาที)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_cooldown_minutes')) {
                $table->unsignedSmallInteger('gesture_flood_cooldown_minutes')->default(5)
                    ->comment('แตะเกณฑ์แล้วนับความผิดได้อีกครั้งหลังกี่นาที (กันยิงรัว = แบนทันที)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'gesture_flood_ban_days')) {
                $table->unsignedTinyInteger('gesture_flood_ban_days')->default(7)
                    ->comment('ระงับกี่วันเมื่อครบ 3 strike (ห้าม 0 = ถาวร)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'enable_gesture_flood_guard',
                'gesture_flood_mode',
                'gesture_flood_free_replies',
                'gesture_flood_max',
                'gesture_flood_window_sec',
                'gesture_flood_cooldown_minutes',
                'gesture_flood_ban_days',
            ] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
