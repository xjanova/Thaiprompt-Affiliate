<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง huggingface_model_news
     *
     * เก็บข่าวสารและอัพเดทเกี่ยวกับ Hugging Face models
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('huggingface_model_news')) {
            return;
        }

        Schema::create('huggingface_model_news', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('title')->comment('หัวข้อข่าว');
            $table->text('content')->comment('เนื้อหาข่าว');
            $table->text('summary')->nullable()->comment('สรุปข่าว');

            // Model Info
            $table->string('model_id')->nullable()
                ->comment('Model ID จาก Hugging Face');
            $table->string('model_name')->nullable()
                ->comment('ชื่อ model');
            $table->string('model_type')->nullable()
                ->comment('ประเภท model');
            $table->json('model_tags')->nullable()
                ->comment('Tags ของ model');

            // ข้อมูลข่าว
            $table->enum('news_type', [
                'new_model',
                'model_update',
                'trending',
                'announcement',
                'tutorial',
                'benchmark',
                'other'
            ])->default('new_model')->comment('ประเภทข่าว');

            $table->enum('importance', ['low', 'medium', 'high', 'critical'])
                ->default('medium')
                ->comment('ระดับความสำคัญ');

            // Links
            $table->text('source_url')->nullable()
                ->comment('ลิงก์แหล่งข้อมูล');
            $table->text('model_url')->nullable()
                ->comment('ลิงก์ไปยัง model หน้า Hugging Face');
            $table->text('demo_url')->nullable()
                ->comment('ลิงก์ demo');
            $table->text('paper_url')->nullable()
                ->comment('ลิงก์ paper');

            // Media
            $table->text('image_url')->nullable()
                ->comment('รูปภาพประกอบข่าว');
            $table->json('gallery_urls')->nullable()
                ->comment('รูปภาพเพิ่มเติม (array)');

            // Statistics
            $table->bigInteger('model_downloads')->nullable()
                ->comment('จำนวน downloads');
            $table->integer('model_likes')->nullable()
                ->comment('จำนวน likes');
            $table->decimal('benchmark_score', 8, 2)->nullable()
                ->comment('คะแนน benchmark');

            // Author
            $table->string('author_name')->nullable()
                ->comment('ผู้เขียนข่าว');
            $table->string('author_organization')->nullable()
                ->comment('องค์กรของผู้เขียน');

            // Status
            $table->boolean('is_published')->default(true)
                ->comment('เผยแพร่แล้วหรือไม่');
            $table->boolean('is_featured')->default(false)
                ->comment('แสดงเด่นหรือไม่');
            $table->boolean('is_pinned')->default(false)
                ->comment('ปักหมุดหรือไม่');

            $table->timestamp('published_at')->nullable()
                ->comment('เวลาที่เผยแพร่');
            $table->timestamp('featured_until')->nullable()
                ->comment('แสดงเด่นจนถึง');

            // Engagement
            $table->integer('view_count')->default(0)
                ->comment('จำนวนครั้งที่อ่าน');
            $table->integer('share_count')->default(0)
                ->comment('จำนวนครั้งที่แชร์');
            $table->integer('bookmark_count')->default(0)
                ->comment('จำนวน bookmark');

            // SEO
            $table->string('slug')->unique()->nullable()
                ->comment('URL slug');
            $table->text('meta_description')->nullable()
                ->comment('Meta description');
            $table->json('meta_keywords')->nullable()
                ->comment('Meta keywords');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('news_type');
            $table->index('importance');
            $table->index('is_published');
            $table->index('is_featured');
            $table->index('is_pinned');
            $table->index('published_at');
            $table->index('model_type');
            $table->fullText(['title', 'content', 'summary'], 'news_fulltext_idx');
        });
    }

    /**
     * ลบตาราง huggingface_model_news
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('huggingface_model_news');
    }
};
