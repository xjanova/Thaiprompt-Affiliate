<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * อัพเดท ENUM ของ column 'type' ในตาราง game_settings
     * เพื่อรองรับ float และ decimal
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่หรือไม่
        if (!Schema::hasTable('game_settings')) {
            return;
        }

        // ใช้ raw SQL เพื่ออัพเดท ENUM column
        // Laravel ไม่รองรับการแก้ไข ENUM โดยตรงผ่าน Schema Builder
        DB::statement("
            ALTER TABLE `game_settings`
            MODIFY COLUMN `type` ENUM('string', 'integer', 'float', 'decimal', 'boolean', 'json')
            NOT NULL DEFAULT 'string'
        ");

        $this->command->info('✅ อัพเดท ENUM column สำหรับ game_settings.type สำเร็จ');
    }

    /**
     * ย้อนกลับการเปลี่ยนแปลง
     *
     * @return void
     */
    public function down(): void
    {
        // ตรวจสอบว่าตารางมีอยู่หรือไม่
        if (!Schema::hasTable('game_settings')) {
            return;
        }

        // ย้อนกลับเป็น ENUM เดิม (อาจทำให้ข้อมูล float/decimal หายได้)
        DB::statement("
            ALTER TABLE `game_settings`
            MODIFY COLUMN `type` ENUM('string', 'integer', 'boolean', 'json')
            NOT NULL DEFAULT 'string'
        ");

        $this->command->info('⚠️  ย้อนกลับ ENUM column สำหรับ game_settings.type');
    }
};
