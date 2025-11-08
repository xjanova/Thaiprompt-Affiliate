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
        if (Schema::hasTable('crypto_currencies')) {
            return;
        }

        Schema::create('crypto_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('BTC, ETH, USDT, etc.');
            $table->string('name', 100)->comment('Bitcoin, Ethereum, etc.');
            $table->string('symbol', 10)->comment('฿, Ξ, etc.');
            $table->string('network')->comment('bitcoin, ethereum, bsc, polygon, tron');
            $table->string('contract_address')->nullable()->comment('For tokens (ERC-20, BEP-20, etc.)');
            $table->enum('token_standard', ['native', 'ERC-20', 'BEP-20', 'TRC-20', 'SPL'])->default('native');
            $table->integer('decimals')->default(18)->comment('Token decimals');
            $table->string('chain_id')->nullable()->comment('1 for Ethereum mainnet, 56 for BSC, 137 for Polygon');

            // Icon and display
            $table->string('icon')->nullable()->comment('Icon URL or path');
            $table->string('color', 7)->nullable()->comment('Hex color for UI');

            // Exchange settings
            $table->enum('price_source', ['api', 'manual', 'both'])->default('api')
                ->comment('api=realtime from CoinGecko/Binance, manual=admin sets, both=fallback');
            $table->string('coingecko_id')->nullable()->comment('CoinGecko API ID');
            $table->string('binance_symbol')->nullable()->comment('Binance trading pair symbol');
            $table->decimal('manual_price_thb', 20, 2)->nullable()->comment('Manual price in THB');
            $table->decimal('exchange_fee_percentage', 8, 4)->default(1.0000)
                ->comment('Exchange fee % (1.0 = 1%)');
            $table->decimal('min_exchange_amount', 20, 8)->default(0)
                ->comment('Minimum amount for exchange');

            // Transaction settings
            $table->decimal('min_deposit', 20, 8)->default(0)->comment('Minimum deposit amount');
            $table->decimal('min_withdrawal', 20, 8)->default(0)->comment('Minimum withdrawal amount');
            $table->decimal('max_withdrawal', 20, 8)->nullable()->comment('Maximum withdrawal amount');
            $table->decimal('withdrawal_fee', 20, 8)->default(0)->comment('Fixed withdrawal fee');
            $table->enum('withdrawal_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->integer('confirmations_required')->default(6)
                ->comment('Required confirmations for deposit');

            // RPC/API Configuration
            $table->text('rpc_endpoints')->nullable()->comment('JSON array of RPC URLs');
            $table->string('explorer_url')->nullable()->comment('Block explorer base URL');
            $table->string('explorer_tx_path')->default('/tx/')->comment('Transaction path');
            $table->string('explorer_address_path')->default('/address/')->comment('Address path');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('deposit_enabled')->default(true);
            $table->boolean('withdrawal_enabled')->default(true);
            $table->boolean('exchange_enabled')->default(true);
            $table->boolean('payment_gateway_enabled')->default(false);

            // Display order
            $table->integer('sort_order')->default(0);

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Additional config data');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['network', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_currencies');
    }
};
