<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์ตั้งค่าระบบเทคโอเวอร์ (Admin Takeover) ในตาราง fortune_telling_settings
 *
 * ฟิลด์เดิมที่เก็บไว้ใช้ต่อ:
 * - admin_handover_enabled — เปิด/ปิดระบบ
 * - admin_handover_timeout — เวลา default (นาที) เมื่อแอดมินพิมพ์อัตโนมัติ
 *
 * ฟิลด์ใหม่:
 * - ai_resume_command — คำสั่งให้ AI กลับมาทำงาน (default: /ai)
 * - customer_handoff_keywords — คำที่ลูกค้าพิมพ์แล้วให้หยุด AI (JSON array)
 * - takeover_notify_customer — แจ้งลูกค้าเมื่อแม่หมอเข้ามาคุยหรือไม่
 * - takeover_customer_message — ข้อความแจ้งลูกค้าเมื่อแม่หมอเข้ามา
 * - takeover_resume_message — ข้อความเมื่อ AI กลับมาทำงาน
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ใหม่ในตาราง fortune_telling_settings
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // คำสั่งให้ AI กลับมาทำงาน (แอดมินพิมพ์ใน LINE/Facebook)
            if (! Schema::hasColumn('fortune_telling_settings', 'ai_resume_command')) {
                $table->string('ai_resume_command', 50)
                    ->default('/ai')
                    ->after('admin_handover_timeout')
                    ->comment('คำสั่งให้ AI กลับมาทำงาน เช่น /ai /resume');
            }

            // คำที่ลูกค้าพิมพ์แล้วให้หยุด AI (JSON array)
            if (! Schema::hasColumn('fortune_telling_settings', 'customer_handoff_keywords')) {
                $table->json('customer_handoff_keywords')
                    ->nullable()
                    ->after('ai_resume_command')
                    ->comment('คำที่ลูกค้าพิมพ์แล้วให้หยุด AI');
            }

            // แจ้งลูกค้าเมื่อแม่หมอเข้ามาคุยหรือไม่
            if (! Schema::hasColumn('fortune_telling_settings', 'takeover_notify_customer')) {
                $table->boolean('takeover_notify_customer')
                    ->default(true)
                    ->after('customer_handoff_keywords')
                    ->comment('แจ้งลูกค้าเมื่อแม่หมอเข้ามาคุย');
            }

            // ข้อความแจ้งลูกค้าเมื่อแม่หมอเข้ามา
            if (! Schema::hasColumn('fortune_telling_settings', 'takeover_customer_message')) {
                $table->text('takeover_customer_message')
                    ->nullable()
                    ->after('takeover_notify_customer')
                    ->comment('ข้อความแจ้งลูกค้าเมื่อแม่หมอเข้ามา');
            }

            // ข้อความเมื่อ AI กลับมาทำงาน
            if (! Schema::hasColumn('fortune_telling_settings', 'takeover_resume_message')) {
                $table->text('takeover_resume_message')
                    ->nullable()
                    ->after('takeover_customer_message')
                    ->comment('ข้อความเมื่อ AI กลับมาทำงานแทนแม่หมอ');
            }
        });
    }

    /**
     * ย้อนกลับ: ลบคอลัมน์
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'ai_resume_command',
                'customer_handoff_keywords',
                'takeover_notify_customer',
                'takeover_customer_message',
                'takeover_resume_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
