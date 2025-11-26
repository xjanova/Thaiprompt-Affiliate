<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ใหม่สำหรับระบบ Rank ที่สมบูรณ์
     *
     * ฟิลด์ใหม่:
     * - promotion_bonus: โบนัสอัตโนมัติเมื่อเลื่อนตำแหน่ง (ครั้งเดียว)
     * - max_downline_level_bonus: จำนวนชั้นลูกทีมที่ได้รับค่าตอบแทน
     * - unilevel_commission_levels: เปอร์เซ็นต์คอมมิชชันแต่ละชั้น (JSON)
     * - privileges: สิทธิพิเศษของระดับ (JSON)
     * - is_top_tier: เป็นระดับสูงสุดหรือไม่
     * - monthly_maintenance_pv: PV ที่ต้องรักษาต่อเดือน
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            // โบนัสเลื่อนตำแหน่งอัตโนมัติ (ได้รับครั้งเดียวเมื่อเลื่อนตำแหน่ง)
            if (!Schema::hasColumn('ranks', 'promotion_bonus')) {
                $table->decimal('promotion_bonus', 12, 2)->default(0)
                    ->after('bonus_multiplier')
                    ->comment('โบนัสอัตโนมัติเมื่อเลื่อนตำแหน่ง (ครั้งเดียว)');
            }

            // จำนวนชั้นลูกทีมที่ได้รับค่าตอบแทน (สำหรับ Unilevel)
            if (!Schema::hasColumn('ranks', 'max_downline_level_bonus')) {
                $table->integer('max_downline_level_bonus')->default(5)
                    ->after('promotion_bonus')
                    ->comment('จำนวนชั้นลูกทีมที่ได้รับค่าตอบแทน');
            }

            // เปอร์เซ็นต์คอมมิชชันแต่ละชั้น (JSON array)
            // เช่น [10, 5, 3, 2, 1] หมายถึง ชั้น 1 ได้ 10%, ชั้น 2 ได้ 5%, ...
            if (!Schema::hasColumn('ranks', 'unilevel_commission_levels')) {
                $table->json('unilevel_commission_levels')->nullable()
                    ->after('max_downline_level_bonus')
                    ->comment('เปอร์เซ็นต์คอมมิชชันแต่ละชั้น (JSON array)');
            }

            // สิทธิพิเศษของระดับ (JSON array)
            // เช่น ["vip_support", "priority_withdrawal", "exclusive_products"]
            if (!Schema::hasColumn('ranks', 'privileges')) {
                $table->json('privileges')->nullable()
                    ->after('unilevel_commission_levels')
                    ->comment('สิทธิพิเศษของระดับ (JSON array)');
            }

            // เป็นระดับสูงสุดหรือไม่ (สำหรับโบนัสพิเศษ)
            if (!Schema::hasColumn('ranks', 'is_top_tier')) {
                $table->boolean('is_top_tier')->default(false)
                    ->after('is_default')
                    ->comment('เป็นระดับสูงสุดหรือไม่');
            }

            // PV ที่ต้องรักษาต่อเดือนเพื่อรักษาระดับ
            if (!Schema::hasColumn('ranks', 'monthly_maintenance_pv')) {
                $table->decimal('monthly_maintenance_pv', 12, 2)->default(0)
                    ->after('min_sales')
                    ->comment('PV ที่ต้องรักษาต่อเดือน');
            }

            // ค่าธรรมเนียมถอนเงินขั้นต่ำ (%)
            if (!Schema::hasColumn('ranks', 'withdrawal_fee_discount')) {
                $table->decimal('withdrawal_fee_discount', 5, 2)->default(0)
                    ->after('monthly_maintenance_pv')
                    ->comment('ส่วนลดค่าธรรมเนียมถอนเงิน (%)');
            }

            // จำนวนครั้งที่ถอนได้ต่อเดือน
            if (!Schema::hasColumn('ranks', 'max_withdrawals_per_month')) {
                $table->integer('max_withdrawals_per_month')->nullable()
                    ->after('withdrawal_fee_discount')
                    ->comment('จำนวนครั้งที่ถอนได้ต่อเดือน (null = ไม่จำกัด)');
            }
        });
    }

    /**
     * ลบฟิลด์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $columns = [
                'promotion_bonus',
                'max_downline_level_bonus',
                'unilevel_commission_levels',
                'privileges',
                'is_top_tier',
                'monthly_maintenance_pv',
                'withdrawal_fee_discount',
                'max_withdrawals_per_month',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ranks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
