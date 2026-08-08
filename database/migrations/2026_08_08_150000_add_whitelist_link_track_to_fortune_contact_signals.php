<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม "รางที่ 2" สำหรับจับคนที่ถูก whitelist แล้วแต่ยังยิงลิงก์รัวๆ
     *
     *  - wl_link_count    : จำนวนข้อความ "ลิงก์" (ไม่นับรูป/วิดีโอ) ที่ส่งมาหลังได้เกราะ whitelist
     *  - wl_link_days     : จำนวนวันต่างกันที่ยิงลิงก์หลัง whitelist (ความถี่)
     *  - wl_last_link_day : วันล่าสุดที่ยิงลิงก์หลัง whitelist (ใช้คำนวณ wl_link_days)
     *
     * ที่มา: เคสจริง 2026-08-08 (PSID 27713676774998286 "อุดม ศรีโปฎก")
     *   ยิงลิงก์แชร์ 13 ครั้งใน 11 นาที แต่ระบบแตะไม่ได้เลย เพราะ whitelisted=1 ตลอดชีพ
     *
     * 🛡️ ตั้งใจให้เริ่มจาก 0 ทุกแถว = ไม่มีการแบนย้อนหลัง
     *   (signal row เก่ามี counter ค้างจาก logic เดิม — ห้ามเอามาใช้ตัดสิน)
     *
     * ⚠️ ALTER TABLE — ใช้ hasColumn เช็คทีละคอลัมน์ (ห้าม hasTable + return)
     */
    public function up(): void
    {
        Schema::table('fortune_contact_signals', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_contact_signals', 'wl_link_count')) {
                $table->unsignedInteger('wl_link_count')->default(0)->after('active_days');
            }
            if (! Schema::hasColumn('fortune_contact_signals', 'wl_link_days')) {
                $table->unsignedSmallInteger('wl_link_days')->default(0)->after('wl_link_count');
            }
            if (! Schema::hasColumn('fortune_contact_signals', 'wl_last_link_day')) {
                $table->date('wl_last_link_day')->nullable()->after('last_spam_day');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_contact_signals', function (Blueprint $table) {
            foreach (['wl_link_count', 'wl_link_days', 'wl_last_link_day'] as $col) {
                if (Schema::hasColumn('fortune_contact_signals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
