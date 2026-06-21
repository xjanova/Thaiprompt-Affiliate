<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม audio_source (tts | upload) + ชื่อไฟล์ต้นฉบับ ในตาราง fortune_system_voice_clips
     *
     * รองรับให้แอดมิน "อัปโหลดไฟล์เสียงเอง" แทนการสร้างด้วย TTS
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_system_voice_clips', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_system_voice_clips', 'audio_source')) {
                $table->string('audio_source', 16)->default('tts')->after('audio_provider')
                    ->comment('tts = สร้างจากข้อความ / upload = ไฟล์ที่แอดมินอัปโหลดเอง');
            }
            if (! Schema::hasColumn('fortune_system_voice_clips', 'audio_original_name')) {
                $table->string('audio_original_name')->nullable()->after('audio_source')
                    ->comment('ชื่อไฟล์ต้นฉบับที่อัปโหลด (เก็บไว้แสดง)');
            }
        });
    }

    /**
     * ลบคอลัมน์
     */
    public function down(): void
    {
        Schema::table('fortune_system_voice_clips', function (Blueprint $table) {
            foreach (['audio_source', 'audio_original_name'] as $col) {
                if (Schema::hasColumn('fortune_system_voice_clips', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
