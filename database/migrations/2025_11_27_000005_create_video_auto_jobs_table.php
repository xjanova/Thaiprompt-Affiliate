<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_jobs
     * เก็บงานที่กำลังรัน/รันเสร็จแล้ว ของแต่ละโปรเจกต์
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_jobs')) {
            return;
        }

        Schema::create('video_auto_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('UUID สำหรับ tracking');

            // ความสัมพันธ์
            $table->unsignedBigInteger('project_id')->comment('โปรเจกต์ที่เกี่ยวข้อง');

            // ข้อมูลงาน
            $table->enum('job_type', [
                'generate_music',       // สร้างเพลง
                'generate_images',      // สร้างภาพ
                'create_video',         // รวมเป็นวีดีโอ
                'upload_youtube',       // อัปโหลด YouTube
                'publish_facebook',     // โพส Facebook
                'publish_instagram',    // โพส Instagram
                'publish_tiktok',       // โพส TikTok
                'publish_twitter',      // โพส Twitter
                'publish_threads',      // โพส Threads
                'full_pipeline',         // ทำทั้งหมด
            ])->index();

            $table->enum('status', [
                'pending',      // รอดำเนินการ
                'queued',       // อยู่ใน queue
                'running',      // กำลังทำงาน
                'completed',    // สำเร็จ
                'failed',       // ล้มเหลว
                'cancelled',    // ยกเลิก
                'retrying',      // กำลัง retry
            ])->default('pending')->index();

            // Queue Integration
            $table->string('queue_job_id')->nullable()->comment('Laravel Queue Job ID');
            $table->string('queue_name')->default('default')->comment('ชื่อ queue');
            $table->unsignedTinyInteger('priority')->default(5)->comment('ความสำคัญ 1-10');

            // Progress
            $table->unsignedTinyInteger('progress')->default(0)->comment('ความคืบหน้า 0-100%');
            $table->string('current_step')->nullable()->comment('ขั้นตอนปัจจุบัน');
            $table->text('progress_message')->nullable()->comment('ข้อความแสดงความคืบหน้า');

            // Input/Output
            $table->json('input_data')->nullable()->comment('ข้อมูล input');
            $table->json('output_data')->nullable()->comment('ผลลัพธ์');

            // Timing
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration')->nullable()->comment('เวลาทำงาน (วินาที)');
            $table->unsignedInteger('estimated_duration')->nullable()->comment('เวลาที่คาดว่าจะใช้');

            // Error Handling
            $table->text('error_message')->nullable();
            $table->text('error_stack_trace')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(3);
            $table->timestamp('next_retry_at')->nullable();

            // Resource Usage
            $table->json('resource_usage')->nullable()->comment('การใช้ทรัพยากร { memory, cpu, api_calls }');
            $table->decimal('api_cost', 10, 4)->default(0)->comment('ค่าใช้จ่าย API');

            // Metadata
            $table->unsignedBigInteger('triggered_by')->nullable()->comment('ผู้สั่งรัน');
            $table->string('trigger_source')->default('manual')->comment('manual, schedule, retry, webhook');

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'job_type'], 'vaj_project_type_idx');
            $table->index(['status', 'queue_name', 'priority'], 'vaj_queue_idx');
            $table->index(['status', 'next_retry_at'], 'vaj_retry_idx');

            // Foreign keys
            $table->foreign('project_id')
                ->references('id')
                ->on('video_auto_projects')
                ->onDelete('cascade');

            if (Schema::hasTable('users')) {
                $table->foreign('triggered_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * ลบตาราง video_auto_jobs
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_jobs');
    }
};
