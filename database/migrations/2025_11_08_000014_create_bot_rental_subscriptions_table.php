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
        Schema::create('bot_rental_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('bot_marketplace_listings')->onDelete('cascade');
            $table->foreignId('automation_id')->constrained('bot_automations')->onDelete('cascade');

            $table->string('subscription_id')->unique();
            $table->enum('status', ['trial', 'active', 'paused', 'cancelled', 'expired'])->default('active');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one_time', 'per_use'])->default('monthly');

            $table->decimal('price_paid', 10, 2);
            $table->decimal('platform_commission', 10, 2);
            $table->decimal('owner_earning', 10, 2);

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->integer('usage_count')->default(0); // For per-use billing
            $table->integer('usage_limit')->nullable(); // Max uses per period
            $table->decimal('total_spent', 12, 2)->default(0);

            $table->json('settings')->nullable(); // User-specific settings
            $table->text('cancellation_reason')->nullable();
            $table->boolean('auto_renew')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['listing_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_rental_subscriptions');
    }
};
