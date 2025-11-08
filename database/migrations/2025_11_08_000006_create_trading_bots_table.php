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
        Schema::create('trading_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained('trading_bot_subscriptions')->onDelete('restrict');
            $table->foreignId('account_id')->constrained('trading_accounts')->onDelete('restrict');
            $table->foreignId('strategy_id')->constrained('trading_strategies')->onDelete('restrict');

            $table->string('name');
            $table->text('description')->nullable();

            // Trading Configuration
            $table->string('trading_pair'); // BTC/USDT, EUR/USD, etc.
            $table->string('base_currency', 10);
            $table->string('quote_currency', 10);
            $table->enum('timeframe', ['1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w'])->default('1h');

            // Capital Management
            $table->decimal('allocated_capital', 20, 8);
            $table->decimal('available_capital', 20, 8);
            $table->decimal('position_size_percentage', 5, 2)->default(10.00); // % of capital per trade
            $table->boolean('use_leverage')->default(false);
            $table->decimal('leverage', 5, 2)->default(1.00);

            // Performance Tracking
            $table->decimal('total_profit', 20, 8)->default(0);
            $table->decimal('total_loss', 20, 8)->default(0);
            $table->decimal('net_profit', 20, 8)->default(0);
            $table->decimal('roi_percentage', 10, 4)->default(0);
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('average_win', 20, 8)->default(0);
            $table->decimal('average_loss', 20, 8)->default(0);
            $table->decimal('profit_factor', 10, 4)->nullable();
            $table->decimal('max_consecutive_wins')->default(0);
            $table->decimal('max_consecutive_losses')->default(0);

            // Status
            $table->enum('status', ['stopped', 'running', 'paused', 'error', 'backtesting'])->default('stopped');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('last_trade_at')->nullable();
            $table->integer('uptime_seconds')->default(0);

            // Settings
            $table->boolean('dry_run')->default(false); // Paper trading
            $table->boolean('auto_restart')->default(false);
            $table->boolean('notifications_enabled')->default(true);
            $table->json('notification_channels')->nullable(); // ['email', 'line', 'telegram']
            $table->json('advanced_settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['account_id', 'status']);
            $table->index('trading_pair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_bots');
    }
};
