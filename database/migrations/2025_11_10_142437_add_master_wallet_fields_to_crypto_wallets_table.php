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
        Schema::table('crypto_wallets', function (Blueprint $table) {
            // HD Wallet (Hierarchical Deterministic Wallet) fields
            $table->boolean('is_master_wallet')->default(false)
                ->after('wallet_type')
                ->comment('True if this is a master wallet that can derive child wallets');

            $table->foreignId('master_wallet_id')->nullable()
                ->after('is_master_wallet')
                ->constrained('crypto_wallets')->onDelete('cascade')
                ->comment('Reference to master wallet (null if this is master wallet)');

            $table->text('master_seed_encrypted')->nullable()
                ->after('encrypted_seed')
                ->comment('Encrypted BIP39 master seed phrase (only for master wallets)');

            $table->integer('derivation_index')->default(0)
                ->after('derivation_path')
                ->comment('BIP44 account index for deriving child addresses');

            $table->integer('total_derived_wallets')->default(0)
                ->after('derivation_index')
                ->comment('Total number of child wallets derived from this master wallet');

            // Add index for better query performance
            $table->index(['master_wallet_id', 'derivation_index']);
            $table->index(['is_master_wallet', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_wallets', function (Blueprint $table) {
            $table->dropForeign(['master_wallet_id']);
            $table->dropIndex(['master_wallet_id', 'derivation_index']);
            $table->dropIndex(['is_master_wallet', 'status']);
            $table->dropColumn([
                'is_master_wallet',
                'master_wallet_id',
                'master_seed_encrypted',
                'derivation_index',
                'total_derived_wallets',
            ]);
        });
    }
};
