<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ address_type ให้กับตาราง crypto_addresses
     * เพื่อระบุประเภทของที่อยู่ (deposit, withdrawal, etc.)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            // ตรวจสอบและเพิ่มคอลัมน์ address_type
            if (!Schema::hasColumn('crypto_addresses', 'address_type')) {
                $table->enum('address_type', ['deposit', 'withdrawal', 'general'])
                    ->default('general')
                    ->after('network')
                    ->comment('ประเภทของที่อยู่: deposit=รับเงิน, withdrawal=ถอนเงิน, general=ทั่วไป');

                // เพิ่ม index เพื่อเพิ่มประสิทธิภาพในการ query
                $table->index(['crypto_wallet_id', 'address_type'], 'wallet_address_type_idx');
            }
        });
    }

    /**
     * ลบคอลัมน์ address_type
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            // ลบ index ก่อน
            if (Schema::hasColumn('crypto_addresses', 'address_type')) {
                $table->dropIndex('wallet_address_type_idx');
            }

            // ลบคอลัมน์
            if (Schema::hasColumn('crypto_addresses', 'address_type')) {
                $table->dropColumn('address_type');
            }
        });
    }
};
