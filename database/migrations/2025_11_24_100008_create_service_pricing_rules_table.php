<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_pricing_rules - กฎคำนวณราคาตามระยะทาง
     *
     * กำหนดกฎการคิดค่าเดินทางตามระยะทาง เช่น:
     * - 0-5 km: ค่าคงที่ 50฿
     * - 5-10 km: 50฿ + 10฿/km
     * - 10+ km: 100฿ + 15฿/km
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_pricing_rules')) {
            return;
        }

        Schema::create('service_pricing_rules', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์ (nullable = ใช้กับทุก service)
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->onDelete('cascade')
                ->comment('บริการเฉพาะ (null = ทุกบริการ)');

            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของระบบ');

            // ข้อมูลกฎ
            $table->string('name', 255)->comment('ชื่อกฎ เช่น ค่าเดินทางพื้นฐาน');

            // ช่วงระยะทาง
            $table->decimal('min_distance_km', 5, 2)->default(0)->comment('ระยะทางขั้นต่ำ (km)');
            $table->decimal('max_distance_km', 5, 2)->nullable()->comment('ระยะทางสูงสุด (km), null = ไม่จำกัด');

            // ราคา
            $table->decimal('price_per_km', 8, 2)->nullable()->comment('ราคาต่อ km');
            $table->decimal('flat_fee', 8, 2)->default(0)->comment('ค่าบริการคงที่');

            // ตัวอย่างการคำนวณ: ระยะ 7 km
            // Rule: min=5, max=10, flat_fee=50, price_per_km=10
            // ผลลัพธ์: 50 + (7 * 10) = 120฿

            // การตั้งค่า
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->integer('priority')->default(0)->comment('ลำดับความสำคัญ (เลขน้อย = ตรวจสอบก่อน)');

            // หมายเหตุ
            $table->text('description')->nullable()->comment('คำอธิบายกฎ');

            $table->timestamps();

            // Indexes
            $table->index(['service_id', 'is_active'], 'service_price_service_idx');
            $table->index(['owner_id', 'is_active'], 'service_price_owner_idx');
            $table->index(['min_distance_km', 'max_distance_km'], 'service_price_distance_idx');
            $table->index('priority', 'service_price_priority_idx');
        });
    }

    /**
     * ลบตาราง service_pricing_rules
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_pricing_rules');
    }
};
