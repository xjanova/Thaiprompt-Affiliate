<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_templates
     * เก็บเทมเพลตสำหรับสร้างวีดีโอ รวมถึงแนวเพลง, สไตล์ภาพ, และการตั้งค่า
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_templates')) {
            return;
        }

        Schema::create('video_auto_templates', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name')->comment('ชื่อเทมเพลต');
            $table->string('slug')->unique()->comment('Slug สำหรับ URL');
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->string('thumbnail')->nullable()->comment('รูปตัวอย่าง');

            // === Suno Music Settings ===
            $table->string('music_genre')->nullable()->comment('แนวเพลง: pop, rock, jazz, lofi, ambient, etc.');
            $table->string('music_mood')->nullable()->comment('อารมณ์เพลง: happy, sad, energetic, calm, etc.');
            $table->string('music_style')->nullable()->comment('สไตล์: instrumental, vocal, acoustic, electronic');
            $table->unsignedInteger('music_duration')->default(180)->comment('ความยาวเพลง (วินาที)');
            $table->text('music_prompt')->nullable()->comment('Prompt สำหรับ Suno AI');
            $table->json('music_tags')->nullable()->comment('Tags สำหรับ Suno');

            // === Freepik Image Settings ===
            $table->string('image_style')->nullable()->comment('สไตล์ภาพ: realistic, anime, 3d, illustration, etc.');
            $table->string('image_theme')->nullable()->comment('ธีมภาพ: nature, city, abstract, people, etc.');
            $table->string('image_color_scheme')->nullable()->comment('โทนสี: warm, cool, vibrant, pastel, dark');
            $table->unsignedInteger('images_count')->default(10)->comment('จำนวนภาพที่ต้องสร้าง');
            $table->string('image_ratio')->default('16:9')->comment('อัตราส่วนภาพ: 16:9, 9:16, 1:1, 4:3');
            $table->text('image_prompt_template')->nullable()->comment('Template prompt สำหรับ Freepik');
            $table->json('image_keywords')->nullable()->comment('Keywords สำหรับค้นหา/สร้างภาพ');

            // === Video Creation Settings ===
            $table->string('video_resolution')->default('1080p')->comment('ความละเอียด: 720p, 1080p, 4k');
            $table->string('video_format')->default('mp4')->comment('รูปแบบไฟล์: mp4, mov, webm');
            $table->unsignedInteger('slide_duration')->default(5)->comment('ความยาวแต่ละ slide (วินาที)');
            $table->string('transition_type')->default('fade')->comment('Transition: fade, slide, zoom, dissolve');
            $table->unsignedInteger('transition_duration')->default(1000)->comment('ความยาว transition (ms)');
            $table->boolean('add_intro')->default(false)->comment('เพิ่ม intro หรือไม่');
            $table->boolean('add_outro')->default(false)->comment('เพิ่ม outro หรือไม่');
            $table->string('intro_video')->nullable()->comment('Path intro video');
            $table->string('outro_video')->nullable()->comment('Path outro video');

            // === Video Effects ===
            $table->boolean('enable_ken_burns')->default(true)->comment('เปิด Ken Burns effect');
            $table->boolean('enable_text_overlay')->default(false)->comment('เปิดข้อความซ้อน');
            $table->json('text_overlay_settings')->nullable()->comment('ตั้งค่าข้อความซ้อน');
            $table->boolean('enable_watermark')->default(false)->comment('เปิด watermark');
            $table->string('watermark_image')->nullable()->comment('รูป watermark');
            $table->string('watermark_position')->default('bottom-right')->comment('ตำแหน่ง watermark');

            // === Publishing Settings ===
            $table->string('default_title_template')->nullable()->comment('Template ชื่อวีดีโอ');
            $table->text('default_description_template')->nullable()->comment('Template คำอธิบาย');
            $table->json('default_tags')->nullable()->comment('Tags default');
            $table->string('default_category')->nullable()->comment('Category default');
            $table->string('default_privacy')->default('public')->comment('Privacy: public, private, unlisted');
            $table->json('target_platforms')->nullable()->comment('Platforms ที่จะโพส');

            // === Schedule Settings ===
            $table->boolean('enable_auto_schedule')->default(false)->comment('เปิด auto schedule');
            $table->json('schedule_settings')->nullable()->comment('ตั้งค่า schedule: days, times, frequency');

            // การจัดการ
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('usage_count')->default(0)->comment('จำนวนครั้งที่ใช้');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['music_genre', 'is_active'], 'vat_genre_active_idx');
            $table->index(['is_default', 'is_active'], 'vat_default_active_idx');

            // Foreign keys
            if (Schema::hasTable('users')) {
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * ลบตาราง video_auto_templates
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_templates');
    }
};
