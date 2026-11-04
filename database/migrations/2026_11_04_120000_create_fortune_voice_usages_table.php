<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔈 (2026-11-04) บันทึกการใช้บริการเสียงของเว็บจันทรา — สร้างเสียง (TTS) / ถอดเสียง (STT) / AI เขียนบทเล่า
 *
 * ทำไม: ai_api_keys บันทึกแค่ LLM · ElevenLabs ไม่มี log การใช้ต่อคำขอเลย
 * โหมดคุยด้วยเสียง (+฿100) ต้องรู้ต้นทุนจริงต่อ pass / ต่อลูกค้า ก่อนปรับราคาหรือเพดาน
 * บันทึกเฉพาะงานที่เสียเงินจริง (สร้างเสียงใหม่ ถอดเสียง เรียก AI) — เสียงจากแคชนับที่ clips.hits แทน
 */
return new class extends Migration
{
    /**
     * สร้างตาราง fortune_voice_usages
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_voice_usages')) {
            return;
        }

        Schema::create('fortune_voice_usages', function (Blueprint $table) {
            $table->id();
            // tts | stt | narration | greeting
            $table->string('kind', 16);
            // chat | greeting | narration | sample | transcribe
            $table->string('purpose', 16)->nullable();
            // elevenlabs | scribe | google_tts | gtts | ai
            $table->string('provider', 24);
            $table->string('persona', 16)->nullable();
            // id ลูกค้าฝั่งเว็บ (user_ref) — ไม่ใช่ users.id ของ Thaiprompt
            $table->string('user_ref', 64)->nullable();
            $table->unsignedInteger('chars')->default(0);
            $table->decimal('seconds', 8, 2)->default(0);
            $table->boolean('success')->default(true);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['kind', 'created_at'], 'fvu_kind_created_idx');
            $table->index(['user_ref', 'created_at'], 'fvu_user_created_idx');
        });
    }

    /**
     * ลบตาราง fortune_voice_usages
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_voice_usages');
    }
};
