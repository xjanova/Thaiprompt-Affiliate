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
        Schema::create('mlm_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mlm_plan_id')->constrained()->onDelete('cascade');

            // Unilevel Structure
            $table->foreignId('unilevel_sponsor_id')->nullable()->constrained('mlm_members')->onDelete('set null');
            $table->integer('unilevel_level')->default(1);
            $table->string('unilevel_path')->nullable(); // e.g., "1/5/23" for quick hierarchy queries

            // Binary Structure
            $table->foreignId('binary_sponsor_id')->nullable()->constrained('mlm_members')->onDelete('set null');
            $table->foreignId('binary_parent_id')->nullable()->constrained('mlm_members')->onDelete('set null');
            $table->enum('binary_position', ['left', 'right'])->nullable();
            $table->string('binary_path')->nullable(); // Binary tree path

            // Statistics
            $table->integer('total_direct_referrals')->default(0);
            $table->integer('total_team_members')->default(0);
            $table->decimal('total_pv', 15, 2)->default(0);
            $table->decimal('total_team_pv', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);

            // Binary Legs Statistics
            $table->decimal('left_leg_pv', 15, 2)->default(0);
            $table->decimal('right_leg_pv', 15, 2)->default(0);
            $table->decimal('left_leg_sales', 15, 2)->default(0);
            $table->decimal('right_leg_sales', 15, 2)->default(0);
            $table->integer('left_leg_members')->default(0);
            $table->integer('right_leg_members')->default(0);
            $table->decimal('carried_left_pv', 15, 2)->default(0); // Unflushed PV
            $table->decimal('carried_right_pv', 15, 2)->default(0);

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            $table->boolean('is_qualified')->default(true); // For commission eligibility

            // Membership
            $table->string('member_code')->unique();
            $table->decimal('joining_fee_paid', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'mlm_plan_id']);
            $table->index(['unilevel_sponsor_id', 'unilevel_level']);
            $table->index(['binary_parent_id', 'binary_position']);
            $table->index('member_code');
            $table->index('status');
            $table->unique(['user_id', 'mlm_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_members');
    }
};
