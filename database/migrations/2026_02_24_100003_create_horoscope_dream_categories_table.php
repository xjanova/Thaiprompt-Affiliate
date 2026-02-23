<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง horoscope_dream_categories — หมวดหมู่ทำนายฝัน
     *
     * เก็บหมวดหมู่สำหรับจัดกลุ่มสัญลักษณ์ในฝัน
     * เช่น สัตว์, คน, ธรรมชาติ, น้ำ, ตาย, เงิน ฯลฯ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('horoscope_dream_categories')) {
            return;
        }

        Schema::create('horoscope_dream_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_th', 100)->comment('ชื่อหมวดหมู่ภาษาไทย');
            $table->string('slug', 50)->unique()->comment('URL slug');
            $table->string('icon', 50)->nullable()->comment('Font Awesome icon class หรือ emoji');
            $table->string('color_hex', 7)->default('#6366f1')->comment('สีหมวดหมู่');
            $table->text('description_th')->nullable()->comment('คำอธิบายหมวดหมู่');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active', 'dream_cat_active_idx');
        });
    }

    /**
     * ลบตาราง horoscope_dream_categories
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('horoscope_dream_categories');
    }
};
