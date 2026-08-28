<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มสวิตช์ "การ์ดทางเข้าในแชท" (2026-08-28)
 *
 * - entry_cards_on_chat : ลูกค้า **พิมพ์** ขอดวงรายวัน / ขอดูฟรี → ตอบเป็นการ์ด 2 ใบ
 *                         [🎁 รับดวงฟรี] + [👑 VIP มีค่าครู] แทนข้อความ + ปุ่ม quick reply
 *
 * ⚠️ ต่างจาก entry_cards_on_dm ตรง "ใครเริ่ม":
 *     entry_cards_on_dm   = **เรา** ยิง DM ตามคอมเมนต์/ไลก์ (ขาออก · funnel เย็น)
 *     entry_cards_on_chat = **ลูกค้า** ทักมาเอง (ขาเข้า · คนสนใจอยู่แล้ว)
 *   จึงห้ามใช้สวิตช์ตัวเดียวกัน — เจ้าของอาจอยากเปิดฝั่งหนึ่งแต่ปิดอีกฝั่ง
 *
 * ⚠️ default = **เปิด** (ต่างจากอีก 2 ตัวที่ default ปิด)
 *    เจ้าของสั่งตรง ๆ ในรอบนี้ว่า "พาไป 2 การ์ด ก่อนดีกว่า ที่มีให้ดูฟรี กับ ดู vip"
 *    ⇒ ถ้า default ปิด = ของที่สั่งไม่ทำงานจนกว่าจะมีคนไปติ๊กในหลังบ้าน
 *    ปิดได้ที่ Admin → ดูดวง → แบนเนอร์/การ์ด โดยไม่ต้อง deploy
 *
 * ⚠️ เป็น ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
 *    เพราะตารางมีอยู่แล้วเสมอ คอลัมน์ใหม่จะไม่ถูกสร้าง
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สวิตช์การ์ดทางเข้าฝั่งแชท
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'entry_cards_on_chat')) {
                $table->boolean('entry_cards_on_chat')
                    ->default(true)
                    ->comment('ลูกค้าพิมพ์ขอดวงรายวัน/ขอดูฟรี → ตอบเป็นการ์ด 2 ใบ (ฟรี/VIP)');
            }
        });
    }

    /**
     * ถอนคอลัมน์สวิตช์การ์ดทางเข้าฝั่งแชท
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'entry_cards_on_chat')) {
                $table->dropColumn('entry_cards_on_chat');
            }
        });
    }
};
