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
        Schema::create('trading_bot_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained('trading_bot_packages')->onDelete('restrict');

            $table->enum('status', ['active', 'paused', 'cancelled', 'expired', 'trial'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Usage tracking
            $table->integer('bots_used')->default(0);
            $table->integer('exchanges_connected')->default(0);
            $table->integer('strategies_created')->default(0);
            $table->integer('trades_executed')->default(0);

            // Billing
            $table->string('payment_gateway')->nullable();
            $table->string('payment_id')->nullable();
            $table->boolean('auto_renew')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_bot_subscriptions');
    }
};
