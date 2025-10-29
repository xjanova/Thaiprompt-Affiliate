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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 20, 2)->default(0.00);
            $table->string('currency', 3)->default('THB');
            $table->string('wallet_address')->unique(); // Unique wallet address
            $table->string('pin_hash')->nullable(); // Encrypted PIN for transactions
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();
            $table->enum('status', ['active', 'suspended', 'locked'])->default('active');
            $table->timestamp('last_transaction_at')->nullable();
            $table->decimal('total_income', 20, 2)->default(0.00);
            $table->decimal('total_expense', 20, 2)->default(0.00);
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index('wallet_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
