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
        Schema::create('ai_bot_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // Rental Details
            $table->enum('rental_plan', ['monthly', 'per_message'])->default('monthly');
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            // Pricing
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('price_per_message', 10, 4)->nullable();

            // Usage Tracking
            $table->integer('total_messages')->default(0);
            $table->bigInteger('total_tokens_used')->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);

            // Commission
            $table->decimal('commission_rate', 5, 2);               // % commission
            $table->decimal('platform_commission', 10, 2)->default(0);
            $table->decimal('owner_earning', 10, 2)->default(0);

            $table->timestamps();
            $table->timestamp('cancelled_at')->nullable();

            $table->index(['renter_id', 'is_active']);
            $table->index(['owner_id', 'is_active']);
            $table->index(['bot_profile_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_bot_rentals');
    }
};
