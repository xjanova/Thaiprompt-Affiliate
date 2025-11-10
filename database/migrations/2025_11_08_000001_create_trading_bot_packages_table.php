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
        Schema::create('trading_bot_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly', 'lifetime'])->default('monthly');

            // Feature limits
            $table->integer('max_bots')->default(1);
            $table->integer('max_exchanges')->default(1);
            $table->integer('max_strategies')->default(3);
            $table->integer('max_active_trades')->default(5);
            $table->boolean('ai_signals')->default(false);
            $table->boolean('advanced_analytics')->default(false);
            $table->boolean('backtesting')->default(false);
            $table->boolean('paper_trading')->default(true);
            $table->boolean('copy_trading')->default(false);
            $table->boolean('strategy_marketplace')->default(false);
            $table->boolean('custom_indicators')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('priority_support')->default(false);

            // Display settings
            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // Additional features as JSON

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_bot_packages');
    }
};
