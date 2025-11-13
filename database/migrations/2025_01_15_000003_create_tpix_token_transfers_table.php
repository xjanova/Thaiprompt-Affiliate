<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpix_token_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tpix_tokens')->onDelete('cascade');

            // Transaction Information
            $table->string('tx_hash')->unique()->comment('TPIX blockchain transaction hash');
            $table->string('from_address', 42)->comment('Sender address');
            $table->string('to_address', 42)->comment('Recipient address');
            $table->decimal('amount', 30, 8)->comment('Transfer amount');

            // User References (nullable for external addresses)
            $table->foreignId('from_user_id')->nullable()->constrained('users');
            $table->foreignId('to_user_id')->nullable()->constrained('users');

            // Blockchain Data
            $table->bigInteger('block_number')->nullable();
            $table->timestamp('block_timestamp')->nullable();
            $table->integer('confirmations')->default(0);
            $table->decimal('gas_used', 20, 0)->nullable();
            $table->decimal('gas_price', 20, 0)->nullable();

            // Transfer Type
            $table->enum('transfer_type', [
                'transfer',      // Normal transfer
                'mint',          // Token minting
                'burn',          // Token burning
                'airdrop',       // Airdrop distribution
                'reward',        // Referral/staking reward
                'swap',          // DEX swap
                'liquidity_add', // Add liquidity
                'liquidity_remove' // Remove liquidity
            ])->default('transfer');

            // Status
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['token_id', 'created_at']);
            $table->index(['from_address', 'token_id']);
            $table->index(['to_address', 'token_id']);
            $table->index(['from_user_id', 'token_id']);
            $table->index(['to_user_id', 'token_id']);
            $table->index('block_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_token_transfers');
    }
};
