<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /** ชื่อ index (สั้นกว่า 64 ตัวอักษร) */
    private const INDEX = 'ft_takeover_logs_reason_created_idx';

    /**
     * 🔎 เพิ่ม index (reason, created_at) ให้ fortune_takeover_logs
     *
     * คิว "ลูกค้าขอคุยกับคน" ของแอปแอดมิน (App\Services\AdminApp\CustomerRequestQueue) กรอง
     * reason = customer_request + created_at ย้อนหลัง 24 ชม. — เดิมมีแค่ index (action) เดี่ยว
     * ⇒ สแกนทุกแถว message/takeover ตั้งแต่เปิดระบบ ทุกครั้งที่ admin-app:push-alerts รัน (ทุก 2 นาที)
     *
     * ปลอดภัย: ตารางไม่มี → ข้าม · index มีแล้ว → ข้าม (SafeMigration::safeAddIndex เช็คให้)
     * ไม่แตะคอลัมน์เวลา (ไม่มีกับดัก ON UPDATE ของ MariaDB)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_takeover_logs')) {
            return;
        }

        $this->safeAddIndex('fortune_takeover_logs', ['reason', 'created_at'], self::INDEX);
    }

    /**
     * ลบ index ที่เพิ่ม
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_takeover_logs')) {
            return;
        }

        $this->safeDropIndex('fortune_takeover_logs', self::INDEX);
    }
};
