<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บหมวดหมู่ + คำค้น (seed) ของระบบโพสคอนเทนต์สายมู
     *
     * 1 row = 1 หมวดหมู่ใหญ่ (เช่น "การแก้เคล็ด")
     *   - sub_topic_pool: array seed คำค้น เช่น ["แก้เคล็ดเงินไหลออก", "แก้กรรมเก่า", ...]
     *   - hashtag_pool: array hashtag ที่จะหมุนใช้
     *   - image_keywords: array keywords สำหรับ AI image gen
     *   - search_queries: array คำค้น Tavily/Brave (ภาษาไทย/อังกฤษ ตามแหล่งข้อมูล)
     *
     * Service จะสุ่ม sub_topic 1 ตัว + ใช้ hashtag/image keyword/search query
     * ผูกกับหมวด เพื่อให้คอนเทนต์ไม่ซ้ำและสอดคล้อง
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_mystic_topics')) {
            return;
        }

        Schema::create('fortune_mystic_topics', function (Blueprint $table) {
            $table->id();

            // ── ข้อมูลหมวด
            $table->string('slug', 100)->unique()
                ->comment('slug สำหรับ reference เช่น "saimoo", "kae-khlet"');
            $table->string('name_th', 200)
                ->comment('ชื่อหมวด ภาษาไทย เช่น "สายมู"');
            $table->text('description_th')->nullable()
                ->comment('คำอธิบายสั้น');
            $table->string('emoji', 10)->nullable()
                ->comment('emoji ประจำหมวด');

            // ── Pool ของ sub-topic, hashtag, image keyword, search query
            $table->json('sub_topic_pool')
                ->comment('array sub-topics สุ่ม เช่น ["แก้เคล็ดเงินไหลออก", "ทำอย่างไรเมื่อบ้านมีพลังงานลบ"]');
            $table->json('hashtag_pool')
                ->comment('array hashtags เช่น ["#สายมู", "#แก้เคล็ด"]');
            $table->json('image_keywords')
                ->comment('array keywords สำหรับ AI image gen เช่น ["thai temple", "incense smoke"]');
            $table->json('search_queries')
                ->comment('array คำค้น Tavily/Brave เช่น ["how to fix bad luck thai belief", "ทำอย่างไรเมื่อชีวิตติดขัด"]');

            // ── Caption template (optional override AI prompt)
            $table->text('caption_prompt_template')->nullable()
                ->comment('Override AI prompt — เว้นว่างใช้ template ปกติ');

            // ── Control
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('use_count')->default(0)
                ->comment('นับการใช้ — รอบหน้าจะหมุนหา topic ที่ใช้น้อยสุด');
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['is_enabled', 'sort_order'], 'fmt_active_idx');
            $table->index('last_used_at', 'fmt_last_used_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_mystic_topics');
    }
};
