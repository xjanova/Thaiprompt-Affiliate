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
        Schema::create('trading_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exchange_id')->constrained('trading_exchanges')->onDelete('restrict');

            $table->string('account_name');
            $table->enum('account_type', ['live', 'demo', 'paper'])->default('paper');
            $table->enum('market_type', ['spot', 'futures', 'margin'])->default('spot');

            // Encrypted API Credentials
            $table->text('api_key')->nullable(); // Encrypted
            $table->text('api_secret')->nullable(); // Encrypted
            $table->text('api_passphrase')->nullable(); // For exchanges that require it (encrypted)
            $table->text('additional_credentials')->nullable(); // JSON for MT4/MT5 server details etc.

            // Account Info
            $table->decimal('initial_balance', 20, 8)->nullable();
            $table->decimal('current_balance', 20, 8)->nullable();
            $table->string('balance_currency', 10)->default('USDT');
            $table->timestamp('last_sync_at')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'error', 'pending_verification'])->default('pending_verification');
            $table->text('error_message')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Settings
            $table->boolean('allow_trading')->default(false);
            $table->boolean('notifications_enabled')->default(true);
            $table->json('settings')->nullable(); // Additional settings

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['exchange_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_accounts');
    }
};
