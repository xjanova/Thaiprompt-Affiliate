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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // Unique transaction identifier
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Transaction type: 'order_payment', 'wallet_topup', 'withdrawal', etc.
            $table->enum('type', ['order_payment', 'wallet_topup', 'withdrawal', 'refund'])->default('order_payment');

            // Payment method: 'wallet', 'promptpay', 'credit_card', 'bank_transfer'
            $table->enum('payment_method', ['wallet', 'promptpay', 'credit_card', 'bank_transfer', 'cash_on_delivery']);

            // Payment status: 'pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');

            // Amount
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('THB');

            // Related entities
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->onDelete('set null');

            // Payment gateway info
            $table->string('gateway')->nullable(); // 'stripe', 'omise', 'promptpay', etc.
            $table->string('gateway_transaction_id')->nullable(); // External transaction ID from payment gateway
            $table->json('gateway_response')->nullable(); // Full response from payment gateway

            // PromptPay specific
            $table->string('promptpay_qr_code')->nullable(); // QR code data
            $table->string('promptpay_ref_no')->nullable(); // Reference number

            // Metadata
            $table->json('metadata')->nullable(); // Additional data
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['order_id']);
            $table->index(['type', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
