<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง forum_likes สำหรับเก็บการกดไลค์ (หัวใจ)
     *
     * ใช้ระบบ polymorphic เพื่อรองรับทั้ง threads และ posts
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('forum_likes')) {
            return;
        }

        Schema::create('forum_likes', function (Blueprint $table) {
            $table->id();

            // ผู้ที่กดไลค์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Polymorphic relation (thread หรือ post)
            $table->morphs('likeable');

            $table->timestamp('created_at')->useCurrent();

            // Unique constraint - ผู้ใช้กดไลค์ได้ครั้งเดียวต่อ item
            $table->unique(['user_id', 'likeable_type', 'likeable_id'], 'forum_likes_unique');
        });
    }

    /**
     * ลบตาราง forum_likes
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_likes');
    }
};
