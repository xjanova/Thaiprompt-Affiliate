<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง facebook_oauth_settings
     *
     * เก็บ config ของ Facebook OAuth Login ไว้ใน DB แทน .env
     * เพื่อให้ admin แก้ไขจากหน้าหลังบ้านได้โดยไม่ต้อง redeploy
     *
     * Pattern เดียวกับ line_oa_settings table
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('facebook_oauth_settings')) {
            return;
        }

        Schema::create('facebook_oauth_settings', function (Blueprint $table) {
            $table->id();

            // Facebook App credentials (from developers.facebook.com)
            $table->string('app_id', 100)
                ->nullable()
                ->comment('Facebook App ID จาก Developer Console');

            $table->string('app_secret', 191)
                ->nullable()
                ->comment('Facebook App Secret (encrypted ใน hidden array)');

            $table->string('redirect_uri', 500)
                ->nullable()
                ->comment('Callback URL ที่ลงทะเบียนใน FB Developer Console');

            // Optional config
            $table->string('graph_version', 10)
                ->default('v18.0')
                ->comment('Facebook Graph API version');

            $table->json('scopes')
                ->nullable()
                ->comment('OAuth scopes ที่ขอจาก user (default: email,public_profile)');

            // Feature toggle
            $table->boolean('is_enabled')
                ->default(false)
                ->comment('เปิด/ปิด Facebook Login บนหน้า /login');

            $table->boolean('auto_create_user')
                ->default(true)
                ->comment('สร้าง user อัตโนมัติเมื่อยังไม่มีในระบบ');

            $table->boolean('auto_create_wallet')
                ->default(true)
                ->comment('สร้าง wallet อัตโนมัติให้ user ใหม่');

            $table->boolean('auto_create_mlm_member')
                ->default(true)
                ->comment('สร้าง MlmMember อัตโนมัติ (ผูก sponsor = Super Admin)');

            // Tracking
            $table->timestamp('last_login_at')
                ->nullable()
                ->comment('login ล่าสุดผ่าน FB OAuth');

            $table->unsignedInteger('total_logins')
                ->default(0)
                ->comment('จำนวนครั้งที่ login สะสม');

            $table->timestamps();
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_oauth_settings');
    }
};
