<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง push_notifications สำหรับระบบแจ้งเตือนแบบ Push
 */
return new class extends Migration
{
    /**
     * สร้างตาราง push_notifications
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('push_notifications')) {
            return;
        }

        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();

            // ผู้รับ (null = ส่งทุกคน, มีค่า = ส่งเฉพาะคน)
            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            // ผู้ส่ง (แอดมิน)
            $table->foreignId('sent_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // หัวข้อและเนื้อหา
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // ข้อมูลเพิ่มเติม

            // ประเภท
            $table->enum('type', [
                'general',      // ทั่วไป
                'order',        // เกี่ยวกับออเดอร์
                'rider',        // สำหรับไรเดอร์
                'wallet',       // กระเป๋าเงิน
                'promotion',    // โปรโมชั่น
                'system',       // ระบบ
                'ticket'        // Ticket
            ])->default('general');

            // การจัดกลุ่มเป้าหมาย
            $table->enum('target_type', [
                'individual',   // รายบุคคล
                'all_users',    // ผู้ใช้ทั้งหมด
                'riders',       // ไรเดอร์ทั้งหมด
                'sellers',      // ผู้ขายทั้งหมด
                'segment'       // กลุ่มที่กำหนด
            ])->default('individual');

            // ไอคอนและรูปภาพ
            $table->string('icon')->nullable();
            $table->string('image')->nullable();

            // ลิงก์เมื่อกดแจ้งเตือน
            $table->string('action_url')->nullable();

            // สถานะการส่ง
            $table->enum('status', [
                'pending',      // รอส่ง
                'sent',         // ส่งแล้ว
                'failed',       // ส่งไม่สำเร็จ
                'cancelled'     // ยกเลิก
            ])->default('pending');
            $table->text('error_message')->nullable();

            // เวลาที่กำหนดส่ง
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('target_type');
            $table->index('scheduled_at');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง push_notifications
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
