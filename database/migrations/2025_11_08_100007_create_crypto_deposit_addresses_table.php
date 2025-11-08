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
        Schema::create('crypto_deposit_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_currency_id')->constrained()->onDelete('cascade');

            // Deposit address (unique per user per currency)
            $table->string('address')->comment('Generated deposit address');
            $table->string('network')->comment('ethereum, bsc, polygon, bitcoin, tron');

            // QR Code
            $table->text('qr_code')->nullable()->comment('Base64 QR code image');

            // Monitoring
            $table->boolean('is_monitored')->default(true)
                ->comment('Actively watching for deposits');
            $table->timestamp('last_checked_at')->nullable()
                ->comment('Last time we checked for new transactions');
            $table->string('last_checked_block')->nullable()
                ->comment('Last block number checked');

            // Statistics
            $table->integer('total_deposits')->default(0);
            $table->decimal('total_amount_received', 30, 18)->default(0);
            $table->timestamp('last_deposit_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();

            // Label
            $table->string('label')->nullable()->comment('User-defined label');

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes and constraints
            $table->unique(['address', 'network']);
            $table->unique(['user_id', 'crypto_currency_id', 'network']);
            $table->index(['is_monitored', 'is_active']);
            $table->index(['network', 'last_checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_deposit_addresses');
    }
};
