<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ system_voice_enabled (master toggle เสียงระบบ) ในตาราง fortune_telling_settings
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return (คอลัมน์ใหม่จะไม่ถูกสร้าง)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // master toggle: เปิด/ปิดการส่ง "เสียงระบบ" ทั้งหมด (default ปิด = ปลอดภัย)
            if (! Schema::hasColumn('fortune_telling_settings', 'system_voice_enabled')) {
                $table->boolean('system_voice_enabled')
                    ->default(false)
                    ->after('voice_storage_config')
                    ->comment('master toggle ส่งเสียงระบบ (กล่องกระตุ้น/กติกา/วันเกิด ฯลฯ) — FB เท่านั้น');
            }
        });
    }

    /**
     * ลบคอลัมน์ system_voice_enabled
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'system_voice_enabled')) {
                $table->dropColumn('system_voice_enabled');
            }
        });
    }
};
