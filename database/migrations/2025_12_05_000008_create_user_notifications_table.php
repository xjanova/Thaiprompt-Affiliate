<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง user_notifications สำหรับเก็บประวัติการแจ้งเตือนของผู้ใช้แต่ละคน
 */
return new class extends Migration
{
    /**
     * สร้างตาราง user_notifications
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // เชื่อมกับ push notification (ถ้ามี)
            $table->foreignId('push_notification_id')->nullable()
                ->constrained('push_notifications')
                ->onDelete('set null');

            // หัวข้อและเนื้อหา
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();

            // ประเภท
            $table->enum('type', [
                'general',
                'order',
                'rider',
                'wallet',
                'promotion',
                'system',
                'ticket'
            ])->default('general');

            // ไอคอนและรูปภาพ
            $table->string('icon')->nullable();
            $table->string('image')->nullable();

            // ลิงก์เมื่อกด
            $table->string('action_url')->nullable();

            // สถานะการอ่าน
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('is_read');
            $table->index('type');
            $table->index('created_at');
            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * ลบตาราง user_notifications
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
