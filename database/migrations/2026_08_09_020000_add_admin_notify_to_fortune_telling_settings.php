<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์แจ้งเตือนแอดมินทาง Facebook Messenger
     *
     * ทำไมเลือก Messenger ไม่ใช่ LINE:
     * LINE OA คิดโควต้าทุก push (Notify ปิดบริการไปแล้ว ไม่มี push ฟรีเหลือ)
     * แต่ Messenger ส่งฟรีไม่จำกัด ติดแค่กรอบ 24 ชม. นับจากที่แอดมินตอบครั้งล่าสุด
     * → ข้อความเตือนรายวันจึงชวนให้แอดมินพิมพ์ตอบ ซึ่งต่ออายุกรอบ 24 ชม. ไปในตัว
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     *    (จะทำให้คอลัมน์ใหม่ไม่ถูกสร้างบนเครื่องที่มีตารางอยู่แล้ว)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'admin_notify_psid')) {
                $table->string('admin_notify_psid', 100)
                    ->nullable()
                    ->comment('PSID ของแอดมินที่รับแจ้งเตือนทาง Messenger (ผูกผ่านรหัสในหน้าแอดมิน)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'admin_notify_enabled')) {
                $table->boolean('admin_notify_enabled')
                    ->default(true)
                    ->comment('เปิด/ปิดการแจ้งเตือนแอดมินทาง Messenger');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'admin_notify_psid')) {
                $table->dropColumn('admin_notify_psid');
            }

            if (Schema::hasColumn('fortune_telling_settings', 'admin_notify_enabled')) {
                $table->dropColumn('admin_notify_enabled');
            }
        });
    }
};
