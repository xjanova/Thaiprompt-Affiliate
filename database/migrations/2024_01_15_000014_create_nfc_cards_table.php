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
        Schema::create('nfc_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_uid')->unique()->comment('NFC Card Unique ID');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('wallet_id')->nullable()->constrained()->onDelete('set null');
            $table->string('card_type')->default('standard')->comment('standard, premium, vip');
            $table->string('card_name')->nullable()->comment('Custom name for the card');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('linked_at')->nullable()->comment('When card was linked to user');
            $table->foreignId('linked_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin who linked the card');
            $table->text('notes')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('card_uid');
            $table->index(['user_id', 'is_active']);
        });

        // NFC card transactions log
        Schema::create('nfc_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfc_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type')->comment('payment, balance_check, link, unlink');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('success')->comment('success, failed, pending');
            $table->string('terminal_id')->nullable()->comment('POS or terminal ID');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->index('nfc_card_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_card_transactions');
        Schema::dropIfExists('nfc_cards');
    }
};
