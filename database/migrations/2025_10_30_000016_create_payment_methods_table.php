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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['promptpay', 'bank_transfer', 'stripe', 'paypal', 'other'])->default('promptpay');
            $table->string('name'); // e.g., "PromptPay", "Bangkok Bank", "PayPal"
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // PromptPay / Bank Transfer
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('branch')->nullable();

            // PayPal
            $table->string('paypal_email')->nullable();

            // Stripe
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();

            // QR Code for PromptPay
            $table->text('qr_code')->nullable();

            // Additional details
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
