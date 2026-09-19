<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔔 บอทแจ้งเตือนแอดมินทาง Telegram — **คนละตัวกับบอทแม่หมอ** (2026-09-19)
 *
 * เจ้าของยืนยัน: "bot แจ้งเตือน กับบอท แม่หมอ คนละตัวกันนะ"
 * ⇒ ห้ามยืม `telegram_bot_token` ของแม่หมอมาส่งแจ้งเตือนเด็ดขาด
 *   (บอทแม่หมอคุยกับลูกค้า · บอทแจ้งเตือนคุยกับแอดมินเท่านั้น)
 *
 * ทำไมต้องมีคอลัมน์ ไม่ใช้ .env อย่างเดียว:
 *   เจ้าของแก้ .env บน prod เองไม่สะดวก และค่าใน .env ถูกเขียนทับตอน deploy ได้
 *   ⇒ กรอกในหลังบ้านจบในหน้าเว็บ · `.env` ยังใช้ได้อยู่ในฐานะตัวสำรอง (ดู TelegramAlertService)
 *
 * - telegram_alert_bot_token : token ของบอทแจ้งเตือน จาก @BotFather (encrypted)
 * - telegram_alert_chat_id   : chat id ของแอดมินที่จะรับแจ้งเตือน
 *   ⚠️ **ต้องมีทั้งคู่** — บอท Telegram ส่งหาคนที่ไม่เคยทักมันไม่ได้
 *
 * ⚠️ เป็น ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ค่าตั้งบอทแจ้งเตือน
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_alert_bot_token')) {
                $table->text('telegram_alert_bot_token')->nullable()
                    ->comment('Telegram แจ้งเตือนแอดมิน: bot token (encrypted) — คนละตัวกับบอทแม่หมอ');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'telegram_alert_chat_id')) {
                $table->string('telegram_alert_chat_id', 64)->nullable()
                    ->comment('Telegram แจ้งเตือนแอดมิน: chat id ปลายทาง');
            }
        });
    }

    /**
     * ถอนคอลัมน์บอทแจ้งเตือน
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['telegram_alert_bot_token', 'telegram_alert_chat_id'] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
