<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_bookings - การจองบริการ
     *
     * ตารางหลักสำหรับเก็บข้อมูลการจองบริการทั้งหมด
     * รวมถึงระบบ notification, accept/reject, payment, tracking
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_bookings')) {
            return;
        }

        Schema::create('service_bookings', function (Blueprint $table) {
            $table->id();

            // เลขที่การจอง
            $table->string('booking_number', 50)->unique()->comment('เลขที่จอง เช่น SB-20251124-0001');

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ลูกค้า');

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->onDelete('set null')
                ->comment('บริการเดี่ยว (ถ้าจอง service)');

            $table->foreignId('package_id')
                ->nullable()
                ->constrained('service_packages')
                ->onDelete('set null')
                ->comment('แพคเกจ (ถ้าจอง package)');

            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('service_providers')
                ->onDelete('set null')
                ->comment('ผู้ให้บริการที่รับงาน');

            // เวลานัดหมาย
            $table->dateTime('scheduled_at')->comment('วันเวลานัดหมาย');
            $table->integer('service_duration_minutes')->default(60)->comment('ระยะเวลาให้บริการ (นาที)');

            // ราคา
            $table->decimal('base_price', 10, 2)->default(0)->comment('ราคาบริการ');
            $table->decimal('distance_km', 8, 2)->default(0)->comment('ระยะทางจริง (km)');
            $table->decimal('distance_price', 10, 2)->default(0)->comment('ค่าเดินทาง');
            $table->decimal('options_price', 10, 2)->default(0)->comment('ค่าออฟชั่นเพิ่มเติม');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('รวมย่อย');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('ภาษี VAT 7%');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('ส่วนลด');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('ราคารวมสุทธิ');

            // การชำระเงิน
            $table->string('payment_method', 50)->nullable()->comment('วิธีชำระเงิน: wallet, promptpay, card');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])
                ->default('pending')
                ->comment('สถานะการชำระเงิน');

            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->constrained('payment_transactions')
                ->onDelete('set null')
                ->comment('รายการชำระเงิน');

            // 🔔 Provider Notification & Response (NEW!)
            $table->dateTime('provider_notified_at')->nullable()->comment('เวลาที่แจ้งเตือน provider');
            $table->dateTime('provider_response_deadline')->nullable()->comment('deadline ตอบกลับ (เช่น 15 นาที)');
            $table->dateTime('provider_viewed_at')->nullable()->comment('provider เปิดดูเมื่อไร');
            $table->dateTime('provider_accepted_at')->nullable()->comment('เวลาที่รับงาน');
            $table->dateTime('provider_rejected_at')->nullable()->comment('เวลาที่ปฏิเสธ');
            $table->text('provider_rejected_reason')->nullable()->comment('เหตุผลที่ปฏิเสธ');
            $table->integer('provider_response_minutes')->nullable()->comment('ใช้เวลาตอบกลับกี่นาที');

            // สถานะการจอง
            $table->enum('status', [
                'pending',              // รอชำระเงิน
                'paid',                 // ชำระเงินแล้ว รอมอบหมาย provider
                'notifying_provider',   // กำลังแจ้งเตือน provider
                'waiting_provider',     // รอ provider ตอบกลับ
                'provider_accepted',    // provider รับงานแล้ว
                'provider_rejected',    // provider ปฏิเสธ
                'provider_on_way',      // provider กำลังเดินทาง
                'in_progress',          // กำลังให้บริการ
                'completed',            // เสร็จสิ้น
                'cancelled'             // ยกเลิก
            ])->default('pending')->comment('สถานะการจอง');

            // หมายเหตุ
            $table->text('customer_notes')->nullable()->comment('หมายเหตุจากลูกค้า');
            $table->text('provider_notes')->nullable()->comment('หมายเหตุจาก provider');
            $table->text('admin_notes')->nullable()->comment('หมายเหตุจาก admin');

            // การยกเลิก
            $table->text('cancellation_reason')->nullable()->comment('เหตุผลการยกเลิก');
            $table->dateTime('cancelled_at')->nullable()->comment('เวลาที่ยกเลิก');
            $table->enum('cancelled_by', ['customer', 'provider', 'admin', 'system'])
                ->nullable()
                ->comment('ยกเลิกโดย');

            // Timestamps สำคัญ
            $table->dateTime('confirmed_at')->nullable()->comment('เวลาที่ยืนยัน');
            $table->dateTime('started_at')->nullable()->comment('เวลาเริ่มให้บริการ');
            $table->dateTime('completed_at')->nullable()->comment('เวลาเสร็จสิ้น');

            // MLM Commission
            $table->decimal('commission_amount', 10, 2)->default(0)->comment('คอมมิชชั่น MLM');
            $table->foreignId('mlm_member_id')
                ->nullable()
                ->constrained('mlm_members')
                ->onDelete('set null')
                ->comment('สมาชิก MLM ที่ได้คอมมิชชั่น');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('booking_number', 'service_booking_number_idx');
            $table->index(['user_id', 'status'], 'service_booking_user_idx');
            $table->index(['provider_id', 'status'], 'service_booking_provider_idx');
            $table->index('status', 'service_booking_status_idx');
            $table->index('scheduled_at', 'service_booking_schedule_idx');
            $table->index('payment_status', 'service_booking_payment_idx');
            $table->index(['provider_id', 'provider_notified_at'], 'service_booking_notified_idx');
        });
    }

    /**
     * ลบตาราง service_bookings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_bookings');
    }
};
