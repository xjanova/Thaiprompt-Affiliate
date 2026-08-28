<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌙 สวิตช์เปิด/ปิด "ระบบชวนรับดวงรายวันฟรี" (ฝั่ง DM ขาออกเท่านั้น)
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — เป็นการเพิ่มคอลัมน์ (ALTER TABLE)
     *
     * daily_free_horoscope_enabled:
     *   เปิด (default) → พฤติกรรมเดิม 100% — DM ชวนบอกวันเกิดรับดวงฟรี / การ์ด 🎁 รับดวงฟรี
     *   ปิด            → DM ขาออกกลับไปใช้ "ชุดข้อความชวนดูดวงชุดแรก" (mode=all/classic)
     *                    ไม่ชวนรับดวงฟรี ไม่ยื่นการ์ดฟรี ไม่ตั้งธงถามวันเกิด
     *
     * 🚨 **ปิดแล้วเลนขาเข้ายังทำงานครบ** — ลูกค้าพิมพ์ "ดูดวงฟรี" เองยังได้ดวงประจำวันเกิด
     *    + การ์ด 7 ใบ เหมือนเดิม (เจ้าของสั่ง 2026-08-28) ตัวที่ปิดคือ "การชวน" ไม่ใช่ "ของ"
     *
     * default = true เพราะเป็นสวิตช์ของฟีเจอร์ที่วิ่งอยู่จริงบน prod แล้ว
     * (deploy ไม่ควรเปลี่ยนพฤติกรรมเอง — ต้องให้แอดมินติ๊กปิดเอง)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'daily_free_horoscope_enabled')) {
                $table->boolean('daily_free_horoscope_enabled')
                    ->default(true)
                    ->after('dm_daily_horoscope_enabled')
                    ->comment('เปิดระบบชวนรับดวงรายวันฟรีใน DM (ปิด = ใช้ชุดข้อความชวนดูดวงชุดแรกอย่างเดียว)');
            }
        });
    }

    /**
     * ลบคอลัมน์สวิตช์ระบบดวงรายวันฟรี
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'daily_free_horoscope_enabled')) {
                $table->dropColumn('daily_free_horoscope_enabled');
            }
        });
    }
};
