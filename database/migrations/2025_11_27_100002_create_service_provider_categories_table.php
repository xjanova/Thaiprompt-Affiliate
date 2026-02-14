<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง service_provider_categories
 *
 * Pivot table เชื่อม ServiceProvider กับ ServiceCategory
 */
return new class extends Migration
{
    /**
     * สร้างตาราง pivot สำหรับ provider และ categories
     */
    public function up(): void
    {
        if (Schema::hasTable('service_provider_categories')) {
            return;
        }

        Schema::create('service_provider_categories', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('provider_id')
                ->constrained('service_providers')
                ->onDelete('cascade')
                ->comment('ผู้ให้บริการ');

            $table->foreignId('category_id')
                ->constrained('service_categories')
                ->onDelete('cascade')
                ->comment('หมวดหมู่บริการ');

            // การตั้งค่าเพิ่มเติม
            $table->boolean('is_primary')->default(false)->comment('หมวดหมู่หลัก');
            $table->integer('experience_years')->nullable()->comment('ประสบการณ์ในหมวดนี้ (ปี)');

            $table->timestamps();

            // Unique constraint: provider ไม่สามารถเพิ่มหมวดเดียวกันซ้ำ
            $table->unique(['provider_id', 'category_id'], 'service_prov_cat_unique');

            // Indexes
            $table->index('provider_id', 'service_prov_cat_prov_idx');
            $table->index('category_id', 'service_prov_cat_cat_idx');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_categories');
    }
};
