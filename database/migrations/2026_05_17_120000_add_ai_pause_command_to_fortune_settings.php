<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม ai_pause_command field สำหรับ Admin manual takeover
     *
     * พื้นหลัง (2026-05-17): ลูกค้าบ่นว่า auto-takeover ผ่าน FB echo
     * ไม่เวิร์ค (delivery issue + false-positive) → เปลี่ยนเป็น manual control
     *
     * - /aistop = admin pause bot (ai_pause_command)
     * - /aistart หรือ /ai (legacy) = admin resume bot (ai_resume_command)
     *
     * Customer "คุยกับคน" → เปลี่ยนเป็น alert-only (ไม่ auto-takeover)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ⚠️ ห้ามใช้ Schema::hasTable() + return — ทำให้คอลัมน์ใหม่ไม่ถูกสร้าง
            if (! Schema::hasColumn('fortune_telling_settings', 'ai_pause_command')) {
                $table->string('ai_pause_command', 50)
                    ->default('/aistop')
                    ->after('ai_resume_command')
                    ->comment('คำสั่งให้บอทหยุดทำงาน (admin manual pause via FB echo)');
            }
        });
    }

    /**
     * ลบคอลัมน์ ai_pause_command
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'ai_pause_command')) {
                $table->dropColumn('ai_pause_command');
            }
        });
    }
};
