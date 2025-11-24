<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_rental_models
     *
     * ตารางนี้เก็บข้อมูล AI Models ที่สามารถ deploy ได้
     * เช่น Stable Diffusion, LLaMA, Whisper, etc.
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือยัง
        if (Schema::hasTable('ai_rental_models')) {
            return;
        }

        Schema::create('ai_rental_models', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name')->comment('ชื่อ AI Model (เช่น Stable Diffusion XL, LLaMA 3)');
            $table->string('slug')->unique()->comment('URL-friendly name');
            $table->text('description')->nullable()->comment('คำอธิบาย AI Model');
            $table->string('version')->nullable()->comment('เวอร์ชัน (เช่น 1.0, 2.1)');

            // หมวดหมู่และประเภท
            $table->enum('category', [
                'image_generation',   // สร้างภาพ (Stable Diffusion, DALL-E)
                'text_generation',    // สร้างข้อความ (GPT, LLaMA)
                'audio_generation',   // สร้างเสียง (Whisper, TTS)
                'video_generation',   // สร้างวิดีโอ
                'code_generation',    // สร้างโค้ด (CodeLlama, Copilot)
                'translation',        // แปลภาษา
                'chatbot',           // แชทบอท
                'other',             // อื่นๆ
            ])->default('other')->index()->comment('หมวดหมู่ของ AI Model');

            $table->string('framework')->nullable()->comment('Framework (PyTorch, TensorFlow, JAX)');
            $table->string('license')->nullable()->comment('ลิขสิทธิ์ (MIT, Apache 2.0, Commercial)');

            // Docker Configuration
            $table->string('docker_image')->nullable()->comment('Docker image path');
            $table->json('docker_env_vars')->nullable()->comment('Environment variables สำหรับ Docker');
            $table->json('docker_ports')->nullable()->comment('Ports ที่ต้องเปิด');
            $table->string('docker_command')->nullable()->comment('Command สำหรับรัน container');

            // GPU Requirements
            $table->string('min_gpu_type')->nullable()->comment('GPU ต่ำสุดที่รองรับ (RTX 3060, A100)');
            $table->integer('min_vram_gb')->default(4)->comment('VRAM ขั้นต่ำ (GB)');
            $table->integer('recommended_vram_gb')->nullable()->comment('VRAM แนะนำ (GB)');
            $table->json('supported_gpu_types')->nullable()->comment('GPU types ที่รองรับทั้งหมด');

            // System Requirements
            $table->integer('min_cpu_cores')->default(2)->comment('CPU cores ขั้นต่ำ');
            $table->integer('min_ram_gb')->default(8)->comment('RAM ขั้นต่ำ (GB)');
            $table->integer('min_storage_gb')->default(20)->comment('Storage ขั้นต่ำ (GB)');

            // Performance Metrics
            $table->decimal('avg_inference_time_ms', 10, 2)->nullable()->comment('เวลาประมวลผลเฉลี่ย (milliseconds)');
            $table->integer('max_batch_size')->nullable()->comment('Batch size สูงสุด');

            // Pricing
            $table->decimal('base_cost_per_hour', 10, 4)->nullable()->comment('ราคาพื้นฐาน/ชั่วโมง ($)');
            $table->json('pricing_tiers')->nullable()->comment('ราคาตาม GPU type');

            // Links และ Resources
            $table->string('official_url')->nullable()->comment('Website ของ AI Model');
            $table->string('documentation_url')->nullable()->comment('Documentation');
            $table->string('github_url')->nullable()->comment('GitHub repository');
            $table->string('huggingface_url')->nullable()->comment('Hugging Face model card');
            $table->string('demo_url')->nullable()->comment('Demo/Playground URL');

            // Thumbnail และ Media
            $table->string('thumbnail_url')->nullable()->comment('รูปตัวอย่าง');
            $table->json('gallery_images')->nullable()->comment('รูปภาพตัวอย่างเพิ่มเติม');
            $table->string('video_url')->nullable()->comment('วิดีโอแนะนำ');

            // Tags และ Keywords
            $table->json('tags')->nullable()->comment('Tags สำหรับการค้นหา (array of strings)');
            $table->json('use_cases')->nullable()->comment('กรณีการใช้งาน');

            // Popularity และ Statistics
            $table->integer('total_deployments')->default(0)->comment('จำนวนครั้งที่ถูก deploy ทั้งหมด');
            $table->decimal('average_rating', 3, 2)->nullable()->comment('คะแนนเฉลี่ย (0-5)');
            $table->integer('total_reviews')->default(0)->comment('จำนวน reviews');
            $table->integer('view_count')->default(0)->comment('จำนวนครั้งที่ถูกดู');

            // Status และ Availability
            $table->boolean('is_active')->default(true)->index()->comment('เปิดใช้งาน');
            $table->boolean('is_featured')->default(false)->index()->comment('แนะนำ (featured)');
            $table->boolean('is_new')->default(false)->comment('ใหม่');
            $table->boolean('is_beta')->default(false)->comment('เวอร์ชันทดสอบ');
            $table->enum('availability', ['public', 'private', 'beta', 'coming_soon'])->default('public')->comment('สถานะการเปิดให้ใช้งาน');

            // Display Settings
            $table->integer('display_order')->default(0)->index()->comment('ลำดับการแสดงผล');
            $table->string('badge_text')->nullable()->comment('ข้อความ badge (NEW, POPULAR, BETA)');
            $table->string('badge_color')->nullable()->comment('สี badge (#hex)');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติมอื่นๆ');
            $table->json('config_options')->nullable()->comment('ตัวเลือกการตั้งค่าที่ปรับแต่งได้');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('display_order');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง ai_rental_models
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_models');
    }
};
