<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปิดระบบ affiliate สำหรับดูดวงเป็นค่าเริ่มต้น
     *
     * อัพเดท record ที่มีอยู่แล้วให้ fortune_affiliate_enabled = true
     * เพื่อให้ระบบลงทะเบียนสมาชิกอัตโนมัติทำงานทันทีหลัง migrate
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_telling_settings') && Schema::hasColumn('fortune_telling_settings', 'fortune_affiliate_enabled')) {
            DB::table('fortune_telling_settings')
                ->where('fortune_affiliate_enabled', false)
                ->update(['fortune_affiliate_enabled' => true]);
        }
    }

    /**
     * Rollback: ปิดระบบ affiliate กลับเป็น false
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings') && Schema::hasColumn('fortune_telling_settings', 'fortune_affiliate_enabled')) {
            DB::table('fortune_telling_settings')
                ->update(['fortune_affiliate_enabled' => false]);
        }
    }
};
