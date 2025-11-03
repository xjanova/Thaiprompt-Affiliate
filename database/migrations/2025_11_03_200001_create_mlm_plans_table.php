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
        Schema::create('mlm_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();
            $table->string('slug')->unique();

            // Plan Type
            $table->enum('type', ['unilevel', 'binary', 'hybrid'])->default('unilevel');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Display Settings
            $table->string('color')->default('#4F46E5'); // For UI theming
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);

            // Joining Fee
            $table->decimal('joining_fee', 10, 2)->default(0);
            $table->boolean('requires_joining_fee')->default(false);

            // PV Settings
            $table->boolean('use_pv_system')->default(true);
            $table->decimal('global_pv_rate', 10, 2)->default(1.00); // 1 THB = 1 PV by default
            $table->decimal('global_commission_per_pv', 10, 2)->default(0.10); // Commission per PV

            // Unilevel Settings
            $table->json('unilevel_levels')->nullable(); // [{"level": 1, "percentage": 10}, ...]
            $table->integer('unilevel_max_depth')->default(10);
            $table->boolean('unilevel_compression')->default(false); // Skip inactive members

            // Binary Settings
            $table->decimal('binary_pair_commission', 10, 2)->default(100.00);
            $table->decimal('binary_match_percentage', 5, 2)->default(10.00); // % of weaker leg
            $table->decimal('binary_max_pairs_per_day', 10, 2)->nullable();
            $table->decimal('binary_max_commission_per_day', 10, 2)->nullable();
            $table->decimal('binary_flush_percentage', 5, 2)->default(100.00); // How much of weak leg is flushed
            $table->boolean('binary_spillover')->default(true);
            $table->enum('binary_pairing_type', ['1:1', '2:1'])->default('1:1');

            // Rank Requirements
            $table->boolean('requires_rank')->default(false);
            $table->json('rank_requirements')->nullable(); // Rank-based multipliers

            // Auto-placement Settings
            $table->boolean('auto_placement')->default(false);
            $table->enum('auto_placement_type', ['left_to_right', 'balanced', 'weak_leg'])->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'type']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_plans');
    }
};
