<?php

/**
 * Migration สร้างตาราง service_booking_disputes
 *
 * ตารางนี้ใช้เก็บข้อมูลข้อพิพาท/ร้องเรียนระหว่างผู้ใช้และผู้ให้บริการ
 * เป็นส่วนหนึ่งของระบบป้องกันการกลั่นแกล้ง (Anti-Abuse Protection)
 *
 * @see App\Models\ServiceBookingDispute
 * @see App\Http\Controllers\Admin\AntiAbuseController
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_booking_disputes
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('service_booking_disputes')) {
            return;
        }

        Schema::create('service_booking_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_number', 20)->unique();

            // Foreign key ไปยัง service_bookings
            $table->unsignedBigInteger('service_booking_id');
            $table->foreign('service_booking_id')
                ->references('id')
                ->on('service_bookings')
                ->onDelete('cascade');

            // ผู้ร้องเรียน
            $table->unsignedBigInteger('reporter_user_id')->nullable();
            $table->foreign('reporter_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unsignedBigInteger('reporter_provider_id')->nullable();
            $table->foreign('reporter_provider_id')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->enum('reporter_type', ['user', 'provider', 'system']);

            // ผู้ถูกร้องเรียน
            $table->unsignedBigInteger('accused_user_id')->nullable();
            $table->foreign('accused_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unsignedBigInteger('accused_provider_id')->nullable();
            $table->foreign('accused_provider_id')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->enum('accused_type', ['user', 'provider']);

            // ประเภทการร้องเรียน
            $table->enum('dispute_type', [
                'no_show',              // ไม่มาตามนัด
                'late_cancellation',    // ยกเลิกกระทันหัน
                'payment_issue',        // ปัญหาการชำระเงิน
                'service_quality',      // คุณภาพบริการไม่ดี
                'fraud',                // การฉ้อโกง
                'harassment',           // คุกคาม/ล่วงละเมิด
                'safety_concern',       // ปัญหาความปลอดภัย
                'property_damage',      // ทำทรัพย์สินเสียหาย
                'overcharge',           // เก็บเงินเกิน
                'fake_location',        // ปลอมตำแหน่ง GPS
                'other',                // อื่นๆ
            ]);

            // รายละเอียด
            $table->string('title');
            $table->text('description');
            $table->json('evidence_files')->nullable()->comment('รูปภาพ, วิดีโอ หลักฐาน');
            $table->json('location_evidence')->nullable()->comment('หลักฐาน GPS จาก location_logs');

            // สถานะ
            $table->enum('status', [
                'pending',                    // รอตรวจสอบ
                'investigating',              // กำลังตรวจสอบ
                'awaiting_response',          // รอคำชี้แจง
                'resolved_favor_reporter',    // ตัดสินให้ผู้ร้อง
                'resolved_favor_accused',     // ตัดสินให้ผู้ถูกร้อง
                'resolved_mutual',            // ยอมความ
                'dismissed',                  // ยกฟ้อง
                'escalated',                  // ส่งต่อ
            ])->default('pending');

            // ผลการตัดสิน
            $table->text('resolution_notes')->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->boolean('resulted_in_ban')->default(false);

            // Admin ที่จัดการ
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->foreign('handled_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamp('resolved_at')->nullable();

            // Priority
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->timestamps();
            $table->softDeletes();

            // Indexes สำหรับการค้นหาที่รวดเร็ว
            $table->index(['status', 'priority', 'created_at'], 'disputes_status_priority_idx');
        });
    }

    /**
     * ลบตาราง service_booking_disputes
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_disputes');
    }
};
