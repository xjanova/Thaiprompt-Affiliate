<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;
    /**
     * เพิ่ม payment fields ใน tpix_configurations table
     *
     * ใช้ SafeMigration trait เพื่อความปลอดภัยในการเพิ่ม columns และ indexes
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('tpix_configurations', function (Blueprint $table) {
            // Payment Information
            $this->safeAddColumn($table, 'tpix_configurations', 'wallet_address', function ($t) {
                $t->string('wallet_address', 42)->nullable()->after('deploy_cost_tpix')
                    ->comment('Web3 wallet address ที่ใช้ชำระเงิน');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'payment_amount', function ($t) {
                $t->decimal('payment_amount', 18, 2)->nullable()->after('wallet_address')
                    ->comment('จำนวนเงินที่ชำระ (TPIX)');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'payment_tx_hash', function ($t) {
                $t->string('payment_tx_hash', 66)->nullable()->after('payment_amount')
                    ->comment('Transaction hash ของการชำระเงิน');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'payment_status', function ($t) {
                $t->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'refunded'])
                    ->default('pending')->after('payment_tx_hash')
                    ->comment('สถานะการชำระเงิน');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'payment_verified_at', function ($t) {
                $t->timestamp('payment_verified_at')->nullable()->after('payment_status')
                    ->comment('เวลาที่ verify payment สำเร็จ');
            });

            // Pricing Breakdown
            $this->safeAddColumn($table, 'tpix_configurations', 'pricing_breakdown', function ($t) {
                $t->json('pricing_breakdown')->nullable()->after('payment_verified_at')
                    ->comment('รายละเอียดการคำนวณราคา');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'subtotal_amount', function ($t) {
                $t->decimal('subtotal_amount', 18, 2)->nullable()->after('pricing_breakdown')
                    ->comment('ยอดรวมก่อนหักส่วนลด');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'discount_amount', function ($t) {
                $t->decimal('discount_amount', 18, 2)->default(0)->after('subtotal_amount')
                    ->comment('ส่วนลด');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'discount_code', function ($t) {
                $t->string('discount_code', 50)->nullable()->after('discount_amount')
                    ->comment('รหัสส่วนลดที่ใช้');
            });

            // Refund Information
            $this->safeAddColumn($table, 'tpix_configurations', 'refund_amount', function ($t) {
                $t->decimal('refund_amount', 18, 2)->nullable()->after('discount_code')
                    ->comment('จำนวนเงินที่คืน (กรณี deploy ล้มเหลว)');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'refund_tx_hash', function ($t) {
                $t->string('refund_tx_hash', 66)->nullable()->after('refund_amount')
                    ->comment('Transaction hash ของการคืนเงิน');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'refund_reason', function ($t) {
                $t->text('refund_reason')->nullable()->after('refund_tx_hash')
                    ->comment('เหตุผลการคืนเงิน');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'refunded_at', function ($t) {
                $t->timestamp('refunded_at')->nullable()->after('refund_reason')
                    ->comment('เวลาที่คืนเงิน');
            });

            // Premium Services
            $this->safeAddColumn($table, 'tpix_configurations', 'premium_services', function ($t) {
                $t->json('premium_services')->nullable()->after('refunded_at')
                    ->comment('บริการเสริมที่เลือก (audit, marketing, etc.)');
            });

            // Referral & Promotions
            $this->safeAddColumn($table, 'tpix_configurations', 'referral_code', function ($t) {
                $t->string('referral_code', 50)->nullable()->after('premium_services')
                    ->comment('รหัส referral ที่ใช้');
            });

            $this->safeAddColumn($table, 'tpix_configurations', 'is_early_adopter', function ($t) {
                $t->boolean('is_early_adopter')->default(false)->after('referral_code')
                    ->comment('เป็น Early Adopter หรือไม่');
            });
        });

        // เพิ่ม indexes หลังจากเพิ่ม columns เสร็จแล้ว (ปลอดภัยกว่า)
        $this->safeAddIndex('tpix_configurations', 'wallet_address', 'idx_wallet_address');
        $this->safeAddIndex('tpix_configurations', 'payment_status', 'idx_payment_status');
        $this->safeAddIndex('tpix_configurations', 'payment_tx_hash', 'idx_payment_tx');
    }

    /**
     * Reverse the migrations.
     *
     * ลบ indexes และ columns ที่เพิ่มเข้าไปอย่างปลอดภัย
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ indexes ก่อน (ปลอดภัย)
        $this->safeDropIndex('tpix_configurations', 'idx_wallet_address');
        $this->safeDropIndex('tpix_configurations', 'idx_payment_status');
        $this->safeDropIndex('tpix_configurations', 'idx_payment_tx');

        // ลบ columns ทีละตัวอย่างปลอดภัย
        $this->safeDropColumn('tpix_configurations', [
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
        ]);
    }
};
