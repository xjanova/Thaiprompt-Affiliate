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
        Schema::dropIfExists('vendor_subscriptions');
        Schema::create('vendor_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('vendor_packages')->onDelete('restrict');

            // Subscription Details
            $table->enum('subscription_type', ['trial', 'monthly', 'yearly']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('THB');

            // Period
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Payment
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            // Renewal
            $table->boolean('auto_renew')->default(true);
            $table->date('next_billing_date')->nullable();

            // Status
            $table->enum('status', ['active', 'cancelled', 'expired', 'suspended'])->default('active');
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'package_id']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_subscriptions');
    }
};
