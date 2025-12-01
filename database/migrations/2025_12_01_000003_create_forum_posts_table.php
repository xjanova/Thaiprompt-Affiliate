<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง forum_posts สำหรับเก็บคอมเมนต์/การตอบกลับ
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('forum_posts')) {
            return;
        }

        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('thread_id')
                ->constrained('forum_threads')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // การตอบกลับซ้อน (nested replies)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('forum_posts')
                ->onDelete('cascade');

            // เนื้อหา
            $table->longText('content');                    // เนื้อหา (HTML/Markdown)

            // สถิติและสถานะ
            $table->unsignedInteger('likes_count')->default(0);     // จำนวนไลค์
            $table->boolean('is_best_answer')->default(false);      // คำตอบที่ดีที่สุด
            $table->boolean('is_hidden')->default(false);           // ซ่อนโพสต์ (ถูกแบน)

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('thread_id');
            $table->index('user_id');
            $table->index('parent_id');
            $table->index('is_best_answer');
            $table->index('likes_count');
        });
    }

    /**
     * ลบตาราง forum_posts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
