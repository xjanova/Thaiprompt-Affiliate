<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ระบบ Affiliate/MLM ในตาราง fortune_telling_settings
 *
 * ใช้สำหรับ:
 * - เปิด/ปิดระบบ affiliate อัตโนมัติสำหรับลูกค้าดูดวง LINE
 * - ตั้งค่า PV และคอมมิชชั่นสำหรับการดูดวง
 * - ข้อความเชิญชวนกำหนดเอง
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เปิด/ปิดระบบ affiliate อัตโนมัติสำหรับลูกค้าดูดวง
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_affiliate_enabled', function ($table) {
                $table->boolean('fortune_affiliate_enabled')->default(false)->after('payment_display_mode');
            });

            // เปิด/ปิดการลงทะเบียน User อัตโนมัติ
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_auto_register_enabled', function ($table) {
                $table->boolean('fortune_auto_register_enabled')->default(true)->after('fortune_affiliate_enabled');
            });

            // ค่า PV ของการดูดวง (เช่น 49 PV สำหรับราคา 49 บาท)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_pv_value', function ($table) {
                $table->decimal('fortune_pv_value', 10, 2)->default(0)->after('fortune_auto_register_enabled');
            });

            // ใช้อัตราคอมมิชชั่นกลาง (MlmGlobalSetting) หรือกำหนดเอง
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_use_global_commission_rate', function ($table) {
                $table->boolean('fortune_use_global_commission_rate')->default(true)->after('fortune_pv_value');
            });

            // อัตราคอมมิชชั่นกำหนดเอง (ใช้เมื่อไม่ใช้อัตรากลาง)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_custom_commission_per_pv', function ($table) {
                $table->decimal('fortune_custom_commission_per_pv', 10, 2)->nullable()->after('fortune_use_global_commission_rate');
            });

            // ข้อความเชิญชวน affiliate กำหนดเอง
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_affiliate_invite_message', function ($table) {
                $table->text('fortune_affiliate_invite_message')->nullable()->after('fortune_custom_commission_per_pv');
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', [
            'fortune_affiliate_enabled',
            'fortune_auto_register_enabled',
            'fortune_pv_value',
            'fortune_use_global_commission_rate',
            'fortune_custom_commission_per_pv',
            'fortune_affiliate_invite_message',
        ]);
    }
};
