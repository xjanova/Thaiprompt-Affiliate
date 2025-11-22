<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม payment fields ใน tpix_configurations table
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('tpix_configurations', function (Blueprint $table) {
            // Payment Information
            if (!Schema::hasColumn('tpix_configurations', 'wallet_address')) {
                $table->string('wallet_address', 42)->nullable()->after('deployer_private_key')
                    ->comment('Web3 wallet address ที่ใช้ชำระเงิน');
            }

            if (!Schema::hasColumn('tpix_configurations', 'payment_amount')) {
                $table->decimal('payment_amount', 18, 2)->nullable()->after('wallet_address')
                    ->comment('จำนวนเงินที่ชำระ (TPIX)');
            }

            if (!Schema::hasColumn('tpix_configurations', 'payment_tx_hash')) {
                $table->string('payment_tx_hash', 66)->nullable()->after('payment_amount')
                    ->comment('Transaction hash ของการชำระเงิน');
            }

            if (!Schema::hasColumn('tpix_configurations', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'refunded'])
                    ->default('pending')->after('payment_tx_hash')
                    ->comment('สถานะการชำระเงิน');
            }

            if (!Schema::hasColumn('tpix_configurations', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_status')
                    ->comment('เวลาที่ verify payment สำเร็จ');
            }

            // Pricing Breakdown
            if (!Schema::hasColumn('tpix_configurations', 'pricing_breakdown')) {
                $table->json('pricing_breakdown')->nullable()->after('payment_verified_at')
                    ->comment('รายละเอียดการคำนวณราคา');
            }

            if (!Schema::hasColumn('tpix_configurations', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 18, 2)->nullable()->after('pricing_breakdown')
                    ->comment('ยอดรวมก่อนหักส่วนลด');
            }

            if (!Schema::hasColumn('tpix_configurations', 'discount_amount')) {
                $table->decimal('discount_amount', 18, 2)->default(0)->after('subtotal_amount')
                    ->comment('ส่วนลด');
            }

            if (!Schema::hasColumn('tpix_configurations', 'discount_code')) {
                $table->string('discount_code', 50)->nullable()->after('discount_amount')
                    ->comment('รหัสส่วนลดที่ใช้');
            }

            // Refund Information
            if (!Schema::hasColumn('tpix_configurations', 'refund_amount')) {
                $table->decimal('refund_amount', 18, 2)->nullable()->after('discount_code')
                    ->comment('จำนวนเงินที่คืน (กรณี deploy ล้มเหลว)');
            }

            if (!Schema::hasColumn('tpix_configurations', 'refund_tx_hash')) {
                $table->string('refund_tx_hash', 66)->nullable()->after('refund_amount')
                    ->comment('Transaction hash ของการคืนเงิน');
            }

            if (!Schema::hasColumn('tpix_configurations', 'refund_reason')) {
                $table->text('refund_reason')->nullable()->after('refund_tx_hash')
                    ->comment('เหตุผลการคืนเงิน');
            }

            if (!Schema::hasColumn('tpix_configurations', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_reason')
                    ->comment('เวลาที่คืนเงิน');
            }

            // Premium Services
            if (!Schema::hasColumn('tpix_configurations', 'premium_services')) {
                $table->json('premium_services')->nullable()->after('refunded_at')
                    ->comment('บริการเสริมที่เลือก (audit, marketing, etc.)');
            }

            // Referral & Promotions
            if (!Schema::hasColumn('tpix_configurations', 'referral_code')) {
                $table->string('referral_code', 50)->nullable()->after('premium_services')
                    ->comment('รหัส referral ที่ใช้');
            }

            if (!Schema::hasColumn('tpix_configurations', 'is_early_adopter')) {
                $table->boolean('is_early_adopter')->default(false)->after('referral_code')
                    ->comment('เป็น Early Adopter หรือไม่');
            }

            // Indexes สำหรับ query ที่ใช้บ่อย
            if (!Schema::hasColumn('tpix_configurations', 'wallet_address')) {
                $table->index('wallet_address', 'idx_wallet_address');
            }
            if (!Schema::hasColumn('tpix_configurations', 'payment_status')) {
                $table->index('payment_status', 'idx_payment_status');
            }
            if (!Schema::hasColumn('tpix_configurations', 'payment_tx_hash')) {
                $table->index('payment_tx_hash', 'idx_payment_tx');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('tpix_configurations', function (Blueprint $table) {
            // ลบ indexes ก่อน
            $table->dropIndex('idx_wallet_address');
            $table->dropIndex('idx_payment_status');
            $table->dropIndex('idx_payment_tx');

            // ลบ columns
            $columns = [
                'wallet_address',
                'payment_amount',
                'payment_tx_hash',
                'payment_status',
                'payment_verified_at',
                'pricing_breakdown',
                'subtotal_amount',
                'discount_amount',
                'discount_code',
                'refund_amount',
                'refund_tx_hash',
                'refund_reason',
                'refunded_at',
                'premium_services',
                'referral_code',
                'is_early_adopter',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tpix_configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
