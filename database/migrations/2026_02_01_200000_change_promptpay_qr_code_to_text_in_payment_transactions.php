<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * แก้ไขคอลัมน์ promptpay_qr_code จาก VARCHAR(255) เป็น TEXT
     *
     * ข้อมูล QR code แบบ base64-encoded มีขนาดเกิน 255 ตัวอักษร
     * ทำให้เกิด error: Data too long for column 'promptpay_qr_code'
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_transactions') && Schema::hasColumn('payment_transactions', 'promptpay_qr_code')) {
            DB::statement('ALTER TABLE payment_transactions MODIFY COLUMN promptpay_qr_code TEXT NULL');
        }
    }

    /**
     * คืนค่าคอลัมน์กลับเป็น VARCHAR(255)
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_transactions') && Schema::hasColumn('payment_transactions', 'promptpay_qr_code')) {
            DB::statement('ALTER TABLE payment_transactions MODIFY COLUMN promptpay_qr_code VARCHAR(255) NULL');
        }
    }
};
