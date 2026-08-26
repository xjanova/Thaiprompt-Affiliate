<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มสวิตช์ "การ์ดทางเข้า" ให้ระบบดูดวง (2026-08-26)
 *
 * - entry_cards_on_dm       : DM ตอบคอมเมนต์/กดไลก์ ส่งเป็นการ์ด 2 ใบ (ฟรี / VIP) แทนข้อความล้วน
 * - birth_day_cards_enabled : ตอนถามวันเกิด ส่งเป็นการ์ด 7 ใบมีรูป แทนปุ่ม quick reply
 *
 * ⚠️ default ปิดทั้งคู่ — เส้นนี้คือ funnel หลักของเพจ
 *    เคยเปลี่ยน Stage 1 ของ DM แล้วยอดจ่ายจริงเป็น 0 ทั้งวัน (2026-08-10)
 *    เจ้าของต้องเปิดเองแล้วเทียบยอด
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สวิตช์การ์ดทางเข้า
     *
     * ⚠️ เป็น ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
     *    เพราะตารางมีอยู่แล้วเสมอ คอลัมน์ใหม่จะไม่ถูกสร้าง
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'entry_cards_on_dm')) {
                $table->boolean('entry_cards_on_dm')
                    ->default(false)
                    ->comment('DM ตอบคอมเมนต์เป็นการ์ด 2 ใบ (ฟรี/VIP)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'birth_day_cards_enabled')) {
                $table->boolean('birth_day_cards_enabled')
                    ->default(false)
                    ->comment('ถามวันเกิดด้วยการ์ด 7 วันมีรูป แทนปุ่มข้อความ');
            }
        });
    }

    /**
     * ถอนคอลัมน์สวิตช์การ์ดทางเข้า
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'entry_cards_on_dm')) {
                $table->dropColumn('entry_cards_on_dm');
            }

            if (Schema::hasColumn('fortune_telling_settings', 'birth_day_cards_enabled')) {
                $table->dropColumn('birth_day_cards_enabled');
            }
        });
    }
};
