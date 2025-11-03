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
        Schema::create('rental_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('bot_rentals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Transaction Type
            $table->enum('type', ['subscription', 'usage', 'refund'])->default('subscription');

            // Amount
            $table->decimal('amount', 10, 4); // Total amount
            $table->decimal('platform_commission', 10, 4)->default(0); // Platform cut
            $table->decimal('owner_earning', 10, 4)->default(0); // Owner gets

            // Usage details (for per-message)
            $table->integer('message_count')->default(0);
            $table->decimal('rate_per_message', 10, 4)->nullable();

            // Period (for monthly subscription)
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            // Payment info
            $table->string('payment_method')->nullable(); // wallet, credit_card, etc.
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['rental_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_transactions');
    }
};
