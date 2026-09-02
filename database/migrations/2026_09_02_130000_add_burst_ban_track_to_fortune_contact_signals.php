<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🚫 (2026-09-02, เจ้าของสั่ง) นับ "ลิงก์/รูปติดต่อกัน" เพื่อแบนอัตโนมัติ
 *
 * กติกา: ส่งลิงก์หรือรูปติดต่อกันครบ 5 ครั้ง → แบน 7 วัน · ทำซ้ำอีกรอบ → ถาวร
 *
 * ทำไมต้องเก็บลง DB ไม่ใช่ Cache:
 *   deploy รัน `cache:clear` ซึ่งเป็น flushdb ทั้งตัว 3 หนต่อรอบ
 *   → ตัวนับบน Cache จะถูกล้างกลางทางทุกครั้งที่ deploy = สแปมเมอร์ได้เริ่มนับใหม่ฟรีๆ
 *
 * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ติดตาม streak ลิงก์/รูป
     */
    public function up(): void
    {
        Schema::table('fortune_contact_signals', function (Blueprint $table) {
            // จำนวนลิงก์/รูปที่ส่งติดต่อกัน (รีเซ็ตเป็น 0 ทันทีที่คุยจริง/กดปุ่ม/จ่ายเงิน)
            if (! Schema::hasColumn('fortune_contact_signals', 'burst_streak')) {
                $table->unsignedInteger('burst_streak')
                    ->default(0)
                    ->after('wl_last_link_day');
            }

            // เวลาข้อความลิงก์/รูปล่าสุดที่นับเข้า streak — ใช้ตัดสินว่า streak เก่าเกินไปแล้วหรือยัง
            if (! Schema::hasColumn('fortune_contact_signals', 'burst_last_at')) {
                $table->timestamp('burst_last_at')
                    ->nullable()
                    ->after('burst_streak');
            }

            // เคยโดนแบนด้วยกฎนี้มาแล้วกี่ครั้ง — ≥1 แปลว่ารอบต่อไปต้องถาวร
            if (! Schema::hasColumn('fortune_contact_signals', 'burst_ban_count')) {
                $table->unsignedInteger('burst_ban_count')
                    ->default(0)
                    ->after('burst_last_at');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_contact_signals', function (Blueprint $table) {
            foreach (['burst_streak', 'burst_last_at', 'burst_ban_count'] as $column) {
                if (Schema::hasColumn('fortune_contact_signals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
