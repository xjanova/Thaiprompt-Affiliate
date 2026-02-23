<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง horoscope_zodiac_signs — 12 ราศีไทย/สากล
     *
     * เก็บข้อมูลพื้นฐานราศี: ชื่อไทย/อังกฤษ, ช่วงวันที่, ธาตุ, ดาวประจำราศี,
     * สัญลักษณ์, สี, คำอธิบาย, บุคลิกภาพ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('horoscope_zodiac_signs')) {
            return;
        }

        Schema::create('horoscope_zodiac_signs', function (Blueprint $table) {
            $table->id();

            // ชื่อราศี
            $table->string('name_th', 100)->comment('ชื่อราศีภาษาไทย เช่น ราศีเมษ');
            $table->string('name_en', 100)->comment('ชื่อราศีภาษาอังกฤษ เช่น Aries');
            $table->string('slug', 50)->unique()->comment('URL slug เช่น aries');

            // ช่วงวันที่ (เดือน-วัน)
            $table->string('date_range_start', 5)->comment('วันเริ่มต้น เช่น 04-13 (13 เม.ย.)');
            $table->string('date_range_end', 5)->comment('วันสิ้นสุด เช่น 05-14 (14 พ.ค.)');
            $table->string('date_range_display_th', 100)->nullable()->comment('ช่วงวันแสดงผลภาษาไทย');

            // ข้อมูลโหราศาสตร์
            $table->string('element', 20)->comment('ธาตุ: ไฟ, ดิน, ลม, น้ำ');
            $table->string('ruling_planet', 50)->comment('ดาวประจำราศี เช่น Mars (อังคาร)');
            $table->string('quality', 30)->nullable()->comment('คุณภาพ: Cardinal, Fixed, Mutable');

            // สัญลักษณ์และรูปภาพ
            $table->string('symbol_emoji', 10)->comment('Emoji สัญลักษณ์ เช่น ♈');
            $table->string('symbol_image_url', 500)->nullable()->comment('URL รูปสัญลักษณ์ราศี');
            $table->string('color_hex', 7)->default('#6366f1')->comment('สี hex ของราศี');
            $table->string('gradient_from', 7)->nullable()->comment('สี gradient เริ่มต้น');
            $table->string('gradient_to', 7)->nullable()->comment('สี gradient สิ้นสุด');

            // คำอธิบาย
            $table->text('description_th')->nullable()->comment('คำอธิบายราศีภาษาไทย');
            $table->text('personality_th')->nullable()->comment('บุคลิกภาพราศีภาษาไทย');
            $table->text('strengths_th')->nullable()->comment('จุดแข็ง');
            $table->text('weaknesses_th')->nullable()->comment('จุดอ่อน');

            // การจัดการ
            $table->boolean('is_active')->default(true)->comment('เปิด/ปิดใช้งาน');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('ลำดับการแสดงผล');

            $table->timestamps();

            // Indexes
            $table->index('is_active', 'zodiac_active_idx');
            $table->index('sort_order', 'zodiac_sort_idx');
        });
    }

    /**
     * ลบตาราง horoscope_zodiac_signs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('horoscope_zodiac_signs');
    }
};
