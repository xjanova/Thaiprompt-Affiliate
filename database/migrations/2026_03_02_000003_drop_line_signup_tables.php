<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * ลบตาราง LINE Membership Signup ทั้งหมด
 *
 * ระบบนี้ถูกแทนที่ด้วย auto-registration เมื่อ follow LINE OA ตลาดสด
 * ไม่ต้อง signup 8 ขั้นตอนอีกต่อไป
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'line_signup_step_logs',
            'line_signup_conversations',
            'line_signup_reward_claims',
            'line_signup_rewards',
            'line_signup_invitations',
            'line_signup_sessions',
            'line_signup_templates',
            'line_signup_flows',
            'line_signup_settings',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // ไม่สร้างตารางคืน — ระบบนี้ถูกลบถาวร
    }
};
