<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_provider_areas - พื้นที่ที่ provider รับงาน
     *
     * เชื่อมโยง provider กับพื้นที่ที่เขายินดีให้บริการ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_provider_areas')) {
            return;
        }

        Schema::create('service_provider_areas', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('provider_id')
                ->constrained('service_providers')
                ->onDelete('cascade')
                ->comment('ผู้ให้บริการ');

            $table->foreignId('area_id')
                ->constrained('service_areas')
                ->onDelete('cascade')
                ->comment('พื้นที่ที่รับงาน');

            // การตั้งค่าเฉพาะพื้นที่
            $table->boolean('is_primary')->default(false)->comment('พื้นที่หลัก/ถิ่นฐาน');
            $table->integer('priority')->default(0)->comment('ลำดับความสำคัญ (เลขน้อย = สำคัญกว่า)');

            $table->timestamps();

            // Unique constraint: provider ไม่สามารถเพิ่มพื้นที่เดียวกันซ้ำ
            $table->unique(['provider_id', 'area_id'], 'service_prov_area_unique');

            // Indexes
            $table->index('provider_id', 'service_prov_area_prov_idx');
            $table->index('area_id', 'service_prov_area_area_idx');
        });
    }

    /**
     * ลบตาราง service_provider_areas
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_areas');
    }
};
