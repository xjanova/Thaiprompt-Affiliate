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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('transaction_id')->unique(); // UUID for transaction
            $table->enum('type', ['deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'commission', 'refund', 'fee', 'bonus']);
            $table->decimal('amount', 20, 2);
            $table->decimal('balance_before', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->string('currency', 3)->default('THB');
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable(); // e.g., 'commission', 'manual', 'transfer'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the related record
            $table->foreignId('related_wallet_id')->nullable()->constrained('wallets')->onDelete('set null'); // For transfers
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Store additional info
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('wallet_id');
            $table->index('user_id');
            $table->index('transaction_id');
            $table->index('type');
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
