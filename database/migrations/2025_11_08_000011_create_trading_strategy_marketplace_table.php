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
        Schema::create('trading_strategy_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('strategy_id')->constrained('trading_strategies')->onDelete('restrict');

            $table->decimal('price', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->decimal('commission_percentage', 5, 2)->default(10.00); // Platform commission
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('seller_payout', 15, 2);

            $table->enum('status', ['pending', 'completed', 'refunded', 'disputed'])->default('pending');
            $table->string('payment_id')->nullable();
            $table->string('payment_method')->nullable();

            $table->timestamp('purchased_at');
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();

            $table->timestamps();

            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index('strategy_id');
        });

        Schema::create('trading_strategy_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_id')->constrained('trading_strategies')->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('trading_strategy_purchases')->onDelete('set null');

            $table->integer('rating'); // 1-5
            $table->text('review')->nullable();
            $table->json('performance_rating')->nullable(); // Breakdown: profitability, reliability, etc.

            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('helpful_count')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'strategy_id']);
            $table->index('strategy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_strategy_reviews');
        Schema::dropIfExists('trading_strategy_purchases');
    }
};
