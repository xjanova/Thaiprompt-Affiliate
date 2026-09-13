<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ✈️ เพิ่ม Telegram เป็นช่องทางที่ 3 ของบอทแม่หมอ (2026-09-13)
 *
 * เจ้าของสั่ง: "เราใช้เทเลแกรม ดูดวงได้ไหม ... เรามาเริ่มเลย"
 *   - จ่ายเงิน = QR พร้อมเพย์ + SMS Checker เหมือน FB/LINE
 *   - ขอบเขต = ครบทุกแพคเกจเท่า FB/LINE
 *
 * 1) fortune_telling_settings — ค่าตั้งบอท Telegram
 *    - telegram_enabled        : สวิตช์เปิดรับข้อความ (default **ปิด** — เปิดเองหลังทดสอบครบ)
 *    - telegram_bot_token      : token จาก @BotFather (เข้ารหัสด้วย cast 'encrypted' ในโมเดล)
 *      ⚠️ เจ้าของกรอกเองที่หน้าหลังบ้านเท่านั้น
 *    - telegram_bot_username   : ชื่อบอท (ไม่มี @) — ได้จาก getMe ตอนทดสอบ ใช้สร้างลิงก์ t.me/<ชื่อ>
 *    - telegram_webhook_secret : ค่าลับที่ Telegram แนบมาใน header ทุกครั้ง (เข้ารหัส)
 *    - telegram_webhook_set_at : เวลาที่ตั้ง webhook สำเร็จล่าสุด (แสดงสถานะในหลังบ้าน)
 *
 * 2) ขยาย enum 2 คอลัมน์ที่รู้จักแค่ facebook/line — ไม่งั้นแบน/บันทึก QA ลูกค้า Telegram ไม่ได้ (SQL error)
 *    - fortune_user_bans.platform
 *    - fortune_admin_qa.source_platform
 *
 * ⚠️ เป็น ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ค่าตั้ง Telegram + ขยาย enum
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_enabled')) {
                $table->boolean('telegram_enabled')->default(false)
                    ->comment('Telegram: เปิดรับข้อความจากบอท');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_bot_token')) {
                $table->text('telegram_bot_token')->nullable()
                    ->comment('Telegram: bot token จาก @BotFather (encrypted)');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_bot_username')) {
                $table->string('telegram_bot_username', 64)->nullable()
                    ->comment('Telegram: ชื่อบอท ไม่มี @ (จาก getMe)');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_webhook_secret')) {
                $table->text('telegram_webhook_secret')->nullable()
                    ->comment('Telegram: secret_token ของ webhook (encrypted)');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_webhook_set_at')) {
                $table->timestamp('telegram_webhook_set_at')->nullable()
                    ->comment('Telegram: ตั้ง webhook สำเร็จล่าสุดเมื่อ');
            }
        });

        // enum เปลี่ยนด้วย Schema builder ไม่ได้ (ต้องมี doctrine) → ALTER ตรง เฉพาะ MySQL/MariaDB
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasTable('fortune_user_bans') && Schema::hasColumn('fortune_user_bans', 'platform')) {
            DB::statement("ALTER TABLE `fortune_user_bans` MODIFY `platform` ENUM('facebook','line','telegram') NOT NULL COMMENT 'แพลตฟอร์มที่ถูกแบน'");
        }

        if (Schema::hasTable('fortune_admin_qa') && Schema::hasColumn('fortune_admin_qa', 'source_platform')) {
            // ⚠️ ต่อค่าใหม่ไว้ "ท้ายสุด" เท่านั้น — แทรกกลาง enum = MySQL ต้อง copy ทั้งตาราง (ล็อกการเขียนตอน deploy)
            DB::statement("ALTER TABLE `fortune_admin_qa` MODIFY `source_platform` ENUM('facebook','line','manual','telegram') NOT NULL DEFAULT 'facebook'");
        }
    }

    /**
     * ถอนคอลัมน์ Telegram + หด enum กลับ
     *
     * ⚠️ หด enum ได้ก็ต่อเมื่อไม่มีแถว 'telegram' เหลือ — ถ้ามี ต้องย้าย/ลบแถวเหล่านั้นก่อน
     *    (จงใจไม่ลบให้อัตโนมัติ: แถวแบน/QA เป็นข้อมูลที่แอดมินตั้งใจสร้าง)
     */
    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            if (Schema::hasTable('fortune_user_bans')
                && ! DB::table('fortune_user_bans')->where('platform', 'telegram')->exists()) {
                DB::statement("ALTER TABLE `fortune_user_bans` MODIFY `platform` ENUM('facebook','line') NOT NULL COMMENT 'แพลตฟอร์มที่ถูกแบน'");
            }

            if (Schema::hasTable('fortune_admin_qa')
                && ! DB::table('fortune_admin_qa')->where('source_platform', 'telegram')->exists()) {
                DB::statement("ALTER TABLE `fortune_admin_qa` MODIFY `source_platform` ENUM('facebook','line','manual') NOT NULL DEFAULT 'facebook'");
            }
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['telegram_enabled', 'telegram_bot_token', 'telegram_bot_username', 'telegram_webhook_secret', 'telegram_webhook_set_at'] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
