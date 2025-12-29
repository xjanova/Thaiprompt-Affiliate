<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง provinces สำหรับจังหวัดในประเทศไทย
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('provinces')) {
            return;
        }

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();

            // ข้อมูลจังหวัด
            $table->string('name_th', 100)->comment('ชื่อจังหวัดภาษาไทย');
            $table->string('name_en', 100)->comment('ชื่อจังหวัดภาษาอังกฤษ');
            $table->string('code', 10)->unique()->comment('รหัสจังหวัด');

            // ภูมิภาค
            $table->enum('region', [
                'central',   // ภาคกลาง
                'north',     // ภาคเหนือ
                'northeast', // ภาคตะวันออกเฉียงเหนือ
                'east',      // ภาคตะวันออก
                'west',      // ภาคตะวันตก
                'south',     // ภาคใต้
            ])->default('central')->comment('ภูมิภาค');

            // พิกัด GPS
            $table->decimal('latitude', 10, 8)->nullable()->comment('ละติจูด');
            $table->decimal('longitude', 11, 8)->nullable()->comment('ลองจิจูด');

            // สถานะ
            $table->boolean('is_active')->default(true)->comment('สถานะการใช้งาน');
            $table->integer('sort_order')->default(0)->comment('ลำดับการแสดงผล');

            $table->timestamps();

            // Indexes
            $table->index('region', 'provinces_region_idx');
            $table->index('is_active', 'provinces_active_idx');
            $table->index(['is_active', 'region'], 'provinces_active_region_idx');
        });
    }

    /**
     * ลบตาราง provinces
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
