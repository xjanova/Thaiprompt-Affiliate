<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ payment_display_mode ในตาราง fortune_telling_settings
 *
 * ใช้สำหรับเลือกช่องทางชำระเงินที่จะแสดงให้ลูกค้าเห็น:
 * - both: แสดงทั้งบัญชีธนาคารและพร้อมเพย์
 * - bank_only: แสดงเฉพาะเลขบัญชีธนาคาร (ไม่แสดงพร้อมเพย์)
 * - promptpay_only: แสดงเฉพาะพร้อมเพย์ (ไม่แสดงเลขบัญชี)
 *
 * วัตถุประสงค์: หลีกเลี่ยง Facebook ตรวจจับเรื่องการเงินบัญชี
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'payment_display_mode', function ($table) {
                $table->string('payment_display_mode', 20)->default('both')->after('fortune_bank_account_ids');
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', 'payment_display_mode');
    }
};
