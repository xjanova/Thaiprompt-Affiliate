<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง forum_threads สำหรับเก็บกระทู้/หัวข้อ
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('forum_threads')) {
            return;
        }

        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('category_id')
                ->constrained('forum_categories')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ข้อมูลกระทู้
            $table->string('title');                        // หัวข้อกระทู้
            $table->string('slug')->unique();               // Slug สำหรับ URL
            $table->longText('content');                    // เนื้อหากระทู้ (HTML/Markdown)
            $table->text('excerpt')->nullable();            // บทคัดย่อ

            // การปักหมุดและล็อค
            $table->boolean('is_pinned')->default(false);   // ปักหมุดไว้ด้านบน
            $table->boolean('is_locked')->default(false);   // ล็อคไม่ให้ตอบ
            $table->boolean('is_featured')->default(false); // กระทู้แนะนำ

            // สถิติ
            $table->unsignedInteger('views_count')->default(0);     // จำนวนการเข้าชม
            $table->unsignedInteger('posts_count')->default(0);     // จำนวนคอมเมนต์
            $table->unsignedInteger('likes_count')->default(0);     // จำนวนไลค์

            // การตอบกลับล่าสุด
            $table->foreignId('last_post_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('last_post_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('category_id');
            $table->index('user_id');
            $table->index('is_pinned');
            $table->index('is_featured');
            $table->index('last_post_at');
            $table->index('likes_count');
            $table->fullText(['title', 'content'], 'ft_threads_search');
        });
    }

    /**
     * ลบตาราง forum_threads
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_threads');
    }
};
