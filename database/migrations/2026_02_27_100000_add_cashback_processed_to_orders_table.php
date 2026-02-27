<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ cashback_processed และ cashback_processed_at ในตาราง orders
     *
     * แก้ bug: CashbackService ไม่สามารถ track ว่า cashback ถูก process แล้วหรือยัง
     * ทำให้เกิด infinite recursion ใน OrderObserver เมื่อ completePayment() ถูกเรียก
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'cashback_processed')) {
                $table->boolean('cashback_processed')
                    ->default(false)
                    ->after('cashback_amount')
                    ->comment('สถานะว่า cashback ถูก process แล้วหรือยัง');
            }

            if (! Schema::hasColumn('orders', 'cashback_processed_at')) {
                $table->timestamp('cashback_processed_at')
                    ->nullable()
                    ->after('cashback_processed')
                    ->comment('เวลาที่ cashback ถูก process');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cashback_processed_at')) {
                $table->dropColumn('cashback_processed_at');
            }
            if (Schema::hasColumn('orders', 'cashback_processed')) {
                $table->dropColumn('cashback_processed');
            }
        });
    }
};
