<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ขยาย pos_staff_assignments.pin_code เป็น varchar(255)
 *
 * PIN ของพนักงานถูกเก็บแบบ bcrypt hash (60 ตัวอักษร — PosStaffAssignment::setPinCodeAttribute)
 * แต่คอลัมน์เดิมเป็น varchar(6) → ตั้ง PIN ให้พนักงานไม่ได้เลย (Data too long บน MySQL strict)
 */
return new class extends Migration
{
    /**
     * ขยายความยาวคอลัมน์ (เฉพาะ MySQL)
     */
    public function up(): void
    {
        if (! Schema::hasTable('pos_staff_assignments') || ! Schema::hasColumn('pos_staff_assignments', 'pin_code')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE pos_staff_assignments MODIFY pin_code VARCHAR(255) NULL');
    }

    /**
     * ไม่ย่อคอลัมน์กลับ — hash ที่บันทึกไปแล้วยาวกว่า 6 ตัว จะถูกตัดจนใช้ไม่ได้
     */
    public function down(): void
    {
        // ตั้งใจไม่ทำอะไร (ปลอดภัยต่อข้อมูล PIN ที่บันทึกแล้ว)
    }
};
