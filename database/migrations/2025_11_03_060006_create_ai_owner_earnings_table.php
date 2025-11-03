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
        Schema::create('ai_owner_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');
            $table->foreignId('rental_id')->nullable()->constrained('ai_bot_rentals')->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained('ai_rental_transactions')->onDelete('set null');

            // Amounts
            $table->decimal('gross_amount', 10, 4); // Total before commission
            $table->decimal('commission', 10, 4); // Platform commission
            $table->decimal('net_amount', 10, 4); // Owner receives

            // Period
            $table->date('earning_date');
            $table->string('earning_month', 7); // Format: YYYY-MM for grouping

            // Payout
            $table->enum('payout_status', ['pending', 'processing', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('payout_date')->nullable();
            $table->string('payout_method')->nullable();
            $table->string('payout_reference')->nullable();

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['owner_id', 'earning_date']);
            $table->index(['bot_profile_id', 'earning_date']);
            $table->index('earning_month');
            $table->index('payout_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_owner_earnings');
    }
};
