<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บวันเกิดที่ได้จากโหมด DM ดูดวงรายวัน
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — เป็นการเพิ่มคอลัมน์ (ALTER TABLE)
     *
     * 🌙 (2026-07-31) ทำไมเก็บที่ fortune_user_credits ไม่ใช่สร้างแถว fortune_readings:
     *   1. แถว reading ใหม่ต้องเลือก conversation_status ให้ดี ไม่งั้นกลายเป็น
     *      "บทสนทนา active ผี" ที่บล็อกการดูดวงจริง (เคยเกิดแล้วกับ pro_session_active
     *      ค้าง — ลูกค้า 82 คนดูดวงไม่ได้)
     *   2. findLatestBirthdate() เรียงด้วย latest('updated_at') → แถวใหม่จะ **กลบ**
     *      วันเกิดที่ลูกค้ากรอกในบิลที่จ่ายเงินจริง ถ้า parse ผิดเท่ากับทำลายข้อมูล
     *      ลูกค้าจ่ายเงินด้วยข้อมูลจากช่องทางฟรี
     *   3. reading_type ใหม่จะไหลเข้าหน้าสถิติ/รายงานยอด/อัตราค่าแนะนำ
     *   ตารางนี้เป็น 1 แถวต่อ 1 ลูกค้าอยู่แล้ว (มี DM tracking ครบ) จึงเหมาะที่สุด
     */
    public function up(): void
    {
        Schema::table('fortune_user_credits', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_user_credits', 'birth_date')) {
                $table->date('birth_date')->nullable()
                    ->comment('วันเดือนปีเกิดเต็ม (ได้จากโหมด DM ดูดวงรายวัน)');
            }

            // รู้แค่วันในสัปดาห์ (ลูกค้าตอบ "วันจันทร์" เฉย ๆ) — 0=อาทิตย์ … 6=เสาร์
            // เก็บแยกเพราะใช้ส่งดวงรายวันได้เลยโดยไม่ต้องมีวันเดือนปีครบ
            if (! Schema::hasColumn('fortune_user_credits', 'birth_day')) {
                $table->unsignedTinyInteger('birth_day')->nullable()
                    ->comment('วันเกิดในสัปดาห์ 0=อาทิตย์ … 6=เสาร์ (ตรงกับ horoscope_daily_predictions.birth_day)');
            }

            if (! Schema::hasColumn('fortune_user_credits', 'birth_date_source')) {
                $table->string('birth_date_source', 32)->nullable()
                    ->comment('ที่มา: daily_dm_button | daily_dm_text');
            }

            if (! Schema::hasColumn('fortune_user_credits', 'birth_date_at')) {
                $table->timestamp('birth_date_at')->nullable()
                    ->comment('เวลาที่ได้ข้อมูลวันเกิดมา');
            }

            // 📊 สถิติโหมด daily — ต้อง log ตั้งแต่วันแรก ไม่งั้นประเมินโหมดย้อนหลังไม่ได้
            if (! Schema::hasColumn('fortune_user_credits', 'daily_dm_asked_at')) {
                $table->timestamp('daily_dm_asked_at')->nullable()
                    ->comment('ส่ง DM ชวนบอกวันเกิดครั้งล่าสุดเมื่อไหร่');
            }

            if (! Schema::hasColumn('fortune_user_credits', 'daily_dm_answered_at')) {
                $table->timestamp('daily_dm_answered_at')->nullable()
                    ->comment('ลูกค้าตอบวันเกิดกลับมาครั้งล่าสุดเมื่อไหร่');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_user_credits', function (Blueprint $table) {
            foreach ([
                'birth_date',
                'birth_day',
                'birth_date_source',
                'birth_date_at',
                'daily_dm_asked_at',
                'daily_dm_answered_at',
            ] as $column) {
                if (Schema::hasColumn('fortune_user_credits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
