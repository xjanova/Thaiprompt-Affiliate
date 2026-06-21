<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม celtic_pick_voice_delay_sec — หน่วงเวลา (วินาที) ก่อนเล่นเสียง "เลือกไพ่ใบต่อไป"
     *
     * owner spec: หลังลูกค้าเปิดไพ่ใบที่แล้ว ถ้าเงียบเกิน N วินาที (ยังไม่กดเปิดใบต่อไป)
     * → cron ส่งเสียงกระตุ้น 1 ครั้งต่อใบ. ถ้ากดก่อนครบเวลา = ไม่เล่น
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_pick_voice_delay_sec')) {
                $table->unsignedInteger('celtic_pick_voice_delay_sec')
                    ->default(45)
                    ->after('system_voice_enabled')
                    ->comment('หน่วงเวลา(วินาที)ก่อนเล่นเสียงเลือกไพ่ใบต่อไป (เงียบเกินนี้ค่อยเล่น) 0=ปิด');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'celtic_pick_voice_delay_sec')) {
                $table->dropColumn('celtic_pick_voice_delay_sec');
            }
        });
    }
};
