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
        Schema::create('email_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // gmail_api, gmail_smtp, ses, mailgun, etc.
            $table->string('display_name'); // For UI
            $table->enum('type', ['smtp', 'api'])->default('smtp');
            $table->json('configuration'); // Encrypted credentials and settings
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0); // For fallback order
            $table->integer('daily_limit')->nullable(); // Max emails per day
            $table->integer('hourly_limit')->nullable(); // Max emails per hour
            $table->integer('sent_today')->default(0);
            $table->integer('sent_this_hour')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->json('health_check')->nullable(); // Last health check result
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_providers');
    }
};
