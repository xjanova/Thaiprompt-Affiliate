<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: แก้ไข kyc_status enum ใน users table ให้รวม 'not_submitted' และ 'draft'
 *
 * ปัญหา:
 * - create_users_comprehensive_v3.php กำหนด enum เป็น ['pending', 'approved', 'rejected', 'expired']
 * - แต่โค้ดใน DashboardController พยายามบันทึกค่า 'not_submitted'
 * - และ KycController พยายาม sync ค่า 'draft' จาก kyc_verifications.status
 * - ทำให้เกิด "Data truncated for column 'kyc_status'" error
 *
 * วิธีแก้:
 * - เพิ่ม 'not_submitted' และ 'draft' เข้าไปใน enum values
 */
return new class extends Migration
{
    /**
     * แก้ไข kyc_status enum ให้รวม 'not_submitted' และ 'draft'
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users และ column kyc_status มีอยู่
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'kyc_status')) {
            return;
        }

        // แก้ไข enum ให้รวม 'not_submitted' และ 'draft'
        // ใช้ raw SQL เพราะ Laravel ไม่รองรับการแก้ไข enum โดยตรง
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `kyc_status` ENUM('not_submitted', 'draft', 'pending', 'approved', 'rejected', 'expired') NULL DEFAULT NULL COMMENT 'สถานะ KYC'");
    }

    /**
     * Revert กลับไปใช้ enum เดิม
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'kyc_status')) {
            return;
        }

        // ⚠️ ก่อน revert ต้องเปลี่ยนค่า 'not_submitted' และ 'draft' เป็น null ก่อน
        // เพื่อป้องกัน data truncation error
        DB::table('users')
            ->whereIn('kyc_status', ['not_submitted', 'draft'])
            ->update(['kyc_status' => null]);

        // Revert กลับไปใช้ enum เดิม
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `kyc_status` ENUM('pending', 'approved', 'rejected', 'expired') NULL DEFAULT NULL COMMENT 'สถานะ KYC'");
    }
};
