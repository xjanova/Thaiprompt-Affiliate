<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_publish_history
     * เก็บประวัติการโพสต์วีดีโอไปยัง Social Media
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_publish_history')) {
            return;
        }

        Schema::create('video_auto_publish_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('UUID สำหรับ tracking');

            // ความสัมพันธ์
            $table->unsignedBigInteger('project_id')->comment('โปรเจกต์ที่โพสต์');
            $table->unsignedBigInteger('job_id')->nullable()->comment('Job ที่โพสต์');
            $table->unsignedBigInteger('schedule_id')->nullable()->comment('Schedule ที่ trigger');

            // Platform Info
            $table->enum('platform', [
                'youtube',
                'facebook',
                'instagram',
                'tiktok',
                'twitter',
                'threads',
                'line_voom',
                'other'
            ])->index()->comment('Platform ที่โพสต์');

            $table->string('platform_account')->nullable()->comment('บัญชีที่ใช้โพสต์');

            // Content Info
            $table->string('title')->comment('ชื่อวีดีโอที่โพสต์');
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->json('hashtags')->nullable()->comment('Hashtags');
            $table->string('thumbnail_path')->nullable()->comment('Path ของ thumbnail');
            $table->string('thumbnail_url')->nullable()->comment('URL ของ thumbnail');

            // Video Info (snapshot ตอนโพสต์)
            $table->string('video_path')->nullable()->comment('Path ของวีดีโอตอนโพสต์');
            $table->string('music_title')->nullable()->comment('ชื่อเพลงที่ใช้');
            $table->string('music_genre')->nullable()->comment('แนวเพลง');
            $table->unsignedInteger('video_duration')->nullable()->comment('ความยาววีดีโอ (วินาที)');
            $table->unsignedBigInteger('video_size')->nullable()->comment('ขนาดไฟล์ (bytes)');

            // Publish Result
            $table->enum('status', [
                'pending',      // รอโพสต์
                'uploading',    // กำลังอัปโหลด
                'processing',   // Platform กำลังประมวลผล
                'published',    // โพสต์สำเร็จ
                'failed',       // โพสต์ล้มเหลว
                'deleted'       // ถูกลบจาก Platform
            ])->default('pending')->index();

            $table->string('platform_post_id')->nullable()->comment('ID ของโพสต์บน Platform');
            $table->string('platform_url')->nullable()->comment('URL ของโพสต์บน Platform');

            // Timing
            $table->timestamp('scheduled_at')->nullable()->comment('เวลาที่ตั้งไว้');
            $table->timestamp('published_at')->nullable()->index()->comment('เวลาที่โพสต์จริง');

            // Error Info
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            // Engagement (อัปเดตหลัง)
            $table->unsignedBigInteger('views_count')->default(0)->comment('ยอดวิว');
            $table->unsignedBigInteger('likes_count')->default(0)->comment('ยอดไลค์');
            $table->unsignedBigInteger('comments_count')->default(0)->comment('ยอดคอมเม้นต์');
            $table->unsignedBigInteger('shares_count')->default(0)->comment('ยอดแชร์');
            $table->timestamp('engagement_updated_at')->nullable();

            // File Cleanup
            $table->boolean('source_deleted')->default(false)->comment('ลบไฟล์ต้นฉบับแล้ว');
            $table->timestamp('source_deleted_at')->nullable();

            // Metadata
            $table->json('api_response')->nullable()->comment('Response จาก API');
            $table->unsignedBigInteger('published_by')->nullable()->comment('ผู้โพสต์');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['project_id', 'platform'], 'vaph_project_platform_idx');
            $table->index(['platform', 'status', 'published_at'], 'vaph_platform_status_idx');
            $table->index(['published_at', 'platform'], 'vaph_published_idx');

            // Foreign keys
            $table->foreign('project_id')
                ->references('id')
                ->on('video_auto_projects')
                ->onDelete('cascade');

            if (Schema::hasTable('video_auto_jobs')) {
                $table->foreign('job_id')
                    ->references('id')
                    ->on('video_auto_jobs')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('video_auto_schedules')) {
                $table->foreign('schedule_id')
                    ->references('id')
                    ->on('video_auto_schedules')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('users')) {
                $table->foreign('published_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * ลบตาราง video_auto_publish_history
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_publish_history');
    }
};
