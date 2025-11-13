<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpix_token_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tpix_tokens')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('wallet_address', 42)->comment('User wallet address on TPIX');

            // Balance Information
            $table->decimal('balance', 30, 8)->default(0)->comment('Current balance');
            $table->decimal('locked_balance', 30, 8)->default(0)->comment('Locked/vesting balance');
            $table->decimal('staked_balance', 30, 8)->default(0)->comment('Staked balance');
            $table->decimal('available_balance', 30, 8)->default(0)->comment('Available for transfer');

            // Statistics
            $table->decimal('total_received', 30, 8)->default(0);
            $table->decimal('total_sent', 30, 8)->default(0);
            $table->bigInteger('transfers_count')->default(0);
            $table->timestamp('first_received_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            // Status
            $table->boolean('is_frozen')->default(false)->comment('Account frozen by token owner');
            $table->text('freeze_reason')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['token_id', 'user_id']);
            $table->unique(['token_id', 'wallet_address']);

            // Indexes
            $table->index(['token_id', 'balance']);
            $table->index(['user_id', 'balance']);
            $table->index('is_frozen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_token_balances');
    }
};
