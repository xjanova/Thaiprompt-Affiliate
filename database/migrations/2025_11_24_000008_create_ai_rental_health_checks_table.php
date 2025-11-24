<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_health_checks
     *
     * เก็บผลการตรวจสอบสุขภาพของ deployments
     * รองรับการ monitor อัตโนมัติและ auto-recovery
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_rental_health_checks')) {
            return;
        }

        Schema::create('ai_rental_health_checks', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('deployment_id')
                ->constrained('ai_rental_deployments')
                ->onDelete('cascade')
                ->comment('Deployment ที่ตรวจสอบ');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของ deployment');

            // Health Check Configuration
            $table->string('check_type')->default('api')
                ->comment('ประเภทการตรวจสอบ');
            // check_type: api, ping, websocket, endpoint, custom

            $table->text('check_url')
                ->comment('URL ที่ใช้ตรวจสอบ');

            $table->string('http_method')->default('GET')
                ->comment('HTTP method (GET, POST, HEAD)');

            $table->json('request_headers')->nullable()
                ->comment('Headers สำหรับ request');

            $table->json('request_body')->nullable()
                ->comment('Body สำหรับ request (JSON)');

            // Health Check Results
            $table->enum('status', ['healthy', 'degraded', 'unhealthy', 'unknown'])
                ->default('unknown')
                ->comment('สถานะสุขภาพ');

            $table->integer('http_status_code')->nullable()
                ->comment('HTTP status code ที่ได้รับ');

            $table->decimal('response_time_ms', 10, 2)->nullable()
                ->comment('เวลาตอบสนอง (milliseconds)');

            $table->boolean('is_success')->default(false)
                ->comment('ตรวจสอบสำเร็จหรือไม่');

            $table->text('response_body')->nullable()
                ->comment('Response body ที่ได้รับ');

            $table->text('error_message')->nullable()
                ->comment('ข้อความ error (ถ้ามี)');

            $table->text('error_details')->nullable()
                ->comment('รายละเอียด error');

            // Performance Metrics
            $table->decimal('cpu_usage', 5, 2)->nullable()
                ->comment('CPU usage (%)');

            $table->decimal('memory_usage', 5, 2)->nullable()
                ->comment('Memory usage (%)');

            $table->decimal('gpu_usage', 5, 2)->nullable()
                ->comment('GPU usage (%)');

            $table->bigInteger('memory_used_mb')->nullable()
                ->comment('Memory ที่ใช้ (MB)');

            $table->bigInteger('memory_total_mb')->nullable()
                ->comment('Memory ทั้งหมด (MB)');

            $table->decimal('disk_usage', 5, 2)->nullable()
                ->comment('Disk usage (%)');

            $table->integer('active_connections')->nullable()
                ->comment('จำนวน connections ที่ active');

            $table->integer('queue_length')->nullable()
                ->comment('จำนวน requests ใน queue');

            // Thresholds & Alerts
            $table->boolean('threshold_exceeded')->default(false)
                ->comment('เกิน threshold ที่กำหนด');

            $table->json('thresholds')->nullable()
                ->comment('Thresholds ที่ตั้งไว้ (JSON)');

            $table->boolean('alert_triggered')->default(false)
                ->comment('ส่ง alert แล้ว');

            $table->foreignId('alert_id')
                ->nullable()
                ->constrained('ai_rental_alerts')
                ->onDelete('set null')
                ->comment('Alert ที่เกี่ยวข้อง');

            // Retry Logic
            $table->integer('retry_count')->default(0)
                ->comment('จำนวนครั้งที่ retry');

            $table->integer('max_retries')->default(3)
                ->comment('จำนวน retries สูงสุด');

            $table->timestamp('next_retry_at')->nullable()
                ->comment('เวลาที่จะ retry ครั้งถัดไป');

            // Auto-recovery
            $table->boolean('auto_recovery_attempted')->default(false)
                ->comment('พยายาม recovery อัตโนมัติแล้ว');

            $table->enum('recovery_action', ['none', 'restart', 'scale', 'notify'])
                ->default('none')
                ->comment('การกระทำเพื่อ recovery');

            $table->boolean('recovery_success')->default(false)
                ->comment('Recovery สำเร็จ');

            $table->text('recovery_notes')->nullable()
                ->comment('บันทึกการ recovery');

            // Timing
            $table->timestamp('checked_at')
                ->comment('เวลาที่ตรวจสอบ');

            $table->timestamp('started_at')->nullable()
                ->comment('เริ่มตรวจสอบเมื่อ');

            $table->timestamp('completed_at')->nullable()
                ->comment('ตรวจสอบเสร็จเมื่อ');

            $table->integer('duration_ms')->nullable()
                ->comment('ระยะเวลาที่ใช้ตรวจสอบ (ms)');

            // Scheduled Check Info
            $table->string('check_frequency')->default('5min')
                ->comment('ความถี่ในการตรวจสอบ (1min, 5min, 15min, 30min, 1hour)');

            $table->timestamp('next_check_at')->nullable()
                ->comment('เวลาที่จะตรวจสอบครั้งถัดไป');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->json('headers_received')->nullable()
                ->comment('Response headers ที่ได้รับ');

            $table->timestamps();

            // Indexes
            $table->index('deployment_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('is_success');
            $table->index('checked_at');
            $table->index(['deployment_id', 'checked_at']);
            $table->index('next_check_at');
            $table->index('alert_triggered');
        });
    }

    /**
     * ลบตาราง ai_rental_health_checks
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_health_checks');
    }
};
