<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์สำหรับ "เข้าสู่ระบบด้วย Google" ในตาราง users
     *
     * - google_id     = รหัสบัญชี Google (claim "sub") ไม่ซ้ำกัน → 1 บัญชี Google = 1 บัญชีในระบบ
     * - google_avatar = รูปโปรไฟล์จาก Google
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return ในไฟล์นี้ (เพิ่มคอลัมน์ในตารางที่มีอยู่แล้ว)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $this->safeAddColumn($table, 'users', 'google_id', function ($table) {
                $table->string('google_id', 64)
                    ->nullable()
                    ->comment('รหัสบัญชี Google (sub) — ใช้จับคู่ตอนเข้าสู่ระบบด้วย Google');
            });

            $this->safeAddColumn($table, 'users', 'google_avatar', function ($table) {
                $table->string('google_avatar', 1000)
                    ->nullable()
                    ->comment('รูปโปรไฟล์จาก Google');
            });
        });

        // unique — กันบัญชี Google เดียวผูกหลายบัญชี (NULL ซ้ำได้ใน MySQL)
        if (Schema::hasColumn('users', 'google_id')) {
            $this->safeAddIndex('users', 'google_id', 'users_google_id_unique', 'unique');
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        $this->safeDropIndex('users', 'users_google_id_unique');
        $this->safeDropColumn('users', ['google_id', 'google_avatar']);
    }
};
