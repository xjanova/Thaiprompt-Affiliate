<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แก้ไขปัญหาคอลัมน์ cashback ที่หายไปในตาราง products
     *
     * ปัญหา: Migration เก่าใช้ Schema::hasColumn() ภายใน Schema::table() callback
     * ซึ่งอาจไม่ทำงานถูกต้องใน Laravel บางเวอร์ชัน
     *
     * วิธีแก้: ตรวจสอบคอลัมน์ก่อนเข้า Schema::table() callback
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง products มีอยู่หรือไม่
        if (! Schema::hasTable('products')) {
            return;
        }

        // ตรวจสอบคอลัมน์ก่อนเพิ่ม (ต้องทำนอก Schema::table callback)
        $hasCustomerCashback = Schema::hasColumn('products', 'customer_cashback');
        $hasCashbackPercentage = Schema::hasColumn('products', 'cashback_percentage');
        $hasPvValue = Schema::hasColumn('products', 'pv_value');
        $hasVatPercentage = Schema::hasColumn('products', 'vat_percentage');

        // เพิ่มคอลัมน์ที่ยังไม่มี
        Schema::table('products', function (Blueprint $table) use (
            $hasCustomerCashback,
            $hasCashbackPercentage,
            $hasPvValue,
            $hasVatPercentage
        ) {
            // เพิ่มคอลัมน์ customer_cashback ถ้ายังไม่มี
            if (! $hasCustomerCashback) {
                $table->decimal('customer_cashback', 10, 2)
                    ->default(0)
                    ->after('commission_rate')
                    ->comment('Fixed cashback amount for customer');
            }

            // เพิ่มคอลัมน์ cashback_percentage ถ้ายังไม่มี
            if (! $hasCashbackPercentage) {
                // ใช้ after ที่ถูกต้องขึ้นอยู่กับว่า customer_cashback มีอยู่หรือไม่
                $afterColumn = $hasCustomerCashback ? 'customer_cashback' : 'commission_rate';
                $table->decimal('cashback_percentage', 5, 2)
                    ->default(0)
                    ->after($afterColumn)
                    ->comment('Cashback percentage of price');
            }

            // เพิ่มคอลัมน์ pv_value ถ้ายังไม่มี
            if (! $hasPvValue) {
                $afterColumn = 'cashback_percentage';
                if (! $hasCashbackPercentage && ! $hasCustomerCashback) {
                    $afterColumn = 'commission_rate';
                } elseif (! $hasCashbackPercentage) {
                    $afterColumn = 'customer_cashback';
                }
                $table->decimal('pv_value', 10, 2)
                    ->default(0)
                    ->after($afterColumn)
                    ->comment('PV สำหรับคำนวณ MLM Commission (0 = ไม่มีคอม)');
            }

            // เพิ่มคอลัมน์ vat_percentage ถ้ายังไม่มี
            if (! $hasVatPercentage) {
                $afterColumn = 'pv_value';
                if (! $hasPvValue) {
                    $afterColumn = $hasCashbackPercentage ? 'cashback_percentage' : 'commission_rate';
                }
                $table->decimal('vat_percentage', 5, 2)
                    ->default(7.00)
                    ->after($afterColumn)
                    ->comment('ภาษี VAT (%) หักจากผู้ขาย');
            }
        });
    }

    /**
     * ย้อนกลับ migration
     *
     * หมายเหตุ: ไม่ลบคอลัมน์เพราะอาจมีข้อมูลอยู่แล้ว
     */
    public function down(): void
    {
        // ไม่ลบคอลัมน์เพื่อป้องกันการสูญหายของข้อมูล
    }
};
