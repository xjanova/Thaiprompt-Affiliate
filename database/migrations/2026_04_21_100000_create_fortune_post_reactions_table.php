<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_post_reactions
     *
     * ใช้เก็บ reactions (like, love, wow, haha ฯลฯ) ที่ user กดบนโพสต์เพจ
     * เพื่อ:
     * 1. Analytics — รู้ว่าโพสต์ไหน engagement ดี
     * 2. Warm lead — user ที่กด reaction แล้วมา comment → treat เป็น high-intent
     * 3. Future retargeting — ส่ง DM ถ้า user กลับเข้ามา (มี conversation window)
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_post_reactions')) {
            return;
        }

        Schema::create('fortune_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_user_id', 100)->index();
            $table->string('facebook_post_id', 100)->index();
            $table->string('reaction_type', 20)->nullable(); // like, love, wow, haha, sad, angry
            $table->string('verb', 20)->nullable(); // add, remove, edit
            $table->string('user_name')->nullable();
            $table->boolean('dm_attempted')->default(false); // ลอง DM ไปแล้วหรือยัง
            $table->boolean('dm_success')->default(false); // DM ส่งสำเร็จไหม (อยู่ใน 24hr window)
            $table->timestamp('reacted_at')->nullable();
            $table->timestamps();

            // ป้องกัน duplicate — user reaction ใน post เดียว (แต่ update type ได้ผ่าน verb)
            $table->unique(['facebook_user_id', 'facebook_post_id'], 'unique_user_post_reaction');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_post_reactions');
    }
};
