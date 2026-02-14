<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_schedules
     * เก็บตารางเวลาสำหรับสร้างวีดีโออัตโนมัติ
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_schedules')) {
            return;
        }

        Schema::create('video_auto_schedules', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->unsignedBigInteger('template_id')->nullable()->comment('เทมเพลตที่ใช้');

            // ข้อมูลพื้นฐาน
            $table->string('name')->comment('ชื่อตารางเวลา');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // === Frequency Settings ===
            $table->enum('frequency_type', [
                'once',         // ครั้งเดียว
                'daily',        // ทุกวัน
                'weekly',       // รายสัปดาห์
                'monthly',      // รายเดือน
                'custom',        // กำหนดเอง (cron)
            ])->default('daily');

            // สำหรับ once
            $table->timestamp('run_at')->nullable()->comment('เวลาที่จะรัน (once)');

            // สำหรับ daily
            $table->time('daily_time')->nullable()->comment('เวลาที่จะรันทุกวัน');

            // สำหรับ weekly
            $table->json('weekly_days')->nullable()->comment('วันในสัปดาห์ [0=อาทิตย์, 1=จันทร์, ...]');
            $table->time('weekly_time')->nullable()->comment('เวลาที่จะรัน');

            // สำหรับ monthly
            $table->json('monthly_days')->nullable()->comment('วันที่ในเดือน [1, 15, 28]');
            $table->time('monthly_time')->nullable()->comment('เวลาที่จะรัน');

            // สำหรับ custom
            $table->string('cron_expression')->nullable()->comment('Cron expression');

            // Timezone
            $table->string('timezone')->default('Asia/Bangkok')->comment('Timezone');

            // === Content Generation Settings ===
            $table->unsignedInteger('videos_per_run')->default(1)->comment('จำนวนวีดีโอที่สร้างต่อครั้ง');
            $table->boolean('randomize_content')->default(false)->comment('สุ่มเนื้อหาหรือไม่');
            $table->json('content_variations')->nullable()->comment('รูปแบบเนื้อหาที่หมุนเวียน');

            // === Publishing Settings ===
            $table->boolean('auto_publish')->default(true)->comment('โพสอัตโนมัติหรือไม่');
            $table->unsignedInteger('publish_delay')->default(0)->comment('หน่วงโพสกี่นาทีหลังสร้างเสร็จ');
            $table->json('publish_platforms')->nullable()->comment('Platforms ที่จะโพส');
            $table->boolean('stagger_publishing')->default(false)->comment('โพสทีละ platform');
            $table->unsignedInteger('stagger_interval')->default(30)->comment('ระยะห่างระหว่าง platform (นาที)');

            // === Limits ===
            $table->unsignedInteger('max_videos_per_day')->nullable()->comment('จำกัดวีดีโอต่อวัน');
            $table->unsignedInteger('max_videos_per_week')->nullable()->comment('จำกัดวีดีโอต่อสัปดาห์');
            $table->unsignedInteger('max_videos_per_month')->nullable()->comment('จำกัดวีดีโอต่อเดือน');
            $table->unsignedInteger('max_total_videos')->nullable()->comment('จำกัดวีดีโอทั้งหมด');

            // === Status ===
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_run_at')->nullable()->comment('รันครั้งล่าสุด');
            $table->timestamp('next_run_at')->nullable()->index()->comment('รันครั้งต่อไป');
            $table->unsignedInteger('run_count')->default(0)->comment('จำนวนครั้งที่รัน');
            $table->unsignedInteger('success_count')->default(0)->comment('รันสำเร็จ');
            $table->unsignedInteger('fail_count')->default(0)->comment('รันล้มเหลว');

            // === Validity ===
            $table->date('valid_from')->nullable()->comment('เริ่มใช้งานวันที่');
            $table->date('valid_until')->nullable()->comment('หมดอายุวันที่');

            // === Error Handling ===
            $table->boolean('pause_on_error')->default(false)->comment('หยุดเมื่อเกิด error');
            $table->unsignedTinyInteger('consecutive_failures')->default(0)->comment('ล้มเหลวติดต่อกัน');
            $table->unsignedTinyInteger('max_consecutive_failures')->default(3)->comment('ล้มเหลวติดต่อกันสูงสุด');
            $table->text('last_error')->nullable();

            // Metadata
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'next_run_at'], 'vas_active_next_idx');
            $table->index(['frequency_type', 'is_active'], 'vas_freq_active_idx');

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
     * ลบตาราง video_auto_schedules
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_schedules');
    }
};
