<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ cancelled_at และ cancel_reason ในตาราง earnings_ledger
     * สำหรับระบบ Refund และ MLM Clawback
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง earnings_ledger มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('earnings_ledger')) {
            return;
        }

        Schema::table('earnings_ledger', function (Blueprint $table) {
            // เพิ่มคอลัมน์ cancelled_at ถ้ายังไม่มี
            if (! Schema::hasColumn('earnings_ledger', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            }

            // เพิ่มคอลัมน์ cancel_reason ถ้ายังไม่มี
            if (! Schema::hasColumn('earnings_ledger', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('cancelled_at');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('earnings_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('earnings_ledger', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('earnings_ledger', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
        });
    }
};
