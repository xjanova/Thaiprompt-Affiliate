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
        Schema::table('otp_verifications', function (Blueprint $table) {
            // Add user_id to link OTP to specific user
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Add channel to support multiple delivery methods
            $table->string('channel')->default('sms')->after('phone'); // sms, line, email

            // Make phone nullable since LINE users might not have phone
            $table->string('phone')->nullable()->change();

            // Add LINE user ID for LINE channel
            $table->string('line_user_id')->nullable()->after('channel');

            // Add index for better performance
            $table->index(['user_id', 'purpose', 'verified']);
            $table->index('line_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'purpose', 'verified']);
            $table->dropIndex(['line_user_id']);
            $table->dropColumn(['user_id', 'channel', 'line_user_id']);

            // Revert phone to NOT NULL
            $table->string('phone')->nullable(false)->change();
        });
    }
};
