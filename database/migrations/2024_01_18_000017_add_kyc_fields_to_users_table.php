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
            $table->timestamp('kyc_verified_at')->nullable()->after('is_kyc_verified');
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
                'kyc_verified_at',
                'profile_image_source',
                'use_line_avatar'
            ]);
        });
    }
};
