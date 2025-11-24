<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง huggingface_trending_models
     *
     * เก็บข้อมูล trending models จาก Hugging Face
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('huggingface_trending_models')) {
            return;
        }

        Schema::create('huggingface_trending_models', function (Blueprint $table) {
            $table->id();

            // ข้อมูล Model
            $table->string('model_id')->unique()
                ->comment('Model ID จาก Hugging Face');
            $table->string('model_name')->comment('ชื่อ model');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // ประเภท
            $table->string('task')->nullable()
                ->comment('Task ของ model (text-generation, image-generation, etc.)');
            $table->string('library')->nullable()
                ->comment('Library ที่ใช้ (transformers, diffusers, etc.)');
            $table->json('tags')->nullable()
                ->comment('Tags');
            $table->json('pipeline_tag')->nullable()
                ->comment('Pipeline tags');

            // Author/Organization
            $table->string('author')->nullable()
                ->comment('ผู้สร้าง model');
            $table->string('organization')->nullable()
                ->comment('องค์กร');

            // Statistics
            $table->bigInteger('downloads')->default(0)
                ->comment('จำนวน downloads');
            $table->bigInteger('downloads_last_month')->default(0)
                ->comment('Downloads เดือนล่าสุด');
            $table->integer('likes')->default(0)
                ->comment('จำนวน likes');
            $table->integer('trending_score')->default(0)
                ->comment('คะแนน trending (0-100)');

            // Model Info
            $table->string('model_size')->nullable()
                ->comment('ขนาด model (เช่น 7B, 13B)');
            $table->integer('model_size_bytes')->nullable()
                ->comment('ขนาด model (bytes)');
            $table->json('supported_languages')->nullable()
                ->comment('ภาษาที่รองรับ');
            $table->string('license')->nullable()
                ->comment('ประเภท license');

            // Requirements
            $table->string('min_gpu_memory')->nullable()
                ->comment('GPU memory ขั้นต่ำที่แนะนำ (GB)');
            $table->string('recommended_gpu')->nullable()
                ->comment('GPU ที่แนะนำ');
            $table->json('hardware_requirements')->nullable()
                ->comment('ข้อกำหนด hardware (JSON)');

            // Performance
            $table->json('benchmark_scores')->nullable()
                ->comment('คะแนน benchmark ต่างๆ (JSON)');
            $table->decimal('avg_inference_time_ms', 10, 2)->nullable()
                ->comment('เวลา inference เฉลี่ย (ms)');
            $table->string('performance_tier')->nullable()
                ->comment('ระดับประสิทธิภาพ (fast, medium, slow)');

            // Links
            $table->text('model_url')->nullable()
                ->comment('ลิงก์หน้า Hugging Face');
            $table->text('paper_url')->nullable()
                ->comment('ลิงก์ paper');
            $table->text('demo_url')->nullable()
                ->comment('ลิงก์ demo');
            $table->text('documentation_url')->nullable()
                ->comment('ลิงก์เอกสาร');

            // Compatibility
            $table->boolean('supports_inference_api')->default(false)
                ->comment('รองรับ Inference API หรือไม่');
            $table->boolean('supports_spaces')->default(false)
                ->comment('รองรับ Spaces หรือไม่');
            $table->boolean('can_run_locally')->default(true)
                ->comment('สามารถรันในเครื่องได้หรือไม่');

            // Cloud Compatibility
            $table->json('compatible_cloud_providers')->nullable()
                ->comment('Cloud providers ที่เข้ากันได้');
            $table->decimal('estimated_cost_per_hour', 10, 4)->nullable()
                ->comment('ค่าใช้จ่ายประมาณต่อชั่วโมง (USD)');

            // Ranking & Categorization
            $table->integer('rank_overall')->nullable()
                ->comment('อันดับโดยรวม');
            $table->integer('rank_in_category')->nullable()
                ->comment('อันดับในหมวดหมู่');
            $table->string('category')->nullable()
                ->comment('หมวดหมู่หลัก');

            // Status & Features
            $table->boolean('is_featured')->default(false)
                ->comment('แนะนำหรือไม่');
            $table->boolean('is_recommended')->default(false)
                ->comment('แนะนำสำหรับผู้เริ่มต้น');
            $table->boolean('is_production_ready')->default(false)
                ->comment('พร้อมใช้งาน production');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
                ->default('intermediate')
                ->comment('ระดับความยาก');

            // Timestamps
            $table->timestamp('first_seen_at')->nullable()
                ->comment('เห็นครั้งแรกเมื่อ');
            $table->timestamp('last_updated_at')->nullable()
                ->comment('อัพเดทล่าสุดจาก Hugging Face');
            $table->timestamp('featured_at')->nullable()
                ->comment('เริ่มแสดงเด่นเมื่อ');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('task');
            $table->index('trending_score');
            $table->index('downloads');
            $table->index('likes');
            $table->index('is_featured');
            $table->index('is_recommended');
            $table->index('category');
            $table->index('rank_overall');
            $table->fullText(['model_name', 'description'], 'model_fulltext_idx');
        });
    }

    /**
     * ลบตาราง huggingface_trending_models
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('huggingface_trending_models');
    }
};
