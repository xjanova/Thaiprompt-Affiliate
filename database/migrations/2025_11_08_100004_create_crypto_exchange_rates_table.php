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
        Schema::create('crypto_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_currency_id')->constrained()->onDelete('cascade');

            // Exchange rates
            $table->decimal('rate_thb', 20, 2)->comment('1 crypto unit = X THB');
            $table->decimal('buy_rate_thb', 20, 2)->comment('Rate when buying crypto (higher)');
            $table->decimal('sell_rate_thb', 20, 2)->comment('Rate when selling crypto (lower)');

            // Spread/Fee
            $table->decimal('spread_percentage', 8, 4)->default(1.0000)
                ->comment('Spread % between buy/sell (1.0 = 1%)');

            // Source
            $table->enum('source', ['api', 'manual', 'fallback'])->default('api');
            $table->string('api_source')->nullable()->comment('coingecko, binance, etc.');

            // API response data (for debugging)
            $table->json('api_response')->nullable();

            // Price change tracking
            $table->decimal('price_change_24h', 8, 4)->nullable()->comment('24h change %');
            $table->decimal('volume_24h', 20, 2)->nullable()->comment('24h volume in THB');
            $table->decimal('market_cap', 20, 2)->nullable()->comment('Market cap in THB');

            // Valid period
            $table->timestamp('valid_from')->useCurrent();
            $table->timestamp('valid_until')->nullable()
                ->comment('API rates expire after X minutes');

            // Updated by
            $table->enum('updated_by_type', ['system', 'admin'])->default('system');
            $table->foreignId('updated_by_admin')->nullable()
                ->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['crypto_currency_id', 'created_at']);
            $table->index(['valid_from', 'valid_until']);
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_exchange_rates');
    }
};
