<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⏰🛡️ (2026-06-12) Bill Timeout 3 ชม. + Bill-Troll Guard
 *
 * เจ้าของสั่ง:
 *   1. "บิลยกเลิกโดยระบบเร็วไป ให้ปรับใหม่เป็น 3 ชั่วโมง บางคนลืม"
 *      → เพิ่ม bill_payment_timeout_minutes (default 180) — แอดมินปรับได้เอง
 *      → แยกจาก conversation timeout เดิม (30 นาที) ที่ใช้กับ state อื่นๆ
 *   2. "สร้างบิลโดยไม่ชำระ 2 บิลแล้ว บิลที่ 3 เตือนก่อน — ถ้าไม่ชำระใน 3 ชม. แบนถาวร"
 *      → เพิ่ม enable_bill_troll_ban (default true) — toggle ระบบแบนคนสร้างบิลเล่นๆ
 *      → strike นับเฉพาะ 3 วันย้อนหลัง + จ่ายสำเร็จล้างทันที (เห็นใจคนโอนไม่เป็น)
 *
 * @see App\Services\Fortune\BillTrollGuardService
 * @see App\Models\FortuneReading::billTimeoutMinutes()
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ bill_payment_timeout_minutes + enable_bill_troll_ban
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'bill_payment_timeout_minutes')) {
                // nullable — กันแอดมินล้างช่องแล้ว save error ; โค้ดมี fallback 180 รองรับ
                $table->integer('bill_payment_timeout_minutes')
                    ->nullable()
                    ->default(180)
                    ->after('deep_reading_price')
                    ->comment('อายุบิลรอชำระ (นาที) ก่อนระบบยกเลิกอัตโนมัติ — default 180 (3 ชม.)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'enable_bill_troll_ban')) {
                $table->boolean('enable_bill_troll_ban')
                    ->nullable()
                    ->default(true)
                    ->after('bill_payment_timeout_minutes')
                    ->comment('แบนถาวรคนสร้างบิลเล่นๆ ไม่ชำระ 3 ครั้งใน 3 วัน (เตือนก่อนตอนบิลที่ 3)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'bill_payment_timeout_minutes')) {
                $table->dropColumn('bill_payment_timeout_minutes');
            }

            if (Schema::hasColumn('fortune_telling_settings', 'enable_bill_troll_ban')) {
                $table->dropColumn('enable_bill_troll_ban');
            }
        });
    }
};
