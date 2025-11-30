<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม Google Authenticator (TOTP) fields
 *
 * เปลี่ยนระบบ 2FA จาก OTP (SMS/LINE) เป็น Google Authenticator
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง two_factor_settings มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('two_factor_settings')) {
            return;
        }

        // ตรวจสอบว่าตาราง two_factor_user_settings มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('two_factor_user_settings')) {
            return;
        }

        Schema::table('two_factor_user_settings', function (Blueprint $table) {
            // Google Authenticator Secret Key (encrypted)
            if (!Schema::hasColumn('two_factor_user_settings', 'google2fa_secret')) {
                $table->text('google2fa_secret')->nullable()->after('preferred_method');
            }

            // เปลี่ยน default preferred_method เป็น authenticator
            if (Schema::hasColumn('two_factor_user_settings', 'preferred_method')) {
                $table->string('preferred_method')->default('authenticator')->change();
            }
        });

        // อัพเดท global settings ให้ใช้ authenticator เป็น default
        Schema::table('two_factor_settings', function (Blueprint $table) {
            // เพิ่ม allow_authenticator
            if (!Schema::hasColumn('two_factor_settings', 'allow_authenticator')) {
                $table->boolean('allow_authenticator')->default(true)->after('enabled');
            }

            // เปลี่ยน default method
            if (Schema::hasColumn('two_factor_settings', 'default_method')) {
                $table->string('default_method')->default('authenticator')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('two_factor_user_settings', function (Blueprint $table) {
            if (Schema::hasColumn('two_factor_user_settings', 'google2fa_secret')) {
                $table->dropColumn('google2fa_secret');
            }
        });

        Schema::table('two_factor_settings', function (Blueprint $table) {
            if (Schema::hasColumn('two_factor_settings', 'allow_authenticator')) {
                $table->dropColumn('allow_authenticator');
            }
        });
    }
};
