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
        Schema::table('otp_settings', function (Blueprint $table) {
            // Update provider enum to include 'line'
            $table->string('provider')->default('twilio')->change(); // twilio, nexmo, custom, line

            // LINE OA Settings for messaging
            $table->boolean('enable_line_otp')->default(false)->after('enabled');
            $table->text('line_otp_message_template')->nullable()->after('enable_line_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_settings', function (Blueprint $table) {
            $table->dropColumn(['enable_line_otp', 'line_otp_message_template']);
        });
    }
};
