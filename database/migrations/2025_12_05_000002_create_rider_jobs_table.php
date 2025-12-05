<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง rider_jobs สำหรับเก็บข้อมูลงานของไรเดอร์
 */
return new class extends Migration
{
    /**
     * สร้างตาราง rider_jobs
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('rider_jobs')) {
            return;
        }

        Schema::create('rider_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number', 20)->unique();
            $table->foreignId('rider_id')
                ->constrained('riders')
                ->onDelete('cascade');

            // ข้อมูลงาน
            $table->enum('job_type', [
                'delivery',     // ส่งของ
                'food',         // ส่งอาหาร
                'document',     // ส่งเอกสาร
                'service',      // ให้บริการ
                'pickup'        // รับของ
            ])->default('delivery');
            $table->string('title');
            $table->text('description')->nullable();

            // จุดรับ
            $table->string('pickup_address');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->string('pickup_contact_name')->nullable();
            $table->string('pickup_contact_phone')->nullable();
            $table->text('pickup_notes')->nullable();

            // จุดส่ง
            $table->string('delivery_address');
            $table->decimal('delivery_latitude', 10, 8);
            $table->decimal('delivery_longitude', 11, 8);
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone')->nullable();
            $table->text('delivery_notes')->nullable();

            // ระยะทางและเวลา
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();

            // ค่าบริการ
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('distance_fee', 10, 2)->default(0);
            $table->decimal('extra_fee', 10, 2)->default(0);
            $table->decimal('total_fee', 10, 2)->default(0);
            $table->decimal('rider_earnings', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);

            // สถานะ
            $table->enum('status', [
                'pending',          // รอไรเดอร์รับงาน
                'accepted',         // ไรเดอร์รับงานแล้ว
                'picking_up',       // กำลังไปรับของ
                'picked_up',        // รับของแล้ว
                'delivering',       // กำลังส่ง
                'delivered',        // ส่งแล้ว
                'completed',        // เสร็จสิ้น
                'cancelled',        // ยกเลิก
                'failed'            // ล้มเหลว
            ])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['rider', 'customer', 'system'])->nullable();

            // เวลา
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // ลูกค้า
            $table->foreignId('customer_id')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // รูปภาพหลักฐาน
            $table->string('pickup_proof_image')->nullable();
            $table->string('delivery_proof_image')->nullable();
            $table->string('signature_image')->nullable();

            // การให้คะแนน
            $table->integer('customer_rating')->nullable();
            $table->text('customer_review')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('job_type');
            $table->index(['pickup_latitude', 'pickup_longitude']);
            $table->index(['delivery_latitude', 'delivery_longitude']);
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง rider_jobs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_jobs');
    }
};
