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
        Schema::table('users', function (Blueprint $table) {
            $table->string('video_referral_code')->unique()->nullable()->after('affiliate_id');
            $table->foreignId('video_referred_by')->nullable()->constrained('users')->onDelete('set null')->after('video_referral_code');
            $table->timestamp('video_referral_joined_at')->nullable()->after('video_referred_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['video_referred_by']);
            $table->dropColumn(['video_referral_code', 'video_referred_by', 'video_referral_joined_at']);
        });
    }
};
