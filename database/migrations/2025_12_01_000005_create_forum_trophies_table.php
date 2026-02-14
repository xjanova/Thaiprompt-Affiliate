<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง forum_trophies สำหรับเก็บประเภทรางวัล/โทรฟี่
     *
     * รองรับทั้งโทรฟี่ดี (positive) และไม่ดี (negative)
     * โทรฟี่ไม่ดีจะติดตลอดไป (is_permanent = true)
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('forum_trophies')) {
            return;
        }

        Schema::create('forum_trophies', function (Blueprint $table) {
            $table->id();

            // ข้อมูลโทรฟี่
            $table->string('name');                         // ชื่อโทรฟี่
            $table->string('slug')->unique();               // Slug
            $table->text('description')->nullable();        // คำอธิบาย

            // ไอคอนและสี
            $table->string('icon');                         // Font Awesome icon หรือ emoji
            $table->string('icon_color')->nullable();       // สีไอคอน (Tailwind class)
            $table->string('name_color')->nullable();       // สีชื่อผู้ใช้ที่ได้รับโทรฟี่นี้
            $table->string('badge_gradient_from')->nullable(); // สี gradient เริ่มต้น
            $table->string('badge_gradient_to')->nullable();   // สี gradient สิ้นสุด

            // ประเภท
            $table->enum('type', ['positive', 'negative'])->default('positive');

            // การติดตลอด - โทรฟี่ไม่ดีจะถอดไม่ได้
            $table->boolean('is_permanent')->default(false);

            // เงื่อนไขการได้รับ (automatic)
            $table->enum('requirement_type', [
                'likes_received',           // ได้รับไลค์รวม X ครั้ง
                'posts_count',              // โพสต์ครบ X โพสต์
                'threads_count',            // สร้างกระทู้ครบ X กระทู้
                'best_answers_count',       // ได้รับ best answer X ครั้ง
                'spam_reports',             // ถูกรายงาน spam X ครั้ง
                'warnings_count',           // ได้รับคำเตือน X ครั้ง
                'banned_count',             // ถูกแบน X ครั้ง
                'manual',                   // ให้โดย admin เท่านั้น
            ])->default('manual');
            $table->unsignedInteger('requirement_value')->default(0);

            // ลำดับการแสดงผล
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('is_permanent');
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง forum_trophies
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_trophies');
    }
};
