<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคุณสมบัติ spending limits และ wallet integration สำหรับ NFC cards
     *
     * คุณสมบัติที่เพิ่ม:
     * - Daily/Monthly/Transaction spending limits
     * - Enable/Disable card functionality
     * - TPIX wallet integration
     * - Current period spending tracking
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('nfc_cards', function (Blueprint $table) {
            // Spending Limits (วงเงินการใช้จ่าย)
            if (!Schema::hasColumn('nfc_cards', 'daily_spending_limit')) {
                $table->decimal('daily_spending_limit', 15, 2)->default(10000)->after('credit_limit')
                    ->comment('วงเงินการใช้จ่ายสูงสุดต่อวัน');
            }

            if (!Schema::hasColumn('nfc_cards', 'monthly_spending_limit')) {
                $table->decimal('monthly_spending_limit', 15, 2)->default(100000)->after('daily_spending_limit')
                    ->comment('วงเงินการใช้จ่ายสูงสุดต่อเดือน');
            }

            if (!Schema::hasColumn('nfc_cards', 'transaction_limit')) {
                $table->decimal('transaction_limit', 15, 2)->default(5000)->after('monthly_spending_limit')
                    ->comment('วงเงินสูงสุดต่อธุรกรรม');
            }

            // Current Period Spending (ยอดใช้จ่ายในช่วงปัจจุบัน)
            if (!Schema::hasColumn('nfc_cards', 'daily_spent')) {
                $table->decimal('daily_spent', 15, 2)->default(0)->after('transaction_limit')
                    ->comment('ยอดที่ใช้จ่ายไปแล้ววันนี้');
            }

            if (!Schema::hasColumn('nfc_cards', 'monthly_spent')) {
                $table->decimal('monthly_spent', 15, 2)->default(0)->after('daily_spent')
                    ->comment('ยอดที่ใช้จ่ายไปแล้วเดือนนี้');
            }

            if (!Schema::hasColumn('nfc_cards', 'last_reset_daily')) {
                $table->date('last_reset_daily')->nullable()->after('monthly_spent')
                    ->comment('วันที่ reset ยอดใช้จ่ายรายวันล่าสุด');
            }

            if (!Schema::hasColumn('nfc_cards', 'last_reset_monthly')) {
                $table->date('last_reset_monthly')->nullable()->after('last_reset_daily')
                    ->comment('วันที่ reset ยอดใช้จ่ายรายเดือนล่าสุด');
            }

            // Enable/Disable Functionality (เปิด/ปิดการใช้งาน)
            if (!Schema::hasColumn('nfc_cards', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('is_paired')
                    ->comment('เปิด/ปิดการใช้งานโดย user (แยกจาก status)');
            }

            if (!Schema::hasColumn('nfc_cards', 'disabled_at')) {
                $table->timestamp('disabled_at')->nullable()->after('is_enabled')
                    ->comment('วันเวลาที่ปิดการใช้งาน');
            }

            if (!Schema::hasColumn('nfc_cards', 'disabled_reason')) {
                $table->string('disabled_reason')->nullable()->after('disabled_at')
                    ->comment('เหตุผลที่ปิดการใช้งาน');
            }

            // Wallet Integration (ผูกกับ Wallet)
            if (!Schema::hasColumn('nfc_cards', 'wallet_id')) {
                $table->foreignId('wallet_id')->nullable()->after('user_id')
                    ->constrained('wallets')->onDelete('set null')
                    ->comment('Wallet ที่ผูกกับการ์ด');
            }

            // TPIX Integration
            if (!Schema::hasColumn('nfc_cards', 'supports_tpix')) {
                $table->boolean('supports_tpix')->default(false)->after('wallet_id')
                    ->comment('รองรับการชำระด้วย TPIX token หรือไม่');
            }

            if (!Schema::hasColumn('nfc_cards', 'tpix_wallet_address')) {
                $table->string('tpix_wallet_address')->nullable()->after('supports_tpix')
                    ->comment('TPIX wallet address สำหรับรับ/ส่ง token');
            }

            // Additional Settings
            if (!Schema::hasColumn('nfc_cards', 'auto_topup_enabled')) {
                $table->boolean('auto_topup_enabled')->default(false)->after('tpix_wallet_address')
                    ->comment('เปิดใช้งาน auto top-up หรือไม่');
            }

            if (!Schema::hasColumn('nfc_cards', 'auto_topup_threshold')) {
                $table->decimal('auto_topup_threshold', 15, 2)->default(100)->after('auto_topup_enabled')
                    ->comment('ยอดเงินที่เหลือต่ำกว่านี้จะ auto top-up');
            }

            if (!Schema::hasColumn('nfc_cards', 'auto_topup_amount')) {
                $table->decimal('auto_topup_amount', 15, 2)->default(1000)->after('auto_topup_threshold')
                    ->comment('จำนวนเงินที่เติมแต่ละครั้ง auto top-up');
            }

            // Indexes สำหรับ performance
            if (!Schema::hasColumn('nfc_cards', 'is_enabled')) {
                $table->index('is_enabled');
            }
            if (!Schema::hasColumn('nfc_cards', 'wallet_id')) {
                $table->index('wallet_id');
            }
            if (!Schema::hasColumn('nfc_cards', 'supports_tpix')) {
                $table->index('supports_tpix');
            }
        });
    }

    /**
     * ยกเลิกการเพิ่มคอลัมน์
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('nfc_cards', function (Blueprint $table) {
            // ลบ indexes ก่อน
            $table->dropIndex(['is_enabled']);
            $table->dropIndex(['wallet_id']);
            $table->dropIndex(['supports_tpix']);

            // ลบ foreign key
            $table->dropForeign(['wallet_id']);

            // ลบคอลัมน์ทั้งหมด
            $table->dropColumn([
                'daily_spending_limit',
                'monthly_spending_limit',
                'transaction_limit',
                'daily_spent',
                'monthly_spent',
                'last_reset_daily',
                'last_reset_monthly',
                'is_enabled',
                'disabled_at',
                'disabled_reason',
                'wallet_id',
                'supports_tpix',
                'tpix_wallet_address',
                'auto_topup_enabled',
                'auto_topup_threshold',
                'auto_topup_amount',
            ]);
        });
    }
};
