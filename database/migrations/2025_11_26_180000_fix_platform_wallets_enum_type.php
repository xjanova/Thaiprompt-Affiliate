<?php

/**
 * Migration แก้ไข ENUM type ของ platform_wallets
 *
 * เพิ่ม admin_shop และ admin_services เข้าไปใน ENUM
 * Migration นี้จะ run เสมอเพราะเป็นไฟล์ใหม่
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม ENUM values ใหม่ในคอลัมน์ type ของตาราง platform_wallets
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ไม่ต้อง rollback เพราะ ENUM ใหม่รองรับ backward compatible
    }
};
