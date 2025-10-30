<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            // เพิ่มฟิลด์สำหรับระบุประเภทของสไลด์
            $table->enum('media_type', ['image', 'video'])->default('image')->after('id');

            // สำหรับวีดีโอ YouTube, Vimeo หรือแหล่งอื่น
            $table->string('video_url')->nullable()->after('image');

            // สำหรับวีดีโอที่อัพโหลดเอง
            $table->string('video_file')->nullable()->after('video_url');

            // ประเภทวีดีโอ: youtube, vimeo, upload, other
            $table->enum('video_type', ['youtube', 'vimeo', 'upload', 'other'])->nullable()->after('video_file');

            // การตั้งค่าวีดีโอ (JSON)
            // - autoplay: true/false
            // - muted: true/false
            // - loop: true/false
            // - controls: true/false
            $table->json('video_settings')->nullable()->after('video_type');

            // Text overlay แบบมืออาชีพ (JSON)
            // - text: ข้อความ
            // - position: top-left, top-center, top-right, center-left, center, center-right, bottom-left, bottom-center, bottom-right
            // - style: ชื่อ style preset (elegant, modern, bold, minimal)
            // - fontSize: ขนาดตัวอักษร
            // - color: สี
            // - backgroundColor: สีพื้นหลัง
            // - animation: fade-in, slide-in-left, slide-in-right, zoom-in
            $table->json('text_overlay')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            $table->dropColumn([
                'media_type',
                'video_url',
                'video_file',
                'video_type',
                'video_settings',
                'text_overlay'
            ]);
        });
    }
};
