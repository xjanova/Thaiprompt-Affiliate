<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์สำหรับติดตาม Roll-up Commission
 *
 * Roll-up = เมื่อสมาชิกไม่ active (ไม่รักษายอด) คอมมิชชันจะข้ามไปยัง upline ที่ active
 *
 * ฟิลด์ที่เพิ่ม:
 * - is_rollup: ระบุว่าเป็น commission จาก roll-up หรือไม่
 * - rollup_from_member_id: ID ของคนที่ถูกข้าม (inactive member)
 * - rollup_original_level: level เดิมก่อน roll-up
 * - rollup_chain: JSON array ของ chain การ roll-up (เก็บ path ทั้งหมด)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mlm_commissions มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('mlm_commissions')) {
            return;
        }

        Schema::table('mlm_commissions', function (Blueprint $table) {
            // ระบุว่าเป็น roll-up commission หรือไม่
            if (!Schema::hasColumn('mlm_commissions', 'is_rollup')) {
                $table->boolean('is_rollup')->default(false)->after('status');
            }

            // ID ของสมาชิกที่ถูกข้าม (inactive)
            if (!Schema::hasColumn('mlm_commissions', 'rollup_from_member_id')) {
                $table->unsignedBigInteger('rollup_from_member_id')->nullable()->after('is_rollup');
            }

            // Level เดิมก่อน roll-up (เพื่อคำนวณ % ถูกต้อง)
            if (!Schema::hasColumn('mlm_commissions', 'rollup_original_level')) {
                $table->unsignedInteger('rollup_original_level')->nullable()->after('rollup_from_member_id');
            }

            // Chain ของ roll-up - เก็บทั้ง path ที่ roll-up มา
            // Format: [{"member_id": 5, "level": 2, "reason": "inactive"}, ...]
            if (!Schema::hasColumn('mlm_commissions', 'rollup_chain')) {
                $table->json('rollup_chain')->nullable()->after('rollup_original_level');
            }

            // สาย/ขา ที่มาของ commission (สำหรับ Binary)
            if (!Schema::hasColumn('mlm_commissions', 'source_leg')) {
                $table->string('source_leg', 20)->nullable()->after('rollup_chain');
                // 'left', 'right', หรือ null สำหรับ unilevel
            }

            // Tree type ที่ใช้คำนวณ
            if (!Schema::hasColumn('mlm_commissions', 'tree_type')) {
                $table->string('tree_type', 20)->default('unilevel')->after('source_leg');
                // 'unilevel', 'binary', 'genealogy'
            }
        });

        // เพิ่ม indexes
        Schema::table('mlm_commissions', function (Blueprint $table) {
            // ตรวจสอบก่อนสร้าง index
            $indexExists = collect(\DB::select("SHOW INDEX FROM mlm_commissions WHERE Key_name = 'mlm_commissions_is_rollup_index'"))->isNotEmpty();
            if (!$indexExists) {
                $table->index('is_rollup', 'mlm_commissions_is_rollup_index');
            }

            $indexExists2 = collect(\DB::select("SHOW INDEX FROM mlm_commissions WHERE Key_name = 'mlm_commissions_tree_type_index'"))->isNotEmpty();
            if (!$indexExists2) {
                $table->index('tree_type', 'mlm_commissions_tree_type_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_commissions', function (Blueprint $table) {
            $columns = ['is_rollup', 'rollup_from_member_id', 'rollup_original_level', 'rollup_chain', 'source_leg', 'tree_type'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('mlm_commissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
