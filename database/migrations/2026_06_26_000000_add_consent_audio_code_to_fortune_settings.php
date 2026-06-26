<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มการตั้งค่า "บังคับฟังเสียงกติกา + กรอกรหัสยืนยัน" ก่อนสร้างบิล
     *
     * - enable_consent_audio_code: เปิด/ปิดระบบ (default ปิด — opt-in)
     * - consent_audio_code_voice_provider: เลือกโมเดล TTS ที่ใช้เจน "คลิปรหัส" ต่อท้ายเสียงกติกา
     *   (default minimax = 32kHz ตรงกับ consent_rules.mp3 ; แต่ ffmpeg concat ทำให้ provider ไหนก็ได้)
     *
     * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) — เช็คแต่ละคอลัมน์ ห้ามใช้ Schema::hasTable()+return
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_consent_audio_code')) {
                $table->boolean('enable_consent_audio_code')
                    ->default(false)
                    ->after('enable_celtic_black_magic_mode');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'consent_audio_code_voice_provider')) {
                $table->string('consent_audio_code_voice_provider', 40)
                    ->nullable()
                    ->default('minimax')
                    ->after('enable_consent_audio_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'consent_audio_code_voice_provider')) {
                $table->dropColumn('consent_audio_code_voice_provider');
            }
            if (Schema::hasColumn('fortune_telling_settings', 'enable_consent_audio_code')) {
                $table->dropColumn('enable_consent_audio_code');
            }
        });
    }
};
