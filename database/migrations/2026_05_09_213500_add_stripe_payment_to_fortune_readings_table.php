<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💳 (2026-05-09) เพิ่ม Stripe Checkout payment ในแชท Fortune
 *
 * เดิม: บิลทุกใบใช้ QR PromptPay ไทย + UniquePaymentAmount (random satang)
 *       — ลูกค้าต่างประเทศจ่ายไม่ได้ (ไม่มี QR Thai)
 * ใหม่: ลูกค้าเลือกวิธีชำระก่อนสร้างบิล
 *       - QR ไทย (เดิม, satang random + SMS check)
 *       - Stripe Checkout (บัตรต่างประเทศ, +15 บาท ค่าบริการ, API check)
 *
 * Columns:
 *   - payment_method: enum 'qr_thai' | 'stripe' (default 'qr_thai' = backward compat)
 *   - service_fee: decimal(8,2) (ค่าบริการ Stripe = 15 บาท, 0 ถ้า QR Thai)
 *   - stripe_session_id: Stripe Checkout Session ID (cs_live_xxx / cs_test_xxx)
 *   - stripe_payment_intent_id: Stripe Payment Intent ID (pi_xxx) — เก็บหลังจ่าย
 *   - stripe_paid_at: timestamp ที่ Stripe webhook confirm จ่ายสำเร็จ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            // วิธีชำระเงิน — qr_thai (default) หรือ stripe (บัตรต่างประเทศ)
            if (! Schema::hasColumn('fortune_readings', 'payment_method')) {
                $table->enum('payment_method', ['qr_thai', 'stripe'])
                    ->default('qr_thai')
                    ->after('amount_paid')
                    ->comment('วิธีชำระเงิน: qr_thai=PromptPay ไทย / stripe=บัตรต่างประเทศ');
            }

            // ค่าบริการเพิ่มเติม (เฉพาะ Stripe = 15 บาท)
            if (! Schema::hasColumn('fortune_readings', 'service_fee')) {
                $table->decimal('service_fee', 8, 2)
                    ->default(0)
                    ->after('payment_method')
                    ->comment('ค่าบริการเพิ่มเติม (Stripe=15฿, QR Thai=0)');
            }

            // Stripe Checkout Session ID
            if (! Schema::hasColumn('fortune_readings', 'stripe_session_id')) {
                $table->string('stripe_session_id', 255)
                    ->nullable()
                    ->after('service_fee')
                    ->comment('Stripe Checkout Session ID (cs_live_xxx / cs_test_xxx)');
            }

            // Stripe Payment Intent ID (หลังจ่ายเสร็จ webhook ส่งกลับมา)
            if (! Schema::hasColumn('fortune_readings', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 255)
                    ->nullable()
                    ->after('stripe_session_id')
                    ->comment('Stripe Payment Intent ID (pi_xxx) สำหรับ refund/dispute');
            }

            // เวลาที่ Stripe webhook confirm
            if (! Schema::hasColumn('fortune_readings', 'stripe_paid_at')) {
                $table->timestamp('stripe_paid_at')
                    ->nullable()
                    ->after('stripe_payment_intent_id')
                    ->comment('เวลาที่ Stripe webhook confirm จ่ายสำเร็จ');
            }
        });

        // Index แยก (ภายใน closure จะ chain method ไม่ได้กับ if)
        Schema::table('fortune_readings', function (Blueprint $table) {
            // 🔍 Index สำหรับ webhook lookup โดย session_id (high cardinality)
            try {
                $table->index('stripe_session_id', 'fr_stripe_session_idx');
            } catch (\Throwable $e) {
                // Index อาจมีอยู่แล้ว — ignore
            }

            // 🔍 Index สำหรับ admin filter ตาม payment_method
            try {
                $table->index('payment_method', 'fr_payment_method_idx');
            } catch (\Throwable $e) {
                // ignore duplicate
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            // ลบ indexes ก่อน (กัน drop column fail)
            try {
                $table->dropIndex('fr_stripe_session_idx');
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                $table->dropIndex('fr_payment_method_idx');
            } catch (\Throwable $e) {
                // ignore
            }

            $columns = [
                'stripe_paid_at',
                'stripe_payment_intent_id',
                'stripe_session_id',
                'service_fee',
                'payment_method',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_readings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
