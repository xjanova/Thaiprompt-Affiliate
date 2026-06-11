<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🚫 (2026-06-11) เพิ่ม Abuse Auto-Ban toggles
     *
     * Feature: ตรวจเจอคำหยาบคายรุนแรง (ชัดเจน ไม่กำกวม) ซ้ำครบเกณฑ์ → แบนอัตโนมัติทันที
     * (owner: "ถ้าวิเคราะห์แล้วใช้คำหยาบคายรุนแรง ต้องรีบแบน ไม่ควรปล่อยนาน")
     *
     * เกราะป้องกัน false-ban:
     *   - ใช้รายการคำหยาบ "รุนแรงชัดเจน" เท่านั้น (ไม่นับ กู/มึง เดี่ยวๆ — ลูกค้าโกรธใช้ได้)
     *   - ต้องโดนซ้ำ ≥ abuse_auto_ban_min_strikes ข้อความ (default 2) ภายใน 24 ชม.
     *   - ห้ามแบนคนเคยจ่ายเงินจริง (userHasPaidHistory) + คนมี paid active reading
     *   - ห้ามแบน persona วิกฤต (mental_unstable / drugged) — ส่ง hotline แทน
     *
     * Default: ปิด — admin opt-in ผ่าน DB UPDATE (เหมือน enable_abuse_clapback)
     *
     * Columns:
     *   - enable_abuse_auto_ban (bool, default false) → master switch
     *   - abuse_auto_ban_min_strikes (int, default 2) → จำนวนข้อความหยาบรุนแรงก่อนแบน
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_abuse_auto_ban')) {
                $table->boolean('enable_abuse_auto_ban')
                    ->default(false)
                    ->after('abuse_clapback_use_grok')
                    ->comment('🚫 auto-ban คำหยาบรุนแรงซ้ำครบเกณฑ์ — default ปิด (admin opt-in)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'abuse_auto_ban_min_strikes')) {
                $table->unsignedTinyInteger('abuse_auto_ban_min_strikes')
                    ->default(2)
                    ->after('enable_abuse_auto_ban')
                    ->comment('จำนวนข้อความหยาบรุนแรง (ภายใน 24 ชม.) ก่อน auto-ban — ขั้นต่ำ 1');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'abuse_auto_ban_min_strikes')) {
                $table->dropColumn('abuse_auto_ban_min_strikes');
            }

            if (Schema::hasColumn('fortune_telling_settings', 'enable_abuse_auto_ban')) {
                $table->dropColumn('enable_abuse_auto_ban');
            }
        });
    }
};
