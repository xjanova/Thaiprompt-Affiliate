<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม ENUM values ใหม่ในคอลัมน์ type ของตาราง platform_wallets
     * - admin_shop: รายได้ร้านแอดมิน
     * - admin_services: รายได้บริการแอดมิน
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่
        if (! Schema::hasTable('platform_wallets')) {
            return;
        }

        // ตรวจสอบว่า ENUM มี admin_shop อยู่แล้วหรือไม่
        $columnType = DB::selectOne("SHOW COLUMNS FROM platform_wallets WHERE Field = 'type'");

        if ($columnType && str_contains($columnType->Type, 'admin_shop')) {
            // ENUM มี admin_shop อยู่แล้ว ไม่ต้องทำอะไร
            return;
        }

        // แก้ไข ENUM เพื่อเพิ่ม values ใหม่
        DB::statement("ALTER TABLE platform_wallets MODIFY COLUMN type ENUM(
            'platform_fee',
            'vat',
            'mlm_pool',
            'reserve',
            'refund_pool',
            'admin_shop',
            'admin_services',
            'other'
        ) DEFAULT 'platform_fee'");
    }

    /**
     * ลบ ENUM values ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        if (! Schema::hasTable('platform_wallets')) {
            return;
        }

        // กลับไปใช้ ENUM เดิม (ลบ admin_shop, admin_services)
        // หมายเหตุ: จะ error ถ้ามีข้อมูลที่ใช้ type เหล่านี้อยู่
        DB::statement("ALTER TABLE platform_wallets MODIFY COLUMN type ENUM(
            'platform_fee',
            'vat',
            'mlm_pool',
            'reserve',
            'refund_pool',
            'other'
        ) DEFAULT 'platform_fee'");
    }
};
