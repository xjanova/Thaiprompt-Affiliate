<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม 'gateway' ให้กับ provider_type enum ในตาราง ai_providers
     *
     * PostXAgent AI Manager ต้องการ provider_type = 'gateway'
     * เพราะทำหน้าที่เป็น gateway เชื่อมต่อหลาย AI providers ผ่าน API เดียว
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่ก่อน
        if (!Schema::hasTable('ai_providers')) {
            return;
        }

        // MySQL: ต้องใช้ raw SQL เพื่อแก้ไข ENUM
        // เพิ่ม 'gateway' เป็นค่าที่อนุญาตใน provider_type
        DB::statement("ALTER TABLE `ai_providers` MODIFY COLUMN `provider_type` ENUM('cloud', 'self-hosted', 'gateway') NOT NULL DEFAULT 'cloud'");
    }

    /**
     * ย้อนกลับการเปลี่ยนแปลง (ลบ 'gateway' ออก)
     *
     * ⚠️ หมายเหตุ: การลบ 'gateway' จะทำให้ records ที่มี provider_type = 'gateway' เกิด error
     * ควรลบหรืออัพเดท records เหล่านั้นก่อนรัน rollback
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('ai_providers')) {
            return;
        }

        // ก่อน rollback ต้องอัพเดท records ที่มี provider_type = 'gateway' ให้เป็นค่าอื่น
        DB::table('ai_providers')
            ->where('provider_type', 'gateway')
            ->update(['provider_type' => 'cloud']);

        // ลบ 'gateway' ออกจาก ENUM
        DB::statement("ALTER TABLE `ai_providers` MODIFY COLUMN `provider_type` ENUM('cloud', 'self-hosted') NOT NULL DEFAULT 'cloud'");
    }
};
