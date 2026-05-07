<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎙️ (2026-05-08) Voice Summary (TTS) — สังเคราะห์เสียงสรุปคำทำนายให้ลูกค้า
 *
 * เปิดใช้เฉพาะ Celtic 99฿ — เป็น VIP perk
 * Provider chain (admin ตั้ง):
 *   1. MiniMax T2A v2  (paid, premium quality, primary)
 *   2. Google Cloud TTS (free 1M chars/month — uses existing google credentials)
 *   3. gTTS (Google Translate, ไม่ต้องมี key — fallback สุดท้าย)
 *   4. OpenAI TTS (paid, fallback ผ่าน API key pool)
 *
 * แม่หมอจันทรา persona — voice_id ตั้งใน MiniMax + Google Cloud
 * Audio file เก็บใน storage/app/public/fortune-voice/{reading_id}-{hash}.mp3
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ============================================================
            // 🎙️ Voice Summary master toggle + tier scope
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_enabled')) {
                $table->boolean('voice_summary_enabled')
                    ->default(false)
                    ->comment('เปิดส่งเสียงสรุปคำทำนายให้ลูกค้า (Celtic 99฿ เท่านั้น)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_tier_scope')) {
                $table->string('voice_summary_tier_scope', 30)
                    ->default('celtic_99_only')
                    ->comment('Scope: celtic_99_only / paid_all / all (default celtic 99 only ตามที่ user request)');
            }

            // ============================================================
            // 🎙️ Provider chain — ลำดับ fallback
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_primary_provider')) {
                $table->string('voice_summary_primary_provider', 30)
                    ->default('minimax')
                    ->comment('Primary TTS: minimax/openai_tts/google_tts/gtts');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_fallback_providers')) {
                $table->json('voice_summary_fallback_providers')
                    ->nullable()
                    ->comment('JSON list ลำดับ fallback (default: [google_tts, gtts])');
            }

            // ============================================================
            // 🎙️ MiniMax (paid, primary)
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'minimax_api_key')) {
                $table->text('minimax_api_key')
                    ->nullable()
                    ->comment('MiniMax API key (JWT token) — ถ้าเว้น จะดึงจาก ai_api_keys pool (provider=minimax, purpose=tts)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'minimax_group_id')) {
                $table->string('minimax_group_id', 100)
                    ->nullable()
                    ->comment('MiniMax group_id (จำเป็นสำหรับ T2A v2 endpoint)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'minimax_model')) {
                $table->string('minimax_model', 50)
                    ->default('speech-02-hd')
                    ->comment('MiniMax model: speech-02-hd / speech-02-turbo');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'minimax_voice_id')) {
                $table->string('minimax_voice_id', 100)
                    ->default('Thai_warmFemaleHost')
                    ->comment('MiniMax voice_id — เลือก voice แม่หมอจันทรา');
            }

            // ============================================================
            // 🎙️ OpenAI TTS (paid fallback ผ่าน pool)
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'openai_tts_model')) {
                $table->string('openai_tts_model', 50)
                    ->default('gpt-4o-mini-tts')
                    ->comment('OpenAI TTS model: gpt-4o-mini-tts / tts-1 / tts-1-hd');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'openai_tts_voice')) {
                $table->string('openai_tts_voice', 50)
                    ->default('shimmer')
                    ->comment('OpenAI voice: alloy/echo/fable/onyx/nova/shimmer');
            }

            // ============================================================
            // 🎙️ Google Cloud TTS (free tier — ใช้ google credentials เดิม)
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'google_tts_voice')) {
                $table->string('google_tts_voice', 100)
                    ->default('th-TH-Neural2-C')
                    ->comment('Google Cloud TTS voice: th-TH-Neural2-C (female) / th-TH-Standard-A');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'google_tts_speaking_rate')) {
                $table->decimal('google_tts_speaking_rate', 3, 2)
                    ->default(0.95)
                    ->comment('Speaking rate 0.25-4.0 (1.0 = normal, แม่หมอช้านิดใช้ 0.95)');
            }

            // ============================================================
            // 🎙️ Voice content control
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_max_chars')) {
                $table->integer('voice_summary_max_chars')
                    ->default(2000)
                    ->comment('จำกัด char ของ summary text ก่อนสังเคราะห์ (cost cap)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_prompt')) {
                $table->text('voice_summary_prompt')
                    ->nullable()
                    ->comment('Custom AI prompt สำหรับ generate spoken summary (ถ้าเว้น = default แม่หมอจันทรา)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_intro_message')) {
                $table->string('voice_summary_intro_message', 500)
                    ->default('🎙️ แม่หมอจันทราอัดเสียงสรุปคำทำนายให้เจ้าชะตาแล้วค่ะ ลองฟังดูนะ ✨')
                    ->comment('ข้อความก่อนส่ง audio (text intro)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        $columns = [
            'voice_summary_enabled',
            'voice_summary_tier_scope',
            'voice_summary_primary_provider',
            'voice_summary_fallback_providers',
            'minimax_api_key',
            'minimax_group_id',
            'minimax_model',
            'minimax_voice_id',
            'openai_tts_model',
            'openai_tts_voice',
            'google_tts_voice',
            'google_tts_speaking_rate',
            'voice_summary_max_chars',
            'voice_summary_prompt',
            'voice_summary_intro_message',
        ];

        $this->safeDropColumn('fortune_telling_settings', $columns);
    }
};
