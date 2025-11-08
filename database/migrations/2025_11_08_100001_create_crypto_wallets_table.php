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
        Schema::create('crypto_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Wallet type
            $table->enum('wallet_type', ['external', 'custodial'])
                ->comment('external=MetaMask/WalletConnect, custodial=system-generated');

            // Wallet name (user can name their wallets)
            $table->string('name')->default('My Crypto Wallet');

            // For custodial wallets - encrypted seed phrase
            $table->text('encrypted_seed')->nullable()
                ->comment('AES-256 encrypted mnemonic seed phrase');
            $table->string('derivation_path')->default("m/44\'/60\'/0\'/0/0")
                ->comment('BIP44 derivation path');

            // Security
            $table->string('pin_hash')->nullable()->comment('Hashed PIN for custodial wallet access');
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();

            // Wallet status
            $table->enum('status', ['active', 'locked', 'suspended'])->default('active');
            $table->timestamp('locked_until')->nullable();
            $table->integer('failed_attempts')->default(0);

            // Default wallet flag
            $table->boolean('is_default')->default(false);

            // Metadata
            $table->string('device_info')->nullable()->comment('Device used to create wallet');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();

            // Verification (for external wallets)
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('signature_proof')->nullable()->comment('Signature proof of ownership');

            // Statistics
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('total_transactions')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'wallet_type', 'is_default']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_wallets');
    }
};
