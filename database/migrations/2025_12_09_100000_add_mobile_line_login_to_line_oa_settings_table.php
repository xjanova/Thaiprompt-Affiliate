<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม Mobile LINE Login fields ในตาราง line_oa_settings
 *
 * สำหรับแยก LINE Login channel ระหว่าง Web และ Mobile App
 * เพื่อให้ Mobile App ใช้ LINE SDK ทำ native login ได้
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            // เพิ่ม Mobile LINE Login columns
            if (!Schema::hasColumn('line_oa_settings', 'mobile_login_channel_id')) {
                $table->string('mobile_login_channel_id')->nullable()
                    ->after('redirect_uri')
                    ->comment('LINE Login Channel ID สำหรับ Mobile App');
            }

            if (!Schema::hasColumn('line_oa_settings', 'mobile_login_channel_secret')) {
                $table->string('mobile_login_channel_secret')->nullable()
                    ->after('mobile_login_channel_id')
                    ->comment('LINE Login Channel Secret สำหรับ Mobile App');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            if (Schema::hasColumn('line_oa_settings', 'mobile_login_channel_id')) {
                $table->dropColumn('mobile_login_channel_id');
            }

            if (Schema::hasColumn('line_oa_settings', 'mobile_login_channel_secret')) {
                $table->dropColumn('mobile_login_channel_secret');
            }
        });
    }
};
