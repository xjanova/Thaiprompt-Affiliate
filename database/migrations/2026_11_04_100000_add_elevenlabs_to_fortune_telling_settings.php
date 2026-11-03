<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎭 (2026-11-04) เสียงพากย์ตัวละครต่อ ElevenLabs ตรง (แทน X-DREAMER ที่ยังไม่มีบริการเสียงจริง)
 *
 * key เข้ารหัสด้วย cast 'encrypted' ในโมเดล — คอลัมน์ text เพราะค่าที่เข้ารหัสยาวกว่า key จริงมาก
 * คอลัมน์ xdreamer_* เดิมยังอยู่ (provider X-DREAMER ใน chain เสียงของบอทยังอ้างถึง)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'elevenlabs_api_key', function ($table) {
                $table->text('elevenlabs_api_key')->nullable();
            });
            // ⚠️ text ไม่ใช่ string — ตารางนี้ชนเพดาน row size 65,535 ไบต์ของ MySQL แล้ว (varchar นับเต็มความยาว, text นับแค่ตัวชี้)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'elevenlabs_tts_model', function ($table) {
                $table->text('elevenlabs_tts_model')->nullable();
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', ['elevenlabs_api_key', 'elevenlabs_tts_model']);
    }
};
