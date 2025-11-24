<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_deployments
     *
     * เก็บข้อมูลการ deploy AI models ไปยัง Cloud GPU
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_rental_deployments')) {
            return;
        }

        Schema::create('ai_rental_deployments', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ที่ deploy');

            $table->foreignId('cloud_config_id')
                ->constrained('ai_rental_cloud_configs')
                ->onDelete('cascade')
                ->comment('Cloud config ที่ใช้');

            // ข้อมูล Model
            $table->string('model_name')->comment('ชื่อ model');
            $table->string('model_source')->default('huggingface')
                ->comment('แหล่งที่มาของ model (huggingface, custom, etc.)');
            $table->string('model_id')->nullable()
                ->comment('Model ID จาก Hugging Face หรือ source อื่น');
            $table->string('model_type')->nullable()
                ->comment('ประเภท model (text-generation, image-generation, etc.)');
            $table->string('model_version')->nullable()
                ->comment('Version ของ model');

            // Deployment Info
            $table->string('deployment_id')->unique()->nullable()
                ->comment('ID จาก cloud provider');
            $table->string('deployment_name')->comment('ชื่อ deployment');
            $table->text('deployment_description')->nullable()
                ->comment('คำอธิบาย deployment');

            // Configuration
            $table->json('model_config')->nullable()
                ->comment('การตั้งค่า model (JSON)');
            $table->json('runtime_config')->nullable()
                ->comment('การตั้งค่า runtime (JSON)');
            $table->integer('max_batch_size')->default(1)
                ->comment('ขนาด batch สูงสุด');
            $table->integer('timeout_seconds')->default(300)
                ->comment('Timeout (วินาที)');

            // Status
            $table->enum('status', [
                'pending',
                'building',
                'deploying',
                'running',
                'stopped',
                'error',
                'deleted'
            ])->default('pending')->comment('สถานะ deployment');

            $table->timestamp('deployed_at')->nullable()
                ->comment('เวลาที่ deploy สำเร็จ');
            $table->timestamp('stopped_at')->nullable()
                ->comment('เวลาที่หยุด');

            // Endpoints
            $table->text('api_endpoint')->nullable()
                ->comment('API endpoint URL');
            $table->text('websocket_endpoint')->nullable()
                ->comment('WebSocket endpoint URL');
            $table->text('health_check_url')->nullable()
                ->comment('Health check URL');

            // Performance & Usage
            $table->integer('total_requests')->default(0)
                ->comment('จำนวน requests ทั้งหมด');
            $table->integer('successful_requests')->default(0)
                ->comment('จำนวน requests สำเร็จ');
            $table->integer('failed_requests')->default(0)
                ->comment('จำนวน requests ที่ล้มเหลว');
            $table->decimal('avg_response_time_ms', 10, 2)->nullable()
                ->comment('เวลาตอบสนองเฉลี่ย (ms)');
            $table->integer('uptime_percentage')->nullable()
                ->comment('Uptime (%)');

            // Cost
            $table->decimal('cost_per_hour', 10, 4)->nullable()
                ->comment('ค่าใช้จ่ายต่อชั่วโมง (USD)');
            $table->decimal('total_cost', 10, 2)->default(0)
                ->comment('ค่าใช้จ่ายรวม (USD)');
            $table->integer('total_hours')->default(0)
                ->comment('จำนวนชั่วโมงรวม');

            // Logs & Errors
            $table->text('last_error')->nullable()
                ->comment('Error ล่าสุด');
            $table->timestamp('last_error_at')->nullable()
                ->comment('เวลาที่เกิด error ล่าสุด');
            $table->text('build_logs')->nullable()
                ->comment('Build logs');

            // Settings
            $table->boolean('auto_stop')->default(false)
                ->comment('หยุดอัตโนมัติเมื่อไม่ใช้งาน');
            $table->integer('auto_stop_minutes')->nullable()
                ->comment('หยุดอัตโนมัติหลังจาก (นาที)');
            $table->boolean('auto_restart')->default(false)
                ->comment('Restart อัตโนมัติเมื่อ error');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');
            $table->text('notes')->nullable()
                ->comment('บันทึกเพิ่มเติม');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('cloud_config_id');
            $table->index('status');
            $table->index('model_source');
            $table->index('model_type');
            $table->index('deployed_at');
        });
    }

    /**
     * ลบตาราง ai_rental_deployments
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_deployments');
    }
};
