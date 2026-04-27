<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บโพสดวงประจำวันรายเจ้าของวันเกิด (จันทร์-อาทิตย์)
     *
     * Flow:
     * 1. Scheduler ที่ 01:00–07:00 → generate-and-publish ทีละวันเกิด
     * 2. AI สุ่มไพ่ทาโรต์ + แต่งคำทำนายสั้นๆ ของวันนั้น
     * 3. Composite รูปไพ่ + overlay text → เก็บที่ storage
     * 4. POST /me/feed (Facebook Page) → คืน fb_post_id
     *
     * 1 row = 1 โพส (วันละ 7 rows ต่อ Page)
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_daily_horoscope_posts')) {
            return;
        }

        Schema::create('fortune_daily_horoscope_posts', function (Blueprint $table) {
            $table->id();

            // Target
            $table->date('post_date')
                ->comment('วันที่ดวง (post_date = today)');
            $table->unsignedTinyInteger('day_of_birth')
                ->comment('วันเกิด: 1=จันทร์, 2=อังคาร, 3=พุธ, 4=พฤหัส, 5=ศุกร์, 6=เสาร์, 7=อาทิตย์');

            // Content (generated)
            $table->unsignedBigInteger('tarot_card_id')
                ->nullable()
                ->comment('TarotCard ที่สุ่มได้');
            $table->boolean('is_reversed')
                ->default(false)
                ->comment('ไพ่กลับด้านหรือไม่');
            $table->text('caption')
                ->nullable()
                ->comment('ข้อความ caption ที่ AI สร้าง (สั้นๆ ตรงประเด็น)');
            $table->string('image_path', 500)
                ->nullable()
                ->comment('path รูปประกอบ (storage)');
            $table->string('image_url', 500)
                ->nullable()
                ->comment('public URL ของรูป');

            // Status
            $table->enum('status', ['pending', 'generating', 'ready', 'publishing', 'posted', 'failed'])
                ->default('pending');
            $table->string('fb_post_id', 100)
                ->nullable()
                ->comment('Facebook Post ID หลัง post สำเร็จ');
            $table->string('fb_post_url', 500)
                ->nullable();
            $table->text('error_message')
                ->nullable()
                ->comment('error ตอน generate หรือ publish');

            // Audit
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['post_date', 'day_of_birth'], 'fdh_date_day_unique');
            $table->index('status', 'fdh_status_idx');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_daily_horoscope_posts');
    }
};
