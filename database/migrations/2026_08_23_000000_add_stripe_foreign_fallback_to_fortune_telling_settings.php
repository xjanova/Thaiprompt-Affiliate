<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌍 (2026-08-23) เลน Stripe เฉพาะลูกค้าต่างประเทศ — แยกจากเมนูเลือกวิธีจ่ายของลูกค้าไทย
 *
 * ที่มา: บิล FTU-260822-U7900 (Celtic 99฿) ลูกค้าอยู่ต่างประเทศ โอนพร้อมเพย์ไม่ได้
 *        ระบบ Stripe สร้างไว้ครบและคีย์ live ใช้ได้จริง แต่ `enable_stripe_payment` ปิดอยู่
 *        → ทุกเส้นทางที่พาไป Stripe ตายหมด
 *
 * ทำไมต้องมีสวิตช์ตัวใหม่ ไม่ใช้ตัวเดิม:
 *   `enable_stripe_payment` = สวิตช์ "เมนูเลือกวิธีจ่าย" — เปิดแล้วลูกค้า **ทุกคน**
 *   รวมคนไทยจะเจอเมนู 2 ปุ่มก่อนสร้างบิล (เพิ่ม 1 สเตปให้คนส่วนใหญ่)
 *
 *   `enable_stripe_foreign_fallback` = เลนสำรอง — โผล่เฉพาะตอนลูกค้า **บอกเองว่า**
 *   อยู่ต่างประเทศ / ไม่มีพร้อมเพย์ / ขอจ่ายบัตร → funnel ไทยไม่กระทบเลย
 *
 * default = false (ต้องเปิดที่ /admin/fortune/settings เอง)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สวิตช์เลน Stripe ต่างประเทศ
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — เป็น ALTER TABLE เพิ่มคอลัมน์
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_stripe_foreign_fallback')) {
                $table->boolean('enable_stripe_foreign_fallback')
                    ->default(false)
                    ->after('enable_stripe_payment')
                    ->comment('เปิดเลนจ่ายบัตรสำหรับลูกค้าต่างประเทศ (ไม่กระทบเมนูของลูกค้าไทย)');
            }
        });
    }

    /**
     * ลบคอลัมน์สวิตช์เลน Stripe ต่างประเทศ
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_stripe_foreign_fallback')) {
                $table->dropColumn('enable_stripe_foreign_fallback');
            }
        });
    }
};
