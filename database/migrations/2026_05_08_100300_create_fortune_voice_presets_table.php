<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎙️ (2026-05-08) Voice Presets — Library ของ voice configs
 *
 * วัตถุประสงค์:
 *   - admin บันทึก voice combinations ที่ใช้บ่อย ๆ (provider+voice_id+model)
 *   - สามารถ import bulk paste จาก MiniMax voice catalog
 *   - มี sample audio ทดสอบฟังก่อนใช้งานจริง
 *   - mark ตัวที่ active สำหรับใช้งานปัจจุบัน
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_voice_presets')) {
            return;
        }

        Schema::create('fortune_voice_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('ชื่อ preset เช่น "แม่หมอใจดี", "เสียงเร็ว"');
            $table->string('provider', 30)->index()->comment('minimax / openai_tts / google_tts / gtts');
            $table->string('voice_id', 100)->comment('voice ID — Thai_warmFemaleHost / shimmer / th-TH-Neural2-C');
            $table->string('model', 50)->nullable()->comment('Model: speech-2.8-hd / gpt-4o-mini-tts / etc');
            $table->string('language', 10)->default('th')->comment('Language code: th/en/zh');
            $table->boolean('is_default')->default(false)->index()->comment('Active ใช้งานปัจจุบัน');
            $table->integer('sort_order')->default(0)->comment('ลำดับแสดงผล');
            $table->text('sample_text')->nullable()->comment('ข้อความที่ใช้ทดสอบ');
            $table->string('sample_audio_path', 255)->nullable()->comment('Path ของ test audio (storage/app/public/...)');
            $table->timestamp('sample_generated_at')->nullable()->comment('เวลา generate sample ล่าสุด');
            $table->text('notes')->nullable()->comment('หมายเหตุ admin');
            $table->timestamps();

            $table->index(['provider', 'is_default']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_voice_presets');
    }
};
