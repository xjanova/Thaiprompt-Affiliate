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
        Schema::create('trading_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('trading_bots')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('trading_accounts')->onDelete('restrict');
            $table->foreignId('signal_id')->nullable()->constrained('trading_signals')->onDelete('set null');

            $table->string('trade_id')->unique(); // Unique trade identifier
            $table->string('exchange_order_id')->nullable(); // Order ID from exchange

            // Trade Details
            $table->string('trading_pair');
            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['market', 'limit', 'stop_loss', 'take_profit'])->default('market');
            $table->enum('position_side', ['long', 'short'])->default('long');

            // Quantities and Prices
            $table->decimal('quantity', 20, 8);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8)->nullable();
            $table->decimal('average_entry', 20, 8)->nullable(); // For DCA
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();

            // Fees
            $table->decimal('entry_fee', 20, 8)->default(0);
            $table->decimal('exit_fee', 20, 8)->default(0);
            $table->decimal('total_fees', 20, 8)->default(0);
            $table->string('fee_currency', 10)->nullable();

            // P&L
            $table->decimal('gross_profit', 20, 8)->default(0);
            $table->decimal('net_profit', 20, 8)->default(0);
            $table->decimal('profit_percentage', 10, 4)->default(0);
            $table->decimal('roi', 10, 4)->default(0);

            // Risk/Reward
            $table->decimal('risk_amount', 20, 8)->nullable();
            $table->decimal('reward_amount', 20, 8)->nullable();
            $table->decimal('risk_reward_ratio', 10, 4)->nullable();

            // Status and Timing
            $table->enum('status', ['pending', 'open', 'partially_filled', 'filled', 'closed', 'cancelled', 'failed'])->default('pending');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Additional Info
            $table->text('close_reason')->nullable(); // stop_loss, take_profit, manual, signal, timeout
            $table->boolean('is_paper_trade')->default(false);
            $table->json('metadata')->nullable(); // Additional trade data
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['bot_id', 'status']);
            $table->index(['account_id', 'status']);
            $table->index('trading_pair');
            $table->index('opened_at');
            $table->index('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_trades');
    }
};
