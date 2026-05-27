<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🛡️ (2026-05-27) เพิ่ม Abuse Clapback toggles
     *
     * Feature: แม่หมอ "สอน" ลูกค้าหยาบคาย — Grok-3 + psychology + กฎหมายอ้างอิง
     *
     * Default: ปิดทั้งหมด — admin opt-in ผ่าน /admin/fortune/settings
     *
     * Columns:
     *   - enable_abuse_clapback (bool, default false)
     *       → master switch
     *   - abuse_clapback_use_grok (bool, default true)
     *       → ถ้า true ใช้ grok-3-latest เป็น provider หลัก, false = ใช้ chat AI ปกติ
     *
     * ⚠️ ห้ามเปิดถ้า:
     *   - ไม่มี Grok API key ใน pool (จะ fail-back ไป chat ปกติ — tone ไม่ savage พอ)
     *   - ลูกค้ากลุ่มเป้าหมายเป็น senior/sensitive — savage mode ไม่เหมาะ
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_abuse_clapback')) {
                $table->boolean('enable_abuse_clapback')
                    ->default(false)
                    ->after('enable_celtic_enrichment')
                    ->comment('🛡️ เปิด savage clapback mode สำหรับลูกค้าหยาบคาย/หน้าหม้อ — default ปิด (admin opt-in)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'abuse_clapback_use_grok')) {
                $table->boolean('abuse_clapback_use_grok')
                    ->default(true)
                    ->after('enable_abuse_clapback')
                    ->comment('ใช้ Grok-3 (xAI) เป็น provider หลักของ clapback — false = chat AI ปกติ');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'abuse_clapback_use_grok')) {
                $table->dropColumn('abuse_clapback_use_grok');
            }

            if (Schema::hasColumn('fortune_telling_settings', 'enable_abuse_clapback')) {
                $table->dropColumn('enable_abuse_clapback');
            }
        });
    }
};
