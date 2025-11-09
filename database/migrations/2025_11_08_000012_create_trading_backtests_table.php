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
        Schema::create('trading_backtests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_id')->constrained('trading_strategies')->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();

            // Backtest Configuration
            $table->string('trading_pair');
            $table->foreignId('exchange_id')->constrained('trading_exchanges')->onDelete('restrict');
            $table->enum('timeframe', ['1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w']);
            $table->decimal('initial_capital', 20, 8);
            $table->date('start_date');
            $table->date('end_date');

            // Results
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('progress_percentage')->default(0);

            // Performance Metrics
            $table->decimal('final_balance', 20, 8)->nullable();
            $table->decimal('total_return', 20, 8)->nullable();
            $table->decimal('return_percentage', 10, 4)->nullable();
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('profit_factor', 10, 4)->nullable();
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('sortino_ratio', 10, 4)->nullable();
            $table->decimal('max_drawdown', 10, 4)->nullable();
            $table->decimal('average_win', 20, 8)->nullable();
            $table->decimal('average_loss', 20, 8)->nullable();
            $table->decimal('largest_win', 20, 8)->nullable();
            $table->decimal('largest_loss', 20, 8)->nullable();
            $table->integer('max_consecutive_wins')->default(0);
            $table->integer('max_consecutive_losses')->default(0);

            // Execution Details
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_seconds')->nullable();

            // Results Data
            $table->json('equity_curve')->nullable(); // Daily equity values
            $table->json('trade_log')->nullable(); // Summary of all trades (can be large)
            $table->json('statistics')->nullable(); // Additional statistics
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['strategy_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_backtests');
    }
};
