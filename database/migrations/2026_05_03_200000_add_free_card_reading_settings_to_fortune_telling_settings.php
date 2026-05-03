<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎁 (2026-05-03) เพิ่มการตั้งค่าระบบ "ทำนายฟรี 1 ใบ ครั้งเดียว ครั้งแรกที่เข้ามา"
 *
 * เปลี่ยนแนวคิด:
 *   เดิม (max_free_readings) — ดูดวงพื้นฐานฟรี N ครั้ง/วัน (text-based)
 *   ใหม่ (enable_free_card_reading) — เปิด/ปิดระบบ "ทำนายฟรี 1 ใบ"
 *                                     เฉพาะลูกค้าใหม่ครั้งแรกของ platform
 *
 * Flow ใหม่:
 *   1. ลูกค้าใหม่ DM แรก → เห็นปุ่ม "🎁 ทำนายฟรี (1 ใบ)" + "🔹 ดูดวง 39฿" + "🔮 99฿"
 *   2. กดฟรี → จั่วไพ่ 1 ใบ (จิตสัมผัสดวงสมพงษ์) → Gemini ทำนายสถานการณ์ปัจจุบัน + ทางออก
 *   3. ทำนายเสร็จ → AI ชวนซื้อ 39/99 เนียน + Quick Reply
 *   4. ปฏิเสธ → คำลา + ปรัชญาชีวิต (ไม่ฮาร์ดเซล ไม่ตบท้าย)
 *
 * เก็บ max_free_readings ไว้ใน DB เพื่อ backward compat (ค่าเดิม) แต่ไม่ใช้แล้ว
 */
return new class extends Migration
{
    /**
     * เพิ่ม columns ใหม่สำหรับระบบฟรีรูปแบบใหม่
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // 🎁 toggle เปิด/ปิดระบบทำนายฟรี 1 ใบ — ครั้งเดียวต่อ platform_user_id
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_free_card_reading')) {
                $table->boolean('enable_free_card_reading')
                    ->default(true)
                    ->after('enable_celtic_cross')
                    ->comment('🎁 เปิดระบบทำนายฟรี 1 ใบ — เฉพาะลูกค้าใหม่ครั้งแรก/platform');
            }

            // 📰 บริบทข่าวบ้านเมือง/เศรษฐกิจปัจจุบัน — admin update เพื่อให้ AI inject ในคำทำนาย
            //    เช่น "เศรษฐกิจชะลอตัว, การเลือกตั้งใกล้เข้ามา, น้ำมันแพง"
            //    AI จะใช้ผูกกับสถานการณ์ที่ลูกค้ากำลังเผชิญอยู่
            if (! Schema::hasColumn('fortune_telling_settings', 'free_card_news_context')) {
                $table->text('free_card_news_context')
                    ->nullable()
                    ->after('enable_free_card_reading')
                    ->comment('📰 บริบทข่าว/เหตุการณ์บ้านเมืองปัจจุบัน — inject ลง prompt Gemini');
            }
        });
    }

    /**
     * ลบ columns ใหม่ (rollback)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'free_card_news_context')) {
                $table->dropColumn('free_card_news_context');
            }
            if (Schema::hasColumn('fortune_telling_settings', 'enable_free_card_reading')) {
                $table->dropColumn('enable_free_card_reading');
            }
        });
    }
};
