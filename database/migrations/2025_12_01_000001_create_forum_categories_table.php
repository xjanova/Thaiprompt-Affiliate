<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง forum_categories สำหรับเก็บหมวดหมู่ฟอรั่ม
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('forum_categories')) {
            return;
        }

        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์สำหรับ Sub-categories
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('forum_categories')
                ->onDelete('cascade');

            // ข้อมูลหมวดหมู่
            $table->string('name');                         // ชื่อหมวดหมู่
            $table->string('slug')->unique();               // Slug สำหรับ URL
            $table->text('description')->nullable();        // คำอธิบาย
            $table->string('icon')->nullable();             // Font Awesome icon class
            $table->string('icon_color')->nullable();       // สีของไอคอน (hex หรือ tailwind)
            $table->string('gradient_from')->nullable();    // สี gradient เริ่มต้น
            $table->string('gradient_to')->nullable();      // สี gradient สิ้นสุด

            // การจัดการ
            $table->integer('display_order')->default(0);   // ลำดับการแสดงผล
            $table->boolean('is_active')->default(true);    // เปิด/ปิดหมวดหมู่
            $table->boolean('is_locked')->default(false);   // ล็อคไม่ให้สร้างกระทู้ใหม่

            // สถิติ (cached)
            $table->unsignedInteger('threads_count')->default(0);  // จำนวนกระทู้
            $table->unsignedInteger('posts_count')->default(0);    // จำนวนโพสต์

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * ลบตาราง forum_categories
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_categories');
    }
};
