<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม columns สำหรับ Facebook OAuth ใน users table
     *
     * ทำไมต้องมี:
     * เดิม users มีแค่ line_user_id — ลูกค้า Facebook ที่จ่ายดูดวงไม่มีทาง login
     * (ต้องรู้ email/password ที่ระบบ generate ให้)
     *
     * ใหม่: ลูกค้า Facebook กด "Login with Facebook" → Socialite ส่ง FB user data
     * → match เข้ากับ User ที่ auto-register จากดูดวง → login ทันที
     *
     * Match priority (ใน FacebookLoginController::callback):
     *   1. facebook_user_id (app-scoped FB ID) ตรง → existing user
     *   2. email ตรง → link FB ให้ existing user
     *   3. ไม่เจอ → สร้างใหม่
     *
     * fields:
     * - facebook_user_id: app-scoped FB ID (ต่างจาก Messenger PSID)
     * - facebook_email: email จาก FB (อาจไม่เท่า user.email)
     * - facebook_name: display name จาก FB profile
     * - facebook_picture_url: รูป profile
     * - facebook_verified: true เมื่อ login ผ่าน FB OAuth สำเร็จ
     * - facebook_linked_at: timestamp ลิงก์
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $this->safeAddColumn($table, 'users', 'facebook_user_id', function ($table) {
                $table->string('facebook_user_id', 64)
                    ->nullable()
                    ->after('line_user_id')
                    ->comment('Facebook app-scoped user ID (จาก Socialite OAuth)');
            });

            $this->safeAddColumn($table, 'users', 'facebook_email', function ($table) {
                $table->string('facebook_email', 191)
                    ->nullable()
                    ->after('facebook_user_id')
                    ->comment('Email จาก Facebook (อาจไม่เท่า user.email)');
            });

            $this->safeAddColumn($table, 'users', 'facebook_name', function ($table) {
                $table->string('facebook_name', 191)
                    ->nullable()
                    ->after('facebook_email')
                    ->comment('Display name จาก Facebook profile');
            });

            $this->safeAddColumn($table, 'users', 'facebook_picture_url', function ($table) {
                $table->string('facebook_picture_url', 500)
                    ->nullable()
                    ->after('facebook_name')
                    ->comment('URL รูป profile จาก Facebook');
            });

            $this->safeAddColumn($table, 'users', 'facebook_verified', function ($table) {
                $table->boolean('facebook_verified')
                    ->default(false)
                    ->after('facebook_picture_url')
                    ->comment('true = login ผ่าน FB OAuth สำเร็จแล้ว');
            });

            $this->safeAddColumn($table, 'users', 'facebook_linked_at', function ($table) {
                $table->timestamp('facebook_linked_at')
                    ->nullable()
                    ->after('facebook_verified')
                    ->comment('เวลาที่ลิงก์บัญชี FB');
            });
        });

        // Unique index — กัน facebook_user_id ซ้ำ (1 FB user = 1 account)
        $this->safeAddIndex('users', 'facebook_user_id', 'users_facebook_user_id_unique', 'unique');
    }

    /**
     * ลบ columns + index เมื่อ rollback
     */
    public function down(): void
    {
        $this->safeDropIndex('users', 'users_facebook_user_id_unique');
        $this->safeDropColumn('users', [
            'facebook_user_id',
            'facebook_email',
            'facebook_name',
            'facebook_picture_url',
            'facebook_verified',
            'facebook_linked_at',
        ]);
    }
};
