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
        if (Schema::hasTable('crypto_withdrawal_requests')) {
            return;
        }

        Schema::create('crypto_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_currency_id')->constrained()->onDelete('cascade');

            // Request ID
            $table->string('request_id', 100)->unique()
                ->comment('CWDR + 20 chars');

            // Withdrawal details
            $table->string('to_address')->comment('Destination blockchain address');
            $table->string('network')->comment('ethereum, bsc, polygon, bitcoin, tron');
            $table->decimal('amount', 30, 18)->comment('Amount to withdraw');

            // Fees
            $table->decimal('network_fee', 30, 18)->default(0)
                ->comment('Blockchain network fee (gas)');
            $table->decimal('platform_fee', 30, 18)->default(0)
                ->comment('Platform withdrawal fee');
            $table->decimal('total_fee', 30, 18)->default(0);
            $table->decimal('net_amount', 30, 18)
                ->comment('Amount user will receive (amount - fees)');

            // Estimated gas (for approval)
            $table->string('estimated_gas_price')->nullable();
            $table->string('estimated_gas_limit')->nullable();

            // Status workflow
            $table->enum('status', [
                'pending',           // User submitted
                'verifying',         // Verifying address and amount
                'approved',          // Approved for processing
                'processing',        // Broadcasting to blockchain
                'broadcasted',       // Sent to blockchain
                'confirming',        // Waiting for confirmations
                'completed',         // Fully completed
                'rejected',          // Rejected by admin
                'cancelled',         // Cancelled by user
                'failed'             // Transaction failed
            ])->default('pending');

            // Approval workflow
            $table->boolean('requires_admin_approval')->default(false)
                ->comment('Large amounts require manual approval');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')->nullable()
                ->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Linked crypto transaction
            $table->foreignId('crypto_transaction_id')->nullable()
                ->constrained()->onDelete('set null');

            // Security verification
            $table->string('verification_code', 6)->nullable()
                ->comment('Email/SMS verification code');
            $table->timestamp('verification_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('two_factor_verified')->default(false);

            // Address validation
            $table->boolean('address_validated')->default(false);
            $table->text('address_validation_result')->nullable();

            // User notes
            $table->text('user_note')->nullable();
            $table->text('admin_note')->nullable();

            // Risk assessment
            $table->boolean('is_suspicious')->default(false);
            $table->text('risk_flags')->nullable()->comment('JSON array of risk indicators');
            $table->decimal('risk_score', 5, 2)->default(0)
                ->comment('0-100 risk score');

            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['crypto_wallet_id', 'created_at']);
            $table->index(['status', 'requires_admin_approval']);
            $table->index('request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_withdrawal_requests');
    }
};
