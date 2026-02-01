<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม store_id ในตาราง SMS Checker ที่มีอยู่
 * เพื่อรองรับ multi-store SMS Payment Gateway
 *
 * store_id = null หมายถึง admin/official shop (backward compatible)
 * store_id = X หมายถึงอุปกรณ์/ข้อมูลของร้านค้า X
 */
return new class extends Migration
{
    public function up(): void
    {
        // เพิ่ม store_id ให้ sms_checker_devices
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('user_id');
            $table->index('store_id');
        });

        // เพิ่ม store_id ให้ payment_transactions (for routing)
        if (Schema::hasTable('payment_transactions') && !Schema::hasColumn('payment_transactions', 'store_id')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('order_id');
                $table->index('store_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            $table->dropIndex(['store_id']);
            $table->dropColumn('store_id');
        });

        if (Schema::hasTable('payment_transactions') && Schema::hasColumn('payment_transactions', 'store_id')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->dropIndex(['store_id']);
                $table->dropColumn('store_id');
            });
        }
    }
};
