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
        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('marketing_emails')->default(true);
            $table->boolean('security_alerts')->default(true);
            $table->boolean('commission_notifications')->default(true);
            $table->boolean('withdrawal_notifications')->default(true);
            $table->boolean('system_announcements')->default(true);
            $table->boolean('weekly_reports')->default(true);
            $table->boolean('all_emails')->default(true); // Master switch
            $table->string('preferred_language')->default('th');
            $table->json('custom_preferences')->nullable(); // For additional email types
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_preferences');
    }
};
