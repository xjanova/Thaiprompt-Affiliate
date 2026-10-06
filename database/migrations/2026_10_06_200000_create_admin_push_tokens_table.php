<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔔 สร้างตาราง admin_push_tokens — FCM token ของเครื่องที่ติดตั้งแอปแอดมิน (ไทยพร้อม แอดมิน)
     *
     * - 1 token = 1 แถว (unique) · ลงทะเบียนซ้ำ = อัปเดตแถวเดิม (เครื่องเปลี่ยนบัญชีแอดมินได้)
     * - access_token_id = Sanctum token ที่ลงทะเบียน → ออกจากระบบเครื่องนั้น = ลบ push token ของเครื่องนั้น
     * - ⚠️ คอลัมน์เวลามีแค่ timestamps() (nullable) — ห้ามเพิ่ม timestamp() เปล่า (MariaDB บน prod เติม ON UPDATE ให้)
     */
    public function up(): void
    {
        // ✅ SAFE: สร้างตารางใหม่ — มีแล้วข้าม
        if (Schema::hasTable('admin_push_tokens')) {
            return;
        }

        Schema::create('admin_push_tokens', function (Blueprint $table) {
            $table->id();

            // แอดมินเจ้าของเครื่อง — ลบผู้ใช้ = ลบ token ตาม
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Sanctum token (personal_access_tokens.id) ที่ใช้ลงทะเบียน — ไม่ทำ FK (token ถูกลบ/หมุนได้ตลอด)
            $table->unsignedBigInteger('access_token_id')->nullable();

            // FCM registration token (ความยาวไม่ตายตัว ~150–200 ตัวอักษร)
            $table->string('token', 512);
            $table->string('platform', 20)->default('android');
            $table->string('device_id', 191)->nullable();
            $table->string('app_version', 50)->nullable();

            $table->timestamps();

            $table->unique('token', 'admin_push_tokens_token_unique');
            $table->index('user_id', 'admin_push_tokens_user_idx');
            $table->index('access_token_id', 'admin_push_tokens_access_idx');
        });
    }

    /**
     * ลบตาราง admin_push_tokens
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_push_tokens');
    }
};
