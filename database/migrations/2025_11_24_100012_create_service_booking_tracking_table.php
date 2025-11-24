<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_booking_tracking - ติดตามสถานะ Real-time
     *
     * เก็บประวัติการอัพเดทตำแหน่งและสถานะของ provider
     * ใช้สำหรับแสดง real-time tracking บนแผนที่
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_booking_tracking')) {
            return;
        }

        Schema::create('service_booking_tracking', function (Blueprint $table) {
            $table->id();

            // การจองและ provider
            $table->foreignId('booking_id')
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('การจอง');

            $table->foreignId('provider_id')
                ->constrained('service_providers')
                ->onDelete('cascade')
                ->comment('ผู้ให้บริการ');

            // สถานะ
            $table->string('status', 50)->comment('สถานะปัจจุบัน');

            // ตำแหน่ง GPS
            $table->decimal('latitude', 10, 8)->nullable()->comment('ละติจูดปัจจุบัน');
            $table->decimal('longitude', 11, 8)->nullable()->comment('ลองจิจูดปัจจุบัน');

            // ข้อมูลการเดินทาง
            $table->decimal('distance_to_customer_km', 8, 2)->nullable()->comment('ระยะทางถึงลูกค้า (km)');
            $table->integer('estimated_arrival_minutes')->nullable()->comment('เวลาถึงโดยประมาณ (นาที)');

            // หมายเหตุ
            $table->text('notes')->nullable()->comment('หมายเหตุ/อัพเดท');

            // Metadata
            $table->decimal('speed_kmh', 5, 2)->nullable()->comment('ความเร็ว (km/h)');
            $table->string('battery_level', 10)->nullable()->comment('แบตเตอรี่เหลือ (%)');

            // Timestamp - บันทึกเพียง created_at (ไม่มี updated_at เพราะเป็น log)
            $table->timestamp('created_at')->useCurrent()->comment('เวลาที่บันทึก');

            // Indexes
            $table->index('booking_id', 'service_tracking_booking_idx');
            $table->index('provider_id', 'service_tracking_provider_idx');
            $table->index(['booking_id', 'created_at'], 'service_tracking_timeline_idx');
            $table->index(['latitude', 'longitude'], 'service_tracking_coords_idx');
        });
    }

    /**
     * ลบตาราง service_booking_tracking
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_tracking');
    }
};
