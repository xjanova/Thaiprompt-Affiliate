<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลด admin_handover_timeout ของ row ที่มีอยู่แล้วเป็น 1 นาที
     *
     * เหตุผล:
     * - เก่า: 15 นาที — AI หยุดนานเกินไปเมื่อแอดมินพิมพ์ ทำให้ลูกค้ารอ
     * - ใหม่: 1 นาที — แอดมินตอบเสร็จไม่ทันโดน AI แทรก แต่ AI กลับมาเร็ว
     *
     * - เปิด admin_handover_enabled = true ในทุก row ด้วย (default: ปิด → เปิด)
     *   เพื่อให้ระบบ takeover ทำงานเลยหลัง deploy
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // อัพเดต row ที่มีอยู่ — ลดเวลาเป็น 1 นาที + เปิดระบบ
        DB::table('fortune_telling_settings')->update([
            'admin_handover_timeout' => 1,
            'admin_handover_enabled' => true,
        ]);
    }

    /**
     * Rollback: คืนค่าเป็น 15 นาที (default เก่า)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        DB::table('fortune_telling_settings')->update([
            'admin_handover_timeout' => 15,
        ]);
    }
};
