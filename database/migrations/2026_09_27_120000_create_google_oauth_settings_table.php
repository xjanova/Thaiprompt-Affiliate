<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง google_oauth_settings — ตั้งค่า "เข้าสู่ระบบด้วย Google" (แถวเดียว)
     *
     * แอดมินกรอก Client ID / Client secret จาก Google Cloud ที่ /admin/auth/google-oauth
     * client_secret เก็บแบบเข้ารหัส (Eloquent cast encrypted) จึงใช้ text
     * ยังไม่มีแถว / is_active = false → ปุ่ม Google ทุกที่ถูกซ่อน
     */
    public function up(): void
    {
        if (Schema::hasTable('google_oauth_settings')) {
            return;
        }

        Schema::create('google_oauth_settings', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 255)->nullable()->comment('OAuth Client ID (Web application) จาก Google Cloud');
            $table->text('client_secret')->nullable()->comment('Client secret — เข้ารหัสด้วย APP_KEY');
            $table->boolean('is_active')->default(false)->comment('เปิดให้ลูกค้ากดเข้าสู่ระบบด้วย Google');
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('total_logins')->default(0);
            $table->timestamps();
        });
    }

    /**
     * ลบตาราง google_oauth_settings
     */
    public function down(): void
    {
        Schema::dropIfExists('google_oauth_settings');
    }
};
