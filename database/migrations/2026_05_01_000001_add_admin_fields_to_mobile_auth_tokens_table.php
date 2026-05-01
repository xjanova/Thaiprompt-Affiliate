<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์สำหรับ Admin QR Pairing ในตาราง mobile_auth_tokens
 *
 * ใช้ตารางเดิมเพื่อ reuse PKCE infrastructure แต่เพิ่มฟิลด์
 * เฉพาะ Admin Mobile App pairing flow:
 *
 * - is_admin           : true = token นี้สำหรับ admin app
 * - pair_code          : รหัสสั้น 8 ตัวอักษรสำหรับ QR pairing (ทางเลือกแทน login_token)
 * - pair_code_expires_at: หมดอายุ pair_code (5 นาที)
 * - generator_user_id  : Admin ที่สร้าง pair_code (web side)
 * - requires_2fa       : ต้องผ่าน 2FA ก่อนเปลี่ยนเป็น token จริง
 * - two_factor_passed  : ผ่าน 2FA แล้วหรือยัง
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * รัน migration
     */
    public function up(): void
    {
        Schema::table('mobile_auth_tokens', function (Blueprint $table) {
            // ✅ ใช้ SafeMigration trait เช็ค column ก่อน
            $this->safeAddColumn($table, 'mobile_auth_tokens', 'is_admin', function ($table) {
                $table->boolean('is_admin')
                    ->default(false)
                    ->after('user_id')
                    ->comment('true = admin app pairing flow');
            });

            $this->safeAddColumn($table, 'mobile_auth_tokens', 'pair_code', function ($table) {
                $table->string('pair_code', 16)
                    ->nullable()
                    ->after('is_admin')
                    ->comment('รหัส QR pairing (8 ตัว uppercase) สำหรับ admin app');
            });

            $this->safeAddColumn($table, 'mobile_auth_tokens', 'pair_code_expires_at', function ($table) {
                $table->timestamp('pair_code_expires_at')
                    ->nullable()
                    ->after('pair_code');
            });

            $this->safeAddColumn($table, 'mobile_auth_tokens', 'generator_user_id', function ($table) {
                $table->foreignId('generator_user_id')
                    ->nullable()
                    ->after('pair_code_expires_at')
                    ->comment('Admin ผู้สร้าง pair_code (web side)')
                    ->constrained('users')
                    ->nullOnDelete();
            });

            $this->safeAddColumn($table, 'mobile_auth_tokens', 'requires_2fa', function ($table) {
                $table->boolean('requires_2fa')
                    ->default(false)
                    ->after('generator_user_id');
            });

            $this->safeAddColumn($table, 'mobile_auth_tokens', 'two_factor_passed', function ($table) {
                $table->boolean('two_factor_passed')
                    ->default(false)
                    ->after('requires_2fa');
            });

            $this->safeAddColumn($table, 'mobile_auth_tokens', 'claimed_at', function ($table) {
                $table->timestamp('claimed_at')
                    ->nullable()
                    ->after('used_at')
                    ->comment('เวลาที่ app เริ่ม claim pair_code');
            });
        });

        // เพิ่ม index สำหรับการค้นหาที่เร็วขึ้น
        $this->safeAddIndex('mobile_auth_tokens', 'pair_code');
        $this->safeAddIndex('mobile_auth_tokens', 'is_admin');
    }

    /**
     * ย้อน migration
     */
    public function down(): void
    {
        $this->safeDropIndex('mobile_auth_tokens', 'mobile_auth_tokens_pair_code_index');
        $this->safeDropIndex('mobile_auth_tokens', 'mobile_auth_tokens_is_admin_index');

        Schema::table('mobile_auth_tokens', function (Blueprint $table) {
            // ลบ foreign key ก่อนลบ column
            if (Schema::hasColumn('mobile_auth_tokens', 'generator_user_id')) {
                $table->dropForeign(['generator_user_id']);
            }
        });

        $this->safeDropColumn('mobile_auth_tokens', [
            'is_admin',
            'pair_code',
            'pair_code_expires_at',
            'generator_user_id',
            'requires_2fa',
            'two_factor_passed',
            'claimed_at',
        ]);
    }
};
