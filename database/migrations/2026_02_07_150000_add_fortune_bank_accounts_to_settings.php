<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ fortune_bank_account_ids ในตาราง fortune_telling_settings
 *
 * เก็บ IDs ของบัญชีธนาคารที่ใช้เฉพาะระบบดูดวง
 * แยกจากบัญชีกลาง (อีคอมเมิร์ช) เพื่อไม่ให้ปะปนกัน
 * สัมพันธ์กับ payment_bank_accounts ที่เปิด sms_checker_enabled
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ fortune_bank_account_ids
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('fortune_telling_settings', 'fortune_bank_account_ids')) {
                $table->json('fortune_bank_account_ids')
                    ->nullable()
                    ->after('payment_qr_image')
                    ->comment('IDs ของบัญชีธนาคารที่ใช้เฉพาะระบบดูดวง (JSON array)');
            }
        });
    }

    /**
     * ลบคอลัมน์ fortune_bank_account_ids
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'fortune_bank_account_ids')) {
                $table->dropColumn('fortune_bank_account_ids');
            }
        });
    }
};
