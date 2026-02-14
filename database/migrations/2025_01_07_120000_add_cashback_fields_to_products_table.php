<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม cashback fields ในตาราง products
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง products มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            // ✅ เช็คก่อนเพิ่มคอลัมน์ (idempotent)
            if (! Schema::hasColumn('products', 'customer_cashback')) {
                $table->decimal('customer_cashback', 10, 2)->default(0)->after('commission_rate')
                    ->comment('Fixed cashback amount for customer');
            }

            if (! Schema::hasColumn('products', 'cashback_percentage')) {
                $table->decimal('cashback_percentage', 5, 2)->default(0)->after('customer_cashback')
                    ->comment('Cashback percentage of price');
            }
        });
    }

    /**
     * ลบ cashback fields จากตาราง products
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('products', 'customer_cashback')) {
                $columnsToDrop[] = 'customer_cashback';
            }

            if (Schema::hasColumn('products', 'cashback_percentage')) {
                $columnsToDrop[] = 'cashback_percentage';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
