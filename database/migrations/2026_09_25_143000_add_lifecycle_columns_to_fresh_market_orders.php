<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์วงจรชีวิตออเดอร์ตลาดสด
     *
     * - status_history : ประวัติการเปลี่ยนสถานะ (ใคร/ทำอะไร/เหตุผล/เวลา) ใช้ audit + ข้อพิพาท
     * - refunded_amount: ยอดที่คืนเงินจริง (คืนเฉพาะเงินที่เก็บมาแล้วเท่านั้น)
     * - gp_rate        : อัตรา GP (%) ที่ใช้ตอนสร้างออเดอร์ (snapshot กันตั้งค่าเปลี่ยนภายหลัง)
     * - paid_at        : เวลาที่เก็บเงินได้จริง (หัก wallet / เก็บเงินปลายทาง)
     *
     * ⚠️ ALTER TABLE → ห้ามใช้ hasTable + return, เช็คทีละคอลัมน์แทน
     */
    public function up(): void
    {
        if (! Schema::hasTable('fresh_market_orders')) {
            return;
        }

        Schema::table('fresh_market_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('fresh_market_orders', 'status_history')) {
                $table->json('status_history')->nullable()->after('cancelled_by');
            }

            if (! Schema::hasColumn('fresh_market_orders', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('seller_earning');
            }

            if (! Schema::hasColumn('fresh_market_orders', 'gp_rate')) {
                $table->decimal('gp_rate', 5, 2)->nullable()->after('platform_fee');
            }

            if (! Schema::hasColumn('fresh_market_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        if (! Schema::hasTable('fresh_market_orders')) {
            return;
        }

        Schema::table('fresh_market_orders', function (Blueprint $table) {
            foreach (['status_history', 'refunded_amount', 'gp_rate', 'paid_at'] as $column) {
                if (Schema::hasColumn('fresh_market_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
