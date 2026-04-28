<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บ log การโพสคอนเทนต์สายมู (1 row = 1 โพส)
     *
     * Flow:
     * 1. Scheduler (ทุกชั่วโมง) เรียก fortune:mystic:publish
     * 2. Command เช็คเวลาว่าตรง slot ใน mystic_content_schedule หรือยัง
     * 3. ถ้าใช่ → สุ่ม topic + sub_topic → Tavily search → Groq rewrite → AI image → publish FB
     * 4. บันทึก fb_post_id + fb_post_url + sources
     *
     * Idempotent: unique (post_date, slot_hour) — กันโพสซ้ำในช่วงเดียวกัน
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_mystic_posts')) {
            return;
        }

        Schema::create('fortune_mystic_posts', function (Blueprint $table) {
            $table->id();

            // ── Slot identification
            $table->date('post_date')
                ->comment('วันที่ของโพส (Asia/Bangkok)');
            $table->unsignedTinyInteger('slot_hour')
                ->comment('ชั่วโมงของ slot (0-23) เช่น 8, 20');

            // ── Topic ที่สุ่มได้
            $table->foreignId('topic_id')
                ->nullable()
                ->constrained('fortune_mystic_topics')
                ->nullOnDelete()
                ->comment('หมวดหมู่ที่สุ่มได้');
            $table->string('sub_topic', 500)->nullable()
                ->comment('sub-topic ที่สุ่มได้ เช่น "แก้เคล็ดเงินไหลออก"');

            // ── Web search sources (audit trail)
            $table->json('sources')->nullable()
                ->comment('array {url, title, snippet} จาก Tavily/Brave');
            $table->string('search_provider', 50)->nullable()
                ->comment('tavily | brave | duckduckgo | none');

            // ── Generated content
            $table->text('caption')->nullable()
                ->comment('caption หลัง AI rewrite (Groq)');
            $table->json('hashtags')->nullable()
                ->comment('array hashtags ที่ใช้');
            $table->string('image_path', 500)->nullable()
                ->comment('relative path รูป (storage/app/public)');
            $table->string('image_url', 500)->nullable()
                ->comment('absolute URL รูปสำหรับ FB upload');
            $table->string('image_provider', 50)->nullable()
                ->comment('cloudflare-ai | pollinations | gd');

            // ── Status
            $table->enum('status', ['pending', 'researching', 'generating', 'ready', 'publishing', 'posted', 'failed'])
                ->default('pending');
            $table->string('fb_post_id', 100)->nullable();
            $table->string('fb_post_url', 500)->nullable();
            $table->text('error_message')->nullable();

            // ── Timing audit
            $table->timestamp('researched_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            // ── Indexes
            $table->unique(['post_date', 'slot_hour'], 'fmp_date_slot_unique');
            $table->index('status', 'fmp_status_idx');
            $table->index('topic_id', 'fmp_topic_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_mystic_posts');
    }
};
