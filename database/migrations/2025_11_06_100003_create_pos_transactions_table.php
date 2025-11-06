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
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->foreignId('pos_session_id')->constrained('pos_sessions')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('vendor_stores')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // cashier
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete(); // optional customer

            // Transaction Info
            $table->string('transaction_code')->unique(); // POS-YYYYMMDD-XXXXXX
            $table->string('receipt_number')->unique();
            $table->timestamp('transaction_date');

            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(7); // VAT 7%
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            // Payment
            $table->enum('payment_method', ['cash', 'card', 'qr', 'e-wallet', 'credit', 'multiple'])->default('cash');
            $table->json('payment_details')->nullable(); // for multiple payments
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['completed', 'refunded', 'partial_refund', 'void'])->default('completed');

            // Discount & Coupon
            $table->string('discount_code')->nullable();
            $table->string('discount_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users'); // for manager approval

            // Customer Info (snapshot)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();

            // Items Summary
            $table->integer('total_items')->default(0);
            $table->integer('total_quantity')->default(0);

            // Status & Sync
            $table->enum('status', ['completed', 'refunded', 'void'])->default('completed');
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->boolean('created_offline')->default(false);

            // Notes
            $table->text('notes')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('pos_device_id');
            $table->index('pos_session_id');
            $table->index('store_id');
            $table->index('transaction_code');
            $table->index('transaction_date');
            $table->index('payment_status');
            $table->index('is_synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
