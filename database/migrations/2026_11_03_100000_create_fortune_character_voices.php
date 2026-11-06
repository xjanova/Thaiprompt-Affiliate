<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎭 (2026-11-03) เสียงพากย์ตัวละคร "แม่หมอจันทรา" + "น้องบุษบา" + "แม่หมอโหมดคุณไสย" ผ่าน X-DREAMER (ElevenLabs V3)
 *
 * 1. fortune_telling_settings: key X-DREAMER (เข้ารหัสด้วย cast 'encrypted' ในโมเดล) + เสียงที่เลือกของแต่ละตัวละคร
 * 2. fortune_character_voice_clips: แคชไฟล์เสียง — ข้อความเดิม+เสียงเดิม = ไฟล์เดิม ไม่สั่งสร้างซ้ำ (คุมต้นทุน)
 *    ใช้ทั้งเสียงตัวอย่างในหน้าแอดมิน (purpose=sample) และเสียงข้อความในห้องแชท (purpose=chat)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // text: ค่าที่เข้ารหัสแล้วยาวกว่า key จริงมาก
            $this->safeAddColumn($table, 'fortune_telling_settings', 'xdreamer_api_key', function ($table) {
                $table->text('xdreamer_api_key')->nullable();
            });
            $this->safeAddColumn($table, 'fortune_telling_settings', 'xdreamer_tts_model', function ($table) {
                $table->string('xdreamer_tts_model', 64)->nullable();
            });
            $this->safeAddColumn($table, 'fortune_telling_settings', 'maemor_voice_id', function ($table) {
                $table->string('maemor_voice_id', 64)->nullable();
            });
            $this->safeAddColumn($table, 'fortune_telling_settings', 'busaba_voice_id', function ($table) {
                $table->string('busaba_voice_id', 64)->nullable();
            });
            // แม่หมอโหมดคุณไสย — ห้องคุณไสยมีเสียงของตัวเอง
            $this->safeAddColumn($table, 'fortune_telling_settings', 'kunsai_voice_id', function ($table) {
                $table->string('kunsai_voice_id', 64)->nullable();
            });
            // เพดานตัวอักษรที่สั่งสร้างใหม่ต่อวัน (เฉพาะที่ไม่เจอในแคช) — กันโดนยิงจนเครดิตหมด
            $this->safeAddColumn($table, 'fortune_telling_settings', 'character_voice_daily_char_limit', function ($table) {
                $table->unsignedInteger('character_voice_daily_char_limit')->nullable();
            });
        });

        $this->safeCreateTable('fortune_character_voice_clips', function (Blueprint $table) {
            $table->id();
            $table->string('persona', 20);          // maemor | busaba | kunsai
            $table->string('purpose', 16)->default('chat'); // sample | chat
            $table->string('provider', 32)->default('xdreamer');
            $table->string('model', 64);
            $table->string('voice_id', 64);
            $table->char('text_hash', 64);          // sha256(provider|model|voice|persona|ข้อความ) = กุญแจแคช
            $table->text('text');
            $table->unsignedInteger('chars')->default(0);
            $table->string('status', 16)->default('pending'); // pending | completed | failed
            $table->unsignedBigInteger('remote_generation_id')->nullable();
            $table->string('audio_path', 255)->nullable();
            $table->string('audio_disk', 16)->nullable();
            $table->string('audio_url', 500)->nullable();
            $table->string('error', 500)->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('text_hash', 'fcvc_text_hash_unique');
            $table->index(['persona', 'purpose', 'voice_id'], 'fcvc_persona_purpose_voice_idx');
            $table->index(['status', 'updated_at'], 'fcvc_status_updated_idx');
        });
    }

    public function down(): void
    {
        $this->safeDropTable('fortune_character_voice_clips');
        $this->safeDropColumn('fortune_telling_settings', [
            'xdreamer_api_key',
            'xdreamer_tts_model',
            'maemor_voice_id',
            'busaba_voice_id',
            'kunsai_voice_id',
            'character_voice_daily_char_limit',
        ]);
    }
};
