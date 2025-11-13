<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpix_staking_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tpix_tokens')->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users');

            // Pool Information
            $table->string('name', 100)->comment('Pool name');
            $table->text('description')->nullable();
            $table->string('contract_address', 42)->nullable();

            // Staking Parameters
            $table->decimal('apy', 8, 2)->comment('Annual Percentage Yield');
            $table->integer('lock_period_days')->comment('Lock period in days');
            $table->decimal('min_stake_amount', 30, 8)->comment('Minimum stake amount');
            $table->decimal('max_stake_amount', 30, 8)->nullable()->comment('Maximum stake amount');

            // Pool Limits
            $table->decimal('pool_cap', 30, 8)->nullable()->comment('Maximum total stake');
            $table->decimal('total_staked', 30, 8')->default(0)->comment('Currently staked');
            $table->integer('max_stakers')->nullable()->comment('Max number of stakers');
            $table->integer('current_stakers')->default(0);

            // Rewards
            $table->enum('reward_token_type', ['same', 'tpix', 'other'])->default('same');
            $table->foreignId('reward_token_id')->nullable()->constrained('tpix_tokens');
            $table->decimal('total_rewards_distributed', 30, 8)->default(0);
            $table->decimal('pending_rewards', 30, 8)->default(0);

            // Status & Timing
            $table->enum('status', ['draft', 'active', 'paused', 'ended'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['token_id', 'status']);
            $table->index('status');
        });

        Schema::create('tpix_stakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_id')->constrained('tpix_staking_pools')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('token_id')->constrained('tpix_tokens');

            // Stake Information
            $table->decimal('amount', 30, 8)->comment('Staked amount');
            $table->decimal('rewards_earned', 30, 8)->default(0);
            $table->decimal('rewards_claimed', 30, 8)->default(0);
            $table->decimal('rewards_pending', 30, 8)->default(0);

            // Timing
            $table->timestamp('staked_at');
            $table->timestamp('unlock_at')->comment('When can unstake');
            $table->timestamp('last_reward_claim_at')->nullable();

            // Status
            $table->enum('status', ['active', 'unstaked', 'slashed'])->default('active');
            $table->timestamp('unstaked_at')->nullable();

            // Blockchain
            $table->string('stake_tx_hash')->nullable();
            $table->string('unstake_tx_hash')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['pool_id', 'status']);
            $table->index('unlock_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_stakes');
        Schema::dropIfExists('tpix_staking_pools');
    }
};
