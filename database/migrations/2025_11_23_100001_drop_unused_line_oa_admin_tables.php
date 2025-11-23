<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * ลบตารางที่ไม่ใช้แล้วสำหรับ LINE OA Admin
     *
     * ระบบ LINE OA ด้านแอดมิน (admin/line-bot/flex) ไม่ใช้แล้ว
     * ใช้ admin/line-membership-signup/templates แทน
     *
     * ระบบ Chat Widget ที่ใช้ avatar ก็ไม่ใช้แล้ว
     * เพราะจะทำในส่วน AI แยกต่างหาก
     *
     * @return void
     */
    public function up(): void
    {
        // ลบตาราง line_flex_message_templates (ไม่ใช้แล้ว)
        $this->safeDropTable('line_flex_message_templates');

        // ลบตาราง line_chat_widget_settings (Chat Widget ไม่ใช้แล้ว)
        $this->safeDropTable('line_chat_widget_settings');

        // ลบตาราง line_avatars (Avatar system ไม่ใช้แล้ว)
        $this->safeDropTable('line_avatars');
    }

    /**
     * สร้างตารางกลับคืน (ถ้า rollback)
     *
     * หมายเหตุ: migration นี้เป็นการลบตาราง
     * ถ้า rollback จะไม่สร้างตารางกลับคืนเพราะโครงสร้างตารางเดิม
     * อยู่ใน migrations เก่าแล้ว
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่ทำอะไร - ตารางเดิมจะถูกสร้างโดย migrations ต้นฉบับ
        // ถ้าต้องการ rollback ให้ใช้: php artisan migrate:rollback --step=4
        // เพื่อ rollback ไปยัง migrations ที่สร้างตารางเหล่านี้
    }
};
