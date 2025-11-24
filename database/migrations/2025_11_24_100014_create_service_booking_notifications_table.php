<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_booking_notifications - ประวัติการแจ้งเตือน
     *
     * เก็บบันทึกการส่ง notification ทุกช่องทาง (In-App, LINE, Email, SMS)
     * ใช้สำหรับ tracking และ debugging ระบบ notification
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_booking_notifications')) {
            return;
        }

        Schema::create('service_booking_notifications', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('booking_id')
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('การจอง');

            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('service_providers')
                ->onDelete('cascade')
                ->comment('ผู้ให้บริการ (ถ้าส่งถึง provider)');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ (ถ้าส่งถึง customer)');

            // ประเภทการแจ้งเตือน
            $table->enum('notification_type', [
                'new_booking',          // มีงานใหม่
                'booking_reminder',     // เตือนก่อนหมดเวลา
                'booking_accepted',     // provider รับงานแล้ว
                'booking_rejected',     // provider ปฏิเสธงาน
                'booking_cancelled',    // งานถูกยกเลิก
                'provider_on_way',      // provider เดินทาง
                'service_started',      // เริ่มให้บริการ
                'service_completed',    // เสร็จสิ้น
                'payment_received'      // รับเงินแล้ว
            ])->comment('ประเภทการแจ้งเตือน');

            // ช่องทางการส่ง
            $table->enum('channel', ['in_app', 'line', 'email', 'sms', 'push'])
                ->comment('ช่องทางการส่ง');

            // ผู้รับ
            $table->string('recipient', 255)->comment('ผู้รับ (LINE User ID, email, phone)');

            // เนื้อหา
            $table->string('title', 255)->nullable()->comment('หัวข้อ');
            $table->text('message')->comment('ข้อความ');
            $table->json('data')->nullable()->comment('ข้อมูลเพิ่มเติม (JSON)');

            // สถานะการส่ง
            $table->boolean('is_sent')->default(false)->comment('ส่งสำเร็จหรือยัง');
            $table->dateTime('sent_at')->nullable()->comment('เวลาที่ส่งสำเร็จ');

            // การอ่าน (สำหรับ in-app notification)
            $table->boolean('is_read')->default(false)->comment('อ่านแล้วหรือยัง');
            $table->dateTime('read_at')->nullable()->comment('เวลาที่อ่าน');

            // การ retry
            $table->text('failed_reason')->nullable()->comment('เหตุผลที่ส่งไม่สำเร็จ');
            $table->integer('retry_count')->default(0)->comment('จำนวนครั้งที่ลองส่งใหม่');
            $table->dateTime('next_retry_at')->nullable()->comment('เวลาที่จะลองส่งใหม่');

            // Response from provider (สำหรับ LINE)
            $table->json('provider_response')->nullable()->comment('คำตอบจาก provider (ถ้ามี)');

            $table->timestamps();

            // Indexes
            $table->index('booking_id', 'service_notif_booking_idx');
            $table->index('provider_id', 'service_notif_provider_idx');
            $table->index(['notification_type', 'channel'], 'service_notif_type_idx');
            $table->index(['is_sent', 'sent_at'], 'service_notif_sent_idx');
            $table->index(['is_read', 'read_at'], 'service_notif_read_idx');
            $table->index('next_retry_at', 'service_notif_retry_idx');
        });
    }

    /**
     * ลบตาราง service_booking_notifications
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_notifications');
    }
};
