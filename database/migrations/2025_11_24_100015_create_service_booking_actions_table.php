<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_booking_actions - บันทึก Action Log
     *
     * เก็บประวัติทุก action ที่เกิดขึ้นกับการจอง
     * ใช้สำหรับ audit trail และ debugging
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_booking_actions')) {
            return;
        }

        Schema::create('service_booking_actions', function (Blueprint $table) {
            $table->id();

            // การจอง
            $table->foreignId('booking_id')
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('การจอง');

            // ผู้ทำ action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ผู้ใช้ที่ทำ action (customer/admin)');

            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('service_providers')
                ->onDelete('set null')
                ->comment('provider ที่ทำ action');

            // Action
            $table->enum('action', [
                'created',              // สร้างการจอง
                'paid',                 // ชำระเงิน
                'assigned',             // มอบหมาย provider
                'notified',             // แจ้งเตือน provider
                'viewed',               // provider เปิดดู
                'accepted',             // provider รับงาน
                'rejected',             // provider ปฏิเสธ
                'on_way',               // provider เดินทาง
                'started',              // เริ่มให้บริการ
                'completed',            // เสร็จสิ้น
                'reviewed',             // รีวิวแล้ว
                'cancelled',            // ยกเลิก
                'refunded',             // คืนเงิน
                'updated',              // แก้ไขข้อมูล
                'reassigned'            // มอบหมายใหม่
            ])->comment('Action ที่เกิดขึ้น');

            // สถานะ
            $table->string('from_status', 50)->nullable()->comment('สถานะก่อนหน้า');
            $table->string('to_status', 50)->nullable()->comment('สถานะใหม่');

            // รายละเอียด
            $table->text('notes')->nullable()->comment('หมายเหตุ/รายละเอียด');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม (JSON)');

            // ข้อมูลการเข้าถึง
            $table->string('ip_address', 45)->nullable()->comment('IP Address');
            $table->text('user_agent')->nullable()->comment('User Agent');

            // Timestamp - บันทึกเพียง created_at (ไม่มี updated_at เพราะเป็น log)
            $table->timestamp('created_at')->useCurrent()->comment('เวลาที่เกิด action');

            // Indexes
            $table->index('booking_id', 'service_action_booking_idx');
            $table->index('user_id', 'service_action_user_idx');
            $table->index('provider_id', 'service_action_provider_idx');
            $table->index('action', 'service_action_type_idx');
            $table->index(['booking_id', 'created_at'], 'service_action_timeline_idx');
        });
    }

    /**
     * ลบตาราง service_booking_actions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_actions');
    }
};
