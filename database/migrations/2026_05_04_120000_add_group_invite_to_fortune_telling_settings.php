<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌟 (2026-05-04) เพิ่มการตั้งค่าระบบเชิญลูกค้าเข้ากลุ่ม Facebook + แคมเปญดูฟรีรายเดือน
 *
 * แนวคิด:
 *   - บอทส่งปุ่ม "เข้ากลุ่ม" ใน Messenger ทุกจุดสำคัญ (greeting / หลังทำนาย / comment DM)
 *   - กลุ่มเป็น private — มี post รายเดือนชวนคลิกลิงก์รับสิทธิ์ดูไพ่ฟรี 1 ใบ/เดือน
 *   - HMAC token + (psid, month_key) unique → กดซ้ำเดือนเดียวกันไม่ได้
 *   - รีเซ็ตอัตโนมัติทุกวันที่ 1 (month_key เปลี่ยนเอง)
 *
 * 💡 Auto-post in group: ใช้ Facebook "Linked Pages" feature ของกลุ่ม
 *    (Group Settings → Manage → Linked Pages → link page) — ไม่ต้องเขียน API
 *    เพราะ Meta deprecate `publish_to_groups` permission ไปแล้ว April 2024
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // 🌟 URL กลุ่ม Facebook — ใช้กับปุ่มเชิญทุกจุด
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_group_url')) {
                $table->string('fortune_group_url', 500)
                    ->nullable()
                    ->after('free_card_news_context')
                    ->comment('🌟 URL กลุ่ม Facebook (เช่น facebook.com/groups/123456)');
            }

            // 🎯 toggle เปิด/ปิดปุ่มเชิญเข้ากลุ่ม
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_group_invite_enabled')) {
                $table->boolean('fortune_group_invite_enabled')
                    ->default(false)
                    ->after('fortune_group_url')
                    ->comment('🎯 เปิดปุ่มเชิญเข้ากลุ่มใน Messenger');
            }

            // 💬 ข้อความเชิญเข้ากลุ่ม (ส่งคู่กับปุ่ม)
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_group_invite_message')) {
                $table->text('fortune_group_invite_message')
                    ->nullable()
                    ->after('fortune_group_invite_enabled')
                    ->comment('💬 ข้อความเชิญเข้ากลุ่ม Facebook');
            }

            // 🎁 toggle เปิดแคมเปญรับสิทธิ์ดูฟรีรายเดือน (ลิงก์ในโพสต์กลุ่ม)
            if (! Schema::hasColumn('fortune_telling_settings', 'monthly_free_claim_enabled')) {
                $table->boolean('monthly_free_claim_enabled')
                    ->default(false)
                    ->after('fortune_group_invite_message')
                    ->comment('🎁 เปิดแคมเปญรับสิทธิ์ดูฟรีรายเดือน (ลิงก์ในโพสต์กลุ่ม)');
            }

            // 🔐 secret key สำหรับ HMAC token (ถ้าไม่มี = ใช้ APP_KEY)
            if (! Schema::hasColumn('fortune_telling_settings', 'monthly_free_claim_secret')) {
                $table->string('monthly_free_claim_secret', 128)
                    ->nullable()
                    ->after('monthly_free_claim_enabled')
                    ->comment('🔐 secret key สำหรับ HMAC token (เว้นว่าง = ใช้ APP_KEY)');
            }

            // 📝 ข้อความขอบคุณหลัง claim สำเร็จ
            if (! Schema::hasColumn('fortune_telling_settings', 'monthly_free_claim_success_message')) {
                $table->text('monthly_free_claim_success_message')
                    ->nullable()
                    ->after('monthly_free_claim_secret')
                    ->comment('📝 ข้อความตอบกลับหลัง claim สำเร็จ');
            }

            // ⛔ ข้อความเมื่อ claim ซ้ำในเดือนเดียวกัน
            if (! Schema::hasColumn('fortune_telling_settings', 'monthly_free_claim_already_message')) {
                $table->text('monthly_free_claim_already_message')
                    ->nullable()
                    ->after('monthly_free_claim_success_message')
                    ->comment('⛔ ข้อความเมื่อกด claim ซ้ำเดือนเดียวกัน');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'monthly_free_claim_already_message',
                'monthly_free_claim_success_message',
                'monthly_free_claim_secret',
                'monthly_free_claim_enabled',
                'fortune_group_invite_message',
                'fortune_group_invite_enabled',
                'fortune_group_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
