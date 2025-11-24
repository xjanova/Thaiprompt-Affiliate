<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง services - บริการแต่ละประเภท
     *
     * เก็บรายละเอียดบริการ เช่น นวดไทย 1 ชม., นวดน้ำมัน 2 ชม., delivery อาหาร
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('services')) {
            return;
        }

        Schema::create('services', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('category_id')
                ->constrained('service_categories')
                ->onDelete('cascade')
                ->comment('หมวดหมู่บริการ');

            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของระบบ/ผู้สร้างบริการ');

            // ข้อมูลบริการ
            $table->string('name', 255)->comment('ชื่อบริการ เช่น นวดไทย 1 ชั่วโมง');
            $table->string('slug', 255)->unique()->comment('URL slug');
            $table->text('description')->nullable()->comment('คำอธิบายบริการแบบเต็ม');
            $table->string('short_description', 500)->nullable()->comment('คำอธิบายสั้น');

            // รูปภาพ
            $table->json('images')->nullable()->comment('รูปภาพบริการ (array of URLs)');

            // ราคาและเวลา
            $table->decimal('base_price', 10, 2)->default(0)->comment('ราคาพื้นฐาน (ยังไม่รวมค่าเดินทาง)');
            $table->integer('duration_minutes')->default(60)->comment('ระยะเวลาให้บริการ (นาที)');

            // ออฟชั่นเพิ่มเติม
            $table->json('options')->nullable()->comment('ออฟชั่นเพิ่มเติม เช่น แรงกด, ขนาดบรรจุ');
            // [{"name": "แรงกดปานกลาง", "price": 0}, {"name": "แรงกดแรง", "price": 50}]

            // การตั้งค่าบริการ
            $table->boolean('requires_location')->default(true)->comment('ต้องการตำแหน่ง GPS หรือไม่');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->boolean('is_featured')->default(false)->comment('แนะนำ/เด่น');

            // กฎการจอง
            $table->integer('min_booking_hours')->default(2)->comment('จองล่วงหน้าขั้นต่ำ (ชั่วโมง)');
            $table->integer('max_bookings_per_day')->nullable()->comment('จำกัดการจองต่อวัน');
            $table->integer('cancellation_hours')->default(24)->comment('ยกเลิกได้กี่ชั่วโมงก่อนนัด');

            // คอมมิชชั่น MLM
            $table->decimal('commission_rate', 5, 2)->default(10.00)->comment('อัตราคอมมิชชั่น MLM (%)');

            // SEO & Marketing
            $table->string('meta_title', 255)->nullable()->comment('SEO Title');
            $table->text('meta_description')->nullable()->comment('SEO Description');
            $table->string('meta_keywords', 500)->nullable()->comment('SEO Keywords');

            // สถิติ
            $table->integer('view_count')->default(0)->comment('จำนวนครั้งที่ถูกดู');
            $table->integer('booking_count')->default(0)->comment('จำนวนครั้งที่ถูกจอง');
            $table->decimal('average_rating', 3, 2)->default(0)->comment('คะแนนเฉลี่ย');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug', 'services_slug_idx');
            $table->index(['category_id', 'is_active'], 'services_cat_active_idx');
            $table->index(['owner_id', 'is_active'], 'services_owner_active_idx');
            $table->index('is_featured', 'services_featured_idx');
        });
    }

    /**
     * ลบตาราง services
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
