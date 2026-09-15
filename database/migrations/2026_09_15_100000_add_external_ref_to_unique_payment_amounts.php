<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌙 (2026-09-15) ยอดทศนิยมที่จองให้เว็บ จันทรา.online (transaction_type = 'juntraweb_topup')
 *
 * บริบท: บัญชีรับเงิน + มือถือ SMS เครื่องเดียวกันรับเงินของทั้งสองเว็บ → Thaiprompt เป็นผู้จองยอด
 *   ให้ทั้งคู่ผ่าน UniquePaymentAmount::generate() ตัวเดียว ยอดทศนิยมจะได้ไม่ชนข้ามเว็บ
 *
 * ทำไมต้องมีคอลัมน์ใหม่ ไม่ยัด id ของจันทราลง transaction_id:
 *   transaction_id มี FK ไป payment_transactions และมีหลายเส้นค้นด้วย where('transaction_id', ...)
 *   เพื่อตัด/ยกเลิกบิลอีคอมเมิร์ซ (SmsPaymentController, cleanup()) — ถ้าใส่ id ของ wallet_transactions
 *   ฝั่งจันทรา จะไปชนเลข PaymentTransaction ของเราแล้วโดนยกเลิก/ตัดผิดตัว
 *
 * ⚠️ ALTER TABLE เพิ่มคอลัมน์ — เช็ค hasColumn ทีละตัว (ห้าม hasTable+return ก่อน Schema::table)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unique_payment_amounts')) {
            return;
        }

        Schema::table('unique_payment_amounts', function (Blueprint $table) {
            if (! Schema::hasColumn('unique_payment_amounts', 'external_ref')) {
                $table->string('external_ref', 64)->nullable()->after('transaction_type')
                    ->comment('juntraweb_topup: reference_code ฝั่งจันทรา.online (TUP-XXXX)');
            }
            if (! Schema::hasColumn('unique_payment_amounts', 'external_id')) {
                $table->unsignedBigInteger('external_id')->nullable()->after('external_ref')
                    ->comment('juntraweb_topup: wallet_transactions.id ฝั่งจันทรา.online');
            }
        });

        // index ค้นตาม ref (idempotent reserve/release) — try/catch กัน duplicate key name ตอนรันซ้ำ
        try {
            Schema::table('unique_payment_amounts', function (Blueprint $table) {
                $table->index(['transaction_type', 'external_ref'], 'upa_type_external_ref_idx');
            });
        } catch (\Throwable $e) {
            // มี index อยู่แล้ว
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('unique_payment_amounts')) {
            return;
        }

        try {
            Schema::table('unique_payment_amounts', function (Blueprint $table) {
                $table->dropIndex('upa_type_external_ref_idx');
            });
        } catch (\Throwable $e) {
            // ไม่มี index
        }

        Schema::table('unique_payment_amounts', function (Blueprint $table) {
            if (Schema::hasColumn('unique_payment_amounts', 'external_id')) {
                $table->dropColumn('external_id');
            }
            if (Schema::hasColumn('unique_payment_amounts', 'external_ref')) {
                $table->dropColumn('external_ref');
            }
        });
    }
};
