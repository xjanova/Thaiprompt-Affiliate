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
        Schema::create('game_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('skin_id')->nullable()->constrained('game_skins')->onDelete('set null');

            $table->string('type'); // skin_purchase, reward, refund
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('THB');
            $table->string('status')->default('completed'); // pending, completed, failed, refunded

            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            // Wallet integration
            $table->decimal('wallet_balance_before', 10, 2)->nullable();
            $table->decimal('wallet_balance_after', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_transactions');
    }
};
