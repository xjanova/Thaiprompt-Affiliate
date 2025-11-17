<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม 'button' ใน input_type ENUM ของตาราง line_signup_flows
     *
     * แก้ไข bug ที่ LineSignupFlowSeeder พยายามใส่ 'button' แต่ ENUM ไม่มีค่านี้
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่จริง
        if (!Schema::hasTable('line_signup_flows')) {
            return;
        }

        // ⚠️ IMPORTANT: MySQL ไม่สามารถ ALTER ENUM โดยตรงได้
        // ต้องใช้ raw SQL เพื่อเปลี่ยน column definition
        DB::statement("ALTER TABLE line_signup_flows MODIFY COLUMN input_type ENUM(
            'text',
            'phone',
            'email',
            'name',
            'confirm',
            'choice',
            'button',
            'none'
        ) DEFAULT 'text'");
    }

    /**
     * ลบ 'button' จาก input_type ENUM
     *
     * @return void
     */
    public function down(): void
    {
        // ตรวจสอบว่าตารางมีอยู่จริง
        if (!Schema::hasTable('line_signup_flows')) {
            return;
        }

        // คืนค่า ENUM กลับไปเป็นเดิม (ไม่มี 'button')
        DB::statement("ALTER TABLE line_signup_flows MODIFY COLUMN input_type ENUM(
            'text',
            'phone',
            'email',
            'name',
            'confirm',
            'choice',
            'none'
        ) DEFAULT 'text'");
    }
};
