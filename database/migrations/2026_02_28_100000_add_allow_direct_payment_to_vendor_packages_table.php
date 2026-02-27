<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ allow_direct_payment ในตาราง vendor_packages
     *
     * ฟีเจอร์นี้เปิดให้เฉพาะแพคเกจ Enterprise เท่านั้น
     * เมื่อเปิด → ร้านค้าสามารถรับชำระเงินเข้าบัญชีตนเองได้โดยตรง
     * ผ่านระบบ SMS Payment Gateway
     */
    public function up(): void
    {
        Schema::table('vendor_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_packages', 'allow_direct_payment')) {
                $table->boolean('allow_direct_payment')
                    ->default(false)
                    ->after('allow_marketing_tools')
                    ->comment('อนุญาตให้รับชำระเงินเข้าบัญชีตนเอง (Enterprise only)');
            }
        });
    }

    /**
     * ลบคอลัมน์ allow_direct_payment
     */
    public function down(): void
    {
        Schema::table('vendor_packages', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_packages', 'allow_direct_payment')) {
                $table->dropColumn('allow_direct_payment');
            }
        });
    }
};
