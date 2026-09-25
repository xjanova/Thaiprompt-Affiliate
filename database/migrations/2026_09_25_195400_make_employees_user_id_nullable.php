<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ให้ employees.user_id เป็น NULL ได้
 *
 * ระบบพนักงานของร้าน (seller.staff.*) เพิ่มพนักงานหน้าร้านที่ "ไม่มีบัญชีผู้ใช้ในระบบ" ได้
 * แต่คอลัมน์ user_id เป็น NOT NULL ไม่มีค่าเริ่มต้น → StaffController@store บันทึกไม่ได้เลย (MySQL strict)
 * พนักงานที่ผูกบัญชีผู้ใช้ยังใส่ user_id ได้เหมือนเดิม (FK ไป users ยังอยู่ครบ)
 */
return new class extends Migration
{
    /**
     * เปลี่ยน user_id เป็น nullable (เฉพาะ MySQL — คง foreign key เดิมไว้)
     */
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'user_id')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE employees MODIFY user_id BIGINT UNSIGNED NULL');
    }

    /**
     * ย้อนกลับ: ทำได้เฉพาะเมื่อไม่มีแถวที่ user_id เป็น NULL (กันข้อมูลพนักงานหาย)
     */
    public function down(): void
    {
        if (! Schema::hasTable('employees') || DB::getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('employees')->whereNull('user_id')->exists()) {
            return;
        }

        DB::statement('ALTER TABLE employees MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};
