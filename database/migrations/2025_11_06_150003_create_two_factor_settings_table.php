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
        Schema::create('two_factor_settings', function (Blueprint $table) {
            $table->id();

            // Global 2FA enable/disable
            $table->boolean('enabled')->default(false);

            // 2FA enforcement settings by action
            $table->boolean('require_on_login')->default(false);
            $table->boolean('require_on_withdrawal')->default(true);
            $table->boolean('require_on_transfer')->default(true);
            $table->boolean('require_on_profile_change')->default(false);
            $table->boolean('require_on_password_change')->default(false);
            $table->boolean('require_on_payment_method_change')->default(false);

            // Role-based 2FA settings
            $table->json('role_requirements')->nullable(); // Custom requirements per role

            // 2FA methods allowed
            $table->boolean('allow_sms')->default(true);
            $table->boolean('allow_line')->default(true);
            $table->boolean('allow_email')->default(false);

            // Default method
            $table->string('default_method')->default('sms'); // sms, line, email

            // Grace period after login (minutes)
            $table->integer('grace_period_minutes')->default(15);

            // Remember device feature
            $table->boolean('allow_remember_device')->default(true);
            $table->integer('remember_device_days')->default(30);

            // Withdrawal threshold (require 2FA only above this amount)
            $table->decimal('withdrawal_threshold', 15, 2)->nullable();
            $table->decimal('transfer_threshold', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_settings');
    }
};
