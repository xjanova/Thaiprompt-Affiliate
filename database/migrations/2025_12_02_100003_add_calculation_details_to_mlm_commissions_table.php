<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม calculation_details column และ unilevel_rollup type
 *
 * calculation_details ใช้เก็บรายละเอียดการคำนวณ commission
 * รวมถึงข้อมูล rollup และ pool bonus
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mlm_commissions มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('mlm_commissions')) {
            return;
        }

        Schema::table('mlm_commissions', function (Blueprint $table) {
            // เพิ่ม calculation_details column
            if (! Schema::hasColumn('mlm_commissions', 'calculation_details')) {
                $table->json('calculation_details')->nullable()->after('notes');
            }
        });

        // อัพเดท type enum เพื่อเพิ่ม unilevel_rollup
        // หมายเหตุ: MySQL ไม่รองรับการเพิ่มค่าใน enum โดยตรง
        // ต้องใช้ ALTER TABLE แทน
        \DB::statement("ALTER TABLE mlm_commissions MODIFY COLUMN type ENUM(
            'unilevel_direct',
            'unilevel_indirect',
            'unilevel_rollup',
            'binary_pair',
            'binary_matching',
            'sponsor_bonus',
            'rank_bonus',
            'leadership_bonus',
            'matching_bonus',
            'pool_bonus'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('mlm_commissions', 'calculation_details')) {
                $table->dropColumn('calculation_details');
            }
        });

        // Revert enum (หมายเหตุ: อาจทำให้ข้อมูลหาย)
        \DB::statement("ALTER TABLE mlm_commissions MODIFY COLUMN type ENUM(
            'unilevel_direct',
            'unilevel_indirect',
            'binary_pair',
            'binary_matching',
            'sponsor_bonus',
            'rank_bonus',
            'leadership_bonus',
            'matching_bonus',
            'pool_bonus'
        )");
    }
};
