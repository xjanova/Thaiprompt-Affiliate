<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม fields สำหรับระบบ Referral ใน users table
 *
 * แก้ไขปัญหา: MobileApiController::register() ต้องการ referral_code และ sponsor_id
 * แต่ columns เหล่านี้ไม่มีใน users table
 *
 * @see App\Http\Controllers\Api\V1\MobileApiController::register()
 */
return new class extends Migration
{
    /**
     * เพิ่ม columns สำหรับระบบ referral
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Referral code สำหรับแชร์ให้คนอื่นสมัคร
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 20)->nullable()->unique()->after('email')->comment('รหัสแนะนำสมาชิก');
            }

            // Sponsor ID - ผู้แนะนำที่ใช้ referral code
            if (!Schema::hasColumn('users', 'sponsor_id')) {
                $table->unsignedBigInteger('sponsor_id')->nullable()->after('referral_code')->comment('ID ของผู้แนะนำ');

                // เพิ่ม foreign key ถ้ายังไม่มี
                // ใช้ try-catch เพื่อป้องกัน error กรณี foreign key มีอยู่แล้ว
            }

            // เพิ่ม index สำหรับ performance
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->index('referral_code');
            }
            if (!Schema::hasColumn('users', 'sponsor_id')) {
                $table->index('sponsor_id');
            }
        });

        // เพิ่ม foreign key แยกต่างหาก เพื่อป้องกัน error
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('sponsor_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key อาจมีอยู่แล้ว - ข้าม
        }
    }

    /**
     * ลบ columns ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ลบ foreign key ก่อน
            try {
                $table->dropForeign(['sponsor_id']);
            } catch (\Exception $e) {
                // ไม่มี foreign key - ข้าม
            }

            // ลบ columns
            $columns = ['referral_code', 'sponsor_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
