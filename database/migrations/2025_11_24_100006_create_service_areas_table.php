<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_areas - พื้นที่ให้บริการ
     *
     * กำหนดพื้นที่ที่สามารถให้บริการได้ เช่น กรุงเทพมหานคร, นนทบุรี
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_areas')) {
            return;
        }

        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();

            // เจ้าของ
            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของระบบที่กำหนดพื้นที่');

            // ข้อมูลพื้นที่
            $table->string('name', 255)->comment('ชื่อพื้นที่ เช่น กรุงเทพมหานคร');
            $table->string('province', 100)->comment('จังหวัด');
            $table->string('district', 100)->nullable()->comment('อำเภอ/เขต');
            $table->string('subdistrict', 100)->nullable()->comment('ตำบล/แขวง');

            // พิกัดขอบเขต (Polygon)
            $table->json('coordinates')->nullable()->comment('พิกัดขอบเขตพื้นที่ (polygon)');
            // [{"lat": 13.75, "lng": 100.52}, {"lat": 13.76, "lng": 100.53}, ...]

            // ศูนย์กลางพื้นที่ (สำหรับคำนวณระยะทาง)
            $table->decimal('center_latitude', 10, 8)->nullable()->comment('ละติจูดจุดกึ่งกลาง');
            $table->decimal('center_longitude', 11, 8)->nullable()->comment('ลองจิจูดจุดกึ่งกลาง');

            // การตั้งค่า
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->integer('sort_order')->default(0)->comment('ลำดับการแสดงผล');

            // สถิติ
            $table->integer('provider_count')->default(0)->comment('จำนวน provider ในพื้นที่');
            $table->integer('booking_count')->default(0)->comment('จำนวนการจองในพื้นที่');

            $table->timestamps();

            // Indexes
            $table->index(['owner_id', 'is_active'], 'service_area_owner_idx');
            $table->index(['province', 'district'], 'service_area_location_idx');
            $table->index(['center_latitude', 'center_longitude'], 'service_area_center_idx');
        });
    }

    /**
     * ลบตาราง service_areas
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};
