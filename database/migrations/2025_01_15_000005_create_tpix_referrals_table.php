<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpix_referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('token_id')->nullable()->constrained('tpix_tokens')->onDelete('cascade');

            // Code Information
            $table->string('code', 20)->unique()->comment('Unique referral code');
            $table->string('name', 100)->nullable()->comment('Code name/description');
            $table->enum('type', ['user', 'token', 'campaign'])->default('user');

            // Rewards Configuration
            $table->decimal('reward_percentage', 5, 2)->default(5.00)->comment('Reward % for referee');
            $table->decimal('referrer_percentage', 5, 2)->default(2.00)->comment('Reward % for referrer');
            $table->enum('reward_type', ['tpix', 'token', 'both'])->default('tpix');

            // Limits
            $table->integer('max_uses')->nullable()->comment('Maximum number of uses');
            $table->integer('total_uses')->default(0);
            $table->decimal('min_transaction_amount', 30, 8)->nullable()->comment('Minimum amount to earn reward');

            // Status & Timing
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            // Statistics
            $table->decimal('total_rewards_paid', 30, 8)->default(0);
            $table->integer('successful_referrals')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['token_id', 'is_active']);
            $table->index('code');
            $table->index('expires_at');
        });

        Schema::create('tpix_referral_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained('tpix_referral_codes')->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->comment('Who owns the code');
            $table->foreignId('referee_id')->constrained('users')->comment('Who used the code');
            $table->foreignId('token_id')->nullable()->constrained('tpix_tokens');

            // Transaction Information
            $table->string('related_tx_hash')->nullable()->comment('Related transaction');
            $table->decimal('transaction_amount', 30, 8)->nullable();

            // Rewards
            $table->decimal('referrer_reward', 30, 8)->default(0)->comment('Reward to referrer');
            $table->decimal('referee_reward', 30, 8)->default(0)->comment('Reward to referee');
            $table->enum('reward_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('reward_paid_at')->nullable();

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_code', 10)->nullable()->comment('Email/SMS verification code');
            $table->integer('verification_attempts')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['referrer_id', 'reward_status']);
            $table->index(['referee_id', 'created_at']);
            $table->index('reward_status');
            $table->index('is_verified');
        });

        Schema::create('tpix_referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_use_id')->constrained('tpix_referral_uses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('token_id')->nullable()->constrained('tpix_tokens');

            // Reward Information
            $table->enum('reward_type', ['referrer', 'referee'])->comment('Who gets this reward');
            $table->decimal('amount', 30, 8);
            $table->string('currency_type')->comment('TPIX or token symbol');

            // Distribution
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_tx_hash')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_referral_rewards');
        Schema::dropIfExists('tpix_referral_uses');
        Schema::dropIfExists('tpix_referral_codes');
    }
};
