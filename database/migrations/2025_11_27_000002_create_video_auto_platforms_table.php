<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_platforms
     * เก็บข้อมูลการเชื่อมต่อกับ platforms ต่างๆ เช่น YouTube, Facebook, TikTok
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_platforms')) {
            return;
        }

        Schema::create('video_auto_platforms', function (Blueprint $table) {
            $table->id();

            // ข้อมูล Platform
            $table->string('platform_type')->comment('ประเภท: youtube, facebook, instagram, tiktok, twitter, threads');
            $table->string('name')->comment('ชื่อที่ตั้งเอง เช่น "YouTube หลัก"');
            $table->string('channel_id')->nullable()->comment('Channel/Page ID');
            $table->string('channel_name')->nullable()->comment('ชื่อ Channel/Page');
            $table->string('channel_url')->nullable()->comment('URL ของ Channel/Page');

            // การยืนยันตัวตน
            $table->text('access_token')->nullable()->comment('Access Token (encrypted)');
            $table->text('refresh_token')->nullable()->comment('Refresh Token (encrypted)');
            $table->text('api_key')->nullable()->comment('API Key (encrypted)');
            $table->text('client_id')->nullable()->comment('OAuth Client ID (encrypted)');
            $table->text('client_secret')->nullable()->comment('OAuth Client Secret (encrypted)');
            $table->json('extra_credentials')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // สถานะการเชื่อมต่อ
            $table->enum('status', ['connected', 'disconnected', 'expired', 'error'])->default('disconnected');
            $table->timestamp('token_expires_at')->nullable()->comment('Token หมดอายุเมื่อไหร่');
            $table->timestamp('last_connected_at')->nullable()->comment('เชื่อมต่อสำเร็จล่าสุด');
            $table->text('last_error')->nullable()->comment('Error ล่าสุด');

            // ตั้งค่าการโพส
            $table->boolean('auto_publish')->default(true)->comment('โพสอัตโนมัติหรือไม่');
            $table->json('publish_settings')->nullable()->comment('ตั้งค่าการโพส: privacy, category, tags');

            // สถิติ
            $table->unsignedInteger('total_posts')->default(0)->comment('จำนวนโพสทั้งหมด');
            $table->unsignedInteger('successful_posts')->default(0)->comment('โพสสำเร็จ');
            $table->unsignedInteger('failed_posts')->default(0)->comment('โพสล้มเหลว');

            // การจัดการ
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->comment('ใช้เป็น default หรือไม่');
            $table->integer('priority')->default(0)->comment('ลำดับความสำคัญในการโพส');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['platform_type', 'is_active'], 'vap_type_active_idx');
            $table->index(['status', 'is_active'], 'vap_status_active_idx');
        });
    }

    /**
     * ลบตาราง video_auto_platforms
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_platforms');
    }
};
