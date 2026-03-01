<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ rider tracking ใน fresh_market_orders
     *
     * หมายเหตุ: rider_job_id มีอยู่แล้ว ดังนั้นเพิ่มเฉพาะ:
     * - rider_id — เชื่อมตรงกับ rider (shortcut ไม่ต้องผ่าน rider_job)
     * - rider_assigned_at, rider_accepted_at, rider_picked_up_at, rider_delivered_at — tracking timestamps
     * - delivery_distance_km — ระยะทางส่ง
     */
    public function up(): void
    {
        Schema::table('fresh_market_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('fresh_market_orders', 'rider_id')) {
                $table->unsignedBigInteger('rider_id')->nullable()->after('rider_job_id');
            }
            if (!Schema::hasColumn('fresh_market_orders', 'rider_assigned_at')) {
                $table->timestamp('rider_assigned_at')->nullable()->after('rider_id');
            }
            if (!Schema::hasColumn('fresh_market_orders', 'rider_accepted_at')) {
                $table->timestamp('rider_accepted_at')->nullable()->after('rider_assigned_at');
            }
            if (!Schema::hasColumn('fresh_market_orders', 'rider_picked_up_at')) {
                $table->timestamp('rider_picked_up_at')->nullable()->after('rider_accepted_at');
            }
            if (!Schema::hasColumn('fresh_market_orders', 'rider_delivered_at')) {
                $table->timestamp('rider_delivered_at')->nullable()->after('rider_picked_up_at');
            }
            if (!Schema::hasColumn('fresh_market_orders', 'delivery_distance_km')) {
                $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_fee');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fresh_market_orders', function (Blueprint $table) {
            $columns = [
                'rider_id', 'rider_assigned_at', 'rider_accepted_at',
                'rider_picked_up_at', 'rider_delivered_at', 'delivery_distance_km',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fresh_market_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
