<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง push_notification_deliveries
 *
 * ติดตามการส่ง Push Notification ไปยังแต่ละเครื่อง
 * รองรับการ retry เมื่อเครื่องกลับมา online
 *
 * Features:
 * - ติดตามสถานะการส่งแต่ละเครื่อง
 * - รองรับ retry เมื่อเครื่อง offline
 * - บันทึก error message สำหรับ debugging
 * - สร้างกราฟวิเคราะห์ success rate
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('push_notification_deliveries')) {
            Schema::create('push_notification_deliveries', function (Blueprint $table) {
                $table->id();

                // ความสัมพันธ์กับ notification และ device
                $table->foreignId('notification_id')
                    ->constrained('mobile_push_notifications')
                    ->cascadeOnDelete();

                $table->foreignId('device_id')
                    ->constrained('mobile_devices')
                    ->cascadeOnDelete();

                // สถานะการส่ง
                $table->enum('status', [
                    'pending',        // รอส่ง
                    'sent',           // ส่งไปยัง FCM/APNs แล้ว
                    'delivered',      // ส่งถึงเครื่องแล้ว (ได้รับการยืนยัน)
                    'failed',         // ล้มเหลวถาวร
                    'retry_pending',  // รอ retry
                ])->default('pending')->index();

                // จำนวนครั้งที่พยายามส่ง
                $table->unsignedTinyInteger('attempt_count')->default(0);

                // Timestamps
                $table->timestamp('sent_at')->nullable()->comment('เวลาที่ส่งไปยัง FCM/APNs');
                $table->timestamp('delivered_at')->nullable()->comment('เวลาที่ส่งถึงเครื่อง');
                $table->timestamp('next_retry_at')->nullable()->comment('เวลาที่จะ retry ถัดไป');

                // Error tracking
                $table->text('error_message')->nullable()->comment('ข้อความ error ล่าสุด');

                $table->timestamps();

                // Indexes สำหรับ query ที่ใช้บ่อย
                $table->unique(['notification_id', 'device_id'], 'notif_device_unique');
                $table->index(['status', 'next_retry_at'], 'retry_queue_idx');
                $table->index(['device_id', 'status'], 'device_delivery_idx');
                $table->index('delivered_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_deliveries');
    }
};
