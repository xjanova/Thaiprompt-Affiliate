<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_booking_locations - ตำแหน่งจัดส่ง/บริการ
     *
     * เก็บตำแหน่ง GPS และที่อยู่สำหรับให้บริการ
     * รวมถึงตำแหน่งปัจจุบันของ provider
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_booking_locations')) {
            return;
        }

        Schema::create('service_booking_locations', function (Blueprint $table) {
            $table->id();

            // การจอง
            $table->foreignId('booking_id')
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('การจอง');

            // ประเภทตำแหน่ง
            $table->enum('type', ['service_location', 'provider_location'])
                ->comment('ประเภท: ตำแหน่งให้บริการ หรือ ตำแหน่ง provider');

            // พิกัด GPS (ใช้สำหรับคำนวณระยะทาง)
            $table->decimal('latitude', 10, 8)->comment('ละติจูด');
            $table->decimal('longitude', 11, 8)->comment('ลองจิจูด');

            // ที่อยู่
            $table->text('address')->comment('ที่อยู่เต็ม');
            $table->string('google_place_id', 255)->nullable()->comment('Google Place ID');

            // รายละเอียดเพิ่มเติม (สำหรับ service_location)
            $table->string('building_name', 255)->nullable()->comment('ชื่ออาคาร/หมู่บ้าน');
            $table->string('floor_number', 50)->nullable()->comment('ชั้น');
            $table->string('room_number', 50)->nullable()->comment('เลขที่ห้อง');
            $table->string('landmark', 255)->nullable()->comment('จุดสังเกต');

            // ติดต่อ
            $table->string('contact_name', 255)->nullable()->comment('ชื่อผู้ติดต่อ');
            $table->string('contact_phone', 20)->nullable()->comment('เบอร์โทรผู้ติดต่อ');

            $table->timestamps();

            // Indexes
            $table->index('booking_id', 'service_booking_loc_booking_idx');
            $table->index(['latitude', 'longitude'], 'service_booking_loc_coords_idx');
            $table->index('type', 'service_booking_loc_type_idx');
        });
    }

    /**
     * ลบตาราง service_booking_locations
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_locations');
    }
};
