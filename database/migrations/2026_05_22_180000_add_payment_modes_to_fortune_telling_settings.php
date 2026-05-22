<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💳 (2026-05-22) เพิ่ม payment mode toggle ใน fortune_telling_settings
 *
 * เดิม: enable_stripe_payment เปิด → Stripe + SMS ทั้งคู่ (ลูกค้าเลือก)
 *       SMS-checker เป็น default ปิดไม่ได้
 *
 * ใหม่: รองรับ 3 โหมด
 *   1. SMS-only: enable_sms_payment=true + enable_stripe_payment=false  (ค่าเริ่มต้น, backward compat)
 *   2. Stripe-only: enable_sms_payment=false + enable_stripe_payment=true
 *   3. Both: enable_sms_payment=true + enable_stripe_payment=true (ลูกค้าเลือก)
 *
 * Column:
 *   - enable_sms_payment (bool): เปิดให้รับชำระผ่าน QR ไทย + SMS checker (default true)
 *
 * Note: เมื่อทั้งสองเป็น false → fallback กลับเป็น SMS-only โดย service layer + log warn
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_sms_payment')) {
                $table->boolean('enable_sms_payment')
                    ->default(true)
                    ->after('enable_stripe_payment')
                    ->comment('เปิดรับชำระผ่าน QR ไทย + SMS-checker (default true, backward compat)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_sms_payment')) {
                $table->dropColumn('enable_sms_payment');
            }
        });
    }
};
