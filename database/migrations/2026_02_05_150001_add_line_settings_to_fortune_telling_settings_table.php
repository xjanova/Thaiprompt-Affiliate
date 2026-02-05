<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม LINE Official Account settings สำหรับระบบดูดวง
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // LINE Channel Settings
            if (!Schema::hasColumn('fortune_telling_settings', 'line_enabled')) {
                $table->boolean('line_enabled')->default(false)->after('is_enabled');
            }

            if (!Schema::hasColumn('fortune_telling_settings', 'line_channel_id')) {
                $table->string('line_channel_id', 100)->nullable()->after('line_enabled');
            }

            if (!Schema::hasColumn('fortune_telling_settings', 'line_channel_secret')) {
                $table->string('line_channel_secret', 255)->nullable()->after('line_channel_id');
            }

            if (!Schema::hasColumn('fortune_telling_settings', 'line_channel_access_token')) {
                $table->text('line_channel_access_token')->nullable()->after('line_channel_secret');
            }

            // Multi-channel settings
            if (!Schema::hasColumn('fortune_telling_settings', 'enabled_platforms')) {
                $table->json('enabled_platforms')->nullable()->after('line_channel_access_token');
            }

            // Flex Message customization
            if (!Schema::hasColumn('fortune_telling_settings', 'line_flex_primary_color')) {
                $table->string('line_flex_primary_color', 7)->default('#6B46C1')->after('enabled_platforms');
            }

            if (!Schema::hasColumn('fortune_telling_settings', 'line_welcome_image_url')) {
                $table->string('line_welcome_image_url', 500)->nullable()->after('line_flex_primary_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'line_enabled',
                'line_channel_id',
                'line_channel_secret',
                'line_channel_access_token',
                'enabled_platforms',
                'line_flex_primary_color',
                'line_welcome_image_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
