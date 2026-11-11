<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง personal_post_drafts — โพสต์เตรียมล่วงหน้าสำหรับเฟซบุ๊กส่วนตัวของเจ้าของ
     *
     * ระบบเตรียมบทความ + ภาพไว้วันละ 4 ช่อง (slot 1-4) จากเทรนด์ Google และข่าวเทค
     * เจ้าของเปิดหน้าหลังบ้านแล้วกดโพสต์เอง — ระบบไม่โพสต์ลงโปรไฟล์ส่วนตัวให้ (Meta ไม่มี API ให้)
     */
    public function up(): void
    {
        if (Schema::hasTable('personal_post_drafts')) {
            return;
        }

        Schema::create('personal_post_drafts', function (Blueprint $table) {
            $table->id();
            $table->date('post_date');
            $table->unsignedTinyInteger('slot');

            // ที่มาของหัวข้อ: google_trend | tech_news
            $table->string('source_type', 20)->default('google_trend');
            $table->string('topic', 255)->nullable();
            $table->string('source_title', 500)->nullable();
            $table->string('source_url', 1000)->nullable();
            $table->string('source_name', 150)->nullable();
            // หัวข่าวที่ใช้เป็นข้อเท็จจริงให้ AI เขียน (กันแต่งข่าวเอง)
            $table->json('news_context')->nullable();

            $table->text('caption')->nullable();
            $table->text('image_prompt')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('image_provider', 80)->nullable();
            $table->string('text_provider', 80)->nullable();

            // draft | generating | ready | failed | posted | skipped
            $table->string('status', 20)->default('draft');
            $table->text('error')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['post_date', 'slot'], 'ppd_date_slot_unique');
            $table->index('status', 'ppd_status_idx');
        });
    }

    /**
     * ลบตาราง personal_post_drafts
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_post_drafts');
    }
};
