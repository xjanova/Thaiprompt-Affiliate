<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SMS Checker devices table
        Schema::create('sms_checker_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('device_name')->nullable();
            $table->string('api_key', 64)->unique();
            $table->string('secret_key', 64);
            $table->string('platform')->default('android');
            $table->string('app_version')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamp('last_active_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('api_key');
            $table->index('device_id');
        });

        // SMS payment notifications table
        Schema::create('sms_payment_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('bank', 20);
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->string('account_number', 50)->nullable();
            $table->string('sender_or_receiver')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->timestamp('sms_timestamp');
            $table->string('device_id');
            $table->string('nonce', 50);
            $table->enum('status', ['pending', 'matched', 'confirmed', 'rejected', 'expired'])->default('pending');
            $table->unsignedBigInteger('matched_transaction_id')->nullable();
            $table->text('raw_payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['amount', 'status']);
            $table->index(['bank', 'type']);
            $table->index('reference_number');
            $table->index('device_id');
            $table->index('nonce');
            $table->index('matched_transaction_id');
        });

        // Unique decimal amounts for payment matching
        Schema::create('unique_payment_amounts', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_amount', 15, 2);
            $table->decimal('unique_amount', 15, 2);
            $table->smallInteger('decimal_suffix');  // 01-99
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('transaction_type', 50)->nullable();  // order, topup, etc.
            $table->enum('status', ['reserved', 'used', 'expired', 'cancelled'])->default('reserved');
            $table->timestamp('expires_at');
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->index(['base_amount', 'decimal_suffix', 'status']);
            $table->index(['unique_amount', 'status']);
            $table->index('transaction_id');
            $table->index('expires_at');
        });

        // Nonce tracking for replay attack prevention
        Schema::create('sms_payment_nonces', function (Blueprint $table) {
            $table->id();
            $table->string('nonce', 50)->unique();
            $table->string('device_id');
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index(['nonce', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_payment_nonces');
        Schema::dropIfExists('unique_payment_amounts');
        Schema::dropIfExists('sms_payment_notifications');
        Schema::dropIfExists('sms_checker_devices');
    }
};
