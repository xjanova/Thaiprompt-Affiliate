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
        Schema::create('ai_gen_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained('ai_gen_packages')->onDelete('cascade');

            // Credits
            $table->integer('image_credits_total')->default(0);
            $table->integer('image_credits_used')->default(0);
            $table->integer('video_credits_total')->default(0);
            $table->integer('video_credits_used')->default(0);

            // Dates
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Status
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');

            // Payment
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_subscriptions');
    }
};
