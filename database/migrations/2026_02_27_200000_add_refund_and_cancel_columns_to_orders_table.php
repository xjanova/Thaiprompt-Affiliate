<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สำหรับระบบยกเลิก/คืนเงินในตาราง orders
     *
     * แก้ bug: RefundService ต้องการ refund_reason, refunded_at, refunded_by
     * แต่คอลัมน์เหล่านี้ยังไม่มีในตาราง orders
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // คอลัมน์สำหรับการคืนเงิน
            if (! Schema::hasColumn('orders', 'refund_reason')) {
                $table->text('refund_reason')->nullable()
                    ->after('cancellation_reason')
                    ->comment('เหตุผลในการคืนเงิน');
            }

            if (! Schema::hasColumn('orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()
                    ->after('refund_reason')
                    ->comment('เวลาที่คืนเงิน');
            }

            if (! Schema::hasColumn('orders', 'refunded_by')) {
                $table->unsignedBigInteger('refunded_by')->nullable()
                    ->after('refunded_at')
                    ->comment('Admin ที่ทำการคืนเงิน');
            }

            // คอลัมน์สำหรับการยกเลิก
            if (! Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()
                    ->after('refunded_by')
                    ->comment('เวลาที่ยกเลิก');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = ['refund_reason', 'refunded_at', 'refunded_by', 'cancelled_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
