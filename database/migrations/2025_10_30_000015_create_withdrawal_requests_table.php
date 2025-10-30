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
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->string('request_id')->unique(); // Unique request ID
            $table->decimal('amount', 20, 2);
            $table->decimal('fee', 20, 2)->default(0.00);
            $table->decimal('tax', 20, 2)->default(0.00);
            $table->decimal('net_amount', 20, 2); // amount - fee - tax
            $table->string('currency', 3)->default('THB');
            $table->enum('status', ['pending', 'processing', 'approved', 'rejected', 'cancelled', 'completed'])->default('pending');

            // Payment method details
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->string('payment_type')->nullable(); // 'promptpay', 'bank_transfer', 'paypal', etc.
            $table->json('payment_details')->nullable(); // Store account details

            // Admin actions
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Transfer proof/slip
            $table->string('transfer_slip')->nullable(); // Path to uploaded slip image
            $table->timestamp('transfer_completed_at')->nullable();
            $table->text('transfer_note')->nullable();

            // User notes and metadata
            $table->text('user_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('metadata')->nullable();

            // Audit trail
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index('wallet_id');
            $table->index('request_id');
            $table->index('status');
            $table->index('payment_type');
            $table->index('approved_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
