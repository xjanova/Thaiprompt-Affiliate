<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💎 (2026-06-07) SlipOK Auto-Provision — ลูกค้าโอนเงิน "ก่อน" สร้างบิล/QR แล้วส่งสลิปมา
 *
 * เดิม: สลิปที่ไม่มีบิลให้กู้ → บอทตรวจแล้ว "ส่งต่อแอดมิน" (verifyNoBillSlipForAdmin) → แอดมินต้องมานั่งสร้างบิล/เปิดไพ่เอง
 * ใหม่: เปิดสวิตช์นี้ → ถ้าสลิปจริง + เข้าบัญชีร้าน + ยอด ≥ ขั้นต่ำ (99) + ไม่ใช่สลิปซ้ำ
 *       → ระบบ "สร้างบิล Celtic + เปิดไพ่ให้เอง" ทันที (โอนขาด → เครดิต+บอกยอดขาด+ให้เติม)
 *       โดยแอดมินไม่ต้องมาดูแล
 *
 * เพิ่ม 1 ค่า config (admin ปรับได้):
 *   - slipok_auto_provision : เปิด/ปิด auto-provision จากสลิปที่ไม่มีบิล (default true — เจ้าของสั่งเปิดไว้)
 *
 * 🛡️ guard ที่ใช้ซ้ำของเดิมทั้งหมด: transRef dedup กันใช้สลิปซ้ำ / เช็คบัญชีปลายทาง /
 *    flood guard / classifier กันเปลืองโควต้า / รับสลิปย้อนหลังไม่เกิน 3 วัน
 *
 * @see App\Services\FortuneConversationService::autoProvisionCelticFromSlip
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ slipok_auto_provision ในตาราง fortune_telling_settings
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // nullable — กันเคสแอดมินล้างช่องแล้ว save error ; โค้ดมี ?? true รองรับ (default เปิด)
            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_auto_provision')) {
                $table->boolean('slipok_auto_provision')
                    ->nullable()
                    ->default(true)
                    ->after('slipok_ban_after_rounds')
                    ->comment('โอนก่อนสร้างบิล/QR → สลิปจริง+เข้าบัญชีเรา+ยอดครบ → สร้างบิล Celtic+เปิดไพ่เอง (ไม่ต้องรอแอดมิน)');
            }
        });
    }

    /**
     * ลบคอลัมน์ slipok_auto_provision
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'slipok_auto_provision')) {
                $table->dropColumn('slipok_auto_provision');
            }
        });
    }
};
