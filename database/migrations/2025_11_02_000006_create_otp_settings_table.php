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
        Schema::create('otp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('twilio'); // twilio, nexmo, custom
            $table->boolean('enabled')->default(false);

            // Twilio Settings
            $table->string('twilio_sid')->nullable();
            $table->string('twilio_token')->nullable();
            $table->string('twilio_from')->nullable();

            // Nexmo/Vonage Settings
            $table->string('nexmo_key')->nullable();
            $table->string('nexmo_secret')->nullable();
            $table->string('nexmo_from')->nullable();

            // Custom API Settings
            $table->string('custom_api_url')->nullable();
            $table->string('custom_api_key')->nullable();
            $table->text('custom_api_headers')->nullable();

            // OTP Configuration
            $table->integer('otp_length')->default(6);
            $table->integer('otp_expiry_minutes')->default(5);
            $table->integer('max_attempts')->default(3);
            $table->boolean('alphanumeric')->default(false);

            // Rate Limiting
            $table->integer('rate_limit_per_phone')->default(3); // per hour
            $table->integer('rate_limit_window')->default(60); // minutes

            // Message Template
            $table->text('message_template')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_settings');
    }
};
