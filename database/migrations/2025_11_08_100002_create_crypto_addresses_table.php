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
        if (Schema::hasTable('crypto_addresses')) {
            return;
        }

        Schema::create('crypto_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_currency_id')->constrained()->onDelete('cascade');

            // Address info
            $table->string('address')->nullable()->comment('Blockchain address');
            $table->string('network')->comment('ethereum, bsc, polygon, bitcoin, tron');

            // For external wallets
            $table->boolean('is_imported')->default(false)
                ->comment('true if from MetaMask/WalletConnect');

            // For custodial wallets
            $table->text('encrypted_private_key')->nullable()
                ->comment('AES-256 encrypted private key');
            $table->string('public_key')->nullable();
            $table->integer('address_index')->default(0)
                ->comment('HD wallet address index');

            // Balance tracking (cached from blockchain)
            $table->decimal('balance', 30, 18)->default(0);
            $table->timestamp('balance_updated_at')->nullable();

            // Label and notes
            $table->string('label')->nullable()->comment('User-defined label');
            $table->text('notes')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_watched')->default(true)
                ->comment('Monitor this address for incoming transactions');

            // Statistics
            $table->integer('total_received_transactions')->default(0);
            $table->integer('total_sent_transactions')->default(0);
            $table->timestamp('last_transaction_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes and constraints
            $table->unique(['address', 'network']);
            $table->index(['crypto_wallet_id', 'network']);
            $table->index(['crypto_currency_id', 'is_active']);
            $table->index(['network', 'is_watched']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_addresses');
    }
};
