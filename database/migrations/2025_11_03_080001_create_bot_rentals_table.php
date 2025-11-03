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
        Schema::create('bot_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');

            // Rental Type
            $table->enum('rental_type', ['monthly', 'per_message'])->default('monthly');

            // Pricing
            $table->decimal('price', 10, 2); // Monthly price or per-message rate
            $table->decimal('commission_rate', 5, 2)->default(0); // Platform commission %

            // Period (for monthly)
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->boolean('auto_renew')->default(true);

            // Usage (for per-message)
            $table->integer('total_messages')->default(0);
            $table->decimal('total_amount', 10, 4)->default(0);

            // Status
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Billing
            $table->timestamp('last_billing_date')->nullable();
            $table->timestamp('next_billing_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['bot_profile_id', 'status']);
            $table->index(['renter_id', 'status']);
            $table->index('rental_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_rentals');
    }
};
