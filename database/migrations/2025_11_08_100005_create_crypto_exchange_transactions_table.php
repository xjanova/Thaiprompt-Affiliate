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
        Schema::create('crypto_exchange_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Exchange ID
            $table->string('exchange_id', 100)->unique()
                ->comment('EXG + 20 chars');

            // Exchange type
            $table->enum('type', ['buy', 'sell'])
                ->comment('buy=THB→Crypto, sell=Crypto→THB');

            // From (source)
            $table->enum('from_currency_type', ['fiat', 'crypto']);
            $table->string('from_currency_code', 10)->comment('THB, BTC, ETH, etc.');
            $table->decimal('from_amount', 30, 18);
            $table->foreignId('from_wallet_id')->nullable()->comment('wallet_id for THB');
            $table->foreignId('from_crypto_wallet_id')->nullable()
                ->constrained('crypto_wallets')->onDelete('set null');

            // To (destination)
            $table->enum('to_currency_type', ['fiat', 'crypto']);
            $table->string('to_currency_code', 10)->comment('THB, BTC, ETH, etc.');
            $table->decimal('to_amount', 30, 18);
            $table->foreignId('to_wallet_id')->nullable()->comment('wallet_id for THB');
            $table->foreignId('to_crypto_wallet_id')->nullable()
                ->constrained('crypto_wallets')->onDelete('set null');

            // Exchange rate at transaction time
            $table->decimal('exchange_rate', 20, 2)
                ->comment('1 crypto unit = X THB');
            $table->foreignId('crypto_exchange_rate_id')->nullable()
                ->constrained()->onDelete('set null');

            // Fees
            $table->decimal('fee_percentage', 8, 4)->default(0);
            $table->decimal('fee_amount', 20, 2)->default(0);
            $table->string('fee_currency', 10)->comment('Currency of fee (THB or crypto)');

            // Network fee (for crypto withdrawal after exchange)
            $table->decimal('network_fee', 30, 18)->default(0);

            // Final amounts after fees
            $table->decimal('final_from_amount', 30, 18)
                ->comment('Amount deducted from source');
            $table->decimal('final_to_amount', 30, 18)
                ->comment('Amount credited to destination');

            // Status
            $table->enum('status', [
                'pending',           // Order placed
                'processing',        // Being processed
                'completed',         // Exchange completed
                'failed',            // Exchange failed
                'cancelled',         // Cancelled by user
                'refunded'           // Refunded
            ])->default('pending');

            // Related transactions
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained()->onDelete('set null')
                ->comment('THB wallet transaction');
            $table->foreignId('crypto_transaction_id')->nullable()
                ->constrained()->onDelete('set null')
                ->comment('Crypto transaction');

            // Price protection (slippage)
            $table->decimal('max_slippage_percentage', 8, 4)->default(1.0000)
                ->comment('Max allowed price change %');
            $table->decimal('actual_slippage', 8, 4)->nullable()
                ->comment('Actual price change at execution');

            // Execution
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();

            // Description and notes
            $table->text('description')->nullable();
            $table->text('admin_note')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('exchange_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_exchange_transactions');
    }
};
