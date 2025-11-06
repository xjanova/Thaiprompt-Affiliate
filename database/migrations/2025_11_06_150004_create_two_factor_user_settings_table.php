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
        Schema::create('two_factor_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 2FA enabled for this user
            $table->boolean('enabled')->default(false);

            // User's preferred 2FA method
            $table->string('preferred_method')->default('sms'); // sms, line, email

            // Backup methods
            $table->json('backup_methods')->nullable(); // ['line', 'email']

            // Phone for SMS 2FA
            $table->string('phone')->nullable();
            $table->boolean('phone_verified')->default(false);
            $table->timestamp('phone_verified_at')->nullable();

            // LINE for LINE 2FA
            $table->string('line_user_id')->nullable();
            $table->boolean('line_verified')->default(false);
            $table->timestamp('line_verified_at')->nullable();

            // Email for Email 2FA
            $table->string('email')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();

            // Last 2FA verification
            $table->timestamp('last_verified_at')->nullable();
            $table->ipAddress('last_verified_ip')->nullable();

            // Trusted devices (JSON: device fingerprints)
            $table->json('trusted_devices')->nullable();

            // Recovery codes for account recovery
            $table->json('recovery_codes')->nullable();
            $table->timestamp('recovery_codes_generated_at')->nullable();

            // Statistics
            $table->integer('total_verifications')->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('last_failed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique('user_id');
            $table->index('phone');
            $table->index('line_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_user_settings');
    }
};
