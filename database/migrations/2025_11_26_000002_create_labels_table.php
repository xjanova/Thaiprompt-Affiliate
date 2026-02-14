<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง labels
 *
 * เก็บข้อมูลฉลากที่ผู้ใช้สร้างขึ้น (ฉลากจริงที่จะพิมพ์)
 *
 * @author Claude AI
 */
return new class extends Migration
{
    /**
     * สร้างตาราง labels
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('labels')) {
            return;
        }

        Schema::create('labels', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('label_templates')
                ->onDelete('set null');

            // ข้อมูลพื้นฐาน
            $table->string('name'); // ชื่อฉลาก
            $table->text('description')->nullable(); // คำอธิบาย
            $table->string('category')->nullable(); // หมวดหมู่

            // ขนาด (หน่วย: mm)
            $table->decimal('width', 8, 2)->default(50);
            $table->decimal('height', 8, 2)->default(30);
            $table->string('unit', 10)->default('mm');

            // เนื้อหา
            $table->json('elements'); // องค์ประกอบทั้งหมด
            $table->json('data_fields')->nullable(); // ฟิลด์ข้อมูลที่ใช้ (สำหรับ batch print)

            // ตั้งค่า
            $table->json('settings')->nullable(); // ตั้งค่าทั่วไป
            $table->json('print_settings')->nullable(); // ตั้งค่าการพิมพ์

            // สถานะ
            $table->boolean('is_favorite')->default(false);
            $table->integer('print_count')->default(0); // จำนวนครั้งที่พิมพ์
            $table->timestamp('last_printed_at')->nullable();

            // ไฟล์
            $table->string('thumbnail_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id', 'labels_user_idx');
            $table->index('template_id', 'labels_template_idx');
            $table->index('category', 'labels_category_idx');
            $table->index('is_favorite', 'labels_favorite_idx');
            $table->index('created_at', 'labels_created_idx');
        });
    }

    /**
     * ลบตาราง labels
     */
    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};
