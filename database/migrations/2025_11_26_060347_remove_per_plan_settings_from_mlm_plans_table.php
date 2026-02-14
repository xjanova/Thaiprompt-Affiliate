<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับลบ per-plan commission settings columns
 *
 * ⚠️ IMPORTANT: การตั้งค่าคอมมิชชั่นถูกย้ายไปที่ MlmGlobalSetting แล้ว
 * ลบ columns ที่ไม่ใช้แล้วเพื่อลดความซ้ำซ้อน
 *
 * @see MlmGlobalSetting สำหรับการตั้งค่าใหม่
 */
return new class extends Migration
{
    /**
     * Columns ที่จะลบ (per-plan commission settings)
     * การตั้งค่าเหล่านี้ถูกย้ายไปใช้ MlmGlobalSetting แทน
     */
    private array $columnsToRemove = [
        // PV settings
        'use_pv_system',
        'global_pv_rate',
        'global_commission_per_pv',

        // Overpay protection
        'max_total_commission_percentage',
        'enable_overpay_protection',

        // Unilevel settings
        'unilevel_levels',
        'unilevel_max_depth',
        'unilevel_compression',
        'unilevel_max_commission_per_level',
        'unilevel_max_commission_per_order',

        // Binary settings
        'binary_pair_commission',
        'binary_match_percentage',
        'binary_max_pairs_per_day',
        'binary_max_commission_per_day',
        'binary_min_pv_for_commission',
        'binary_flush_percentage',
        'binary_flush_enabled',
        'binary_flush_mode',
        'binary_carry_forward_pv',
        'binary_carry_forward_days',
        'binary_spillover',
        'binary_pairing_type',
        'binary_placement_preference',
        'binary_placement_fill_level_first',

        // Rank settings
        'requires_rank',
        'rank_requirements',

        // Auto placement
        'auto_placement',
        'auto_placement_type',
    ];

    /**
     * ลบ per-plan settings columns
     * การตั้งค่าเหล่านี้ถูกย้ายไปใช้ MlmGlobalSetting แทน
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mlm_plans มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('mlm_plans')) {
            return;
        }

        Schema::table('mlm_plans', function (Blueprint $table) {
            foreach ($this->columnsToRemove as $column) {
                if (Schema::hasColumn('mlm_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Re-add columns ที่ถูกลบ (สำหรับ rollback)
     * หมายเหตุ: Data จะหายไปหลังจาก rollback
     */
    public function down(): void
    {
        Schema::table('mlm_plans', function (Blueprint $table) {
            // PV settings
            if (! Schema::hasColumn('mlm_plans', 'use_pv_system')) {
                $table->boolean('use_pv_system')->default(true)->after('requires_joining_fee');
            }
            if (! Schema::hasColumn('mlm_plans', 'global_pv_rate')) {
                $table->decimal('global_pv_rate', 10, 2)->default(1)->after('use_pv_system');
            }
            if (! Schema::hasColumn('mlm_plans', 'global_commission_per_pv')) {
                $table->decimal('global_commission_per_pv', 10, 2)->default(1)->after('global_pv_rate');
            }

            // Overpay protection
            if (! Schema::hasColumn('mlm_plans', 'max_total_commission_percentage')) {
                $table->decimal('max_total_commission_percentage', 5, 2)->default(50)->after('global_commission_per_pv');
            }
            if (! Schema::hasColumn('mlm_plans', 'enable_overpay_protection')) {
                $table->boolean('enable_overpay_protection')->default(true)->after('max_total_commission_percentage');
            }

            // Unilevel settings
            if (! Schema::hasColumn('mlm_plans', 'unilevel_levels')) {
                $table->json('unilevel_levels')->nullable()->after('enable_overpay_protection');
            }
            if (! Schema::hasColumn('mlm_plans', 'unilevel_max_depth')) {
                $table->integer('unilevel_max_depth')->default(10)->after('unilevel_levels');
            }
            if (! Schema::hasColumn('mlm_plans', 'unilevel_compression')) {
                $table->boolean('unilevel_compression')->default(true)->after('unilevel_max_depth');
            }
            if (! Schema::hasColumn('mlm_plans', 'unilevel_max_commission_per_level')) {
                $table->decimal('unilevel_max_commission_per_level', 10, 2)->nullable()->after('unilevel_compression');
            }
            if (! Schema::hasColumn('mlm_plans', 'unilevel_max_commission_per_order')) {
                $table->decimal('unilevel_max_commission_per_order', 10, 2)->nullable()->after('unilevel_max_commission_per_level');
            }

            // Binary settings
            if (! Schema::hasColumn('mlm_plans', 'binary_pair_commission')) {
                $table->decimal('binary_pair_commission', 10, 2)->default(100)->after('unilevel_max_commission_per_order');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_match_percentage')) {
                $table->decimal('binary_match_percentage', 5, 2)->default(50)->after('binary_pair_commission');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_max_pairs_per_day')) {
                $table->decimal('binary_max_pairs_per_day', 10, 2)->nullable()->after('binary_match_percentage');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_max_commission_per_day')) {
                $table->decimal('binary_max_commission_per_day', 10, 2)->nullable()->after('binary_max_pairs_per_day');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_min_pv_for_commission')) {
                $table->decimal('binary_min_pv_for_commission', 10, 2)->default(0)->after('binary_max_commission_per_day');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_flush_percentage')) {
                $table->decimal('binary_flush_percentage', 5, 2)->default(100)->after('binary_min_pv_for_commission');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_flush_enabled')) {
                $table->boolean('binary_flush_enabled')->default(true)->after('binary_flush_percentage');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_flush_mode')) {
                $table->string('binary_flush_mode')->default('weekly')->after('binary_flush_enabled');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_carry_forward_pv')) {
                $table->boolean('binary_carry_forward_pv')->default(true)->after('binary_flush_mode');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_carry_forward_days')) {
                $table->integer('binary_carry_forward_days')->default(7)->after('binary_carry_forward_pv');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_spillover')) {
                $table->boolean('binary_spillover')->default(false)->after('binary_carry_forward_days');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_pairing_type')) {
                $table->string('binary_pairing_type')->default('1:1')->after('binary_spillover');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_placement_preference')) {
                $table->string('binary_placement_preference')->default('balanced')->after('binary_pairing_type');
            }
            if (! Schema::hasColumn('mlm_plans', 'binary_placement_fill_level_first')) {
                $table->boolean('binary_placement_fill_level_first')->default(false)->after('binary_placement_preference');
            }

            // Rank settings
            if (! Schema::hasColumn('mlm_plans', 'requires_rank')) {
                $table->boolean('requires_rank')->default(false)->after('binary_placement_fill_level_first');
            }
            if (! Schema::hasColumn('mlm_plans', 'rank_requirements')) {
                $table->json('rank_requirements')->nullable()->after('requires_rank');
            }

            // Auto placement
            if (! Schema::hasColumn('mlm_plans', 'auto_placement')) {
                $table->boolean('auto_placement')->default(true)->after('rank_requirements');
            }
            if (! Schema::hasColumn('mlm_plans', 'auto_placement_type')) {
                $table->string('auto_placement_type')->default('balanced')->after('auto_placement');
            }
        });
    }
};
