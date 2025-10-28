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
            $table->boolean('is_kyc_verified')->default(false)->after('line_display_name');
            // Note: kyc_verified_at is already added in 2024_01_16_000016_create_line_oa_kyc_tables
            $table->string('profile_image_source')->default('default')->after('avatar')->comment('default, line, upload');
            $table->boolean('use_line_avatar')->default(true)->after('profile_image_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_kyc_verified',
                // 'kyc_verified_at', // Dropped in 2024_01_16_000016_create_line_oa_kyc_tables
                'profile_image_source',
                'use_line_avatar'
            ]);
        });
    }
};
