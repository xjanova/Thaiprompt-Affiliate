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
        Schema::create('trading_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('strategy_type', [
                'trend_following',
                'mean_reversion',
                'breakout',
                'scalping',
                'grid_trading',
                'arbitrage',
                'ai_ml',
                'custom'
            ])->default('custom');

            // Strategy Configuration
            $table->json('indicators')->nullable(); // RSI, MACD, EMA, etc.
            $table->json('entry_conditions')->nullable();
            $table->json('exit_conditions')->nullable();
            $table->json('risk_parameters')->nullable();

            // AI/ML Settings
            $table->boolean('use_ai')->default(false);
            $table->string('ai_model')->nullable(); // lstm, transformer, etc.
            $table->json('ai_config')->nullable();
            $table->decimal('ai_confidence_threshold', 5, 2)->default(70.00);

            // Risk Management
            $table->decimal('max_position_size', 15, 8)->nullable();
            $table->decimal('stop_loss_percentage', 5, 2)->default(2.00);
            $table->decimal('take_profit_percentage', 5, 2)->default(5.00);
            $table->decimal('trailing_stop_percentage', 5, 2)->nullable();
            $table->decimal('max_daily_loss', 5, 2)->default(5.00);

            // Performance Metrics
            $table->decimal('total_pnl', 20, 8)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('max_drawdown', 5, 2)->nullable();

            // Marketplace
            $table->boolean('is_public')->default(false);
            $table->boolean('is_for_sale')->default(false);
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('times_purchased')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);

            // Status
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_run_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['is_public', 'is_for_sale']);
            $table->fullText(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_strategies');
    }
};
