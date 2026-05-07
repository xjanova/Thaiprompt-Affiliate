<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎙️ (2026-05-08) เพิ่ม voice_audio_* columns ใน fortune_readings
 *
 * วัตถุประสงค์:
 *   - เก็บ random token (32 chars) สำหรับ public URL — กัน enumeration
 *     โดย reading_id (เพราะ sequential)
 *   - เก็บ path + duration ของไฟล์ TTS — admin debug + UI replay
 *   - Track provider used + cost (chars synthesized)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_readings', 'voice_audio_token')) {
                $table->string('voice_audio_token', 64)
                    ->nullable()
                    ->unique()
                    ->comment('Random 32-char token สำหรับ public URL (กัน enumeration)');
            }

            if (! Schema::hasColumn('fortune_readings', 'voice_audio_path')) {
                $table->string('voice_audio_path', 255)
                    ->nullable()
                    ->comment('Relative path ใน storage/app/public — เช่น fortune-voice/{token}.mp3');
            }

            if (! Schema::hasColumn('fortune_readings', 'voice_audio_duration_ms')) {
                $table->integer('voice_audio_duration_ms')
                    ->nullable()
                    ->comment('ระยะเวลา audio (ms) — ใช้ส่งใน LINE message');
            }

            if (! Schema::hasColumn('fortune_readings', 'voice_audio_provider')) {
                $table->string('voice_audio_provider', 30)
                    ->nullable()
                    ->comment('Provider ที่ generate: minimax / google_tts / openai_tts / gtts');
            }

            if (! Schema::hasColumn('fortune_readings', 'voice_audio_chars')) {
                $table->integer('voice_audio_chars')
                    ->nullable()
                    ->comment('จำนวน chars ที่ synthesize (cost tracking)');
            }

            if (! Schema::hasColumn('fortune_readings', 'voice_audio_generated_at')) {
                $table->timestamp('voice_audio_generated_at')
                    ->nullable()
                    ->comment('เวลา generate สำเร็จ');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        $this->safeDropColumn('fortune_readings', [
            'voice_audio_token',
            'voice_audio_path',
            'voice_audio_duration_ms',
            'voice_audio_provider',
            'voice_audio_chars',
            'voice_audio_generated_at',
        ]);
    }
};
