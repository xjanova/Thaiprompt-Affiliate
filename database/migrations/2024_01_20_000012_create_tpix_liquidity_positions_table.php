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
        Schema::create('tpix_liquidity_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pool_id')->constrained('tpix_liquidity_pools')->onDelete('cascade');

            // Position amounts
            $table->decimal('token_a_amount', 30, 8);
            $table->decimal('token_b_amount', 30, 8);
            $table->decimal('lp_tokens', 30, 8);

            // Position value tracking
            $table->decimal('initial_value_tpix', 30, 8)->nullable();
            $table->decimal('current_value_tpix', 30, 8)->nullable();

            // Rewards
            $table->decimal('fees_earned', 30, 8)->default(0);
            $table->decimal('fees_claimed', 30, 8)->default(0);
            $table->timestamp('last_fee_claim_at')->nullable();

            // Impermanent loss tracking
            $table->decimal('impermanent_loss', 30, 8)->nullable();
            $table->decimal('impermanent_loss_percentage', 10, 4)->nullable();

            // Transaction hashes
            $table->string('add_tx_hash', 66)->nullable();
            $table->string('remove_tx_hash', 66)->nullable();

            // Status
            $table->enum('status', ['active', 'removed', 'partial'])->default('active');
            $table->timestamp('removed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('pool_id');
            $table->index('status');
            $table->index(['user_id', 'pool_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpix_liquidity_positions');
    }
};
