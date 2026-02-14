<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แก้ไขปัญหาคอลัมน์ที่หายไปในตาราง products
     *
     * คอลัมน์ที่เพิ่ม:
     * - pv_value: PV สำหรับคำนวณ MLM Commission
     * - vat_percentage: ภาษี VAT หักจากผู้ขาย
     * - customer_cashback: Cashback แบบจำนวนคงที่
     * - cashback_percentage: Cashback แบบเปอร์เซ็นต์
     *
     * Migration นี้สร้างเพื่อแก้ไขกรณีที่ migration ก่อนหน้าไม่ได้รัน
     * หรือรันไม่สำเร็จ ทำให้ไม่สามารถเพิ่มสินค้าในร้านทางการได้
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // เพิ่มคอลัมน์ customer_cashback ถ้ายังไม่มี
            if (! Schema::hasColumn('products', 'customer_cashback')) {
                $table->decimal('customer_cashback', 10, 2)
                    ->default(0)
                    ->after('commission_rate')
                    ->comment('Fixed cashback amount for customer');
            }

            // เพิ่มคอลัมน์ cashback_percentage ถ้ายังไม่มี
            if (! Schema::hasColumn('products', 'cashback_percentage')) {
                $table->decimal('cashback_percentage', 5, 2)
                    ->default(0)
                    ->after('customer_cashback')
                    ->comment('Cashback percentage of price');
            }

            // เพิ่มคอลัมน์ pv_value ถ้ายังไม่มี
            if (! Schema::hasColumn('products', 'pv_value')) {
                $table->decimal('pv_value', 10, 2)
                    ->default(0)
                    ->after('cashback_percentage')
                    ->comment('PV สำหรับคำนวณ MLM Commission (0 = ไม่มีคอม)');
            }

            // เพิ่มคอลัมน์ vat_percentage ถ้ายังไม่มี
            if (! Schema::hasColumn('products', 'vat_percentage')) {
                $table->decimal('vat_percentage', 5, 2)
                    ->default(7.00)
                    ->after('pv_value')
                    ->comment('ภาษี VAT (%) หักจากผู้ขาย');
            }
        });
    }

    /**
     * ย้อนกลับ migration
     *
     * หมายเหตุ: ไม่ลบคอลัมน์เพราะอาจมีข้อมูลอยู่แล้ว
     * และ migration ก่อนหน้าอาจเป็นผู้สร้างคอลัมน์เหล่านี้
     */
    public function down(): void
    {
        // ไม่ลบคอลัมน์เพื่อป้องกันการสูญหายของข้อมูล
        // ถ้าต้องการลบจริง ให้ใช้ migration 2025_11_24_160001 down() แทน
    }
};
