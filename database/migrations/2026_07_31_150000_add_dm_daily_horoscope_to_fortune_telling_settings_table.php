<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มสวิตช์ "ส่งดวงรายวันไปกับ DM ด้วยไหม"
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — เป็นการเพิ่มคอลัมน์ (ALTER TABLE)
     *
     * dm_daily_horoscope_enabled:
     *   เปิด  → DM กลับหาลูกค้าจะมี "กล่องดวงรายวัน" นำหน้า แล้วต่อด้วยข้อความ DM ปกติ
     *   ปิด   → พฤติกรรมเดิม 100% (กล่อง DM ปกติกล่องเดียว)
     *   default = false เพื่อให้ deploy แล้วไม่เปลี่ยนพฤติกรรม DM ทันที
     *   (funnel DM เคยเปลี่ยนแล้วยอดตอบตก จนต้อง revert — ต้องให้แอดมินเปิดเอง)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_daily_horoscope_enabled')) {
                $table->boolean('dm_daily_horoscope_enabled')
                    ->default(false)
                    ->after('daily_horoscope_per_day_enabled')
                    ->comment('DM: แนบกล่องดวงรายวัน (บทความ AI 6 โมง) นำหน้าข้อความ DM ปกติ');
            }
        });
    }

    /**
     * ลบคอลัมน์สวิตช์ DM ดวงรายวัน
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'dm_daily_horoscope_enabled')) {
                $table->dropColumn('dm_daily_horoscope_enabled');
            }
        });
    }
};
