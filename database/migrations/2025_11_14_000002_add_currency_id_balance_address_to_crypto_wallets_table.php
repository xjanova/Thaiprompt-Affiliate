<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ currency_id, balance, และ address ให้กับตาราง crypto_wallets
     * เพื่อรองรับการใช้งานใน TPIXWalletController
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('crypto_wallets', function (Blueprint $table) {
            // ตรวจสอบและเพิ่มคอลัมน์ currency_id
            if (!Schema::hasColumn('crypto_wallets', 'currency_id')) {
                $table->foreignId('currency_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('crypto_currencies')
                    ->onDelete('cascade')
                    ->comment('สกุลเงิน crypto ที่ wallet นี้ใช้');
            }

            // ตรวจสอบและเพิ่มคอลัมน์ balance
            if (!Schema::hasColumn('crypto_wallets', 'balance')) {
                $table->decimal('balance', 36, 18)
                    ->default(0)
                    ->after('wallet_type')
                    ->comment('ยอดคงเหลือใน wallet');
            }

            // ตรวจสอบและเพิ่มคอลัมน์ address
            if (!Schema::hasColumn('crypto_wallets', 'address')) {
                $table->string('address', 255)
                    ->nullable()
                    ->after('balance')
                    ->comment('ที่อยู่ wallet สำหรับ custodial wallet');

                // เพิ่ม unique index สำหรับ address
                $table->unique('address', 'crypto_wallets_address_unique');
            }

            // เพิ่ม index เพื่อเพิ่มประสิทธิภาพ query
            if (!Schema::hasColumn('crypto_wallets', 'currency_id')) {
                $table->index(['user_id', 'currency_id'], 'user_currency_idx');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('crypto_wallets', function (Blueprint $table) {
            // ลบ indexes ก่อน
            if (Schema::hasColumn('crypto_wallets', 'currency_id')) {
                $table->dropIndex('user_currency_idx');
            }

            if (Schema::hasColumn('crypto_wallets', 'address')) {
                $table->dropUnique('crypto_wallets_address_unique');
            }

            // ลบ foreign key
            if (Schema::hasColumn('crypto_wallets', 'currency_id')) {
                $table->dropForeign(['currency_id']);
            }

            // ลบคอลัมน์
            $table->dropColumn([
                'currency_id',
                'balance',
                'address',
            ]);
        });
    }
};
