<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌐 (2026-07-24) เพิ่มการตั้งค่า "ปุ่มดูดวงฟรีบนเว็บ" (Web Fortune Gateway)
     *
     * ระบบ Magic Link พาลูกค้าจากบอท FB/LINE ไปดูดวงบนเว็บ จันทรา.online
     * พร้อมสมัครสมาชิก/ล็อกอินอัตโนมัติผ่าน SSO (Passport OAuth ที่มีอยู่แล้ว)
     *
     * ⚠️ ADD COLUMN — ห้ามใช้ Schema::hasTable()+return (คอลัมน์ใหม่จะไม่ถูกสร้าง)
     *    ใช้ Schema::table() + เช็คทีละคอลัมน์แทน
     *
     * default = ปิด (โค้ดพร้อมรอบน prod แต่พฤติกรรมบอทไม่เปลี่ยนจนกว่าจะเปิดสวิตช์)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เปิด/ปิดปุ่ม "ดูดวงฟรีบนเว็บ" ในบอท
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_web_fortune_button')) {
                $table->boolean('enable_web_fortune_button')->default(false)->comment('เปิดปุ่มดูดวงฟรีบนเว็บ (magic link)');
            }

            // URL ปลายทาง SSO ของเว็บจันทรา (เว้นว่าง = ใช้ค่า default ในโค้ด)
            if (! Schema::hasColumn('fortune_telling_settings', 'web_fortune_sso_url')) {
                $table->string('web_fortune_sso_url', 500)->nullable()->comment('override URL SSO เว็บจันทรา (ปกติเว้นว่าง)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'enable_web_fortune_button',
                'web_fortune_sso_url',
            ] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
