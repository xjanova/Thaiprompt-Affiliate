<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สวิตช์เปิด/ปิด "พฤติกรรมเชิงรุก" ของบอท (รวมไว้หน้า celtic-cross เพื่อตั้งค่าง่าย)
     *
     * - enable_sales_pitch         เปิด/ปิด AI เสนอเริ่มดูดวงเอง ([OFFER_FORTUNE]) — default เปิด
     * - enable_bill_payment_nudge  เปิด/ปิด เตือนบิลค้าง + nudge "กดพร้อมบูชาครู" — default เปิด
     *   (ถามก่อนยกเลิกบิล ใช้ของเดิม fortune_consent_cancel_enabled — แค่ย้ายมาโชว์หน้านี้)
     *
     * default = true → ค่าเดิม (พฤติกรรมไม่เปลี่ยนจนกว่าแอดมินปิดเอง)
     * ⚠️ ALTER TABLE — เช็คคอลัมน์ก่อนเพิ่ม
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_sales_pitch')) {
                $table->boolean('enable_sales_pitch')
                    ->default(true)
                    ->after('consent_gate_bypass');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_bill_payment_nudge')) {
                $table->boolean('enable_bill_payment_nudge')
                    ->default(true)
                    ->after('enable_sales_pitch');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['enable_bill_payment_nudge', 'enable_sales_pitch'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
