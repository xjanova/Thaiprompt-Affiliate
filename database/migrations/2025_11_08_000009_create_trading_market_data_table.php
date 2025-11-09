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
        Schema::create('trading_market_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained('trading_exchanges')->onDelete('cascade');

            $table->string('symbol'); // BTC/USDT
            $table->enum('timeframe', ['1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w']);
            $table->timestamp('timestamp');

            // OHLCV Data
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 30, 8);

            // Additional Market Metrics
            $table->decimal('quote_volume', 30, 8)->nullable();
            $table->integer('trades_count')->nullable();
            $table->decimal('vwap', 20, 8)->nullable(); // Volume Weighted Average Price

            // Technical Indicators (cached)
            $table->json('indicators')->nullable(); // RSI, MACD, EMA, etc.

            $table->timestamps();

            $table->unique(['exchange_id', 'symbol', 'timeframe', 'timestamp'], 'mktdata_exchange_symbol_tf_ts_unique');
            $table->index(['symbol', 'timeframe', 'timestamp']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_market_data');
    }
};
