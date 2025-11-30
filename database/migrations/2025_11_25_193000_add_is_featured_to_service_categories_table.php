<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ is_featured ในตาราง service_categories
     *
     * คอลัมน์นี้ใช้ระบุหมวดหมู่ที่แนะนำ/โปรโมต
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง service_categories มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('service_categories')) {
            return;
        }

        Schema::table('service_categories', function (Blueprint $table) {
            // เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('service_categories', 'is_featured')) {
                $table->boolean('is_featured')
                    ->default(false)
                    ->after('is_active')
                    ->comment('หมวดหมู่ที่แนะนำหรือไม่');
            }
        });
    }

    /**
     * ลบคอลัมน์ is_featured
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            if (Schema::hasColumn('service_categories', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};
