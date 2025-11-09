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
        Schema::create('trading_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->nullable()->constrained('trading_bots')->onDelete('cascade');
            $table->foreignId('strategy_id')->constrained('trading_strategies')->onDelete('cascade');

            $table->string('trading_pair');
            $table->enum('signal_type', ['buy', 'sell', 'hold'])->default('hold');
            $table->enum('timeframe', ['1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w']);

            // Signal Details
            $table->decimal('price', 20, 8);
            $table->decimal('suggested_entry', 20, 8)->nullable();
            $table->decimal('suggested_stop_loss', 20, 8)->nullable();
            $table->decimal('suggested_take_profit', 20, 8)->nullable();
            $table->decimal('suggested_position_size', 20, 8)->nullable();

            // AI/ML Confidence
            $table->decimal('confidence_score', 5, 2)->default(50.00); // 0-100%
            $table->string('signal_source')->default('indicator'); // indicator, ai, manual
            $table->json('indicators_data')->nullable(); // RSI, MACD values etc.
            $table->json('ai_prediction')->nullable(); // AI model predictions

            // Market Context
            $table->string('market_trend')->nullable(); // bullish, bearish, neutral
            $table->decimal('volatility', 10, 4)->nullable();
            $table->json('market_data')->nullable();

            // Execution
            $table->enum('status', ['pending', 'executed', 'expired', 'cancelled', 'failed'])->default('pending');
            $table->foreignId('trade_id')->nullable()->constrained('trading_trades')->onDelete('set null');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['bot_id', 'status']);
            $table->index(['trading_pair', 'signal_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_signals');
    }
};
