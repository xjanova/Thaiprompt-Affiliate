<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌐 (2026-11-03) ธง "FB web-only" — ปิดการดูดวง/รับเงินในแชท Facebook ทั้งหมด
 *
 * เจ้าของสั่ง: FB แบนเพจที่มีสลิปโอนเงินใน Messenger → ย้ายลูกค้า FB ทุกคนไปดูดวงที่เว็บ จันทรา.online
 * ไม่มีทางถอยกลับไปออกบิลบน FB (fail-closed)
 *
 * - facebook_web_only  : สวิตช์หลัก (default ปิด = พฤติกรรมเดิม 100%)
 * - fb_web_only_since  : เวลาที่เปิดสวิตช์ล่าสุด (จุดตัด T0 + audit) — โมเดลตั้งให้เองตอนเปิด
 *
 * ⚠️ ธงนี้อ่านจากแถวกลางเท่านั้น — FortuneTellingSetting::applyPageOverrides ห้ามเพจสาขา override
 * ⚠️ ไม่แตะ fortune_bot_mode (คุม LINE ด้วย) และไม่แตะ enable_web_fortune_button
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ธง FB web-only (เช็คคอลัมน์ทีละตัว — ห้ามใช้ hasTable + return กับ ALTER)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'facebook_web_only', function (Blueprint $table) {
                $table->boolean('facebook_web_only')->default(false);
            });

            $this->safeAddColumn($table, 'fortune_telling_settings', 'fb_web_only_since', function (Blueprint $table) {
                $table->timestamp('fb_web_only_since')->nullable();
            });
        });
    }

    /**
     * ลบคอลัมน์ธง FB web-only
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', ['facebook_web_only', 'fb_web_only_since']);
    }
};
