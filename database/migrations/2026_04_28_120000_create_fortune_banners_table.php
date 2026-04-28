<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_banners
 *
 * เก็บรูปแบนเนอร์ที่ส่งให้ลูกค้าเมื่อ:
 *   - กด reaction บนโพสต์
 *   - คอมเมนต์ใต้โพสต์
 *   - ทักเพจครั้งแรก (welcome)
 *
 * แอดมินอัพโหลดได้, จัดเรียงได้, เปิด/ปิดเป็นรายตัว, สุ่มหรือเรียงตามลำดับได้
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (Schema::hasTable('fortune_banners')) {
            return;
        }

        Schema::create('fortune_banners', function (Blueprint $table) {
            $table->id();

            // ชื่อ + คำอธิบาย
            $table->string('name', 100); // ชื่อภายใน เช่น "Banner #1 - ขอบคุณกดไลก์"
            $table->string('description', 500)->nullable();

            // ไฟล์ภาพ
            $table->string('image_path', 500); // เช่น images/fortune/banners/banner-01.jpg
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('file_size')->nullable(); // bytes

            // จัดเรียง + เปิดปิด
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // สถิติการใช้งาน
            $table->unsignedInteger('send_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();

            // Tracking
            $table->unsignedBigInteger('uploaded_by')->nullable(); // user_id ของ admin ที่ upload

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order'], 'fb_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_banners');
    }
};
