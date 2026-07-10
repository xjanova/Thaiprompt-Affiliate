<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * log โพสของระบบแคมเปญคอนเทนต์อัตโนมัติ (1 row = 1 โพส)
     *
     * ต่างจาก fortune_mystic_posts เดิม:
     * - ผูกกับ campaign_id — หลายแคมเปญโพสวัน/เวลาเดียวกันได้ ไม่ชนกัน
     * - slot_time เป็น "HH:MM" (เดิมเป็น slot_hour tinyint — จำกัดชั่วโมงละ 1 โพส)
     * - เก็บ image_prompt ที่ AI สร้างจากเนื้อหา (audit trail)
     *
     * Idempotent: unique (campaign_id, post_date, slot_time)
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('fortune_content_posts')) {
            return;
        }

        Schema::create('fortune_content_posts', function (Blueprint $table) {
            $table->id();

            // ── Slot identification
            $table->foreignId('campaign_id')
                ->constrained('fortune_content_campaigns')
                ->cascadeOnDelete()
                ->comment('แคมเปญเจ้าของโพส');
            $table->date('post_date')
                ->comment('วันที่ของโพส (Asia/Bangkok)');
            $table->string('slot_time', 5)
                ->comment('เวลา slot รูปแบบ "HH:MM" เช่น 08:30');

            // ── หัวข้อที่ได้
            $table->string('sub_topic', 500)->nullable()
                ->comment('หัวข้อย่อยที่สุ่ม/AI เลือก');
            $table->foreignId('mystic_topic_id')
                ->nullable()
                ->constrained('fortune_mystic_topics')
                ->nullOnDelete()
                ->comment('หมวดสายมูเดิม (เฉพาะแคมเปญ topic_source=mystic_topics)');

            // ── Web search sources (audit trail)
            $table->json('sources')->nullable()
                ->comment('array {url, title, snippet} จาก Tavily/Brave/DDG');
            $table->string('search_provider', 50)->nullable()
                ->comment('tavily | brave | duckduckgo | none');

            // ── Generated content
            $table->text('caption')->nullable()
                ->comment('caption หลัง AI เขียน');
            $table->json('hashtags')->nullable();
            $table->text('image_prompt')->nullable()
                ->comment('image prompt ที่ AI สร้างจากเนื้อหา (อังกฤษ)');
            $table->string('image_path', 500)->nullable()
                ->comment('relative path รูป (storage/app/public)');
            $table->string('image_url', 500)->nullable()
                ->comment('absolute URL รูปสำหรับ FB upload');
            $table->string('image_provider', 50)->nullable()
                ->comment('cloudflare-ai | pollinations');

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
            $table->unique(['campaign_id', 'post_date', 'slot_time'], 'fcp_camp_date_slot_unique');
            $table->index('status', 'fcp_status_idx');
            $table->index('post_date', 'fcp_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_content_posts');
    }
};
