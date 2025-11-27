<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_projects
     * เก็บโปรเจกต์สร้างวีดีโอแต่ละรายการ
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_projects')) {
            return;
        }

        Schema::create('video_auto_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('UUID สำหรับ public access');

            // ความสัมพันธ์
            $table->unsignedBigInteger('template_id')->nullable()->comment('เทมเพลตที่ใช้');
            $table->unsignedBigInteger('created_by')->nullable()->comment('ผู้สร้าง');

            // ข้อมูลโปรเจกต์
            $table->string('name')->comment('ชื่อโปรเจกต์');
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->enum('status', [
                'draft',        // แบบร่าง
                'pending',      // รอดำเนินการ
                'generating',   // กำลังสร้าง
                'completed',    // สร้างเสร็จ
                'publishing',   // กำลังโพส
                'published',    // โพสแล้ว
                'failed',       // ล้มเหลว
                'cancelled'     // ยกเลิก
            ])->default('draft')->index();

            // === Music Configuration (Override Template) ===
            $table->string('music_genre')->nullable();
            $table->string('music_mood')->nullable();
            $table->string('music_style')->nullable();
            $table->unsignedInteger('music_duration')->nullable();
            $table->text('music_prompt')->nullable()->comment('Custom prompt สำหรับโปรเจกต์นี้');

            // === Music Output ===
            $table->string('generated_music_url')->nullable()->comment('URL เพลงที่สร้างได้');
            $table->string('generated_music_path')->nullable()->comment('Path ไฟล์เพลงในเครื่อง');
            $table->string('music_title')->nullable()->comment('ชื่อเพลงที่ Suno สร้าง');
            $table->json('music_metadata')->nullable()->comment('Metadata จาก Suno');

            // === Image Configuration (Override Template) ===
            $table->string('image_style')->nullable();
            $table->string('image_theme')->nullable();
            $table->string('image_color_scheme')->nullable();
            $table->unsignedInteger('images_count')->nullable();
            $table->text('image_prompt')->nullable()->comment('Custom prompt สำหรับภาพ');

            // === Image Output ===
            $table->json('generated_images')->nullable()->comment('รายการภาพที่สร้างได้ [{ url, path, prompt }]');
            $table->unsignedInteger('generated_images_count')->default(0);

            // === Video Configuration (Override Template) ===
            $table->string('video_resolution')->nullable();
            $table->unsignedInteger('slide_duration')->nullable();
            $table->string('transition_type')->nullable();
            $table->boolean('add_intro')->nullable();
            $table->boolean('add_outro')->nullable();

            // === Video Output ===
            $table->string('generated_video_url')->nullable()->comment('URL วีดีโอที่สร้างได้');
            $table->string('generated_video_path')->nullable()->comment('Path ไฟล์วีดีโอในเครื่อง');
            $table->unsignedInteger('video_duration')->nullable()->comment('ความยาววีดีโอ (วินาที)');
            $table->unsignedBigInteger('video_size')->nullable()->comment('ขนาดไฟล์ (bytes)');
            $table->json('video_metadata')->nullable()->comment('Metadata ของวีดีโอ');

            // === Publishing Configuration ===
            $table->string('video_title')->nullable()->comment('ชื่อวีดีโอที่จะโพส');
            $table->text('video_description')->nullable()->comment('คำอธิบายวีดีโอ');
            $table->json('video_tags')->nullable()->comment('Tags');
            $table->string('video_category')->nullable()->comment('Category');
            $table->string('video_privacy')->default('public')->comment('Privacy setting');
            $table->string('video_thumbnail')->nullable()->comment('Thumbnail');

            // === Publishing Status ===
            $table->json('target_platforms')->nullable()->comment('Platforms ที่จะโพส');
            $table->json('published_urls')->nullable()->comment('URLs ที่โพสแล้ว { platform: url }');
            $table->json('publish_results')->nullable()->comment('ผลการโพสแต่ละ platform');

            // === Scheduling ===
            $table->boolean('is_scheduled')->default(false)->comment('ตั้งเวลาโพสหรือไม่');
            $table->timestamp('scheduled_at')->nullable()->comment('เวลาที่จะโพส');
            $table->timestamp('published_at')->nullable()->comment('เวลาที่โพสจริง');

            // === Progress & Timing ===
            $table->unsignedTinyInteger('progress')->default(0)->comment('ความคืบหน้า 0-100%');
            $table->string('current_step')->nullable()->comment('ขั้นตอนปัจจุบัน');
            $table->timestamp('started_at')->nullable()->comment('เริ่มทำงานเมื่อไหร่');
            $table->timestamp('completed_at')->nullable()->comment('เสร็จเมื่อไหร่');
            $table->unsignedInteger('processing_time')->nullable()->comment('เวลาทำงานทั้งหมด (วินาที)');

            // === Error Handling ===
            $table->text('last_error')->nullable()->comment('Error ล่าสุด');
            $table->unsignedTinyInteger('retry_count')->default(0)->comment('จำนวนครั้งที่ retry');
            $table->unsignedTinyInteger('max_retries')->default(3)->comment('retry สูงสุด');

            // === Statistics ===
            $table->json('statistics')->nullable()->comment('สถิติการรับชม { views, likes, comments }');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'created_at'], 'vap_status_created_idx');
            $table->index(['is_scheduled', 'scheduled_at'], 'vap_schedule_idx');
            $table->index(['created_by', 'status'], 'vap_creator_status_idx');

            // Foreign keys
            if (Schema::hasTable('video_auto_templates')) {
                $table->foreign('template_id')
                    ->references('id')
                    ->on('video_auto_templates')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('users')) {
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * ลบตาราง video_auto_projects
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_projects');
    }
};
