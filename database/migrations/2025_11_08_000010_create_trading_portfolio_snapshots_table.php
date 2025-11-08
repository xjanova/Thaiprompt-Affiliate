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
        Schema::create('trading_portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bot_id')->nullable()->constrained('trading_bots')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('trading_accounts')->onDelete('cascade');

            $table->timestamp('snapshot_at');

            // Balance Information
            $table->decimal('total_balance', 20, 8);
            $table->decimal('available_balance', 20, 8);
            $table->decimal('in_orders', 20, 8)->default(0);
            $table->string('currency', 10);

            // Performance Metrics
            $table->decimal('total_profit', 20, 8)->default(0);
            $table->decimal('total_loss', 20, 8)->default(0);
            $table->decimal('net_profit', 20, 8)->default(0);
            $table->decimal('roi_percentage', 10, 4)->default(0);
            $table->decimal('daily_pnl', 20, 8)->default(0);

            // Risk Metrics
            $table->decimal('max_drawdown', 10, 4)->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('sortino_ratio', 10, 4)->nullable();
            $table->decimal('calmar_ratio', 10, 4)->nullable();

            // Trade Statistics
            $table->integer('open_positions')->default(0);
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);

            // Holdings (for multi-asset portfolios)
            $table->json('holdings')->nullable(); // Array of assets and quantities

            $table->timestamps();

            $table->index(['user_id', 'snapshot_at']);
            $table->index(['bot_id', 'snapshot_at']);
            $table->index('snapshot_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_portfolio_snapshots');
    }
};
