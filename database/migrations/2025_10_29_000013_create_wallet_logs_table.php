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
        Schema::create('wallet_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('wallet_transactions')->onDelete('set null');
            $table->enum('action', [
                'login',
                'logout',
                'transaction_attempt',
                'transaction_success',
                'transaction_failed',
                'pin_changed',
                'pin_failed',
                'two_factor_enabled',
                'two_factor_disabled',
                'wallet_locked',
                'wallet_unlocked',
                'suspicious_activity',
                'settings_changed'
            ]);
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index('wallet_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_logs');
    }
};
