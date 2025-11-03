<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mlm_plans', function (Blueprint $table) {
            // PV Flush Options
            $table->boolean('binary_flush_enabled')->default(true)->after('binary_flush_percentage');
            $table->enum('binary_flush_mode', ['percentage', 'full', 'none'])->default('percentage')->after('binary_flush_enabled');

            // Carry Forward PV
            $table->boolean('binary_carry_forward_pv')->default(false)->after('binary_flush_mode');
            $table->integer('binary_carry_forward_days')->nullable()->after('binary_carry_forward_pv');

            // Auto Placement Advanced Options
            $table->enum('binary_placement_preference', ['left_first', 'right_first', 'fill_level', 'weak_leg', 'balanced'])->default('left_first')->after('auto_placement_type');
            $table->boolean('binary_placement_fill_level_first')->default(false)->after('binary_placement_preference');

            // Ceiling & Floor
            $table->decimal('unilevel_max_commission_per_level', 10, 2)->nullable()->after('unilevel_compression');
            $table->decimal('unilevel_max_commission_per_order', 10, 2)->nullable()->after('unilevel_max_commission_per_level');
            $table->decimal('binary_min_pv_for_commission', 10, 2)->default(0)->after('binary_max_commission_per_day');

            // Safety Limits
            $table->decimal('max_total_commission_percentage', 5, 2)->default(100.00)->after('global_commission_per_pv');
            $table->boolean('enable_overpay_protection')->default(true)->after('max_total_commission_percentage');

            // Advanced Settings JSON
            $table->json('advanced_settings')->nullable()->after('enable_overpay_protection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_plans', function (Blueprint $table) {
            $table->dropColumn([
                'binary_flush_enabled',
                'binary_flush_mode',
                'binary_carry_forward_pv',
                'binary_carry_forward_days',
                'binary_placement_preference',
                'binary_placement_fill_level_first',
                'unilevel_max_commission_per_level',
                'unilevel_max_commission_per_order',
                'binary_min_pv_for_commission',
                'max_total_commission_percentage',
                'enable_overpay_protection',
                'advanced_settings',
            ]);
        });
    }
};
