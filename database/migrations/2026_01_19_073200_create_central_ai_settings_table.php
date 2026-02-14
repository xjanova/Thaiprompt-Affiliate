<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง central_ai_settings
     * เก็บการตั้งค่ารวมของระบบ Central AI สำหรับจัดการ Ollama และ AI Providers
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('central_ai_settings')) {
            return;
        }

        Schema::create('central_ai_settings', function (Blueprint $table) {
            $table->id();

            // === การตั้งค่าโดยรวม ===
            $table->string('name')->default('Central AI')->comment('ชื่อการตั้งค่า');
            $table->boolean('setup_completed')->default(false)->comment('การติดตั้งเสร็จสมบูรณ์');
            $table->integer('setup_step')->default(0)->comment('ขั้นตอนการติดตั้งปัจจุบัน (0-5)');
            $table->boolean('is_active')->default(false)->comment('สถานะการใช้งานระบบ');

            // === Ollama Configuration ===
            $table->boolean('is_ollama_installed')->default(false)->comment('Ollama ติดตั้งแล้ว');
            $table->string('ollama_version')->nullable()->comment('เวอร์ชันของ Ollama');
            $table->enum('ollama_status', ['running', 'stopped', 'error', 'not_installed'])
                ->default('not_installed')
                ->comment('สถานะของ Ollama service');
            $table->string('ollama_host')->default('localhost')->comment('Ollama host');
            $table->integer('ollama_port')->default(11434)->comment('Ollama port');
            $table->string('default_ollama_model')->default('llama3:8b')->comment('โมเดล Ollama เริ่มต้น');
            $table->json('installed_models')->nullable()->comment('รายการโมเดลที่ติดตั้งแล้ว');

            // === PostXAgent Configuration ===
            $table->boolean('postxagent_enabled')->default(false)->comment('เปิดใช้งาน PostXAgent');
            $table->string('postxagent_host')->default('localhost')->comment('PostXAgent host');
            $table->integer('postxagent_api_port')->default(5000)->comment('PostXAgent API port');
            $table->integer('postxagent_signalr_port')->default(5000)->comment('PostXAgent SignalR port');
            $table->string('postxagent_api_key')->nullable()->comment('PostXAgent API key');
            $table->enum('postxagent_status', ['running', 'stopped', 'error', 'not_configured'])
                ->default('not_configured')
                ->comment('สถานะของ PostXAgent');
            $table->string('postxagent_preferred_provider')->default('auto')
                ->comment('Provider ที่ต้องการใช้ (auto, ollama, openai, anthropic, gemini)');

            // === System Resources ===
            $table->integer('cpu_cores')->nullable()->comment('จำนวน CPU cores ทั้งหมด');
            $table->decimal('ram_gb', 8, 2)->nullable()->comment('RAM ทั้งหมด (GB)');
            $table->decimal('disk_gb', 10, 2)->nullable()->comment('พื้นที่ disk ทั้งหมด (GB)');
            $table->decimal('disk_available_gb', 10, 2)->nullable()->comment('พื้นที่ disk ที่ว่าง (GB)');
            $table->string('os_type')->nullable()->comment('ระบบปฏิบัติการ (linux, windows, macos)');

            // === RAG Settings ===
            $table->boolean('enable_rag')->default(true)->comment('เปิดใช้งาน RAG');
            $table->string('embedding_provider')->default('ollama')->comment('Provider สำหรับ embeddings (ollama, openai)');
            $table->string('embedding_model')->default('nomic-embed-text')->comment('โมเดลสำหรับ embeddings');
            $table->decimal('similarity_threshold', 3, 2)->default(0.70)->comment('Threshold สำหรับ cosine similarity');
            $table->integer('max_context_chunks')->default(5)->comment('จำนวน chunks สูงสุดที่ใช้ใน context');

            // === LINE Bot Settings ===
            $table->boolean('auto_reply_enabled')->default(true)->comment('เปิดใช้งานตอบกลับอัตโนมัติ');
            $table->integer('response_max_tokens')->default(2048)->comment('จำนวน tokens สูงสุดในคำตอบ');
            $table->decimal('default_temperature', 3, 2)->default(0.70)->comment('Temperature เริ่มต้น');
            $table->string('fallback_message')->nullable()->comment('ข้อความสำรอง (ถ้า AI ล้มเหลว)');

            // === Statistics & Monitoring ===
            $table->bigInteger('total_requests')->default(0)->comment('จำนวนคำขอทั้งหมด');
            $table->bigInteger('successful_requests')->default(0)->comment('จำนวนคำขอที่สำเร็จ');
            $table->bigInteger('failed_requests')->default(0)->comment('จำนวนคำขอที่ล้มเหลว');
            $table->timestamp('last_health_check_at')->nullable()->comment('เวลา health check ล่าสุด');
            $table->json('health_check_result')->nullable()->comment('ผลลัพธ์ health check ล่าสุด');

            // === Metadata ===
            $table->json('extra_config')->nullable()->comment('การตั้งค่าเพิ่มเติม (JSON)');
            $table->text('notes')->nullable()->comment('หมายเหตุ');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('ollama_status');
            $table->index('postxagent_status');
        });
    }

    /**
     * ลบตาราง central_ai_settings
     */
    public function down(): void
    {
        Schema::dropIfExists('central_ai_settings');
    }
};
