<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม index เดี่ยว platform_user_id บน fortune_readings
     *
     * 🐛 (2026-07-25) เดิมมีแค่ composite (platform, platform_user_id) ซึ่ง query แบบ
     * `orWhere('platform_user_id', ...)` (handleMyBills/handleViewBill — ปุ่ม Rich Menu
     * "ดูย้อนหลัง" กดได้ตลอดเวลา) ใช้ index นี้ไม่ได้ → full scan ทุกครั้งที่กดปุ่ม
     */
    public function up(): void
    {
        $this->safeAddIndex('fortune_readings', 'platform_user_id', 'fortune_readings_platform_user_id_idx');
    }

    /**
     * ลบ index platform_user_id
     */
    public function down(): void
    {
        $this->safeDropIndex('fortune_readings', 'fortune_readings_platform_user_id_idx');
    }
};
