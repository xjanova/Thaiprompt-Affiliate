<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💳 (2026-05-22) บันทึก customer region สำหรับ Stripe payment
 *
 * เดิม: Stripe ทุกบิล +15฿ ค่าบริการ (เพราะสมมติเป็นต่างประเทศ)
 * ใหม่: แยก 2 tier
 *   - 'th' = ลูกค้าในไทย → ไม่บวก fee (ราคาเต็มเท่า QR)
 *   - 'foreign' = ลูกค้าต่างประเทศ → +15฿
 *   - null = ยังไม่ระบุ (เผื่อ legacy reading)
 *
 * Source of truth:
 *   1. customer self-select via Quick Reply button (PAY_METHOD_STRIPE_TH / _FOREIGN)
 *   2. Cross-check จาก customer_details.address.country ที่ Stripe ส่งกลับใน webhook
 *      (audit only — ไม่ override tier เพราะอาจ refund ยุ่ง)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_readings', 'stripe_customer_region')) {
                $table->enum('stripe_customer_region', ['th', 'foreign'])
                    ->nullable()
                    ->after('stripe_paid_at')
                    ->comment('ลูกค้าเลือก/Stripe detect: th=ในไทย ไม่บวกค่าบริการ, foreign=ต่างประเทศ +15฿');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_readings', 'stripe_customer_region')) {
                $table->dropColumn('stripe_customer_region');
            }
        });
    }
};
