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
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_currency_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_address_id')->nullable()->constrained()->onDelete('set null');

            // Transaction ID
            $table->string('transaction_id', 100)->unique()
                ->comment('Internal TX ID (CRTX + 20 chars)');

            // Blockchain data
            $table->string('tx_hash')->nullable()->unique()
                ->comment('Blockchain transaction hash');
            $table->string('network')->comment('ethereum, bsc, polygon, bitcoin, tron');
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->integer('block_number')->nullable();
            $table->timestamp('block_timestamp')->nullable();

            // Transaction type
            $table->enum('type', [
                'deposit',           // Receiving crypto
                'withdrawal',        // Sending crypto
                'exchange_buy',      // Bought crypto with THB
                'exchange_sell',     // Sold crypto to THB
                'payment',           // Paid for order with crypto
                'refund',            // Refunded crypto
                'transfer_in',       // Received from another user
                'transfer_out',      // Sent to another user
                'gas_fee'            // Gas fee deduction
            ]);

            // Amount
            $table->decimal('amount', 30, 18)->comment('Amount in crypto');
            $table->decimal('fee', 30, 18)->default(0)->comment('Network fee (gas)');
            $table->decimal('amount_thb', 20, 2)->nullable()
                ->comment('THB equivalent at transaction time');
            $table->decimal('exchange_rate', 20, 2)->nullable()
                ->comment('Crypto to THB rate at transaction time');

            // Status
            $table->enum('status', [
                'pending',           // Waiting for broadcast
                'broadcasted',       // Sent to blockchain
                'confirming',        // Waiting for confirmations
                'confirmed',         // Fully confirmed
                'completed',         // Processed in system
                'failed',            // Transaction failed
                'cancelled'          // Cancelled before broadcast
            ])->default('pending');

            $table->integer('confirmations')->default(0);
            $table->integer('confirmations_required')->default(6);

            // Gas/Fee details
            $table->string('gas_price')->nullable()->comment('Gas price in wei/gwei');
            $table->string('gas_limit')->nullable();
            $table->string('gas_used')->nullable();

            // Nonce (for EVM chains)
            $table->string('nonce')->nullable();

            // Related records
            $table->foreignId('payment_transaction_id')->nullable()
                ->constrained()->onDelete('set null')
                ->comment('Link to payment_transactions table');
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained()->onDelete('set null')
                ->comment('Link to wallet_transactions for exchange');

            // Description and notes
            $table->text('description')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional transaction data');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Risk flags
            $table->boolean('requires_review')->default(false);
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            // Timestamps
            $table->timestamp('broadcasted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'type', 'status']);
            $table->index(['crypto_wallet_id', 'created_at']);
            $table->index(['network', 'status', 'confirmations']);
            $table->index(['tx_hash', 'network']);
            $table->index(['to_address', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_transactions');
    }
};
